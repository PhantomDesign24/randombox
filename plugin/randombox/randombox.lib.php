<?php
/*
 * 파일명: randombox.lib.php
 * 위치: /plugin/randombox/
 * 기능: 랜덤박스 시스템 핵심 함수 라이브러리
 * 작성일: 2025-01-04
 */

if (!defined('_GNUBOARD_')) exit;

// ===================================
// 설정 관련 함수
// ===================================

/**
 * 랜덤박스 설정값 가져오기
 * 
 * @param string $cfg_name 설정명
 * @return string 설정값
 */
function get_randombox_config($cfg_name) {
    global $g5;
    
    $sql = "SELECT cfg_value FROM {$g5['g5_prefix']}randombox_config WHERE cfg_name = '$cfg_name'";
    $row = sql_fetch($sql);
    
    return $row['cfg_value'];
}

/**
 * 랜덤박스 설정값 저장하기
 * 
 * @param string $cfg_name 설정명
 * @param string $cfg_value 설정값
 * @return boolean
 */
function set_randombox_config($cfg_name, $cfg_value) {
    global $g5;
    
    $sql = "UPDATE {$g5['g5_prefix']}randombox_config SET cfg_value = '$cfg_value' WHERE cfg_name = '$cfg_name'";
    return sql_query($sql);
}

/**
 * 전체 설정 가져오기
 * 
 * @return array
 */
function get_randombox_all_config() {
    global $g5;
    
    $config = array();
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_config";
    $result = sql_query($sql);
    
    while ($row = sql_fetch_array($result)) {
        $config[$row['cfg_name']] = $row['cfg_value'];
    }
    
    return $config;
}

// ===================================
// 박스 관련 함수
// ===================================

/**
 * 랜덤박스 정보 가져오기
 * 
 * @param int $rb_id 박스 ID
 * @return array
 */
function get_randombox($rb_id) {
    global $g5;
    
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox WHERE rb_id = '$rb_id'";
    return sql_fetch($sql);
}

/**
 * 활성화된 랜덤박스 목록 가져오기
 * 
 * @return array
 */
function get_randombox_list() {
    global $g5;
    
    $list = array();
    $now = date('Y-m-d H:i:s');
    
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox 
            WHERE rb_status = 1 
            AND (rb_start_date IS NULL OR rb_start_date <= '$now')
            AND (rb_end_date IS NULL OR rb_end_date >= '$now')
            AND (rb_total_qty = 0 OR rb_sold_qty < rb_total_qty)
            ORDER BY rb_order, rb_id DESC";
    
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
    
    return $list;
}

/**
 * 박스 구매 가능 여부 확인
 * 
 * @param int $rb_id 박스 ID
 * @param string $mb_id 회원 ID
 * @return array
 */
function check_randombox_purchase($rb_id, $mb_id) {
    global $g5, $member;
    
    $result = array('status' => true, 'msg' => '');
    
    // 시스템 활성화 확인
    if (!get_randombox_config('system_enable')) {
        return array('status' => false, 'msg' => '랜덤박스 시스템이 비활성화 상태입니다.');
    }
    
    // 점검 모드 확인
    if (get_randombox_config('maintenance_mode')) {
        return array('status' => false, 'msg' => get_randombox_config('maintenance_msg'));
    }
    
    // 회원 레벨 확인
    $min_level = get_randombox_config('min_level');
    if ($member['mb_level'] < $min_level) {
        return array('status' => false, 'msg' => "레벨 {$min_level} 이상만 이용 가능합니다.");
    }
    
    // 박스 정보 확인
    $box = get_randombox($rb_id);
    if (!$box) {
        return array('status' => false, 'msg' => '존재하지 않는 박스입니다.');
    }
    
    // 박스 상태 확인
    if (!$box['rb_status']) {
        return array('status' => false, 'msg' => '판매 중지된 박스입니다.');
    }
    
    // 판매 기간 확인
    $now = date('Y-m-d H:i:s');
    if ($box['rb_start_date'] && $box['rb_start_date'] > $now) {
        return array('status' => false, 'msg' => '아직 판매 시작 전입니다.');
    }
    if ($box['rb_end_date'] && $box['rb_end_date'] < $now) {
        return array('status' => false, 'msg' => '판매가 종료된 박스입니다.');
    }
    
    // 전체 수량 제한 확인
    if ($box['rb_total_qty'] > 0 && $box['rb_sold_qty'] >= $box['rb_total_qty']) {
        return array('status' => false, 'msg' => '매진된 박스입니다.');
    }
    
    // 일일 구매 제한 확인
    if ($box['rb_limit_qty'] > 0) {
        $today = date('Y-m-d');
        $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history 
                WHERE mb_id = '$mb_id' AND rb_id = '$rb_id' 
                AND DATE(rbh_created_at) = '$today'";
        $row = sql_fetch($sql);
        
        if ($row['cnt'] >= $box['rb_limit_qty']) {
            return array('status' => false, 'msg' => "일일 구매 제한({$box['rb_limit_qty']}개)을 초과했습니다.");
        }
    }
    
    // 포인트 확인
    if ($member['mb_point'] < $box['rb_price']) {
        return array('status' => false, 'msg' => '포인트가 부족합니다.');
    }
    
    return $result;
}

