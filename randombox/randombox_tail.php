<?php
/*
 * 파일명: randombox_tail.php
 * 위치: /randombox/
 * 기능: 랜덤박스 전용 푸터
 * 작성일: 2025-07-17
 */

if (!defined('_GNUBOARD_')) exit;
?>
    </main>
    <!-- 메인 컨텐츠 끝 -->
    
    <!-- ===================================
     * 공통 푸터
     * =================================== -->
    <footer class="rb-footer">
        <div class="rb-footer-inner">
            <div class="rb-footer-info">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $config['cf_title']; ?>. All rights reserved.</p>
                <p class="rb-footer-desc">
                    랜덤박스 시스템 v2.0 | 
                    <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=privacy">개인정보처리방침</a> | 
                    <a href="<?php echo G5_BBS_URL; ?>/content.php?co_id=provision">이용약관</a>
                </p>
            </div>
            
            <?php if ($is_admin) : ?>
            <div class="rb-footer-admin">
                <a href="<?php echo G5_ADMIN_URL; ?>/randombox_admin/plugin.php" target="_blank">
                    <i class="bi bi-gear"></i> 관리자
                </a>
            </div>
            <?php endif; ?>
        </div>
    </footer>
    
</div>
<!-- #randombox_wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- 랜덤박스 공통 스크립트 -->
<script src="./js/randombox.common.js?v=<?php echo time(); ?>"></script>

<!-- 페이지별 스크립트 -->
<?php if (isset($page_script) && $page_script) : ?>
<script src="./js/<?php echo $page_script; ?>.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>

<script>
// 전역 설정
var g5_url = "<?php echo G5_URL; ?>";
var g5_bbs_url = "<?php echo G5_BBS_URL; ?>";
var g5_is_member = <?php echo $is_member ? 'true' : 'false'; ?>;
var g5_is_admin = <?php echo $is_admin ? 'true' : 'false'; ?>;

// 모바일 메뉴 토글
$(document).on('click', '.rb-mobile-menu-btn', function() {
    $('.rb-mobile-menu').toggleClass('active');
    $('body').toggleClass('rb-mobile-menu-open');
});

// 모바일 메뉴 외부 클릭 시 닫기
$(document).on('click', function(e) {
    if (!$(e.target).closest('.rb-mobile-menu, .rb-mobile-menu-btn').length) {
        $('.rb-mobile-menu').removeClass('active');
        $('body').removeClass('rb-mobile-menu-open');
    }
});

// 사용자 드롭다운 메뉴
$(document).on('click', '.rb-user-btn', function(e) {
    e.stopPropagation();
    $(this).next('.rb-user-dropdown').toggleClass('active');
});

// 드롭다운 외부 클릭 시 닫기
$(document).on('click', function(e) {
    if (!$(e.target).closest('.rb-user-menu').length) {
        $('.rb-user-dropdown').removeClass('active');
    }
});

// 포인트 실시간 업데이트 함수
function updateUserPoint() {
    if (!g5_is_member) return;
    
    $.ajax({
        url: g5_bbs_url + '/ajax.mb_point.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.point !== undefined) {
                $('#userPoint').text(number_format(response.point) + 'P');
            }
        }
    });
}

// 숫자 포맷 함수
function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>