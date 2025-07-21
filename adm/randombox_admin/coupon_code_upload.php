<?php
/*
 * 파일명: coupon_code_upload.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 코드 일괄 업로드
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'w');

$rct_id = (int)$rct_id;
if (!$rct_id) {
    alert('교환권 타입을 선택해 주세요.', './coupon_list.php');
}

// 교환권 타입 정보
$sql = "SELECT * FROM {$g5['g5_prefix']}randombox_coupon_types WHERE rct_id = '$rct_id'";
$coupon_type = sql_fetch($sql);
if (!$coupon_type) {
    alert('존재하지 않는 교환권 타입입니다.', './coupon_list.php');
}

// 기프티콘 타입만 코드 관리 가능
if ($coupon_type['rct_type'] != 'gifticon') {
    alert('기프티콘 타입만 코드를 관리할 수 있습니다.', './coupon_list.php');
}

$g5['title'] = '코드 일괄등록 - ' . $coupon_type['rct_name'];
include_once('./admin.head.php');

// ===================================
// 업로드 처리
// ===================================

if ($_POST['mode'] == 'upload') {
    
    $codes = trim($_POST['codes']);
    $expire_date = $_POST['expire_date'];
    
    if (!$codes) {
        alert('코드를 입력해 주세요.');
    }
    
    // 날짜 형식 검증
    if ($expire_date && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $expire_date)) {
        alert('유효기간 형식이 올바르지 않습니다.');
    }
    
    // 코드 파싱
    $lines = explode("\n", $codes);
    $success_count = 0;
    $fail_count = 0;
    $duplicates = array();
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        
        // 탭이나 쉼표로 구분된 경우 처리 (코드, PIN)
        $parts = preg_split('/[\t,]/', $line);
        $code = trim($parts[0]);
        $pin = isset($parts[1]) ? trim($parts[1]) : '';
        
        if (!$code) continue;
        
        // 중복 체크
        $sql = "SELECT rcc_id FROM {$g5['g5_prefix']}randombox_coupon_codes 
                WHERE rct_id = '{$rct_id}' AND rcc_code = '{$code}'";
        $exists = sql_fetch($sql);
        
        if ($exists) {
            $duplicates[] = $code;
            $fail_count++;
            continue;
        }
        
        // 코드 등록
        $sql = "INSERT INTO {$g5['g5_prefix']}randombox_coupon_codes SET
                rct_id = '{$rct_id}',
                rcc_code = '{$code}',
                rcc_pin = '{$pin}',
                rcc_expire_date = " . ($expire_date ? "'{$expire_date}'" : "NULL") . ",
                rcc_status = 'available',
                rcc_created_at = NOW()";
        
        if (sql_query($sql, false)) {
            $success_count++;
        } else {
            $fail_count++;
        }
    }
    
    $msg = "코드 등록이 완료되었습니다.\\n\\n";
    $msg .= "성공: {$success_count}개\\n";
    $msg .= "실패: {$fail_count}개";
    
    if (count($duplicates) > 0) {
        $msg .= "\\n\\n중복 코드: " . implode(', ', array_slice($duplicates, 0, 5));
        if (count($duplicates) > 5) {
            $msg .= " 외 " . (count($duplicates) - 5) . "개";
        }
    }
    
    alert($msg, './coupon_code_list.php?rct_id='.$rct_id);
}

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_desc01 local_desc">
    <p>
        교환권 코드를 일괄로 등록합니다.<br>
        한 줄에 하나의 코드를 입력하며, 코드와 PIN이 있는 경우 탭(Tab) 또는 쉼표(,)로 구분합니다.<br>
        예시: ABCD-1234-5678 또는 ABCD-1234-5678,1234 (PIN이 있는 경우)
    </p>
</div>

<form name="fupload" method="post" onsubmit="return fupload_submit(this);">
<input type="hidden" name="mode" value="upload">
<input type="hidden" name="rct_id" value="<?php echo $rct_id; ?>">

<section>
    <h2 class="h2_frm">코드 입력</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>코드 입력</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">교환권 타입</th>
            <td><?php echo $coupon_type['rct_name']; ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="codes">코드 입력</label> <span class="required">필수</span></th>
            <td>
                <textarea name="codes" id="codes" rows="20" class="frm_input" style="width:100%;" required placeholder="한 줄에 하나씩 입력하세요.

예시1) 코드만 입력
ABCD-1234-5678
EFGH-9012-3456

예시2) 코드와 PIN 입력 (탭 또는 쉼표로 구분)
ABCD-1234-5678	1234
EFGH-9012-3456,5678"></textarea>
                <div class="frm_info">
                    - 한 줄에 하나의 코드를 입력하세요.<br>
                    - PIN이 있는 경우 코드 뒤에 탭(Tab) 또는 쉼표(,)로 구분하여 입력하세요.<br>
                    - 중복된 코드는 자동으로 제외됩니다.
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="expire_date">유효기간</label></th>
            <td>
                <input type="text" name="expire_date" id="expire_date" class="frm_input" size="12" maxlength="10" placeholder="YYYY-MM-DD">
                <span class="frm_info">비워두면 무기한으로 설정됩니다.</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="일괄등록" class="btn_submit" accesskey="s">
    <a href="./coupon_code_list.php?rct_id=<?php echo $rct_id; ?>" class="btn btn_02">목록</a>
</div>

</form>

<section>
    <h2 class="h2_frm">CSV 파일 업로드 (선택사항)</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>CSV 파일 업로드</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">CSV 파일 형식</th>
            <td>
                <div style="background:#f5f5f5;padding:10px;font-family:monospace;font-size:13px;">
                    코드,PIN,유효기간<br>
                    ABCD-1234-5678,1234,2025-12-31<br>
                    EFGH-9012-3456,5678,2025-12-31<br>
                    IJKL-3456-7890,,  (PIN과 유효기간 생략 가능)
                </div>
                <div class="frm_info">
                    - 첫 줄은 헤더로 무시됩니다.<br>
                    - PIN과 유효기간은 선택사항입니다.
                </div>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<script>
function fupload_submit(f) {
    if (!f.codes.value.trim()) {
        alert("코드를 입력해 주세요.");
        f.codes.focus();
        return false;
    }
    
    var lines = f.codes.value.trim().split('\n');
    var valid_count = 0;
    
    for (var i = 0; i < lines.length; i++) {
        if (lines[i].trim()) {
            valid_count++;
        }
    }
    
    if (valid_count == 0) {
        alert("유효한 코드를 입력해 주세요.");
        f.codes.focus();
        return false;
    }
    
    if (!confirm(valid_count + "개의 코드를 등록하시겠습니까?")) {
        return false;
    }
    
    return true;
}

// 날짜 선택기
$(function() {
    $("#expire_date").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: 0
    });
});
</script>

<!-- jQuery UI CSS -->
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<?php
include_once('./admin.tail.php');
?>