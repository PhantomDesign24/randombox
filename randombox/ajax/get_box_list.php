<?php
/*
 * 파일명: get_box_list.php
 * 위치: /randombox/ajax/
 * 기능: 박스 목록 조회 (AJAX)
 * 작성일: 2025-01-04
 */

include_once('../../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'boxes' => array()
);

// 사용자가 구매한 박스 목록만 조회
$sql = "SELECT DISTINCT rb_id, rb_name 
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}' 
        ORDER BY rb_name";
$result = sql_query($sql);

$boxes = array();
while ($row = sql_fetch_array($result)) {
    $boxes[] = array(
        'rb_id' => $row['rb_id'],
        'rb_name' => $row['rb_name']
    );
}

$response['status'] = true;
$response['boxes'] = $boxes;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>