<?php
/*
 * 파일명: item_form.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 아이템 등록/수정 폼 페이지
 * 작성일: 2025-01-04
 */

$sub_menu = "300930";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rb_id = (int)$rb_id;
$rbi_id = (int)$rbi_id;
$w = ($w == 'u') ? 'u' : '';

// 박스 정보 확인
if (!$rb_id) {
    alert('박스를 선택해 주세요.', './box_list.php');
}

$box = get_randombox($rb_id);
if (!$box) {
    alert('존재하지 않는 박스입니다.', './box_list.php');
}

if ($w == 'u') {
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items WHERE rbi_id = '$rbi_id' AND rb_id = '$rb_id'";
    $item = sql_fetch($sql);
    if (!$item) {
        alert('존재하지 않는 아이템입니다.');
    }
} else {
    $item = array(
        'rbi_id' => '',
        'rbi_name' => '',
        'rbi_desc' => '',
        'rbi_image' => '',
        'rbi_grade' => 'normal',
        'rbi_probability' => 0,
        'rbi_value' => 0,
        'rbi_limit_qty' => 0,
        'rbi_status' => 1,
        'rbi_order' => 0
    );
}

$g5['title'] = ($w == 'u') ? '아이템 수정' : '아이템 등록';
$g5['title'] .= ' - ' . $box['rb_name'];
include_once('./admin.head.php');
include_once(G5_EDITOR_LIB);

// 확률 계산
$sql = "SELECT SUM(rbi_probability) as total_prob 
        FROM {$g5['g5_prefix']}randombox_items 
        WHERE rb_id = '$rb_id' 
        " . ($w == 'u' ? "AND rbi_id != '$rbi_id'" : "");
$row = sql_fetch($sql);
$used_probability = $row['total_prob'] ? $row['total_prob'] : 0;
$available_probability = 100 - $used_probability;
?>

<form name="fitemform" method="post" action="./item_form_update.php" enctype="multipart/form-data" onsubmit="return fitemform_submit(this);">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="rb_id" value="<?php echo $rb_id; ?>">
<input type="hidden" name="rbi_id" value="<?php echo $rbi_id; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="">

<div class="local_desc01 local_desc">
    <p>
        <strong>박스명:</strong> <?php echo $box['rb_name']; ?> | 
        <strong>사용 가능 확률:</strong> <span style="color:<?php echo ($available_probability < 0) ? '#e74c3c' : '#27ae60'; ?>;font-weight:bold;"><?php echo number_format($available_probability, 6); ?>%</span>
    </p>
