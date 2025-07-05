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

<div class="randombox-container">
    
    <!-- 상단 정보 바 -->
    <div class="rb-top-bar">
        <div class="rb-logo">RANDOM BOX</div>
        
        <div class="rb-user-info">
            <div class="point-display">
                <span class="point-label">보유 포인트</span>
                <span class="point-value"><?php echo number_format($member['mb_point']); ?>P</span>
            </div>
            
            <div class="user-menu">
                <a href="./history.php">구매내역</a>
                <?php if (get_randombox_config('enable_gift')) : ?>
                <a href="./gift.php">선물함</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 메인 그리드 -->
    <div class="main-grid">
        
        <!-- 사이드바 통계 -->
        <aside class="sidebar-stats">
            <div class="stat-block">
                <h4>오늘 판매</h4>
                <div class="stat-number"><?php echo number_format($today_sales['cnt']); ?></div>
                <div class="stat-sub">전체 거래</div>
            </div>
            
            <div class="stat-block">
                <h4>내 구매</h4>
                <div class="stat-number"><?php echo number_format($my_stats['total_count']); ?></div>
                <div class="stat-sub">총 <?php echo number_format($my_stats['rare_count']); ?>개 희귀</div>
            </div>
            
            <div class="stat-block">
                <h4>활성 사용자</h4>
                <div class="stat-number"><?php echo number_format($active_users['user_count']); ?></div>
                <div class="stat-sub">최근 30일</div>
            </div>
            
            <div class="stat-block">
                <h4>판매 박스</h4>
                <div class="stat-number"><?php echo count($box_list); ?></div>
                <div class="stat-sub">종류</div>
            </div>
        </aside>
        
        <!-- 박스 섹션 -->
        <main class="box-section">
            <div class="section-header">
                <h1 class="section-title">판매중인 박스</h1>
                
                <div class="view-options">
                    <button class="view-btn active" data-filter="all">전체</button>
                    <button class="view-btn" data-filter="normal">일반</button>
                    <button class="view-btn" data-filter="event">이벤트</button>
                    <button class="view-btn" data-filter="premium">프리미엄</button>
                </div>
            </div>
            
            <?php if (!$box_list) : ?>
            <div class="empty-message">
                <p>현재 판매 중인 박스가 없습니다</p>
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
                    
                    // 남은 수량
                    $remaining = null;
                    if ($box['rb_total_qty'] > 0) {
                        $remaining = $box['rb_total_qty'] - $box['rb_sold_qty'];
                        $remaining_percent = round(($remaining / $box['rb_total_qty']) * 100);
                    }
                ?>
                
                <div class="rb-box-card type-<?php echo $box['rb_type']; ?>" 
                     data-box-id="<?php echo $box['rb_id']; ?>" 
                     data-type="<?php echo $box['rb_type']; ?>">
                    
                    <div class="box-type-indicator"></div>
                    
                    <div class="box-image">
                        <?php if ($box['rb_type'] != 'normal') : ?>
                        <span class="box-label <?php echo $box['rb_type']; ?>">
                            <?php echo get_box_type_name($box['rb_type']); ?>
                        </span>
                        <?php endif; ?>
                        <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
                    </div>
                    
                    <div class="box-info">
                        <h3 class="box-name"><?php echo $box['rb_name']; ?></h3>
                        
                        <div class="box-meta">
                            <span class="meta-item">
                                <?php echo $item_count['cnt']; ?>종 아이템
                            </span>
                            
                            <?php if ($box['rb_limit_qty'] > 0) : ?>
                            <span class="meta-item">
                                일일 <?php echo $box['rb_limit_qty']; ?>개
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($remaining !== null && $remaining_percent < 30) : ?>
                            <span class="meta-item" style="color: #FF4757;">
                                <?php echo $remaining; ?>개 남음
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="box-footer">
                        <div class="box-price">
                            <?php echo number_format($box['rb_price']); ?><small>P</small>
                        </div>
                        
                        <?php if ($can_purchase['status']) : ?>
                        <button type="button" class="btn-purchase" data-box-id="<?php echo $box['rb_id']; ?>">
                            구매
                        </button>
                        <?php else : ?>
                        <button type="button" class="btn-purchase" disabled>
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
    <div class="realtime-section">
        <div class="realtime-header">
            <h2>실시간 당첨</h2>
            <span style="font-size: 13px; color: #999;">최근 10건</span>
        </div>
        
        <div class="realtime-ticker">
            <?php foreach ($recent_winners as $winner) : ?>
            <div class="winner-item">
                <div class="winner-avatar">
                    <?php echo mb_substr($winner['display_name'], 0, 1); ?>
                </div>
                <div class="winner-info">
                    <span class="winner-name"><?php echo $winner['display_name']; ?></span>
                    <span class="winner-item-grade grade-<?php echo $winner['rbi_grade']; ?>">
                        <?php echo get_grade_name($winner['rbi_grade']); ?>
                    </span>
                    <span class="winner-item-name"><?php echo $winner['rbi_name']; ?></span>
                    <span class="winner-time"><?php echo date('H:i', strtotime($winner['rbh_created_at'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<!-- 구매 확인 모달 -->
<div id="purchaseModal" class="rb-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>구매 확인</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="purchase-info">
                <div class="box-preview">
                    <img src="" alt="" id="modalBoxImage">
                </div>
                <div class="purchase-details">
                    <h4 id="modalBoxName"></h4>
                    <div class="price-info">
                        <div class="price-row">
                            <span>구매 가격</span>
                            <span id="modalBoxPrice"></span>
                        </div>
                        <div class="price-row">
                            <span>보유 포인트</span>
                            <span><?php echo number_format($member['mb_point']); ?>P</span>
                        </div>
                        <div class="price-row total">
                            <span>구매 후 잔액</span>
                            <span id="modalAfterPoint"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary modal-close">취소</button>
            <button type="button" class="btn-primary" id="confirmPurchase">구매</button>
        </div>
    </div>
</div>

<!-- 결과 모달 -->
<div id="resultModal" class="rb-modal">
    <div class="modal-content modal-result">
        <div class="result-animation">
            <div class="box-opening">
                <img src="./img/box-opening.gif" alt="Opening...">
            </div>
            <div class="result-item" style="display:none;">
                <div class="item-grade-effect"></div>
                <img src="" alt="" id="resultItemImage">
                <h3 id="resultItemName"></h3>
                <div class="item-grade" id="resultItemGrade"></div>
                <div class="item-value" id="resultItemValue"></div>
            </div>
        </div>
        <div class="modal-footer" style="display:none;">
            <button type="button" class="btn-primary modal-close">확인</button>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="./randombox.js?v=<?php echo time(); ?>"></script>
<script>
// 필터 기능
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // 활성 버튼 변경
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // 박스 필터링
        const filter = this.dataset.filter;
        document.querySelectorAll('.rb-box-card').forEach(card => {
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