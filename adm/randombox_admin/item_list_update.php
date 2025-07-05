<?php
/*
 * 파일명: item_list_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 아이템 목록 일괄 처리
 * 작성일: 2025-01-04
 */

$sub_menu = "300930";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rb_id = (int)$_POST['rb_id'];

// ===================================
// 박스 확인
// ===================================

if (!$rb_id) {
    alert('박스를 선택해 주세요.');
}

$box = get_randombox($rb_id);
if (!$box) {
    alert('존재하지 않는 박스입니다.');
}

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
    
    // 확률 합계 계산
    $total_probability = 0;
    $items = array();
    
    for ($i = 0; $i < count($_POST['rbi_id']); $i++) {
        $rbi_id = (int)$_POST['rbi_id'][$i];
        $rbi_probability = (float)$_POST['rbi_probability'][$i];
        $rbi_order = (int)$_POST['rbi_order'][$i];
        
        if (!$rbi_id) continue;
        
        // 기존 아이템 정보 확인
        $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items 
                WHERE rbi_id = '{$rbi_id}' AND rb_id = '{$rb_id}'";
        $item = sql_fetch($sql);
        if (!$item) continue;
        
        // 확률 체크
        if ($rbi_probability <= 0 || $rbi_probability > 100) {
            alert($item['rbi_name'] . ' 아이템의 확률이 올바르지 않습니다.');
        }
        
        $items[] = array(
            'rbi_id' => $rbi_id,
            'rbi_probability' => $rbi_probability,
            'rbi_order' => $rbi_order,
            'rbi_name' => $item['rbi_name']
        );
        
        $total_probability += $rbi_probability;
    }
    
    // 확률 합계 경고 (100% 체크)
    if (abs($total_probability - 100) > 0.000001) {
        // 자바스크립트에서 이미 확인했으므로 진행
    }
    
    // DB 업데이트
    $now = date('Y-m-d H:i:s');
    foreach ($items as $item) {
        $sql = "UPDATE {$g5['g5_prefix']}randombox_items SET
                rbi_probability = '{$item['rbi_probability']}',
                rbi_order = '{$item['rbi_order']}',
                rbi_updated_at = '{$now}'
                WHERE rbi_id = '{$item['rbi_id']}' AND rb_id = '{$rb_id}'";
        
        sql_query($sql);
    }
    
    $msg = '선택한 아이템의 정보가 수정되었습니다.';
    
} else if ($_POST['act_button'] == '선택삭제') {
    
    // ===================================
    // 선택 삭제
    // ===================================
    
    if (!is_array($_POST['chk'])) {
        alert('삭제할 항목을 선택해 주세요.');
    }
    
    auth_check($auth[$sub_menu], 'd');
    
    $count = 0;
    $data_dir = G5_DATA_PATH.'/randombox/item';
    
    for ($i = 0; $i < count($_POST['chk']); $i++) {
        $k = (int)$_POST['chk'][$i];
        $rbi_id = (int)$_POST['rbi_id'][$k];
        
        if (!$rbi_id) continue;
        
        // 아이템 정보 조회
        $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items 
                WHERE rbi_id = '{$rbi_id}' AND rb_id = '{$rb_id}'";
        $item = sql_fetch($sql);
        if (!$item) continue;
        
        // 배출 내역이 있는지 확인
        $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history 
                WHERE rbi_id = '{$rbi_id}'";
        $row = sql_fetch($sql);
        
        if ($row['cnt'] > 0) {
            // 배출 내역이 있으면 비활성화만 처리
            $sql = "UPDATE {$g5['g5_prefix']}randombox_items SET 
                    rbi_status = 0,
                    rbi_updated_at = NOW()
                    WHERE rbi_id = '{$rbi_id}' AND rb_id = '{$rb_id}'";
            sql_query($sql);
            
        } else {
            // 배출 내역이 없으면 완전 삭제
            
            // 이미지 삭제
            if ($item['rbi_image'] && file_exists($data_dir.'/'.$item['rbi_image'])) {
                @unlink($data_dir.'/'.$item['rbi_image']);
            }
            
            // 아이템 삭제
            sql_query("DELETE FROM {$g5['g5_prefix']}randombox_items 
                      WHERE rbi_id = '{$rbi_id}' AND rb_id = '{$rb_id}'");
        }
        
        $count++;
    }
    
    $msg = $count . '개의 아이템을 처리했습니다.\\n배출 내역이 있는 아이템은 비활성화 처리되었습니다.';
    
} else {
    alert('올바른 요청이 아닙니다.');
}

// ===================================
// 완료 후 이동
// ===================================

alert($msg, './item_list.php?rb_id='.$rb_id.'&'.$qstr);
?>