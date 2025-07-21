<?php
/*
 * 파일명: plugin.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 플러그인 설치/제거 관리
 * 작성일: 2025-01-04
 * 수정일: 2025-07-17
 */

$sub_menu = "300900";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '랜덤박스 플러그인 관리';
include_once('./admin.head.php');

// ===================================
// 설치 상태 확인
// ===================================

/* 테이블 존재 여부 확인 */
$is_installed = false;
$install_status = array();

$tables = array(
    'randombox' => '랜덤박스 정보',
    'randombox_items' => '아이템 정보',
    'randombox_history' => '구매/획득 기록',
    'randombox_config' => '시스템 설정',
    'randombox_ceiling' => '천장 시스템',
    'randombox_gift' => '선물하기',
    'randombox_coupon_types' => '교환권 타입',
    'randombox_coupon_codes' => '교환권 코드',
    'randombox_member_coupons' => '회원 보유 교환권',
    'randombox_coupon_use_log' => '교환권 사용 기록',
    'randombox_guaranteed' => '보장된 분배'
);

foreach ($tables as $table => $desc) {
    $sql = "SHOW TABLES LIKE '{$g5['g5_prefix']}{$table}'";
    $result = sql_fetch($sql);
    $install_status[$table] = array(
        'desc' => $desc,
        'installed' => $result ? true : false
    );
    
    if ($result) {
        $is_installed = true;
    }
}

// ===================================
// 디렉토리 권한 확인
// ===================================

$dir_status = array();
$upload_dirs = array(
    G5_DATA_PATH.'/randombox' => '메인 디렉토리',
    G5_DATA_PATH.'/randombox/box' => '박스 이미지',
    G5_DATA_PATH.'/randombox/item' => '아이템 이미지',
    G5_DATA_PATH.'/randombox/coupon' => '교환권 이미지'
);

foreach ($upload_dirs as $dir => $desc) {
    $dir_status[$dir] = array(
        'desc' => $desc,
        'exists' => is_dir($dir),
        'writable' => is_writable($dir)
    );
}

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_desc01 local_desc">
    <p>
        랜덤박스 시스템의 설치 상태를 확인하고 관리합니다.<br>
        설치 전 반드시 데이터베이스를 백업하시기 바랍니다.
    </p>
</div>

<!-- 설치 상태 -->
<section>
    <h2 class="h2_frm">설치 상태</h2>
    
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>설치 상태</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">전체 설치 상태</th>
            <td>
                <?php if ($is_installed) : ?>
                    <span style="color:#27ae60;font-weight:bold;">✓ 설치됨</span>
                <?php else : ?>
                    <span style="color:#e74c3c;font-weight:bold;">✗ 미설치</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">플러그인 버전</th>
            <td>v2.0 (2025-07-17) - 교환권 시스템 추가</td>
        </tr>
        <tr>
            <th scope="row">호환성</th>
            <td>그누보드 5.3 이상, PHP 5.6 이상</td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<!-- 테이블 상태 -->
<section>
    <h2 class="h2_frm">데이터베이스 테이블</h2>
    
    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption>데이터베이스 테이블</caption>
        <thead>
        <tr>
            <th scope="col">테이블명</th>
            <th scope="col">설명</th>
            <th scope="col" width="100">상태</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($install_status as $table => $info) : ?>
        <tr>
            <td><?php echo $g5['g5_prefix'] . $table; ?></td>
            <td><?php echo $info['desc']; ?></td>
            <td class="td_center">
                <?php if ($info['installed']) : ?>
                    <span style="color:#27ae60;">✓ 설치됨</span>
                <?php else : ?>
                    <span style="color:#e74c3c;">✗ 미설치</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</section>

<!-- 디렉토리 상태 -->
<section>
    <h2 class="h2_frm">디렉토리 권한</h2>
    
    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption>디렉토리 권한</caption>
        <thead>
        <tr>
            <th scope="col">디렉토리</th>
            <th scope="col">용도</th>
            <th scope="col" width="100">존재여부</th>
            <th scope="col" width="100">쓰기권한</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($dir_status as $dir => $info) : ?>
        <tr>
            <td><?php echo str_replace(G5_PATH, '', $dir); ?></td>
            <td><?php echo $info['desc']; ?></td>
            <td class="td_center">
                <?php if ($info['exists']) : ?>
                    <span style="color:#27ae60;">✓</span>
                <?php else : ?>
                    <span style="color:#e74c3c;">✗</span>
                <?php endif; ?>
            </td>
            <td class="td_center">
                <?php if ($info['writable']) : ?>
                    <span style="color:#27ae60;">✓</span>
                <?php else : ?>
                    <span style="color:#e74c3c;">✗</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</section>

