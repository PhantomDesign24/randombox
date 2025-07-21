<?php
/*
 * 파일명: coupon_form_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 타입 등록/수정 처리
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rct_id = (int)$_POST['rct_id'];
$w = $_POST['w'];

// ===================================
// 입력값 검증
// ===================================

/* 필수 입력값 체크 */
if (!$_POST['rct_name']) {
    alert('교환권명을 입력하세요.');
}

if (!$_POST['rct_exchange_item']) {
    alert('교환 상품을 입력하세요.');
}

if ($_POST['rct_value'] < 1) {
    alert('가치를 1 이상 입력하세요.');
}

// ===================================
// 데이터 준비
// ===================================

/* 입력값 정리 */
$rct_name = strip_tags($_POST['rct_name']);
$rct_desc = $_POST['rct_desc'];
$rct_type = $_POST['rct_type'];
$rct_value = (int)$_POST['rct_value'];
$rct_exchange_item = strip_tags($_POST['rct_exchange_item']);
$rct_status = (int)$_POST['rct_status'];

// ===================================
// 이미지 처리
// ===================================

/* 이미지 업로드 디렉토리 */
$data_dir = G5_DATA_PATH.'/randombox/coupon';
$data_url = G5_DATA_URL.'/randombox/coupon';

/* 디렉토리 생성 */
if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
}

/* 기존 이미지 정보 */
$rct_image = '';
if ($w == 'u') {
    $sql = "SELECT rct_image FROM {$g5['g5_prefix']}randombox_coupon_types WHERE rct_id = '$rct_id'";
    $coupon = sql_fetch($sql);
    $rct_image = $coupon['rct_image'];
}

/* 이미지 삭제 처리 */
if (isset($_POST['rct_image_del']) && $_POST['rct_image_del']) {
    if ($rct_image && file_exists($data_dir.'/'.$rct_image)) {
        @unlink($data_dir.'/'.$rct_image);
    }
    $rct_image = '';
}

/* 이미지 업로드 처리 */
if (isset($_FILES['rct_image']) && $_FILES['rct_image']['name']) {
    $tmp_name = $_FILES['rct_image']['tmp_name'];
    $filename = $_FILES['rct_image']['name'];
    
    // 확장자 체크
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) {
        alert('이미지는 jpg, png, gif 파일만 업로드 가능합니다.');
    }
    
    // 파일명 생성
    $new_filename = 'coupon_' . time() . '_' . md5($filename) . '.' . $ext;
    
    // 업로드
    if (move_uploaded_file($tmp_name, $data_dir.'/'.$new_filename)) {
        // 기존 이미지 삭제
        if ($rct_image && file_exists($data_dir.'/'.$rct_image)) {
            @unlink($data_dir.'/'.$rct_image);
        }
        $rct_image = $new_filename;
    }
}

// ===================================
// 데이터 처리
// ===================================

if ($w == '') {
    // 등록
    $sql = "INSERT INTO {$g5['g5_prefix']}randombox_coupon_types SET
            rct_name = '{$rct_name}',
            rct_desc = '{$rct_desc}',
            rct_type = '{$rct_type}',
            rct_image = '{$rct_image}',
            rct_value = '{$rct_value}',
            rct_exchange_item = '{$rct_exchange_item}',
            rct_status = '{$rct_status}',
            rct_created_at = NOW(),
            rct_updated_at = NOW()";
    
    sql_query($sql);
    $rct_id = sql_insert_id();
    
    $msg = '교환권 타입이 등록되었습니다.';
    
} else if ($w == 'u') {
    // 수정
    $sql = "UPDATE {$g5['g5_prefix']}randombox_coupon_types SET
            rct_name = '{$rct_name}',
            rct_desc = '{$rct_desc}',
            rct_type = '{$rct_type}',
            rct_image = '{$rct_image}',
            rct_value = '{$rct_value}',
            rct_exchange_item = '{$rct_exchange_item}',
            rct_status = '{$rct_status}',
            rct_updated_at = NOW()
            WHERE rct_id = '{$rct_id}'";
    
    sql_query($sql);
    
    $msg = '교환권 타입이 수정되었습니다.';
}

// ===================================
// 결과 처리
// ===================================

alert($msg, './coupon_list.php?'.$qstr);
?>