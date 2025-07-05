<?php
/*
 * 파일명: box_detail.php
 * 위치: /randombox/
 * 기능: 랜덤박스 상세 정보 페이지
 * 작성일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// 파라미터 확인
// ===================================

$rb_id = (int)$_GET['rb_id'];

if (!$rb_id) {
    alert('박스를 선택해 주세요.', './');
}

// ===================================
// 박스 정보 조회
// ===================================

/* 박스 정보 */
$box = get_randombox($rb_id);
if (!$box || !$box['rb_status']) {
    alert('존재하지 않거나 판매 중지된 박스입니다.', './');
}

/* 박스 이미지 */
$box_img = './img/box-default.png';
if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
    $box_img = G5_DATA_URL.'/randombox/box/'.$box['rb_image'];
}

/* 구매 가능 여부 */
$can_purchase = check_randombox_purchase($rb_id, $member['mb_id']);

/* 아이템 목록 */
$items = get_randombox_items($rb_id, true);

/* 확률 공개 여부 */
$show_probability = get_randombox_config('show_probability');

/* 통계 정보 */
$sql = "SELECT 
        COUNT(*) as total_sold,
        COUNT(DISTINCT mb_id) as total_buyers
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE rb_id = '{$rb_id}'";
$stats = sql_fetch($sql);

/* 최근 당첨자 */
$sql = "SELECT h.*, m.mb_nick 
        FROM {$g5['g5_prefix']}randombox_history h 
        LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id 
        WHERE h.rb_id = '{$rb_id}' 
        AND h.rbi_grade IN ('rare', 'epic', 'legendary')
        ORDER BY h.rbh_created_at DESC 
        LIMIT 5";
$recent_result = sql_query($sql);

// ===================================
// 페이지 헤더
// ===================================

$g5['title'] = $box['rb_name'] . ' - 랜덤박스';
include_once(G5_PATH.'/head.php');
?>

<!-- 랜덤박스 CSS -->
<link rel="stylesheet" href="./style.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="./modal.css?v=<?php echo time(); ?>">

<style>
/* ===================================
 * 박스 상세 페이지 전용 스타일
 * =================================== */

.box-detail-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

