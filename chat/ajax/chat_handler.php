<?php
/*
 * 파일명: chat_handler.php
 * 위치: /chat/ajax/chat_handler.php
 * 기능: 채팅 AJAX 요청 처리 (관리자 기능 포함)
 * 작성일: 2025-07-12
 * 수정일: 2025-07-13
 */

// 오류 표시 끄기 (프로덕션용)
error_reporting(0);
ini_set('display_errors', 0);

include_once('../../common.php');
include_once('../lib/chat.lib.php');
include_once('../lib/chat_admin.lib.php');

// JSON 응답 헤더
header('Content-Type: application/json; charset=utf-8');

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

// ===================================
// 액션별 처리
// ===================================

switch ($action) {
    /* 채팅방 목록 조회 */
    case 'get_room_list':
        $rooms = get_chat_rooms(1, 50);
        
        echo json_encode([
            'success' => true,
            'rooms' => $rooms
        ]);
        break;
    
    /* 채팅방 입장 */
    case 'join_room':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        join_chat_room($room_id, $member['mb_id'], $member['mb_nick']);
        
        echo json_encode(['success' => true]);
        break;
    
    /* 채팅방 생성 */
    case 'create_room':
        $room_name = isset($input['room_name']) ? trim($input['room_name']) : '';
        
        if (!$room_name) {
            die(json_encode(['success' => false, 'message' => '채팅방 이름을 입력하세요.']));
        }
        
        if (mb_strlen($room_name) > 100) {
            die(json_encode(['success' => false, 'message' => '채팅방 이름이 너무 깁니다.']));
        }
        
        $room_id = create_chat_room($room_name);
        
        echo json_encode([
            'success' => true,
            'room_id' => $room_id
        ]);
        break;
    
    /* 메시지 전송 */
    case 'send_message':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        $message = isset($input['message']) ? trim($input['message']) : '';
        $reply_to = isset($input['reply_to']) ? (int)$input['reply_to'] : null;
        
        if (!$room_id || !$message) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        if (mb_strlen($message) > 500) {
            die(json_encode(['success' => false, 'message' => '메시지가 너무 깁니다.']));
        }
        
        // 차단 상태 확인
        $ban_status = check_user_ban($room_id, $member['mb_id']);
        if ($ban_status) {
            if ($ban_status['ban_type'] === 'mute') {
                die(json_encode(['success' => false, 'message' => '음소거 상태입니다. 메시지를 보낼 수 없습니다.']));
            } else if ($ban_status['ban_type'] === 'kick') {
                die(json_encode(['success' => false, 'message' => '채팅방에서 강퇴되었습니다.']));
            }
        }
        
        // 채팅방 설정 확인
        $sql = " SELECT * FROM g5_chat_room WHERE room_id = '".(int)$room_id."' ";
        $room = sql_fetch($sql);
        
        if (!$room) {
            die(json_encode(['success' => false, 'message' => '채팅방을 찾을 수 없습니다.']));
        }
        
        // 읽기 전용 모드 확인
        if ($room['is_readonly']) {
            $user_role = check_user_role($room_id, $member['mb_id']);
            if (!in_array($user_role, array('owner', 'admin')) && !$is_admin) {
                die(json_encode(['success' => false, 'message' => '읽기 전용 모드입니다.']));
            }
        }
        
        // 최소 레벨 확인
        if ($room['min_level'] > $member['mb_level']) {
            die(json_encode(['success' => false, 'message' => '레벨이 부족합니다. (최소 레벨: '.$room['min_level'].')']));
        }
        
        // 슬로우 모드 확인
        if ($room['slow_mode'] > 0) {
            $last_msg_time = get_last_message_time($room_id, $member['mb_id']);
            if ($last_msg_time) {
                $diff = time() - strtotime($last_msg_time);
                if ($diff < $room['slow_mode']) {
                    $remaining = $room['slow_mode'] - $diff;
                    die(json_encode(['success' => false, 'message' => '슬로우 모드: '.$remaining.'초 후에 메시지를 보낼 수 있습니다.']));
                }
            }
        }
        
        // 참여자 확인 (활성/비활성 모두 허용)
        $sql = " SELECT * FROM g5_chat_participant 
                WHERE room_id = '".(int)$room_id."' 
                AND mb_id = '".sql_real_escape_string($member['mb_id'])."' ";
        $participant = sql_fetch($sql);
        
        if (!$participant) {
            // 참여 기록이 없으면 추가
            join_chat_room($room_id, $member['mb_id'], $member['mb_nick']);
        }
        
        $msg_id = send_message($room_id, $message, $reply_to);
        
        echo json_encode([
            'success' => true,
            'msg_id' => $msg_id
        ]);
        break;
    
    /* 메시지 조회 */
    case 'get_messages':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        $last_msg_id = isset($input['last_msg_id']) ? (int)$input['last_msg_id'] : 0;
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        $messages = get_messages($room_id, $last_msg_id);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
        break;
    
    /* 사용자 목록 조회 */
    case 'get_users':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        $users = get_room_users($room_id);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        break;
    
    /* 하트비트 */
    case 'heartbeat':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if ($room_id) {
            update_heartbeat($member['mb_id'], $room_id);
        }
        
        echo json_encode(['success' => true]);
        break;
    
    /* 채팅방 나가기 */
    case 'leave_room':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if ($room_id) {
            try {
                leave_chat_room($room_id, $member['mb_id']);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Leave room error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => true, 'message' => 'No room to leave']);
        }
        break;
    
    /* 채팅방 정보 조회 */
    case 'get_room_info':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        $room = get_room_info($room_id, $member['mb_id']);
        
        if (!$room) {
            die(json_encode(['success' => false, 'message' => '채팅방을 찾을 수 없습니다.']));
        }
        
        echo json_encode([
            'success' => true,
            'room' => $room
        ]);
        break;
    
    /* 관리자 설정 */
    case 'set_admin':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        $target_mb_id = isset($input['target_mb_id']) ? $input['target_mb_id'] : '';
        $role = isset($input['role']) ? $input['role'] : 'member';
        
        if (!$room_id || !$target_mb_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        // 권한 확인
        $user_role = check_user_role($room_id, $member['mb_id']);
        if (!in_array($user_role, array('owner', 'admin'))) {
            die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
        }
        
        // 소유자는 변경 불가
        $target_role = check_user_role($room_id, $target_mb_id);
        if ($target_role === 'owner') {
            die(json_encode(['success' => false, 'message' => '소유자의 권한은 변경할 수 없습니다.']));
        }
        
        $result = set_room_admin($room_id, $target_mb_id, $role);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? '권한이 변경되었습니다.' : '권한 변경에 실패했습니다.'
        ]);
        break;
    
    /* 사용자 정보 조회 */
    case 'get_user_info':
        $mb_id = isset($input['mb_id']) ? $input['mb_id'] : '';
        
        if (!$mb_id) {
            die(json_encode(['success' => false, 'message' => '사용자 ID가 필요합니다.']));
        }
        
        $user = get_user_info($mb_id);
        
        if (!$user) {
            die(json_encode(['success' => false, 'message' => '사용자를 찾을 수 없습니다.']));
        }
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        break;
    
    /* 개인 채팅방 생성 */
    case 'create_private_chat':
        $target_mb_id = isset($input['target_mb_id']) ? $input['target_mb_id'] : '';
        
        if (!$target_mb_id) {
            die(json_encode(['success' => false, 'message' => '대상 사용자가 필요합니다.']));
        }
        
        $room_id = get_or_create_private_room($member['mb_id'], $target_mb_id);
        
        echo json_encode([
            'success' => true,
            'room_id' => $room_id
        ]);
        break;
    
    /* 멘션 알림 조회 */
    case 'get_mentions':
        $mentions = get_unread_mentions($member['mb_id']);
        
        echo json_encode([
            'success' => true,
            'mentions' => $mentions,
            'count' => count($mentions)
        ]);
        break;
    
    /* 멘션 읽음 처리 */
    case 'mark_mentions_read':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : null;
        
        mark_mentions_read($member['mb_id'], $room_id);
        
        echo json_encode(['success' => true]);
        break;
    
    /* 채팅 설정 조회 */
    case 'get_settings':
        $settings = array();
        $setting_names = array('message_retention_days', 'max_file_size', 'allowed_file_types', 'max_users_per_room');
        
        foreach ($setting_names as $name) {
            $settings[$name] = get_chat_setting($name);
        }
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
        break;
    
    /* 채팅 설정 저장 (관리자만) */
    case 'save_settings':
        // 관리자 권한 확인
        if (!$is_admin) {
            die(json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']));
        }
        
        $settings = isset($input['settings']) ? $input['settings'] : array();
        
        foreach ($settings as $name => $value) {
            save_chat_setting($name, $value);
        }
        
        echo json_encode(['success' => true]);
        break;
    
    // ===================================
    // 관리자 기능 추가
    // ===================================
    
    /* 공지사항 설정 */
    case 'set_notice':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        $notice_content = isset($input['notice_content']) ? trim($input['notice_content']) : '';
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        // 권한 확인
        $user_role = check_user_role($room_id, $member['mb_id']);
        if (!in_array($user_role, array('owner', 'admin')) && !$is_admin) {
            die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
        }
        
        $result = set_room_notice($room_id, $member['mb_id'], $notice_content);
        
        // 관리자 활동 로그
        log_admin_action($room_id, $member['mb_id'], 'set_notice');
        
        echo json_encode(['success' => true]);
        break;
    
    /* 공지사항 가져오기 */
    case 'get_notice':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        $notice = get_room_notice($room_id);
        
        echo json_encode([
            'success' => true,
            'notice' => $notice
        ]);
        break;
    
    /* 사용자 차단 */
    case 'ban_user':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        $target_mb_id = isset($input['target_mb_id']) ? $input['target_mb_id'] : '';
        $ban_type = isset($input['ban_type']) ? $input['ban_type'] : 'mute';
        $duration = isset($input['duration']) ? (int)$input['duration'] : null;
        $reason = isset($input['reason']) ? trim($input['reason']) : '';
        
        if (!$room_id || !$target_mb_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        // 권한 확인
        $user_role = check_user_role($room_id, $member['mb_id']);
        if (!in_array($user_role, array('owner', 'admin')) && !$is_admin) {
            die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
        }
        
        // 소유자는 차단 불가
        $target_role = check_user_role($room_id, $target_mb_id);
        if ($target_role === 'owner') {
            die(json_encode(['success' => false, 'message' => '소유자는 차단할 수 없습니다.']));
        }
        
        $result = ban_user($room_id, $target_mb_id, $member['mb_id'], $ban_type, $duration, $reason);
        
        // 관리자 활동 로그
        log_admin_action($room_id, $member['mb_id'], 'ban_user', $target_mb_id, $ban_type);
        
        echo json_encode(['success' => true]);
        break;
    
    /* 차단 해제 */
    case 'unban_user':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        $target_mb_id = isset($input['target_mb_id']) ? $input['target_mb_id'] : '';
        
        if (!$room_id || !$target_mb_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        // 권한 확인
        $user_role = check_user_role($room_id, $member['mb_id']);
        if (!in_array($user_role, array('owner', 'admin')) && !$is_admin) {
            die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
        }
        
        $result = unban_user($room_id, $target_mb_id);
        
        // 관리자 활동 로그
        log_admin_action($room_id, $member['mb_id'], 'unban_user', $target_mb_id);
        
        echo json_encode(['success' => true]);
        break;
    
    /* 차단된 사용자 목록 */
    case 'get_banned_users':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        // 권한 확인
        $user_role = check_user_role($room_id, $member['mb_id']);
        if (!in_array($user_role, array('owner', 'admin')) && !$is_admin) {
            die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
        }
        
        $users = get_banned_users($room_id);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        break;
    
    /* 차단 상태 확인 */
    case 'check_ban_status':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        $ban_status = check_user_ban($room_id, $member['mb_id']);
        
        echo json_encode([
            'success' => true,
            'ban_status' => $ban_status
        ]);
        break;
    
    /* 채팅방 설정 저장 */
    case 'save_room_settings':
        $room_id = isset($input['room_id']) ? (int)$input['room_id'] : 0;
        $settings = isset($input['settings']) ? $input['settings'] : array();
        
        if (!$room_id) {
            die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
        }
        
        // 권한 확인
        $user_role = check_user_role($room_id, $member['mb_id']);
        if (!in_array($user_role, array('owner', 'admin')) && !$is_admin) {
            die(json_encode(['success' => false, 'message' => '권한이 없습니다.']));
        }
        
        // 설정 저장
        if (isset($settings['is_readonly'])) {
            set_readonly_mode($room_id, (int)$settings['is_readonly']);
        }
        
        if (isset($settings['slow_mode'])) {
            set_slow_mode($room_id, (int)$settings['slow_mode']);
            
            // 관리자 활동 로그
            if ($settings['slow_mode'] > 0) {
                log_admin_action($room_id, $member['mb_id'], 'set_slow_mode', null, $settings['slow_mode'].'초');
            }
        }
        
        if (isset($settings['min_level'])) {
            set_min_level($room_id, (int)$settings['min_level']);
        }
        
        // 읽기 전용 모드 로그
        if (isset($settings['is_readonly'])) {
            log_admin_action($room_id, $member['mb_id'], 'set_readonly', null, $settings['is_readonly'] ? '활성화' : '비활성화');
        }
        
        echo json_encode(['success' => true]);
        break;
    
/* 메시지 삭제 - chat_handler.php의 해당 case 부분을 이것으로 교체 */
case 'delete_message':
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
    
    // 권한 확인 (본인 메시지 또는 관리자)
    $user_role = check_user_role($msg['room_id'], $member['mb_id']);
    $is_admin_role = in_array($user_role, array('owner', 'admin')) || $is_admin;
    
    if ($msg['mb_id'] !== $member['mb_id'] && !$is_admin_role) {
        die(json_encode(['success' => false, 'message' => '권한이 없습니다. (본인 메시지가 아니며 관리자도 아님)']));
    }
    
    // 컬럼 존재 여부 확인
    $sql = "SHOW COLUMNS FROM g5_chat_message LIKE 'is_deleted'";
    $col_result = sql_query($sql);
    
    if (sql_num_rows($col_result) == 0) {
        // 컬럼이 없으면 추가
        $alter_sql = "ALTER TABLE g5_chat_message 
                      ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER message,
                      ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER is_deleted,
                      ADD COLUMN deleted_by VARCHAR(20) DEFAULT NULL AFTER deleted_at";
        
        $alter_result = sql_query($alter_sql);
        
        if (!$alter_result) {
            die(json_encode(['success' => false, 'message' => '데이터베이스 구조 업데이트 실패: ' . sql_error()]));
        }
    }
    
    try {
        $result = delete_message($msg_id, $member['mb_id'], $is_admin_role);
        
        if ($result) {
            // 관리자 활동 로그
            if ($is_admin_role && $msg['mb_id'] !== $member['mb_id']) {
                log_admin_action($msg['room_id'], $member['mb_id'], 'delete_message', $msg['mb_id']);
            }
            
            echo json_encode(['success' => true]);
        } else {
            // 실패 이유 확인
            $check_sql = "SELECT * FROM g5_chat_message WHERE msg_id = '".(int)$msg_id."'";
            $check_msg = sql_fetch($check_sql);
            
            if ($check_msg['is_deleted'] == 1) {
                echo json_encode(['success' => true, 'message' => '이미 삭제된 메시지입니다.']);
            } else {
                echo json_encode(['success' => false, 'message' => '메시지 삭제에 실패했습니다. (DB 업데이트 실패)']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => '메시지 삭제 중 오류 발생: ' . $e->getMessage()]);
    }
    break;
    
    /* 알 수 없는 액션 */
    default:
        echo json_encode(['success' => false, 'message' => '알 수 없는 요청입니다.']);
        break;
}
?>