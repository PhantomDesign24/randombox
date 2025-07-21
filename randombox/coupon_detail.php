<?php
/*
 * 파일명: coupon_detail.php
 * 위치: /randombox/
 * 기능: 교환권 상세 정보 조회 (AJAX)
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'message' => '',
    'data' => null
);

// 로그인 체크
if (!$member['mb_id']) {
    $response['message'] = '로그인이 필요합니다.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$rmc_id = (int)$_GET['rmc_id'];
if (!$rmc_id) {
    $response['message'] = '잘못된 접근입니다.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 교환권 정보 조회
$sql = "SELECT mc.*, ct.*, cc.rcc_code, cc.rcc_pin, h.rbh_created_at
        FROM {$g5['g5_prefix']}randombox_member_coupons mc
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_types ct ON mc.rct_id = ct.rct_id
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_codes cc ON mc.rcc_id = cc.rcc_id
        LEFT JOIN {$g5['g5_prefix']}randombox_history h ON mc.rbh_id = h.rbh_id
        WHERE mc.rmc_id = '{$rmc_id}'
        AND mc.mb_id = '{$member['mb_id']}'";
$coupon = sql_fetch($sql);

if (!$coupon) {
    $response['message'] = '존재하지 않는 교환권입니다.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 이미지 처리
$coupon_img = G5_URL.'/randombox/img/item-default.png';
if ($coupon['rct_image'] && file_exists(G5_DATA_PATH.'/randombox/coupon/'.$coupon['rct_image'])) {
    $coupon_img = G5_DATA_URL.'/randombox/coupon/'.$coupon['rct_image'];
}

// 응답 데이터 구성
$data = array(
    'rmc_id' => $coupon['rmc_id'],
    'name' => $coupon['rct_name'],
    'type' => $coupon['rct_type'],
    'exchange_item' => $coupon['rct_exchange_item'],
    'value' => $coupon['rct_value'],
    'desc' => nl2br($coupon['rct_desc']),
    'image' => $coupon_img,
    'status' => $coupon['rmc_status'],
    'expire_date' => $coupon['rmc_expire_date'],
    'created_at' => date('Y-m-d H:i', strtotime($coupon['rbh_created_at'] ?: $coupon['rmc_created_at'])),
    'used_at' => $coupon['rmc_used_at'] ? date('Y-m-d H:i', strtotime($coupon['rmc_used_at'])) : null
);

// 기프티콘인 경우 코드 정보 추가
if ($coupon['rct_type'] == 'gifticon' && $coupon['rmc_status'] == 'active') {
    $data['code'] = $coupon['rcc_code'];
    $data['pin'] = $coupon['rcc_pin'];
}

$response['status'] = true;
$response['data'] = $data;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>