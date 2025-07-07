<?php
/*
 * 파일명: grade_form.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 등급 등록/수정 폼
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300915";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rbg_id = (int)$rbg_id;
$w = ($w == 'u') ? 'u' : '';

if ($w == 'u') {
    $sql = "SELECT * FROM {$g5['g5_prefix']}randombox_grades WHERE rbg_id = '$rbg_id'";
    $grade = sql_fetch($sql);
    if (!$grade) {
        alert('존재하지 않는 등급입니다.');
    }
    
    // 기본 등급은 키 수정 불가 (하지만 다른 정보는 수정 가능)
    $is_default = in_array($grade['rbg_key'], array('normal', 'rare', 'epic', 'legendary'));
} else {
    $grade = array(
        'rbg_id' => '',
        'rbg_key' => '',
        'rbg_name' => '',
        'rbg_color' => '#000000',
        'rbg_icon' => '',
        'rbg_image' => '',
        'rbg_level' => 1,
        'rbg_order' => 0
    );
    $is_default = false;
}

/* 희귀 등급 최소 레벨 */
$rare_min_level = get_randombox_config('rare_min_level');
if (!$rare_min_level) $rare_min_level = 2;

$g5['title'] = ($w == 'u') ? '등급 수정' : '등급 등록';
include_once('./admin.head.php');
?>

<form name="fgradeform" method="post" action="./grade_form_update.php" enctype="multipart/form-data" onsubmit="return fgradeform_submit(this);">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="rbg_id" value="<?php echo $rbg_id; ?>">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row"><label for="rbg_key">등급 키</label> <span class="required">필수</span></th>
        <td>
            <input type="text" name="rbg_key" value="<?php echo $grade['rbg_key']; ?>" id="rbg_key" class="required frm_input" size="20" maxlength="20" required <?php echo ($w == 'u' && $is_default) ? 'readonly' : ''; ?>>
            <span class="frm_info">영문 소문자, 숫자, 언더바(_)만 사용 가능. <?php echo ($w == 'u' && $is_default) ? '기본 등급은 키 수정 불가' : ''; ?></span>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="rbg_name">등급명</label> <span class="required">필수</span></th>
        <td>
            <input type="text" name="rbg_name" value="<?php echo $grade['rbg_name']; ?>" id="rbg_name" class="required frm_input" size="30" maxlength="50" required>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="rbg_level">희귀도 레벨</label> <span class="required">필수</span></th>
        <td>
            <input type="number" name="rbg_level" value="<?php echo $grade['rbg_level']; ?>" id="rbg_level" class="required frm_input" min="1" max="99" required>
            <span class="frm_info">1부터 시작, 숫자가 높을수록 희귀한 등급 (현재 희귀 등급 기준: 레벨 <?php echo $rare_min_level; ?> 이상)</span>
            <div id="rareLevelInfo" style="margin-top:10px;padding:10px;background:#f8f9fa;border-radius:5px;display:none;">
                <strong>현재 레벨 상태:</strong> <span id="levelStatus"></span>
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="rbg_color">등급 색상</label> <span class="required">필수</span></th>
        <td>
            <input type="color" name="rbg_color" value="<?php echo $grade['rbg_color']; ?>" id="rbg_color" class="required frm_input" required>
            <span class="frm_info">등급을 나타낼 대표 색상</span>
        </td>
    </tr>
    <tr>
        <th scope="row">시각 표시</th>
        <td>
            <div style="margin-bottom:15px;">
                <h4 style="margin:0 0 10px;font-size:14px;">1. 이미지 (우선 표시)</h4>
                <input type="file" name="rbg_image" id="rbg_image" class="frm_input">
                <?php
                if ($grade['rbg_image'] && file_exists(G5_DATA_PATH.'/randombox/grade/'.$grade['rbg_image'])) {
                    echo '<div style="margin-top:10px;">';
                    echo '<img src="'.G5_DATA_URL.'/randombox/grade/'.$grade['rbg_image'].'" style="max-width:50px;max-height:50px;border:1px solid #ddd;padding:5px;">';
                    echo '<br><label style="margin-top:5px;display:inline-block;"><input type="checkbox" name="rbg_image_del" value="1"> 이미지 삭제</label>';
                    echo '</div>';
                }
                ?>
                <span class="frm_info">권장 크기: 50x50px, PNG/JPG/GIF 형식</span>
            </div>
            
            <div>
                <h4 style="margin:0 0 10px;font-size:14px;">2. 아이콘 (이미지가 없을 때 표시)</h4>
                <input type="text" name="rbg_icon" value="<?php echo $grade['rbg_icon']; ?>" id="rbg_icon" class="frm_input" size="30" maxlength="50">
                <button type="button" class="btn_frmline" onclick="showIconPreview()">아이콘 미리보기</button>
                <span id="iconPreview" style="margin-left:10px;font-size:24px;"></span>
                <br>
                <span class="frm_info">Bootstrap Icons 클래스명 (예: bi-gem)</span>
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="rbg_order">정렬순서</label></th>
        <td>
            <input type="number" name="rbg_order" value="<?php echo $grade['rbg_order']; ?>" id="rbg_order" class="frm_input" size="10">
            <span class="frm_info">숫자가 작을수록 먼저 표시됩니다.</span>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./grade_list.php" class="btn btn_02">목록</a>
    <input type="submit" value="저장" class="btn_submit btn" accesskey="s">
