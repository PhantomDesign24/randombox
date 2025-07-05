<?php
/*
 * 파일명: gift_send.php
 * 위치: /randombox/
 * 기능: 랜덤박스 선물 보내기 처리
 * 작성일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// 선물하기 기능 확인
// ===================================

if (!get_randombox_config('enable_gift')) {
    alert('선물하기 기능이 비활성화 상태입니다.');
}

// ===================================
// 입력값 검증
// ===================================

$recv_mb_id = trim($_POST['recv_mb_id']);
$rb_id = (int)$_POST['rb_id'];
$quantity = (int)$_POST['quantity'];
$message = strip_tags($_POST['message']);

if (!$recv_mb_id) {
    alert('받는 사람 아이디를 입력해 주세요.');
}

if ($recv_mb_id == $member['mb_id']) {
    alert('자기 자신에게는 선물할 수 없습니다.');
}

if (!$rb_id) {
    alert('선물할 박스를 선택해 주세요.');
}

if ($quantity < 1 || $quantity > 10) {
    alert('수량은 1개 이상 10개 이하로 입력해 주세요.');
}

// ===================================
// 받는 회원 확인
// ===================================

$sql = "SELECT mb_id, mb_nick, mb_level FROM {$g5['member_table']} WHERE mb_id = '{$recv_mb_id}'";
$recv_member = sql_fetch($sql);

if (!$recv_member) {
    alert('존재하지 않는 회원입니다.');
}

// 받는 회원의 레벨 확인
$min_level = (int)get_randombox_config('min_level');
if ($recv_member['mb_level'] < $min_level) {
    alert('받는 회원의 레벨이 낮아 선물할 수 없습니다.');
}

// ===================================
// 박스 정보 확인
// ===================================

$box = get_randombox($rb_id);
if (!$box || !$box['rb_status']) {
    alert('존재하지 않거나 판매 중지된 박스입니다.');
}

// 판매 기간 확인
$now = date('Y-m-d H:i:s');
if ($box['rb_start_date'] && $box['rb_start_date'] > $now) {
    alert('아직 판매 시작 전인 박스입니다.');
}
if ($box['rb_end_date'] && $box['rb_end_date'] < $now) {
    alert('판매가 종료된 박스입니다.');
}

// ===================================
// 포인트 확인
// ===================================

$total_price = $box['rb_price'] * $quantity;

if ($member['mb_point'] < $total_price) {
    alert('포인트가 부족합니다.');
}

// ===================================
// 선물 처리
// ===================================

// 트랜잭션 시작
sql_query("START TRANSACTION");

try {
    // 포인트 차감
    insert_point($member['mb_id'], -$total_price, "랜덤박스 선물: {$recv_member['mb_nick']}({$recv_mb_id})에게 {$box['rb_name']} {$quantity}개");
    
    // 선물 기록 저장
    $sql = "INSERT INTO {$g5['g5_prefix']}randombox_gift SET
            send_mb_id = '{$member['mb_id']}',
            recv_mb_id = '{$recv_mb_id}',
            rb_id = '{$rb_id}',
            rbg_quantity = '{$quantity}',
            rbg_message = '{$message}',
            rbg_status = 'pending',
            rbg_created_at = '{$now}'";
    
    if (!sql_query($sql)) {
        throw new Exception('선물 기록 저장에 실패했습니다.');
    }
    
    // 커밋
    sql_query("COMMIT");
    
    $msg = "선물이 발송되었습니다.\\n\\n";
    $msg .= "받는 사람: {$recv_member['mb_nick']}({$recv_mb_id})\\n";
    $msg .= "선물 내용: {$box['rb_name']} {$quantity}개\\n";
    $msg .= "사용 포인트: " . number_format($total_price) . "P";
    
    alert($msg, './gift.php?tab=sent');
    
} catch (Exception $e) {
    // 롤백
    sql_query("ROLLBACK");
    alert($e->getMessage());
}
?>