<?php
/*
 * 파일명: plugin_install.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 플러그인 설치/제거 처리
 * 작성일: 2025-01-04
 * 수정일: 2025-07-17
 */

$sub_menu = "300900";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$install_sample = isset($_POST['install_sample']) ? (int)$_POST['install_sample'] : 0;

if (!$mode) {
    alert('잘못된 접근입니다.');
}

// ===================================
// 설치 처리
// ===================================

if ($mode == 'install') {
    
    // 기본 테이블 생성
    $sql_file = G5_PLUGIN_PATH.'/randombox/install.sql';
    if (!file_exists($sql_file)) {
        alert('설치 파일이 존재하지 않습니다.');
    }
    
    $sql = file_get_contents($sql_file);
    
    // 테이블 접두어 치환
    $sql = str_replace('g5_', $g5['g5_prefix'], $sql);
    
    // 샘플 데이터 제거 (선택하지 않은 경우)
    if (!$install_sample) {
        // 샘플 데이터 섹션 찾아서 제거
        $sql = preg_replace('/-- ===================================\s*-- 샘플 데이터.*$/s', '', $sql);
    }
    
    // SQL 실행
    $sql_array = explode(';', $sql);
    $success_count = 0;
    $error_count = 0;
    $errors = array();
    
    foreach ($sql_array as $query) {
        $query = trim($query);
        if (!$query) continue;
        
        if (sql_query($query, false)) {
            $success_count++;
        } else {
            $error_count++;
            $errors[] = sql_error();
        }
    }
    
    // 디렉토리 생성
    $upload_dirs = array(
        G5_DATA_PATH.'/randombox',
        G5_DATA_PATH.'/randombox/box',
        G5_DATA_PATH.'/randombox/item',
        G5_DATA_PATH.'/randombox/coupon'
    );
    
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            @chmod($dir, 0755);
        }
    }
    
    // 관리자 메뉴 추가 안내
    $menu_msg = '';
    if ($success_count > 0) {
        $menu_msg = '<br><br>관리자 메뉴 등록을 위해 /adm/admin.menu300.php 파일에 다음 코드를 추가하세요:<br>';
        $menu_msg .= '<pre style="background:#f5f5f5;padding:10px;margin:10px 0;">';
        $menu_msg .= "// 랜덤박스 메뉴\n";
        $menu_msg .= "\$menu['menu300'][] = array('300900', '랜덤박스', G5_ADMIN_URL . '/randombox_admin/plugin.php', 'randombox');\n";
        $menu_msg .= "\$menu['menu300'][] = array('300910', '랜덤박스 설정', G5_ADMIN_URL . '/randombox_admin/config.php', 'randombox_config');\n";
        $menu_msg .= "\$menu['menu300'][] = array('300920', '박스 관리', G5_ADMIN_URL . '/randombox_admin/box_list.php', 'randombox_box');\n";
        $menu_msg .= "\$menu['menu300'][] = array('300930', '아이템 관리', G5_ADMIN_URL . '/randombox_admin/item_list.php', 'randombox_item');\n";
        $menu_msg .= "\$menu['menu300'][] = array('300940', '통계 관리', G5_ADMIN_URL . '/randombox_admin/statistics.php', 'randombox_stats');\n";
        $menu_msg .= "\$menu['menu300'][] = array('300950', '교환권 관리', G5_ADMIN_URL . '/randombox_admin/coupon_list.php', 'randombox_coupon');";
        $menu_msg .= '</pre>';
    }
    
    if ($error_count > 0) {
        $error_msg = implode('<br>', $errors);
        alert("설치 중 일부 오류가 발생했습니다.\\n\\n성공: {$success_count}개\\n실패: {$error_count}개\\n\\n{$error_msg}{$menu_msg}", './plugin.php');
    } else {
        $sample_msg = $install_sample ? ' (샘플 데이터 포함)' : '';
        alert("랜덤박스 시스템이 성공적으로 설치되었습니다.{$sample_msg}\\n\\n실행된 쿼리: {$success_count}개{$menu_msg}", './plugin.php');
    }
    
