<?php
/*
 * 파일명: chat.lib.php
 * 위치: /chat/lib/chat.lib.php
 * 기능: 채팅 관련 함수 라이브러리
 * 작성일: 2025-07-12
 */

if (!defined('_GNUBOARD_')) exit;

// ===================================
// 채팅방 관련 함수
// ===================================

/* 채팅방 생성 */
function create_chat_room($room_name, $room_type = 'public') {
    global $g5, $member;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    $sql = " INSERT INTO g5_chat_room 
            SET room_name = '".sql_real_escape_string($room_name)."',
                room_type = '".sql_real_escape_string($room_type)."',
                created_by = '".sql_real_escape_string($member['mb_id'])."',
                created_at = '".$current_time."' ";
    
    sql_query($sql);
    
    $room_id = sql_insert_id();
    
    // 생성자를 소유자로 추가
    join_chat_room($room_id, $member['mb_id'], $member['mb_nick'], 'owner');
    
    return $room_id;
}

/* 채팅방 목록 조회 */
function get_chat_rooms($page = 1, $limit = 20) {
    global $g5;
    
    $offset = ($page - 1) * $limit;
    
    // 60초 이내 하트비트가 있는 활성 사용자만 카운트
    $timeout = date('Y-m-d H:i:s', strtotime('-60 seconds'));
    
    $sql = "SELECT r.*, 
            (SELECT COUNT(DISTINCT p.mb_id) 
             FROM g5_chat_participant p 
             INNER JOIN g5_chat_heartbeat h ON p.mb_id = h.mb_id AND p.room_id = h.room_id
             WHERE p.room_id = r.room_id 
             AND p.is_active = 1 
             AND h.last_heartbeat > '".$timeout."') as user_count,
            (SELECT MAX(created_at) FROM g5_chat_message m 
             WHERE m.room_id = r.room_id) as last_message_time
            FROM g5_chat_room r
            WHERE r.is_active = 1
            ORDER BY last_message_time DESC, r.created_at DESC
            LIMIT {$offset}, {$limit}";
    
    $result = sql_query($sql);
    $rooms = array();
    
    while ($row = sql_fetch_array($result)) {
        $rooms[] = $row;
    }
    
    return $rooms;
}

/* 채팅방 입장 */
function join_chat_room($room_id, $mb_id, $mb_nick, $role = 'member') {
    global $g5;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 이미 참여중인지 확인
    $sql = " SELECT * FROM g5_chat_participant 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($mb_id)."' ";
    $row = sql_fetch($sql);
    
    if ($row) {
        // 업데이트 (role은 변경하지 않음)
        $sql = " UPDATE g5_chat_participant 
                SET is_active = 1, 
                    joined_at = '".$current_time."' 
                WHERE room_id = '".(int)$room_id."' 
                AND mb_id = '".sql_real_escape_string($mb_id)."' ";
        sql_query($sql);
    } else {
        // 신규 추가
        $sql = " INSERT INTO g5_chat_participant 
                SET room_id = '".(int)$room_id."',
                    mb_id = '".sql_real_escape_string($mb_id)."',
                    mb_nick = '".sql_real_escape_string($mb_nick)."',
                    role = '".sql_real_escape_string($role)."',
                    joined_at = '".$current_time."',
                    is_active = 1 ";
        sql_query($sql);
    }
    
    // 하트비트 초기화
    update_heartbeat($mb_id, $room_id);
}

/* 채팅방 나가기 */
function leave_chat_room($room_id, $mb_id) {
    global $g5;
    
    // 참여자 비활성화
    $sql = " UPDATE g5_chat_participant 
            SET is_active = 0 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($mb_id)."' ";
    
    sql_query($sql);
    
    // 하트비트 삭제
    $sql = " DELETE FROM g5_chat_heartbeat 
            WHERE mb_id = '".sql_real_escape_string($mb_id)."' 
            AND room_id = '".(int)$room_id."' ";
    
    sql_query($sql);
    
    return true;
}

// ===================================
// 메시지 관련 함수
// ===================================

