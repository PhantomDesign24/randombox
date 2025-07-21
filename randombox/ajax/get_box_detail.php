<?php
/*
 * 파일명: get_box_detail.php
 * 위치: /randombox/ajax/
 * 기능: 박스 상세 정보 조회 (AJAX) - 수정본
 * 작성일: 2025-07-17
 * 수정일: 2025-07-17
 */

include_once('../../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'message' => '',
    'data' => null
);

// 로그인 체크
if (!$member['mb_id']) {
    $response['message'] = '로그인이 필요합니다.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 박스 ID 확인
$rb_id = (int)$_GET['id'];
if (!$rb_id) {
    $response['message'] = '잘못된 접근입니다.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 박스 정보 조회 - 직접 쿼리
$sql = "SELECT * FROM {$g5['g5_prefix']}randombox WHERE rb_id = '{$rb_id}' AND rb_status = 1";
$box = sql_fetch($sql);

if (!$box) {
    $response['message'] = '판매중인 상품이 아닙니다.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 박스 이미지
$box_img = G5_URL.'/randombox/img/box-default.png';
if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
    $box_img = G5_DATA_URL.'/randombox/box/'.$box['rb_image'];
}

// 구매 가능 여부 확인
$can_purchase = true;
$purchase_message = '';

// 판매 기간 체크
if ($box['rb_start_date'] && $box['rb_start_date'] > G5_TIME_YMDHIS) {
    $can_purchase = false;
    $purchase_message = '판매 시작 전입니다.';
} elseif ($box['rb_end_date'] && $box['rb_end_date'] < G5_TIME_YMDHIS) {
    $can_purchase = false;
    $purchase_message = '판매가 종료되었습니다.';
}

// 일일 구매 제한 체크
if ($can_purchase && $box['rb_limit_qty'] > 0) {
    $today = date('Y-m-d');
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history 
            WHERE mb_id = '{$member['mb_id']}' 
            AND rb_id = '{$rb_id}' 
            AND DATE(rbh_created_at) = '{$today}'";
    $today_cnt = sql_fetch($sql);
    
    if ($today_cnt['cnt'] >= $box['rb_limit_qty']) {
        $can_purchase = false;
        $purchase_message = '일일 구매 한도를 초과했습니다.';
    }
}

// 전체 수량 체크
if ($can_purchase && $box['rb_total_qty'] > 0 && $box['rb_sold_qty'] >= $box['rb_total_qty']) {
    $can_purchase = false;
    $purchase_message = '품절되었습니다.';
}

// 포인트 체크
if ($can_purchase && $member['mb_point'] < $box['rb_price']) {
    $can_purchase = false;
    $purchase_message = '포인트가 부족합니다.';
}

// 통계 정보
$sql = "SELECT 
        COUNT(*) as total_sold,
        COUNT(DISTINCT mb_id) as unique_buyers
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE rb_id = '{$rb_id}'";
$stats = sql_fetch($sql);

// 아이템 목록 조회
$items = array();
$sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items 
        WHERE rb_id = '{$rb_id}' 
        AND rbi_status = 1 
        ORDER BY rbi_order, rbi_id";
$result = sql_query($sql);

while ($item = sql_fetch_array($result)) {
    // 아이템 이미지
    $item_img = G5_URL.'/randombox/img/item-default.png';
    if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
        $item_img = G5_DATA_URL.'/randombox/item/'.$item['rbi_image'];
    }
    
    // 등급명 설정
    $grade_names = array(
        'normal' => '일반',
        'rare' => '레어',
        'epic' => '에픽',
        'legendary' => '레전더리'
    );
    
    $items[] = array(
        'id' => $item['rbi_id'],
        'name' => $item['rbi_name'],
        'desc' => $item['rbi_desc'],
        'image' => $item_img,
        'grade' => $item['rbi_grade'],
        'grade_name' => $grade_names[$item['rbi_grade']] ?: $item['rbi_grade'],
        'probability' => number_format($item['rbi_probability'], 2),
        'value' => (int)$item['rbi_value']
    );
}

// 최근 당첨자
$recent_winners = array();
$sql = "SELECT h.*, m.mb_nick, m.mb_name
        FROM {$g5['g5_prefix']}randombox_history h
        LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id
        WHERE h.rb_id = '{$rb_id}'
        AND h.rbi_grade IN ('rare', 'epic', 'legendary')
        ORDER BY h.rbh_created_at DESC
        LIMIT 5";
$result = sql_query($sql);

while ($row = sql_fetch_array($result)) {
    // 시간 계산
    $time_diff = time() - strtotime($row['rbh_created_at']);
    if ($time_diff < 60) {
        $time_ago = '방금 전';
    } elseif ($time_diff < 3600) {
        $time_ago = floor($time_diff / 60) . '분 전';
    } elseif ($time_diff < 86400) {
        $time_ago = floor($time_diff / 3600) . '시간 전';
    } else {
        $time_ago = date('m.d', strtotime($row['rbh_created_at']));
    }
    
    // 닉네임 마스킹
    $display_name = $row['mb_nick'] ?: $row['mb_name'];
    if (mb_strlen($display_name) > 2) {
        $display_name = mb_substr($display_name, 0, 1) . str_repeat('*', mb_strlen($display_name) - 2) . mb_substr($display_name, -1);
    }
    
    $recent_winners[] = array(
        'display_name' => $display_name,
        'item_name' => $row['rbi_name'],
        'grade' => $row['rbi_grade'],
        'time_ago' => $time_ago
    );
}

// 응답 데이터 구성
$response_data = array(
    'id' => (int)$box['rb_id'],
    'name' => $box['rb_name'],
    'desc' => nl2br($box['rb_desc']),
    'price' => (int)$box['rb_price'],
    'image' => $box_img,
    'type' => $box['rb_type'],
    'sold_qty' => (int)$box['rb_sold_qty'],
    'total_sold' => (int)$stats['total_sold'],
    'unique_buyers' => (int)$stats['unique_buyers'],
    'can_purchase' => $can_purchase,
    'purchase_message' => $purchase_message,
    'show_items' => true, // 항상 아이템 표시
    'show_probability' => true, // 항상 확률 표시
    'items' => $items,
    'recent_winners' => $recent_winners
);

// 판매 기간 정보
if ($box['rb_start_date'] && $box['rb_end_date']) {
    $response_data['sale_period'] = date('Y.m.d', strtotime($box['rb_start_date'])) . ' ~ ' . date('Y.m.d', strtotime($box['rb_end_date']));
}

// 구매 제한 정보
if ($box['rb_limit_qty'] > 0) {
    $response_data['daily_limit'] = $box['rb_limit_qty'];
}

if ($box['rb_total_qty'] > 0) {
    $response_data['total_limit'] = $box['rb_total_qty'];
    $response_data['remaining_qty'] = $box['rb_total_qty'] - $box['rb_sold_qty'];
}

$response['status'] = true;
$response['data'] = $response_data;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>