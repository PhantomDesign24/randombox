<?php
/*
 * 파일명: coupon_form.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 타입 등록/수정 폼
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rct_id = (int)$rct_id;
$w = ($w == 'u') ? 'u' : '';

if ($w == 'u') {
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_coupon_types WHERE rct_id = '$rct_id'";
    $coupon = sql_fetch($sql);
    if (!$coupon) {
        alert('존재하지 않는 교환권 타입입니다.');
    }
} else {
    $coupon = array(
        'rct_id' => '',
        'rct_name' => '',
        'rct_desc' => '',
        'rct_type' => 'exchange',
        'rct_image' => '',
        'rct_value' => 0,
        'rct_exchange_item' => '',
        'rct_status' => 1
    );
}

$g5['title'] = ($w == 'u') ? '교환권 타입 수정' : '교환권 타입 등록';
include_once('./admin.head.php');
?>

<form name="fcouponform" method="post" action="./coupon_form_update.php" enctype="multipart/form-data" onsubmit="return fcouponform_submit(this);">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="rct_id" value="<?php echo $rct_id; ?>">
<input type="hidden" name="token" value="">

<section>
    <h2 class="h2_frm">기본 정보</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>기본 정보</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="rct_name">교환권명</label> <span class="required">필수</span></th>
            <td>
                <input type="text" name="rct_name" value="<?php echo $coupon['rct_name']; ?>" id="rct_name" class="required frm_input" size="50" maxlength="255" required>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rct_type">타입</label> <span class="required">필수</span></th>
            <td>
                <select name="rct_type" id="rct_type" class="frm_input" required onchange="changeCouponType(this.value)">
                    <option value="exchange" <?php echo ($coupon['rct_type'] == 'exchange') ? 'selected' : ''; ?>>교환용</option>
                    <option value="gifticon" <?php echo ($coupon['rct_type'] == 'gifticon') ? 'selected' : ''; ?>>기프티콘</option>
                </select>
                <span class="frm_info">
                    교환용: 특정 상품으로 교환 가능한 교환권<br>
                    기프티콘: 코드를 입력하여 사용하는 기프티콘
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rct_exchange_item">교환 상품</label> <span class="required">필수</span></th>
            <td>
                <input type="text" name="rct_exchange_item" value="<?php echo $coupon['rct_exchange_item']; ?>" id="rct_exchange_item" class="required frm_input" size="50" maxlength="255" required>
                <span class="frm_info">교환 가능한 상품명을 입력하세요. (예: 스타벅스 아메리카노 Tall)</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rct_value">가치 (포인트)</label> <span class="required">필수</span></th>
            <td>
                <input type="number" name="rct_value" value="<?php echo $coupon['rct_value']; ?>" id="rct_value" class="required frm_input" min="0" required> 포인트
                <span class="frm_info">교환권의 포인트 가치를 입력하세요.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rct_status">상태</label></th>
            <td>
                <select name="rct_status" id="rct_status" class="frm_input">
                    <option value="1" <?php echo ($coupon['rct_status'] == 1) ? 'selected' : ''; ?>>활성</option>
                    <option value="0" <?php echo ($coupon['rct_status'] == 0) ? 'selected' : ''; ?>>비활성</option>
                </select>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">상세 설명</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>상세 설명</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="rct_desc">설명</label></th>
            <td>
                <textarea name="rct_desc" id="rct_desc" rows="5" class="frm_input" style="width:100%;"><?php echo $coupon['rct_desc']; ?></textarea>
                <span class="frm_info">사용 방법, 유효기간, 주의사항 등을 입력하세요.</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">이미지 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>이미지 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="rct_image">교환권 이미지</label></th>
            <td>
                <input type="file" name="rct_image" id="rct_image" class="frm_input">
                <?php
                if ($coupon['rct_image'] && file_exists(G5_DATA_PATH.'/randombox/coupon/'.$coupon['rct_image'])) {
                    echo '<div style="margin-top:5px;">';
                    echo '<img src="'.G5_DATA_URL.'/randombox/coupon/'.$coupon['rct_image'].'" style="max-width:200px;height:auto;">';
                    echo '<br><label><input type="checkbox" name="rct_image_del" value="1"> 이미지 삭제</label>';
                    echo '</div>';
                }
                ?>
                <div class="frm_info">
                    권장 크기: 400x400px<br>
                    지원 형식: jpg, png, gif
                </div>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div id="gifticon_section" style="display:<?php echo ($coupon['rct_type'] == 'gifticon') ? 'block' : 'none'; ?>;">
<section>
    <h2 class="h2_frm">기프티콘 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>기프티콘 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">코드 관리</th>
            <td>
                <?php if ($w == 'u' && $coupon['rct_type'] == 'gifticon') : ?>
                    <?php
                    $sql = "SELECT 
                            COUNT(*) as total_codes,
                            SUM(CASE WHEN rcc_status = 'available' THEN 1 ELSE 0 END) as available_codes,
                            SUM(CASE WHEN rcc_status = 'used' THEN 1 ELSE 0 END) as used_codes
                            FROM {$g5['g5_prefix']}randombox_coupon_codes 
                            WHERE rct_id = '{$rct_id}'";
                    $code_stats = sql_fetch($sql);
                    ?>
                    <div class="code_stats">
                        <span>전체: <?php echo number_format($code_stats['total_codes']); ?>개</span>
                        <span style="margin-left:20px;">사용가능: <strong style="color:#27ae60;"><?php echo number_format($code_stats['available_codes']); ?>개</strong></span>
                        <span style="margin-left:20px;">사용완료: <?php echo number_format($code_stats['used_codes']); ?>개</span>
                    </div>
                    <a href="./coupon_code_list.php?rct_id=<?php echo $rct_id; ?>" class="btn btn_02" style="margin-top:10px;">코드 관리 페이지로 이동</a>
                <?php else : ?>
                    <span class="frm_info">저장 후 코드를 등록할 수 있습니다.</span>
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn_submit" accesskey="s">
    <a href="./coupon_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
</div>

</form>

<script>
function fcouponform_submit(f) {
    if (!f.rct_name.value) {
        alert("교환권명을 입력하세요.");
        f.rct_name.focus();
        return false;
    }
    
    if (!f.rct_exchange_item.value) {
        alert("교환 상품을 입력하세요.");
        f.rct_exchange_item.focus();
        return false;
    }
    
    if (!f.rct_value.value || f.rct_value.value < 1) {
        alert("가치를 1 이상 입력하세요.");
        f.rct_value.focus();
        return false;
    }
    
    return true;
}

function changeCouponType(type) {
    if (type == 'gifticon') {
        document.getElementById('gifticon_section').style.display = 'block';
    } else {
        document.getElementById('gifticon_section').style.display = 'none';
    }
}

// 초기 로드 시 타입에 따른 표시
document.addEventListener('DOMContentLoaded', function() {
    changeCouponType('<?php echo $coupon['rct_type']; ?>');
});
</script>

<style>
.code_stats {
    padding: 10px;
    background: #f5f5f5;
    border-radius: 5px;
    font-size: 14px;
}
</style>

<?php
include_once('./admin.tail.php');
?>