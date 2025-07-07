<?php
/*
 * 파일명: box_list.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 목록 관리 페이지
 * 작성일: 2025-01-04
 */

$sub_menu = "300920";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '랜덤박스 관리';
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

/* 검색 조건 처리 */
$sql_search = "";

if ($stx) {
    $sql_search .= " AND rb_name LIKE '%{$stx}%' ";
}

if ($sfl == 'rb_status') {
    $sql_search .= " AND rb_status = '{$sfv}' ";
}

if ($sfl == 'rb_type') {
    $sql_search .= " AND rb_type = '{$sfv}' ";
}

// ===================================
// 데이터 조회
// ===================================

/* 전체 개수 - 테이블명 수정 */
$sql = "SELECT COUNT(*) as cnt FROM randombox WHERE 1 {$sql_search}";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

/* 페이징 */
$total_page = ceil($total_count / $rows);
$from_record = ($page - 1) * $rows;

/* 목록 조회 - 테이블명 수정 */
$sql = "SELECT * FROM randombox 
        WHERE 1 {$sql_search} 
        ORDER BY rb_order, rb_id DESC 
        LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01">
        <span class="ov_txt">전체 랜덤박스</span>
        <span class="ov_num"><?php echo number_format($total_count); ?>개</span>
    </span>
</div>

<form name="fsearch" method="get" class="local_sch01 local_sch">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="">전체</option>
    <option value="rb_status" <?php echo ($sfl == 'rb_status') ? 'selected' : ''; ?>>상태</option>
    <option value="rb_type" <?php echo ($sfl == 'rb_type') ? 'selected' : ''; ?>>타입</option>
</select>

<?php if ($sfl == 'rb_status') : ?>
<select name="sfv">
    <option value="">전체</option>
    <option value="1" <?php echo ($sfv == '1') ? 'selected' : ''; ?>>활성</option>
    <option value="0" <?php echo ($sfv == '0') ? 'selected' : ''; ?>>비활성</option>
</select>
<?php elseif ($sfl == 'rb_type') : ?>
<select name="sfv">
    <option value="">전체</option>
    <option value="normal" <?php echo ($sfv == 'normal') ? 'selected' : ''; ?>>일반</option>
    <option value="event" <?php echo ($sfv == 'event') ? 'selected' : ''; ?>>이벤트</option>
    <option value="premium" <?php echo ($sfv == 'premium') ? 'selected' : ''; ?>>프리미엄</option>
</select>
<?php endif; ?>

<label for="stx" class="sound_only">검색어</label>
<input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" class="frm_input" placeholder="박스명 검색">
<input type="submit" value="검색" class="btn_submit">
</form>

<div class="btn_fixed_top">
    <a href="./box_form.php" class="btn btn_01">박스 추가</a>
</div>

