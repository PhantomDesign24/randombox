<?php
/*
 * 파일명: chat_admin.lib.php
 * 위치: /chat/lib/chat_admin.lib.php
 * 기능: 채팅방 관리자 기능 라이브러리
 * 작성일: 2025-07-13
 */

if (!defined('_GNUBOARD_')) exit;

// ===================================
// 공지사항 관련 함수
// ===================================

/* 공지사항 설정 */
function set_room_notice($room_id, $mb_id, $notice_content) {
    global $g5;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 기존 공지사항 비활성화
    $sql = " UPDATE g5_chat_notice 
            SET is_active = 0, 
                updated_at = '".$current_time."' 
            WHERE room_id = '".(int)$room_id."' 
            AND is_active = 1 ";
    sql_query($sql);
    
    // 새 공지사항 추가
    if ($notice_content) {
        $sql = " INSERT INTO g5_chat_notice 
                SET room_id = '".(int)$room_id."',
                    mb_id = '".sql_real_escape_string($mb_id)."',
                    notice_content = '".sql_real_escape_string($notice_content)."',
                    created_at = '".$current_time."',
                    updated_at = '".$current_time."' ";
        sql_query($sql);
        
        return sql_insert_id();
    }
    
    return true;
}

/* 공지사항 가져오기 */
function get_room_notice($room_id) {
    global $g5;
    
    $sql = " SELECT n.*, m.mb_nick 
            FROM g5_chat_notice n
            LEFT JOIN {$g5['member_table']} m ON n.mb_id = m.mb_id
            WHERE n.room_id = '".(int)$room_id."' 
            AND n.is_active = 1 
            ORDER BY n.notice_id DESC 
            LIMIT 1 ";
    
    return sql_fetch($sql);
}

// ===================================
// 사용자 차단 관련 함수
// ===================================

/* 사용자 차단 (음소거/강퇴) */
function ban_user($room_id, $target_mb_id, $banned_by, $ban_type = 'mute', $duration = null, $reason = '') {
    global $g5;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 만료 시간 계산
    $expire_at = null;
    if ($duration) {
        $expire = clone $now;
        $expire->add(new DateInterval('PT'.$duration.'M'));
        $expire_at = $expire->format('Y-m-d H:i:s');
    }
    
    // 기존 차단 해제
    $sql = " UPDATE g5_chat_ban 
            SET is_active = 0 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($target_mb_id)."' 
            AND is_active = 1 ";
    sql_query($sql);
    
    // 새 차단 추가
    $sql = " INSERT INTO g5_chat_ban 
            SET room_id = '".(int)$room_id."',
                mb_id = '".sql_real_escape_string($target_mb_id)."',
                banned_by = '".sql_real_escape_string($banned_by)."',
                ban_type = '".sql_real_escape_string($ban_type)."',
                ban_duration = ".($duration ? "'".(int)$duration."'" : "NULL").",
                ban_reason = '".sql_real_escape_string($reason)."',
                banned_at = '".$current_time."',
                expire_at = ".($expire_at ? "'".$expire_at."'" : "NULL").",
                is_active = 1 ";
    sql_query($sql);
    
    // 강퇴인 경우 참여자에서 제거
    if ($ban_type === 'kick') {
        leave_chat_room($room_id, $target_mb_id);
    }
    
    return sql_insert_id();
}

/* 차단 해제 */
function unban_user($room_id, $target_mb_id) {
    global $g5;
    
    $sql = " UPDATE g5_chat_ban 
            SET is_active = 0 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($target_mb_id)."' 
            AND is_active = 1 ";
    sql_query($sql);
    
    return true;
}

/* 사용자 차단 확인 */
function check_user_ban($room_id, $mb_id) {
    global $g5;
    
    $now = date('Y-m-d H:i:s');
    
    // 만료되지 않은 활성 차단 확인
    $sql = " SELECT * FROM g5_chat_ban 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($mb_id)."' 
            AND is_active = 1 
            AND (expire_at IS NULL OR expire_at > '".$now."') 
            ORDER BY ban_id DESC 
            LIMIT 1 ";
    
    $ban = sql_fetch($sql);
    
    // 만료된 차단 자동 해제
    if ($ban && $ban['expire_at'] && $ban['expire_at'] <= $now) {
        unban_user($room_id, $mb_id);
        return false;
    }
    
    return $ban;
}