// ===================================
// 아이템 관련 함수
// ===================================

/**
 * 박스의 아이템 목록 가져오기
 * 
 * @param int $rb_id 박스 ID
 * @param boolean $active_only 활성 아이템만
 * @return array
 */
function get_randombox_items($rb_id, $active_only = true) {
    global $g5;
    
    $list = array();
    $where = "rb_id = '$rb_id'";
    
    if ($active_only) {
        $where .= " AND rbi_status = 1";
    }
    
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items 
            WHERE $where 
            ORDER BY rbi_order, rbi_id";
    
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
    
    return $list;
}

/**
 * 확률에 따른 아이템 추첨
 * 
 * @param int $rb_id 박스 ID
 * @param string $mb_id 회원 ID
 * @return array
 */
function draw_randombox_item($rb_id, $mb_id) {
    global $g5;
    
    // 활성 아이템 목록 가져오기
    $items = get_randombox_items($rb_id, true);
    if (!$items) {
        return null;
    }
    
    // 배출 가능한 아이템만 필터링
    $available_items = array();
    $total_probability = 0;
    
    foreach ($items as $item) {
        // 수량 제한 확인
        if ($item['rbi_limit_qty'] > 0 && $item['rbi_issued_qty'] >= $item['rbi_limit_qty']) {
            continue;
        }
        
        $available_items[] = $item;
        $total_probability += $item['rbi_probability'];
    }
    
    if (!$available_items) {
        return null;
    }
    
    // 천장 시스템 확인
    $drawn_item = null;
    $ceiling_enabled = get_randombox_config('enable_ceiling');
    $ceiling_count = (int)get_randombox_config('ceiling_count');
    
    if ($ceiling_enabled && $ceiling_count > 0) {
        $ceiling = get_randombox_ceiling($mb_id, $rb_id);
        
        // 천장 도달 시 레어 이상 아이템 보장
        if ($ceiling && $ceiling['rbc_count'] >= $ceiling_count) {
            $rare_items = array();
            foreach ($available_items as $item) {
                if (in_array($item['rbi_grade'], array('rare', 'epic', 'legendary'))) {
                    $rare_items[] = $item;
                }
            }
            
            if ($rare_items) {
                // 레어 이상 아이템 중에서 추첨
                $drawn_item = draw_from_items($rare_items);
                
                // 천장 카운트 초기화
                update_randombox_ceiling($mb_id, $rb_id, 0);
            }
        }
    }
    
    // 일반 추첨
    if (!$drawn_item) {
        $drawn_item = draw_from_items($available_items);
        
        // 천장 카운트 증가
        if ($ceiling_enabled) {
            $current_count = $ceiling ? $ceiling['rbc_count'] + 1 : 1;
            
            // 레어 이상 획득 시 카운트 초기화
            if (in_array($drawn_item['rbi_grade'], array('rare', 'epic', 'legendary'))) {
                $current_count = 0;
            }
            
            update_randombox_ceiling($mb_id, $rb_id, $current_count);
        }
    }
    
    return $drawn_item;
}

