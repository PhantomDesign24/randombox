<?php
/*
 * 파일명: install.php
 * 위치: /plugin/randombox/
 * 기능: 랜덤박스 시스템 설치 스크립트
 * 작성일: 2025-01-04
 */

include_once('./_common.php');
include_once(G5_PATH.'/head.sub.php');

// 관리자만 접근 가능
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

// ===================================
// 설치 처리
// ===================================

/* 설치 상태 확인 */
$is_installed = sql_fetch("SHOW TABLES LIKE '{$g5['g5_prefix']}randombox'");

if ($is_installed && !isset($_POST['reinstall'])) {
    ?>
    <div style="margin: 50px; padding: 30px; border: 1px solid #ddd; background: #f5f5f5;">
        <h2>🎰 그누보드 랜덤박스 시스템</h2>
        <p style="margin: 20px 0; color: #666;">
            랜덤박스 시스템이 이미 설치되어 있습니다.<br>
            재설치하시면 기존 데이터가 모두 삭제됩니다.
        </p>
        <form method="post">
            <input type="hidden" name="reinstall" value="1">
            <button type="submit" class="btn btn-danger" onclick="return confirm('정말로 재설치하시겠습니까? 모든 데이터가 삭제됩니다.')">재설치</button>
            <a href="<?php echo G5_ADMIN_URL; ?>" class="btn btn-default">관리자 페이지로</a>
        </form>
    </div>
    <?php
    include_once(G5_PATH.'/tail.sub.php');
    exit;
}

// ===================================
// 테이블 생성
// ===================================

/* 기존 테이블 삭제 (재설치인 경우) */
if (isset($_POST['reinstall'])) {
    $tables = array(
        'randombox',
        'randombox_items',
        'randombox_history',
        'randombox_config',
        'randombox_ceiling',
        'randombox_gift'
    );
    
    foreach ($tables as $table) {
        sql_query("DROP TABLE IF EXISTS `{$g5['g5_prefix']}{$table}`", false);
    }
}

/* 랜덤박스 메인 테이블 */
$sql = "CREATE TABLE IF NOT EXISTS `{$g5['g5_prefix']}randombox` (
    `rb_id` int(11) NOT NULL AUTO_INCREMENT,
    `rb_name` varchar(255) NOT NULL DEFAULT '' COMMENT '박스명',
    `rb_desc` text COMMENT '박스 설명',
    `rb_price` int(11) NOT NULL DEFAULT '0' COMMENT '가격(포인트)',
    `rb_image` varchar(255) NOT NULL DEFAULT '' COMMENT '박스 이미지',
    `rb_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '상태(0:비활성,1:활성)',
    `rb_type` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '박스 타입(normal,event,premium)',
    `rb_start_date` datetime DEFAULT NULL COMMENT '판매 시작일',
    `rb_end_date` datetime DEFAULT NULL COMMENT '판매 종료일',
    `rb_limit_qty` int(11) NOT NULL DEFAULT '0' COMMENT '일일 구매 제한(0:무제한)',
    `rb_total_qty` int(11) NOT NULL DEFAULT '0' COMMENT '전체 판매 수량 제한(0:무제한)',
    `rb_sold_qty` int(11) NOT NULL DEFAULT '0' COMMENT '판매된 수량',
    `rb_order` int(11) NOT NULL DEFAULT '0' COMMENT '정렬순서',
    `rb_created_at` datetime NOT NULL,
    `rb_updated_at` datetime NOT NULL,
    PRIMARY KEY (`rb_id`),
    KEY `idx_status_order` (`rb_status`,`rb_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
sql_query($sql, true);

