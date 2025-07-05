<?php
/*
 * 파일명: box_form_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 등록/수정 처리
 * 작성일: 2025-01-04
 */

$sub_menu = "300920";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rb_id = (int)$_POST['rb_id'];
$w = $_POST['w'];

// ===================================
// 입력값 검증
// ===================================

/* 필수 입력값 체크 */
if (!$_POST['rb_name']) {
    alert('박스명을 입력하세요.');
}

if ($_POST['rb_price'] < 0) {
    alert('판매 가격을 올바르게 입력하세요.');
}

/* 날짜 형식 검증 */
if ($_POST['rb_start_date'] && !preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $_POST['rb_start_date'])) {
    alert('시작일 형식이 올바르지 않습니다.');
}

if ($_POST['rb_end_date'] && !preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/", $_POST['rb_end_date'])) {
    alert('종료일 형식이 올바르지 않습니다.');
}

if ($_POST['rb_start_date'] && $_POST['rb_end_date'] && $_POST['rb_start_date'] >= $_POST['rb_end_date']) {
    alert('종료일은 시작일보다 이후여야 합니다.');
}

// ===================================
// 데이터 준비
// ===================================

/* 입력값 정리 */
$rb_name = strip_tags($_POST['rb_name']);
$rb_desc = $_POST['rb_desc'];
$rb_price = (int)$_POST['rb_price'];
$rb_status = (int)$_POST['rb_status'];
$rb_type = $_POST['rb_type'];
$rb_start_date = $_POST['rb_start_date'] ? $_POST['rb_start_date'] : null;
$rb_end_date = $_POST['rb_end_date'] ? $_POST['rb_end_date'] : null;
$rb_limit_qty = (int)$_POST['rb_limit_qty'];
$rb_total_qty = (int)$_POST['rb_total_qty'];
$rb_order = (int)$_POST['rb_order'];

// ===================================
// 이미지 처리
// ===================================

/* 이미지 업로드 디렉토리 */
$data_dir = G5_DATA_PATH.'/randombox/box';
$data_url = G5_DATA_URL.'/randombox/box';

/* 디렉토리 생성 */
if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
}

/* 기존 이미지 정보 */
$rb_image = '';
if ($w == 'u') {
    $box = get_randombox($rb_id);
    $rb_image = $box['rb_image'];
}

/* 이미지 삭제 처리 */
if (isset($_POST['rb_image_del']) && $_POST['rb_image_del']) {
    if ($rb_image && file_exists($data_dir.'/'.$rb_image)) {
        @unlink($data_dir.'/'.$rb_image);
    }
    $rb_image = '';
}

/* 이미지 업로드 처리 */
if (isset($_FILES['rb_image']) && $_FILES['rb_image']['name']) {
    $tmp_name = $_FILES['rb_image']['tmp_name'];
    $filename = $_FILES['rb_image']['name'];
    
    // 확장자 체크
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) {
        alert('이미지는 jpg, png, gif 파일만 업로드 가능합니다.');
    }
    
    // 파일명 생성
    $new_filename = time() . '_' . md5($filename) . '.' . $ext;
    
    // 업로드
    if (move_uploaded_file($tmp_name, $data_dir.'/'.$new_filename)) {
        // 기존 이미지 삭제
        if ($rb_image && file_exists($data_dir.'/'.$rb_image)) {
            @unlink($data_dir.'/'.$rb_image);
        }
        $rb_image = $new_filename;
    } else {
        alert('이미지 업로드에 실패했습니다.');
    }
}

// ===================================
// DB 처리
// ===================================

/* 현재 시간 */
$now = date('Y-m-d H:i:s');

if ($w == '') {
    // 등록
    $sql = "INSERT INTO {$g5['g5_prefix']}randombox SET
            rb_name = '{$rb_name}',
            rb_desc = '{$rb_desc}',
            rb_price = '{$rb_price}',
            rb_image = '{$rb_image}',
            rb_status = '{$rb_status}',
            rb_type = '{$rb_type}',
            rb_start_date = " . ($rb_start_date ? "'{$rb_start_date}'" : "NULL") . ",
            rb_end_date = " . ($rb_end_date ? "'{$rb_end_date}'" : "NULL") . ",
            rb_limit_qty = '{$rb_limit_qty}',
            rb_total_qty = '{$rb_total_qty}',
            rb_sold_qty = 0,
            rb_order = '{$rb_order}',
            rb_created_at = '{$now}',
            rb_updated_at = '{$now}'";
    
    sql_query($sql);
    $rb_id = sql_insert_id();
    
    $msg = '랜덤박스가 등록되었습니다.';
    
} else if ($w == 'u') {
    // 수정
    if (!$rb_id) {
        alert('잘못된 접근입니다.');
    }
    
    // 판매 수량이 전체 수량보다 많은지 체크
    if ($rb_total_qty > 0 && $box['rb_sold_qty'] > $rb_total_qty) {
        alert('전체 판매 수량은 이미 판매된 수량('.$box['rb_sold_qty'].'개)보다 적을 수 없습니다.');
    }
    
    $sql = "UPDATE {$g5['g5_prefix']}randombox SET
            rb_name = '{$rb_name}',
            rb_desc = '{$rb_desc}',
            rb_price = '{$rb_price}',
            rb_image = '{$rb_image}',
            rb_status = '{$rb_status}',
            rb_type = '{$rb_type}',
            rb_start_date = " . ($rb_start_date ? "'{$rb_start_date}'" : "NULL") . ",
            rb_end_date = " . ($rb_end_date ? "'{$rb_end_date}'" : "NULL") . ",
            rb_limit_qty = '{$rb_limit_qty}',
            rb_total_qty = '{$rb_total_qty}',
            rb_order = '{$rb_order}',
            rb_updated_at = '{$now}'
            WHERE rb_id = '{$rb_id}'";
    
    sql_query($sql);
    
    $msg = '랜덤박스가 수정되었습니다.';
}

// ===================================
// 완료 후 이동
// ===================================

alert($msg, './box_form.php?w=u&rb_id='.$rb_id.'&'.$qstr);
?>