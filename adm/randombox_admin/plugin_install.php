<?php
/*
 * 파일명: plugin_install.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 플러그인 설치/제거 처리
 * 작성일: 2025-01-04
 */

$sub_menu = "300900";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

$mode = $_POST['mode'];

if (!in_array($mode, array('install', 'uninstall'))) {
    alert('올바른 요청이 아닙니다.');
}

// ===================================
// 설치 처리
// ===================================

if ($mode == 'install') {
    
    // 플러그인 설치 파일 실행
    include_once(G5_PLUGIN_PATH.'/randombox/install.php');
    
    // 설치 후 리다이렉트를 위해 스크립트 종료
    exit;
}

// ===================================
// 제거 처리
// ===================================

if ($mode == 'uninstall') {
    
    $msg = '';
    $removed_count = 0;
    $preserved_count = 0;
    
    // 구매 내역 확인
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history";
    $row = sql_fetch($sql);
    $has_history = $row['cnt'] > 0;
    
    if ($has_history) {
        // 구매 내역이 있는 경우 - 데이터 보존하고 비활성화만 처리
        
        // 모든 박스 비활성화
        sql_query("UPDATE {$g5['g5_prefix']}randombox SET rb_status = 0");
        
        // 시스템 비활성화
        sql_query("UPDATE {$g5['g5_prefix']}randombox_config SET cfg_value = '0' WHERE cfg_name = 'system_enable'");
        
        $msg = '구매 내역이 있어 데이터를 보존했습니다.\\n시스템이 비활성화되었습니다.';
        
    } else {
        // 구매 내역이 없는 경우 - 완전 삭제
        
        // 이미지 파일 삭제
        $data_dir = G5_DATA_PATH.'/randombox';
        
        // 박스 이미지 삭제
        $sql = "SELECT rb_image FROM {$g5['g5_prefix']}randombox WHERE rb_image != ''";
        $result = sql_query($sql);
        while ($row = sql_fetch_array($result)) {
            if (file_exists($data_dir.'/box/'.$row['rb_image'])) {
                @unlink($data_dir.'/box/'.$row['rb_image']);
                $removed_count++;
            }
        }
        
        // 아이템 이미지 삭제
        $sql = "SELECT rbi_image FROM {$g5['g5_prefix']}randombox_items WHERE rbi_image != ''";
        $result = sql_query($sql);
        while ($row = sql_fetch_array($result)) {
            if (file_exists($data_dir.'/item/'.$row['rbi_image'])) {
                @unlink($data_dir.'/item/'.$row['rbi_image']);
                $removed_count++;
            }
        }
        
        // 디렉토리 삭제 (비어있는 경우만)
        @rmdir($data_dir.'/item');
        @rmdir($data_dir.'/box');
        @rmdir($data_dir);
        
        // 테이블 삭제
        $tables = array(
            'randombox',
            'randombox_items',
            'randombox_history',
            'randombox_config',
            'randombox_ceiling',
            'randombox_gift'
        );
        
        foreach ($tables as $table) {
            sql_query("DROP TABLE IF EXISTS `{$g5['g5_prefix']}{$table}`", false);
        }
        
        $msg = '랜덤박스 시스템이 완전히 제거되었습니다.\\n삭제된 이미지: ' . $removed_count . '개';
    }
    
    alert($msg, './plugin.php');
}
?>