/* 메시지 전송 */
function send_message($room_id, $message, $reply_to_msg_id = null) {
    global $g5, $member;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 비활성 사용자인 경우 활성화
    $sql = " SELECT is_active FROM g5_chat_participant 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($member['mb_id'])."' ";
    $participant = sql_fetch($sql);
    
    if ($participant && $participant['is_active'] == 0) {
        // 참여자 활성화
        $sql = " UPDATE g5_chat_participant 
                SET is_active = 1, 
                    joined_at = '".$current_time."' 
                WHERE room_id = '".(int)$room_id."' 
                AND mb_id = '".sql_real_escape_string($member['mb_id'])."' ";
        sql_query($sql);
    }
    
    // 답글 정보 처리
    $reply_to_nick = null;
    if ($reply_to_msg_id) {
        $sql = " SELECT mb_nick FROM g5_chat_message 
                WHERE msg_id = '".(int)$reply_to_msg_id."' ";
        $reply_msg = sql_fetch($sql);
        if ($reply_msg) {
            $reply_to_nick = $reply_msg['mb_nick'];
        }
    }
    
    // 메시지 저장
    $sql = " INSERT INTO g5_chat_message 
            SET room_id = '".(int)$room_id."',
                mb_id = '".sql_real_escape_string($member['mb_id'])."',
                mb_nick = '".sql_real_escape_string($member['mb_nick'])."',
                message = '".sql_real_escape_string($message)."',
                reply_to_msg_id = ".($reply_to_msg_id ? "'".(int)$reply_to_msg_id."'" : "NULL").",
                reply_to_nick = ".($reply_to_nick ? "'".sql_real_escape_string($reply_to_nick)."'" : "NULL").",
                created_at = '".$current_time."' ";
    
    sql_query($sql);
    $msg_id = sql_insert_id();
    
    // 멘션 처리
    process_mentions($room_id, $msg_id, $message, $member['mb_id']);
    
    // 하트비트 업데이트
    update_heartbeat($member['mb_id'], $room_id);
    
    return $msg_id;
}

/* 메시지 조회 함수 수정 */
function get_messages($room_id, $last_msg_id = 0, $limit = 50) {
    global $g5;
    
    $where = " m.room_id = '".(int)$room_id."' ";
    
    // is_deleted = 0 조건 제거하여 삭제된 메시지도 표시
    // 삭제된 메시지는 클라이언트에서 처리
    
    if ($last_msg_id > 0) {
        $where .= " AND m.msg_id > '".(int)$last_msg_id."' ";
    }
    
    $sql = " SELECT m.*, mem.mb_level,
            reply.message as reply_message
            FROM g5_chat_message m
            LEFT JOIN {$g5['member_table']} mem ON m.mb_id = mem.mb_id
            LEFT JOIN g5_chat_message reply ON m.reply_to_msg_id = reply.msg_id
            WHERE {$where}
            ORDER BY m.msg_id DESC 
            LIMIT {$limit} ";
    
    $result = sql_query($sql);
    $messages = array();
    
    while ($row = sql_fetch_array($result)) {
        $messages[] = $row;
    }
    
    return array_reverse($messages);
}

// ===================================
// 사용자 관련 함수
// ===================================

/* 채팅방 사용자 목록 */
function get_room_users($room_id) {
    global $g5;
    
    // 60초 이내 하트비트가 있는 사용자만 조회
    $timeout = date('Y-m-d H:i:s', strtotime('-60 seconds'));
    
    $sql = " SELECT p.*, h.last_heartbeat, m.mb_level, m.mb_nick as member_nick
            FROM g5_chat_participant p
            LEFT JOIN g5_chat_heartbeat h 
                ON p.mb_id = h.mb_id AND p.room_id = h.room_id
            LEFT JOIN {$g5['member_table']} m
                ON p.mb_id = m.mb_id
            WHERE p.room_id = '".(int)$room_id."' 
                AND p.is_active = 1 
                AND h.last_heartbeat > '".$timeout."'
            ORDER BY p.role DESC, m.mb_level DESC, p.mb_nick ";
    
    $result = sql_query($sql);
    
    $users = array();
    while ($row = sql_fetch_array($result)) {
        $users[] = $row;
    }
    
    return $users;
}

