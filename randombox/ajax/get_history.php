<?php
/*
 * 파일명: get_history.php
 * 위치: /randombox/ajax/
 * 기능: 구매내역 조회 (AJAX)
 * 작성일: 2025-01-04
 * 수정일: 2025-07-17
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
$start_date = isset($_GET['start_date']) ? clean_xss_tags($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? clean_xss_tags($_GET['end_date']) : '';
$rb_id = isset($_GET['rb_id']) ? (int)$_GET['rb_id'] : 0;
$grade = isset($_GET['grade']) ? clean_xss_tags($_GET['grade']) : '';

// 날짜 처리 개선 (days 파라미터 처리)
$days = isset($_GET['days']) ? clean_xss_tags($_GET['days']) : '30';
if (!$start_date && !$end_date && $days !== 'all') {
    $start_date = date('Y-m-d', strtotime('-' . (int)$days . ' days'));
    $end_date = date('Y-m-d');
}

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
$response['total_count'] = (int)$stats['total_count'];

// ===================================
// 페이징 계산
// ===================================

$total_pages = ceil($stats['total_count'] / $rows);
$from_record = ($page - 1) * $rows;
$response['total_pages'] = $total_pages;
$response['current_page'] = $page;

// ===================================
// 목록 조회 - JOIN으로 최적화
// ===================================

$sql = "SELECT h.*, 
        b.rb_name, b.rb_image as box_image,
        i.rbi_image
        FROM {$g5['g5_prefix']}randombox_history h
        LEFT JOIN {$g5['g5_prefix']}randombox b ON h.rb_id = b.rb_id
        LEFT JOIN {$g5['g5_prefix']}randombox_items i ON h.rbi_id = i.rbi_id
        {$sql_search} 
        ORDER BY h.rbh_created_at DESC 
        LIMIT {$from_record}, {$rows}";

$result = sql_query($sql);

$list = array();
while ($row = sql_fetch_array($result)) {
    // 박스 이미지 처리
    $box_image = './img/box-default.png';
    if ($row['box_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$row['box_image'])) {
        $box_image = G5_DATA_URL.'/randombox/box/'.$row['box_image'];
    }
    
    // 아이템 이미지 처리
    $item_image = './img/item-default.png';
    if ($row['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$row['rbi_image'])) {
        $item_image = G5_DATA_URL.'/randombox/item/'.$row['rbi_image'];
    }
    
    $list[] = array(
        'rbh_id' => $row['rbh_id'],
        'rb_id' => $row['rb_id'],
        'rb_name' => $row['rb_name'],
        'rb_price' => (int)$row['rb_price'],
        'rbi_id' => $row['rbi_id'],
        'rbi_name' => $row['rbi_name'],
        'rbi_grade' => $row['rbi_grade'],
        'rbi_value' => (int)$row['rbi_value'],
        'rbh_status' => $row['rbh_status'],
        'purchase_date' => $row['rbh_created_at'],
        'purchase_date_format' => date('Y-m-d H:i', strtotime($row['rbh_created_at'])),
        'item_image' => $item_image,
        'box_image' => $box_image
    );
}

$response['status'] = true;
$response['list'] = $list;

// 디버깅용 정보 (개발 시에만 사용)
if ($is_admin && isset($_GET['debug'])) {
    $response['debug'] = array(
        'sql' => $sql,
        'params' => array(
            'page' => $page,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'rb_id' => $rb_id,
            'grade' => $grade
        )
    );
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>