/* 차단된 사용자 목록 */
function get_banned_users($room_id) {
    global $g5;
    
    $now = date('Y-m-d H:i:s');
    
    $sql = " SELECT b.*, m.mb_nick, m.mb_level,
            bm.mb_nick as banned_by_nick
            FROM g5_chat_ban b
            LEFT JOIN {$g5['member_table']} m ON b.mb_id = m.mb_id
            LEFT JOIN {$g5['member_table']} bm ON b.banned_by = bm.mb_id
            WHERE b.room_id = '".(int)$room_id."' 
            AND b.is_active = 1 
            AND (b.expire_at IS NULL OR b.expire_at > '".$now."')
            ORDER BY b.banned_at DESC ";
    
    $result = sql_query($sql);
    $users = array();
    
    while ($row = sql_fetch_array($result)) {
        $users[] = $row;
    }
    
    return $users;
}

// ===================================
// 채팅방 설정 관련 함수
// ===================================

/* 슬로우 모드 설정 */
function set_slow_mode($room_id, $seconds) {
    global $g5;
    
    $sql = " UPDATE g5_chat_room 
            SET slow_mode = '".(int)$seconds."' 
            WHERE room_id = '".(int)$room_id."' ";
    sql_query($sql);
    
    return true;
}

/* 읽기 전용 모드 설정 */
function set_readonly_mode($room_id, $is_readonly) {
    global $g5;
    
    $sql = " UPDATE g5_chat_room 
            SET is_readonly = '".(int)$is_readonly."' 
            WHERE room_id = '".(int)$room_id."' ";
    sql_query($sql);
    
    return true;
}

/* 최소 레벨 설정 */
function set_min_level($room_id, $level) {
    global $g5;
    
    $sql = " UPDATE g5_chat_room 
            SET min_level = '".(int)$level."' 
            WHERE room_id = '".(int)$room_id."' ";
    sql_query($sql);
    
    return true;
}

/* 메시지 삭제 */
function delete_message($msg_id, $mb_id, $is_admin = false) {
    global $g5;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 먼저 is_deleted 컬럼이 있는지 확인
    $sql = "SHOW COLUMNS FROM g5_chat_message LIKE 'is_deleted'";
    $result = sql_query($sql);
    
    if (sql_num_rows($result) == 0) {
        // is_deleted 컬럼이 없으면 추가
        $sql = "ALTER TABLE g5_chat_message 
                ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER message,
                ADD COLUMN deleted_at DATETIME DEFAULT NULL AFTER is_deleted,
                ADD COLUMN deleted_by VARCHAR(20) DEFAULT NULL AFTER deleted_at";
        sql_query($sql);
    }
    
    // 관리자가 아닌 경우 본인 메시지만 삭제 가능
    $where = " msg_id = '".(int)$msg_id."' ";
    if (!$is_admin) {
        $where .= " AND mb_id = '".sql_real_escape_string($mb_id)."' ";
    }
    
    $sql = " UPDATE g5_chat_message 
            SET is_deleted = 1,
                deleted_at = '".$current_time."',
                deleted_by = '".sql_real_escape_string($mb_id)."'
            WHERE {$where} ";
    
    $result = sql_query($sql);
    
    // 영향받은 행이 있는지 확인
    if (sql_affected_rows() > 0) {
        return true;
    }
    
    return false;
}

/* 마지막 메시지 시간 확인 (슬로우 모드용) */
function get_last_message_time($room_id, $mb_id) {
    global $g5;
    
    $sql = " SELECT created_at 
            FROM g5_chat_message 
            WHERE room_id = '".(int)$room_id."' 
            AND mb_id = '".sql_real_escape_string($mb_id)."' 
            AND is_deleted = 0 
            ORDER BY msg_id DESC 
            LIMIT 1 ";
    
    $row = sql_fetch($sql);
    return $row ? $row['created_at'] : null;
}

/* 관리자 활동 로그 */
function log_admin_action($room_id, $admin_id, $action, $target_id = null, $details = '') {
    global $g5;
    
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $current_time = $now->format('Y-m-d H:i:s');
    
    // 시스템 메시지로 기록
    $message = '';
    switch ($action) {
        case 'set_notice':
            $message = '공지사항이 설정되었습니다.';
            break;
        case 'ban_user':
            $message = '사용자가 차단되었습니다.';
            break;
        case 'unban_user':
            $message = '사용자 차단이 해제되었습니다.';
            break;
        case 'delete_message':
            $message = '메시지가 삭제되었습니다.';
            break;
        case 'set_slow_mode':
            $message = '슬로우 모드가 설정되었습니다.';
            break;
        case 'set_readonly':
            $message = '읽기 전용 모드가 설정되었습니다.';
            break;
    }
    
    if ($message) {
        $sql = " INSERT INTO g5_chat_message 
                SET room_id = '".(int)$room_id."',
                    mb_id = 'system',
                    mb_nick = '시스템',
                    message = '".sql_real_escape_string($message)."',
                    msg_type = 'system',
                    created_at = '".$current_time."' ";
        sql_query($sql);
    }
}