<?php
/*
 * 파일명: coupon_use.php
 * 위치: /randombox/
 * 기능: 교환권 사용 처리
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

// 로그인 체크
if (!$member['mb_id']) {
    alert('로그인이 필요한 서비스입니다.', G5_BBS_URL.'/login.php');
}

$rmc_id = (int)$_GET['rmc_id'];
if (!$rmc_id) {
    alert('잘못된 접근입니다.');
}

// 교환권 정보 조회
$sql = "SELECT mc.*, ct.rct_name, ct.rct_type, ct.rct_exchange_item, ct.rct_value,
        cc.rcc_code, cc.rcc_pin
        FROM {$g5['g5_prefix']}randombox_member_coupons mc
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_types ct ON mc.rct_id = ct.rct_id
        LEFT JOIN {$g5['g5_prefix']}randombox_coupon_codes cc ON mc.rcc_id = cc.rcc_id
        WHERE mc.rmc_id = '{$rmc_id}'
        AND mc.mb_id = '{$member['mb_id']}'";
$coupon = sql_fetch($sql);

if (!$coupon) {
    alert('존재하지 않는 교환권입니다.');
}

if ($coupon['rmc_status'] != 'active') {
    alert('이미 사용했거나 만료된 교환권입니다.');
}

// 만료일 체크
if ($coupon['rmc_expire_date'] && $coupon['rmc_expire_date'] < G5_TIME_YMD) {
    // 만료 처리
    sql_query("UPDATE {$g5['g5_prefix']}randombox_member_coupons SET rmc_status = 'expired' WHERE rmc_id = '{$rmc_id}'");
    alert('만료된 교환권입니다.');
}

// POST 요청인 경우 사용 처리
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $use_type = $_POST['use_type'];
    $use_memo = strip_tags($_POST['use_memo']);
    
    // 트랜잭션 시작
    sql_query("START TRANSACTION");
    
    try {
        // 교환권 사용 처리
        $sql = "UPDATE {$g5['g5_prefix']}randombox_member_coupons SET
                rmc_status = 'used',
                rmc_used_at = NOW()
                WHERE rmc_id = '{$rmc_id}'";
        
        if (!sql_query($sql)) {
            throw new Exception('교환권 사용 처리에 실패했습니다.');
        }
        
        // 기프티콘인 경우 코드 사용 처리
        if ($coupon['rct_type'] == 'gifticon' && $coupon['rcc_id']) {
            $sql = "UPDATE {$g5['g5_prefix']}randombox_coupon_codes SET
                    rcc_status = 'used',
                    rcc_used_by = '{$member['mb_id']}',
                    rcc_used_at = NOW()
                    WHERE rcc_id = '{$coupon['rcc_id']}'";
            
            if (!sql_query($sql)) {
                throw new Exception('코드 사용 처리에 실패했습니다.');
            }
        }
        
        // 사용 로그 기록
        $sql = "INSERT INTO {$g5['g5_prefix']}randombox_coupon_use_log SET
                mb_id = '{$member['mb_id']}',
                rmc_id = '{$rmc_id}',
                rct_id = '{$coupon['rct_id']}',
                rcl_type = '{$use_type}',
                rcl_memo = '{$use_memo}',
                rcl_ip = '{$_SERVER['REMOTE_ADDR']}',
                rcl_created_at = NOW()";
        
        if (!sql_query($sql)) {
            throw new Exception('사용 로그 기록에 실패했습니다.');
        }
        
        // 커밋
        sql_query("COMMIT");
        
        $msg = "교환권이 사용 처리되었습니다.\\n\\n";
        $msg .= "교환권: {$coupon['rct_name']}\\n";
        $msg .= "교환 상품: {$coupon['rct_exchange_item']}";
        
        alert($msg, './my_coupons.php');
        
    } catch (Exception $e) {
        // 롤백
        sql_query("ROLLBACK");
        alert($e->getMessage());
    }
}

$g5['title'] = '교환권 사용';
include_once(G5_PATH.'/head.php');

// 이미지 처리
$coupon_img = G5_URL.'/randombox/img/item-default.png';
if ($coupon['rct_image'] && file_exists(G5_DATA_PATH.'/randombox/coupon/'.$coupon['rct_image'])) {
    $coupon_img = G5_DATA_URL.'/randombox/coupon/'.$coupon['rct_image'];
}
?>

<!-- 스타일시트 -->
<link rel="stylesheet" href="<?php echo G5_URL; ?>/randombox/style.css">
<style>
.coupon-use-page {
    max-width: 600px;
    margin: 40px auto;
    padding: 20px;
}

.coupon-use-card {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.use-title {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    text-align: center;
    margin-bottom: 30px;
}

.coupon-preview {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.preview-image {
    width: 120px;
    height: 120px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.preview-info {
    text-align: center;
}

.preview-name {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.preview-exchange {
    font-size: 16px;
    color: #666;
    margin-bottom: 8px;
}

.preview-value {
    font-size: 18px;
    font-weight: 600;
    color: #3498db;
}

/* 코드 표시 */
.code-section {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.code-title {
    font-size: 16px;
    font-weight: 600;
    color: #856404;
    margin-bottom: 15px;
    text-align: center;
}

.code-box {
    background: #fff;
    border: 2px dashed #ffeeba;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    margin-bottom: 10px;
}

.code-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.code-value {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    font-family: monospace;
    letter-spacing: 1px;
}

/* 사용 확인 폼 */
.use-form {
    margin-top: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-select,
.form-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #3498db;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-info {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

/* 버튼 */
.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn-use {
    flex: 1;
    padding: 15px;
    background: #1a1a1a;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-use:hover {
    background: #000;
}

.btn-cancel {
    flex: 1;
    padding: 15px;
    background: #f0f0f0;
    color: #333;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    text-align: center;
}

.btn-cancel:hover {
    background: #e0e0e0;
}

/* 주의사항 */
.warning-box {
    background: #fff5f5;
    border: 1px solid #ffdddd;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.warning-title {
    font-size: 14px;
    font-weight: 600;
    color: #e74c3c;
    margin-bottom: 10px;
}

.warning-list {
    margin: 0;
    padding-left: 20px;
}

.warning-list li {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}
</style>

<div class="coupon-use-page">
    <div class="coupon-use-card">
        <h1 class="use-title">교환권 사용</h1>
        
        <!-- 교환권 미리보기 -->
        <div class="coupon-preview">
            <div class="preview-image">
                <img src="<?php echo $coupon_img; ?>" alt="<?php echo $coupon['rct_name']; ?>">
            </div>
            <div class="preview-info">
                <div class="preview-name"><?php echo $coupon['rct_name']; ?></div>
                <div class="preview-exchange"><?php echo $coupon['rct_exchange_item']; ?></div>
                <div class="preview-value"><?php echo number_format($coupon['rct_value']); ?>P 상당</div>
            </div>
        </div>
        
        <!-- 기프티콘 코드 표시 -->
        <?php if ($coupon['rct_type'] == 'gifticon' && $coupon['rcc_code']) : ?>
        <div class="code-section">
            <div class="code-title">아래 코드를 사용하여 교환하세요</div>
            
            <div class="code-box">
                <div class="code-label">교환권 코드</div>
                <div class="code-value"><?php echo $coupon['rcc_code']; ?></div>
            </div>
            
            <?php if ($coupon['rcc_pin']) : ?>
            <div class="code-box">
                <div class="code-label">PIN 번호</div>
                <div class="code-value"><?php echo $coupon['rcc_pin']; ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 사용 확인 폼 -->
        <form method="post" onsubmit="return confirmUse();" class="use-form">
            <div class="form-group">
                <label for="use_type" class="form-label">사용 구분</label>
                <select name="use_type" id="use_type" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="online">온라인 사용</option>
                    <option value="offline">오프라인 사용</option>
                    <option value="gift">선물/양도</option>
                    <option value="other">기타</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="use_memo" class="form-label">사용 메모 (선택)</label>
                <textarea name="use_memo" id="use_memo" class="form-textarea" placeholder="사용처나 메모를 남겨주세요"></textarea>
                <div class="form-info">최대 200자까지 입력 가능합니다.</div>
            </div>
            
            <div class="warning-box">
                <div class="warning-title">⚠️ 주의사항</div>
                <ul class="warning-list">
                    <li>사용 처리된 교환권은 복구할 수 없습니다.</li>
                    <li>실제로 사용한 후에 처리해 주세요.</li>
                    <li>부정 사용 시 불이익을 받을 수 있습니다.</li>
                </ul>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-use">사용 완료</button>
                <a href="./my_coupons.php" class="btn-cancel">취소</a>
            </div>
        </form>
    </div>
</div>

<script>
function confirmUse() {
    if (!document.getElementById('use_type').value) {
        alert('사용 구분을 선택해 주세요.');
        return false;
    }
    
    return confirm('정말로 이 교환권을 사용 처리하시겠습니까?\n\n사용 처리 후에는 복구할 수 없습니다.');
}
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>