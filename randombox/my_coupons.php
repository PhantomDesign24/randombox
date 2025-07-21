<?php
/*
 * 파일명: my_coupons.php
 * 위치: /randombox/
 * 기능: 내 교환권 보기
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

// 로그인 체크
if (!$member['mb_id']) {
    alert('로그인이 필요한 서비스입니다.', G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/randombox/my_coupons.php'));
}

// 시스템 활성화 체크
if (!get_randombox_config('system_enable')) {
    alert(get_randombox_config('maintenance_msg') ?: '시스템 점검 중입니다.');
}

$g5['title'] = '내 교환권';
include_once(G5_PATH.'/head.php');

// ===================================
// 통계 데이터
// ===================================

$sql = "SELECT 
        COUNT(*) as total_count,
        COUNT(CASE WHEN rmc_status = 'active' THEN 1 END) as active_count,
        COUNT(CASE WHEN rmc_status = 'used' THEN 1 END) as used_count,
        COUNT(CASE WHEN rmc_status = 'expired' THEN 1 END) as expired_count
        FROM {$g5['g5_prefix']}randombox_member_coupons
        WHERE mb_id = '{$member['mb_id']}'";
$stats = sql_fetch($sql);

// ===================================
// 교환권 목록 조회
// ===================================

// 활성 교환권
$sql = "SELECT mc.*, ct.rct_name, ct.rct_image, ct.rct_exchange_item, ct.rct_value, ct.rct_type,
        cc.rcc_code, cc.rcc_pin
        FROM {$g5['g5_prefix']}randombox_member_coupons mc
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_types ct ON mc.rct_id = ct.rct_id
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_codes cc ON mc.rcc_id = cc.rcc_id
        WHERE mc.mb_id = '{$member['mb_id']}'
        AND mc.rmc_status = 'active'
        ORDER BY mc.rmc_created_at DESC";
$active_result = sql_query($sql);

// 사용/만료 교환권
$sql = "SELECT mc.*, ct.rct_name, ct.rct_image, ct.rct_exchange_item, ct.rct_value, ct.rct_type,
        cc.rcc_code, cc.rcc_pin
        FROM {$g5['g5_prefix']}randombox_member_coupons mc
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_types ct ON mc.rct_id = ct.rct_id
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_codes cc ON mc.rcc_id = cc.rcc_id
        WHERE mc.mb_id = '{$member['mb_id']}'
        AND mc.rmc_status IN ('used', 'expired')
        ORDER BY mc.rmc_created_at DESC
        LIMIT 50";
$history_result = sql_query($sql);
?>

<!-- 스타일시트 -->
<link rel="stylesheet" href="<?php echo G5_URL; ?>/randombox/style.css">
<link rel="stylesheet" href="<?php echo G5_URL; ?>/randombox/modal.css">
<style>
/* 교환권 페이지 전용 스타일 */
.coupon-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.coupon-header {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.coupon-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 20px;
}

.coupon-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
}

