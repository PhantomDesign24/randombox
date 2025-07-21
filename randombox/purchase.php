<?php
/*
 * 파일명: purchase.php
 * 위치: /randombox/
 * 기능: 랜덤박스 구매 처리 (AJAX)
 * 작성일: 2025-01-04
 * 수정일: 2025-07-17
 */

include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

// ===================================
// AJAX 전용
// ===================================

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'status' => false,
    'message' => '',
    'item' => null,
    'user_point' => 0
);

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = '잘못된 접근입니다.';
    echo json_encode($response);
    exit;
}

// 로그인 체크
if (!$member['mb_id']) {
    $response['message'] = '로그인이 필요합니다.';
    echo json_encode($response);
    exit;
}

// ===================================
// 입력값 검증
// ===================================

$rb_id = (int)$_POST['rb_id'];
if (!$rb_id) {
    $response['message'] = '박스를 선택해 주세요.';
    echo json_encode($response);
    exit;
}

// ===================================
// 구매 처리
// ===================================

try {
    // 구매 처리
    $result = purchase_randombox($rb_id, $member['mb_id']);
    
    if ($result['status']) {
        // 성공
        $item = $result['item'];
        
        // 이미지 URL 처리
        if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
            $item['image'] = G5_DATA_URL.'/randombox/item/'.$item['rbi_image'];
        } else {
            $item['image'] = '';  // 프론트엔드에서 기본 이미지 처리
        }
        
        // 등급명 추가
        $grade_names = array(
            'normal' => '일반',
            'rare' => '레어',
            'epic' => '에픽',
            'legendary' => '레전더리'
        );
        $item['grade_name'] = $grade_names[$item['rbi_grade']] ?: $item['rbi_grade'];
        
        // 현재 포인트 조회
        $mb = get_member($member['mb_id']);
        
        $response['status'] = true;
        $response['message'] = $result['msg'] ?: '구매가 완료되었습니다.';
        $response['item'] = $item;
        $response['user_point'] = (int)$mb['mb_point'];
        
        // 이전 버전 호환성을 위해 msg도 추가
        $response['msg'] = $response['message'];
        
    } else {
        // 실패
        $response['message'] = $result['msg'] ?: '구매 처리 중 오류가 발생했습니다.';
        $response['msg'] = $response['message'];
    }
    
} catch (Exception $e) {
    $response['message'] = '시스템 오류가 발생했습니다.';
    error_log('Randombox purchase error: ' . $e->getMessage());
}

// ===================================
// 결과 반환
// ===================================

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>