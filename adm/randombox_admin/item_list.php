<?php
/*
 * 파일명: item_list.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 아이템 목록 관리 페이지
 * 작성일: 2025-01-04
 */

$sub_menu = "300930";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$rb_id = (int)$rb_id;
if (!$rb_id) {
    alert('박스를 선택해 주세요.', './box_list.php');
}

// 박스 정보 조회
$box = get_randombox($rb_id);
if (!$box) {
    alert('존재하지 않는 박스입니다.', './box_list.php');
}

$g5['title'] = '아이템 관리 - ' . $box['rb_name'];
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
// 데이터 조회
// ===================================

/* 전체 개수 및 확률 합계 */
$sql = "SELECT COUNT(*) as cnt, SUM(rbi_probability) as total_prob 
        FROM {$g5['g5_prefix']}randombox_items 
        WHERE rb_id = '{$rb_id}'";
$row = sql_fetch($sql);
$total_count = $row['cnt'];
$total_probability = $row['total_prob'];

/* 페이징 */
$total_page = ceil($total_count / $rows);
$from_record = ($page - 1) * $rows;

/* 목록 조회 */
$sql = "SELECT * FROM {$g5['g5_prefix']}randombox_items 
        WHERE rb_id = '{$rb_id}' 
        ORDER BY rbi_order, rbi_grade DESC, rbi_id DESC 
        LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// ===================================
// 등급별 색상 정의
// ===================================

$grade_colors = array(
    'normal' => '#95a5a6',
    'rare' => '#3498db',
    'epic' => '#9b59b6',
    'legendary' => '#e74c3c'
);

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01">
        <span class="ov_txt">박스명</span>
        <span class="ov_num"><?php echo $box['rb_name']; ?></span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">전체 아이템</span>
        <span class="ov_num"><?php echo number_format($total_count); ?>개</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">확률 합계</span>
        <span class="ov_num <?php echo ($total_probability != 100) ? 'txt_error' : ''; ?>"><?php echo number_format($total_probability, 6); ?>%</span>
    </span>
</div>

<?php if ($total_probability != 100) : ?>
<div class="local_desc01 local_desc">
    <p style="color:#e74c3c;font-weight:bold;">
        <i class="bi bi-exclamation-triangle-fill"></i> 
        주의: 전체 아이템의 확률 합계가 100%가 아닙니다. (현재: <?php echo number_format($total_probability, 6); ?>%)
    </p>
</div>
<?php endif; ?>

<div class="btn_fixed_top">
    <a href="./box_list.php?<?php echo $qstr; ?>" class="btn btn_02">박스 목록</a>
    <a href="./item_form.php?rb_id=<?php echo $rb_id; ?>" class="btn btn_01">아이템 추가</a>
</div>