</div>

</form>

<script>
function fgradeform_submit(f) {
    if (!f.rbg_key.value) {
        alert("등급 키를 입력하세요.");
        f.rbg_key.focus();
        return false;
    }
    
    // 등급 키 유효성 검사 (신규 등록시만)
    <?php if ($w == '' || ($w == 'u' && !$is_default)) : ?>
    var key_pattern = /^[a-z0-9_]+$/;
    if (!key_pattern.test(f.rbg_key.value)) {
        alert("등급 키는 영문 소문자, 숫자, 언더바(_)만 사용 가능합니다.");
        f.rbg_key.focus();
        return false;
    }
    <?php endif; ?>
    
    if (!f.rbg_name.value) {
        alert("등급명을 입력하세요.");
        f.rbg_name.focus();
        return false;
    }
    
    if (!f.rbg_level.value || f.rbg_level.value < 1) {
        alert("희귀도 레벨을 1 이상으로 입력하세요.");
        f.rbg_level.focus();
        return false;
    }
    
    return true;
}

function showIconPreview() {
    var iconClass = document.getElementById('rbg_icon').value;
    var preview = document.getElementById('iconPreview');
    
    if (iconClass) {
        preview.className = iconClass;
        preview.style.color = document.getElementById('rbg_color').value;
    } else {
        preview.className = '';
        preview.textContent = '아이콘 클래스를 입력하세요';
        preview.style.color = '#999';
        preview.style.fontSize = '14px';
    }
}

// 색상 변경시 아이콘 미리보기 색상도 변경
document.getElementById('rbg_color').addEventListener('change', function() {
    var preview = document.getElementById('iconPreview');
    if (preview.className) {
        preview.style.color = this.value;
    }
});

// 희귀도 레벨 변경시 상태 표시
function updateLevelStatus() {
    var level = parseInt(document.getElementById('rbg_level').value) || 1;
    var rareMinLevel = <?php echo $rare_min_level; ?>;
    var infoDiv = document.getElementById('rareLevelInfo');
    var statusSpan = document.getElementById('levelStatus');
    
    infoDiv.style.display = 'block';
    
    if (level >= rareMinLevel) {
        statusSpan.innerHTML = '<span style="color:#e74c3c;font-weight:bold;">희귀 등급</span> (천장 시스템 적용, 실시간 당첨 표시)';
        infoDiv.style.background = '#fee';
    } else {
        statusSpan.innerHTML = '<span style="color:#27ae60;">일반 등급</span> (천장 시스템 미적용)';
        infoDiv.style.background = '#efe';
    }
}

document.getElementById('rbg_level').addEventListener('input', updateLevelStatus);
document.getElementById('rbg_level').addEventListener('change', updateLevelStatus);

// 페이지 로드시 레벨 상태 표시
<?php if ($w == 'u') : ?>
updateLevelStatus();
<?php endif; ?>
</script>

<style>
/* 필수 입력 표시 */
.required {
    color: #e74c3c;
    font-weight: bold;
}

/* 색상 입력 필드 */
input[type="color"] {
    width: 80px;
    height: 35px;
    cursor: pointer;
    vertical-align: middle;
}

/* 아이콘 미리보기 */
#iconPreview {
    vertical-align: middle;
}

.btn_frmline {
    padding: 5px 10px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
}

.btn_frmline:hover {
    background: #e9ecef;
}

/* 파일 입력 */
input[type="file"] {
    padding: 5px;
    border: 1px solid #ddd;
    background: #fff;
}
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<?php
include_once('./admin.tail.php');
?>