</div>

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
            <th scope="row"><label for="rbi_name">아이템명</label> <span class="required">필수</span></th>
            <td>
                <input type="text" name="rbi_name" value="<?php echo $item['rbi_name']; ?>" id="rbi_name" class="required frm_input" size="50" maxlength="255" required>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rbi_grade">등급</label> <span class="required">필수</span></th>
            <td>
                <select name="rbi_grade" id="rbi_grade" class="frm_input" required>
                    <option value="normal" <?php echo ($item['rbi_grade'] == 'normal') ? 'selected' : ''; ?>>일반 (Normal)</option>
                    <option value="rare" <?php echo ($item['rbi_grade'] == 'rare') ? 'selected' : ''; ?>>레어 (Rare)</option>
                    <option value="epic" <?php echo ($item['rbi_grade'] == 'epic') ? 'selected' : ''; ?>>에픽 (Epic)</option>
                    <option value="legendary" <?php echo ($item['rbi_grade'] == 'legendary') ? 'selected' : ''; ?>>레전더리 (Legendary)</option>
                </select>
                <span class="frm_info">천장 시스템 적용 시 레어 이상이 보장됩니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rbi_probability">확률</label> <span class="required">필수</span></th>
            <td>
                <input type="number" name="rbi_probability" value="<?php echo $item['rbi_probability']; ?>" id="rbi_probability" class="required frm_input" min="0" max="100" step="0.000001" required> %
                <span class="frm_info">소수점 6자리까지 입력 가능 (예: 0.000001)</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rbi_value">아이템 가치</label></th>
            <td>
                <input type="number" name="rbi_value" value="<?php echo $item['rbi_value']; ?>" id="rbi_value" class="frm_input" min="0"> 포인트
                <span class="frm_info">획득 시 지급할 포인트 (0: 미지급)</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rbi_limit_qty">최대 배출 수량</label></th>
            <td>
                <input type="number" name="rbi_limit_qty" value="<?php echo $item['rbi_limit_qty']; ?>" id="rbi_limit_qty" class="frm_input" min="0"> 개
                <span class="frm_info">0 입력 시 무제한</span>
                <?php if ($w == 'u' && $item['rbi_issued_qty'] > 0) : ?>
                <div style="margin-top:5px;color:#e74c3c;">현재 배출된 수량: <?php echo number_format($item['rbi_issued_qty']); ?>개</div>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rbi_status">상태</label></th>
            <td>
                <select name="rbi_status" id="rbi_status" class="frm_input">
                    <option value="1" <?php echo ($item['rbi_status'] == 1) ? 'selected' : ''; ?>>활성</option>
                    <option value="0" <?php echo ($item['rbi_status'] == 0) ? 'selected' : ''; ?>>비활성</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rbi_order">정렬순서</label></th>
            <td>
                <input type="number" name="rbi_order" value="<?php echo $item['rbi_order']; ?>" id="rbi_order" class="frm_input" size="10">
                <span class="frm_info">숫자가 작을수록 먼저 표시됩니다.</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">아이템 설명</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>아이템 설명</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="rbi_desc">설명</label></th>
            <td>
                <?php echo editor_html('rbi_desc', $item['rbi_desc']); ?>
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
            <th scope="row"><label for="rbi_image">아이템 이미지</label></th>
            <td>
                <input type="file" name="rbi_image" id="rbi_image" class="frm_input">
                <?php
                if ($item['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$item['rbi_image'])) {
                    echo '<div style="margin-top:5px;">';
                    echo '<img src="'.G5_DATA_URL.'/randombox/item/'.$item['rbi_image'].'" style="max-width:200px;max-height:200px;">';
                    echo '<br><label><input type="checkbox" name="rbi_image_del" value="1"> 이미지 삭제</label>';
                    echo '</div>';
                }
                ?>
                <div class="frm_info">권장 크기: 300x300px, JPG/PNG/GIF 형식</div>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div class="btn_fixed_top">
    <a href="./item_list.php?rb_id=<?php echo $rb_id; ?>&<?php echo $qstr; ?>" class="btn btn_02">목록</a>
    <input type="submit" value="저장" class="btn_submit btn" accesskey="s">
</div>

</form>

<script>
function fitemform_submit(f) {
    <?php echo get_editor_js('rbi_desc'); ?>
    
    if (!f.rbi_name.value) {
        alert("아이템명을 입력하세요.");
        f.rbi_name.focus();
        return false;
    }
    
    var probability = parseFloat(f.rbi_probability.value);
    if (isNaN(probability) || probability <= 0 || probability > 100) {
        alert("확률은 0보다 크고 100 이하로 입력하세요.");
        f.rbi_probability.focus();
        return false;
    }
    
    var available = <?php echo $available_probability; ?>;
    if (probability > available) {
        alert("사용 가능한 확률(" + available.toFixed(6) + "%)을 초과했습니다.");
        f.rbi_probability.focus();
        return false;
    }
    
    if (f.rbi_limit_qty.value && f.rbi_limit_qty.value > 0) {
        <?php if ($w == 'u' && $item['rbi_issued_qty'] > 0) : ?>
        if (parseInt(f.rbi_limit_qty.value) < <?php echo $item['rbi_issued_qty']; ?>) {
            alert("최대 배출 수량은 이미 배출된 수량(<?php echo $item['rbi_issued_qty']; ?>개)보다 적을 수 없습니다.");
            f.rbi_limit_qty.focus();
            return false;
        }
        <?php endif; ?>
    }
    
    return true;
}

// 등급 선택 시 권장 확률 표시
document.getElementById('rbi_grade').addEventListener('change', function() {
    var grade = this.value;
    var recommended = {
        'normal': '60-80%',
        'rare': '15-30%',
        'epic': '5-10%',
        'legendary': '1-5%'
    };
    
    var info = this.nextElementSibling;
    if (info) {
        info.innerHTML = '천장 시스템 적용 시 레어 이상이 보장됩니다. (권장 확률: ' + recommended[grade] + ')';
    }
});
</script>

<style>
/* 필수 입력 표시 */
.required {
    color: #e74c3c;
    font-weight: bold;
}

/* 폼 스타일 */
.h2_frm {
    margin: 30px 0 10px;
    padding: 10px 0;
    border-bottom: 2px solid #2c3e50;
    font-size: 1.3em;
    color: #2c3e50;
}

.tbl_frm01 th {
    background: #f7f7f7;
}

.frm_info {
    display: inline-block;
    margin-top: 5px;
    color: #666;
    font-size: 0.9em;
}

/* 확률 입력 필드 */
input[name="rbi_probability"] {
    text-align: right;
}
</style>

<?php
include_once('./admin.tail.php');
?>