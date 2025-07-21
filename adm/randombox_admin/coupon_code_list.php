<?php
/*
 * 파일명: coupon_code_list.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 코드 관리 페이지
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

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

$g5['title'] = '교환권 코드 관리 - ' . $coupon_type['rct_name'];
include_once('./admin.head.php');

// ===================================
// 리스트 설정
// ===================================

/* 페이지 설정 */
$page = (int)$page;
if ($page < 1) $page = 1;

$rows = 30;
$total_count = 0;

// ===================================
// 검색 조건
// ===================================

$sql_search = " WHERE rct_id = '{$rct_id}' ";

if ($stx) {
    $sql_search .= " AND (rcc_code LIKE '%{$stx}%' OR rcc_pin LIKE '%{$stx}%') ";
}

if ($sfl == 'rcc_status') {
    $sql_search .= " AND rcc_status = '{$sfv}' ";
}

// ===================================
// 통계 데이터
// ===================================

$sql = "SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN rcc_status = 'available' THEN 1 ELSE 0 END) as available_count,
        SUM(CASE WHEN rcc_status = 'used' THEN 1 ELSE 0 END) as used_count,
        SUM(CASE WHEN rcc_status = 'expired' THEN 1 ELSE 0 END) as expired_count
        FROM {$g5['g5_prefix']}randombox_coupon_codes
        {$sql_search}";
$stats = sql_fetch($sql);
$total_count = $stats['total_count'];

// ===================================
// 데이터 조회
// ===================================

/* 페이징 */
$total_page = ceil($total_count / $rows);
$from_record = ($page - 1) * $rows;

/* 목록 조회 */
$sql = "SELECT c.*, m.mb_nick, m.mb_name 
        FROM {$g5['g5_prefix']}randombox_coupon_codes c
        LEFT JOIN {$g5['member_table']} m ON c.rcc_used_by = m.mb_id
        {$sql_search} 
        ORDER BY c.rcc_id DESC 
        LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01">
        <span class="ov_txt">교환권명</span>
        <span class="ov_num"><?php echo $coupon_type['rct_name']; ?></span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">전체 코드</span>
        <span class="ov_num"><?php echo number_format($stats['total_count']); ?>개</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">사용가능</span>
        <span class="ov_num" style="color:#27ae60;"><?php echo number_format($stats['available_count']); ?>개</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">사용완료</span>
        <span class="ov_num"><?php echo number_format($stats['used_count']); ?>개</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">만료</span>
        <span class="ov_num" style="color:#e74c3c;"><?php echo number_format($stats['expired_count']); ?>개</span>
    </span>
</div>

<form name="fsearch" method="get" class="local_sch01 local_sch">
<input type="hidden" name="rct_id" value="<?php echo $rct_id; ?>">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="">전체</option>
    <option value="rcc_status" <?php echo ($sfl == 'rcc_status') ? 'selected' : ''; ?>>상태</option>
</select>

<?php if ($sfl == 'rcc_status') : ?>
<select name="sfv">
    <option value="">전체</option>
    <option value="available" <?php echo ($sfv == 'available') ? 'selected' : ''; ?>>사용가능</option>
    <option value="used" <?php echo ($sfv == 'used') ? 'selected' : ''; ?>>사용완료</option>
    <option value="expired" <?php echo ($sfv == 'expired') ? 'selected' : ''; ?>>만료</option>
</select>
<?php endif; ?>

<label for="stx" class="sound_only">검색어</label>
<input type="text" name="stx" id="stx" value="<?php echo $stx; ?>" class="frm_input" placeholder="코드 또는 PIN">
<input type="submit" value="검색" class="btn_submit">
</form>

<div class="btn_fixed_top">
    <a href="./coupon_list.php?<?php echo $qstr; ?>" class="btn btn_02">교환권 목록</a>
    <a href="./coupon_code_upload.php?rct_id=<?php echo $rct_id; ?>" class="btn btn_01">코드 일괄등록</a>
</div>

