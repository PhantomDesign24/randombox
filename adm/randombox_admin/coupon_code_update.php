<?php
/*
 * 파일명: coupon_code_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 개별 코드 처리 (만료 등)
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$mode = $_GET['mode'];
$rcc_id = (int)$_GET['rcc_id'];
$rct_id = (int)$_GET['rct_id'];

if (!$rcc_id) {
    alert('코드를 선택해 주세요.');
}

if ($mode == 'expire') {
    // 만료 처리
    sql_query("UPDATE {$g5['g5_prefix']}randombox_coupon_codes SET rcc_status = 'expired' WHERE rcc_id = '{$rcc_id}' AND rcc_status = 'available'");
    
    alert('만료 처리되었습니다.', './coupon_code_list.php?rct_id='.$rct_id);
}

alert('잘못된 접근입니다.');
?>