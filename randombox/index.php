<?php
/*
 * 파일명: index.php
 * 위치: /randombox/
 * 기능: 랜덤박스 메인 페이지 - 모던 컴팩트 디자인
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// 데이터 조회
// ===================================

/* 활성화된 박스 목록 */
$box_list = get_randombox_list();

/* 통계 데이터 */
$today = date('Y-m-d');

// 오늘 판매량
$sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history 
        WHERE DATE(rbh_created_at) = '{$today}'";
$today_sales = sql_fetch($sql);

// 내 구매 통계
$sql = "SELECT 
        COUNT(*) as total_count,
        COUNT(CASE WHEN rbi_grade IN ('rare', 'epic', 'legendary') THEN 1 END) as rare_count,
        IFNULL(SUM(rb_price), 0) as total_spent,
        IFNULL(SUM(rbi_value), 0) as total_earned
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}'";
$my_stats = sql_fetch($sql);

// ===================================
// 페이지 헤더
// ===================================

$g5['title'] = '랜덤박스';
include_once(G5_PATH.'/head.php');
?>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
/* ===================================
 * 기본 설정
 * =================================== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f5f5f5;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* ===================================
 * 메인 컨테이너
 * =================================== */
.rb-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 15px;
}

/* ===================================
 * 헤더
 * =================================== */
.rb-header {
    background: #fff;
    border: 1px solid #e0e0e0;
    height: 60px;
    display: flex;
    align-items: center;
    padding: 0 20px;
    margin-bottom: 15px;
}

.rb-header-left {
    display: flex;
    align-items: center;
    gap: 30px;
    flex: 1;
}

.rb-logo {
    font-size: 18px;
    font-weight: 900;
    color: #000;
    text-decoration: none;
    letter-spacing: -0.5px;
}

.rb-nav {
    display: flex;
    gap: 20px;
}

.rb-nav a {
    color: #666;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: color 0.2s;
}

.rb-nav a:hover {
    color: #000;
}

.rb-header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.rb-point {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 0 15px;
    border-left: 1px solid #e0e0e0;
    border-right: 1px solid #e0e0e0;
}