/* 하트비트 업데이트 */
function update_heartbeat($mb_id, $room_id) {
    global $g5;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 기존 하트비트 확인
    $sql = " SELECT * FROM g5_chat_heartbeat 
            WHERE mb_id = '".sql_real_escape_string($mb_id)."' 
            AND room_id = '".(int)$room_id."' ";
    $row = sql_fetch($sql);
    
    if ($row) {
        // 업데이트
        $sql = " UPDATE g5_chat_heartbeat 
                SET last_heartbeat = '".$current_time."' 
                WHERE mb_id = '".sql_real_escape_string($mb_id)."' 
                AND room_id = '".(int)$room_id."' ";
        sql_query($sql);
    } else {
        // 신규 추가
        $sql = " INSERT INTO g5_chat_heartbeat 
                SET mb_id = '".sql_real_escape_string($mb_id)."',
                    room_id = '".(int)$room_id."',
                    last_heartbeat = '".$current_time."' ";
        sql_query($sql);
    }
    
    // 오래된 하트비트 정리
    cleanup_heartbeat();
}

/* 오래된 하트비트 정리 */
function cleanup_heartbeat() {
    global $g5;
    
    $timeout = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    
    // 5분 이상 된 하트비트 삭제
    $sql = " DELETE FROM g5_chat_heartbeat 
            WHERE last_heartbeat < '".$timeout."' ";
    
    sql_query($sql);
}

// ===================================
// 관리 관련 함수
// ===================================

/* 채팅방 관리자 설정 */
function set_room_admin($room_id, $mb_id, $role) {
    global $g5;
    
    // 허용된 역할만 설정
    if (!in_array($role, array('member', 'admin'))) {
        return false;
    }
    
    $sql = " UPDATE g5_chat_participant 
            SET role = '".sql_real_escape_string($role)."' 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($mb_id)."' ";
    
    sql_query($sql);
    
    return true;
}

/* 사용자 권한 확인 */
function check_user_role($room_id, $mb_id) {
    global $g5;
    
    $sql = " SELECT role FROM g5_chat_participant 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($mb_id)."' ";
    
    $row = sql_fetch($sql);
    
    return $row ? $row['role'] : false;
}

/* 채팅방 정보 조회 (참여자 역할 포함) */
function get_room_info($room_id, $mb_id = '') {
    global $g5;
    
    $sql = " SELECT r.*, 
            (SELECT mb_nick FROM {$g5['member_table']} WHERE mb_id = r.created_by) as owner_nick
            FROM g5_chat_room r
            WHERE r.room_id = '".(int)$room_id."' 
            AND r.is_active = 1 ";
    
    $room = sql_fetch($sql);
    
    if ($room && $mb_id) {
        $room['user_role'] = check_user_role($room_id, $mb_id);
    }
    
    return $room;
}

// ===================================
// 새로운 기능 함수들
// ===================================

/* 멘션 처리 */
function process_mentions($room_id, $msg_id, $message, $sender_id) {
    global $g5;
    
    // @멘션 패턴 찾기
    preg_match_all('/@(\S+)/', $message, $matches);
    
    if (!empty($matches[1])) {
        $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
        $current_time = $now->format('Y-m-d H:i:s');
        
        foreach ($matches[1] as $mentioned_nick) {
            // 닉네임으로 사용자 찾기
            $sql = " SELECT mb_id FROM {$g5['member_table']} 
                    WHERE mb_nick = '".sql_real_escape_string($mentioned_nick)."' ";
            $user = sql_fetch($sql);
            
            if ($user && $user['mb_id'] != $sender_id) {
                // 멘션 기록 저장
                $sql = " INSERT INTO g5_chat_mention 
                        SET room_id = '".(int)$room_id."',
                            msg_id = '".(int)$msg_id."',
                            mentioned_by = '".sql_real_escape_string($sender_id)."',
                            mentioned_user = '".sql_real_escape_string($user['mb_id'])."',
                            created_at = '".$current_time."' ";
                sql_query($sql);
            }
        }
    }
}

