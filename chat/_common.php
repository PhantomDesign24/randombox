<?php
/*
 * 파일명: _common.php
 * 위치: /chat/_common.php
 * 기능: 채팅 공통 설정 파일
 * 작성일: 2025-07-12
 */

include_once('../common.php');

// 채팅 경로 상수 정의
define('G5_CHAT_PATH', G5_PATH.'/chat');
define('G5_CHAT_URL', G5_URL.'/chat');

// 시간대 설정 (한국 시간)
ini_set('date.timezone', 'Asia/Seoul');
date_default_timezone_set('Asia/Seoul');
?>