<?php
/*
 * 파일명: coupon_list.php
 * 위치: /adm/randombox_admin/
 * 기능: 교환권 타입 목록 관리
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300950";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '교환권 관리';
include_once('./admin.head.php');

// ===================================
// 리스트 설정
// ===================================

/* 페이지 설정 */
$page = (int)$page;
if ($page < 1) $page = 1;

$rows = 20;
$total_count = 0;

// ===================================
// 검색 조건
// ===================================

$sql_search = "";

if ($stx) {
    $sql_search .= " AND (rct_name LIKE '%{$stx}%' OR rct_exchange_item LIKE '%{$stx}%') ";
}

if ($sfl == 'rct_type') {
    $sql_search .= " AND rct_type = '{$sfv}' ";
}

if ($sfl == 'rct_status') {
    $sql_search .= " AND rct_status = '{$sfv}' ";
}

// ===================================
// 데이터 조회
// ===================================

/* 전체 개수 */
$sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_coupon_types WHERE 1 {$sql_search}";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

/* 페이징 */
$total_page = ceil($total_count / $rows);
$from_record = ($page - 1) * $rows;

/* 목록 조회 */
$sql = "SELECT ct.*, 
        (SELECT COUNT(*) FROM {$g5['g5_prefix']}randombox_coupon_codes WHERE rct_id = ct.rct_id) as total_codes,
        (SELECT COUNT(*) FROM {$g5['g5_prefix']}randombox_coupon_codes WHERE rct_id = ct.rct_id AND rcc_status = 'available') as available_codes,
        (SELECT COUNT(*) FROM {$g5['g5_prefix']}randombox_member_coupons WHERE rct_id = ct.rct_id AND rmc_status = 'active') as active_coupons
        FROM {$g5['g5_prefix']}randombox_coupon_types ct
        WHERE 1 {$sql_search} 
        ORDER BY rct_id DESC 
        LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01">
        <span class="ov_txt">전체 교환권 타입</span>
        <span class="ov_num"><?php echo number_format($total_count); ?>개</span>
    </span>
</div>

<form name="fsearch" method="get" class="local_sch01 local_sch">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="">전체</option>
    <option value="rct_type" <?php echo ($sfl == 'rct_type') ? 'selected' : ''; ?>>타입</option>
    <option value="rct_status" <?php echo ($sfl == 'rct_status') ? 'selected' : ''; ?>>상태</option>
</select>

<?php if ($sfl == 'rct_type') : ?>
<select name="sfv">
    <option value="">전체</option>
    <option value="exchange" <?php echo ($sfv == 'exchange') ? 'selected' : ''; ?>>교환용</option>
    <option value="gifticon" <?php echo ($sfv == 'gifticon') ? 'selected' : ''; ?>>기프티콘</option>
</select>
<?php elseif ($sfl == 'rct_status') : ?>
<select name="sfv">
    <option value="">전체</option>
    <option value="1" <?php echo ($sfv == '1') ? 'selected' : ''; ?>>활성</option>
    <option value="0" <?php echo ($sfv == '0') ? 'selected' : ''; ?>>비활성</option>
</select>
<?php endif; ?>

<label for="stx" class="sound_only">검색어</label>
<input type="text" name="stx" id="stx" value="<?php echo $stx; ?>" class="frm_input">
<input type="submit" value="검색" class="btn_submit">
</form>

<div class="btn_fixed_top">
    <a href="./coupon_form.php" class="btn btn_01">교환권 타입 추가</a>
</div>

