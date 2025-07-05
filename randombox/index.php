<?php
/*
 * 파일명: index.php
 * 위치: /randombox/
 * 기능: 랜덤박스 메인 페이지 - 컴팩트 모던 디자인
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

include_once('./_common.php');

// ===================================
// 데이터 조회
// ===================================

/* 활성화된 박스 목록 */
$box_list = get_randombox_list();

/* 통계 데이터 */
$today = date('Y-m-d');

// 오늘 판매량
$sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history 
        WHERE DATE(rbh_created_at) = '{$today}'";
$today_sales = sql_fetch($sql);

// 내 구매 통계
$sql = "SELECT 
        COUNT(*) as total_count,
        COUNT(CASE WHEN rbi_grade IN ('rare', 'epic', 'legendary') THEN 1 END) as rare_count
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}'";
$my_stats = sql_fetch($sql);

// ===================================
// 페이지 헤더
// ===================================

$g5['title'] = '랜덤박스';
include_once(G5_PATH.'/head.php');
?>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
/* ===================================
 * 기본 설정
 * =================================== */
:root {
    --primary: #000;
    --dark: #222;
    --gray: #666;
    --light: #f8f8f8;
    --border: #ddd;
    --white: #fff;
}

* {
    box-sizing: border-box;
}

body {
    background: #f5f5f5;
}

/* ===================================
 * 메인 컨테이너
 * =================================== */

/* 컨테이너 */
.rb-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* ===================================
 * 헤더 영역
 * =================================== */

/* 헤더 */
.rb-header {
    background: var(--white);
    border: 1px solid var(--border);
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rb-brand {
    display: flex;
    align-items: center;
    gap: 40px;
}

.rb-logo {
    font-size: 22px;
    font-weight: 900;
    color: var(--primary);
    text-decoration: none;
}

.rb-nav {
    display: flex;
    gap: 20px;
}

.rb-nav a {
    color: var(--gray);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
}

.rb-nav a:hover {
    color: var(--primary);
}

/* 유저 정보 */
.rb-user {
    display: flex;
    align-items: center;
    gap: 30px;
}

.rb-balance {
    text-align: right;
}

.rb-balance-label {
    font-size: 11px;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rb-balance-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary);
}

.rb-actions {
    display: flex;
    gap: 8px;
}

.rb-btn {
    padding: 8px 16px;
    background: var(--primary);
    color: var(--white);
    border: 1px solid var(--primary);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.rb-btn:hover {
    background: var(--dark);
    border-color: var(--dark);
}

.rb-btn-outline {
    background: var(--white);
    color: var(--primary);
}

.rb-btn-outline:hover {
    background: var(--primary);
    color: var(--white);
}

/* ===================================
 * 콘텐츠 영역
 * =================================== */

.rb-content {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
}

/* ===================================
 * 메인 영역
 * =================================== */

.rb-main-area {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* 타이틀 바 */
.rb-title-bar {
    background: var(--white);
    border: 1px solid var(--border);
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rb-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
    margin: 0;
}

/* 필터 */
.rb-filters {
    display: flex;
    gap: 0;
    background: var(--light);
    padding: 2px;
    border-radius: 4px;
}

.rb-filter {
    padding: 6px 16px;
    background: transparent;
    border: none;
    color: var(--gray);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 3px;
}

.rb-filter:hover {
    color: var(--primary);
}

.rb-filter.active {
    background: var(--primary);
    color: var(--white);
}

/* ===================================
 * 박스 그리드
 * =================================== */

/* 그리드 */
.rb-boxes {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* 박스 카드 */
.rb-box {
    background: var(--white);
    border: 1px solid var(--border);
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
}

.rb-box:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* 박스 타입 */
.rb-box-type {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 10px;
    background: var(--primary);
    color: var(--white);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 1;
}

.rb-box-type.premium {
    background: #FFD700;
    color: var(--primary);
}

.rb-box-type.event {
    background: #FF4444;
}

/* 박스 이미지 */
.rb-box-img {
    height: 200px;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.rb-box-img img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* 박스 정보 */
.rb-box-info {
    padding: 16px;
}

.rb-box-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--primary);
    margin: 0 0 8px;
}

.rb-box-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    font-size: 13px;
    color: var(--gray);
}

.rb-box-stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* 박스 구매 */
.rb-box-buy {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.rb-box-price {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary);
}

.rb-box-price small {
    font-size: 14px;
    font-weight: 400;
    color: var(--gray);
}

.rb-buy-btn {
    padding: 8px 20px;
    background: var(--primary);
    color: var(--white);
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.rb-buy-btn:hover:not(:disabled) {
    background: var(--dark);
}

.rb-buy-btn:disabled {
    background: var(--border);
    color: var(--gray);
    cursor: not-allowed;
}

/* ===================================
 * 사이드바
 * =================================== */

.rb-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* 통계 위젯 */
.rb-widget {
    background: var(--white);
    border: 1px solid var(--border);
    padding: 20px;
}

.rb-widget-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--primary);
    margin: 0 0 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* 통계 아이템 */
.rb-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rb-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rb-stat-name {
    font-size: 13px;
    color: var(--gray);
}

.rb-stat-val {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
}

/* 실시간 당첨 */
.rb-winners {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rb-winner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--light);
    border-radius: 4px;
}

