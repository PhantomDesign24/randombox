<?php
/*
 * 파일명: ajax.coupon_stock.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 재고 확인 (AJAX)
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'data' => null
);

// 관리자 체크
if (!$is_admin) {
    echo json_encode($response);
    exit;
}

$rct_id = (int)$_GET['rct_id'];
if (!$rct_id) {
    echo json_encode($response);
    exit;
}

// 교환권 타입 정보 조회
$sql = "SELECT * FROM {$g5['g5_prefix']}randombox_coupon_types WHERE rct_id = '{$rct_id}'";
$coupon_type = sql_fetch($sql);

if (!$coupon_type) {
    echo json_encode($response);
    exit;
}

$data = array(
    'type' => $coupon_type['rct_type'],
    'name' => $coupon_type['rct_name']
);

if ($coupon_type['rct_type'] == 'gifticon') {
    // 기프티콘인 경우 코드 재고 확인
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN rcc_status = 'available' THEN 1 ELSE 0 END) as available
            FROM {$g5['g5_prefix']}randombox_coupon_codes 
            WHERE rct_id = '{$rct_id}'";
    $stock = sql_fetch($sql);
    
    $data['total'] = (int)$stock['total'];
    $data['available'] = (int)$stock['available'];
} else {
    // 교환용은 재고 무제한
    $data['total'] = -1;
    $data['available'] = -1;
}

$response['status'] = true;
$response['data'] = $data;

echo json_encode($response);
?>