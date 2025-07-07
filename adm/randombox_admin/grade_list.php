<?php
/*
 * 파일명: grade_list.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 등급 관리 페이지
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

$sub_menu = "300915";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '등급 관리';
include_once('./admin.head.php');

// ===================================
// 데이터 조회
// ===================================

/* 등급 목록 조회 - g5_ 접두어 제거 */
$sql = "SELECT * FROM randombox_grades ORDER BY rbg_level DESC, rbg_order, rbg_id";
$result = sql_query($sql);

/* 희귀 등급 최소 레벨 가져오기 */
$rare_min_level = get_randombox_config('rare_min_level');
if (!$rare_min_level) $rare_min_level = 2; // 기본값: 레벨 2 이상이 희귀

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01">
        <span class="ov_txt">전체 등급</span>
        <span class="ov_num"><?php echo sql_num_rows($result); ?>개</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">희귀 등급 기준</span>
        <span class="ov_num">레벨 <?php echo $rare_min_level; ?> 이상</span>
    </span>
</div>

<div class="local_desc01 local_desc">
    <p>
        <strong>희귀도 레벨</strong>: 숫자가 높을수록 희귀한 등급입니다. (1=가장 흔함)<br>
        희귀 등급 기준 레벨(<?php echo $rare_min_level; ?>) 이상의 등급만 천장 시스템 적용 및 실시간 당첨 현황에 표시됩니다.<br>
        아이콘과 이미지를 모두 설정한 경우 이미지가 우선 표시됩니다.
    </p>
</div>

<div class="btn_fixed_top">
    <a href="./config.php#rare_level_setting" class="btn btn_02">희귀 등급 기준 설정</a>
    <a href="./grade_form.php" class="btn btn_01">등급 추가</a>
</div>

