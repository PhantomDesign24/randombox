<?php
/*
 * 파일명: gift_accept.php
 * 위치: /randombox/
 * 기능: 랜덤박스 선물 수락 처리 (AJAX)
 * 작성일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// AJAX 전용
// ===================================

header('Content-Type: application/json');

$response = array(
    'status' => false,
    'msg' => ''
);

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['msg'] = '잘못된 접근입니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 입력값 검증
// ===================================

$rbg_id = (int)$_POST['rbg_id'];

if (!$rbg_id) {
    $response['msg'] = '선물 정보가 올바르지 않습니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 선물 정보 확인
// ===================================

$sql = "SELECT g.*, b.rb_name, b.rb_price 
        FROM {$g5['g5_prefix']}randombox_gift g
        LEFT JOIN {$g5['g5_prefix']}randombox b ON g.rb_id = b.rb_id
        WHERE g.rbg_id = '{$rbg_id}' 
        AND g.recv_mb_id = '{$member['mb_id']}'
        AND g.rbg_status = 'pending'";
$gift = sql_fetch($sql);

if (!$gift) {
    $response['msg'] = '수락할 수 있는 선물이 아닙니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 선물 수락 처리
// ===================================

$now = date('Y-m-d H:i:s');

// 트랜잭션 시작
sql_query("START TRANSACTION");

try {
    // 선물 상태 업데이트
    $sql = "UPDATE {$g5['g5_prefix']}randombox_gift SET
            rbg_status = 'accepted',
            rbg_accepted_at = '{$now}'
            WHERE rbg_id = '{$rbg_id}'";
    
    if (!sql_query($sql)) {
        throw new Exception('선물 상태 업데이트에 실패했습니다.');
    }
    
    // 선물 받은 박스를 구매 내역에 추가 (quantity만큼 반복)
    for ($i = 0; $i < $gift['rbg_quantity']; $i++) {
        // 아이템 추첨
        $item = draw_randombox_item($gift['rb_id'], $member['mb_id']);
        if (!$item) {
            throw new Exception('아이템 추첨에 실패했습니다.');
        }
        
        // 구매 기록 저장
        $sql = "INSERT INTO {$g5['g5_prefix']}randombox_history SET
                mb_id = '{$member['mb_id']}',
                rb_id = '{$gift['rb_id']}',
                rb_name = '{$gift['rb_name']}',
                rb_price = 0,
                rbi_id = '{$item['rbi_id']}',
                rbi_name = '{$item['rbi_name']}',
                rbi_grade = '{$item['rbi_grade']}',
                rbi_value = '{$item['rbi_value']}',
                rbh_status = 'gift',
                rbh_ip = '{$_SERVER['REMOTE_ADDR']}',
                rbh_created_at = '{$now}'";
        
        if (!sql_query($sql)) {
            throw new Exception('구매 기록 저장에 실패했습니다.');
        }
        
        // 아이템 배출 수량 증가
        sql_query("UPDATE {$g5['g5_prefix']}randombox_items SET rbi_issued_qty = rbi_issued_qty + 1 WHERE rbi_id = '{$item['rbi_id']}'");
        
        // 아이템 가치만큼 포인트 지급
        if ($item['rbi_value'] > 0) {
            insert_point($member['mb_id'], $item['rbi_value'], "선물 아이템 획득: {$item['rbi_name']}");
        }
    }
    
    // 박스 판매 수량 증가
    sql_query("UPDATE {$g5['g5_prefix']}randombox SET rb_sold_qty = rb_sold_qty + {$gift['rbg_quantity']} WHERE rb_id = '{$gift['rb_id']}'");
    
    // 커밋
    sql_query("COMMIT");
    
    $response['status'] = true;
    $response['msg'] = '선물을 수락했습니다!\\n구매내역에서 획득한 아이템을 확인하세요.';
    
} catch (Exception $e) {
    // 롤백
    sql_query("ROLLBACK");
    
    $response['msg'] = $e->getMessage();
}

// ===================================
// 결과 반환
// ===================================

echo json_encode($response);
?>