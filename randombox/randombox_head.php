<?php
/*
 * 파일명: randombox_head.php
 * 위치: /randombox/
 * 기능: 랜덤박스 전용 헤더
 * 작성일: 2025-07-17
 */

if (!defined('_GNUBOARD_')) exit;

// 랜덤박스 라이브러리 포함
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox_coupon.lib.php');

// 시스템 활성화 확인
if (!get_randombox_config('system_enable')) {
    alert('랜덤박스 시스템이 비활성화되어 있습니다.');
}

// 점검 모드 확인
if (get_randombox_config('maintenance_mode') && !$is_admin) {
    $maintenance_msg = get_randombox_config('maintenance_msg');
    alert($maintenance_msg ?: '시스템 점검 중입니다.');
}

// 최소 레벨 확인
$min_level = (int)get_randombox_config('min_level');
if ($member['mb_level'] < $min_level) {
    alert("레벨 {$min_level} 이상만 이용할 수 있습니다.");
}

// 페이지 타이틀 설정
$g5['title'] = isset($page_title) ? $page_title . ' - 랜덤박스' : '랜덤박스';

// 그누보드 헤더 포함
include_once(G5_PATH.'/head.sub.php');
?>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<!-- 랜덤박스 공통 스타일 -->
<link rel="stylesheet" href="./css/randombox.common.css?v=<?php echo time(); ?>">

<!-- 페이지별 스타일 -->
<?php if (isset($page_css) && $page_css) : ?>
<link rel="stylesheet" href="./css/<?php echo $page_css; ?>.css?v=<?php echo time(); ?>">
<?php endif; ?>

<div id="randombox_wrapper">
    
    <!-- ===================================
     * 공통 헤더
     * =================================== -->
    <header class="rb-header">
        <div class="rb-header-inner">
            <!-- 로고 -->
            <div class="rb-logo">
                <a href="./">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>RANDOMBOX</span>
                </a>
            </div>
            
            <!-- 네비게이션 -->
            <nav class="rb-nav">
                <a href="./" class="<?php echo (!isset($page_name) || $page_name == 'index') ? 'active' : ''; ?>">
                    <i class="bi bi-shop"></i>
                    <span>상점</span>
                </a>
                <a href="./history.php" class="<?php echo (isset($page_name) && $page_name == 'history') ? 'active' : ''; ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>구매내역</span>
                </a>
                <a href="./my_coupons.php" class="<?php echo (isset($page_name) && $page_name == 'my_coupons') ? 'active' : ''; ?>">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>내 교환권</span>
                </a>
                <?php if (get_randombox_config('enable_gift')) : ?>
                <a href="./gift.php" class="<?php echo (isset($page_name) && $page_name == 'gift') ? 'active' : ''; ?>">
                    <i class="bi bi-gift"></i>
                    <span>선물함</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <!-- 사용자 정보 -->
            <div class="rb-user-info">
                <?php if ($is_member) : ?>
                    <div class="rb-user-point">
                        <i class="bi bi-coin"></i>
                        <span id="userPoint"><?php echo number_format($member['mb_point']); ?>P</span>
                    </div>
                    <div class="rb-user-menu">
                        <button type="button" class="rb-user-btn">
                            <i class="bi bi-person-circle"></i>
                            <span><?php echo $member['mb_nick']; ?></span>
                        </button>
                        <div class="rb-user-dropdown">
                            <a href="<?php echo G5_BBS_URL; ?>/member_confirm.php?url=<?php echo G5_BBS_URL; ?>/register_form.php">
                                <i class="bi bi-gear"></i> 정보수정
                            </a>
                            <a href="<?php echo G5_BBS_URL; ?>/logout.php">
                                <i class="bi bi-box-arrow-right"></i> 로그아웃
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <a href="<?php echo G5_BBS_URL; ?>/login.php?url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="rb-login-btn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>로그인</span>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- 모바일 메뉴 버튼 -->
            <button type="button" class="rb-mobile-menu-btn">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </header>
    
    <!-- 모바일 메뉴 -->
    <div class="rb-mobile-menu">
        <div class="rb-mobile-menu-header">
            <?php if ($is_member) : ?>
                <div class="rb-mobile-user">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $member['mb_nick']; ?></span>
                </div>
                <div class="rb-mobile-point">
                    <i class="bi bi-coin"></i>
                    <?php echo number_format($member['mb_point']); ?>P
                </div>
            <?php else : ?>
                <a href="<?php echo G5_BBS_URL; ?>/login.php" class="rb-mobile-login">
                    로그인이 필요합니다
                </a>
            <?php endif; ?>
        </div>
        <nav class="rb-mobile-nav">
            <a href="./">
                <i class="bi bi-shop"></i> 상점
            </a>
            <a href="./history.php">
                <i class="bi bi-clock-history"></i> 구매내역
            </a>
            <a href="./my_coupons.php">
                <i class="bi bi-ticket-perforated"></i> 내 교환권
            </a>
            <?php if (get_randombox_config('enable_gift')) : ?>
            <a href="./gift.php">
                <i class="bi bi-gift"></i> 선물함
            </a>
            <?php endif; ?>
        </nav>
    </div>
    
    <!-- 메인 컨텐츠 시작 -->
    <main class="rb-main">