// ===================================
// 제거 처리
// ===================================

} else if ($mode == 'uninstall') {
    
    $uninstall_type = isset($_POST['uninstall_type']) ? $_POST['uninstall_type'] : 'preserve';
    
    if ($uninstall_type == 'complete') {
        // ===================================
        // 완전 제거
        // ===================================
        
        // 1. 모든 테이블 삭제
        $tables = array(
            'randombox_gift',
            'randombox_ceiling',
            'randombox_coupon_use_log',
            'randombox_member_coupons',
            'randombox_coupon_codes',
            'randombox_coupon_types',
            'randombox_guaranteed',
            'randombox_history',
            'randombox_items',
            'randombox_config',
            'randombox'
        );
        
        $success_count = 0;
        foreach ($tables as $table) {
            $full_table = $g5['g5_prefix'] . $table;
            sql_query("DROP TABLE IF EXISTS `{$full_table}`", false);
            $success_count++;
        }
        
        // 2. 파일 및 폴더 삭제
        $randombox_dir = G5_DATA_PATH.'/randombox';
        
        // 재귀적으로 폴더 삭제하는 함수
        function deleteDirectory($dir) {
            if (!is_dir($dir)) return;
            
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    deleteDirectory($path);
                } else {
                    @unlink($path);
                }
            }
            @rmdir($dir);
        }
        
        // randombox 폴더 전체 삭제
        deleteDirectory($randombox_dir);
        
        $msg = "랜덤박스 시스템이 완전히 제거되었습니다.\\n\\n";
        $msg .= "삭제된 테이블: {$success_count}개\\n";
        $msg .= "데이터 폴더: 삭제됨\\n\\n";
        $msg .= "⚠️ 모든 데이터가 영구적으로 삭제되었습니다.";
        
    } else {
        // ===================================
        // 데이터 보존 제거
        // ===================================
        
        $tables = array(
            'randombox_gift',
            'randombox_ceiling',
            'randombox_coupon_use_log',
            'randombox_member_coupons',
            'randombox_coupon_codes',
            'randombox_coupon_types',
            'randombox_guaranteed',
            'randombox_history',
            'randombox_items',
            'randombox_config',
            'randombox'
        );
        
        $success_count = 0;
        $skip_count = 0;
        
        foreach ($tables as $table) {
            $full_table = $g5['g5_prefix'] . $table;
            
            // 구매 내역이 있는지 확인
            if ($table == 'randombox_history') {
                $cnt = sql_fetch("SELECT COUNT(*) as cnt FROM `{$full_table}`");
                if ($cnt['cnt'] > 0) {
                    $skip_count++;
                    continue; // 구매 내역이 있으면 보존
                }
            }
            
            // 관련 테이블도 확인
            if (in_array($table, array('randombox', 'randombox_items', 'randombox_config'))) {
                $cnt = sql_fetch("SELECT COUNT(*) as cnt FROM `{$g5['g5_prefix']}randombox_history`");
                if ($cnt && $cnt['cnt'] > 0) {
                    $skip_count++;
                    continue; // 구매 내역이 있으면 기본 테이블도 보존
                }
            }
            
            // 교환권 관련 테이블 확인
            if (in_array($table, array('randombox_coupon_types', 'randombox_coupon_codes'))) {
                $cnt = sql_fetch("SELECT COUNT(*) as cnt FROM `{$g5['g5_prefix']}randombox_member_coupons`");
                if ($cnt && $cnt['cnt'] > 0) {
                    $skip_count++;
                    continue; // 보유 교환권이 있으면 보존
                }
            }
            
            // 테이블 삭제
            sql_query("DROP TABLE IF EXISTS `{$full_table}`", false);
            $success_count++;
        }
        
        $msg = "랜덤박스 시스템이 제거되었습니다. (데이터 보존)\\n\\n";
        $msg .= "삭제된 테이블: {$success_count}개\\n";
        if ($skip_count > 0) {
            $msg .= "보존된 테이블: {$skip_count}개\\n";
            $msg .= "이미지 파일: 보존됨\\n\\n";
            $msg .= "재설치 시 기존 데이터를 복구할 수 있습니다.";
        }
    }
    
    alert($msg, './plugin.php');
    
} else {
    alert('잘못된 요청입니다.');
}
?>