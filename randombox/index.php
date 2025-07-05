<?php
/*
 * 파일명: index.php
 * 위치: /randombox/
 * 기능: 랜덤박스 메인 페이지 - 모던 미니멀 디자인
 * 작성일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// 데이터 조회
// ===================================

/* 활성화된 박스 목록 */
$box_list = get_randombox_list();

/* 실시간 당첨 현황 */
$recent_winners = array();
if (get_randombox_config('enable_realtime')) {
    $recent_winners = get_recent_winners(10);
}

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
        MAX(rbh_created_at) as last_purchase
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}'";
$my_stats = sql_fetch($sql);

// 전체 사용자 수
$sql = "SELECT COUNT(DISTINCT mb_id) as user_count 
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE rbh_created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$active_users = sql_fetch($sql);

// ===================================
// 페이지 헤더
// ===================================

include_once(G5_PATH.'/head.php');
?>

<!-- 랜덤박스 CSS -->
<link rel="stylesheet" href="./style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="./modal.css?v=<?php echo time(); ?>">

<div class="rb-main-container">
    
    <!-- 상단 정보 바 -->
    <div class="rb-header-bar">
        <div class="rb-site-logo">RANDOM BOX</div>
        
        <div class="rb-user-section">
            <div class="rb-point-box">
                <span class="rb-point-text">보유 포인트</span>
                <span class="rb-point-amount"><?php echo number_format($member['mb_point']); ?>P</span>
            </div>
            
            <div class="rb-nav-menu">
                <a href="./history.php">구매내역</a>
                <?php if (get_randombox_config('enable_gift')) : ?>
                <a href="./gift.php">선물함</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 메인 그리드 -->
    <div class="rb-content-grid">
        
        <!-- 사이드바 통계 -->
        <aside class="rb-sidebar-area">
            <div class="rb-stat-widget">
                <h4>오늘 판매</h4>
                <div class="rb-stat-num"><?php echo number_format($today_sales['cnt']); ?></div>
                <div class="rb-stat-desc">전체 거래</div>
            </div>
            
            <div class="rb-stat-widget">
                <h4>내 구매</h4>
                <div class="rb-stat-num"><?php echo number_format($my_stats['total_count']); ?></div>
                <div class="rb-stat-desc">총 <?php echo number_format($my_stats['rare_count']); ?>개 희귀</div>
            </div>
            
            <div class="rb-stat-widget">
                <h4>활성 사용자</h4>
                <div class="rb-stat-num"><?php echo number_format($active_users['user_count']); ?></div>
                <div class="rb-stat-desc">최근 30일</div>
            </div>
            
            <div class="rb-stat-widget">
                <h4>판매 박스</h4>
                <div class="rb-stat-num"><?php echo count($box_list); ?></div>
                <div class="rb-stat-desc">종류</div>
            </div>
        </aside>
        
        <!-- 박스 섹션 -->
        <main class="rb-main-section">
            <div class="rb-section-top">
                <h1 class="rb-section-heading">판매중인 박스</h1>
                
                <div class="rb-filter-tabs">
                    <button class="rb-filter-btn rb-active" data-filter="all">전체</button>
                    <button class="rb-filter-btn" data-filter="normal">일반</button>
                    <button class="rb-filter-btn" data-filter="event">이벤트</button>
                    <button class="rb-filter-btn" data-filter="premium">프리미엄</button>
                </div>
            </div>
            
            <?php if (!$box_list) : ?>
            <div class="rb-empty-state">
                <p>현재 판매 중인 박스가 없습니다</p>
            </div>
            <?php else : ?>
            
            <div class="rb-box-list">
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
                    
                    // 남은 수량
                    $remaining = null;
                    if ($box['rb_total_qty'] > 0) {
                        $remaining = $box['rb_total_qty'] - $box['rb_sold_qty'];
                        $remaining_percent = round(($remaining / $box['rb_total_qty']) * 100);
                    }
                ?>
                
                <div class="rb-box-item rb-type-<?php echo $box['rb_type']; ?>" 
                     data-box-id="<?php echo $box['rb_id']; ?>" 
                     data-type="<?php echo $box['rb_type']; ?>">
                    
                    <div class="rb-type-bar"></div>
                    
                    <div class="rb-box-visual">
                        <?php if ($box['rb_type'] != 'normal') : ?>
                        <span class="rb-box-badge rb-<?php echo $box['rb_type']; ?>">
                            <?php echo get_box_type_name($box['rb_type']); ?>
                        </span>
                        <?php endif; ?>
                        <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
                    </div>
                    
                    <div class="rb-box-detail">
                        <h3 class="rb-box-title"><?php echo $box['rb_name']; ?></h3>
                        
                        <div class="rb-box-specs">
                            <span class="rb-spec-item">
                                <?php echo $item_count['cnt']; ?>종 아이템
                            </span>
                            
                            <?php if ($box['rb_limit_qty'] > 0) : ?>
                            <span class="rb-spec-item">
                                일일 <?php echo $box['rb_limit_qty']; ?>개
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($remaining !== null && $remaining_percent < 30) : ?>
                            <span class="rb-spec-item" style="color: #FF4757;">
                                <?php echo $remaining; ?>개 남음
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="rb-box-bottom">
                        <div class="rb-price-tag">
                            <?php echo number_format($box['rb_price']); ?><small>P</small>
                        </div>
                        
                        <?php if ($can_purchase['status']) : ?>
                        <button type="button" class="rb-buy-btn rb-purchase-trigger" data-box-id="<?php echo $box['rb_id']; ?>">
                            구매
                        </button>
                        <?php else : ?>
                        <button type="button" class="rb-buy-btn" disabled>
                            불가
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    
    <!-- 실시간 당첨 현황 -->
    <?php if (get_randombox_config('enable_realtime') && $recent_winners) : ?>
    <div class="rb-live-section">
        <div class="rb-live-header">
            <h2>실시간 당첨</h2>
            <span style="font-size: 13px; color: #999;">최근 10건</span>
        </div>
        
        <div class="rb-live-feed">
            <?php foreach ($recent_winners as $winner) : ?>
            <div class="rb-winner-card">
                <div class="rb-winner-icon">
                    <?php echo mb_substr($winner['display_name'], 0, 1); ?>
                </div>
                <div class="rb-winner-data">
                    <span class="rb-winner-id"><?php echo $winner['display_name']; ?></span>
                    <span class="rb-item-tier rb-tier-<?php echo $winner['rbi_grade']; ?>">
                        <?php echo get_grade_name($winner['rbi_grade']); ?>
                    </span>
                    <span class="rb-item-title"><?php echo $winner['rbi_name']; ?></span>
                    <span class="rb-win-time"><?php echo date('H:i', strtotime($winner['rbh_created_at'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<!-- 구매 확인 모달 -->
<div id="purchaseModal" class="rb-modal-overlay">
    <div class="rb-modal-box">
        <div class="rb-modal-head">
            <h3 class="rb-modal-title">구매 확인</h3>
            <button type="button" class="rb-modal-close-btn rb-close-modal">&times;</button>
        </div>
        <div class="rb-modal-body">
            <div class="rb-purchase-wrap">
                <div class="rb-box-thumb">
                    <img src="" alt="" id="modalBoxImage">
                </div>
                <div class="rb-purchase-info">
                    <h4 class="rb-purchase-name" id="modalBoxName"></h4>
                    <div class="rb-price-table">
                        <div class="rb-price-line">
                            <span class="rb-price-label">구매 가격</span>
                            <span class="rb-price-value" id="modalBoxPrice"></span>
                        </div>
                        <div class="rb-price-line">
                            <span class="rb-price-label">보유 포인트</span>
                            <span class="rb-price-value" id="modalCurrentPoint"><?php echo number_format($member['mb_point']); ?>P</span>
                        </div>
                        <div class="rb-price-line rb-total">
                            <span class="rb-price-label">구매 후 잔액</span>
                            <span class="rb-price-value" id="modalAfterPoint"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="rb-modal-foot">
            <button type="button" class="rb-btn-cancel rb-close-modal">취소</button>
            <button type="button" class="rb-btn-confirm" id="confirmPurchase">구매하기</button>
        </div>
    </div>
</div>

<!-- 결과 모달 -->
<div id="resultModal" class="rb-modal-overlay">
    <div class="rb-modal-box rb-result-modal">
        <div class="rb-modal-head">
            <h3 class="rb-modal-title">박스 오픈 중...</h3>
            <button type="button" class="rb-modal-close-btn rb-close-modal">&times;</button>
        </div>
        <div class="rb-modal-body">
            <div class="rb-result-stage">
                <div class="rb-box-opening">
                    <img src="./img/box-opening.gif" alt="Opening...">
                </div>
                <div class="rb-result-display" style="display:none;">
                    <div class="rb-grade-effect"></div>
                    <img src="" alt="" class="rb-result-img" id="resultItemImage">
                    <h3 class="rb-result-name" id="resultItemName"></h3>
                    <div class="rb-result-grade" id="resultItemGrade"></div>
                    <div class="rb-result-point" id="resultItemValue"></div>
                </div>
            </div>
        </div>
        <div class="rb-modal-foot" style="display:none;">
            <button type="button" class="rb-btn-confirm rb-close-modal">확인</button>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="./randombox.js?v=<?php echo time(); ?>"></script>
<script>
// 필터 기능
document.querySelectorAll('.rb-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // 활성 버튼 변경
        document.querySelectorAll('.rb-filter-btn').forEach(b => b.classList.remove('rb-active'));
        this.classList.add('rb-active');
        
        // 박스 필터링
        const filter = this.dataset.filter;
        document.querySelectorAll('.rb-box-item').forEach(card => {
            if (filter === 'all' || card.dataset.type === filter) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>