<?php
/*
 * 파일명: history.php
 * 위치: /randombox/
 * 기능: 랜덤박스 구매내역 페이지
 * 작성일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// 검색 조건
// ===================================

/* 기간 검색 */
$fr_date = isset($_GET['fr_date']) ? $_GET['fr_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

/* 박스 검색 */
$search_box = isset($_GET['rb_id']) ? (int)$_GET['rb_id'] : 0;

/* 등급 검색 */
$search_grade = isset($_GET['grade']) ? $_GET['grade'] : '';

// ===================================
// 데이터 조회
// ===================================

/* 검색 조건 생성 */
$sql_search = " WHERE mb_id = '{$member['mb_id']}' ";

if ($fr_date && $to_date) {
    $sql_search .= " AND DATE(rbh_created_at) BETWEEN '{$fr_date}' AND '{$to_date}' ";
}

if ($search_box) {
    $sql_search .= " AND rb_id = '{$search_box}' ";
}

if ($search_grade) {
    $sql_search .= " AND rbi_grade = '{$search_grade}' ";
}

/* 전체 개수 */
$sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history {$sql_search}";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

/* 페이징 */
$page = (int)$_GET['page'];
if ($page < 1) $page = 1;

$rows = 20;
$total_page = ceil($total_count / $rows);
$from_record = ($page - 1) * $rows;

/* 목록 조회 */
$sql = "SELECT * FROM {$g5['g5_prefix']}randombox_history 
        {$sql_search} 
        ORDER BY rbh_created_at DESC 
        LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

/* 통계 정보 */
$sql = "SELECT 
        COUNT(*) as total_count,
        SUM(rb_price) as total_price,
        SUM(rbi_value) as total_value
        FROM {$g5['g5_prefix']}randombox_history 
        {$sql_search}";
$stats = sql_fetch($sql);

/* 박스 목록 (검색용) */
$sql = "SELECT DISTINCT rb_id, rb_name 
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}' 
        ORDER BY rb_name";
$box_result = sql_query($sql);

// ===================================
// 페이지 헤더
// ===================================

$g5['title'] = '구매내역';
include_once(G5_PATH.'/head.php');
?>

<!-- 랜덤박스 CSS -->
<link rel="stylesheet" href="./history.css?v=<?php echo time(); ?>">

<div class="randombox-container">
    
    <h1 class="page-title">구매내역</h1>
    
    <!-- 검색 폼 -->
    <div class="search-form">
        <form method="get" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>기간</label>
                    <input type="date" name="fr_date" value="<?php echo $fr_date; ?>" class="form-control">
                    <span>~</span>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>박스</label>
                    <select name="rb_id" class="form-control">
                        <option value="">전체</option>
                        <?php while ($box = sql_fetch_array($box_result)) : ?>
                        <option value="<?php echo $box['rb_id']; ?>" <?php echo ($search_box == $box['rb_id']) ? 'selected' : ''; ?>>
                            <?php echo $box['rb_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>등급</label>
                    <select name="grade" class="form-control">
                        <option value="">전체</option>
                        <option value="normal" <?php echo ($search_grade == 'normal') ? 'selected' : ''; ?>>일반</option>
                        <option value="rare" <?php echo ($search_grade == 'rare') ? 'selected' : ''; ?>>레어</option>
                        <option value="epic" <?php echo ($search_grade == 'epic') ? 'selected' : ''; ?>>에픽</option>
                        <option value="legendary" <?php echo ($search_grade == 'legendary') ? 'selected' : ''; ?>>레전더리</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">검색</button>
                    <a href="./history.php" class="btn btn-secondary">초기화</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- 통계 정보 -->
    <div class="stat-boxes">
        <div class="stat-box">
            <div class="stat-label">총 구매 횟수</div>
            <div class="stat-value"><?php echo number_format($stats['total_count']); ?>회</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">총 사용 포인트</div>
            <div class="stat-value"><?php echo number_format($stats['total_price']); ?>P</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">총 획득 포인트</div>
            <div class="stat-value"><?php echo number_format($stats['total_value']); ?>P</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">순 사용 포인트</div>
            <div class="stat-value <?php echo ($stats['total_price'] - $stats['total_value'] > 0) ? 'text-danger' : 'text-success'; ?>">
                <?php echo number_format($stats['total_price'] - $stats['total_value']); ?>P
            </div>
        </div>
    </div>
    
    <!-- 구매 내역 목록 -->
    <div class="history-section">
        <?php if ($total_count == 0) : ?>
        <div class="empty-message">
            <p>구매 내역이 없습니다.</p>
        </div>
        <?php else : ?>
        
        <div class="history-list">
            <?php while ($row = sql_fetch_array($result)) : ?>
            <div class="history-item">
                <div class="history-date">
                    <?php echo date('Y-m-d H:i:s', strtotime($row['rbh_created_at'])); ?>
                </div>
                
                <div class="history-content">
                    <div class="box-info">
                        <span class="box-name"><?php echo $row['rb_name']; ?></span>
                        <span class="box-price">-<?php echo number_format($row['rb_price']); ?>P</span>
                    </div>
                    
                    <div class="item-info">
                        <span class="item-grade <?php echo get_grade_class($row['rbi_grade']); ?>">
                            <?php echo get_grade_name($row['rbi_grade']); ?>
                        </span>
                        <span class="item-name"><?php echo $row['rbi_name']; ?></span>
                        <?php if ($row['rbi_value'] > 0) : ?>
                        <span class="item-value">+<?php echo number_format($row['rbi_value']); ?>P</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($row['rbh_status'] == 'gift') : ?>
                <div class="history-badge">선물</div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        
        <!-- 페이징 -->
        <div class="pagination">
            <?php
            $qstr = "fr_date={$fr_date}&to_date={$to_date}&rb_id={$search_box}&grade={$search_grade}";
            echo get_paging(10, $page, $total_page, "./history.php?{$qstr}&page=");
            ?>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- 하단 버튼 -->
    <div class="bottom-buttons">
        <a href="./" class="btn btn-primary">랜덤박스 메인</a>
    </div>
    
</div>


<?php
include_once(G5_PATH.'/tail.php');
?>