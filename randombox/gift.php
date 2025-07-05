<?php
/*
 * 파일명: gift.php
 * 위치: /randombox/
 * 기능: 랜덤박스 선물하기 페이지
 * 작성일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// 선물하기 기능 확인
// ===================================

if (!get_randombox_config('enable_gift')) {
    alert('선물하기 기능이 비활성화 상태입니다.', './');
}

// ===================================
// 탭 설정
// ===================================

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'send';
if (!in_array($tab, array('send', 'received', 'sent'))) {
    $tab = 'send';
}

// ===================================
// 데이터 조회
// ===================================

if ($tab == 'send') {
    // 선물 가능한 박스 목록
    $box_list = get_randombox_list();
    
} else if ($tab == 'received') {
    // 받은 선물 목록
    $sql = "SELECT g.*, b.rb_name, b.rb_image, b.rb_price, m.mb_nick as sender_nick
            FROM {$g5['g5_prefix']}randombox_gift g
            LEFT JOIN {$g5['g5_prefix']}randombox b ON g.rb_id = b.rb_id
            LEFT JOIN {$g5['member_table']} m ON g.send_mb_id = m.mb_id
            WHERE g.recv_mb_id = '{$member['mb_id']}'
            ORDER BY g.rbg_created_at DESC";
    $result = sql_query($sql);
    
} else if ($tab == 'sent') {
    // 보낸 선물 목록
    $sql = "SELECT g.*, b.rb_name, b.rb_image, b.rb_price, m.mb_nick as receiver_nick
            FROM {$g5['g5_prefix']}randombox_gift g
            LEFT JOIN {$g5['g5_prefix']}randombox b ON g.rb_id = b.rb_id
            LEFT JOIN {$g5['member_table']} m ON g.recv_mb_id = m.mb_id
            WHERE g.send_mb_id = '{$member['mb_id']}'
            ORDER BY g.rbg_created_at DESC";
    $result = sql_query($sql);
}

// ===================================
// 페이지 헤더
// ===================================

$g5['title'] = '선물하기';
include_once(G5_PATH.'/head.php');
?>

<!-- 랜덤박스 CSS -->
<link rel="stylesheet" href="./style.css?v=<?php echo time(); ?>">

<style>
/* ===================================
 * 선물하기 페이지 스타일
 * =================================== */

.gift-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
}

.page-title {
    font-size: 32px;
    color: #2c3e50;
    margin-bottom: 40px;
    text-align: center;
    font-weight: 600;
}

/* 탭 메뉴 */
.tab-menu {
    display: flex;
    gap: 4px;
    margin-bottom: 40px;
    background: #f8f9fa;
    padding: 4px;
    border-radius: 8px;
}

.tab-item {
    flex: 1;
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
}

.tab-item.active {
    background: #fff;
    color: #2c3e50;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* 선물하기 폼 */
.gift-form-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 32px;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
}

.box-select-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 12px;
}

.box-option {
    position: relative;
    cursor: pointer;
}

.box-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.box-option-label {
    display: block;
    padding: 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    text-align: center;
    transition: all 0.2s;
}

.box-option input[type="radio"]:checked + .box-option-label {
    border-color: #3498db;
    background: #f0f8ff;
}

.box-option-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin: 0 auto 8px;
}

.box-option-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.box-option-price {
    color: #3498db;
    font-size: 18px;
    font-weight: 700;
}

.quantity-input {
    display: flex;
    align-items: center;
    gap: 12px;
}

.quantity-input input {
    width: 100px;
    text-align: center;
}

.btn-submit {
    width: 100%;
    padding: 16px;
    background: #3498db;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-submit:hover {
    background: #2980b9;
}

/* 선물 목록 */
.gift-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.gift-item {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    gap: 20px;
    align-items: center;
}

.gift-box-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px;
}

.gift-info {
    flex: 1;
}