<!-- 설치/제거 버튼 -->
<div class="btn_confirm01 btn_confirm">
    <?php if (!$is_installed) : ?>
        <!-- 설치 옵션 폼 -->
        <form method="post" action="./plugin_install.php" id="install_form">
            <input type="hidden" name="mode" value="install">
            
            <div class="install_options">
                <h3>설치 옵션</h3>
                <div class="option_box">
                    <label>
                        <input type="checkbox" name="install_sample" value="1" checked>
                        <strong>샘플 데이터 설치</strong>
                        <p class="option_desc">
                            테스트용 샘플 박스와 아이템을 함께 설치합니다.<br>
                            - 일반 포인트 박스<br>
                            - 프리미엄 랜덤 박스 (교환권 포함)<br>
                            - 신규회원 환영 박스 (랜덤 포인트)<br>
                            - 배민 1만원 쿠폰 이벤트 (보장된 분배)<br>
                            - 각종 교환권 샘플 (스타벅스, 배민, GS25 등)
                        </p>
                    </label>
                </div>
                
                <div class="install_notice">
                    <h4>⚠️ 설치 전 확인사항</h4>
                    <ul>
                        <li>데이터베이스를 반드시 백업하세요.</li>
                        <li>기존에 동일한 테이블명이 있으면 오류가 발생할 수 있습니다.</li>
                        <li>설치 후 /adm/admin.menu300.php 파일에 메뉴를 추가해야 합니다.</li>
                    </ul>
                </div>
                
                <div class="btn_area">
                    <input type="submit" value="플러그인 설치" class="btn btn_01 btn_install">
                </div>
            </div>
        </form>
        
        <script>
        $('#install_form').on('submit', function() {
            var sample = $('input[name="install_sample"]').is(':checked') ? '샘플 데이터를 포함하여' : '';
            return confirm('랜덤박스 시스템을 ' + sample + ' 설치하시겠습니까?\n\n설치 전 반드시 백업하시기 바랍니다.');
        });
        </script>
        
    <?php else : ?>
        <a href="./config.php" class="btn btn_02">설정 페이지</a>
        <a href="./box_list.php" class="btn btn_02">박스 관리</a>
        <a href="./coupon_list.php" class="btn btn_02">교환권 관리</a>
        
        <!-- 제거 옵션 -->
        <div class="uninstall_options" style="margin-top:20px;">
            <h3>플러그인 제거 옵션</h3>
            
            <div class="uninstall_type_box">
                <h4>제거 방식을 선택하세요</h4>
                
                <div class="uninstall_type">
                    <label>
                        <input type="radio" name="uninstall_type" value="preserve" checked>
                        <strong>데이터 보존 제거</strong>
                        <p class="type_desc">
                            - 구매 내역이 있는 데이터는 보존<br>
                            - 이미지 파일은 보존<br>
                            - 설정과 빈 테이블만 제거<br>
                            - 재설치 시 기존 데이터 복구 가능
                        </p>
                    </label>
                </div>
                
                <div class="uninstall_type">
                    <label>
                        <input type="radio" name="uninstall_type" value="complete">
                        <strong>완전 제거</strong>
                        <p class="type_desc">
                            - 모든 데이터베이스 테이블 삭제<br>
                            - 모든 이미지 파일 삭제<br>
                            - data/randombox 폴더 전체 삭제<br>
                            - <span style="color:#e74c3c;font-weight:bold;">⚠️ 복구 불가능</span>
                        </p>
                    </label>
                </div>
                
                <div class="btn_area">
                    <button type="button" class="btn btn_03" onclick="confirmUninstall()">플러그인 제거</button>
                </div>
            </div>
        </div>
        
        <form method="post" action="./plugin_install.php" id="uninstall_form" style="display:none;">
            <input type="hidden" name="mode" value="uninstall">
            <input type="hidden" name="uninstall_type" value="">
        </form>
        
        <script>
        function confirmUninstall() {
            var type = $('input[name="uninstall_type"]:checked').val();
            var msg = '';
            
            if (type == 'complete') {
                msg = '⚠️ 경고: 완전 제거를 선택하셨습니다.\n\n';
                msg += '모든 데이터와 파일이 영구적으로 삭제됩니다.\n';
                msg += '이 작업은 되돌릴 수 없습니다.\n\n';
                msg += '정말로 완전 제거하시겠습니까?';
                
                if (confirm(msg)) {
                    if (confirm('마지막 확인입니다.\n\n모든 랜덤박스 데이터가 삭제됩니다.\n계속하시겠습니까?')) {
                        $('#uninstall_form input[name="uninstall_type"]').val(type);
                        $('#uninstall_form').submit();
                    }
                }
            } else {
                msg = '데이터를 보존하면서 플러그인을 제거하시겠습니까?\n\n';
                msg += '구매 내역과 이미지 파일은 보존됩니다.';
                
                if (confirm(msg)) {
                    $('#uninstall_form input[name="uninstall_type"]').val(type);
                    $('#uninstall_form').submit();
                }
            }
        }
        </script>
    <?php endif; ?>
