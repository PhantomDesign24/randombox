<?php
/*
 * 파일명: box_form.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 등록/수정 폼 페이지
 * 작성일: 2025-01-04
 */

$sub_menu = "300920";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rb_id = (int)$rb_id;
$w = ($w == 'u') ? 'u' : '';

if ($w == 'u') {
    $box = get_randombox($rb_id);
    if (!$box) {
        alert('존재하지 않는 랜덤박스입니다.');
    }
} else {
    $box = array(
        'rb_id' => '',
        'rb_name' => '',
        'rb_desc' => '',
        'rb_price' => 0,
        'rb_image' => '',
        'rb_status' => 1,
        'rb_type' => 'normal',
        'rb_start_date' => '',
        'rb_end_date' => '',
        'rb_limit_qty' => 0,
        'rb_total_qty' => 0,
        'rb_order' => 0
    );
}

$g5['title'] = ($w == 'u') ? '랜덤박스 수정' : '랜덤박스 등록';
include_once('./admin.head.php');
include_once(G5_EDITOR_LIB);
?>

<form name="fboxform" method="post" action="./box_form_update.php" enctype="multipart/form-data" onsubmit="return fboxform_submit(this);">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="rb_id" value="<?php echo $rb_id; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
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
            <th scope="row"><label for="rb_name">박스명</label> <span class="required">필수</span></th>
            <td>
                <input type="text" name="rb_name" value="<?php echo $box['rb_name']; ?>" id="rb_name" class="required frm_input" size="50" maxlength="255" required>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rb_type">박스 타입</label></th>
            <td>
                <select name="rb_type" id="rb_type" class="frm_input">
                    <option value="normal" <?php echo ($box['rb_type'] == 'normal') ? 'selected' : ''; ?>>일반</option>
                    <option value="event" <?php echo ($box['rb_type'] == 'event') ? 'selected' : ''; ?>>이벤트</option>
                    <option value="premium" <?php echo ($box['rb_type'] == 'premium') ? 'selected' : ''; ?>>프리미엄</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rb_price">판매 가격</label> <span class="required">필수</span></th>
            <td>
                <input type="number" name="rb_price" value="<?php echo $box['rb_price']; ?>" id="rb_price" class="required frm_input" min="0" required> 포인트
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rb_status">상태</label></th>
            <td>
                <select name="rb_status" id="rb_status" class="frm_input">
                    <option value="1" <?php echo ($box['rb_status'] == 1) ? 'selected' : ''; ?>>활성</option>
                    <option value="0" <?php echo ($box['rb_status'] == 0) ? 'selected' : ''; ?>>비활성</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rb_order">정렬순서</label></th>
            <td>
                <input type="number" name="rb_order" value="<?php echo $box['rb_order']; ?>" id="rb_order" class="frm_input" size="10">
                <span class="frm_info">숫자가 작을수록 먼저 표시됩니다.</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">박스 설명</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>박스 설명</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="rb_desc">설명</label></th>
            <td>
                <?php echo editor_html('rb_desc', $box['rb_desc']); ?>
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
            <th scope="row"><label for="rb_image">박스 이미지</label></th>
            <td>
                <input type="file" name="rb_image" id="rb_image" class="frm_input">
                <?php
                if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
                    echo '<div style="margin-top:5px;">';
                    echo '<img src="'.G5_DATA_URL.'/randombox/box/'.$box['rb_image'].'" style="max-width:200px;max-height:200px;">';
                    echo '<br><label><input type="checkbox" name="rb_image_del" value="1"> 이미지 삭제</label>';
                    echo '</div>';
                }
                ?>
                <div class="frm_info">권장 크기: 400x400px, JPG/PNG/GIF 형식</div>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">판매 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>판매 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">판매 기간</th>
            <td>
                <label for="rb_start_date">시작일</label>
                <input type="text" name="rb_start_date" value="<?php echo $box['rb_start_date']; ?>" id="rb_start_date" class="frm_input" size="21" maxlength="19">
                ~
                <label for="rb_end_date">종료일</label>
                <input type="text" name="rb_end_date" value="<?php echo $box['rb_end_date']; ?>" id="rb_end_date" class="frm_input" size="21" maxlength="19">
                <div class="frm_info">미입력 시 상시 판매. 형식: YYYY-MM-DD HH:MM:SS</div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rb_limit_qty">일일 구매 제한</label></th>
            <td>
                <input type="number" name="rb_limit_qty" value="<?php echo $box['rb_limit_qty']; ?>" id="rb_limit_qty" class="frm_input" min="0"> 개
                <span class="frm_info">0 입력 시 무제한</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="rb_total_qty">전체 판매 수량</label></th>
            <td>
                <input type="number" name="rb_total_qty" value="<?php echo $box['rb_total_qty']; ?>" id="rb_total_qty" class="frm_input" min="0"> 개
                <span class="frm_info">0 입력 시 무제한</span>
                <?php if ($w == 'u' && $box['rb_sold_qty'] > 0) : ?>
                <div style="margin-top:5px;color:#e74c3c;">현재 판매된 수량: <?php echo number_format($box['rb_sold_qty']); ?>개</div>
                <?php endif; ?>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div class="btn_fixed_top">
    <a href="./box_list.php?<?php echo $qstr; ?>" class="btn btn_02">목록</a>
    <?php if ($w == 'u') : ?>
    <a href="./item_list.php?rb_id=<?php echo $rb_id; ?>" class="btn btn_02">아이템 관리</a>
    <?php endif; ?>
    <input type="submit" value="저장" class="btn_submit btn" accesskey="s">
</div>

</form>

<script>
function fboxform_submit(f) {
    <?php echo get_editor_js('rb_desc'); ?>
    
    if (!f.rb_name.value) {
        alert("박스명을 입력하세요.");
        f.rb_name.focus();
        return false;
    }
    
    if (!f.rb_price.value || f.rb_price.value < 0) {
        alert("판매 가격을 올바르게 입력하세요.");
        f.rb_price.focus();
        return false;
    }
    
    // 날짜 형식 검증
    if (f.rb_start_date.value) {
        if (!isValidDateTime(f.rb_start_date.value)) {
            alert("시작일 형식이 올바르지 않습니다.\nYYYY-MM-DD HH:MM:SS 형식으로 입력하세요.");
            f.rb_start_date.focus();
            return false;
        }
    }
    
    if (f.rb_end_date.value) {
        if (!isValidDateTime(f.rb_end_date.value)) {
            alert("종료일 형식이 올바르지 않습니다.\nYYYY-MM-DD HH:MM:SS 형식으로 입력하세요.");
            f.rb_end_date.focus();
            return false;
        }
    }
    
    if (f.rb_start_date.value && f.rb_end_date.value) {
        if (f.rb_start_date.value >= f.rb_end_date.value) {
            alert("종료일은 시작일보다 이후여야 합니다.");
            f.rb_end_date.focus();
            return false;
        }
    }
    
    return true;
}

function isValidDateTime(datetime) {
    var pattern = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;
    return pattern.test(datetime);
}

// 달력 UI (jQuery UI Datepicker 사용 시)
$(function() {
    $("#rb_start_date, #rb_end_date").datetimepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        timeFormat: "HH:mm:ss",
        showTimepicker: true,
        controlType: 'select',
        oneLine: true
    });
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
</style>

<?php
include_once('./admin.tail.php');
?>