<form name="fgradelist" method="post" action="./grade_list_update.php" onsubmit="return fgradelist_submit(this);">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>등급 목록</caption>
    <thead>
    <tr>
        <th scope="col" width="50">
            <label for="chkall" class="sound_only">전체선택</label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col" width="60">번호</th>
        <th scope="col" width="120">등급 키</th>
        <th scope="col">등급명</th>
        <th scope="col" width="80">시각 표시</th>
        <th scope="col" width="100">희귀도 레벨</th>
        <th scope="col" width="80">희귀 여부</th>
        <th scope="col" width="100">사용 아이템</th>
        <th scope="col" width="80">순서</th>
        <th scope="col" width="100">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    while ($row = sql_fetch_array($result)) {
        $bg = ($i % 2) ? 'bg1' : 'bg0';
        
        // 사용 중인 아이템 수 확인 - g5_ 접두어 제거
        $sql2 = "SELECT COUNT(*) as cnt FROM randombox_items WHERE rbi_grade = '{$row['rbg_key']}'";
        $item_count = sql_fetch($sql2);
        
        // 기본 등급 여부 (normal, rare, epic, legendary)
        $is_default = in_array($row['rbg_key'], array('normal', 'rare', 'epic', 'legendary'));
        
        // 희귀 등급 여부
        $is_rare = ($row['rbg_level'] >= $rare_min_level);
        
        // 시각 표시 (이미지 우선, 없으면 아이콘)
        $visual_display = '';
        if ($row['rbg_image'] && file_exists(G5_DATA_PATH.'/randombox/grade/'.$row['rbg_image'])) {
            $visual_display = '<img src="'.G5_DATA_URL.'/randombox/grade/'.$row['rbg_image'].'" style="max-width:30px;max-height:30px;">';
        } elseif ($row['rbg_icon']) {
            $visual_display = '<i class="'.$row['rbg_icon'].'" style="font-size:24px;color:'.$row['rbg_color'].';"></i>';
        }
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="hidden" name="rbg_id[<?php echo $i; ?>]" value="<?php echo $row['rbg_id']; ?>">
            <input type="hidden" name="is_default[<?php echo $i; ?>]" value="<?php echo $is_default ? '1' : '0'; ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo $row['rbg_name']; ?></label>
            <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>" <?php echo $is_default ? 'disabled' : ''; ?>>
        </td>
        <td class="td_num"><?php echo $row['rbg_id']; ?></td>
        <td class="td_center">
            <code><?php echo $row['rbg_key']; ?></code>
            <?php if ($is_default) : ?>
            <span style="color:#999;font-size:11px;">(기본)</span>
            <?php endif; ?>
        </td>
        <td class="td_left">
            <input type="text" name="rbg_name[<?php echo $i; ?>]" value="<?php echo $row['rbg_name']; ?>" class="frm_input" size="20">
            <div style="display:inline-block;width:20px;height:20px;background:<?php echo $row['rbg_color']; ?>;border:1px solid #ddd;vertical-align:middle;margin-left:5px;"></div>
        </td>
        <td class="td_center">
            <?php echo $visual_display; ?>
        </td>
        <td class="td_center">
            <input type="number" name="rbg_level[<?php echo $i; ?>]" value="<?php echo $row['rbg_level']; ?>" class="frm_input" size="5" min="1" max="99" style="width:60px;">
        </td>
        <td class="td_center">
            <?php if ($is_rare) : ?>
                <span style="color:#e74c3c;font-weight:bold;">희귀</span>
            <?php else : ?>
                <span style="color:#999;">일반</span>
            <?php endif; ?>
        </td>
        <td class="td_num"><?php echo number_format($item_count['cnt']); ?>개</td>
        <td class="td_num">
            <input type="text" name="rbg_order[<?php echo $i; ?>]" value="<?php echo $row['rbg_order']; ?>" class="frm_input" size="3">
        </td>
        <td class="td_mng td_mng_s">
            <a href="./grade_form.php?w=u&rbg_id=<?php echo $row['rbg_id']; ?>" class="btn btn_03">수정</a>
        </td>
    </tr>
    <?php
        $i++;
    }
    
    if ($i == 0) {
        echo '<tr><td colspan="10" class="empty_table">등록된 등급이 없습니다.</td></tr>';
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

<div class="local_desc02 local_desc">
    <h3>등급 시스템 가이드</h3>
    <ul>
        <li><strong>희귀도 레벨</strong>: 1부터 시작하며, 숫자가 높을수록 희귀한 등급입니다.</li>
        <li><strong>희귀 등급</strong>: 레벨 <?php echo $rare_min_level; ?> 이상의 등급은 자동으로 희귀 등급으로 분류됩니다.</li>
        <li><strong>천장 시스템</strong>: 희귀 등급만 천장 시스템의 보장 대상이 됩니다.</li>
        <li><strong>실시간 당첨</strong>: 희귀 등급만 실시간 당첨 현황에 표시됩니다.</li>
        <li><strong>시각 표시</strong>: 이미지를 업로드하거나 Bootstrap Icons를 사용할 수 있습니다.</li>
    </ul>
</div>

<script>
function fgradelist_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 할 항목을 선택해 주세요.");
        return false;
    }
    
    if (document.pressed == "선택삭제") {
        if (!confirm("선택한 등급을 정말 삭제하시겠습니까?\n\n해당 등급을 사용하는 아이템이 있다면 삭제할 수 없습니다.")) {
            return false;
        }
    }
    
    return true;
}
</script>

<style>
/* 등급 관리 스타일 */
code {
    padding: 2px 6px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-family: Consolas, Monaco, monospace;
    font-size: 13px;
}

.td_mng .btn {
    padding: 3px 8px;
    font-size: 0.9em;
}

input[type="number"] {
    text-align: center;
}

.local_desc02 {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}

.local_desc02 h3 {
    margin: 0 0 15px;
    font-size: 1.1em;
    color: #333;
}

.local_desc02 ul {
    margin: 0;
    padding-left: 20px;
}

.local_desc02 li {
    margin: 5px 0;
    line-height: 1.6;
}
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<?php
include_once('./admin.tail.php');
?>