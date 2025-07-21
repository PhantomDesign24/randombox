<?php
/*
 * 파일명: index.php
 * 위치: /randombox/
 * 기능: 랜덤박스 올인원 페이지 - 블랙&화이트 고급 디자인
 * 작성일: 2025-07-17
 * 수정일: 2025-07-17
 */

include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

// 로그인 체크
if (!$member['mb_id']) {
    alert('로그인이 필요한 서비스입니다.', G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/randombox/'));
}

// 시스템 활성화 체크
if (!get_randombox_config('system_enable')) {
    alert(get_randombox_config('maintenance_msg') ?: '시스템 점검 중입니다.');
}

// 박스 목록 조회
$box_list = get_randombox_list();

// 사용자 통계
$sql = "SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(rb_price), 0) as total_spent,
        COALESCE(SUM(rbi_value), 0) as total_earned
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}'";
$my_stats = sql_fetch($sql);

// 오늘 구매 수
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as today_count 
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}' 
        AND DATE(rbh_created_at) = '{$today}'";
$today_stats = sql_fetch($sql);

// 최근 구매 내역 (10개)
$sql = "SELECT h.*, m.mb_nick, m.mb_name
        FROM {$g5['g5_prefix']}randombox_history h
        LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id
        WHERE h.mb_id = '{$member['mb_id']}'
        ORDER BY h.rbh_created_at DESC
        LIMIT 10";
$history_result = sql_query($sql);

// 전체 실시간 당첨자 (레어 이상)
$sql = "SELECT h.*, m.mb_nick, m.mb_name
        FROM {$g5['g5_prefix']}randombox_history h
        LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id
        WHERE h.rbi_grade IN ('rare', 'epic', 'legendary')
        ORDER BY h.rbh_created_at DESC
        LIMIT 20";
$winners_result = sql_query($sql);