/**
 * 아이템 목록에서 확률에 따라 추첨
 * 
 * @param array $items 아이템 목록
 * @return array
 */
function draw_from_items($items) {
    $total_probability = 0;
    foreach ($items as $item) {
        $total_probability += $item['rbi_probability'];
    }
    
    // 0~1 사이의 랜덤값 생성
    $random = mt_rand() / mt_getrandmax() * $total_probability;
    
    // 누적 확률로 아이템 선택
    $cumulative = 0;
    foreach ($items as $item) {
        $cumulative += $item['rbi_probability'];
        if ($random <= $cumulative) {
            return $item;
        }
    }
    
    // 안전장치: 마지막 아이템 반환
    return end($items);
}

// ===================================
// 천장 시스템 관련 함수
// ===================================

/**
 * 천장 정보 가져오기
 * 
 * @param string $mb_id 회원 ID
 * @param int $rb_id 박스 ID
 * @return array
 */
function get_randombox_ceiling($mb_id, $rb_id) {
    global $g5;
    
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_ceiling 
            WHERE mb_id = '$mb_id' AND rb_id = '$rb_id'";
    return sql_fetch($sql);
}

/**
 * 천장 카운트 업데이트
 * 
 * @param string $mb_id 회원 ID
 * @param int $rb_id 박스 ID
 * @param int $count 카운트
 * @return boolean
 */
function update_randombox_ceiling($mb_id, $rb_id, $count) {
    global $g5;
    
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO {$g5['g5_prefix']}randombox_ceiling 
            (mb_id, rb_id, rbc_count, rbc_updated_at) 
            VALUES ('$mb_id', '$rb_id', '$count', '$now')
            ON DUPLICATE KEY UPDATE 
            rbc_count = '$count', 
            rbc_updated_at = '$now'";
    
    return sql_query($sql);
}

// ===================================
// 구매/획득 관련 함수
// ===================================

/**
 * 랜덤박스 구매 처리
 * 
 * @param int $rb_id 박스 ID
 * @param string $mb_id 회원 ID
 * @return array
 */
function purchase_randombox($rb_id, $mb_id) {
    global $g5, $member;
    
    // 구매 가능 여부 재확인
    $check = check_randombox_purchase($rb_id, $mb_id);
    if (!$check['status']) {
        return $check;
    }
    
    $box = get_randombox($rb_id);
    
    // 트랜잭션 시작
    sql_query("START TRANSACTION");
    
    try {
        // 포인트 차감
        insert_point($mb_id, -$box['rb_price'], "랜덤박스 구매: {$box['rb_name']}");
        
        // 아이템 추첨
        $item = draw_randombox_item($rb_id, $mb_id);
        if (!$item) {
            throw new Exception('아이템 추첨에 실패했습니다.');
        }
        
        // 구매 기록 저장
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO {$g5['g5_prefix']}randombox_history SET
                mb_id = '$mb_id',
                rb_id = '$rb_id',
                rb_name = '{$box['rb_name']}',
                rb_price = '{$box['rb_price']}',
                rbi_id = '{$item['rbi_id']}',
                rbi_name = '{$item['rbi_name']}',
                rbi_grade = '{$item['rbi_grade']}',
                rbi_value = '{$item['rbi_value']}',
                rbh_status = 'completed',
                rbh_ip = '{$_SERVER['REMOTE_ADDR']}',
                rbh_created_at = '$now'";
        
        if (!sql_query($sql)) {
            throw new Exception('구매 기록 저장에 실패했습니다.');
        }
        
        // 박스 판매 수량 증가
        sql_query("UPDATE {$g5['g5_prefix']}randombox SET rb_sold_qty = rb_sold_qty + 1 WHERE rb_id = '$rb_id'");
        
        // 아이템 배출 수량 증가
        sql_query("UPDATE {$g5['g5_prefix']}randombox_items SET rbi_issued_qty = rbi_issued_qty + 1 WHERE rbi_id = '{$item['rbi_id']}'");
        
        // 아이템 가치만큼 포인트 지급
        if ($item['rbi_value'] > 0) {
            insert_point($mb_id, $item['rbi_value'], "랜덤박스 아이템 획득: {$item['rbi_name']}");
        }
        
        // 커밋
        sql_query("COMMIT");
        
        return array(
            'status' => true, 
            'msg' => '구매가 완료되었습니다.',
            'item' => $item
        );
        
    } catch (Exception $e) {
        // 롤백
        sql_query("ROLLBACK");
        
        return array(
            'status' => false,
            'msg' => $e->getMessage()
        );
    }
}

