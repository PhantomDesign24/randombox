<?php
/*
 * 파일명: get_grade_info.php
 * 위치: /randombox/ajax/
 * 기능: 등급 정보 조회 (AJAX)
 * 작성일: 2025-07-17
 */

include_once('../../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'grades' => array()
);

// 등급 정보 조회 - 등급 테이블이 있다면
$sql = "SELECT * FROM {$g5['g5_prefix']}randombox_grades ORDER BY grade_order";
$result = sql_query($sql, false);

if ($result) {
    // 등급 테이블이 있는 경우
    while ($row = sql_fetch_array($result)) {
        $response['grades'][$row['grade_key']] = array(
            'name' => $row['grade_name'],
            'color' => $row['grade_color'],
            'bg_color' => $row['grade_bg_color'],
            'effect_color' => $row['grade_effect_color'],
            'order' => $row['grade_order']
        );
    }
} else {
    // 등급 테이블이 없는 경우 기본값 사용
    $response['grades'] = array(
        'normal' => array(
            'name' => '일반',
            'color' => '#666666',
            'bg_color' => '#f8f8f8',
            'effect_color' => '#999999',
            'order' => 1
        ),
        'rare' => array(
            'name' => '레어',
            'color' => '#0969da',
            'bg_color' => '#f0f6ff',
            'effect_color' => '#007BFF',
            'order' => 2
        ),
        'epic' => array(
            'name' => '에픽',
            'color' => '#6f42c1',
            'bg_color' => '#f5f3ff',
            'effect_color' => '#8A2BE2',
            'order' => 3
        ),
        'legendary' => array(
            'name' => '레전더리',
            'color' => '#cf222e',
            'bg_color' => '#fff5f5',
            'effect_color' => '#FF0000',
            'order' => 4
        )
    );
    
    // 아이템 테이블에서 사용중인 등급 확인
    $sql = "SELECT DISTINCT rbi_grade FROM {$g5['g5_prefix']}randombox_items WHERE rbi_status = 1";
    $result = sql_query($sql);
    
    $used_grades = array();
    while ($row = sql_fetch_array($result)) {
        $used_grades[] = $row['rbi_grade'];
    }
    
    // 사용중인 등급만 남기기
    foreach ($response['grades'] as $key => $grade) {
        if (!in_array($key, $used_grades) && count($used_grades) > 0) {
            unset($response['grades'][$key]);
        }
    }
}

$response['status'] = true;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>