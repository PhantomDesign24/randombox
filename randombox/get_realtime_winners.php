<?php
/*
 * 파일명: get_realtime_winners.php
 * 위치: /randombox/ajax/
 * 기능: 실시간 당첨 현황 조회 (AJAX)
 * 작성일: 2025-01-04
 */

include_once('../../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'winners' => array()
);

// 실시간 당첨 현황이 활성화된 경우만
if (get_randombox_config('enable_realtime')) {
    $winners = get_recent_winners(10);
    
    foreach ($winners as &$winner) {
        // 시간 표시 (몇 분 전 형식)
        $time_diff = time() - strtotime($winner['rbh_created_at']);
        
        if ($time_diff < 60) {
            $winner['time_ago'] = '방금 전';
        } elseif ($time_diff < 3600) {
            $winner['time_ago'] = floor($time_diff / 60) . '분 전';
        } elseif ($time_diff < 86400) {
            $winner['time_ago'] = floor($time_diff / 3600) . '시간 전';
        } else {
            $winner['time_ago'] = date('m-d', strtotime($winner['rbh_created_at']));
        }
    }
    
    $response['status'] = true;
    $response['winners'] = $winners;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>