<form name="fcodelist" method="post" action="./coupon_code_list_update.php" onsubmit="return fcodelist_submit(this);">
<input type="hidden" name="rct_id" value="<?php echo $rct_id; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>교환권 코드 목록</caption>
    <thead>
    <tr>
        <th scope="col" width="50">
            <label for="chkall" class="sound_only">전체선택</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col" width="60">번호</th>
        <th scope="col">코드</th>
        <th scope="col">PIN</th>
        <th scope="col" width="100">유효기간</th>
        <th scope="col" width="80">상태</th>
        <th scope="col" width="100">사용자</th>
        <th scope="col" width="130">사용일시</th>
        <th scope="col" width="100">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    while ($row = sql_fetch_array($result)) {
        $bg = ($i % 2) ? 'bg1' : 'bg0';
        
        // 상태 표시
        $status_text = '';
        $status_class = '';
        switch ($row['rcc_status']) {
            case 'available':
                $status_text = '사용가능';
                $status_class = 'txt_active';
                break;
            case 'used':
                $status_text = '사용완료';
                $status_class = 'txt_used';
                break;
            case 'expired':
                $status_text = '만료';
                $status_class = 'txt_expired';
                break;
        }
        
        // 유효기간 체크
        if ($row['rcc_expire_date'] && $row['rcc_expire_date'] < G5_TIME_YMD && $row['rcc_status'] == 'available') {
            // 만료 처리
            sql_query("UPDATE {$g5['g5_prefix']}randombox_coupon_codes SET rcc_status = 'expired' WHERE rcc_id = '{$row['rcc_id']}'");
            $status_text = '만료';
            $status_class = 'txt_expired';
        }
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="checkbox" name="chk[]" value="<?php echo $row['rcc_id']; ?>" id="chk_<?php echo $i; ?>">
        </td>
        <td class="td_num"><?php echo $row['rcc_id']; ?></td>
        <td class="td_left">
            <input type="text" value="<?php echo $row['rcc_code']; ?>" class="frm_input" readonly style="width:90%;" onclick="this.select()">
        </td>
        <td class="td_left">
            <?php if ($row['rcc_pin']) : ?>
            <input type="text" value="<?php echo $row['rcc_pin']; ?>" class="frm_input" readonly style="width:90%;" onclick="this.select()">
            <?php else : ?>
            -
            <?php endif; ?>
        </td>
        <td class="td_date">
            <?php echo $row['rcc_expire_date'] ? $row['rcc_expire_date'] : '무기한'; ?>
        </td>
        <td class="td_status">
            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
        </td>
        <td class="td_name">
            <?php if ($row['rcc_used_by']) : ?>
                <?php echo $row['mb_nick'] ? $row['mb_nick'] : $row['rcc_used_by']; ?>
            <?php else : ?>
            -
            <?php endif; ?>
        </td>
        <td class="td_datetime">
            <?php echo $row['rcc_used_at'] ? $row['rcc_used_at'] : '-'; ?>
        </td>
        <td class="td_mng">
            <?php if ($row['rcc_status'] == 'available') : ?>
            <button type="button" onclick="expireCode(<?php echo $row['rcc_id']; ?>)" class="btn btn_03">만료처리</button>
            <?php endif; ?>
        </td>
    </tr>
    <?php
        $i++;
    }
    
    if ($i == 0) {
        echo '<tr><td colspan="9" class="empty_table">등록된 코드가 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_02">
    <input type="submit" name="act_button" value="선택만료처리" onclick="document.pressed=this.value" class="btn btn_02">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?rct_id='.$rct_id.'&'.$qstr.'&amp;page='); ?>

<script>
function fcodelist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 할 항목을 선택해 주세요.");
        return false;
    }
    
    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 코드를 정말 삭제하시겠습니까?\n\n삭제된 코드는 복구할 수 없습니다.")) {
            return false;
        }
    }
    
    if (document.pressed == "선택만료처리") {
        if (!confirm("선택한 코드를 만료 처리하시겠습니까?")) {
            return false;
        }
    }
    
    return true;
}

function expireCode(rcc_id) {
    if (!confirm("이 코드를 만료 처리하시겠습니까?")) {
        return;
    }
    
    location.href = './coupon_code_update.php?mode=expire&rcc_id=' + rcc_id + '&rct_id=<?php echo $rct_id; ?>';
}
</script>

<style>
/* 코드 목록 스타일 */
.td_status { text-align: center; }
.td_date { text-align: center; font-size: 0.9em; }
.td_datetime { text-align: center; font-size: 0.9em; }
.td_name { text-align: center; }

.txt_active { color: #27ae60; font-weight: bold; }
.txt_used { color: #666; }
.txt_expired { color: #e74c3c; }

.td_mng .btn { padding: 3px 8px; font-size: 0.9em; }
</style>

<?php
include_once('./admin.tail.php');
?>