<form name="fitemlist" method="post" action="./item_list_update.php" onsubmit="return fitemlist_submit(this);">
<input type="hidden" name="rb_id" value="<?php echo $rb_id; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>아이템 목록</caption>
    <thead>
    <tr>
        <th scope="col" width="50">
            <label for="chkall" class="sound_only">전체선택</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col" width="60">번호</th>
        <th scope="col" width="80">이미지</th>
        <th scope="col">아이템명</th>
        <th scope="col" width="100">등급</th>
        <th scope="col" width="100">확률</th>
        <th scope="col" width="100">가치</th>
        <th scope="col" width="120">수량제한</th>
        <th scope="col" width="80">상태</th>
        <th scope="col" width="80">순서</th>
        <th scope="col" width="100">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    while ($row = sql_fetch_array($result)) {
        $bg = ($i % 2) ? 'bg1' : 'bg0';
        
        // 이미지 처리
        $item_img = '';
        if ($row['rbi_image'] && file_exists(G5_DATA_PATH.'/randombox/item/'.$row['rbi_image'])) {
            $item_img = '<img src="'.G5_DATA_URL.'/randombox/item/'.$row['rbi_image'].'" alt="'.$row['rbi_name'].'" style="width:50px;height:50px;object-fit:cover;">';
        } else {
            $item_img = '<span style="display:inline-block;width:50px;height:50px;background:#f0f0f0;text-align:center;line-height:50px;color:#999;font-size:0.8em;">NO</span>';
        }
        
        // 등급 표시
        $grade_style = 'background:' . $grade_colors[$row['rbi_grade']] . ';color:#fff;';
        
        // 수량 표시
        $qty_display = '';
        if ($row['rbi_limit_qty'] > 0) {
            $qty_display = number_format($row['rbi_issued_qty']) . ' / ' . number_format($row['rbi_limit_qty']);
            if ($row['rbi_issued_qty'] >= $row['rbi_limit_qty']) {
                $qty_display = '<span style="color:#e74c3c;">' . $qty_display . '</span>';
            }
        } else {
            $qty_display = number_format($row['rbi_issued_qty']) . ' / 무제한';
        }
        
        // 상태 표시
        $status_class = $row['rbi_status'] ? 'txt_active' : 'txt_inactive';
        $status_text = $row['rbi_status'] ? '활성' : '비활성';
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="hidden" name="rbi_id[<?php echo $i; ?>]" value="<?php echo $row['rbi_id']; ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo $row['rbi_name']; ?></label>
            <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
        </td>
        <td class="td_num"><?php echo $row['rbi_id']; ?></td>
        <td class="td_img"><?php echo $item_img; ?></td>
        <td class="td_left">
            <a href="./item_form.php?w=u&rbi_id=<?php echo $row['rbi_id']; ?>&rb_id=<?php echo $rb_id; ?>&<?php echo $qstr; ?>">
                <strong><?php echo $row['rbi_name']; ?></strong>
            </a>
            <?php if ($row['rbi_desc']) : ?>
            <div style="margin-top:3px;color:#666;font-size:0.9em;"><?php echo cut_str(strip_tags($row['rbi_desc']), 50); ?></div>
            <?php endif; ?>
        </td>
        <td class="td_grade">
            <span class="item_grade" style="<?php echo $grade_style; ?>"><?php echo get_grade_name($row['rbi_grade']); ?></span>
        </td>
        <td class="td_num">
            <input type="text" name="rbi_probability[<?php echo $i; ?>]" value="<?php echo $row['rbi_probability']; ?>" class="frm_input" size="8">%
        </td>
        <td class="td_num"><?php echo number_format($row['rbi_value']); ?>P</td>
        <td class="td_qty"><?php echo $qty_display; ?></td>
        <td class="td_status">
            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
        </td>
        <td class="td_num">
            <input type="text" name="rbi_order[<?php echo $i; ?>]" value="<?php echo $row['rbi_order']; ?>" class="frm_input" size="3">
        </td>
        <td class="td_mng td_mng_s">
            <a href="./item_form.php?w=u&rbi_id=<?php echo $row['rbi_id']; ?>&rb_id=<?php echo $rb_id; ?>&<?php echo $qstr; ?>" class="btn btn_03">수정</a>
        </td>
    </tr>
    <?php
        $i++;
    }
    
    if ($i == 0) {
        echo '<tr><td colspan="11" class="empty_table">등록된 아이템이 없습니다.</td></tr>';
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

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?rb_id='.$rb_id.'&'.$qstr.'&amp;page='); ?>

<script>
function fitemlist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 할 항목을 선택해 주세요.");
        return false;
    }
    
    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 항목을 정말 삭제하시겠습니까?\n\n삭제된 데이터는 복구할 수 없습니다.")) {
            return false;
        }
    }
    
    // 확률 합계 체크
    if (document.pressed == "선택수정") {
        var total = 0;
        var probs = document.getElementsByName('rbi_probability[]');
        for (var i = 0; i < probs.length; i++) {
            total += parseFloat(probs[i].value) || 0;
        }
        
        if (Math.abs(total - 100) > 0.000001) {
            if (!confirm("현재 확률 합계가 " + total.toFixed(6) + "%입니다.\n100%가 아니면 정상적으로 작동하지 않을 수 있습니다.\n\n그래도 저장하시겠습니까?")) {
                return false;
            }
        }
    }
    
    return true;
}
</script>

<style>
/* 아이템 목록 스타일 */
.txt_error { color: #e74c3c !important; font-weight: bold; }
.txt_active { color: #27ae60; font-weight: bold; }
.txt_inactive { color: #e74c3c; }

.td_img { text-align: center; }
.td_grade { text-align: center; }
.td_status { text-align: center; }
.td_qty { text-align: center; font-size: 0.9em; }

.item_grade {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 0.9em;
    font-weight: bold;
}

.td_mng .btn { 
    padding: 3px 8px; 
    font-size: 0.9em; 
}

/* 확률 입력 필드 */
input[name^="rbi_probability"] {
    text-align: right;
}
</style>

<?php
include_once('./admin.tail.php');
?>