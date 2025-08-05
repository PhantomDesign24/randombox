<?php
/*
 * 파일명: get_regions.php
 * 위치: /get_regions.php
 * 기능: 당근마켓 지역 정보 조회 API
 * 작성일: 2025-01-28
 */

// 그누보드5 공통 파일
include_once('./_common.php');

// ===================================
// 초기 설정
// ===================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // ===================================
    // 파라미터 처리
    // ===================================
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $depth = isset($_GET['depth']) ? intval($_GET['depth']) : 0;
    $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
    $all_sub = isset($_GET['all_sub']) ? intval($_GET['all_sub']) : 0;
    
    // 디버깅 정보
    $debug = [
        'search' => $search,
        'depth' => $depth,
        'parent_id' => $parent_id,
        'all_sub' => $all_sub
    ];
    
    // ===================================
    // 쿼리 작성
    // ===================================
    if ($all_sub && $parent_id && $depth) {
        // 하위 모든 지역 가져오기
        if ($depth == 1) {
            // 시/도의 모든 동/읍/면 가져오기
            $sql = "SELECT region_id, name3 as name, full_name 
                    FROM daangn_regions 
                    WHERE depth = 3 
                      AND name1_id = {$parent_id}
                    ORDER BY name2, name3";
        } else if ($depth == 2) {
            // 구/군의 모든 동/읍/면 가져오기
            $sql = "SELECT region_id, name3 as name, full_name 
                    FROM daangn_regions 
                    WHERE depth = 3 
                      AND name2_id = {$parent_id}
                    ORDER BY name3";
        }
        
        $result = sql_query($sql);
        $debug['sql'] = $sql;
    } else if ($search) {
        // 검색어가 있는 경우 - 전체 지역에서 검색
        $sql = "SELECT region_id, depth, name1, name2, name3, full_name 
                FROM daangn_regions 
                WHERE full_name LIKE '%{$search}%' 
                   OR name1 LIKE '%{$search}%' 
                   OR name2 LIKE '%{$search}%' 
                   OR name3 LIKE '%{$search}%'
                ORDER BY 
                    CASE 
                        WHEN full_name = '{$search}' THEN 0
                        WHEN name3 = '{$search}' THEN 1
                        WHEN name2 = '{$search}' THEN 2
                        WHEN name1 = '{$search}' THEN 3
                        ELSE 4
                    END,
                    full_name
                LIMIT 50";
        
        $result = sql_query($sql);
        $debug['sql'] = $sql;
    } else if ($depth > 0) {
        // depth별 조회
        switch($depth) {
            case 1:
                // 시/도 목록
                $sql = "SELECT DISTINCT name1_id as region_id, name1 as name 
                        FROM daangn_regions 
                        WHERE depth >= 1 AND name1_id IS NOT NULL
                        ORDER BY name1";
                break;
                
            case 2:
                // 구/군 목록
                $sql = "SELECT DISTINCT name2_id as region_id, name2 as name 
                        FROM daangn_regions 
                        WHERE depth >= 2 
                          AND name1_id = {$parent_id}
                          AND name2_id IS NOT NULL
                        ORDER BY name2";
                break;
                
            case 3:
                // 동/읍/면 목록
                $sql = "SELECT region_id, name3 as name, full_name
                        FROM daangn_regions 
                        WHERE depth = 3 
                          AND name2_id = {$parent_id}
                        ORDER BY name3";
                break;
        }
        
        $result = sql_query($sql);
        $debug['sql'] = $sql;
    } else {
        // 인기 지역 또는 기본 지역 표시
        $sql = "SELECT region_id, depth, name1, name2, name3, full_name 
                FROM daangn_regions 
                WHERE depth = 3 
                  AND (
                    full_name LIKE '%강남%' 
                    OR full_name LIKE '%서초%' 
                    OR full_name LIKE '%송파%'
                    OR full_name LIKE '%일산%'
                    OR full_name LIKE '%분당%'
                    OR full_name LIKE '%판교%'
                  )
                ORDER BY full_name
                LIMIT 20";
        
        $result = sql_query($sql);
        $debug['sql'] = $sql;
    }
    
    // ===================================
    // 결과 처리
    // ===================================
    $regions = array();
    while ($row = sql_fetch_array($result)) {
        $regions[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $regions,
        'count' => count($regions),
        'debug' => $debug
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '지역 정보 조회 중 오류가 발생했습니다.',
        'message' => $e->getMessage(),
        'debug' => isset($debug) ? $debug : null
    ], JSON_UNESCAPED_UNICODE);
}
?>