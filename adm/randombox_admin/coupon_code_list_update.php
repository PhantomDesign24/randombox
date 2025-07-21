<?php
/*
 * 파일명: coupon_code_list_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 코드 일괄 처리
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'd');

check_admin_token();

$rct_id = (int)$_POST['rct_id'];

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택삭제") {
    
    for ($i=0; $i<count($_POST['chk']); $i++) {
        $rcc_id = (int)$_POST['chk'][$i];
        
        // 사용된 코드인지 확인
        $sql = "SELECT rcc_status FROM {$g5['g5_prefix']}randombox_coupon_codes WHERE rcc_id = '{$rcc_id}'";
        $code = sql_fetch($sql);
        
        if ($code['rcc_status'] == 'used') {
            continue; // 사용된 코드는 삭제하지 않음
        }
        
        // 코드 삭제
        sql_query("DELETE FROM {$g5['g5_prefix']}randombox_coupon_codes WHERE rcc_id = '{$rcc_id}'");
    }
    
} else if ($_POST['act_button'] == "선택만료처리") {
    
    for ($i=0; $i<count($_POST['chk']); $i++) {
        $rcc_id = (int)$_POST['chk'][$i];
        
        // 만료 처리
        sql_query("UPDATE {$g5['g5_prefix']}randombox_coupon_codes SET rcc_status = 'expired' WHERE rcc_id = '{$rcc_id}' AND rcc_status = 'available'");
    }
}

goto_url('./coupon_code_list.php?rct_id='.$rct_id.'&'.$qstr);
?>