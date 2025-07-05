<?php
/*
 * 파일명: get_user_point.php
 * 위치: /randombox/ajax/
 * 기능: 사용자 포인트 조회 (AJAX)
 * 작성일: 2025-01-04
 */

include_once('../../common.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'point' => 0
);

if ($member['mb_id']) {
    // 최신 포인트 조회
    $sql = "SELECT mb_point FROM {$g5['member_table']} WHERE mb_id = '{$member['mb_id']}'";
    $mb = sql_fetch($sql);
    
    $response['status'] = true;
    $response['point'] = (int)$mb['mb_point'];
}

echo json_encode($response);
?>