<?php
/*
 * 파일명: daangn_price_analyzer.php
 * 위치: /daangn_price_analyzer.php
 * 기능: 당근마켓 데이터 수집 및 노이즈 필터링을 통한 정확한 시세 분석
 * 작성일: 2025-01-28
 */

// 에러 표시 (개발 중에만 사용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===================================
// 데이터베이스 설정
// ===================================

// db_config.php가 없는 경우를 위한 기본 설정
if (!function_exists('getDB')) {
    if (!file_exists('db_config.php')) {
        // 직접 연결 설정
        if (!defined('DB_HOST')) {
            define('DB_HOST', 'localhost');
            define('DB_NAME', 'bad');
            define('DB_USER', 'root');
            define('DB_PASS', 'root');
        }
        
        function getDB() {
            try {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                return $pdo;
            } catch (PDOException $e) {
                die('데이터베이스 연결 오류: ' . $e->getMessage());
            }
        }
    } else {
        require_once('db_config.php');
    }
}

// ===================================
// DaangnPriceAnalyzer 클래스
// ===================================

if (!class_exists('DaangnPriceAnalyzer')) {
class DaangnPriceAnalyzer {
    private $pdo;
    private $filter_rules;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initFilterRules();
        $this->createTablesIfNotExist();
    }
    
    // 테이블 생성
    private function createTablesIfNotExist() {
        // daangn_raw_data 테이블
        $sql1 = "CREATE TABLE IF NOT EXISTS daangn_raw_data (
            id INT PRIMARY KEY AUTO_INCREMENT,
            search_keyword VARCHAR(100) NOT NULL,
            region_id INT NOT NULL,
            region_name VARCHAR(100) COMMENT '지역명',
            title VARCHAR(255) NOT NULL,
            price DECIMAL(12,0),
            content TEXT,
            status VARCHAR(20) COMMENT 'Ongoing(판매중)/Closed(거래완료)',
            created_at DATETIME COMMENT '게시글 작성일',
            collected_at DATETIME COMMENT '수집일시',
            url VARCHAR(500),
            is_valid TINYINT DEFAULT 0 COMMENT '유효한 데이터 여부',
            filter_reason VARCHAR(100) COMMENT '필터링된 이유',
            thumbnail VARCHAR(500) COMMENT '썸네일 이미지',
            user_nickname VARCHAR(100) COMMENT '판매자 닉네임',
            INDEX idx_search (search_keyword, region_id),
            INDEX idx_valid (is_valid, search_keyword),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->pdo->exec($sql1);
        } catch (PDOException $e) {
            echo "테이블 생성 오류 (daangn_raw_data): " . $e->getMessage() . "<br>";
        }
        
        // daangn_price_stats 테이블
        $sql2 = "CREATE TABLE IF NOT EXISTS daangn_price_stats (
            id INT PRIMARY KEY AUTO_INCREMENT,
            search_keyword VARCHAR(100) NOT NULL,
            region_id INT NOT NULL,
            region_name VARCHAR(100),
            total_count INT,
            valid_count INT,
            avg_price DECIMAL(12,0),
            min_price DECIMAL(12,0),
            max_price DECIMAL(12,0),
            median_price DECIMAL(12,0),
            q1_price DECIMAL(12,0),
            q3_price DECIMAL(12,0),
            calculated_at DATETIME,
            UNIQUE KEY uk_search_region (search_keyword, region_id, calculated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->pdo->exec($sql2);
        } catch (PDOException $e) {
            echo "테이블 생성 오류 (daangn_price_stats): " . $e->getMessage() . "<br>";
        }
        
        // daangn_regions 테이블 (없는 경우)
        $sql3 = "CREATE TABLE IF NOT EXISTS daangn_regions (
            region_id INT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            depth INT,
            name1 VARCHAR(50),
            name2 VARCHAR(50),
            name3 VARCHAR(50)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->pdo->exec($sql3);
        } catch (PDOException $e) {
            // 테이블이 이미 있을 수 있으므로 무시
        }
    }
    
    // 필터링 규칙 초기화
    private function initFilterRules() {
        $this->filter_rules = [
            'price_filters' => [
                'min_price' => 1000,
                'max_price' => 100000000,
                'special_prices' => [0, 1, 123, 1234, 12345, 111111, 999999999]
            ],
            'title_exclude_keywords' => [
                '삽니다', '사요', '구합니다', '구매', '원해요', '찾아요', '찾습니다',
                '매입', '삼', '구함', '사고싶', '원합', '구해요', '급구'
            ],
            'content_exclude_patterns' => [
                '삽니다', '구매합니다', '높은 가격', '비싸게 삽니다',
                '연락주세요', '문의', '가격 제시', '네고', '흥정'
            ],
            'price_ratio' => [
                'iqr_multiplier' => 1.5
            ]
        ];
    }
    
    // 데이터 수집
    public function collectData($search_keyword, $region_ids = []) {
        $collected_count = 0;
        
        foreach ($region_ids as $region_id) {
            $data = $this->fetchFromDaangn($search_keyword, $region_id);
            
            if ($data && isset($data['allPage']['fleamarketArticles'])) {
                foreach ($data['allPage']['fleamarketArticles'] as $article) {
                    if ($this->saveRawData($search_keyword, $region_id, $article)) {
                        $collected_count++;
                    }
                }
            }
        }
        
        // 데이터 검증 및 필터링
        $this->validateData($search_keyword);
        
        // 통계 계산
        $this->calculateStatistics($search_keyword, $region_ids);
        
        return $collected_count;
    }
    
    // 당근마켓 API 호출
    private function fetchFromDaangn($search_keyword, $region_id) {
        // 프록시 사용
        $proxy_url = "daangn_api_proxy.php?region_id={$region_id}&keyword=" . urlencode($search_keyword);
        
        // 로컬 파일인 경우 file_get_contents 사용
        if (file_exists('daangn_api_proxy.php')) {
            // 현재 출력 버퍼 저장
            $current_output = ob_get_contents();
            if ($current_output !== false) {
                ob_clean();
            }
            
            // GET 파라미터 설정
            $_GET['region_id'] = $region_id;
            $_GET['keyword'] = $search_keyword;
            
            // 프록시 실행
            ob_start();
            include('daangn_api_proxy.php');
            $response = ob_get_clean();
            
            // 이전 출력 복원
            if ($current_output !== false) {
                echo $current_output;
            }
            
            $result = json_decode($response, true);
            
            if ($result && !$result['error'] && isset($result['data'])) {
                return $result['data'];
            }
            
            error_log("프록시 오류: " . ($result['message'] ?? 'Unknown error'));
            return null;
        }
        
        // 프록시 파일이 없는 경우 직접 호출
        $url = "https://www.daangn.com/kr/buy-sell/?in={$region_id}&search=" . urlencode($search_keyword) . "&_data=routes%2Fkr.buy-sell._index";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: ko-KR,ko;q=0.9,en;q=0.8',
            'Referer: https://www.daangn.com/'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        return null;
    }
    
    // 원시 데이터 저장
    private function saveRawData($search_keyword, $region_id, $article) {
        try {
            // 날짜 형식 변환
            $created_at = date('Y-m-d H:i:s', strtotime($article['createdAt']));
            
            // 지역명 추출
            $region_name = isset($article['region']['name']) ? $article['region']['name'] : '';
            
            $sql = "INSERT INTO daangn_raw_data 
                    (search_keyword, region_id, region_name, title, price, content, status, 
                     created_at, collected_at, url, thumbnail, user_nickname)
                    VALUES 
                    (:search_keyword, :region_id, :region_name, :title, :price, :content, :status, 
                     :created_at, NOW(), :url, :thumbnail, :user_nickname)
                    ON DUPLICATE KEY UPDATE 
                    price = VALUES(price),
                    status = VALUES(status),
                    region_name = VALUES(region_name),
                    collected_at = NOW()";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':search_keyword' => $search_keyword,
                ':region_id' => $region_id,
                ':region_name' => $region_name,
                ':title' => $article['title'],
                ':price' => floatval($article['price']),
                ':content' => $article['content'] ?? '',
                ':status' => $article['status'],
                ':created_at' => $created_at,
                ':url' => $article['href'],
                ':thumbnail' => $article['thumbnail'] ?? '',
                ':user_nickname' => isset($article['user']['nickname']) ? $article['user']['nickname'] : ''
            ]);
        } catch (PDOException $e) {
            // 중복 키 오류는 무시
            if ($e->getCode() != 23000) {
                echo "데이터 저장 오류: " . $e->getMessage() . "<br>";
            }
            return false;
        }
    }
    
    // 데이터 검증
    public function validateData($search_keyword) {
        // 먼저 모든 데이터를 무효로 초기화
        $sql = "UPDATE daangn_raw_data SET is_valid = 0, filter_reason = NULL WHERE search_keyword = :keyword";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => $search_keyword]);
        
        // 1단계: 기본 필터링
        $this->applyBasicFilters($search_keyword);
        
        // 2단계: 통계적 이상치 제거
        $this->applyStatisticalFilters($search_keyword);
        
        // 3단계: 컨텍스트 기반 필터링
        $this->applyContextFilters($search_keyword);
    }
    
    // 기본 필터링
    private function applyBasicFilters($search_keyword) {
        // 가격 범위 필터
        $sql = "UPDATE daangn_raw_data 
                SET is_valid = 0, filter_reason = '가격 범위 벗어남'
                WHERE search_keyword = :keyword
                AND (price < :min_price OR price > :max_price OR price IN (" . 
                implode(',', $this->filter_rules['price_filters']['special_prices']) . "))";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':keyword' => $search_keyword,
            ':min_price' => $this->filter_rules['price_filters']['min_price'],
            ':max_price' => $this->filter_rules['price_filters']['max_price']
        ]);
        
        // 제목 필터 (삽니다 글 제외)
        foreach ($this->filter_rules['title_exclude_keywords'] as $exclude_word) {
            $sql = "UPDATE daangn_raw_data 
                    SET is_valid = 0, filter_reason = '구매 희망 글'
                    WHERE search_keyword = :keyword
                    AND title LIKE :exclude_word
                    AND filter_reason IS NULL";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':keyword' => $search_keyword,
                ':exclude_word' => '%' . $exclude_word . '%'
            ]);
        }
        
        // 정상적인 판매글로 표시
        $sql = "UPDATE daangn_raw_data 
                SET is_valid = 1
                WHERE search_keyword = :keyword
                AND filter_reason IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => $search_keyword]);
    }
    
    // 통계적 이상치 제거
    private function applyStatisticalFilters($search_keyword) {
        // 유효한 데이터 수 확인
        $sql = "SELECT COUNT(*) FROM daangn_raw_data WHERE search_keyword = :keyword AND is_valid = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => $search_keyword]);
        $count = $stmt->fetchColumn();
        
        if ($count < 4) return; // 데이터가 너무 적으면 통계 필터 적용 안함
        
        // 간단한 사분위수 계산
        $sql = "SELECT price FROM daangn_raw_data 
                WHERE search_keyword = :keyword AND is_valid = 1 
                ORDER BY price";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => $search_keyword]);
        $prices = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $q1_index = floor(count($prices) * 0.25);
        $q3_index = floor(count($prices) * 0.75);
        
        $q1 = $prices[$q1_index];
        $q3 = $prices[$q3_index];
        $iqr = $q3 - $q1;
        
        $lower_bound = $q1 - ($iqr * $this->filter_rules['price_ratio']['iqr_multiplier']);
        $upper_bound = $q3 + ($iqr * $this->filter_rules['price_ratio']['iqr_multiplier']);
        
        // 이상치 제거
        $sql = "UPDATE daangn_raw_data 
                SET is_valid = 0, filter_reason = '통계적 이상치'
                WHERE search_keyword = :keyword
                AND is_valid = 1
                AND (price < :lower_bound OR price > :upper_bound)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':keyword' => $search_keyword,
            ':lower_bound' => $lower_bound,
            ':upper_bound' => $upper_bound
        ]);
    }
    
    // 컨텍스트 기반 필터링
    private function applyContextFilters($search_keyword) {
        $product_rules = [
            'rtx3070' => ['min' => 200000, 'max' => 500000],
            'rtx3080' => ['min' => 400000, 'max' => 800000],
            'rtx4060' => ['min' => 350000, 'max' => 600000],
            'iphone15' => ['min' => 800000, 'max' => 1500000],
            'iphone14' => ['min' => 600000, 'max' => 1200000],
        ];
        
        $keyword_lower = strtolower($search_keyword);
        foreach ($product_rules as $product => $range) {
            if (strpos($keyword_lower, $product) !== false) {
                $sql = "UPDATE daangn_raw_data 
                        SET is_valid = 0, filter_reason = '제품별 가격 범위 벗어남'
                        WHERE search_keyword = :keyword
                        AND is_valid = 1
                        AND (price < :min OR price > :max)";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':keyword' => $search_keyword,
                    ':min' => $range['min'],
                    ':max' => $range['max']
                ]);
                break;
            }
        }
    }
    
    // 통계 계산
    public function calculateStatistics($search_keyword, $region_ids) {
        foreach ($region_ids as $region_id) {
            // 유효한 데이터만으로 통계 계산
            $sql = "SELECT 
                        COUNT(*) as total_count,
                        SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_count,
                        AVG(CASE WHEN is_valid = 1 THEN price END) as avg_price,
                        MIN(CASE WHEN is_valid = 1 THEN price END) as min_price,
                        MAX(CASE WHEN is_valid = 1 THEN price END) as max_price
                    FROM daangn_raw_data
                    WHERE search_keyword = :keyword
                    AND region_id = :region_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':keyword' => $search_keyword,
                ':region_id' => $region_id
            ]);
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats['valid_count'] == 0) continue;
            
            // 중간값 계산
            $sql = "SELECT price FROM daangn_raw_data
                    WHERE search_keyword = :keyword
                    AND region_id = :region_id
                    AND is_valid = 1
                    ORDER BY price";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':keyword' => $search_keyword,
                ':region_id' => $region_id
            ]);
            
            $prices = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $median = $prices[floor(count($prices) / 2)] ?? 0;
            
            // 지역명 조회
            $region_name = $this->getRegionName($region_id);
            
            // 통계 저장
            try {
                $sql = "INSERT INTO daangn_price_stats 
                        (search_keyword, region_id, region_name, total_count, valid_count, 
                         avg_price, min_price, max_price, median_price, calculated_at)
                        VALUES 
                        (:keyword, :region_id, :region_name, :total_count, :valid_count,
                         :avg_price, :min_price, :max_price, :median_price, NOW())";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':keyword' => $search_keyword,
                    ':region_id' => $region_id,
                    ':region_name' => $region_name,
                    ':total_count' => $stats['total_count'],
                    ':valid_count' => $stats['valid_count'],
                    ':avg_price' => round($stats['avg_price'] ?? 0),
                    ':min_price' => $stats['min_price'] ?? 0,
                    ':max_price' => $stats['max_price'] ?? 0,
                    ':median_price' => $median
                ]);
            } catch (PDOException $e) {
                // 중복 키 오류는 무시
                if ($e->getCode() != 23000) {
                    echo "통계 저장 오류: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
    
    // 지역명 조회
    private function getRegionName($region_id) {
        try {
            $sql = "SELECT full_name FROM daangn_regions WHERE region_id = :region_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':region_id' => $region_id]);
            $name = $stmt->fetchColumn();
            return $name ?: "지역ID: {$region_id}";
        } catch (PDOException $e) {
            return "지역ID: {$region_id}";
        }
    }
    
    // 통계 조회
    public function getStatistics($search_keyword, $limit = 20) {
        $sql = "SELECT * FROM daangn_price_stats 
                WHERE search_keyword = :keyword
                AND calculated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                ORDER BY avg_price ASC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':keyword', $search_keyword);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 필터링 통계
    public function getFilterStatistics($search_keyword) {
        $sql = "SELECT 
                    filter_reason,
                    COUNT(*) as count
                FROM daangn_raw_data
                WHERE search_keyword = :keyword
                AND is_valid = 0
                AND filter_reason IS NOT NULL
                GROUP BY filter_reason";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':keyword' => $search_keyword]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
} // class_exists 체크 종료

// ===================================
// 메인 실행 부분
// ===================================

try {
    // 데이터베이스 연결
    $pdo = getDB();
    
    // 분석기 초기화
    $analyzer = new DaangnPriceAnalyzer($pdo);
    
    // 실행할 작업
    $action = $_GET['action'] ?? 'view';
    $search_keyword = $_GET['keyword'] ?? '';
    
    if ($action === 'collect' && $search_keyword) {
        // 지역 ID 목록 (예시)
        $region_ids = [1568, 1565, 4430, 4429]; // 대화동, 탄현동, 주엽동, 일산동
        
        // 데이터 수집
        $count = $analyzer->collectData($search_keyword, $region_ids);
        
        // AJAX 요청인 경우 JSON 응답
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'collected' => $count,
                'message' => "{$count}개의 데이터를 수집했습니다."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 일반 요청인 경우 통계 페이지로 리다이렉트
        header("Location: ?keyword=" . urlencode($search_keyword) . "&action=stats&collected=" . $count);
        exit;
    }
    elseif ($action === 'stats' && $search_keyword) {
        // 통계 조회
        $stats = $analyzer->getStatistics($search_keyword);
        $filter_stats = $analyzer->getFilterStatistics($search_keyword);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'statistics' => $stats,
            'filter_statistics' => $filter_stats
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
} catch (Exception $e) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// HTML 뷰
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>당근마켓 가격 분석기</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .stats-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .stats-table th, .stats-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .stats-table th { background-color: #f2f2f2; }
        .filter-info { background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .price { font-weight: bold; color: #ff6f0f; }
        .search-form { background: #f0f0f0; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; background: #ff6f0f; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #e55f00; }
        .error { color: red; padding: 10px; background: #ffe0e0; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>당근마켓 가격 분석기</h1>
        
        <?php if (!$pdo): ?>
            <div class="error">
                데이터베이스 연결에 실패했습니다. db_config.php 파일을 확인해주세요.
            </div>
        <?php else: ?>
        
        <div class="search-form">
            <form method="get">
                <input type="text" name="keyword" placeholder="검색어 입력 (예: RTX3070)" 
                       value="<?php echo htmlspecialchars($search_keyword); ?>" size="30">
                <button type="submit" name="action" value="collect" class="btn">데이터 수집</button>
                <button type="submit" name="action" value="stats" class="btn">통계 보기</button>
                <button type="submit" name="action" value="debug" class="btn" style="background: #6c757d;">디버그</button>
            </form>
        </div>
        
        <?php if (isset($_GET['collected'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                ✅ <?php echo (int)$_GET['collected']; ?>개의 데이터를 성공적으로 수집했습니다!
            </div>
        <?php endif; ?>
        
        <?php if ($search_keyword && $action === 'stats'): ?>
            <?php
            $stats = $analyzer->getStatistics($search_keyword);
            $filter_stats = $analyzer->getFilterStatistics($search_keyword);
            ?>
            
            <?php if (count($filter_stats) > 0): ?>
            <h2>필터링 정보</h2>
            <div class="filter-info">
                <?php foreach ($filter_stats as $filter): ?>
                    <p><?php echo htmlspecialchars($filter['filter_reason']); ?>: 
                       <?php echo number_format($filter['count']); ?>개 제외</p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <h2>지역별 가격 통계</h2>
            <?php if (count($stats) > 0): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>지역</th>
                        <th>전체 매물</th>
                        <th>판매중</th>
                        <th>거래완료</th>
                        <th>유효 매물</th>
                        <th>최저가</th>
                        <th>평균가</th>
                        <th>중간값</th>
                        <th>최고가</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['region_name']); ?></td>
                        <td><?php echo number_format($stat['total_count']); ?></td>
                        <td>
                            <?php 
                            // 판매중 개수 조회
                            $sql = "SELECT COUNT(*) FROM daangn_raw_data 
                                    WHERE search_keyword = :keyword 
                                    AND region_id = :region_id 
                                    AND status = 'Ongoing'";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                ':keyword' => $search_keyword,
                                ':region_id' => $stat['region_id']
                            ]);
                            echo number_format($stmt->fetchColumn());
                            ?>
                        </td>
                        <td>
                            <?php 
                            // 거래완료 개수 조회
                            $sql = "SELECT COUNT(*) FROM daangn_raw_data 
                                    WHERE search_keyword = :keyword 
                                    AND region_id = :region_id 
                                    AND status = 'Closed'";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                ':keyword' => $search_keyword,
                                ':region_id' => $stat['region_id']
                            ]);
                            echo number_format($stmt->fetchColumn());
                            ?>
                        </td>
                        <td><?php echo number_format($stat['valid_count']); ?></td>
                        <td class="price"><?php echo number_format($stat['min_price']); ?>원</td>
                        <td class="price"><?php echo number_format($stat['avg_price']); ?>원</td>
                        <td class="price"><?php echo number_format($stat['median_price']); ?>원</td>
                        <td class="price"><?php echo number_format($stat['max_price']); ?>원</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h3>최근 수집된 데이터 샘플</h3>
            <?php
            // 최근 데이터 몇 개 보여주기
            $sql = "SELECT * FROM daangn_raw_data 
                    WHERE search_keyword = :keyword 
                    AND is_valid = 1 
                    ORDER BY collected_at DESC 
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':keyword' => $search_keyword]);
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (count($samples) > 0): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>제목</th>
                        <th>가격</th>
                        <th>지역</th>
                        <th>상태</th>
                        <th>작성일</th>
                        <th>링크</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($samples as $sample): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(mb_substr($sample['title'], 0, 30)); ?>...</td>
                        <td class="price"><?php echo number_format($sample['price']); ?>원</td>
                        <td><?php echo htmlspecialchars($sample['region_name']); ?></td>
                        <td>
                            <?php if ($sample['status'] == 'Ongoing'): ?>
                                <span style="color: green;">판매중</span>
                            <?php else: ?>
                                <span style="color: gray;">거래완료</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('m-d H:i', strtotime($sample['created_at'])); ?></td>
                        <td><a href="<?php echo htmlspecialchars($sample['url']); ?>" target="_blank">보기</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <?php else: ?>
                <p>통계 데이터가 없습니다. 먼저 데이터를 수집해주세요.</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($search_keyword && $action === 'debug'): ?>
            <h2>디버그 정보</h2>
            <?php
            // 전체 데이터 개수 확인
            $sql = "SELECT COUNT(*) as total, 
                           SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
                           SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed,
                           SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid
                    FROM daangn_raw_data 
                    WHERE search_keyword = :keyword";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':keyword' => $search_keyword]);
            $debug_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div class="filter-info">
                <h3>데이터 수집 현황</h3>
                <p>전체 데이터: <?php echo $debug_stats['total']; ?>개</p>
                <p>판매중(Ongoing): <?php echo $debug_stats['ongoing']; ?>개</p>
                <p>거래완료(Closed): <?php echo $debug_stats['closed']; ?>개</p>
                <p>유효 데이터: <?php echo $debug_stats['valid']; ?>개</p>
            </div>
            
            <h3>최근 수집 데이터 (원시)</h3>
            <?php
            $sql = "SELECT * FROM daangn_raw_data 
                    WHERE search_keyword = :keyword 
                    ORDER BY collected_at DESC 
                    LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':keyword' => $search_keyword]);
            $raw_samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php foreach ($raw_samples as $sample): ?>
            <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
                <strong>제목:</strong> <?php echo htmlspecialchars($sample['title']); ?><br>
                <strong>가격:</strong> <?php echo number_format($sample['price']); ?>원<br>
                <strong>상태:</strong> <?php echo $sample['status']; ?><br>
                <strong>지역:</strong> <?php echo $sample['region_name']; ?> (ID: <?php echo $sample['region_id']; ?>)<br>
                <strong>유효여부:</strong> <?php echo $sample['is_valid'] ? 'O' : 'X'; ?><br>
                <?php if ($sample['filter_reason']): ?>
                <strong>필터 이유:</strong> <?php echo $sample['filter_reason']; ?><br>
                <?php endif; ?>
                <strong>URL:</strong> <a href="<?php echo htmlspecialchars($sample['url']); ?>" target="_blank">링크</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</body>
</html>