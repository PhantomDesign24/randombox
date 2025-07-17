<?php
/*
 * 파일명: get_box_detail.php
 * 위치: /randombox/ajax/
 * 기능: 박스 상세 정보 조회 (AJAX)
 * 작성일: 2025-07-17
 */

include_once('../../common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

// 박스 ID 확인
$rb_id = (int)$_GET['id'];
if (!$rb_id) {
    die('잘못된 접근입니다.');
}

// 박스 정보 조회
$box = get_randombox($rb_id);
if (!$box || !$box['rb_status']) {
    die('판매중인 상품이 아닙니다.');
}

// 박스 이미지
$box_img = '../img/box-default.png';
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

// 최근 당첨자
$sql = "SELECT h.*, m.mb_nick 
        FROM {$g5['g5_prefix']}randombox_history h
        LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id
        WHERE h.rb_id = '{$rb_id}' 
        AND h.rbi_grade IN ('rare', 'epic', 'legendary')
        ORDER BY h.rbh_created_at DESC 
        LIMIT 5";
$recent_result = sql_query($sql);
?>

<div class="rb-detail-container">
    <!-- 헤더 -->
    <div class="rb-detail-header">
        <button class="rb-modal-close" onclick="this.closest('.rb-modal').classList.remove('show')">
            <i class="bi bi-x-lg"></i>
        </button>
        
        <div class="rb-detail-type <?php echo $box['rb_type']; ?>">
            <?php echo strtoupper($box['rb_type']); ?> BOX
        </div>
    </div>
    
    <!-- 컨텐츠 -->
    <div class="rb-detail-content">
        <div class="rb-detail-grid">
            
            <!-- 왼쪽: 이미지 및 정보 -->
            <div class="rb-detail-left">
                <div class="rb-detail-image">
                    <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
                    
                    <?php if ($box['rb_type'] != 'normal') : ?>
                    <div class="rb-detail-badge <?php echo $box['rb_type']; ?>">
                        <i class="bi bi-star-fill"></i>
                        <?php echo get_box_type_name($box['rb_type']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="rb-detail-stats">
                    <div class="detail-stat">
                        <span class="stat-label">TOTAL SOLD</span>
                        <span class="stat-value"><?php echo number_format($stats['total_sold']); ?></span>
                    </div>
                    <div class="detail-stat">
                        <span class="stat-label">BUYERS</span>
                        <span class="stat-value"><?php echo number_format($stats['total_buyers']); ?></span>
                    </div>
                    <div class="detail-stat">
                        <span class="stat-label">ITEMS</span>
                        <span class="stat-value"><?php echo count($items); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- 오른쪽: 상세 정보 -->
            <div class="rb-detail-right">
                <h2 class="rb-detail-title"><?php echo $box['rb_name']; ?></h2>
                
                <?php if ($box['rb_desc']) : ?>
                <div class="rb-detail-desc">
                    <?php echo nl2br($box['rb_desc']); ?>
                </div>
                <?php endif; ?>
                
                <!-- 구매 정보 -->
                <div class="rb-detail-purchase">
                    <div class="purchase-price">
                        <span class="price-label">PRICE</span>
                        <div class="price-amount">
                            <span class="price-value"><?php echo number_format($box['rb_price']); ?></span>
                            <span class="price-unit">POINTS</span>
                        </div>
                    </div>
                    
                    <?php if ($box['rb_limit_qty'] > 0) : ?>
                    <div class="purchase-limit">
                        <i class="bi bi-info-circle"></i>
                        Daily purchase limit: <?php echo $box['rb_limit_qty']; ?> boxes
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($box['rb_total_qty'] > 0) : ?>
                    <div class="purchase-remaining">
                        <i class="bi bi-archive"></i>
                        Remaining: <?php echo number_format($box['rb_total_qty'] - $box['rb_sold_qty']); ?> / <?php echo number_format($box['rb_total_qty']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <button class="rb-purchase-btn <?php echo !$can_purchase['status'] ? 'disabled' : ''; ?>"
                            onclick="purchaseFromDetail(<?php echo $rb_id; ?>)"
                            <?php echo !$can_purchase['status'] ? 'disabled' : ''; ?>>
                        <?php if (!$can_purchase['status']) : ?>
                            <i class="bi bi-lock"></i>
                            <?php echo $can_purchase['message']; ?>
                        <?php else : ?>
                            <i class="bi bi-cart-plus"></i>
                            PURCHASE NOW
                        <?php endif; ?>
                    </button>
                </div>
                
                <!-- 아이템 목록 -->
                <?php if ($show_probability && $items) : ?>
                <div class="rb-detail-items">
                    <h3 class="items-title">
                        <i class="bi bi-box2"></i>
                        AVAILABLE ITEMS
                    </h3>
                    
                    <div class="items-grid">
                        <?php 
                        $total_probability = 0;
                        foreach ($items as $item) {
                            $total_probability += $item['rbi_probability'];
                        }
                        
                        foreach ($items as $item) : 
                            $item_img = '../img/item-default.png';
                            if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
                                $item_img = G5_DATA_URL.'/randombox/item/'.$item['rbi_image'];
                            }
                            
                            $probability = $total_probability > 0 ? ($item['rbi_probability'] / $total_probability * 100) : 0;
                        ?>
                        <div class="item-card grade-<?php echo $item['rbi_grade']; ?>">
                            <div class="item-image">
                                <img src="<?php echo $item_img; ?>" alt="<?php echo $item['rbi_name']; ?>">
                            </div>
                            <div class="item-info">
                                <div class="item-name"><?php echo $item['rbi_name']; ?></div>
                                <div class="item-grade"><?php echo strtoupper($item['rbi_grade']); ?></div>
                                <div class="item-prob"><?php echo number_format($probability, 2); ?>%</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 최근 당첨자 -->
                <?php if (sql_num_rows($recent_result) > 0) : ?>
                <div class="rb-detail-winners">
                    <h3 class="winners-title">
                        <i class="bi bi-trophy"></i>
                        RECENT WINNERS
                    </h3>
                    
                    <div class="winners-list">
                        <?php while ($winner = sql_fetch_array($recent_result)) : ?>
                        <div class="winner-item">
                            <div class="winner-grade grade-<?php echo $winner['rbi_grade']; ?>"></div>
                            <div class="winner-info">
                                <span class="winner-name"><?php echo mb_substr($winner['mb_nick'], 0, 1) . str_repeat('*', mb_strlen($winner['mb_nick']) - 1); ?></span>
                                <span class="winner-prize"><?php echo $winner['rbi_name']; ?></span>
                            </div>
                            <div class="winner-time"><?php echo date('m.d H:i', strtotime($winner['rbh_created_at'])); ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<style>
/* 박스 상세 스타일 */
.rb-detail-container {
    background: #fff;
}

.rb-detail-header {
    position: relative;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.rb-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: #f5f5f5;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.rb-modal-close:hover {
    background: #000;
    color: #fff;
}

.rb-detail-type {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #666;
}

.rb-detail-type.premium {
    color: #FFA500;
}

.rb-detail-type.event {
    color: #FF4757;
}

.rb-detail-content {
    padding: 0;
}

.rb-detail-grid {
    display: grid;
    grid-template-columns: 400px 1fr;
}

/* 왼쪽 섹션 */
.rb-detail-left {
    background: #f8f8f8;
    padding: 40px;
}

.rb-detail-image {
    position: relative;
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.rb-detail-image img {
    width: 100%;
    height: auto;
    max-height: 300px;
    object-fit: contain;
}

.rb-detail-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 8px 16px;
    background: #000;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.rb-detail-badge.premium {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000;
}

.rb-detail-badge.event {
    background: linear-gradient(135deg, #FF4757, #ff6348);
}

.rb-detail-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.detail-stat {
    text-align: center;
    padding: 16px;
    background: #fff;
    border-radius: 8px;
}

.stat-label {
    display: block;
    font-size: 10px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-value {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

/* 오른쪽 섹션 */
.rb-detail-right {
    padding: 40px;
}

.rb-detail-title {
    font-size: 28px;
    font-weight: 800;
    color: #000;
    margin: 0 0 16px;
}

.rb-detail-desc {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 32px;
}

/* 구매 정보 */
.rb-detail-purchase {
    background: #f8f8f8;
    padding: 24px;
    border-radius: 8px;
    margin-bottom: 40px;
}

.purchase-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.price-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.price-amount {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.price-value {
    font-size: 32px;
    font-weight: 800;
    color: #000;
}

.price-unit {
    font-size: 14px;
    color: #666;
}

.purchase-limit,
.purchase-remaining {
    font-size: 13px;
    color: #666;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.rb-purchase-btn {
    width: 100%;
    padding: 16px;
    background: #000;
    color: #fff;
    border: none;
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.rb-purchase-btn:hover:not(.disabled) {
    background: #333;
    transform: translateY(-2px);
}

.rb-purchase-btn.disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* 아이템 목록 */
.rb-detail-items {
    margin-bottom: 40px;
}

.items-title,
.winners-title {
    font-size: 16px;
    font-weight: 700;
    color: #000;
    margin: 0 0 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
}

.item-card {
    background: #f8f8f8;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    transition: all 0.2s;
}

.item-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.item-image {
    width: 60px;
    height: 60px;
    margin: 0 auto 8px;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.item-name {
    font-size: 11px;
    font-weight: 600;
    color: #000;
    margin-bottom: 4px;
}

.item-grade {
    font-size: 10px;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.item-card.grade-rare {
    background: #e3f2fd;
}

.item-card.grade-epic {
    background: #f3e5f5;
}

.item-card.grade-legendary {
    background: #ffebee;
}

.item-prob {
    font-size: 12px;
    font-weight: 700;
    color: #000;
}

/* 최근 당첨자 */
.winners-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.winner-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f8f8;
    border-radius: 6px;
}

.winner-grade {
    width: 4px;
    height: 30px;
    background: #e0e0e0;
    border-radius: 2px;
}

.winner-grade.grade-rare {
    background: #3498db;
}

.winner-grade.grade-epic {
    background: #9b59b6;
}

.winner-grade.grade-legendary {
    background: #e74c3c;
}

.winner-info {
    flex: 1;
}

.winner-name {
    font-size: 12px;
    font-weight: 600;
    color: #000;
    margin-right: 8px;
}

.winner-prize {
    font-size: 12px;
    color: #666;
}

.winner-time {
    font-size: 11px;
    color: #999;
}

/* 반응형 */
@media (max-width: 768px) {
    .rb-detail-grid {
        grid-template-columns: 1fr;
    }
    
    .rb-detail-left {
        padding: 20px;
    }
    
    .rb-detail-right {
        padding: 20px;
    }
    
    .items-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
// 상세 페이지에서 구매
function purchaseFromDetail(boxId) {
    // 모달 닫기
    document.getElementById('boxDetailModal').classList.remove('show');
    
    // 구매 모달 표시
    const box = document.querySelector(`[data-box-id="${boxId}"]`);
    if (box) {
        const boxName = box.querySelector('.box-name').textContent;
        const boxPrice = parseInt(box.querySelector('.price-value').textContent.replace(/,/g, ''));
        const boxImage = box.querySelector('.box-image img').src;
        
        showPurchaseModal(boxId, boxName, boxPrice, boxImage);
    }
}
</script>