<form name="fboxlist" method="post" action="./box_list_update.php" onsubmit="return fboxlist_submit(this);">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>랜덤박스 목록</caption>
    <thead>
    <tr>
        <th scope="col" width="50">
            <label for="chkall" class="sound_only">전체선택</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col" width="60">번호</th>
        <th scope="col" width="80">이미지</th>
        <th scope="col">박스명</th>
        <th scope="col" width="80">타입</th>
        <th scope="col" width="100">가격</th>
        <th scope="col" width="80">판매수량</th>
        <th scope="col" width="80">상태</th>
        <th scope="col" width="150">판매기간</th>
        <th scope="col" width="100">순서</th>
        <th scope="col" width="150">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    while ($row = sql_fetch_array($result)) {
        $bg = ($i % 2) ? 'bg1' : 'bg0';
        
        // 이미지 처리
        $box_img = '';
        if ($row['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$row['rb_image'])) {
            $box_img = '<img src="'.G5_DATA_URL.'/randombox/box/'.$row['rb_image'].'" alt="'.$row['rb_name'].'" style="width:50px;height:50px;object-fit:cover;">';
        } else {
            $box_img = '<span style="display:inline-block;width:50px;height:50px;background:#f0f0f0;text-align:center;line-height:50px;color:#999;">NO</span>';
        }
        
        // 판매기간 표시
        $sale_period = '';
        if ($row['rb_start_date'] || $row['rb_end_date']) {
            $start = $row['rb_start_date'] ? date('Y-m-d', strtotime($row['rb_start_date'])) : '제한없음';
            $end = $row['rb_end_date'] ? date('Y-m-d', strtotime($row['rb_end_date'])) : '제한없음';
            $sale_period = $start . '<br>~' . $end;
        } else {
            $sale_period = '상시판매';
        }
        
        // 상태 표시
        $status_class = $row['rb_status'] ? 'txt_active' : 'txt_inactive';
        $status_text = $row['rb_status'] ? '활성' : '비활성';
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="hidden" name="rb_id[<?php echo $i; ?>]" value="<?php echo $row['rb_id']; ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo $row['rb_name']; ?></label>
            <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
        </td>
        <td class="td_num"><?php echo $row['rb_id']; ?></td>
        <td class="td_img"><?php echo $box_img; ?></td>
        <td class="td_left">
            <a href="./box_form.php?w=u&rb_id=<?php echo $row['rb_id']; ?>&<?php echo $qstr; ?>">
                <strong><?php echo $row['rb_name']; ?></strong>
            </a>
            <?php if ($row['rb_desc']) : ?>
            <div style="margin-top:3px;color:#666;font-size:0.9em;"><?php echo cut_str(strip_tags($row['rb_desc']), 50); ?></div>
            <?php endif; ?>
        </td>
        <td class="td_type">
            <span class="box_type_<?php echo $row['rb_type']; ?>"><?php echo get_box_type_name($row['rb_type']); ?></span>
        </td>
        <td class="td_num"><?php echo number_format($row['rb_price']); ?>P</td>
        <td class="td_num">
            <?php echo number_format($row['rb_sold_qty']); ?>
            <?php if ($row['rb_total_qty'] > 0) : ?>
            <br><span style="color:#666;font-size:0.9em;">/<?php echo number_format($row['rb_total_qty']); ?></span>
            <?php endif; ?>
        </td>
        <td class="td_status">
            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
        </td>
        <td class="td_date"><?php echo $sale_period; ?></td>
        <td class="td_num">
            <input type="text" name="rb_order[<?php echo $i; ?>]" value="<?php echo $row['rb_order']; ?>" class="frm_input" size="3">
        </td>
        <td class="td_mng td_mng_m">
            <a href="./item_list.php?rb_id=<?php echo $row['rb_id']; ?>" class="btn btn_02">아이템관리</a>
            <a href="./box_form.php?w=u&rb_id=<?php echo $row['rb_id']; ?>&<?php echo $qstr; ?>" class="btn btn_03">수정</a>
            <a href="./box_stats.php?rb_id=<?php echo $row['rb_id']; ?>" class="btn btn_04">통계</a>
        </td>
    </tr>
    <?php
        $i++;
    }
    
    if ($i == 0) {
        echo '<tr><td colspan="11" class="empty_table">등록된 랜덤박스가 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value" class="btn btn_01">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_01">
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<script>
function fboxlist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 할 항목을 선택해 주세요.");
        return false;
    }
    
    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 항목을 정말 삭제하시겠습니까?\n\n삭제된 데이터는 복구할 수 없습니다.")) {
            return false;
        }
    }
    
    return true;
}
</script>

<style>
/* 랜덤박스 목록 스타일 */
.td_img { text-align: center; }
.td_type { text-align: center; }
.td_status { text-align: center; }
.td_date { text-align: center; font-size: 0.9em; }

.box_type_normal { 
    display: inline-block; 
    padding: 2px 8px; 
    background: #95a5a6; 
    color: #fff; 
    border-radius: 3px; 
    font-size: 0.9em; 
}
.box_type_event { 
    display: inline-block; 
    padding: 2px 8px; 
    background: #e74c3c; 
    color: #fff; 
    border-radius: 3px; 
    font-size: 0.9em; 
}
.box_type_premium { 
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