<?php
/*
 * 파일명: _common.php
 * 위치: /randombox/
 * 기능: 랜덤박스 사용자 페이지 공통 설정
 * 작성일: 2025-01-04
 */

include_once('../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

// ===================================
// 시스템 상태 확인
// ===================================

/* 시스템 활성화 확인 */
if (!get_randombox_config('system_enable')) {
    alert('랜덤박스 시스템이 비활성화 상태입니다.', G5_URL);
}

/* 점검 모드 확인 */
if (get_randombox_config('maintenance_mode') && !$is_admin) {
    $maintenance_msg = get_randombox_config('maintenance_msg');
    alert($maintenance_msg ? $maintenance_msg : '시스템 점검 중입니다.', G5_URL);
}

/* 회원 전용 */
if (!$member['mb_id']) {
    alert('로그인 후 이용해 주세요.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
}

/* 레벨 확인 */
$min_level = (int)get_randombox_config('min_level');
if ($member['mb_level'] < $min_level) {
    alert("레벨 {$min_level} 이상만 이용 가능합니다.", G5_URL);
}

// ===================================
// 페이지 설정
// ===================================

/* 페이지 타이틀 */
$g5['title'] = '랜덤박스';

/* 스킨 경로 설정 */
$randombox_skin_path = G5_SKIN_PATH.'/randombox';
$randombox_skin_url = G5_SKIN_URL.'/randombox';

// 기본 스킨이 없으면 플러그인 내부 기본 템플릿 사용
if (!is_dir($randombox_skin_path)) {
    $randombox_skin_path = G5_PLUGIN_PATH.'/randombox/skin';
    $randombox_skin_url = G5_PLUGIN_URL.'/randombox/skin';
}
?>