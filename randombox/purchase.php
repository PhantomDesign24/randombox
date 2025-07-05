<?php
/*
 * 파일명: purchase.php
 * 위치: /randombox/ajax/
 * 기능: 랜덤박스 구매 처리 (AJAX)
 * 작성일: 2025-01-04
 */

include_once('../../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

// ===================================
// AJAX 전용
// ===================================

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'msg' => '',
    'item' => null
);

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['msg'] = '잘못된 접근입니다.';
    echo json_encode($response);
    exit;
}

// 로그인 체크
if (!$member['mb_id']) {
    $response['msg'] = '로그인이 필요합니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 입력값 검증
// ===================================

$rb_id = (int)$_POST['rb_id'];

if (!$rb_id) {
    $response['msg'] = '박스를 선택해 주세요.';
    echo json_encode($response);
    exit;
}

// ===================================
// 구매 처리
// ===================================

/* 구매 처리 */
$result = purchase_randombox($rb_id, $member['mb_id']);

if ($result['status']) {
    // 성공
    $item = $result['item'];
    
    // 이미지 URL 처리
    if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
        $item['image'] = G5_DATA_URL.'/randombox/item/'.$item['rbi_image'];
    } else {
        $item['image'] = G5_URL.'/randombox/img/item-default.png';
    }
    
    $response['status'] = true;
    $response['msg'] = $result['msg'];
    $response['item'] = $item;
    
} else {
    // 실패
    $response['msg'] = $result['msg'];
}

// ===================================
// 결과 반환
// ===================================

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>