/* 개인 채팅방 생성 또는 찾기 */
function get_or_create_private_room($user1_id, $user2_id) {
    global $g5, $member;
    
    // 사용자 ID 정렬 (일관성을 위해)
    $sorted_ids = array($user1_id, $user2_id);
    sort($sorted_ids);
    $user1 = $sorted_ids[0];
    $user2 = $sorted_ids[1];
    
    // 기존 개인 채팅방 찾기
    $sql = " SELECT room_id FROM g5_chat_private 
            WHERE user1_id = '".sql_real_escape_string($user1)."' 
            AND user2_id = '".sql_real_escape_string($user2)."' ";
    $private = sql_fetch($sql);
    
    if ($private) {
        return $private['room_id'];
    }
    
    // 새 개인 채팅방 생성
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 상대방 닉네임 가져오기
    $other_id = ($user1_id == $member['mb_id']) ? $user2_id : $user1_id;
    $sql = " SELECT mb_nick FROM {$g5['member_table']} 
            WHERE mb_id = '".sql_real_escape_string($other_id)."' ";
    $other = sql_fetch($sql);
    $room_name = $other['mb_nick'] . '님과의 대화';
    
    // 채팅방 생성
    $room_id = create_chat_room($room_name, 'private');
    
    // 개인 채팅방 매핑 저장
    $sql = " INSERT INTO g5_chat_private 
            SET room_id = '".(int)$room_id."',
                user1_id = '".sql_real_escape_string($user1)."',
                user2_id = '".sql_real_escape_string($user2)."',
                created_at = '".$current_time."' ";
    sql_query($sql);
    
    // 상대방도 채팅방에 추가
    join_chat_room($room_id, $other_id, $other['mb_nick']);
    
    return $room_id;
}

/* 사용자 정보 조회 */
function get_user_info($mb_id) {
    global $g5;
    
    $sql = " SELECT mb_id, mb_nick, mb_level, mb_email, mb_datetime 
            FROM {$g5['member_table']} 
            WHERE mb_id = '".sql_real_escape_string($mb_id)."' ";
    
    return sql_fetch($sql);
}

/* 멘션 알림 조회 */
function get_unread_mentions($mb_id) {
    global $g5;
    
    $sql = " SELECT m.*, msg.message, msg.mb_nick as sender_nick, r.room_name
            FROM g5_chat_mention m
            INNER JOIN g5_chat_message msg ON m.msg_id = msg.msg_id
            INNER JOIN g5_chat_room r ON m.room_id = r.room_id
            WHERE m.mentioned_user = '".sql_real_escape_string($mb_id)."'
            AND m.is_read = 0
            ORDER BY m.created_at DESC ";
    
    $result = sql_query($sql);
    $mentions = array();
    
    while ($row = sql_fetch_array($result)) {
        $mentions[] = $row;
    }
    
    return $mentions;
}

/* 멘션 읽음 처리 */
function mark_mentions_read($mb_id, $room_id = null) {
    global $g5;
    
    $sql = " UPDATE g5_chat_mention 
            SET is_read = 1 
            WHERE mentioned_user = '".sql_real_escape_string($mb_id)."' ";
    
    if ($room_id) {
        $sql .= " AND room_id = '".(int)$room_id."' ";
    }
    
    sql_query($sql);
}

/* 채팅 설정 조회 */
function get_chat_setting($setting_name) {
    $sql = " SELECT setting_value FROM g5_chat_settings 
            WHERE setting_name = '".sql_real_escape_string($setting_name)."' ";
    $row = sql_fetch($sql);
    
    return $row ? $row['setting_value'] : null;
}

/* 채팅 설정 저장 */
function save_chat_setting($setting_name, $setting_value) {
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    $sql = " INSERT INTO g5_chat_settings 
            SET setting_name = '".sql_real_escape_string($setting_name)."',
                setting_value = '".sql_real_escape_string($setting_value)."',
                updated_at = '".$current_time."'
            ON DUPLICATE KEY UPDATE
                setting_value = '".sql_real_escape_string($setting_value)."',
                updated_at = '".$current_time."' ";
    
    sql_query($sql);
}