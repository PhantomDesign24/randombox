<?php
/*
 * 파일명: daangn_region_api.php
 * 위치: /api/daangn_region_api.php
 * 기능: 특정 지역 ID로 당근마켓 API 호출하여 지역 정보 반환
 * 작성일: 2025-01-28
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ===================================
// 파라미터 처리
// ===================================

$region_id = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;

if ($region_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => '유효한 지역 ID를 입력해주세요.'
    ]);
    exit;
}

// ===================================
// 당근마켓 API 호출
// ===================================

try {
    // 당근마켓 URL 구성 - 해당 지역 ID로 검색
    $url = "https://www.daangn.com/kr/buy-sell/?in={$region_id}&_data=routes%2Fkr.buy-sell._index";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept: application/json, text/plain, */*',
        'Accept-Language: ko-KR,ko;q=0.9,en;q=0.8',
        'Referer: https://www.daangn.com/',
        'X-Requested-With: XMLHttpRequest'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('CURL 오류: ' . $error);
    }
    
    if ($http_code !== 200) {
        // 404인 경우 해당 ID에 지역이 없음
        if ($http_code === 404) {
            echo json_encode([
                'success' => false,
                'message' => '해당 ID의 지역 정보가 없습니다.',
                'region_id' => $region_id
            ]);
            exit;
        }
        
        throw new Exception('HTTP 오류: ' . $http_code);
    }
    
    // JSON 파싱
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON 파싱 오류: ' . json_last_error_msg());
    }
    
    // ===================================
    // 지역 정보 추출
    // ===================================
    
    $region_info = null;
    
    // regionFilterOptions에서 현재 지역 정보 확인
    if (isset($data['regionFilterOptions']['region'])) {
        $region = $data['regionFilterOptions']['region'];
        
        // 요청한 ID와 일치하는지 확인
        if ($region['id'] == $region_id) {
            $region_info = [
                'id' => $region['id'],
                'depth' => $region['depth'],
                'name' => $region['name'],
                'name1' => $region['name1'] ?? null,
                'name2' => $region['name2'] ?? null,
                'name3' => $region['name3'] ?? null,
                'name1_id' => $region['name1Id'] ?? null,
                'name2_id' => $region['name2Id'] ?? null,
                'name3_id' => $region['name3Id'] ?? null
            ];
        }
    }
    
    // 못 찾은 경우 다른 위치에서도 확인
    if (!$region_info) {
        // parentRegion 확인
        if (isset($data['regionFilterOptions']['parentRegion']) && 
            $data['regionFilterOptions']['parentRegion']['id'] == $region_id) {
            $region = $data['regionFilterOptions']['parentRegion'];
            $region_info = [
                'id' => $region['id'],
                'depth' => $region['depth'],
                'name' => $region['name'],
                'name1' => $region['name1'] ?? null,
                'name2' => $region['name2'] ?? null,
                'name3' => $region['name3'] ?? null,
                'name1_id' => $region['name1Id'] ?? null,
                'name2_id' => $region['name2Id'] ?? null,
                'name3_id' => $region['name3Id'] ?? null
            ];
        }
        
        // siblingRegions에서 확인
        if (!$region_info && isset($data['regionFilterOptions']['siblingRegions'])) {
            foreach ($data['regionFilterOptions']['siblingRegions'] as $region) {
                if ($region['id'] == $region_id) {
                    $region_info = [
                        'id' => $region['id'],
                        'depth' => $region['depth'],
                        'name' => $region['name'],
                        'name1' => $region['name1'] ?? null,
                        'name2' => $region['name2'] ?? null,
                        'name3' => $region['name3'] ?? null,
                        'name1_id' => $region['name1Id'] ?? null,
                        'name2_id' => $region['name2Id'] ?? null,
                        'name3_id' => $region['name3Id'] ?? null
                    ];
                    break;
                }
            }
        }
    }
    
    // 결과 반환
    if ($region_info) {
        echo json_encode([
            'success' => true,
            'region' => $region_info,
            'region_id' => $region_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '지역 정보를 찾을 수 없습니다.',
            'region_id' => $region_id
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'region_id' => $region_id
    ]);
}
?>