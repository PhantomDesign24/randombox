<?php
/*
 * 파일명: box_list_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 목록 일괄 처리
 * 작성일: 2025-01-04
 */

$sub_menu = "300920";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

// ===================================
// 처리 모드 확인
// ===================================

if (!$_POST['act_button']) {
    alert('잘못된 접근입니다.');
}

if ($_POST['act_button'] == '선택수정') {
    
    // ===================================
    // 선택 수정
    // ===================================
    
    for ($i = 0; $i < count($_POST['rb_id']); $i++) {
        $rb_id = (int)$_POST['rb_id'][$i];
        $rb_order = (int)$_POST['rb_order'][$i];
        
        if (!$rb_id) continue;
        
        $sql = "UPDATE {$g5['g5_prefix']}randombox SET
                rb_order = '{$rb_order}',
                rb_updated_at = NOW()
                WHERE rb_id = '{$rb_id}'";
        
        sql_query($sql);
    }
    
    $msg = '선택한 랜덤박스의 정보가 수정되었습니다.';
    
} else if ($_POST['act_button'] == '선택삭제') {
    
    // ===================================
    // 선택 삭제
    // ===================================
    
    if (!is_array($_POST['chk'])) {
        alert('삭제할 항목을 선택해 주세요.');
    }
    
    auth_check($auth[$sub_menu], 'd');
    
    $count = 0;
    $data_dir = G5_DATA_PATH.'/randombox/box';
    
    for ($i = 0; $i < count($_POST['chk']); $i++) {
        $k = (int)$_POST['chk'][$i];
        $rb_id = (int)$_POST['rb_id'][$k];
        
        if (!$rb_id) continue;
        
        // 박스 정보 조회
        $box = get_randombox($rb_id);
        if (!$box) continue;
        
        // 구매 내역이 있는지 확인
        $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history WHERE rb_id = '{$rb_id}'";
        $row = sql_fetch($sql);
        
        if ($row['cnt'] > 0) {
            // 구매 내역이 있으면 비활성화만 처리
            $sql = "UPDATE {$g5['g5_prefix']}randombox SET 
                    rb_status = 0,
                    rb_updated_at = NOW()
                    WHERE rb_id = '{$rb_id}'";
            sql_query($sql);
            
        } else {
            // 구매 내역이 없으면 완전 삭제
            
            // 이미지 삭제
            if ($box['rb_image'] && file_exists($data_dir.'/'.$box['rb_image'])) {
                @unlink($data_dir.'/'.$box['rb_image']);
            }
            
            // 아이템 이미지 삭제
            $item_dir = G5_DATA_PATH.'/randombox/item';
            $sql = "SELECT rbi_image FROM {$g5['g5_prefix']}randombox_items WHERE rb_id = '{$rb_id}'";
            $result = sql_query($sql);
            while ($item = sql_fetch_array($result)) {
                if ($item['rbi_image'] && file_exists($item_dir.'/'.$item['rbi_image'])) {
                    @unlink($item_dir.'/'.$item['rbi_image']);
                }
            }
            
            // 아이템 삭제
            sql_query("DELETE FROM {$g5['g5_prefix']}randombox_items WHERE rb_id = '{$rb_id}'");
            
            // 천장 기록 삭제
            sql_query("DELETE FROM {$g5['g5_prefix']}randombox_ceiling WHERE rb_id = '{$rb_id}'");
            
            // 선물 기록 삭제
            sql_query("DELETE FROM {$g5['g5_prefix']}randombox_gift WHERE rb_id = '{$rb_id}'");
            
            // 박스 삭제
            sql_query("DELETE FROM {$g5['g5_prefix']}randombox WHERE rb_id = '{$rb_id}'");
        }
        
        $count++;
    }
    
    $msg = $count . '개의 랜덤박스를 처리했습니다.\\n구매 내역이 있는 박스는 비활성화 처리되었습니다.';
    
} else {
    alert('올바른 요청이 아닙니다.');
}

// ===================================
// 완료 후 이동
// ===================================

alert($msg, './box_list.php?'.$qstr);
?>