<form name="fcouponlist" method="post" action="./coupon_list_update.php" onsubmit="return fcouponlist_submit(this);">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>교환권 타입 목록</caption>
    <thead>
    <tr>
        <th scope="col" width="50">
            <label for="chkall" class="sound_only">전체선택</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col" width="60">번호</th>
        <th scope="col" width="80">이미지</th>
        <th scope="col">교환권명</th>
        <th scope="col" width="100">타입</th>
        <th scope="col" width="100">가치</th>
        <th scope="col">교환 상품</th>
        <th scope="col" width="120">코드 현황</th>
        <th scope="col" width="100">보유 회원</th>
        <th scope="col" width="80">상태</th>
        <th scope="col" width="100">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    while ($row = sql_fetch_array($result)) {
        $bg = ($i % 2) ? 'bg1' : 'bg0';
        
        // 이미지 처리
        $coupon_img = '';
        if ($row['rct_image'] && file_exists(G5_DATA_PATH.'/randombox/coupon/'.$row['rct_image'])) {
            $coupon_img = '<img src="'.G5_DATA_URL.'/randombox/coupon/'.$row['rct_image'].'" alt="'.$row['rct_name'].'" style="width:50px;height:50px;object-fit:cover;">';
        } else {
            $coupon_img = '<span style="display:inline-block;width:50px;height:50px;background:#f0f0f0;text-align:center;line-height:50px;color:#999;font-size:0.8em;">NO</span>';
        }
        
        // 타입 표시
        $type_text = ($row['rct_type'] == 'gifticon') ? '기프티콘' : '교환용';
        $type_class = ($row['rct_type'] == 'gifticon') ? 'txt_gifticon' : 'txt_exchange';
        
        // 코드 현황
        $code_status = '';
        if ($row['rct_type'] == 'gifticon') {
            $code_status = $row['available_codes'] . ' / ' . $row['total_codes'];
            if ($row['available_codes'] == 0) {
                $code_status = '<span style="color:#e74c3c;">' . $code_status . '</span>';
            }
        } else {
            $code_status = '-';
        }
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="checkbox" name="chk[]" value="<?php echo $row['rct_id']; ?>" id="chk_<?php echo $i; ?>">
        </td>
        <td class="td_num"><?php echo $row['rct_id']; ?></td>
        <td class="td_img"><?php echo $coupon_img; ?></td>
        <td class="td_left">
            <strong><?php echo $row['rct_name']; ?></strong>
            <?php if ($row['rct_desc']) : ?>
            <div style="font-size:0.9em;color:#666;margin-top:3px;"><?php echo cut_str(strip_tags($row['rct_desc']), 50); ?></div>
            <?php endif; ?>
        </td>
        <td class="td_type">
            <span class="<?php echo $type_class; ?>"><?php echo $type_text; ?></span>
        </td>
        <td class="td_num"><?php echo number_format($row['rct_value']); ?>P</td>
        <td class="td_left"><?php echo $row['rct_exchange_item']; ?></td>
        <td class="td_num"><?php echo $code_status; ?></td>
        <td class="td_num"><?php echo number_format($row['active_coupons']); ?>명</td>
        <td class="td_status">
            <?php if ($row['rct_status']) : ?>
                <span class="txt_active">활성</span>
            <?php else : ?>
                <span class="txt_inactive">비활성</span>
            <?php endif; ?>
        </td>
        <td class="td_mng">
            <a href="./coupon_form.php?w=u&rct_id=<?php echo $row['rct_id']; ?>&<?php echo $qstr; ?>" class="btn btn_03">수정</a>
            <?php if ($row['rct_type'] == 'gifticon') : ?>
            <a href="./coupon_code_list.php?rct_id=<?php echo $row['rct_id']; ?>&<?php echo $qstr; ?>" class="btn btn_02">코드관리</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php
        $i++;
    }
    
    if ($i == 0) {
        echo '<tr><td colspan="11" class="empty_table">등록된 교환권 타입이 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_02">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<script>
function fcouponlist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 할 항목을 선택해 주세요.");
        return false;
    }
    
    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 교환권 타입을 정말 삭제하시겠습니까?\n\n관련된 모든 데이터가 삭제됩니다.")) {
            return false;
        }
    }
    
    return true;
}
</script>

<style>
/* 교환권 목록 스타일 */
.td_img { text-align: center; }
.td_type { text-align: center; }
.td_status { text-align: center; }

.txt_exchange { 
    display: inline-block; 
    padding: 2px 8px; 
    background: #3498db; 
    color: #fff; 
    border-radius: 3px; 
    font-size: 0.9em; 
}
.txt_gifticon { 
    display: inline-block; 
    padding: 2px 8px; 
    background: #f39c12; 
    color: #fff; 
    border-radius: 3px; 
    font-size: 0.9em; 
}

.txt_active { color: #27ae60; font-weight: bold; }
.txt_inactive { color: #e74c3c; }

.td_mng .btn { padding: 3px 8px; font-size: 0.9em; margin: 1px; }
</style>

<?php
include_once('./admin.tail.php');
?>