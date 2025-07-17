<?php
/*
 * 파일명: box_detail.php
 * 위치: /randombox/
 * 기능: 랜덤박스 상세 정보 페이지
 * 작성일: 2025-07-17
 */

include_once('./_common.php');

// 박스 ID 확인
$rb_id = (int)$_GET['id'];
if (!$rb_id) {
    alert('잘못된 접근입니다.', './');
}

// 박스 정보 조회
$box = get_randombox($rb_id);
if (!$box || !$box['rb_status']) {
    alert('판매중인 상품이 아닙니다.', './');
}

// 페이지 설정
$page_name = 'box_detail';
$page_title = $box['rb_name'];
$page_css = 'box_detail';

// 박스 이미지
$box_img = './img/box-default.png';
if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
    $box_img = G5_DATA_URL.'/randombox/box/'.$box['rb_image'];
}

// 구매 가능 여부
$can_purchase = check_randombox_purchase($rb_id, $member['mb_id']);

// 아이템 목록
$items = get_randombox_items($rb_id, true);

// 확률 공개 설정
$show_probability = get_randombox_config('show_probability');

// 통계 정보
$sql = "SELECT 
        COUNT(*) as total_sold,
        COUNT(DISTINCT mb_id) as total_buyers
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE rb_id = '{$rb_id}'";
$stats = sql_fetch($sql);

// 최근 당첨자 (레어 이상)
$sql = "SELECT h.*, m.mb_nick 
        FROM {$g5['g5_prefix']}randombox_history h 
        LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id 
        WHERE h.rb_id = '{$rb_id}' 
        AND h.rbi_grade IN ('rare', 'epic', 'legendary')
        ORDER BY h.rbh_created_at DESC 
        LIMIT 10";
$winners_result = sql_query($sql);

// 헤더 포함
include_once('./randombox_head.php');
?>

