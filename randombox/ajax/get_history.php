<?php
/*
 * 파일명: get_history.php
 * 위치: /randombox/ajax/
 * 기능: 구매내역 조회 (AJAX)
 * 작성일: 2025-01-04
 */

include_once('../../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'msg' => '',
    'list' => array(),
    'stats' => array(),
    'total_count' => 0,
    'total_pages' => 0
);

// 로그인 체크
if (!$member['mb_id']) {
    $response['msg'] = '로그인이 필요합니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 파라미터 처리
// ===================================

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows = 20;
$start_date = isset($_GET['start_date']) ? clean_xss_tags($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? clean_xss_tags($_GET['end_date']) : date('Y-m-d');
$rb_id = isset($_GET['rb_id']) ? (int)$_GET['rb_id'] : 0;
$grade = isset($_GET['grade']) ? clean_xss_tags($_GET['grade']) : '';

// ===================================
// 검색 조건 생성
// ===================================

$sql_search = " WHERE mb_id = '{$member['mb_id']}' ";

if ($start_date && $end_date) {
    $sql_search .= " AND DATE(rbh_created_at) BETWEEN '{$start_date}' AND '{$end_date}' ";
}

if ($rb_id) {
    $sql_search .= " AND rb_id = '{$rb_id}' ";
}

if ($grade) {
    $sql_search .= " AND rbi_grade = '{$grade}' ";
}

// ===================================
// 통계 조회
// ===================================

$sql = "SELECT 
        COUNT(*) as total_count,
        IFNULL(SUM(rb_price), 0) as total_spent,
        IFNULL(SUM(rbi_value), 0) as total_earned,
        COUNT(CASE WHEN rbi_grade IN ('rare', 'epic', 'legendary') THEN 1 END) as rare_count
        FROM {$g5['g5_prefix']}randombox_history 
        {$sql_search}";
$stats = sql_fetch($sql);

$response['stats'] = $stats;
$response['total_count'] = $stats['total_count'];

// ===================================
// 페이징 계산
// ===================================

$total_pages = ceil($stats['total_count'] / $rows);
$from_record = ($page - 1) * $rows;

$response['total_pages'] = $total_pages;

// ===================================
// 목록 조회
// ===================================

$sql = "SELECT * FROM {$g5['g5_prefix']}randombox_history 
        {$sql_search} 
        ORDER BY rbh_created_at DESC 
        LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

$list = array();
while ($row = sql_fetch_array($result)) {
    // 아이템 이미지 처리
    $item_image = '';
    if ($row['rbi_id']) {
        $sql2 = "SELECT rbi_image FROM {$g5['g5_prefix']}randombox_items WHERE rbi_id = '{$row['rbi_id']}'";
        $item = sql_fetch($sql2);
        if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
            $item_image = G5_DATA_URL.'/randombox/item/'.$item['rbi_image'];
        }
    }
    
    $list[] = array(
        'rbh_id' => $row['rbh_id'],
        'rb_id' => $row['rb_id'],
        'rb_name' => $row['rb_name'],
        'rb_price' => $row['rb_price'],
        'rbi_id' => $row['rbi_id'],
        'rbi_name' => $row['rbi_name'],
        'rbi_grade' => $row['rbi_grade'],
        'rbi_value' => $row['rbi_value'],
        'rbh_status' => $row['rbh_status'],
        'purchase_date' => date('Y-m-d H:i', strtotime($row['rbh_created_at'])),
        'item_image' => $item_image
    );
}

$response['status'] = true;
$response['list'] = $list;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>