.rb-point-label {
    font-size: 11px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rb-point-value {
    font-size: 16px;
    font-weight: 700;
    color: #000;
}

.rb-btn-sm {
    padding: 6px 14px;
    border: 1px solid #000;
    background: #fff;
    color: #000;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.rb-btn-sm:hover {
    background: #000;
    color: #fff;
}

/* ===================================
 * 통계 바
 * =================================== */
.rb-stats-bar {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1px;
    background: #e0e0e0;
    margin-bottom: 15px;
    height: 60px;
}

.rb-stat {
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 10px;
}

.rb-stat-label {
    font-size: 11px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.rb-stat-value {
    font-size: 18px;
    font-weight: 700;
    color: #000;
}

.rb-stat-value.text-success {
    color: #27ae60;
}

.rb-stat-value.text-danger {
    color: #e74c3c;
}

/* ===================================
 * 메인 레이아웃
 * =================================== */
.rb-layout {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 15px;
}

/* ===================================
 * 박스 섹션
 * =================================== */
.rb-box-section {
    background: #fff;
    border: 1px solid #e0e0e0;
}

/* 섹션 헤더 */
.rb-section-header {
    height: 48px;
    padding: 0 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rb-section-title {
    font-size: 14px;
    font-weight: 700;
    color: #000;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* 필터 탭 */
.rb-filter-tabs {
    display: flex;
    gap: 0;
    height: 32px;
    background: #f8f8f8;
    padding: 3px;
    border-radius: 4px;
}

.rb-filter-tab {
    padding: 0 16px;
    background: transparent;
    border: none;
    color: #666;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 3px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rb-filter-tab:hover {
    color: #000;
}

.rb-filter-tab.active {
    background: #000;
    color: #fff;
}

/* 박스 그리드 */
.rb-box-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1px;
    background: #e0e0e0;
    padding: 1px;
}

/* 박스 카드 */
.rb-box-card {
    background: #fff;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}

.rb-box-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1;
}

/* 박스 배지 */
.rb-box-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 3px 8px;
    background: #000;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 1;
}

.rb-box-badge.premium {
    background: #FFD700;
    color: #000;
}

.rb-box-badge.event {
    background: #FF4444;
}

/* 박스 이미지 */
.rb-box-image {
    height: 140px;
    background: #f8f8f8;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
}

.rb-box-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* 박스 정보 */
.rb-box-info {
    padding: 12px;
    border-top: 1px solid #f0f0f0;
}

.rb-box-name {
    font-size: 13px;
    font-weight: 700;
    color: #000;
    margin-bottom: 6px;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rb-box-meta {
    display: flex;
    gap: 12px;
    margin-bottom: 10px;
    font-size: 11px;
    color: #999;
}

.rb-box-meta-item {
    display: flex;
    align-items: center;
    gap: 3px;
}

.rb-box-meta-item i {
    font-size: 12px;
}

/* 박스 푸터 */
.rb-box-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
}

.rb-box-price {
    font-size: 16px;
    font-weight: 700;
    color: #000;
}

.rb-box-price small {
    font-size: 12px;
    font-weight: 400;
    color: #666;
}

.rb-buy-btn {
    padding: 5px 14px;
    background: #000;
    color: #fff;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rb-buy-btn:hover:not(:disabled) {
    background: #333;
}

.rb-buy-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* ===================================
 * 사이드바
 * =================================== */
.rb-sidebar {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* 위젯 */
.rb-widget {
    background: #fff;
    border: 1px solid #e0e0e0;
}

.rb-widget-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #000;
}

.rb-widget-body {
    padding: 16px;
}

/* 내 통계 위젯 */
.rb-my-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rb-my-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rb-my-stat-label {
    font-size: 12px;
    color: #666;
}

.rb-my-stat-value {
    font-size: 16px;
    font-weight: 700;
    color: #000;
}

/* 실시간 당첨 */
.rb-realtime-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rb-realtime-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f8f8;
    border-radius: 4px;
}

.rb-realtime-avatar {
    width: 28px;
    height: 28px;
    background: #000;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.rb-realtime-info {
    flex: 1;
    min-width: 0;
}

.rb-realtime-user {
    font-size: 12px;
    font-weight: 600;
    color: #000;
    margin-bottom: 2px;
}

.rb-realtime-prize {
    font-size: 11px;
    color: #666;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rb-realtime-time {
    font-size: 10px;
    color: #999;
    flex-shrink: 0;
}

/* ===================================
 * 빈 상태
 * =================================== */
.rb-empty {
    padding: 60px 20px;
    text-align: center;
    color: #999;
}

.rb-empty i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.rb-empty p {
    font-size: 14px;
}

/* ===================================
 * 반응형
 * =================================== */
@media (max-width: 1200px) {
    .rb-stats-bar {
        grid-template-columns: repeat(3, 1fr);
        height: auto;
    }
}

@media (max-width: 768px) {
    .rb-container {
        padding: 10px;
    }
    
    .rb-header {
        height: auto;
        flex-direction: column;
        padding: 15px;
        gap: 15px;
    }
    
    .rb-header-left {
        width: 100%;
        justify-content: space-between;
    }
    
    .rb-header-right {
        width: 100%;
        justify-content: space-between;
    }
    
    .rb-point {
        border: none;
        padding: 0;
    }
    
    .rb-layout {
        grid-template-columns: 1fr;
    }
    
    .rb-box-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .rb-sidebar {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="rb-container">
    
    <!-- ===================================
     * 헤더
     * =================================== -->
    <header class="rb-header">
        <div class="rb-header-left">
            <a href="./" class="rb-logo">RANDOMBOX</a>
            <nav class="rb-nav">
                <a href="./">스토어</a>
                <a href="./history.php">구매내역</a>
                <?php if (get_randombox_config('enable_gift')) : ?>
                <a href="./gift.php">선물함</a>
                <?php endif; ?>
            </nav>
        </div>
        
        <div class="rb-header-right">
            <div class="rb-point">
                <span class="rb-point-label">보유 포인트</span>
                <span class="rb-point-value" id="userPoint"><?php echo number_format($member['mb_point']); ?>P</span>
            </div>
            <a href="#" class="rb-btn-sm" onclick="showHistoryModal(); return false;">
                <i class="bi bi-clock-history"></i> 내역
            </a>
        </div>
    </header>
    
    <!-- ===================================
     * 통계 바
     * =================================== -->
    <div class="rb-stats-bar">
        <div class="rb-stat">
            <span class="rb-stat-label">오늘 판매</span>
            <span class="rb-stat-value"><?php echo number_format($today_sales['cnt']); ?></span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">내 구매</span>
            <span class="rb-stat-value"><?php echo number_format($my_stats['total_count']); ?></span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">희귀 획득</span>
            <span class="rb-stat-value"><?php echo number_format($my_stats['rare_count']); ?></span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">사용 포인트</span>
            <span class="rb-stat-value text-danger"><?php echo number_format($my_stats['total_spent']); ?>P</span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">획득 포인트</span>
            <span class="rb-stat-value text-success"><?php echo number_format($my_stats['total_earned']); ?>P</span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">순손익</span>
            <span class="rb-stat-value <?php echo ($my_stats['total_earned'] - $my_stats['total_spent']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo number_format($my_stats['total_earned'] - $my_stats['total_spent']); ?>P
            </span>
        </div>
    </div>
    
    <!-- ===================================
     * 메인 레이아웃
     * =================================== -->
    <div class="rb-layout">
        
        <!-- ===================================
         * 박스 섹션
         * =================================== -->
        <div class="rb-box-section">
            <div class="rb-section-header">
                <h2 class="rb-section-title">랜덤박스 스토어</h2>
                <div class="rb-filter-tabs">
                    <button class="rb-filter-tab active" data-filter="all">전체</button>
                    <button class="rb-filter-tab" data-filter="normal">일반</button>
                    <button class="rb-filter-tab" data-filter="event">이벤트</button>
                    <button class="rb-filter-tab" data-filter="premium">프리미엄</button>
                </div>
            </div>
            
            <?php if (!$box_list) : ?>
            <div class="rb-empty">
                <i class="bi bi-inbox"></i>
                <p>판매 중인 상품이 없습니다</p>
            </div>
            <?php else : ?>
            
            <div class="rb-box-grid">
                <?php foreach ($box_list as $box) : 
                    // 박스 이미지
                    $box_img = './img/box-default.png';
                    if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
                        $box_img = G5_DATA_URL.'/randombox/box/'.$box['rb_image'];
                    }
                    
                    // 구매 가능 여부
                    $can_purchase = check_randombox_purchase($box['rb_id'], $member['mb_id']);
                    
                    // 아이템 수
                    $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_items 
                            WHERE rb_id = '{$box['rb_id']}' AND rbi_status = 1";
                    $item_count = sql_fetch($sql);
                ?>
                
                <div class="rb-box-card" data-type="<?php echo $box['rb_type']; ?>">
                    <?php if ($box['rb_type'] != 'normal') : ?>
                    <span class="rb-box-badge <?php echo $box['rb_type']; ?>">
                        <?php echo get_box_type_name($box['rb_type']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <div class="rb-box-image">
                        <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
                    </div>
                    
                    <div class="rb-box-info">
                        <h3 class="rb-box-name"><?php echo $box['rb_name']; ?></h3>
                        
                        <div class="rb-box-meta">
                            <div class="rb-box-meta-item">
                                <i class="bi bi-box2"></i>
                                <?php echo $item_count['cnt']; ?>종
                            </div>
                            
                            <?php if ($box['rb_limit_qty'] > 0) : ?>
                            <div class="rb-box-meta-item">
                                <i class="bi bi-calendar-day"></i>
                                일일 <?php echo $box['rb_limit_qty']; ?>개
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="rb-box-footer">
                            <div class="rb-box-price">
                                <?php echo number_format($box['rb_price']); ?><small>P</small>
                            </div>
                            
                            <button class="rb-buy-btn" 
                                    data-box-id="<?php echo $box['rb_id']; ?>"
                                    data-box-name="<?php echo $box['rb_name']; ?>"
                                    data-box-price="<?php echo $box['rb_price']; ?>"
                                    data-box-image="<?php echo $box_img; ?>"
                                    <?php echo !$can_purchase['status'] ? 'disabled' : ''; ?>>
                                구매
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
        
        <!-- ===================================
         * 사이드바
         * =================================== -->
        <aside class="rb-sidebar">
            
            <!-- 내 통계 위젯 -->
            <div class="rb-widget">
                <div class="rb-widget-header">내 통계</div>
                <div class="rb-widget-body">
                    <div class="rb-my-stats">
                        <div class="rb-my-stat">
                            <span class="rb-my-stat-label">총 구매 횟수</span>
                            <span class="rb-my-stat-value"><?php echo number_format($my_stats['total_count']); ?>회</span>
                        </div>
                        <div class="rb-my-stat">
                            <span class="rb-my-stat-label">희귀 아이템</span>
                            <span class="rb-my-stat-value"><?php echo number_format($my_stats['rare_count']); ?>개</span>
                        </div>
                        <div class="rb-my-stat">
                            <span class="rb-my-stat-label">수익률</span>
                            <span class="rb-my-stat-value">
                                <?php 
                                $profit_rate = $my_stats['total_spent'] > 0 ? 
                                    round(($my_stats['total_earned'] / $my_stats['total_spent']) * 100, 1) : 0;
                                echo $profit_rate;
                                ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 실시간 당첨 위젯 -->
            <?php if (get_randombox_config('enable_realtime')) : ?>
            <div class="rb-widget">
                <div class="rb-widget-header">실시간 당첨</div>
                <div class="rb-widget-body">
                    <div class="rb-realtime-list" id="realtimeWinners">
                        <!-- AJAX로 로드 -->
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </aside>
        
    </div>
</div>

<!-- 모달 포함 -->
<?php include_once('./modals.php'); ?>

<!-- 내역 모달 -->
<div id="historyModal" class="rb-modal">
    <div class="rb-modal-dialog rb-modal-wide">
        <div class="rb-modal-content">
            <div class="rb-modal-header">
                <h4 class="rb-modal-title">구매 내역</h4>
                <button type="button" class="rb-modal-close" data-dismiss="modal">&times;</button>
            </div>
            
            <div class="rb-modal-body rb-history-body">
                <!-- 통계 요약 -->
                <div class="rb-history-summary">
                    <div class="rb-summary-item">
                        <span class="rb-summary-label">총 구매</span>
                        <span class="rb-summary-value" id="historyTotalCount">0회</span>
                    </div>
                    <div class="rb-summary-item">
                        <span class="rb-summary-label">사용 포인트</span>
                        <span class="rb-summary-value" id="historyTotalSpent">0P</span>
                    </div>
                    <div class="rb-summary-item">
                        <span class="rb-summary-label">획득 포인트</span>
                        <span class="rb-summary-value" id="historyTotalEarned">0P</span>
                    </div>
                    <div class="rb-summary-item">
                        <span class="rb-summary-label">희귀 아이템</span>
                        <span class="rb-summary-value" id="historyRareCount">0개</span>
                    </div>
                </div>
                
                <!-- 필터 -->
                <div class="rb-history-filter">
                    <select id="historyFilterGrade" class="rb-filter-select">
                        <option value="">전체 등급</option>
                        <option value="normal">일반</option>
                        <option value="rare">레어</option>
                        <option value="epic">에픽</option>
                        <option value="legendary">레전더리</option>
                    </select>
                    <select id="historyFilterDays" class="rb-filter-select">
                        <option value="7">최근 7일</option>
                        <option value="30" selected>최근 30일</option>
                        <option value="90">최근 90일</option>
                        <option value="all">전체</option>
                    </select>
                </div>
                
                <!-- 내역 리스트 -->
                <div class="rb-history-list" id="historyList">
                    <div class="rb-loading-spinner">
                        <i class="bi bi-arrow-repeat spin"></i>
                        <p>불러오는 중...</p>
                    </div>
                </div>
                
                <!-- 더보기 버튼 -->
                <div class="rb-history-more" id="historyMore" style="display:none;">
                    <button type="button" class="rb-btn-more" onclick="loadMoreHistory()">
                        더보기 <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 내역 모달 CSS -->
<style>
/* 내역 모달 스타일 */
.rb-modal-wide {
    max-width: 800px;
}

.rb-history-body {
    padding: 0;
    max-height: 80vh;
    overflow-y: auto;
}

/* 통계 요약 */
.rb-history-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: #e0e0e0;
    border-bottom: 1px solid #e0e0e0;
}

.rb-summary-item {
    background: #fff;
    padding: 20px;
    text-align: center;
}

.rb-summary-label {
    display: block;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.rb-summary-value {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

/* 필터 */
.rb-history-filter {
    padding: 20px;
    background: #f8f8f8;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    gap: 12px;
}

.rb-filter-select {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: #fff;
    font-size: 14px;
    cursor: pointer;
    transition: border-color 0.2s;
}

.rb-filter-select:focus {
    outline: none;
    border-color: #000;
}

/* 내역 리스트 */
.rb-history-list {
    padding: 20px;
    min-height: 200px;
}

.rb-history-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #fff;
    border: 1px solid #e0e0e0;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.rb-history-item:hover {
    border-color: #000;
    transform: translateX(4px);
}

.rb-history-date {
    font-size: 12px;
    color: #999;
    min-width: 80px;
}

.rb-history-image {
    width: 60px;
    height: 60px;
    background: #f8f8f8;
    padding: 8px;
    border: 1px solid #e0e0e0;
}

.rb-history-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.rb-history-info {
    flex: 1;
}

.rb-history-box {
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
}

.rb-history-result {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rb-history-grade {
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rb-history-grade.grade-normal {
    background: #f0f0f0;
    color: #666;
}

.rb-history-grade.grade-rare {
    background: #000;
    color: #fff;
}

.rb-history-grade.grade-epic {
    background: #000;
    color: #fff;
    box-shadow: inset 0 0 0 1px #fff;
}

.rb-history-grade.grade-legendary {
    background: #000;
    color: #fff;
    animation: legendaryPulse 2s infinite;
}

@keyframes legendaryPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.rb-history-name {
    font-weight: 600;
    color: #000;
}

.rb-history-points {
    text-align: right;
    min-width: 80px;
}

.rb-history-cost {
    font-size: 14px;
    color: #e74c3c;
    font-weight: 600;
}

.rb-history-earned {
    font-size: 12px;
    color: #27ae60;
}

/* 로딩 스피너 */
.rb-loading-spinner {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.rb-loading-spinner i {
    font-size: 32px;
    margin-bottom: 12px;
}

/* 더보기 버튼 */
.rb-history-more {
    padding: 20px;
    text-align: center;
    border-top: 1px solid #e0e0e0;
}

.rb-btn-more {
    padding: 10px 32px;
    background: #fff;
    border: 1px solid #000;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rb-btn-more:hover {
    background: #000;
    color: #fff;
}

/* 빈 상태 */
.rb-history-empty {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.rb-history-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

/* 반응형 */
@media (max-width: 768px) {
    .rb-modal-wide {
        max-width: 95%;
    }
    
    .rb-history-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .rb-history-item {
        flex-wrap: wrap;
    }
    
    .rb-history-date {
        width: 100%;
        margin-bottom: 8px;
    }
    
    .rb-history-points {
        width: 100%;
        text-align: left;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #e0e0e0;
    }
}
</style>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="./randombox.js?v=<?php echo time(); ?>"></script>
<script>
// 필터 기능
document.querySelectorAll('.rb-filter-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.rb-filter-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        document.querySelectorAll('.rb-box-card').forEach(box => {
            if (filter === 'all' || box.dataset.type === filter) {
                box.style.display = '';
            } else {
                box.style.display = 'none';
            }
        });
    });
});

// 실시간 당첨 현황
<?php if (get_randombox_config('enable_realtime')) : ?>
function loadRealtimeWinners() {
    $.get('./ajax/get_realtime_winners.php', function(data) {
        if (data.status && data.winners) {
            const html = data.winners.slice(0, 5).map(winner => `
                <div class="rb-realtime-item">
                    <div class="rb-realtime-avatar">${winner.display_name.charAt(0)}</div>
                    <div class="rb-realtime-info">
                        <div class="rb-realtime-user">${winner.display_name}</div>
                        <div class="rb-realtime-prize">${winner.rbi_name}</div>
                    </div>
                    <div class="rb-realtime-time">${winner.time_ago}</div>
                </div>
            `).join('');
            
            $('#realtimeWinners').html(html || '<p style="text-align:center;color:#999;font-size:12px;">아직 당첨자가 없습니다</p>');
        }
    });
}

loadRealtimeWinners();
setInterval(loadRealtimeWinners, 10000);
<?php endif; ?>

// ===================================
// 내역 모달 관련 변수
// ===================================

var historyPage = 1;
var historyLoading = false;
var historyHasMore = true;

// ===================================
// 내역 모달 함수
// ===================================

/**
 * 내역 모달 열기
 */
function showHistoryModal() {
    // 변수 초기화
    historyPage = 1;
    historyHasMore = true;
    $('#historyList').html('<div class="rb-loading-spinner"><i class="bi bi-arrow-repeat spin"></i><p>불러오는 중...</p></div>');
    
    // 모달 표시
    $('#historyModal').addClass('show').css('display', 'flex');
    
    // 데이터 로드
    loadHistory();
}

/**
 * 내역 데이터 로드
 */
function loadHistory() {
    if (historyLoading) return;
    historyLoading = true;
    
    const params = {
        page: historyPage,
        days: $('#historyFilterDays').val(),
        grade: $('#historyFilterGrade').val()
    };
    
    // days 값에 따른 날짜 계산
    let startDate = '';
    if (params.days !== 'all') {
        const date = new Date();
        date.setDate(date.getDate() - parseInt(params.days));
        startDate = date.toISOString().split('T')[0];
    }
    
    $.ajax({
        url: './ajax/get_history.php',
        type: 'GET',
        data: {
            page: params.page,
            start_date: startDate,
            end_date: new Date().toISOString().split('T')[0],
            grade: params.grade
        },
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                // 통계 업데이트
                updateHistoryStats(response.stats);
                
                // 리스트 표시
                if (historyPage === 1) {
                    displayHistoryList(response.list);
                } else {
                    appendHistoryList(response.list);
                }
                
                // 더보기 버튼 표시 여부
                if (response.list.length < 20 || historyPage >= response.total_pages) {
                    historyHasMore = false;
                    $('#historyMore').hide();
                } else {
                    $('#historyMore').show();
                }
            }
        },
        error: function() {
            if (historyPage === 1) {
                $('#historyList').html('<div class="rb-history-empty"><i class="bi bi-exclamation-circle"></i><p>데이터를 불러올 수 없습니다.</p></div>');
            }
        },
        complete: function() {
            historyLoading = false;
        }
    });
}