.gift-box-name {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.gift-detail {
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
}

.gift-message {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 6px;
    margin-top: 8px;
    font-size: 14px;
    color: #555;
}

.gift-status {
    text-align: center;
}

.status-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-accepted { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }

.btn-accept {
    margin-top: 8px;
    padding: 8px 20px;
    background: #27ae60;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.empty-message {
    text-align: center;
    padding: 80px 20px;
    color: #999;
}

/* 반응형 */
@media (max-width: 768px) {
    .page-title {
        font-size: 24px;
    }
    
    .tab-menu {
        gap: 2px;
    }
    
    .tab-item {
        font-size: 14px;
        padding: 10px 12px;
    }
    
    .box-select-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .gift-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="gift-container">
    
    <h1 class="page-title">선물하기</h1>
    
    <!-- 탭 메뉴 -->
    <div class="tab-menu">
        <a href="?tab=send" class="tab-item <?php echo $tab == 'send' ? 'active' : ''; ?>">선물 보내기</a>
        <a href="?tab=received" class="tab-item <?php echo $tab == 'received' ? 'active' : ''; ?>">받은 선물</a>
        <a href="?tab=sent" class="tab-item <?php echo $tab == 'sent' ? 'active' : ''; ?>">보낸 선물</a>
    </div>
    
    <?php if ($tab == 'send') : ?>
    <!-- 선물 보내기 -->
    <div class="gift-form-section">
        <form name="fgift" method="post" action="./gift_send.php" onsubmit="return fgift_submit(this);">
            
            <div class="form-group">
                <label class="form-label">받는 사람 아이디</label>
                <input type="text" name="recv_mb_id" class="form-control" placeholder="선물 받을 회원의 아이디를 입력하세요" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">선물할 박스 선택</label>
                <div class="box-select-grid">
                    <?php foreach ($box_list as $idx => $box) : 
                        $box_img = './img/box-default.png';
                        if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
                            $box_img = G5_DATA_URL.'/randombox/box/'.$box['rb_image'];
                        }
                    ?>
                    <div class="box-option">
                        <input type="radio" name="rb_id" id="box_<?php echo $box['rb_id']; ?>" value="<?php echo $box['rb_id']; ?>" data-price="<?php echo $box['rb_price']; ?>" <?php echo $idx == 0 ? 'checked' : ''; ?>>
                        <label for="box_<?php echo $box['rb_id']; ?>" class="box-option-label">
                            <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>" class="box-option-image">
                            <div class="box-option-name"><?php echo $box['rb_name']; ?></div>
                            <div class="box-option-price"><?php echo number_format($box['rb_price']); ?>P</div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">수량</label>
                <div class="quantity-input">
                    <input type="number" name="quantity" value="1" min="1" max="10" class="form-control" required>
                    <span>개</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">선물 메시지 (선택)</label>
                <textarea name="message" class="form-control" rows="3" placeholder="선물과 함께 전달할 메시지를 입력하세요"></textarea>
            </div>
            
            <div class="form-group">
                <div style="background: #f8f9fa; padding: 16px; border-radius: 6px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>선택한 박스</span>
                        <span id="selected_box_name">-</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>단가</span>
                        <span id="selected_box_price">0P</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 18px; padding-top: 8px; border-top: 1px solid #e0e0e0;">
                        <span>총 결제 금액</span>
                        <span id="total_price">0P</span>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">선물하기</button>
            
        </form>
    </div>
    
    <?php elseif ($tab == 'received') : ?>
    <!-- 받은 선물 목록 -->
    <div class="gift-list">
        <?php 
        $has_items = false;
        while ($gift = sql_fetch_array($result)) : 
            $has_items = true;
            $box_img = './img/box-default.png';
            if ($gift['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$gift['rb_image'])) {
                $box_img = G5_DATA_URL.'/randombox/box/'.$gift['rb_image'];
            }
        ?>
        <div class="gift-item">
            <img src="<?php echo $box_img; ?>" alt="<?php echo $gift['rb_name']; ?>" class="gift-box-image">
            
            <div class="gift-info">
                <div class="gift-box-name"><?php echo $gift['rb_name']; ?></div>
                <div class="gift-detail">보낸 사람: <?php echo $gift['sender_nick'] ? $gift['sender_nick'] : $gift['send_mb_id']; ?></div>
                <div class="gift-detail">수량: <?php echo $gift['rbg_quantity']; ?>개</div>
                <div class="gift-detail">받은 날짜: <?php echo date('Y-m-d H:i', strtotime($gift['rbg_created_at'])); ?></div>
                
                <?php if ($gift['rbg_message']) : ?>
                <div class="gift-message">
                    <i class="bi bi-chat-quote"></i> <?php echo nl2br($gift['rbg_message']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="gift-status">
                <?php if ($gift['rbg_status'] == 'pending') : ?>
                    <span class="status-badge status-pending">대기중</span>
                    <br>
                    <button type="button" class="btn-accept" onclick="acceptGift(<?php echo $gift['rbg_id']; ?>)">수락하기</button>
                <?php elseif ($gift['rbg_status'] == 'accepted') : ?>
                    <span class="status-badge status-accepted">수락됨</span>
                <?php else : ?>
                    <span class="status-badge status-rejected">거절됨</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        
        <?php if (!$has_items) : ?>
        <div class="empty-message">
            <p>받은 선물이 없습니다.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php else : ?>
    <!-- 보낸 선물 목록 -->
    <div class="gift-list">
        <?php 
        $has_items = false;
        while ($gift = sql_fetch_array($result)) : 
            $has_items = true;
            $box_img = './img/box-default.png';
            if ($gift['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$gift['rb_image'])) {
                $box_img = G5_DATA_URL.'/randombox/box/'.$gift['rb_image'];
            }
        ?>
        <div class="gift-item">
            <img src="<?php echo $box_img; ?>" alt="<?php echo $gift['rb_name']; ?>" class="gift-box-image">
            
            <div class="gift-info">
                <div class="gift-box-name"><?php echo $gift['rb_name']; ?></div>
                <div class="gift-detail">받는 사람: <?php echo $gift['receiver_nick'] ? $gift['receiver_nick'] : $gift['recv_mb_id']; ?></div>
                <div class="gift-detail">수량: <?php echo $gift['rbg_quantity']; ?>개</div>
                <div class="gift-detail">보낸 날짜: <?php echo date('Y-m-d H:i', strtotime($gift['rbg_created_at'])); ?></div>
                
                <?php if ($gift['rbg_message']) : ?>
                <div class="gift-message">
                    <i class="bi bi-chat-quote"></i> <?php echo nl2br($gift['rbg_message']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="gift-status">
                <?php if ($gift['rbg_status'] == 'pending') : ?>
                    <span class="status-badge status-pending">대기중</span>
                <?php elseif ($gift['rbg_status'] == 'accepted') : ?>
                    <span class="status-badge status-accepted">수락됨</span>
                    <?php if ($gift['rbg_accepted_at']) : ?>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">
                        <?php echo date('Y-m-d', strtotime($gift['rbg_accepted_at'])); ?>
                    </div>
                    <?php endif; ?>
                <?php else : ?>
                    <span class="status-badge status-rejected">거절됨</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        
        <?php if (!$has_items) : ?>
        <div class="empty-message">
            <p>보낸 선물이 없습니다.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- 하단 버튼 -->
    <div style="text-align: center; margin-top: 40px;">
        <a href="./" class="btn btn-secondary" style="padding: 12px 32px; font-size: 16px; text-decoration: none; display: inline-block; background: #95a5a6; color: #fff; border-radius: 8px;">
            랜덤박스 메인
        </a>
    </div>
    
</div>

<script>
// 박스 선택 시 가격 계산
$(document).ready(function() {
    // 초기 선택된 박스 정보 표시
    updateSelectedBox();
    
    // 박스 선택 변경
    $('input[name="rb_id"]').on('change', function() {
        updateSelectedBox();
    });
    
    // 수량 변경
    $('input[name="quantity"]').on('input', function() {
        updateTotalPrice();
    });
});

function updateSelectedBox() {
    var $selected = $('input[name="rb_id"]:checked');
    var boxName = $selected.closest('.box-option').find('.box-option-name').text();
    var boxPrice = parseInt($selected.data('price'));
    
    $('#selected_box_name').text(boxName);
    $('#selected_box_price').text(number_format(boxPrice) + 'P');
    
    updateTotalPrice();
}

function updateTotalPrice() {
    var $selected = $('input[name="rb_id"]:checked');
    var boxPrice = parseInt($selected.data('price'));
    var quantity = parseInt($('input[name="quantity"]').val()) || 1;
    
    var totalPrice = boxPrice * quantity;
    $('#total_price').text(number_format(totalPrice) + 'P');
}

function fgift_submit(f) {
    if (!f.recv_mb_id.value) {
        alert('받는 사람 아이디를 입력해 주세요.');
        f.recv_mb_id.focus();
        return false;
    }
    
    if (!$('input[name="rb_id"]:checked').length) {
        alert('선물할 박스를 선택해 주세요.');
        return false;
    }
    
    var quantity = parseInt(f.quantity.value);
    if (quantity < 1 || quantity > 10) {
        alert('수량은 1개 이상 10개 이하로 입력해 주세요.');
        f.quantity.focus();
        return false;
    }
    
    var totalPrice = parseInt($('#total_price').text().replace(/[^0-9]/g, ''));
    var userPoint = <?php echo $member['mb_point']; ?>;
    
    if (totalPrice > userPoint) {
        alert('포인트가 부족합니다.\n필요 포인트: ' + number_format(totalPrice) + 'P\n보유 포인트: ' + number_format(userPoint) + 'P');
        return false;
    }
    
    return confirm('선물을 보내시겠습니까?\n\n총 ' + number_format(totalPrice) + 'P가 차감됩니다.');
}

function acceptGift(gift_id) {
    if (!confirm('선물을 수락하시겠습니까?')) {
        return;
    }
    
    $.ajax({
        url: './gift_accept.php',
        type: 'POST',
        data: {
            rbg_id: gift_id
        },
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                alert(response.msg);
                location.reload();
            } else {
                alert(response.msg);
            }
        },
        error: function() {
            alert('처리 중 오류가 발생했습니다.');
        }
    });
}

function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>