/* 박스 정보 섹션 */
.box-info-section {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.box-info-grid {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 40px;
    padding: 40px;
}

.box-image-area {
    text-align: center;
}

.box-main-image {
    width: 100%;
    max-width: 350px;
    height: 350px;
    object-fit: contain;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.box-type-badge {
    display: inline-block;
    padding: 6px 16px;
    margin-top: 16px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 20px;
}

.type-normal { background: #e0e0e0; color: #666; }
.type-event { background: #FF4757; color: #fff; }
.type-premium { background: #FFD700; color: #333; }

.box-detail-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.box-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 16px;
}

.box-description {
    font-size: 16px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 24px;
}

.box-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.stat-item {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    text-align: center;
}

.stat-label {
    font-size: 13px;
    color: #999;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
}

.purchase-area {
    display: flex;
    align-items: center;
    gap: 24px;
}

.price-display {
    font-size: 36px;
    font-weight: 700;
    color: #1a1a1a;
}

.price-display small {
    font-size: 20px;
    color: #666;
}

.btn-buy {
    flex: 1;
    padding: 16px 32px;
    background: #1a1a1a;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-buy:hover:not(:disabled) {
    background: #000;
    transform: translateY(-2px);
}

.btn-buy:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* 아이템 목록 섹션 */
.items-section {
    margin-bottom: 40px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.item-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
}

.item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.item-image {
    width: 100px;
    height: 100px;
    object-fit: contain;
    margin: 0 auto 12px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
}

.item-name {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.item-grade {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.item-probability {
    font-size: 16px;
    font-weight: 700;
    color: #3498db;
}

/* 최근 당첨자 섹션 */
.recent-winners-section {
    background: #f8f9fa;
    padding: 32px;
    border-radius: 12px;
}

.winners-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.winner-row {
    background: #fff;
    padding: 16px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.winner-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.winner-name {
    font-weight: 600;
    color: #333;
}

.winner-time {
    font-size: 13px;
    color: #999;
}

/* 반응형 */
@media (max-width: 768px) {
    .box-info-grid {
        grid-template-columns: 1fr;
        gap: 24px;
        padding: 20px;
    }
    
    .box-title {
        font-size: 24px;
    }
    
    .purchase-area {
        flex-direction: column;
        gap: 16px;
    }
    
    .price-display {
        font-size: 28px;
    }
    
    .items-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}
</style>

<div class="box-detail-container">
    
    <!-- 박스 정보 섹션 -->
    <div class="box-info-section">
        <div class="box-info-grid">
            <div class="box-image-area">
                <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>" class="box-main-image">
                <div class="box-type-badge type-<?php echo $box['rb_type']; ?>">
                    <?php echo get_box_type_name($box['rb_type']); ?>
                </div>
            </div>
            
            <div class="box-detail-info">
                <h1 class="box-title"><?php echo $box['rb_name']; ?></h1>
                
                <?php if ($box['rb_desc']) : ?>
                <div class="box-description">
                    <?php echo nl2br($box['rb_desc']); ?>
                </div>
                <?php endif; ?>
                
                <div class="box-stats">
                    <div class="stat-item">
                        <div class="stat-label">총 판매</div>
                        <div class="stat-value"><?php echo number_format($stats['total_sold']); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">구매자</div>
                        <div class="stat-value"><?php echo number_format($stats['total_buyers']); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">아이템 종류</div>
                        <div class="stat-value"><?php echo count($items); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">일일 제한</div>
                        <div class="stat-value"><?php echo $box['rb_limit_qty'] > 0 ? $box['rb_limit_qty'] . '개' : '무제한'; ?></div>
                    </div>
                </div>
                
                <div class="purchase-area">
                    <div class="price-display">
                        <?php echo number_format($box['rb_price']); ?><small>P</small>
                    </div>
                    
                    <?php if ($can_purchase['status']) : ?>
                    <button type="button" class="btn-buy btn-purchase" data-box-id="<?php echo $rb_id; ?>">
                        구매하기
                    </button>
                    <?php else : ?>
                    <button type="button" class="btn-buy" disabled>
                        <?php echo $can_purchase['msg']; ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 아이템 목록 섹션 -->
    <div class="items-section">
        <div class="section-header">
            <h2 class="section-title">획득 가능 아이템</h2>
            <?php if ($show_probability) : ?>
            <span style="font-size: 14px; color: #999;">확률 공개</span>
            <?php endif; ?>
        </div>
        
        <div class="items-grid">
            <?php foreach ($items as $item) : 
                $item_img = './img/item-default.png';
                if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
                    $item_img = G5_DATA_URL.'/randombox/item/'.$item['rbi_image'];
                }
            ?>
            <div class="item-card">
                <img src="<?php echo $item_img; ?>" alt="<?php echo $item['rbi_name']; ?>" class="item-image">
                <div class="item-name"><?php echo $item['rbi_name']; ?></div>
                <div class="item-grade grade-<?php echo $item['rbi_grade']; ?>">
                    <?php echo get_grade_name($item['rbi_grade']); ?>
                </div>
                <?php if ($show_probability) : ?>
                <div class="item-probability"><?php echo number_format($item['rbi_probability'], 2); ?>%</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- 최근 당첨자 섹션 -->
    <?php if (sql_num_rows($recent_result) > 0) : ?>
    <div class="recent-winners-section">
        <h2 class="section-title" style="margin-bottom: 20px;">최근 레어 이상 당첨자</h2>
        
        <div class="winners-list">
            <?php while ($winner = sql_fetch_array($recent_result)) : 
                $display_name = $winner['mb_nick'] ? 
                    mb_substr($winner['mb_nick'], 0, 1) . str_repeat('*', mb_strlen($winner['mb_nick']) - 1) : 
                    mb_substr($winner['mb_id'], 0, 3) . '***';
            ?>
            <div class="winner-row">
                <div class="winner-info">
                    <span class="winner-name"><?php echo $display_name; ?></span>
                    <span class="item-grade grade-<?php echo $winner['rbi_grade']; ?>">
                        <?php echo get_grade_name($winner['rbi_grade']); ?>
                    </span>
                    <span><?php echo $winner['rbi_name']; ?></span>
                </div>
                <span class="winner-time"><?php echo date('Y-m-d H:i', strtotime($winner['rbh_created_at'])); ?></span>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 하단 버튼 -->
    <div style="text-align: center; margin-top: 40px;">
        <a href="./" class="btn btn-secondary" style="padding: 12px 32px; font-size: 16px; text-decoration: none; display: inline-block; background: #95a5a6; color: #fff; border-radius: 8px;">
            목록으로
        </a>
    </div>
    
</div>

<!-- 구매 모달 -->
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
// 박스 상세 페이지용 데이터
var boxDetailData = {
    id: <?php echo $rb_id; ?>,
    name: '<?php echo addslashes($box['rb_name']); ?>',
    price: <?php echo $box['rb_price']; ?>,
    image: '<?php echo $box_img; ?>'
};

// 구매 버튼 클릭 이벤트 오버라이드
$(document).ready(function() {
    $('.btn-purchase').on('click', function() {
        var boxId = $(this).data('box-id');
        
        // 모달에 정보 표시
        $('#modalBoxName').text(boxDetailData.name);
        $('#modalBoxPrice').text(number_format(boxDetailData.price) + 'P');
        $('#modalBoxImage').attr('src', boxDetailData.image).attr('alt', boxDetailData.name);
        
        // 구매 후 포인트 계산
        var currentPoint = <?php echo $member['mb_point']; ?>;
        var afterPoint = currentPoint - boxDetailData.price;
        
        $('#modalAfterPoint').text(number_format(afterPoint) + 'P');
        
        // 포인트 부족 시 버튼 비활성화
        if (afterPoint < 0) {
            $('#modalAfterPoint').css('color', '#e74c3c');
            $('#confirmPurchase').prop('disabled', true).text('포인트 부족');
        } else {
            $('#modalAfterPoint').css('color', '#27ae60');
            $('#confirmPurchase').prop('disabled', false).text('구매하기');
        }
        
        // 전역 변수 설정
        currentBoxId = boxId;
        currentBoxData = boxDetailData;
        
        // 모달 표시
        $('#purchaseModal').addClass('active');
    });
});
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>