</div>

<div class="local_desc02 local_desc">
    <h3>주의사항</h3>
    <ul>
        <li>설치 전 반드시 데이터베이스를 백업하세요.</li>
        <li>디렉토리 권한이 없는 경우 755로 설정해 주세요.</li>
        <li>제거 시 구매 내역이 있는 데이터는 보존됩니다.</li>
        <li>재설치 시 기존 설정은 초기화됩니다.</li>
        <li>샘플 데이터는 테스트용이며, 실제 운영 시 삭제하고 사용하세요.</li>
    </ul>
</div>

<style>
.h2_frm {
    margin: 30px 0 10px;
    padding: 10px 0;
    border-bottom: 2px solid #2c3e50;
    font-size: 1.3em;
    color: #2c3e50;
}

.td_center {
    text-align: center;
}

.btn_confirm01 {
    margin: 30px 0;
    text-align: center;
}

.btn_confirm01 form {
    display: inline-block;
    margin: 0 5px;
}

/* 설치 옵션 스타일 */
.install_options {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.install_options h3 {
    margin: 0 0 20px;
    color: #333;
    font-size: 1.2em;
}

.option_box {
    background: #fff;
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 3px;
    margin-bottom: 20px;
}

.option_box label {
    display: block;
    cursor: pointer;
}

.option_box input[type="checkbox"] {
    margin-right: 10px;
    vertical-align: middle;
}

.option_box strong {
    font-size: 1.1em;
    color: #333;
}

.option_desc {
    margin: 10px 0 0 25px;
    color: #666;
    font-size: 0.95em;
    line-height: 1.6;
}

.install_notice {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 3px;
    padding: 15px;
    margin-bottom: 20px;
}

.install_notice h4 {
    margin: 0 0 10px;
    color: #856404;
    font-size: 1em;
}

.install_notice ul {
    margin: 0;
    padding-left: 20px;
}

.install_notice li {
    margin: 5px 0;
    color: #856404;
    font-size: 0.9em;
}

/* 제거 옵션 스타일 */
.uninstall_options {
    max-width: 800px;
    margin: 20px auto 0;
    padding: 30px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.uninstall_options h3 {
    margin: 0 0 20px;
    color: #333;
    font-size: 1.2em;
    text-align: center;
}

.uninstall_type_box {
    background: #fff;
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 3px;
}

.uninstall_type_box h4 {
    margin: 0 0 20px;
    color: #666;
    font-size: 1em;
    text-align: center;
}

.uninstall_type {
    background: #f8f9fa;
    padding: 20px;
    margin-bottom: 15px;
    border: 2px solid #e9ecef;
    border-radius: 5px;
    transition: all 0.3s;
}

.uninstall_type:hover {
    border-color: #dee2e6;
    background: #fff;
}

.uninstall_type label {
    display: block;
    cursor: pointer;
}

.uninstall_type input[type="radio"] {
    margin-right: 10px;
    vertical-align: middle;
}

.uninstall_type strong {
    font-size: 1.1em;
    color: #333;
}

.type_desc {
    margin: 10px 0 0 25px;
    color: #666;
    font-size: 0.9em;
    line-height: 1.6;
}

.btn_area {
    text-align: center;
    margin-top: 20px;
}

.btn_install {
    padding: 10px 30px;
    font-size: 1.1em;
}

/* 기존 스타일 */
.local_desc02 {
    margin-top: 50px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
}

.local_desc02 h3 {
    margin: 0 0 10px;
    color: #e74c3c;
}

.local_desc02 ul {
    margin: 0;
    padding-left: 20px;
}

.local_desc02 li {
    margin: 5px 0;
    list-style: disc;
}
</style>

<?php
include_once('./admin.tail.php');
?>