/* 랜덤박스 아이템 테이블 */
$sql = "CREATE TABLE IF NOT EXISTS `{$g5['g5_prefix']}randombox_items` (
    `rbi_id` int(11) NOT NULL AUTO_INCREMENT,
    `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
    `rbi_name` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템명',
    `rbi_desc` text COMMENT '아이템 설명',
    `rbi_image` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템 이미지',
    `rbi_grade` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '등급(normal,rare,epic,legendary)',
    `rbi_probability` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '확률(%)',
    `rbi_value` int(11) NOT NULL DEFAULT '0' COMMENT '아이템 가치(포인트)',
    `rbi_limit_qty` int(11) NOT NULL DEFAULT '0' COMMENT '최대 배출 수량(0:무제한)',
    `rbi_issued_qty` int(11) NOT NULL DEFAULT '0' COMMENT '배출된 수량',
    `rbi_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '상태(0:비활성,1:활성)',
    `rbi_order` int(11) NOT NULL DEFAULT '0' COMMENT '정렬순서',
    `rbi_created_at` datetime NOT NULL,
    `rbi_updated_at` datetime NOT NULL,
    PRIMARY KEY (`rbi_id`),
    KEY `idx_box_status` (`rb_id`,`rbi_status`),
    KEY `idx_grade` (`rbi_grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
sql_query($sql, true);

/* 구매/획득 기록 테이블 */
$sql = "CREATE TABLE IF NOT EXISTS `{$g5['g5_prefix']}randombox_history` (
    `rbh_id` int(11) NOT NULL AUTO_INCREMENT,
    `mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '회원 ID',
    `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
    `rb_name` varchar(255) NOT NULL DEFAULT '' COMMENT '박스명(기록용)',
    `rb_price` int(11) NOT NULL DEFAULT '0' COMMENT '구매 가격(기록용)',
    `rbi_id` int(11) NOT NULL DEFAULT '0' COMMENT '획득 아이템 ID',
    `rbi_name` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템명(기록용)',
    `rbi_grade` varchar(20) NOT NULL DEFAULT '' COMMENT '아이템 등급(기록용)',
    `rbi_value` int(11) NOT NULL DEFAULT '0' COMMENT '아이템 가치(기록용)',
    `rbh_status` varchar(20) NOT NULL DEFAULT 'completed' COMMENT '상태(pending,completed,gift)',
    `rbh_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '구매 IP',
    `rbh_created_at` datetime NOT NULL,
    PRIMARY KEY (`rbh_id`),
    KEY `idx_member` (`mb_id`,`rbh_created_at`),
    KEY `idx_box` (`rb_id`),
    KEY `idx_item` (`rbi_id`),
    KEY `idx_created` (`rbh_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
sql_query($sql, true);

/* 시스템 설정 테이블 */
$sql = "CREATE TABLE IF NOT EXISTS `{$g5['g5_prefix']}randombox_config` (
    `cfg_name` varchar(50) NOT NULL DEFAULT '' COMMENT '설정명',
    `cfg_value` text COMMENT '설정값',
    `cfg_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '설정 설명',
    PRIMARY KEY (`cfg_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
sql_query($sql, true);

/* 천장 시스템 테이블 */
$sql = "CREATE TABLE IF NOT EXISTS `{$g5['g5_prefix']}randombox_ceiling` (
    `rbc_id` int(11) NOT NULL AUTO_INCREMENT,
    `mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '회원 ID',
    `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
    `rbc_count` int(11) NOT NULL DEFAULT '0' COMMENT '현재 뽑기 횟수',
    `rbc_last_rare` int(11) NOT NULL DEFAULT '0' COMMENT '마지막 레어 획득 시 카운트',
    `rbc_updated_at` datetime NOT NULL,
    PRIMARY KEY (`rbc_id`),
    UNIQUE KEY `idx_member_box` (`mb_id`,`rb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
sql_query($sql, true);

/* 선물하기 테이블 */
$sql = "CREATE TABLE IF NOT EXISTS `{$g5['g5_prefix']}randombox_gift` (
    `rbg_id` int(11) NOT NULL AUTO_INCREMENT,
    `send_mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '보낸 회원 ID',
    `recv_mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '받는 회원 ID',
    `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
    `rbg_quantity` int(11) NOT NULL DEFAULT '1' COMMENT '선물 수량',
    `rbg_message` text COMMENT '선물 메시지',
    `rbg_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '상태(pending,accepted,rejected)',
    `rbg_created_at` datetime NOT NULL,
    `rbg_accepted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`rbg_id`),
    KEY `idx_receiver` (`recv_mb_id`,`rbg_status`),
    KEY `idx_sender` (`send_mb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
sql_query($sql, true);

// ===================================
// 기본 설정 데이터 삽입
// ===================================

/* 기존 설정 삭제 (재설치인 경우) */
sql_query("DELETE FROM `{$g5['g5_prefix']}randombox_config`", false);

/* 시스템 기본 설정값 */
$configs = array(
    array('system_enable', '1', '전체 시스템 활성화 여부'),
    array('show_probability', '1', '확률 공개 여부'),
    array('enable_ceiling', '1', '천장 시스템 사용 여부'),
    array('ceiling_count', '100', '천장 카운트 (레어 이상 보장)'),
    array('enable_gift', '1', '선물하기 기능 사용 여부'),
    array('enable_history', '1', '구매내역 공개 여부'),
    array('enable_realtime', '1', '실시간 당첨 현황 표시 여부'),
    array('daily_free_count', '0', '일일 무료 뽑기 횟수'),
    array('min_level', '1', '최소 이용 가능 레벨'),
    array('maintenance_mode', '0', '점검 모드'),
    array('maintenance_msg', '시스템 점검 중입니다.', '점검 메시지')
);

foreach ($configs as $config) {
    $sql = "INSERT INTO `{$g5['g5_prefix']}randombox_config` (cfg_name, cfg_value, cfg_desc) VALUES ('{$config[0]}', '{$config[1]}', '{$config[2]}')";
    sql_query($sql, false);
}

// ===================================
// 디렉토리 생성
// ===================================

/* 업로드 디렉토리 생성 */
$upload_dirs = array(
    G5_DATA_PATH.'/randombox',
    G5_DATA_PATH.'/randombox/box',
    G5_DATA_PATH.'/randombox/item'
);

foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
        @chmod($dir, 0755);
    }
}

// ===================================
// 설치 완료
// ===================================
?>

<div style="margin: 50px; padding: 30px; border: 2px solid #2c3e50; background: #ecf0f1; border-radius: 5px;">
    <h2 style="color: #2c3e50;">🎰 그누보드 랜덤박스 시스템 설치 완료</h2>
    
    <div style="margin: 20px 0; padding: 20px; background: #fff; border-radius: 3px;">
        <h3>✅ 설치 내역</h3>
        <ul style="line-height: 1.8;">
            <li>데이터베이스 테이블 생성 완료</li>
            <li>기본 설정값 입력 완료</li>
            <li>업로드 디렉토리 생성 완료</li>
        </ul>
    </div>
    
    <div style="margin: 20px 0; padding: 20px; background: #fff; border-radius: 3px;">
        <h3>📁 생성된 테이블</h3>
        <ul style="line-height: 1.8;">
            <li><?php echo $g5['g5_prefix']; ?>randombox - 랜덤박스 정보</li>
            <li><?php echo $g5['g5_prefix']; ?>randombox_items - 아이템 정보</li>
            <li><?php echo $g5['g5_prefix']; ?>randombox_history - 구매/획득 기록</li>
            <li><?php echo $g5['g5_prefix']; ?>randombox_config - 시스템 설정</li>
            <li><?php echo $g5['g5_prefix']; ?>randombox_ceiling - 천장 시스템</li>
            <li><?php echo $g5['g5_prefix']; ?>randombox_gift - 선물하기</li>
        </ul>
    </div>
    
    <div style="margin: 20px 0; padding: 20px; background: #fff; border-radius: 3px;">
        <h3>🔧 다음 단계</h3>
        <ol style="line-height: 1.8;">
            <li>관리자 페이지에서 랜덤박스 관리 메뉴 추가</li>
            <li>랜덤박스 및 아이템 등록</li>
            <li>사용자 페이지 설정</li>
        </ol>
    </div>
    
    <div style="margin-top: 30px;">
        <p style="text-align:center;font-size:1.1em;color:#27ae60;margin-bottom:20px;">
            잠시 후 자동으로 플러그인 관리 페이지로 이동합니다...
        </p>
    </div>
    
    <script>
    setTimeout(function() {
        location.href = '<?php echo G5_ADMIN_URL; ?>/randombox_admin/plugin.php';
    }, 2000);
    </script>
</div>

<style>
.btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    text-decoration: none;
    border-radius: 3px;
    font-weight: bold;
}
.btn-primary {
    background: #3498db;
    color: #fff;
}
.btn-primary:hover {
    background: #2980b9;
}
.btn-default {
    background: #95a5a6;
    color: #fff;
}
.btn-default:hover {
    background: #7f8c8d;
}
.btn-danger {
    background: #e74c3c;
    color: #fff;
}
.btn-danger:hover {
    background: #c0392b;
}
</style>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>