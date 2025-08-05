<?php
/*
 * 파일명: item_form_update.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 아이템 등록/수정 처리
 * 작성일: 2025-01-04
 * 수정일: 2025-07-21 (교환권 필드 추가)
 */

$sub_menu = "300930";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

// ===================================
// 파라미터 받기
// ===================================

$w = $_POST['w'];
$rb_id = (int)$_POST['rb_id'];
$rbi_id = (int)$_POST['rbi_id'];

// 박스 정보 확인
if (!$rb_id) {
    alert('박스를 선택해 주세요.');
}

$box = get_randombox($rb_id);
if (!$box) {
    alert('존재하지 않는 박스입니다.');
}

// 수정 모드인 경우 아이템 확인
if ($w == 'u') {
    if (!$rbi_id) {
        alert('잘못된 접근입니다.');
    }
    
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items WHERE rbi_id = '$rbi_id' AND rb_id = '$rb_id'";
    $item = sql_fetch($sql);
    if (!$item) {
        alert('존재하지 않는 아이템입니다.');
    }
}

// ===================================
// 입력값 처리
// ===================================

/* 기본 정보 */
$rbi_name = trim($_POST['rbi_name']);
$rbi_desc = trim($_POST['rbi_desc']);
$rbi_grade = $_POST['rbi_grade'];
$rbi_probability = (float)$_POST['rbi_probability'];
$rbi_value = (int)$_POST['rbi_value'];
$rbi_limit_qty = (int)$_POST['rbi_limit_qty'];
$rbi_status = (int)$_POST['rbi_status'];
$rbi_order = (int)$_POST['rbi_order'];

/* 아이템 타입 관련 */
$rbi_item_type = $_POST['rbi_item_type'] ? $_POST['rbi_item_type'] : 'point';
$rct_id = null;

/* 포인트 타입 관련 */
$rbi_point_random = 0;
$rbi_point_min = 0;
$rbi_point_max = 0;

if ($rbi_item_type == 'point') {
    // 포인트 타입인 경우
    if ($box['rb_point_type'] == 'random') {
        $rbi_point_random = 1;
        $rbi_point_min = (int)($box['rb_price'] * $box['rb_point_min_multiplier']);
        $rbi_point_max = (int)($box['rb_price'] * $box['rb_point_max_multiplier']);
        $rbi_value = 0; // 랜덤 포인트는 고정값 없음
    }
} else if ($rbi_item_type == 'coupon') {
    // 교환권 타입인 경우
    $rct_id = (int)$_POST['rct_id'];
    if (!$rct_id) {
        alert('교환권 타입을 선택해 주세요.');
    }
    
    // 교환권 타입 확인
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_coupon_types WHERE rct_id = '{$rct_id}' AND rct_status = 1";
    $coupon_type = sql_fetch($sql);
    if (!$coupon_type) {
        alert('유효하지 않은 교환권 타입입니다.');
    }
    
    $rbi_value = 0; // 교환권은 포인트 가치 없음
}

// ===================================
// 입력값 검증
// ===================================

if (!$rbi_name) {
    alert('아이템명을 입력해 주세요.');
}

if ($rbi_probability <= 0 || $rbi_probability > 100) {
    alert('확률은 0보다 크고 100 이하여야 합니다.');
}

// 수정 모드인 경우 현재 확률 제외한 합계 계산
$exclude_id = ($w == 'u') ? $rbi_id : 0;
$sql = "SELECT SUM(rbi_probability) as total_prob 
        FROM {$g5['g5_prefix']}randombox_items 
        WHERE rb_id = '$rb_id' 
        " . ($exclude_id ? "AND rbi_id != '$exclude_id'" : "");
$row = sql_fetch($sql);
$current_total = $row['total_prob'] ? $row['total_prob'] : 0;

if (($current_total + $rbi_probability) > 100) {
    alert('전체 확률의 합이 100%를 초과할 수 없습니다.\\n현재 사용 가능한 확률: ' . (100 - $current_total) . '%');
}

// ===================================
// 이미지 업로드 처리
// ===================================

$rbi_image = $item['rbi_image'] ? $item['rbi_image'] : '';

if ($_FILES['rbi_image']['name']) {
    $data_dir = G5_DATA_PATH.'/randombox/item';
    
    // 디렉토리 체크
    if (!is_dir($data_dir)) {
        @mkdir($data_dir, G5_DIR_PERMISSION);
        @chmod($data_dir, G5_DIR_PERMISSION);
    }
    
    $filename = $_FILES['rbi_image']['name'];
    $tmp_name = $_FILES['rbi_image']['tmp_name'];
    
    // 확장자 체크
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) {
        alert('이미지 파일만 업로드 가능합니다.');
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
            rbi_item_type = '{$rbi_item_type}',
            rct_id = " . ($rct_id ? "'{$rct_id}'" : "NULL") . ",
            rbi_probability = '{$rbi_probability}',
            rbi_value = '{$rbi_value}',
            rbi_point_random = '{$rbi_point_random}',
            rbi_point_min = '{$rbi_point_min}',
            rbi_point_max = '{$rbi_point_max}',
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
            rbi_item_type = '{$rbi_item_type}',
            rct_id = " . ($rct_id ? "'{$rct_id}'" : "NULL") . ",
            rbi_probability = '{$rbi_probability}',
            rbi_value = '{$rbi_value}',
            rbi_point_random = '{$rbi_point_random}',
            rbi_point_min = '{$rbi_point_min}',
            rbi_point_max = '{$rbi_point_max}',
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