$g5['title'] = '랜덤박스';
include_once(G5_PATH.'/head.php');
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>랜덤박스 - <?php echo $config['cf_title']; ?></title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Font -->
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
    
    <style>
    /* ===================================
     * 리셋 및 기본 설정
     * =================================== */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    body {
        font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: #f5f5f5;
        color: #1a1a1a;
        line-height: 1.5;
    }
    
    /* ===================================
     * 메인 컨테이너
     * =================================== */
    .rb-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        background: #fff;
        min-height: 100vh;
    }
    
    /* ===================================
     * 헤더
     * =================================== */
    .rb-header {
        background: #000;
        color: #fff;
        padding: 15px 20px;
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid #222;
    }
    
    .rb-header-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .rb-logo {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .rb-logo i {
        font-size: 24px;
    }
    
    .rb-header-info {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    
    .rb-point {
        font-size: 16px;
        font-weight: 700;
    }
    
    .rb-point span {
        color: #fff;
        font-size: 18px;
    }
    
    .rb-nav {
        display: flex;
        gap: 5px;
    }
    
    .rb-nav button {
        padding: 8px 16px;
        background: transparent;
        color: #fff;
        border: 1px solid #333;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .rb-nav button:hover {
        background: #fff;
        color: #000;
    }
    
    .rb-nav button.active {
        background: #fff;
        color: #000;
    }
    
    /* ===================================
     * 통계 바
     * =================================== */
    .rb-stats-bar {
        background: #f8f8f8;
        border-bottom: 1px solid #e0e0e0;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .rb-stat {
        text-align: center;
    }
    
    .rb-stat-label {
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .rb-stat-value {
        font-size: 20px;
        font-weight: 800;
        color: #000;
    }
    
    .rb-stat-value.positive {
        color: #2ea043;
    }
    
    .rb-stat-value.negative {
        color: #cf222e;
    }
    
    /* ===================================
     * 메인 레이아웃
     * =================================== */
    .rb-main {
        display: grid;
        grid-template-columns: 1fr 380px;
        min-height: calc(100vh - 120px);
    }
    
    /* ===================================
     * 콘텐츠 영역
     * =================================== */
    .rb-content {
        padding: 20px;
        overflow-y: auto;
    }
    
    /* 필터 */
    .rb-filter {
        margin-bottom: 15px;
        display: flex;
        gap: 8px;
    }
    
    .rb-filter button {
        padding: 6px 14px;
        background: #fff;
        border: 1px solid #d0d0d0;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .rb-filter button:hover {
        border-color: #000;
    }
    
    .rb-filter button.active {
        background: #000;
        color: #fff;
        border-color: #000;
    }
    
    /* 박스 그리드 */
    .rb-boxes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .rb-box {
        background: #fff;
        border: 1px solid #e0e0e0;
        transition: all 0.2s;
        cursor: pointer;
        position: relative;
    }
    
    .rb-box:hover {
        border-color: #000;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .rb-box-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 2px 8px;
        background: #000;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .rb-box-badge.event {
        background: #cf222e;
    }
    
    .rb-box-badge.premium {
        background: #6f42c1;
    }
    
    .rb-box-image {
        height: 120px;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .rb-box-image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .rb-box-info {
        padding: 12px;
    }
    
    .rb-box-name {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 4px;
        height: 40px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .rb-box-meta {
        font-size: 11px;
        color: #666;
        margin-bottom: 8px;
    }
    
    .rb-box-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .rb-box-price {
        font-size: 16px;
        font-weight: 800;
    }
    
    .rb-box-btn {
        padding: 5px 12px;
        background: #000;
        color: #fff;
        border: none;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .rb-box-btn:hover {
        background: #333;
    }
    
    .rb-box-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    /* ===================================
     * 사이드바
     * =================================== */
    .rb-sidebar {
        background: #fafafa;
        border-left: 1px solid #e0e0e0;
        overflow-y: auto;
    }
    
    .rb-side-section {
        border-bottom: 1px solid #e0e0e0;
        padding: 15px;
    }
    
    .rb-side-section:last-child {
        border-bottom: none;
    }
    
    .rb-side-title {
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .rb-side-more {
        font-size: 11px;
        color: #0969da;
        cursor: pointer;
        font-weight: 400;
    }
    
    /* 내 구매내역 */
    .rb-history-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 12px;
    }
    
    .rb-history-item:last-child {
        border-bottom: none;
    }
    
    .rb-history-info {
        flex: 1;
    }
    
    .rb-history-box {
        font-weight: 600;
        margin-bottom: 2px;
    }
    
    .rb-history-result {
        color: #666;
    }
    
    .rb-history-grade {
        font-size: 10px;
        padding: 1px 6px;
        border-radius: 3px;
        font-weight: 700;
        margin-left: 4px;
    }
    
    .rb-history-grade.normal {
        background: #e0e0e0;
        color: #666;
    }
    
    .rb-history-grade.rare {
        background: #0969da;
        color: #fff;
    }
    
    .rb-history-grade.epic {
        background: #6f42c1;
        color: #fff;
    }
    
    .rb-history-grade.legendary {
        background: #cf222e;
        color: #fff;
    }
    
    .rb-history-time {
        color: #999;
        font-size: 11px;
    }
    
    /* 실시간 당첨 */
    .rb-winner-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 12px;
    }
    
    .rb-winner-item:last-child {
        border-bottom: none;
    }
    
    .rb-winner-avatar {
        width: 32px;
        height: 32px;
        background: #000;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
    }
    
    .rb-winner-info {
        flex: 1;
    }
    
    .rb-winner-name {
        font-weight: 600;
        margin-bottom: 2px;
    }
    
    .rb-winner-prize {
        color: #666;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* ===================================
     * 모달
     * =================================== */
    .rb-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .rb-modal.show {
        display: flex;
    }
    
    .rb-modal-content {
        background: #fff;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow: auto;
        position: relative;
    }
    
    .rb-modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafafa;
    }
    
    .rb-modal-title {
        font-size: 16px;
        font-weight: 800;
    }
    
    .rb-modal-close {
        width: 30px;
        height: 30px;
        border: none;
        background: transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    
    .rb-modal-close:hover {
        background: #f0f0f0;
    }
    
    .rb-modal-body {
        padding: 20px;
    }
    
    /* 박스 상세 */
    .rb-detail {
        text-align: center;
    }
    
    .rb-detail-image {
        width: 200px;
        height: 200px;
        margin: 0 auto 20px;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .rb-detail-image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .rb-detail-name {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 10px;
    }
    
    .rb-detail-desc {
        color: #666;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .rb-detail-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f8f8;
    }
    
    .rb-detail-stat {
        text-align: center;
    }
    
    .rb-detail-stat-value {
        font-size: 18px;
        font-weight: 800;
        display: block;
    }
    
    .rb-detail-stat-label {
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
    }
    
    /* 아이템 목록 */
    .rb-items {
        margin-top: 20px;
        border-top: 1px solid #e0e0e0;
        padding-top: 20px;
    }
    
    .rb-items-title {
        font-size: 14px;
        font-weight: 800;
        margin-bottom: 15px;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .rb-items-title i {
        font-size: 16px;
        color: #666;
    }
    
    .rb-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border: 1px solid #e0e0e0;
        margin-bottom: 8px;
        transition: all 0.2s;
    }
    
    .rb-item:hover {
        border-color: #000;
        background: #fafafa;
    }
    
    .rb-item.normal {
        border-left: 3px solid #999;
    }
    
    .rb-item.rare {
        border-left: 3px solid #0969da;
    }
    
    .rb-item.epic {
        border-left: 3px solid #6f42c1;
    }
    
    .rb-item.legendary {
        border-left: 3px solid #cf222e;
    }
    
    .rb-item-image {
        width: 50px;
        height: 50px;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border-radius: 4px;
    }
    
    .rb-item-image img {
        max-width: 40px;
        max-height: 40px;
        object-fit: contain;
    }
    
    .rb-item-info {
        flex: 1;
    }
    
    .rb-item-name {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 2px;
    }
    
    .rb-item-grade {
        font-size: 11px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .rb-item-prob {
        font-size: 16px;
        font-weight: 800;
        text-align: right;
        min-width: 60px;
    }
    
    /* 구매/결과 모달 */
    .rb-purchase-confirm {
        text-align: center;
        padding: 20px 0;
    }
    
    .rb-purchase-box-image {
        width: 120px;
        height: 120px;
        margin: 0 auto 15px;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .rb-purchase-box-image img {
        max-width: 100%;
        max-height: 100%;
    }
    
    .rb-purchase-info {
        background: #f8f8f8;
        padding: 15px;
        margin: 15px 0;
    }
    
    .rb-purchase-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
    }
    
    .rb-purchase-row.total {
        border-top: 1px solid #e0e0e0;
        margin-top: 10px;
        padding-top: 10px;
        font-weight: 700;
    }
    
    .rb-modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 10px;
        justify-content: center;
        background: #fafafa;
    }
    
    .rb-btn {
        padding: 10px 24px;
        border: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .rb-btn-primary {
        background: #000;
        color: #fff;
    }
    
    .rb-btn-primary:hover {
        background: #333;
    }
    
    .rb-btn-secondary {
        background: #fff;
        color: #000;
        border: 1px solid #d0d0d0;
    }
    
    .rb-btn-secondary:hover {
        border-color: #000;
    }
    
    /* 결과 */
    .rb-result {
        text-align: center;
        padding: 20px 0;
    }
    
    .rb-result-animation {
        width: 200px;
        height: 200px;
        margin: 0 auto;
    }
    
    .rb-result-animation img {
        width: 100%;
        height: 100%;
    }
    
    .rb-result-item {
        width: 160px;
        height: 160px;
        margin: 0 auto 20px;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .rb-result-item img {
        max-width: 80%;
        max-height: 80%;
        object-fit: contain;
    }
    
    .rb-result-grade {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 2px 8px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .rb-result-grade.normal {
        background: #e0e0e0;
        color: #666;
    }
    
    .rb-result-grade.rare {
        background: #0969da;
        color: #fff;
    }
    
    .rb-result-grade.epic {
        background: #6f42c1;
        color: #fff;
    }
    
    .rb-result-grade.legendary {
        background: #cf222e;
        color: #fff;
    }
    
    .rb-result-name {
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 10px;
    }
    
    .rb-result-value {
        font-size: 16px;
        color: #2ea043;
        font-weight: 700;
    }
    
    /* 로딩 */
    .rb-loading {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .rb-spinner {
        display: inline-block;
        width: 30px;
        height: 30px;
        border: 3px solid #f0f0f0;
        border-top-color: #000;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* 빈 상태 */
    .rb-empty {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    .rb-empty i {
        font-size: 48px;
        margin-bottom: 10px;
    }
    
    /* 반응형 */
    @media (max-width: 1024px) {
        .rb-main {
            grid-template-columns: 1fr;
        }
        
        .rb-sidebar {
            display: none;
        }
        
        .rb-header-info {
            gap: 15px;
        }
        
        .rb-stats-bar {
            overflow-x: auto;
            white-space: nowrap;
        }
    }
    
    @media (max-width: 768px) {
        .rb-boxes {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .rb-header {
            padding: 10px 15px;
        }
        
        .rb-logo {
            font-size: 16px;
        }
        
        .rb-point {
            font-size: 14px;
        }
        
        .rb-nav button {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .rb-content {
            padding: 15px;
        }
    }
    </style>
</head>
<body>

<div class="rb-wrapper">
    <!-- ===================================
     * 헤더
     * =================================== -->
    <header class="rb-header">
        <div class="rb-header-inner">
            <div class="rb-logo">
                <i class="bi bi-box-seam-fill"></i>
                <span>RANDOMBOX</span>
            </div>
            
            <div class="rb-header-info">
                <div class="rb-point">
                    보유 포인트: <span id="userPoint"><?php echo number_format($member['mb_point']); ?>P</span>
                </div>
                
                <nav class="rb-nav">
                    <button type="button" class="active" onclick="showSection('boxes')">
                        <i class="bi bi-grid-3x3-gap"></i> 박스
                    </button>
                    <button type="button" onclick="showSection('history')">
                        <i class="bi bi-clock-history"></i> 내역
                    </button>
                    <?php if ($is_admin) : ?>
                    <button type="button" onclick="window.open('<?php echo G5_ADMIN_URL; ?>/randombox_admin/plugin.php')">
                        <i class="bi bi-gear"></i> 관리
                    </button>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- ===================================
     * 통계 바
     * =================================== -->
    <div class="rb-stats-bar">
        <div class="rb-stat">
            <div class="rb-stat-label">오늘 구매</div>
            <div class="rb-stat-value"><?php echo number_format($today_stats['today_count']); ?></div>
        </div>
        <div class="rb-stat">
            <div class="rb-stat-label">총 구매</div>
            <div class="rb-stat-value"><?php echo number_format($my_stats['total_count']); ?></div>
        </div>
        <div class="rb-stat">
            <div class="rb-stat-label">사용 포인트</div>
            <div class="rb-stat-value"><?php echo number_format($my_stats['total_spent']); ?>P</div>
        </div>
        <div class="rb-stat">
            <div class="rb-stat-label">획득 포인트</div>
            <div class="rb-stat-value"><?php echo number_format($my_stats['total_earned']); ?>P</div>
        </div>
        <div class="rb-stat">
            <div class="rb-stat-label">수익률</div>
            <div class="rb-stat-value <?php echo ($my_stats['total_earned'] - $my_stats['total_spent']) >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo number_format($my_stats['total_earned'] - $my_stats['total_spent']); ?>P
            </div>
        </div>
    </div>
    
    <!-- ===================================
     * 메인 레이아웃
     * =================================== -->
    <div class="rb-main">
        <!-- 콘텐츠 영역 -->
        <main class="rb-content">
            <!-- 박스 섹션 -->
            <section id="boxesSection">
                <div class="rb-filter">
                    <button type="button" class="active" onclick="filterBoxes('all')">전체</button>
                    <button type="button" onclick="filterBoxes('normal')">일반</button>
                    <button type="button" onclick="filterBoxes('event')">이벤트</button>
                    <button type="button" onclick="filterBoxes('premium')">프리미엄</button>
                </div>
                
                <?php if (!$box_list) : ?>
                <div class="rb-empty">
                    <i class="bi bi-inbox"></i>
                    <p>판매 중인 상품이 없습니다</p>
                </div>
                <?php else : ?>
                <div class="rb-boxes">
                    <?php foreach ($box_list as $box) : 
                        $box_img = './img/box-default.png';
                        if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
                            $box_img = G5_DATA_URL.'/randombox/box/'.$box['rb_image'];
                        }
                        
                        $can_purchase = check_randombox_purchase($box['rb_id'], $member['mb_id']);
                        $is_soldout = ($box['rb_total_qty'] > 0 && $box['rb_sold_qty'] >= $box['rb_total_qty']);
                    ?>
                    <div class="rb-box" data-type="<?php echo $box['rb_type']; ?>" onclick="showBoxDetail(<?php echo $box['rb_id']; ?>)">
                        <?php if ($box['rb_type'] != 'normal') : ?>
                        <div class="rb-box-badge <?php echo $box['rb_type']; ?>">
                            <?php echo strtoupper($box['rb_type']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="rb-box-image">
                            <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
                        </div>
                        
                        <div class="rb-box-info">
                            <div class="rb-box-name"><?php echo $box['rb_name']; ?></div>
                            <div class="rb-box-meta">
                                <i class="bi bi-people"></i> <?php echo number_format($box['rb_sold_qty']); ?>명 구매
                                <?php if ($box['rb_limit_qty'] > 0) : ?>
                                | 일일 <?php echo $box['rb_limit_qty']; ?>개
                                <?php endif; ?>
                            </div>
                            
                            <div class="rb-box-footer">
                                <div class="rb-box-price"><?php echo number_format($box['rb_price']); ?>P</div>
                                <button class="rb-box-btn" onclick="event.stopPropagation(); purchaseBox(<?php echo $box['rb_id']; ?>)" 
                                    <?php echo $is_soldout ? 'disabled' : ''; ?>>
                                    <?php echo $is_soldout ? '품절' : '구매'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>
            
            <!-- 구매내역 섹션 (숨김) -->
            <section id="historySection" style="display: none;">
                <h2 style="font-size: 18px; font-weight: 800; margin-bottom: 20px;">구매내역</h2>
                <!-- 구매내역 내용은 AJAX로 로드 -->
                <div id="historyContent">
                    <div class="rb-loading">
                        <div class="rb-spinner"></div>
                        <p>불러오는 중...</p>
                    </div>
                </div>
            </section>
        </main>
        
        <!-- 사이드바 -->
        <aside class="rb-sidebar">
            <!-- 내 최근 구매 -->
            <div class="rb-side-section">
                <div class="rb-side-title">
                    내 최근 구매
                    <span class="rb-side-more" onclick="showSection('history')">전체보기</span>
                </div>
                <?php 
                $has_history = false;
                while ($history = sql_fetch_array($history_result)) : 
                    $has_history = true;
                ?>
                <div class="rb-history-item">
                    <div class="rb-history-info">
                        <div class="rb-history-box"><?php echo $history['rb_name']; ?></div>
                        <div class="rb-history-result">
                            <?php echo $history['rbi_name']; ?>
                            <span class="rb-history-grade <?php echo $history['rbi_grade']; ?>">
                                <?php echo strtoupper($history['rbi_grade']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="rb-history-time">
                        <?php 
                        $time_diff = time() - strtotime($history['rbh_created_at']);
                        if ($time_diff < 60) {
                            echo '방금 전';
                        } elseif ($time_diff < 3600) {
                            echo floor($time_diff / 60) . '분 전';
                        } elseif ($time_diff < 86400) {
                            echo floor($time_diff / 3600) . '시간 전';
                        } else {
                            echo date('m.d', strtotime($history['rbh_created_at']));
                        }
                        ?>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <?php if (!$has_history) : ?>
                <div class="rb-empty">
                    <p>구매 내역이 없습니다</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 실시간 당첨 -->
            <div class="rb-side-section">
                <div class="rb-side-title">
                    <span><i class="bi bi-broadcast" style="color: #cf222e;"></i> 실시간 당첨</span>
                </div>
                <div id="realtimeWinners">
                    <?php 
                    $has_winners = false;
                    while ($winner = sql_fetch_array($winners_result)) : 
                        $has_winners = true;
                        $display_name = $winner['mb_nick'] ?: $winner['mb_name'];
                        if (mb_strlen($display_name) > 2) {
                            $display_name = mb_substr($display_name, 0, 1) . str_repeat('*', mb_strlen($display_name) - 2) . mb_substr($display_name, -1);
                        }
                    ?>
                    <div class="rb-winner-item">
                        <div class="rb-winner-avatar"><?php echo mb_substr($display_name, 0, 1); ?></div>
                        <div class="rb-winner-info">
                            <div class="rb-winner-name"><?php echo $display_name; ?></div>
                            <div class="rb-winner-prize">
                                <?php echo $winner['rbi_name']; ?>
                                <span class="rb-history-grade <?php echo $winner['rbi_grade']; ?>">
                                    <?php echo strtoupper($winner['rbi_grade']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if (!$has_winners) : ?>
                    <div class="rb-empty">
                        <p>아직 당첨자가 없습니다</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- ===================================
 * 모달
 * =================================== -->

<!-- 박스 상세 모달 -->
<div id="boxDetailModal" class="rb-modal">
    <div class="rb-modal-content">
        <div class="rb-modal-header">
            <h3 class="rb-modal-title">박스 상세 정보</h3>
            <button class="rb-modal-close" onclick="closeModal('boxDetailModal')">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="rb-modal-body" id="boxDetailBody">
            <div class="rb-loading">
                <div class="rb-spinner"></div>
            </div>
        </div>
    </div>
</div>

<!-- 구매 확인 모달 -->
<div id="purchaseModal" class="rb-modal">
    <div class="rb-modal-content">
        <div class="rb-modal-header">
            <h3 class="rb-modal-title">구매 확인</h3>
            <button class="rb-modal-close" onclick="closeModal('purchaseModal')">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="rb-modal-body">
            <div class="rb-purchase-confirm">
                <div class="rb-purchase-box-image">
                    <img src="" alt="" id="purchaseBoxImage">
                </div>
                <h4 id="purchaseBoxName" style="font-size: 16px; font-weight: 700; margin-bottom: 15px;"></h4>
                
                <div class="rb-purchase-info">
                    <div class="rb-purchase-row">
                        <span>구매 가격</span>
                        <span id="purchasePrice"></span>
                    </div>
                    <div class="rb-purchase-row">
                        <span>보유 포인트</span>
                        <span id="currentPoint"></span>
                    </div>
                    <div class="rb-purchase-row total">
                        <span>구매 후 잔액</span>
                        <span id="afterPoint"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="rb-modal-footer">
            <button class="rb-btn rb-btn-secondary" onclick="closeModal('purchaseModal')">취소</button>
            <button class="rb-btn rb-btn-primary" id="confirmPurchaseBtn">구매하기</button>
        </div>
    </div>
</div>

<!-- 결과 모달 -->
<div id="resultModal" class="rb-modal">
    <div class="rb-modal-content">
        <div class="rb-modal-header">
            <h3 class="rb-modal-title">결과</h3>
        </div>
        <div class="rb-modal-body">
            <div class="rb-result" id="resultContent">
                <!-- 동적 생성 -->
            </div>
        </div>
        <div class="rb-modal-footer">
            <button class="rb-btn rb-btn-primary" onclick="closeResult()">확인</button>
        </div>
    </div>
</div>

<script>
// ===================================
// 전역 변수
// ===================================
let currentBoxId = null;
let userPoint = <?php echo $member['mb_point']; ?>;

// ===================================
// 섹션 전환
// ===================================
function showSection(section) {
    // 버튼 활성화
    $('.rb-nav button').removeClass('active');
    if (section === 'boxes') {
        $('.rb-nav button:eq(0)').addClass('active');
        $('#boxesSection').show();
        $('#historySection').hide();
    } else if (section === 'history') {
        $('.rb-nav button:eq(1)').addClass('active');
        $('#boxesSection').hide();
        $('#historySection').show();
        loadHistory();
    }
}

// ===================================
// 필터
// ===================================
function filterBoxes(type) {
    $('.rb-filter button').removeClass('active');
    $(event.target).addClass('active');
    
    if (type === 'all') {
        $('.rb-box').show();
    } else {
        $('.rb-box').hide();
        $(`.rb-box[data-type="${type}"]`).show();
    }
}

// ===================================
// 박스 상세
// ===================================
function showBoxDetail(boxId) {
    currentBoxId = boxId;
    $('#boxDetailModal').addClass('show');
    
    $.ajax({
        url: './ajax/get_box_detail.php',
        type: 'GET',
        data: { id: boxId },
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                displayBoxDetail(response.data);
            } else {
                alert(response.message || '정보를 불러올 수 없습니다.');
                closeModal('boxDetailModal');
            }
        },
        error: function() {
            alert('통신 오류가 발생했습니다.');
            closeModal('boxDetailModal');
        }
    });
}

function displayBoxDetail(box) {
    // 기본값 설정
    box = box || {};
    box.unique_buyers = box.unique_buyers || 0;
    box.total_sold = box.total_sold || 0;
    box.price = box.price || 0;
    box.items = box.items || [];
    box.recent_winners = box.recent_winners || [];
    
    // 기본 이미지 데이터 URI (빈 박스 아이콘)
    const defaultBoxImg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgPHJlY3QgeD0iMjAiIHk9IjQwIiB3aWR0aD0iNjAiIGhlaWdodD0iNDAiIGZpbGw9IiNkZGQiIHN0cm9rZT0iIzk5OSIgc3Ryb2tlLXdpZHRoPSIyIi8+CiAgPHJlY3QgeD0iMjAiIHk9IjMwIiB3aWR0aD0iNjAiIGhlaWdodD0iMTAiIGZpbGw9IiNiYmIiIHN0cm9rZT0iIzk5OSIgc3Ryb2tlLXdpZHRoPSIyIi8+CiAgPHBhdGggZD0iTTQ1IDI1aDEwdjEwaC0xMHoiIGZpbGw9IiM5OTkiLz4KPC9zdmc+';
    
    const defaultItemImg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIyNSIgY3k9IjI1IiByPSIyMCIgZmlsbD0iI2VlZSIgc3Ryb2tlPSIjOTk5IiBzdHJva2Utd2lkdGg9IjIiLz4KICA8dGV4dCB4PSIyNSIgeT0iMzAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5OTkiIGZvbnQtc2l6ZT0iMjAiPj88L3RleHQ+Cjwvc3ZnPg==';
    
    let html = `
        <div class="rb-detail">
            <div class="rb-detail-image">
                <img src="${box.image || defaultBoxImg}" alt="${box.name || '랜덤박스'}" onerror="this.onerror=null; this.src='${defaultBoxImg}';">
            </div>
            <h2 class="rb-detail-name">${box.name || '랜덤박스'}</h2>
            <p class="rb-detail-desc">${box.desc || '특별한 아이템을 획득할 수 있는 랜덤박스입니다.'}</p>
            
            <div class="rb-detail-stats">
                <div class="rb-detail-stat">
                    <span class="rb-detail-stat-value">${box.unique_buyers.toLocaleString()}</span>
                    <span class="rb-detail-stat-label">구매자</span>
                </div>
                <div class="rb-detail-stat">
                    <span class="rb-detail-stat-value">${box.total_sold.toLocaleString()}</span>
                    <span class="rb-detail-stat-label">판매수</span>
                </div>
                <div class="rb-detail-stat">
                    <span class="rb-detail-stat-value">${box.price.toLocaleString()}P</span>
                    <span class="rb-detail-stat-label">가격</span>
                </div>
            </div>
            
            ${box.sale_period ? `
            <div style="text-align: center; margin: 10px 0; font-size: 12px; color: #666;">
                판매기간: ${box.sale_period}
            </div>
            ` : ''}
            
            ${box.can_purchase ? 
                `<button class="rb-btn rb-btn-primary" style="width: 100%;" onclick="closeModal('boxDetailModal'); purchaseBox(${box.id})">
                    <i class="bi bi-cart-plus"></i> 구매하기
                </button>` :
                `<button class="rb-btn rb-btn-secondary" style="width: 100%;" disabled>
                    ${box.purchase_message || '구매 불가'}
                </button>`
            }
            
            ${box.items && box.items.length > 0 ? `
            <div class="rb-items">
                <h3 class="rb-items-title">
                    <i class="bi bi-gift"></i> 획득 가능 아이템 (${box.items.length}종)
                </h3>
                ${box.items.map(item => {
                    // 아이템 기본값 설정
                    item = item || {};
                    item.name = item.name || '아이템';
                    item.image = item.image || defaultItemImg;
                    item.grade = item.grade || 'normal';
                    item.grade_name = item.grade_name || '일반';
                    item.probability = item.probability || '0.00';
                    item.value = item.value || 0;
                    
                    return `
                    <div class="rb-item ${item.grade}">
                        <div class="rb-item-image">
                            <img src="${item.image}" alt="${item.name}" onerror="this.onerror=null; this.src='${defaultItemImg}';">
                        </div>
                        <div class="rb-item-info">
                            <div class="rb-item-name">${item.name}</div>
                            <div class="rb-item-grade">
                                <span class="rb-history-grade ${item.grade}">${item.grade_name}</span>
                                ${item.value > 0 ? `<span style="color: #2ea043; font-size: 11px;">${item.value.toLocaleString()}P</span>` : ''}
                            </div>
                            ${item.desc ? `<div style="font-size: 11px; color: #999; margin-top: 2px;">${item.desc}</div>` : ''}
                        </div>
                        <div class="rb-item-prob">${item.probability}%</div>
                    </div>
                    `;
                }).join('')}
            </div>
            ` : '<div class="rb-empty" style="margin-top: 20px;"><p>아이템 정보가 없습니다.</p></div>'}
            
            ${box.recent_winners && box.recent_winners.length > 0 ? `
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <h3 class="rb-items-title">
                    <i class="bi bi-trophy"></i> 최근 당첨자
                </h3>
                ${box.recent_winners.map(winner => {
                    winner = winner || {};
                    winner.display_name = winner.display_name || '익명';
                    winner.item_name = winner.item_name || '아이템';
                    winner.grade = winner.grade || 'normal';
                    winner.time_ago = winner.time_ago || '방금 전';
                    
                    return `
                    <div style="display: flex; align-items: center; gap: 8px; padding: 6px 0; font-size: 12px;">
                        <span style="font-weight: 600;">${winner.display_name}</span>
                        <span style="color: #666;">님이</span>
                        <span style="font-weight: 600;">${winner.item_name}</span>
                        <span class="rb-history-grade ${winner.grade}" style="font-size: 10px;">${winner.grade.toUpperCase()}</span>
                        <span style="color: #999; margin-left: auto;">${winner.time_ago}</span>
                    </div>
                    `;
                }).join('')}
            </div>
            ` : ''}
        </div>
    `;
    
    $('#boxDetailBody').html(html);
}

// ===================================
// 구매
// ===================================
function purchaseBox(boxId) {
    // 박스 정보 찾기
    const boxElement = $(`.rb-box[onclick*="${boxId}"]`);
    if (!boxElement.length) {
        // 상세 모달에서 호출된 경우 AJAX로 다시 정보 가져오기
        $.ajax({
            url: './ajax/get_box_detail.php',
            type: 'GET',
            data: { id: boxId },
            dataType: 'json',
            success: function(response) {
                if (response.status && response.data) {
                    showPurchaseModal(response.data);
                }
            }
        });
        return;
    }
    
    // 박스 정보 수집
    const boxData = {
        id: boxId,
        name: boxElement.find('.rb-box-name').text(),
        price: parseInt(boxElement.find('.rb-box-price').text().replace(/[^0-9]/g, '')),
        image: boxElement.find('.rb-box-image img').attr('src')
    };
    
    showPurchaseModal(boxData);
}

function showPurchaseModal(boxData) {
    const defaultBoxImg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgPHJlY3QgeD0iMjAiIHk9IjQwIiB3aWR0aD0iNjAiIGhlaWdodD0iNDAiIGZpbGw9IiNkZGQiIHN0cm9rZT0iIzk5OSIgc3Ryb2tlLXdpZHRoPSIyIi8+CiAgPHJlY3QgeD0iMjAiIHk9IjMwIiB3aWR0aD0iNjAiIGhlaWdodD0iMTAiIGZpbGw9IiNiYmIiIHN0cm9rZT0iIzk5OSIgc3Ryb2tlLXdpZHRoPSIyIi8+CiAgPHBhdGggZD0iTTQ1IDI1aDEwdjEwaC0xMHoiIGZpbGw9IiM5OTkiLz4KPC9zdmc+';
    
    $('#purchaseBoxImage').attr('src', boxData.image || defaultBoxImg);
    $('#purchaseBoxImage')[0].onerror = function() { this.onerror=null; this.src=defaultBoxImg; };
    $('#purchaseBoxName').text(boxData.name);
    $('#purchasePrice').text(boxData.price.toLocaleString() + 'P');
    $('#currentPoint').text(userPoint.toLocaleString() + 'P');
    
    const afterPoint = userPoint - boxData.price;
    $('#afterPoint').text(afterPoint.toLocaleString() + 'P').css('color', afterPoint >= 0 ? '#2ea043' : '#cf222e');
    
    $('#confirmPurchaseBtn').prop('disabled', afterPoint < 0)
        .off('click')
        .on('click', function() {
            processPurchase(boxData.id);
        });
    
    $('#purchaseModal').addClass('show');
}

function processPurchase(boxId) {
    $('#confirmPurchaseBtn').prop('disabled', true).text('처리중...');
    
    $.ajax({
        url: './purchase.php',
        type: 'POST',
        data: { 
            rb_id: boxId
        },
        dataType: 'json',
        success: function(response) {
            closeModal('purchaseModal');
            
            if (response && response.status) {
                // 서버에서 반환한 포인트로 업데이트 (있는 경우)
                if (typeof response.user_point !== 'undefined') {
                    userPoint = parseInt(response.user_point);
                    $('#userPoint').text(userPoint.toLocaleString() + 'P');
                }
                
                // 결과 표시
                showResult(response);
            } else {
                // 에러 메시지 표시 (msg 또는 message)
                alert(response.msg || response.message || '구매 처리 중 오류가 발생했습니다.');
            }
        },
        error: function(xhr, status, error) {
            console.error('Purchase error:', error);
            let errorMsg = '통신 오류가 발생했습니다.';
            
            try {
                const errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse.msg || errorResponse.message) {
                    errorMsg = errorResponse.msg || errorResponse.message;
                }
            } catch(e) {
                // JSON 파싱 실패 시 기본 메시지 사용
            }
            
            alert(errorMsg);
        },
        complete: function() {
            $('#confirmPurchaseBtn').prop('disabled', false).text('구매하기');
        }
    });
}

// ===================================
// 프리미엄 박스 오픈 애니메이션
// ===================================
function showResult(data) {
    // 데이터 검증
    if (!data || !data.item) {
        alert('결과 데이터를 받지 못했습니다.');
        return;
    }
    
    // 결과 모달 표시
    $('#resultModal').addClass('show');
    
    // 프리미엄 애니메이션 HTML
    let html = `
        <div class="rb-premium-opening">
            <!-- 배경 효과 -->
            <div class="rb-bg-effects">
                <div class="rb-radial-burst"></div>
                <div class="rb-light-rays">
                    <div class="rb-ray"></div>
                    <div class="rb-ray"></div>
                    <div class="rb-ray"></div>
                    <div class="rb-ray"></div>
                    <div class="rb-ray"></div>
                    <div class="rb-ray"></div>
                </div>
            </div>
            
            <!-- 메인 박스 -->
            <div class="rb-box-container">
                <div class="rb-glow-effect"></div>
                <div class="rb-box-main">
                    <div class="rb-box-body"></div>
                    <div class="rb-box-lid"></div>
                    <div class="rb-box-lock"></div>
                </div>
                
                <!-- 파티클 시스템 -->
                <div class="rb-particle-system">
                    ${Array(20).fill().map((_, i) => `<div class="rb-star-particle" style="--delay: ${i * 0.1}s"></div>`).join('')}
                </div>
                
                <!-- 충격파 효과 -->
                <div class="rb-shockwave"></div>
                <div class="rb-shockwave-2"></div>
            </div>
            
            <!-- 텍스트 -->
            <div class="rb-opening-status">
                <div class="rb-status-text">OPENING</div>
                <div class="rb-progress-bar">
                    <div class="rb-progress-fill"></div>
                </div>
            </div>
        </div>
    `;
    
    // 프리미엄 애니메이션 스타일
    if (!$('#premiumBoxStyles').length) {
        $('head').append(`
            <style id="premiumBoxStyles">
                .rb-premium-opening {
                    position: relative;
                    width: 100%;
                    height: 400px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                    background: radial-gradient(ellipse at center, #1a1a1a 0%, #000 100%);
                }
                
                /* 배경 효과 */
                .rb-bg-effects {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                }
                
                .rb-radial-burst {
                    position: absolute;
                    width: 200%;
                    height: 200%;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
                    animation: radialPulse 3s ease-out;
                }
                
                @keyframes radialPulse {
                    0% {
                        transform: translate(-50%, -50%) scale(0);
                        opacity: 0;
                    }
                    50% {
                        opacity: 1;
                    }
                    100% {
                        transform: translate(-50%, -50%) scale(2);
                        opacity: 0;
                    }
                }
                
                /* 광선 효과 */
                .rb-light-rays {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    animation: raysRotate 10s linear infinite;
                }
                
                .rb-ray {
                    position: absolute;
                    width: 2px;
                    height: 100%;
                    left: 50%;
                    top: 50%;
                    transform-origin: center;
                    background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.3), transparent);
                    opacity: 0;
                    animation: rayAppear 3s ease-out 0.5s;
                }
                
                .rb-ray:nth-child(1) { transform: translate(-50%, -50%) rotate(0deg); }
                .rb-ray:nth-child(2) { transform: translate(-50%, -50%) rotate(60deg); }
                .rb-ray:nth-child(3) { transform: translate(-50%, -50%) rotate(120deg); }
                .rb-ray:nth-child(4) { transform: translate(-50%, -50%) rotate(180deg); }
                .rb-ray:nth-child(5) { transform: translate(-50%, -50%) rotate(240deg); }
                .rb-ray:nth-child(6) { transform: translate(-50%, -50%) rotate(300deg); }
                
                @keyframes raysRotate {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                @keyframes rayAppear {
                    0% { opacity: 0; height: 0%; }
                    50% { opacity: 0.5; height: 100%; }
                    100% { opacity: 0; height: 100%; }
                }
                
                /* 메인 박스 */
                .rb-box-container {
                    position: relative;
                    z-index: 10;
                    transform: scale(0.8);
                    animation: boxAppear 0.5s ease-out forwards, boxShake 2s ease-in-out 0.5s;
                }
                
                @keyframes boxAppear {
                    to { transform: scale(1); }
                }
                
                @keyframes boxShake {
                    0%, 100% { transform: translateX(0) rotate(0deg); }
                    10% { transform: translateX(-2px) rotate(-1deg); }
                    20% { transform: translateX(2px) rotate(1deg); }
                    30% { transform: translateX(-2px) rotate(-1deg); }
                    40% { transform: translateX(2px) rotate(1deg); }
                    50% { transform: translateX(-1px) rotate(-0.5deg); }
                    60% { transform: translateX(1px) rotate(0.5deg); }
                    70% { transform: translateX(-1px) rotate(-0.5deg); }
                    80% { transform: translateX(1px) rotate(0.5deg); }
                }
                
                .rb-glow-effect {
                    position: absolute;
                    width: 200px;
                    height: 200px;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: radial-gradient(circle, rgba(255,215,0,0.4) 0%, transparent 70%);
                    filter: blur(20px);
                    animation: glowPulse 2s ease-in-out infinite;
                }
                
                @keyframes glowPulse {
                    0%, 100% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.5; }
                    50% { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
                }
                
                .rb-box-main {
                    position: relative;
                    width: 120px;
                    height: 120px;
                }
                
                .rb-box-body {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
                    border: 3px solid #000;
                    border-radius: 10px;
                    box-shadow: 
                        inset 0 2px 5px rgba(255,255,255,0.2),
                        0 10px 30px rgba(0,0,0,0.5);
                }
                
                .rb-box-lid {
                    position: absolute;
                    width: 100%;
                    height: 30px;
                    top: -15px;
                    background: linear-gradient(135deg, #3a3a3a 0%, #2a2a2a 100%);
                    border: 3px solid #000;
                    border-radius: 10px 10px 5px 5px;
                    transform-origin: bottom;
                    animation: lidOpen 0.8s ease-out 2.5s forwards;
                    box-shadow: 
                        inset 0 2px 5px rgba(255,255,255,0.3),
                        0 5px 15px rgba(0,0,0,0.5);
                }
                
                @keyframes lidOpen {
                    to { 
                        transform: rotateX(-110deg) translateY(-10px);
                        opacity: 0.8;
                    }
                }
                
                .rb-box-lock {
                    position: absolute;
                    width: 30px;
                    height: 30px;
                    top: 35%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: radial-gradient(circle, #FFD700 0%, #FFA500 100%);
                    border: 2px solid #000;
                    border-radius: 50%;
                    animation: lockBreak 0.5s ease-out 2.3s forwards;
                    box-shadow: 0 0 20px rgba(255,215,0,0.8);
                }
                
                @keyframes lockBreak {
                    to {
                        transform: translate(-50%, -50%) scale(0);
                        opacity: 0;
                    }
                }
                
                /* 파티클 시스템 */
                .rb-particle-system {
                    position: absolute;
                    width: 300px;
                    height: 300px;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    pointer-events: none;
                }
                
                .rb-star-particle {
                    position: absolute;
                    width: 4px;
                    height: 4px;
                    background: #FFD700;
                    opacity: 0;
                    animation: starBurst 2s ease-out calc(2.5s + var(--delay)) forwards;
                    box-shadow: 0 0 6px #FFD700;
                }
                
                @keyframes starBurst {
                    0% {
                        transform: translate(-50%, -50%) scale(0);
                        opacity: 0;
                    }
                    20% {
                        opacity: 1;
                    }
                    100% {
                        transform: translate(
                            calc(-50% + ${() => (Math.random() - 0.5) * 200}px),
                            calc(-50% + ${() => (Math.random() - 0.5) * 200}px)
                        ) scale(0);
                        opacity: 0;
                    }
                }
                
                ${Array(20).fill().map((_, i) => `
                    .rb-star-particle:nth-child(${i + 1}) {
                        top: 50%;
                        left: 50%;
                        animation: starBurst${i} 2s ease-out calc(2.5s + ${i * 0.05}s) forwards;
                    }
                    
                    @keyframes starBurst${i} {
                        0% {
                            transform: translate(-50%, -50%) scale(0);
                            opacity: 0;
                        }
                        20% {
                            opacity: 1;
                        }
                        100% {
                            transform: translate(
                                calc(-50% + ${(Math.random() - 0.5) * 300}px),
                                calc(-50% + ${(Math.random() - 0.5) * 300}px)
                            ) scale(0);
                            opacity: 0;
                        }
                    }
                `).join('')}
                
                /* 충격파 */
                .rb-shockwave,
                .rb-shockwave-2 {
                    position: absolute;
                    width: 100px;
                    height: 100px;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    border: 2px solid rgba(255,255,255,0.5);
                    border-radius: 50%;
                    opacity: 0;
                    animation: shockwave 1s ease-out 2.5s forwards;
                }
                
                .rb-shockwave-2 {
                    animation-delay: 2.7s;
                }
                
                @keyframes shockwave {
                    0% {
                        transform: translate(-50%, -50%) scale(0);
                        opacity: 1;
                    }
                    100% {
                        transform: translate(-50%, -50%) scale(4);
                        opacity: 0;
                    }
                }
                
                /* 상태 텍스트 */
                .rb-opening-status {
                    position: absolute;
                    bottom: 50px;
                    text-align: center;
                }
                
                .rb-status-text {
                    font-size: 14px;
                    font-weight: 700;
                    color: #fff;
                    letter-spacing: 3px;
                    margin-bottom: 10px;
                    text-shadow: 0 0 10px rgba(255,255,255,0.5);
                    animation: textPulse 0.5s ease-in-out infinite;
                }
                
                @keyframes textPulse {
                    0%, 100% { opacity: 0.5; }
                    50% { opacity: 1; }
                }
                
                .rb-progress-bar {
                    width: 200px;
                    height: 4px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 2px;
                    overflow: hidden;
                }
                
                .rb-progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #FFD700, #FFA500);
                    animation: progressFill 3s ease-out forwards;
                    box-shadow: 0 0 10px rgba(255,215,0,0.8);
                }
                
                @keyframes progressFill {
                    from { width: 0%; }
                    to { width: 100%; }
                }
                
                /* 등급별 추가 효과 */
                .rb-premium-opening.legendary {
                    background: radial-gradient(ellipse at center, #2a0000 0%, #000 100%);
                }
                
                .rb-premium-opening.legendary .rb-glow-effect {
                    background: radial-gradient(circle, rgba(255,0,0,0.6) 0%, transparent 70%);
                }
                
                .rb-premium-opening.legendary .rb-star-particle {
                    background: #FF0000;
                    box-shadow: 0 0 10px #FF0000;
                }
                
                .rb-premium-opening.epic {
                    background: radial-gradient(ellipse at center, #1a0a2a 0%, #000 100%);
                }
                
                .rb-premium-opening.epic .rb-glow-effect {
                    background: radial-gradient(circle, rgba(138,43,226,0.6) 0%, transparent 70%);
                }
                
                .rb-premium-opening.epic .rb-star-particle {
                    background: #8A2BE2;
                    box-shadow: 0 0 10px #8A2BE2;
                }
                
                .rb-premium-opening.rare {
                    background: radial-gradient(ellipse at center, #0a1a2a 0%, #000 100%);
                }
                
                .rb-premium-opening.rare .rb-glow-effect {
                    background: radial-gradient(circle, rgba(0,123,255,0.6) 0%, transparent 70%);
                }
                
                .rb-premium-opening.rare .rb-star-particle {
                    background: #007BFF;
                    box-shadow: 0 0 10px #007BFF;
                }
            </style>
        `);
    }
    
    $('#resultContent').html(html);
    
    // 아이템 등급 확인
    const itemGrade = data.item.rbi_grade || 'normal';
    $('.rb-premium-opening').addClass(itemGrade);
    
    // 3.5초 후 결과 표시
    setTimeout(function() {
        const item = data.item;
        
        // 기본값 설정
        const itemName = item.rbi_name || '아이템';
        const itemValue = parseInt(item.rbi_value) || 0;
        const itemImage = item.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIyNSIgY3k9IjI1IiByPSIyMCIgZmlsbD0iI2VlZSIgc3Ryb2tlPSIjOTk5IiBzdHJva2Utd2lkdGg9IjIiLz4KICA8dGV4dCB4PSIyNSIgeT0iMzAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5OTkiIGZvbnQtc2l6ZT0iMjAiPj88L3RleHQ+Cjwvc3ZnPg==';
        
        // 등급별 설정
        let glowColor = '#FFD700';
        let bgGradient = 'radial-gradient(ellipse at center, rgba(255,215,0,0.1) 0%, transparent 50%)';
        
        if (itemGrade === 'legendary') {
            glowColor = '#FF0000';
            bgGradient = 'radial-gradient(ellipse at center, rgba(255,0,0,0.2) 0%, transparent 50%)';
        } else if (itemGrade === 'epic') {
            glowColor = '#8A2BE2';
            bgGradient = 'radial-gradient(ellipse at center, rgba(138,43,226,0.2) 0%, transparent 50%)';
        } else if (itemGrade === 'rare') {
            glowColor = '#007BFF';
            bgGradient = 'radial-gradient(ellipse at center, rgba(0,123,255,0.2) 0%, transparent 50%)';
        }
        
        const gradeNames = {
            'normal': '일반',
            'rare': '레어',
            'epic': '에픽',
            'legendary': '레전더리'
        };
        const gradeName = item.grade_name || gradeNames[itemGrade] || itemGrade.toUpperCase();
        
        html = `
            <div class="rb-result-reveal" style="
                animation: revealResult 0.8s ease-out;
                text-align: center;
                padding: 40px;
                background: ${bgGradient};
            ">
                <div class="rb-result-spotlight" style="
                    position: absolute;
                    width: 300px;
                    height: 300px;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background: radial-gradient(circle, ${glowColor}33 0%, transparent 70%);
                    filter: blur(30px);
                    animation: spotlightPulse 2s ease-in-out infinite;
                "></div>
                
                <div class="rb-result-item" style="
                    position: relative;
                    width: 150px;
                    height: 150px;
                    margin: 0 auto 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 3px solid ${glowColor};
                    border-radius: 10px;
                    background: rgba(255,255,255,0.05);
                    box-shadow: 0 0 30px ${glowColor};
                    animation: itemFloat 3s ease-in-out infinite;
                ">
                    <img src="${itemImage}" alt="${itemName}" style="
                        max-width: 80%;
                        max-height: 80%;
                        filter: drop-shadow(0 0 10px ${glowColor});
                    " onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8Y2lyY2xlIGN4PSIyNSIgY3k9IjI1IiByPSIyMCIgZmlsbD0iI2VlZSIgc3Ryb2tlPSIjOTk5IiBzdHJva2Utd2lkdGg9IjIiLz4KICA8dGV4dCB4PSIyNSIgeT0iMzAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5OTkiIGZvbnQtc2l6ZT0iMjAiPj88L3RleHQ+Cjwvc3ZnPg==';">
                    
                    <div style="
                        position: absolute;
                        top: -15px;
                        right: -15px;
                        padding: 5px 15px;
                        background: ${glowColor};
                        color: #fff;
                        font-size: 12px;
                        font-weight: 700;
                        border-radius: 20px;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        box-shadow: 0 0 20px ${glowColor};
                    ">${gradeName}</div>
                </div>
                
                <h3 style="
                    font-size: 28px;
                    font-weight: 800;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    text-shadow: 0 0 20px ${glowColor};
                    animation: textGlow 2s ease-in-out infinite;
                ">${itemName}</h3>
                
                ${item.rbi_desc ? `<p style="color: #aaa; margin-bottom: 20px; font-size: 14px;">${item.rbi_desc}</p>` : ''}
                
                ${itemValue > 0 ? `
                    <div style="
                        display: inline-flex;
                        align-items: center;
                        gap: 10px;
                        padding: 15px 30px;
                        background: linear-gradient(135deg, ${glowColor}22 0%, transparent 100%);
                        border: 2px solid ${glowColor};
                        border-radius: 30px;
                        font-size: 20px;
                        font-weight: 700;
                        color: ${glowColor};
                        animation: bounceIn 0.6s ease-out 0.5s both;
                    ">
                        <i class="bi bi-coin"></i> 
                        <span>${itemValue.toLocaleString()}P 획득!</span>
                    </div>
                ` : ''}
            </div>
            
            <style>
                @keyframes revealResult {
                    from {
                        opacity: 0;
                        transform: scale(0.8) rotateY(180deg);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1) rotateY(0deg);
                    }
                }
                
                @keyframes spotlightPulse {
                    0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
                    50% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
                }
                
                @keyframes itemFloat {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
                
                @keyframes textGlow {
                    0%, 100% { opacity: 0.8; }
                    50% { opacity: 1; }
                }
                
                @keyframes bounceIn {
                    0% {
                        opacity: 0;
                        transform: scale(0.3);
                    }
                    50% {
                        transform: scale(1.05);
                    }
                    70% {
                        transform: scale(0.9);
                    }
                    100% {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
            </style>
        `;
        
        $('#resultContent').html(html);
        
    }, 3500);
}

function closeResult() {
    closeModal('resultModal');
    location.reload(); // 통계 업데이트를 위해 새로고침
}

// ===================================
// 구매내역
// ===================================
function loadHistory() {
    $.ajax({
        url: './ajax/get_history.php',
        type: 'GET',
        data: { 
            page: 1,
            limit: 50
        },
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                displayHistory(response);
            }
        }
    });
}

function displayHistory(data) {
    let html = '';
    
    if (data.list && data.list.length > 0) {
        html = '<div style="display: grid; gap: 10px;">';
        data.list.forEach(item => {
            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f8f8; border: 1px solid #e0e0e0;">
                    <div>
                        <div style="font-weight: 700; margin-bottom: 4px;">${item.rb_name}</div>
                        <div style="color: #666; font-size: 13px;">
                            ${item.rbi_name}
                            <span class="rb-history-grade ${item.rbi_grade}">
                                ${item.rbi_grade.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700;">${parseInt(item.rb_price).toLocaleString()}P</div>
                        <div style="color: #999; font-size: 11px;">${item.rbh_created_at}</div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
    } else {
        html = '<div class="rb-empty"><i class="bi bi-inbox"></i><p>구매 내역이 없습니다</p></div>';
    }
    
    $('#historyContent').html(html);
}

// ===================================
// 모달 제어
// ===================================
function closeModal(modalId) {
    $('#' + modalId).removeClass('show');
}

// ESC 키로 모달 닫기
$(document).on('keydown', function(e) {
    if (e.key === 'Escape') {
        $('.rb-modal.show').removeClass('show');
    }
});

// 모달 외부 클릭 시 닫기
$('.rb-modal').on('click', function(e) {
    if (e.target === this) {
        $(this).removeClass('show');
    }
});

// ===================================
// 실시간 업데이트
// ===================================
<?php if (get_randombox_config('enable_realtime')) : ?>
function updateRealtimeWinners() {
    $.get('./ajax/get_realtime_winners.php', function(data) {
        if (data.status && data.winners) {
            let html = '';
            data.winners.slice(0, 10).forEach(winner => {
                html += `
                    <div class="rb-winner-item">
                        <div class="rb-winner-avatar">${winner.display_name.charAt(0)}</div>
                        <div class="rb-winner-info">
                            <div class="rb-winner-name">${winner.display_name}</div>
                            <div class="rb-winner-prize">
                                ${winner.rbi_name}
                                <span class="rb-history-grade ${winner.rbi_grade}">
                                    ${winner.rbi_grade.toUpperCase()}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#realtimeWinners').html(html || '<div class="rb-empty"><p>아직 당첨자가 없습니다</p></div>');
        }
    });
}

// 10초마다 업데이트
setInterval(updateRealtimeWinners, 10000);
<?php endif; ?>
</script>

</body>
</html>

<?php
include_once(G5_PATH.'/tail.php');
?>