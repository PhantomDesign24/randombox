<?php
/*
 * 파일명: item_form_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 아이템 등록/수정 처리
 * 작성일: 2025-01-04
 */

$sub_menu = "300930";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rb_id = (int)$_POST['rb_id'];
$rbi_id = (int)$_POST['rbi_id'];
$w = $_POST['w'];

// ===================================
// 입력값 검증
// ===================================

/* 박스 확인 */
if (!$rb_id) {
    alert('박스를 선택해 주세요.');
}

$box = get_randombox($rb_id);
if (!$box) {
    alert('존재하지 않는 박스입니다.');
}

/* 필수 입력값 체크 */
if (!$_POST['rbi_name']) {
    alert('아이템명을 입력하세요.');
}

$rbi_probability = (float)$_POST['rbi_probability'];
if ($rbi_probability <= 0 || $rbi_probability > 100) {
    alert('확률은 0보다 크고 100 이하로 입력하세요.');
}

/* 확률 합계 체크 */
$sql = "SELECT SUM(rbi_probability) as total_prob 
        FROM {$g5['g5_prefix']}randombox_items 
        WHERE rb_id = '$rb_id' 
        " . ($w == 'u' ? "AND rbi_id != '$rbi_id'" : "");
$row = sql_fetch($sql);
$used_probability = $row['total_prob'] ? $row['total_prob'] : 0;
$available_probability = 100 - $used_probability;

if ($rbi_probability > $available_probability) {
    alert('사용 가능한 확률(' . number_format($available_probability, 6) . '%)을 초과했습니다.');
}

// ===================================
// 데이터 준비
// ===================================

/* 입력값 정리 */
$rbi_name = strip_tags($_POST['rbi_name']);
$rbi_desc = $_POST['rbi_desc'];
$rbi_grade = $_POST['rbi_grade'];
$rbi_value = (int)$_POST['rbi_value'];
$rbi_limit_qty = (int)$_POST['rbi_limit_qty'];
$rbi_status = (int)$_POST['rbi_status'];
$rbi_order = (int)$_POST['rbi_order'];

/* 등급 유효성 체크 */
$valid_grades = array('normal', 'rare', 'epic', 'legendary');
if (!in_array($rbi_grade, $valid_grades)) {
    $rbi_grade = 'normal';
}

// ===================================
// 이미지 처리
// ===================================

/* 이미지 업로드 디렉토리 */
$data_dir = G5_DATA_PATH.'/randombox/item';
$data_url = G5_DATA_URL.'/randombox/item';

/* 디렉토리 생성 */
if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
}

/* 기존 이미지 정보 */
$rbi_image = '';
if ($w == 'u') {
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items WHERE rbi_id = '$rbi_id' AND rb_id = '$rb_id'";
    $item = sql_fetch($sql);
    if (!$item) {
        alert('존재하지 않는 아이템입니다.');
    }
    $rbi_image = $item['rbi_image'];
    
    // 수량 제한 체크
    if ($rbi_limit_qty > 0 && $item['rbi_issued_qty'] > $rbi_limit_qty) {
        alert('최대 배출 수량은 이미 배출된 수량(' . $item['rbi_issued_qty'] . '개)보다 적을 수 없습니다.');
    }
}

/* 이미지 삭제 처리 */
if (isset($_POST['rbi_image_del']) && $_POST['rbi_image_del']) {
    if ($rbi_image && file_exists($data_dir.'/'.$rbi_image)) {
        @unlink($data_dir.'/'.$rbi_image);
    }
    $rbi_image = '';
}

/* 이미지 업로드 처리 */
if (isset($_FILES['rbi_image']) && $_FILES['rbi_image']['name']) {
    $tmp_name = $_FILES['rbi_image']['tmp_name'];
    $filename = $_FILES['rbi_image']['name'];
    
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
        if ($rbi_image && file_exists($data_dir.'/'.$rbi_image)) {
            @unlink($data_dir.'/'.$rbi_image);
        }
        $rbi_image = $new_filename;
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
    $sql = "INSERT INTO {$g5['g5_prefix']}randombox_items SET
            rb_id = '{$rb_id}',
            rbi_name = '{$rbi_name}',
            rbi_desc = '{$rbi_desc}',
            rbi_image = '{$rbi_image}',
            rbi_grade = '{$rbi_grade}',
            rbi_probability = '{$rbi_probability}',
            rbi_value = '{$rbi_value}',
            rbi_limit_qty = '{$rbi_limit_qty}',
            rbi_issued_qty = 0,
            rbi_status = '{$rbi_status}',
            rbi_order = '{$rbi_order}',
            rbi_created_at = '{$now}',
            rbi_updated_at = '{$now}'";
    
    sql_query($sql);
    $rbi_id = sql_insert_id();
    
    $msg = '아이템이 등록되었습니다.';
    
} else if ($w == 'u') {
    // 수정
    $sql = "UPDATE {$g5['g5_prefix']}randombox_items SET
            rbi_name = '{$rbi_name}',
            rbi_desc = '{$rbi_desc}',
            rbi_image = '{$rbi_image}',
            rbi_grade = '{$rbi_grade}',
            rbi_probability = '{$rbi_probability}',
            rbi_value = '{$rbi_value}',
            rbi_limit_qty = '{$rbi_limit_qty}',
            rbi_status = '{$rbi_status}',
            rbi_order = '{$rbi_order}',
            rbi_updated_at = '{$now}'
            WHERE rbi_id = '{$rbi_id}' AND rb_id = '{$rb_id}'";
    
    sql_query($sql);
    
    $msg = '아이템이 수정되었습니다.';
}

// ===================================
// 완료 후 이동
// ===================================

alert($msg, './item_list.php?rb_id='.$rb_id.'&'.$qstr);
?>