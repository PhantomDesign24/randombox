<?php
/*
 * 파일명: get_user_point.php
 * 위치: /randombox/ajax/
 * 기능: 사용자 포인트 실시간 조회 (AJAX)
 * 작성일: 2025-01-04
 */

include_once('../../common.php');

// ===================================
// AJAX 전용
// ===================================

header('Content-Type: application/json');

$response = array(
    'status' => false,
    'point' => 0,
    'msg' => ''
);

// ===================================
// 로그인 확인
// ===================================

if (!$member['mb_id']) {
    $response['msg'] = '로그인이 필요합니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 포인트 조회
// ===================================

/* 최신 포인트 조회 */
$sql = "SELECT mb_point FROM {$g5['member_table']} WHERE mb_id = '{$member['mb_id']}'";
$row = sql_fetch($sql);

if ($row) {
    $response['status'] = true;
    $response['point'] = (int)$row['mb_point'];
} else {
    $response['msg'] = '회원 정보를 찾을 수 없습니다.';
}

// ===================================
// 결과 반환
// ===================================

echo json_encode($response);
?>