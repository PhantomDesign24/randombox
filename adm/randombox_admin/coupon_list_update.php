<?php
/*
 * 파일명: coupon_list_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 타입 일괄 처리
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'd');

check_admin_token();

if (!count($_POST['chk'])) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택삭제") {
    
    for ($i=0; $i<count($_POST['chk']); $i++) {
        $rct_id = (int)$_POST['chk'][$i];
        
        // 관련 데이터 확인
        $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_member_coupons WHERE rct_id = '{$rct_id}'";
        $row = sql_fetch($sql);
        
        if ($row['cnt'] > 0) {
            alert("회원이 보유한 교환권이 있는 타입은 삭제할 수 없습니다.\\n\\n교환권 타입 ID: {$rct_id}");
        }
        
        // 관련 코드 삭제
        sql_query("DELETE FROM {$g5['g5_prefix']}randombox_coupon_codes WHERE rct_id = '{$rct_id}'");
        
        // 이미지 삭제
        $sql = "SELECT rct_image FROM {$g5['g5_prefix']}randombox_coupon_types WHERE rct_id = '{$rct_id}'";
        $coupon = sql_fetch($sql);
        
        if ($coupon['rct_image']) {
            $file_path = G5_DATA_PATH.'/randombox/coupon/'.$coupon['rct_image'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
        
        // 교환권 타입 삭제
        sql_query("DELETE FROM {$g5['g5_prefix']}randombox_coupon_types WHERE rct_id = '{$rct_id}'");
    }
}

goto_url('./coupon_list.php?'.$qstr);
?>