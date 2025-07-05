<?php
/*
 * 파일명: plugin.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 플러그인 설치/제거 관리
 * 작성일: 2025-01-04
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
    'randombox_gift' => '선물하기'
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
    G5_DATA_PATH.'/randombox/item' => '아이템 이미지'
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
            <td>v1.0 (2025-01-04)</td>
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
        <form method="post" action="./plugin_install.php" onsubmit="return confirm('랜덤박스 시스템을 설치하시겠습니까?\n\n설치 전 반드시 백업하시기 바랍니다.');">
            <input type="hidden" name="mode" value="install">
            <input type="submit" value="플러그인 설치" class="btn btn_01">
        </form>
    <?php else : ?>
        <a href="./config.php" class="btn btn_02">설정 페이지</a>
        <a href="./box_list.php" class="btn btn_02">박스 관리</a>
        
        <form method="post" action="./plugin_install.php" style="display:inline;">
            <input type="hidden" name="mode" value="uninstall">
            <input type="submit" value="플러그인 제거" class="btn btn_03" onclick="return confirm('정말로 랜덤박스 시스템을 제거하시겠습니까?\n\n구매 내역이 있는 경우 데이터는 보존되지만, 구매 내역이 없는 데이터는 완전히 삭제됩니다.');">
        </form>
    <?php endif; ?>
</div>

<div class="local_desc02 local_desc">
    <h3>주의사항</h3>
    <ul>
        <li>설치 전 반드시 데이터베이스를 백업하세요.</li>
        <li>디렉토리 권한이 없는 경우 755로 설정해 주세요.</li>
        <li>제거 시 구매 내역이 있는 데이터는 보존됩니다.</li>
        <li>재설치 시 기존 설정은 초기화됩니다.</li>
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