<div class="rb-detail-container">
    
    <!-- ===================================
     * 박스 정보 섹션
     * =================================== -->
    <section class="rb-detail-header">
        <div class="rb-detail-image">
            <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
            <?php if ($box['rb_type'] != 'normal') : ?>
            <span class="rb-box-badge <?php echo $box['rb_type']; ?>">
                <?php echo get_box_type_name($box['rb_type']); ?>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="rb-detail-info">
            <h1 class="rb-detail-title"><?php echo $box['rb_name']; ?></h1>
            
            <?php if ($box['rb_desc']) : ?>
            <div class="rb-detail-desc">
                <?php echo nl2br($box['rb_desc']); ?>
            </div>
            <?php endif; ?>
            
            <div class="rb-detail-meta">
                <div class="rb-meta-item">
                    <i class="bi bi-box2"></i>
                    <span>아이템 <?php echo count($items); ?>종</span>
                </div>
                
                <?php if ($box['rb_limit_qty'] > 0) : ?>
                <div class="rb-meta-item">
                    <i class="bi bi-calendar-day"></i>
                    <span>일일 구매제한 <?php echo $box['rb_limit_qty']; ?>개</span>
                </div>
                <?php endif; ?>
                
                <?php if ($box['rb_total_qty'] > 0) : ?>
                <div class="rb-meta-item">
                    <i class="bi bi-archive"></i>
                    <span>남은 수량 <?php echo number_format($box['rb_total_qty'] - $box['rb_sold_qty']); ?>개</span>
                </div>
                <?php endif; ?>
                
                <div class="rb-meta-item">
                    <i class="bi bi-people"></i>
                    <span>구매자 <?php echo number_format($stats['total_buyers']); ?>명</span>
                </div>
            </div>
            
            <div class="rb-detail-price">
                <span class="price-label">판매가격</span>
                <span class="price-value"><?php echo number_format($box['rb_price']); ?></span>
                <span class="price-unit">P</span>
            </div>
            
            <div class="rb-detail-actions">
                <?php if ($can_purchase['status']) : ?>
                    <button type="button" class="rb-btn rb-btn-primary rb-btn-lg rb-buy-btn"
                            data-box-id="<?php echo $box['rb_id']; ?>"
                            data-box-name="<?php echo $box['rb_name']; ?>"
                            data-box-price="<?php echo $box['rb_price']; ?>"
                            data-box-image="<?php echo $box_img; ?>">
                        <i class="bi bi-cart-plus"></i>
                        구매하기
                    </button>
                    
                    <?php if (get_randombox_config('enable_gift')) : ?>
                    <button type="button" class="rb-btn rb-btn-secondary rb-btn-lg">
                        <i class="bi bi-gift"></i>
                        선물하기
                    </button>
                    <?php endif; ?>
                <?php else : ?>
                    <button type="button" class="rb-btn rb-btn-primary rb-btn-lg" disabled>
                        <?php echo $can_purchase['msg']; ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if ($box['rb_distribution_type'] == 'guaranteed' && $box['rb_show_guaranteed_count']) : ?>
            <div class="rb-guaranteed-info">
                <i class="bi bi-info-circle"></i>
                <?php
                // 남은 특별 아이템 개수 계산 (예: 교환권)
                $sql = "SELECT COUNT(*) as cnt 
                        FROM {$g5['g5_prefix']}randombox_guaranteed g
                        INNER JOIN {$g5['g5_prefix']}randombox_items i ON g.rbg_item_id = i.rbi_id
                        WHERE g.rb_id = '{$rb_id}' 
                        AND g.rbg_status = 'available'
                        AND i.rbi_item_type = 'coupon'";
                $special = sql_fetch($sql);
                ?>
                남은 특별 아이템: <strong><?php echo $special['cnt']; ?>개</strong>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- ===================================
     * 아이템 목록 섹션
     * =================================== -->
    <section class="rb-items-section">
        <div class="rb-section-header">
            <h2 class="rb-section-title">
                <i class="bi bi-collection"></i>
                획득 가능 아이템
            </h2>
            <?php if ($show_probability) : ?>
            <span class="rb-probability-badge">
                <i class="bi bi-percent"></i>
                확률 공개
            </span>
            <?php endif; ?>
        </div>
        
        <div class="rb-items-grid">
            <?php foreach ($items as $item) : 
                // 아이템 이미지
                $item_img = './img/item-default.png';
                if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
                    $item_img = G5_DATA_URL.'/randombox/item/'.$item['rbi_image'];
                }
                
                // 교환권인 경우 정보 추가
                $coupon_info = null;
                if ($item['rbi_item_type'] == 'coupon' && $item['rct_id']) {
                    $coupon_info = get_coupon_type($item['rct_id']);
                }
            ?>
            
            <div class="rb-item-card <?php echo 'grade-'.$item['rbi_grade']; ?>">
                <div class="rb-item-image">
                    <img src="<?php echo $item_img; ?>" alt="<?php echo $item['rbi_name']; ?>">
                    <div class="rb-item-grade">
                        <?php echo get_grade_name($item['rbi_grade']); ?>
                    </div>
                </div>
                
                <div class="rb-item-info">
                    <h3 class="rb-item-name"><?php echo $item['rbi_name']; ?></h3>
                    
                    <?php if ($item['rbi_desc']) : ?>
                    <p class="rb-item-desc"><?php echo $item['rbi_desc']; ?></p>
                    <?php endif; ?>
                    
                    <div class="rb-item-details">
                        <?php if ($item['rbi_item_type'] == 'point') : ?>
                            <?php if ($item['rbi_point_random']) : ?>
                                <div class="rb-item-value">
                                    <i class="bi bi-shuffle"></i>
                                    <?php echo number_format($item['rbi_point_min']); ?>~<?php echo number_format($item['rbi_point_max']); ?>P
                                </div>
                            <?php elseif ($item['rbi_value'] > 0) : ?>
                                <div class="rb-item-value">
                                    <i class="bi bi-coin"></i>
                                    <?php echo number_format($item['rbi_value']); ?>P
                                </div>
                            <?php endif; ?>
                        <?php elseif ($item['rbi_item_type'] == 'coupon' && $coupon_info) : ?>
                            <div class="rb-item-value">
                                <i class="bi bi-ticket-perforated"></i>
                                <?php echo $coupon_info['rct_exchange_item']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($show_probability) : ?>
                        <div class="rb-item-probability">
                            <span class="prob-label">확률</span>
                            <span class="prob-value"><?php echo number_format($item['rbi_probability'], 2); ?>%</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($item['rbi_limit_qty'] > 0) : ?>
                        <div class="rb-item-limit">
                            <i class="bi bi-box-seam"></i>
                            한정 <?php echo number_format($item['rbi_limit_qty']); ?>개
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- ===================================
     * 최근 당첨자 섹션
     * =================================== -->
    <?php if (sql_num_rows($winners_result) > 0) : ?>
    <section class="rb-winners-section">
        <div class="rb-section-header">
            <h2 class="rb-section-title">
                <i class="bi bi-trophy"></i>
                최근 레어 아이템 당첨자
            </h2>
        </div>
        
        <div class="rb-winners-list">
            <?php while ($winner = sql_fetch_array($winners_result)) : 
                $display_name = $winner['mb_nick'] ? 
                    mb_substr($winner['mb_nick'], 0, 1) . str_repeat('*', mb_strlen($winner['mb_nick']) - 1) : 
                    mb_substr($winner['mb_id'], 0, 3) . '****';
                
                $time_diff = time() - strtotime($winner['rbh_created_at']);
                if ($time_diff < 3600) {
                    $time_ago = floor($time_diff / 60) . '분 전';
                } elseif ($time_diff < 86400) {
                    $time_ago = floor($time_diff / 3600) . '시간 전';
                } else {
                    $time_ago = floor($time_diff / 86400) . '일 전';
                }
            ?>
            
            <div class="rb-winner-item">
                <div class="rb-winner-avatar">
                    <?php echo mb_substr($display_name, 0, 1); ?>
                </div>
                <div class="rb-winner-info">
                    <div class="rb-winner-name"><?php echo $display_name; ?></div>
                    <div class="rb-winner-prize">
                        <span class="rb-winner-grade <?php echo 'grade-'.$winner['rbi_grade']; ?>">
                            <?php echo get_grade_name($winner['rbi_grade']); ?>
                        </span>
                        <?php echo $winner['rbi_name']; ?>
                    </div>
                </div>
                <div class="rb-winner-time"><?php echo $time_ago; ?></div>
            </div>
            
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>
    
</div>

<!-- 구매 모달 포함 -->
<?php include_once('./modals.php'); ?>

<script>
// 구매 버튼 클릭
$('.rb-buy-btn').on('click', function() {
    const boxId = $(this).data('box-id');
    const boxName = $(this).data('box-name');
    const boxPrice = $(this).data('box-price');
    const boxImage = $(this).data('box-image');
    
    showPurchaseModal(boxId, boxName, boxPrice, boxImage);
});
</script>

<?php
include_once('./randombox_tail.php');
?>