/**
 * 통계 업데이트
 */
function updateHistoryStats(stats) {
    $('#historyTotalCount').text(number_format(stats.total_count || 0) + '회');
    $('#historyTotalSpent').text(number_format(stats.total_spent || 0) + 'P');
    $('#historyTotalEarned').text(number_format(stats.total_earned || 0) + 'P');
    $('#historyRareCount').text(number_format(stats.rare_count || 0) + '개');
}

/**
 * 내역 리스트 표시
 */
function displayHistoryList(list) {
    if (!list || list.length === 0) {
        $('#historyList').html('<div class="rb-history-empty"><i class="bi bi-inbox"></i><p>구매 내역이 없습니다.</p></div>');
        return;
    }
    
    const html = list.map(item => createHistoryItemHtml(item)).join('');
    $('#historyList').html(html);
}

/**
 * 내역 리스트 추가
 */
function appendHistoryList(list) {
    if (!list || list.length === 0) return;
    
    const html = list.map(item => createHistoryItemHtml(item)).join('');
    $('#historyList').append(html);
}

/**
 * 내역 아이템 HTML 생성
 */
function createHistoryItemHtml(item) {
    const itemImage = item.item_image || './img/item-default.png';
    const date = new Date(item.purchase_date);
    const dateStr = (date.getMonth() + 1).toString().padStart(2, '0') + '.' + 
                    date.getDate().toString().padStart(2, '0') + ' ' +
                    date.getHours().toString().padStart(2, '0') + ':' +
                    date.getMinutes().toString().padStart(2, '0');
    
    return `
        <div class="rb-history-item">
            <div class="rb-history-date">${dateStr}</div>
            
            <div class="rb-history-image">
                <img src="${itemImage}" alt="${item.rbi_name}">
            </div>
            
            <div class="rb-history-info">
                <div class="rb-history-box">${item.rb_name}</div>
                <div class="rb-history-result">
                    <span class="rb-history-grade ${getGradeClass(item.rbi_grade)}">
                        ${getGradeName(item.rbi_grade)}
                    </span>
                    <span class="rb-history-name">${item.rbi_name}</span>
                </div>
            </div>
            
            <div class="rb-history-points">
                <div class="rb-history-cost">-${number_format(item.rb_price)}P</div>
                ${item.rbi_value > 0 ? `<div class="rb-history-earned">+${number_format(item.rbi_value)}P</div>` : ''}
            </div>
        </div>
    `;
}

/**
 * 더보기
 */
function loadMoreHistory() {
    historyPage++;
    loadHistory();
}

// 필터 변경 이벤트
$('#historyFilterGrade, #historyFilterDays').on('change', function() {
    historyPage = 1;
    loadHistory();
});
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>