// ===================================
// 통계 관련 함수
// ===================================

/**
 * 박스별 통계 가져오기
 * 
 * @param int $rb_id 박스 ID
 * @return array
 */
function get_randombox_statistics($rb_id) {
    global $g5;
    
    $stats = array();
    
    // 총 구매 횟수
    $sql = "SELECT COUNT(*) as total_count, SUM(rb_price) as total_sales 
            FROM {$g5['g5_prefix']}randombox_history 
            WHERE rb_id = '$rb_id'";
    $stats['sales'] = sql_fetch($sql);
    
    // 아이템별 배출 통계
    $sql = "SELECT rbi_id, rbi_name, rbi_grade, COUNT(*) as count 
            FROM {$g5['g5_prefix']}randombox_history 
            WHERE rb_id = '$rb_id' 
            GROUP BY rbi_id 
            ORDER BY count DESC";
    $result = sql_query($sql);
    
    $stats['items'] = array();
    while ($row = sql_fetch_array($result)) {
        $stats['items'][] = $row;
    }
    
    // 등급별 배출 통계
    $sql = "SELECT rbi_grade, COUNT(*) as count 
            FROM {$g5['g5_prefix']}randombox_history 
            WHERE rb_id = '$rb_id' 
            GROUP BY rbi_grade";
    $result = sql_query($sql);
    
    $stats['grades'] = array();
    while ($row = sql_fetch_array($result)) {
        $stats['grades'][$row['rbi_grade']] = $row['count'];
    }
    
    return $stats;
}

/**
 * 최근 당첨 내역 가져오기
 * 
 * @param int $limit 개수
 * @return array
 */
function get_recent_winners($limit = 10) {
    global $g5;
    
    $list = array();
    
    $sql = "SELECT h.*, m.mb_nick, m.mb_name 
            FROM {$g5['g5_prefix']}randombox_history h 
            LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id 
            WHERE h.rbi_grade IN ('rare', 'epic', 'legendary') 
            ORDER BY h.rbh_created_at DESC 
            LIMIT $limit";
    
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        // 닉네임 마스킹 처리
        if ($row['mb_nick']) {
            $row['display_name'] = mb_substr($row['mb_nick'], 0, 1) . str_repeat('*', mb_strlen($row['mb_nick']) - 1);
        } else {
            $row['display_name'] = mb_substr($row['mb_id'], 0, 3) . '***';
        }
        
        $list[] = $row;
    }
    
    return $list;
}

// ===================================
// 유틸리티 함수
// ===================================

/**
 * 등급별 색상 클래스 반환
 * 
 * @param string $grade 등급
 * @return string
 */
function get_grade_class($grade) {
    $classes = array(
        'normal' => 'grade-normal',
        'rare' => 'grade-rare',
        'epic' => 'grade-epic',
        'legendary' => 'grade-legendary'
    );
    
    return isset($classes[$grade]) ? $classes[$grade] : 'grade-normal';
}

/**
 * 등급별 한글명 반환
 * 
 * @param string $grade 등급
 * @return string
 */
function get_grade_name($grade) {
    $names = array(
        'normal' => '일반',
        'rare' => '레어',
        'epic' => '에픽',
        'legendary' => '레전더리'
    );
    
    return isset($names[$grade]) ? $names[$grade] : '일반';
}

/**
 * 박스 타입별 한글명 반환
 * 
 * @param string $type 타입
 * @return string
 */
function get_box_type_name($type) {
    $names = array(
        'normal' => '일반',
        'event' => '이벤트',
        'premium' => '프리미엄'
    );
    
    return isset($names[$type]) ? $names[$type] : '일반';
}
?>