.rb-winner-avatar {
    width: 32px;
    height: 32px;
    background: var(--primary);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
}

.rb-winner-info {
    flex: 1;
}

.rb-winner-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
}

.rb-winner-item {
    font-size: 12px;
    color: var(--gray);
}

.rb-winner-time {
    font-size: 11px;
    color: var(--gray);
}

/* ===================================
 * 빈 상태
 * =================================== */

.rb-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
    color: var(--gray);
}

.rb-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

/* ===================================
 * 반응형
 * =================================== */

@media (max-width: 1024px) {
    .rb-content {
        grid-template-columns: 1fr;
    }
    
    .rb-sidebar {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .rb-header {
        flex-direction: column;
        gap: 16px;
    }
    
    .rb-brand {
        flex-direction: column;
        gap: 16px;
        width: 100%;
    }
    
    .rb-user {
        width: 100%;
        justify-content: space-between;
    }
    
    .rb-boxes {
        grid-template-columns: 1fr;
    }
    
    .rb-sidebar {
        grid-template-columns: 1fr;
    }
    
    .rb-title-bar {
        flex-direction: column;
        gap: 12px;
    }
}
</style>

<div class="rb-main">
    
    <!-- ===================================
     * 헤더
     * =================================== -->
    <header class="rb-header">
        <div class="rb-brand">
            <a href="./" class="rb-logo">RANDOMBOX</a>
            <nav class="rb-nav">
                <a href="./">스토어</a>
                <a href="./history.php">구매내역</a>
                <?php if (get_randombox_config('enable_gift')) : ?>
                <a href="./gift.php">선물함</a>
                <?php endif; ?>
            </nav>
        </div>
        
        <div class="rb-user">
            <div class="rb-balance">
                <div class="rb-balance-label">보유 포인트</div>
                <div class="rb-balance-value" id="userPoint"><?php echo number_format($member['mb_point']); ?>P</div>
            </div>
            <div class="rb-actions">
                <a href="./history.php" class="rb-btn rb-btn-outline">
                    <i class="bi bi-clock-history"></i>
                    내역
                </a>
            </div>
        </div>
    </header>
    
    <!-- ===================================
     * 콘텐츠
     * =================================== -->
    <div class="rb-content">
        
        <!-- ===================================
         * 메인 영역
         * =================================== -->
        <div class="rb-main-area">
            
            <!-- 타이틀 바 -->
            <div class="rb-title-bar">
                <h1 class="rb-title">전체 상품</h1>
                
                <div class="rb-filters">
                    <button class="rb-filter active" data-filter="all">전체</button>
                    <button class="rb-filter" data-filter="normal">일반</button>
                    <button class="rb-filter" data-filter="event">이벤트</button>
                    <button class="rb-filter" data-filter="premium">프리미엄</button>
                </div>
            </div>
            
            <!-- 박스 그리드 -->
            <?php if (!$box_list) : ?>
            <div class="rb-empty">
                <i class="bi bi-inbox"></i>
                <p>판매 중인 상품이 없습니다</p>
            </div>
            <?php else : ?>
            
            <div class="rb-boxes">
                <?php foreach ($box_list as $box) : 
                    // 박스 이미지
                    $box_img = './img/box-default.png';
                    if ($box['rb_image'] && file_exists(G5_DATA_PATH.'/randombox/box/'.$box['rb_image'])) {
                        $box_img = G5_DATA_URL.'/randombox/box/'.$box['rb_image'];
                    }
                    
                    // 구매 가능 여부
                    $can_purchase = check_randombox_purchase($box['rb_id'], $member['mb_id']);
                    
                    // 아이템 수
                    $sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_items 
                            WHERE rb_id = '{$box['rb_id']}' AND rbi_status = 1";
                    $item_count = sql_fetch($sql);
                ?>
                
                <div class="rb-box" data-type="<?php echo $box['rb_type']; ?>">
                    <?php if ($box['rb_type'] != 'normal') : ?>
                    <span class="rb-box-type <?php echo $box['rb_type']; ?>">
                        <?php echo get_box_type_name($box['rb_type']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <div class="rb-box-img">
                        <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
                    </div>
                    
                    <div class="rb-box-info">
                        <h3 class="rb-box-name"><?php echo $box['rb_name']; ?></h3>
                        
                        <div class="rb-box-stats">
                            <div class="rb-box-stat">
                                <i class="bi bi-box2"></i>
                                <?php echo $item_count['cnt']; ?>종
                            </div>
                            
                            <?php if ($box['rb_limit_qty'] > 0) : ?>
                            <div class="rb-box-stat">
                                <i class="bi bi-calendar-day"></i>
                                일일 <?php echo $box['rb_limit_qty']; ?>개
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="rb-box-buy">
                            <div class="rb-box-price">
                                <?php echo number_format($box['rb_price']); ?><small>P</small>
                            </div>
                            
                            <button class="rb-buy-btn" 
                                    data-box-id="<?php echo $box['rb_id']; ?>"
                                    data-box-name="<?php echo $box['rb_name']; ?>"
                                    data-box-price="<?php echo $box['rb_price']; ?>"
                                    data-box-image="<?php echo $box_img; ?>"
                                    <?php echo !$can_purchase['status'] ? 'disabled' : ''; ?>>
                                구매
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
        
        <!-- ===================================
         * 사이드바
         * =================================== -->
        <aside class="rb-sidebar">
            
            <!-- 통계 위젯 -->
            <div class="rb-widget">
                <h3 class="rb-widget-title">내 통계</h3>
                <div class="rb-stats">
                    <div class="rb-stat-item">
                        <span class="rb-stat-name">구매 횟수</span>
                        <span class="rb-stat-val"><?php echo number_format($my_stats['total_count']); ?></span>
                    </div>
                    <div class="rb-stat-item">
                        <span class="rb-stat-name">희귀 획득</span>
                        <span class="rb-stat-val"><?php echo number_format($my_stats['rare_count']); ?></span>
                    </div>
                    <div class="rb-stat-item">
                        <span class="rb-stat-name">오늘 판매</span>
                        <span class="rb-stat-val"><?php echo number_format($today_sales['cnt']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- 실시간 당첨 -->
            <?php if (get_randombox_config('enable_realtime')) : ?>
            <div class="rb-widget">
                <h3 class="rb-widget-title">실시간 당첨</h3>
                <div class="rb-winners" id="realtimeWinners">
                    <!-- AJAX로 로드 -->
                </div>
            </div>
            <?php endif; ?>
            
        </aside>
        
    </div>
</div>

<!-- 모달 포함 -->
<?php include_once('./modals.php'); ?>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="./randombox.js?v=<?php echo time(); ?>"></script>
<script>
// 필터 기능
document.querySelectorAll('.rb-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.rb-filter').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        document.querySelectorAll('.rb-box').forEach(box => {
            if (filter === 'all' || box.dataset.type === filter) {
                box.style.display = '';
            } else {
                box.style.display = 'none';
            }
        });
    });
});

// 실시간 당첨 현황
<?php if (get_randombox_config('enable_realtime')) : ?>
function loadRealtimeWinners() {
    $.get('./ajax/get_realtime_winners.php', function(data) {
        if (data.status && data.winners) {
            const html = data.winners.slice(0, 5).map(winner => `
                <div class="rb-winner">
                    <div class="rb-winner-avatar">${winner.display_name.charAt(0)}</div>
                    <div class="rb-winner-info">
                        <div class="rb-winner-name">${winner.display_name}</div>
                        <div class="rb-winner-item">${winner.rbi_name}</div>
                    </div>
                    <div class="rb-winner-time">${winner.time_ago}</div>
                </div>
            `).join('');
            
            $('#realtimeWinners').html(html || '<p style="text-align:center;color:#999;font-size:13px;">아직 당첨자가 없습니다</p>');
        }
    });
}

loadRealtimeWinners();
setInterval(loadRealtimeWinners, 10000);
<?php endif; ?>
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>