<?php
/*
 * 파일명: chat_handler_debug.php
 * 위치: /chat/ajax/chat_handler_debug.php
 * 기능: 디버깅용 채팅 핸들러
 * 작성일: 2025-07-13
 */

// 에러 표시 켜기 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON 응답 헤더
header('Content-Type: application/json; charset=utf-8');

try {
    include_once('../common.php');
    include_once('./lib/chat.lib.php');
    
    // chat_admin.lib.php 파일 존재 확인
    $admin_lib_path = '../lib/chat_admin.lib.php';
    if (!file_exists($admin_lib_path)) {
        die(json_encode(['success' => false, 'message' => 'chat_admin.lib.php 파일이 없습니다.']));
    }
    include_once($admin_lib_path);

    // 로그인 체크
    if (!$is_member) {
        die(json_encode(['success' => false, 'message' => '로그인이 필요합니다.']));
    }

    // JSON 입력 받기
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);

    // 입력 데이터 확인
    if (!$input || !is_array($input)) {
        die(json_encode(['success' => false, 'message' => '잘못된 요청입니다. (no data)']));
    }

    $action = isset($input['action']) ? $input['action'] : '';

    // delete_message 액션만 처리
    if ($action === 'delete_message') {
        $msg_id = isset($input['msg_id']) ? (int)$input['msg_id'] : 0;
        
        if (!$msg_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다. (메시지 ID 없음)']));
        }
        
        // 메시지 정보 가져오기
        $sql = " SELECT room_id, mb_id FROM g5_chat_message WHERE msg_id = '".(int)$msg_id."' ";
        $msg = sql_fetch($sql);
        
        if (!$msg) {
            die(json_encode(['success' => false, 'message' => '메시지를 찾을 수 없습니다.']));
        }
        
        // 권한 확인
        $user_role = check_user_role($msg['room_id'], $member['mb_id']);
        $is_admin_role = in_array($user_role, array('owner', 'admin')) || $is_admin;
        
        if ($msg['mb_id'] !== $member['mb_id'] && !$is_admin_role) {
            die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
        }
        
        // delete_message 함수 존재 확인
        if (!function_exists('delete_message')) {
            die(json_encode(['success' => false, 'message' => 'delete_message 함수가 정의되지 않았습니다.']));
        }
        
        // 삭제 시도
        $result = delete_message($msg_id, $member['mb_id'], $is_admin_role);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '메시지 삭제에 실패했습니다.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => '지원하지 않는 액션입니다.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '오류: ' . $e->getMessage()]);
}