.stat-value.active { color: #27ae60; }
.stat-value.used { color: #666; }
.stat-value.expired { color: #e74c3c; }

/* 교환권 섹션 */
.coupon-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #1a1a1a;
}

/* 교환권 그리드 */
.coupon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

/* 교환권 카드 */
.coupon-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
    cursor: pointer;
}

.coupon-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.coupon-card.used,
.coupon-card.expired {
    opacity: 0.6;
    cursor: default;
}

.coupon-card.used:hover,
.coupon-card.expired:hover {
    transform: none;
}

/* 교환권 이미지 */
.coupon-image {
    width: 100%;
    height: 180px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.coupon-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.coupon-status-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 12px;
    background: rgba(0,0,0,0.8);
    color: #fff;
    font-size: 12px;
    border-radius: 20px;
    font-weight: 600;
}

.coupon-status-badge.used { background: #666; }
.coupon-status-badge.expired { background: #e74c3c; }

/* 교환권 정보 */
.coupon-info {
    padding: 20px;
}

.coupon-name {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.coupon-exchange {
    font-size: 14px;
    color: #666;
    margin-bottom: 12px;
}

.coupon-value {
    font-size: 16px;
    font-weight: 600;
    color: #3498db;
    margin-bottom: 12px;
}

.coupon-expire {
    font-size: 13px;
    color: #999;
}

.coupon-expire.warning {
    color: #e74c3c;
    font-weight: 600;
}

/* 사용하기 버튼 */
.use-coupon-btn {
    width: 100%;
    padding: 12px;
    background: #1a1a1a;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 15px;
}

.use-coupon-btn:hover {
    background: #000;
}

/* 교환권 상세 모달 */
.coupon-detail-modal {
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

.coupon-detail-content {
    background: #fff;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.coupon-detail-header {
    padding: 30px 30px 20px;
    border-bottom: 1px solid #e5e5e5;
}

.coupon-detail-title {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
}

.coupon-detail-body {
    padding: 30px;
}

.coupon-detail-image {
    width: 100%;
    height: 200px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 30px;
    border-radius: 8px;
}

.coupon-detail-info {
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-label {
    font-size: 14px;
    color: #666;
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a1a;
}

/* 코드 표시 영역 */
.code-display {
    background: #f8f9fa;
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    margin: 20px 0;
}

.code-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.code-value {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    font-family: monospace;
    letter-spacing: 2px;
    margin-bottom: 10px;
}

.copy-code-btn {
    padding: 8px 16px;
    background: #3498db;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.copy-code-btn:hover {
    background: #2980b9;
}

/* 빈 상태 */
.empty-coupons {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 12px;
}

.empty-icon {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-text {
    font-size: 18px;
    color: #666;
    margin-bottom: 20px;
}

.go-randombox-btn {
    display: inline-block;
    padding: 12px 30px;
    background: #1a1a1a;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s;
}

.go-randombox-btn:hover {
    background: #000;
    color: #fff;
}

/* 반응형 */
@media (max-width: 768px) {
    .coupon-grid {
        grid-template-columns: 1fr;
    }
    
    .coupon-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="coupon-page">
    <!-- 헤더 -->
    <div class="coupon-header">
        <h1 class="coupon-title">
            <i class="bi bi-ticket-perforated"></i> 내 교환권
        </h1>
        
        <div class="coupon-stats">
            <div class="stat-card">
                <div class="stat-label">전체 교환권</div>
                <div class="stat-value"><?php echo number_format($stats['total_count']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">사용 가능</div>
                <div class="stat-value active"><?php echo number_format($stats['active_count']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">사용 완료</div>
                <div class="stat-value used"><?php echo number_format($stats['used_count']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">만료</div>
                <div class="stat-value expired"><?php echo number_format($stats['expired_count']); ?></div>
            </div>
        </div>
    </div>
    
    <!-- 사용 가능한 교환권 -->
    <div class="coupon-section">
        <h2 class="section-title">사용 가능한 교환권</h2>
        
        <?php if (sql_num_rows($active_result) > 0) : ?>
        <div class="coupon-grid">
            <?php while ($coupon = sql_fetch_array($active_result)) : ?>
            <?php
            // 이미지 처리
            $coupon_img = G5_URL.'/randombox/img/item-default.png';
            if ($coupon['rct_image'] && file_exists(G5_DATA_PATH.'/randombox/coupon/'.$coupon['rct_image'])) {
                $coupon_img = G5_DATA_URL.'/randombox/coupon/'.$coupon['rct_image'];
            }
            
            // 만료일 체크
            $is_expiring_soon = false;
            $expire_text = '무기한';
            if ($coupon['rmc_expire_date']) {
                $days_left = ceil((strtotime($coupon['rmc_expire_date']) - time()) / 86400);
                if ($days_left <= 0) {
                    // 만료 처리
                    sql_query("UPDATE {$g5['g5_prefix']}randombox_member_coupons SET rmc_status = 'expired' WHERE rmc_id = '{$coupon['rmc_id']}'");
                    continue;
                } elseif ($days_left <= 7) {
                    $is_expiring_soon = true;
                    $expire_text = 'D-' . $days_left;
                } else {
                    $expire_text = $coupon['rmc_expire_date'];
                }
            }
            ?>
            <div class="coupon-card" onclick="showCouponDetail(<?php echo $coupon['rmc_id']; ?>)">
                <div class="coupon-image">
                    <img src="<?php echo $coupon_img; ?>" alt="<?php echo $coupon['rct_name']; ?>">
                </div>
                <div class="coupon-info">
                    <div class="coupon-name"><?php echo $coupon['rct_name']; ?></div>
                    <div class="coupon-exchange"><?php echo $coupon['rct_exchange_item']; ?></div>
                    <div class="coupon-value"><?php echo number_format($coupon['rct_value']); ?>P 상당</div>
                    <div class="coupon-expire <?php echo $is_expiring_soon ? 'warning' : ''; ?>">
                        유효기간: <?php echo $expire_text; ?>
                    </div>
                    <button type="button" class="use-coupon-btn" onclick="event.stopPropagation(); useCoupon(<?php echo $coupon['rmc_id']; ?>)">
                        사용하기
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else : ?>
        <div class="empty-coupons">
            <div class="empty-icon"><i class="bi bi-ticket-perforated"></i></div>
            <div class="empty-text">사용 가능한 교환권이 없습니다.</div>
            <a href="<?php echo G5_URL; ?>/randombox/" class="go-randombox-btn">랜덤박스 구매하기</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- 사용/만료 교환권 -->
    <?php if (sql_num_rows($history_result) > 0) : ?>
    <div class="coupon-section">
        <h2 class="section-title">사용/만료 교환권</h2>
        
        <div class="coupon-grid">
            <?php while ($coupon = sql_fetch_array($history_result)) : ?>
            <?php
            // 이미지 처리
            $coupon_img = G5_URL.'/randombox/img/item-default.png';
            if ($coupon['rct_image'] && file_exists(G5_DATA_PATH.'/randombox/coupon/'.$coupon['rct_image'])) {
                $coupon_img = G5_DATA_URL.'/randombox/coupon/'.$coupon['rct_image'];
            }
            ?>
            <div class="coupon-card <?php echo $coupon['rmc_status']; ?>">
                <div class="coupon-image">
                    <img src="<?php echo $coupon_img; ?>" alt="<?php echo $coupon['rct_name']; ?>">
                    <span class="coupon-status-badge <?php echo $coupon['rmc_status']; ?>">
                        <?php echo ($coupon['rmc_status'] == 'used') ? '사용완료' : '만료'; ?>
                    </span>
                </div>
                <div class="coupon-info">
                    <div class="coupon-name"><?php echo $coupon['rct_name']; ?></div>
                    <div class="coupon-exchange"><?php echo $coupon['rct_exchange_item']; ?></div>
                    <div class="coupon-value"><?php echo number_format($coupon['rct_value']); ?>P 상당</div>
                    <div class="coupon-expire">
                        <?php if ($coupon['rmc_status'] == 'used') : ?>
                            사용일: <?php echo date('Y-m-d', strtotime($coupon['rmc_used_at'])); ?>
                        <?php else : ?>
                            만료일: <?php echo $coupon['rmc_expire_date'] ?: '-'; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- 교환권 상세 모달 -->
<div id="couponDetailModal" class="coupon-detail-modal">
    <div class="coupon-detail-content">
        <div class="coupon-detail-header">
            <h3 class="coupon-detail-title">교환권 상세정보</h3>
            <button type="button" class="rb-modal-close" onclick="closeCouponDetail()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="coupon-detail-body" id="couponDetailBody">
            <!-- 동적으로 내용 추가 -->
        </div>
    </div>
</div>

<script>
// 교환권 상세 보기
function showCouponDetail(rmc_id) {
    // AJAX로 상세 정보 가져오기
    $.ajax({
        url: './coupon_detail.php',
        type: 'GET',
        data: { rmc_id: rmc_id },
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                displayCouponDetail(response.data);
            } else {
                alert(response.message || '교환권 정보를 불러올 수 없습니다.');
            }
        },
        error: function() {
            alert('통신 오류가 발생했습니다.');
        }
    });
}

// 교환권 상세 표시
function displayCouponDetail(data) {
    let html = `
        <div class="coupon-detail-image">
            <img src="${data.image}" alt="${data.name}">
        </div>
        
        <div class="coupon-detail-info">
            <div class="info-row">
                <span class="info-label">교환권명</span>
                <span class="info-value">${data.name}</span>
            </div>
            <div class="info-row">
                <span class="info-label">교환 상품</span>
                <span class="info-value">${data.exchange_item}</span>
            </div>
            <div class="info-row">
                <span class="info-label">가치</span>
                <span class="info-value">${number_format(data.value)}P</span>
            </div>
            <div class="info-row">
                <span class="info-label">유효기간</span>
                <span class="info-value">${data.expire_date || '무기한'}</span>
            </div>
            <div class="info-row">
                <span class="info-label">획득일</span>
                <span class="info-value">${data.created_at}</span>
            </div>
        </div>
    `;
    
    // 기프티콘인 경우 코드 표시
    if (data.type == 'gifticon' && data.code) {
        html += `
        <div class="code-display">
            <div class="code-label">교환권 코드</div>
            <div class="code-value" id="couponCode">${data.code}</div>
            <button type="button" class="copy-code-btn" onclick="copyCode('${data.code}')">
                <i class="bi bi-clipboard"></i> 복사하기
            </button>
        </div>
        `;
        
        if (data.pin) {
            html += `
            <div class="code-display">
                <div class="code-label">PIN 번호</div>
                <div class="code-value" id="couponPin">${data.pin}</div>
                <button type="button" class="copy-code-btn" onclick="copyCode('${data.pin}')">
                    <i class="bi bi-clipboard"></i> 복사하기
                </button>
            </div>
            `;
        }
    }
    
    // 설명이 있는 경우
    if (data.desc) {
        html += `
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <h4 style="font-size: 16px; margin-bottom: 10px;">사용 안내</h4>
            <div style="font-size: 14px; color: #666; line-height: 1.6;">${data.desc}</div>
        </div>
        `;
    }
    
    $('#couponDetailBody').html(html);
    $('#couponDetailModal').css('display', 'flex');
}

// 모달 닫기
function closeCouponDetail() {
    $('#couponDetailModal').hide();
}

// 코드 복사
function copyCode(code) {
    const textarea = document.createElement('textarea');
    textarea.value = code;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    alert('클립보드에 복사되었습니다.');
}

// 교환권 사용
function useCoupon(rmc_id) {
    if (!confirm('이 교환권을 사용하시겠습니까?\n\n사용 처리된 교환권은 복구할 수 없습니다.')) {
        return;
    }
    
    location.href = './coupon_use.php?rmc_id=' + rmc_id;
}

// 숫자 포맷
function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// ESC 키로 모달 닫기
$(document).on('keyup', function(e) {
    if (e.key === "Escape") {
        closeCouponDetail();
    }
});

// 모달 외부 클릭 시 닫기
$('#couponDetailModal').on('click', function(e) {
    if (e.target === this) {
        closeCouponDetail();
    }
});
</script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<?php
include_once(G5_PATH.'/tail.php');
?>