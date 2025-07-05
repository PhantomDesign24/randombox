<?php
/*
 * 파일명: config.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 시스템 관리자 설정 페이지
 * 작성일: 2025-01-04
 */

$sub_menu = "300910";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '랜덤박스 기본설정';
include_once('./admin.head.php');

// ===================================
// 설정 저장 처리
// ===================================

/* 설정 저장 */
if (isset($_POST['mode']) && $_POST['mode'] == 'save') {
    
    auth_check($auth[$sub_menu], 'w');
    
    $configs = array(
        'system_enable',
        'show_probability',
        'enable_ceiling',
        'ceiling_count',
        'enable_gift',
        'enable_history',
        'enable_realtime',
        'daily_free_count',
        'min_level',
        'maintenance_mode',
        'maintenance_msg'
    );
    
    foreach ($configs as $cfg_name) {
        $cfg_value = isset($_POST[$cfg_name]) ? $_POST[$cfg_name] : '';
        set_randombox_config($cfg_name, $cfg_value);
    }
    
    alert('설정이 저장되었습니다.', './config.php');
}

// ===================================
// 설정값 로드
// ===================================

/* 전체 설정 가져오기 */
$config = get_randombox_all_config();
?>

<div class="local_desc01 local_desc">
    <p>랜덤박스 시스템의 기본 설정을 관리합니다.</p>
</div>

<form name="fconfig" method="post" onsubmit="return fconfig_submit(this);">
<input type="hidden" name="mode" value="save">

<section>
    <h2 class="h2_frm">시스템 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>시스템 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">전체 시스템</th>
            <td>
                <label>
                    <input type="radio" name="system_enable" value="1" <?php echo $config['system_enable'] ? 'checked' : ''; ?>> 활성화
                </label>
                <label>
                    <input type="radio" name="system_enable" value="0" <?php echo !$config['system_enable'] ? 'checked' : ''; ?>> 비활성화
                </label>
                <span class="frm_info">전체 랜덤박스 시스템을 활성화/비활성화합니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row">점검 모드</th>
            <td>
                <label>
                    <input type="radio" name="maintenance_mode" value="1" <?php echo $config['maintenance_mode'] ? 'checked' : ''; ?>> 점검중
                </label>
                <label>
                    <input type="radio" name="maintenance_mode" value="0" <?php echo !$config['maintenance_mode'] ? 'checked' : ''; ?>> 정상운영
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">점검 메시지</th>
            <td>
                <input type="text" name="maintenance_msg" value="<?php echo $config['maintenance_msg']; ?>" class="frm_input" size="80">
                <span class="frm_info">점검 모드일 때 표시할 메시지</span>
            </td>
        </tr>
        <tr>
            <th scope="row">최소 이용 레벨</th>
            <td>
                <input type="number" name="min_level" value="<?php echo $config['min_level']; ?>" class="frm_input" min="1" max="10"> 레벨 이상
                <span class="frm_info">랜덤박스를 이용할 수 있는 최소 회원 레벨</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<section>
    <h2 class="h2_frm">기능 설정</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>기능 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">확률 공개</th>
            <td>
                <label>
                    <input type="radio" name="show_probability" value="1" <?php echo $config['show_probability'] ? 'checked' : ''; ?>> 공개
                </label>
                <label>
                    <input type="radio" name="show_probability" value="0" <?php echo !$config['show_probability'] ? 'checked' : ''; ?>> 비공개
                </label>
                <span class="frm_info">아이템 확률을 사용자에게 공개할지 설정</span>
            </td>
        </tr>
        <tr>
            <th scope="row">천장 시스템</th>
            <td>
                <label>
                    <input type="radio" name="enable_ceiling" value="1" <?php echo $config['enable_ceiling'] ? 'checked' : ''; ?>> 사용
                </label>
                <label>
                    <input type="radio" name="enable_ceiling" value="0" <?php echo !$config['enable_ceiling'] ? 'checked' : ''; ?>> 미사용
                </label>
                <span class="frm_info">일정 횟수 이상 뽑기 시 레어 이상 아이템 보장</span>
            </td>
        </tr>
        <tr>
            <th scope="row">천장 카운트</th>
            <td>
                <input type="number" name="ceiling_count" value="<?php echo $config['ceiling_count']; ?>" class="frm_input" min="0"> 회
                <span class="frm_info">레어 이상 아이템을 보장하는 뽑기 횟수 (0: 미사용)</span>
            </td>
        </tr>
        <tr>
            <th scope="row">선물하기</th>
            <td>
                <label>
                    <input type="radio" name="enable_gift" value="1" <?php echo $config['enable_gift'] ? 'checked' : ''; ?>> 사용
                </label>
                <label>
                    <input type="radio" name="enable_gift" value="0" <?php echo !$config['enable_gift'] ? 'checked' : ''; ?>> 미사용
                </label>
                <span class="frm_info">다른 회원에게 랜덤박스 선물하기 기능</span>
            </td>
        </tr>
        <tr>
            <th scope="row">구매내역 공개</th>
            <td>
                <label>
                    <input type="radio" name="enable_history" value="1" <?php echo $config['enable_history'] ? 'checked' : ''; ?>> 공개
                </label>
                <label>
                    <input type="radio" name="enable_history" value="0" <?php echo !$config['enable_history'] ? 'checked' : ''; ?>> 비공개
                </label>
                <span class="frm_info">다른 회원의 구매내역 열람 가능 여부</span>
            </td>
        </tr>
        <tr>
            <th scope="row">실시간 당첨현황</th>
            <td>
                <label>
                    <input type="radio" name="enable_realtime" value="1" <?php echo $config['enable_realtime'] ? 'checked' : ''; ?>> 표시
                </label>
                <label>
                    <input type="radio" name="enable_realtime" value="0" <?php echo !$config['enable_realtime'] ? 'checked' : ''; ?>> 미표시
                </label>
                <span class="frm_info">메인 페이지에 실시간 당첨 현황 표시</span>
            </td>
        </tr>
        <tr>
            <th scope="row">일일 무료 뽑기</th>
            <td>
                <input type="number" name="daily_free_count" value="<?php echo $config['daily_free_count']; ?>" class="frm_input" min="0" max="10"> 회
                <span class="frm_info">하루에 제공할 무료 뽑기 횟수 (0: 미제공)</span>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<div class="btn_fixed_top">
    <a href="./box_list.php" class="btn btn_02">박스 관리</a>
    <input type="submit" value="저장" class="btn_submit btn" accesskey="s">
</div>

</form>

<script>
function fconfig_submit(f) {
    if (!confirm("설정을 저장하시겠습니까?")) {
        return false;
    }
    
    return true;
}
</script>

<style>
/* 랜덤박스 관리자 스타일 */
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
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 0.9em;
}

label {
    margin-right: 10px;
}

input[type="radio"] {
    margin-right: 5px;
}
</style>

<?php
include_once('./admin.tail.php');
?>