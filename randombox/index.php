<?php
/*
 * 파일명: index.php
 * 위치: /randombox/
 * 기능: 랜덤박스 메인 페이지
 * 작성일: 2025-01-04
 * 수정일: 2025-07-17
 */

include_once('./_common.php');

// 페이지 설정
$page_name = 'index';
$page_title = '랜덤박스 스토어';
$page_css = 'index';

// 활성화된 박스 목록
$box_list = get_randombox_list();

// 통계 데이터
$today = date('Y-m-d');

// 오늘 판매량
$sql = "SELECT COUNT(*) as cnt FROM {$g5['g5_prefix']}randombox_history 
        WHERE DATE(rbh_created_at) = '{$today}'";
$today_sales = sql_fetch($sql);

// 내 구매 통계
$sql = "SELECT 
        COUNT(*) as total_count,
        COUNT(CASE WHEN rbi_grade IN ('rare', 'epic', 'legendary') THEN 1 END) as rare_count,
        IFNULL(SUM(rb_price), 0) as total_spent,
        IFNULL(SUM(rbi_value), 0) as total_earned
        FROM {$g5['g5_prefix']}randombox_history 
        WHERE mb_id = '{$member['mb_id']}'";
$my_stats = sql_fetch($sql);

// 헤더 포함
include_once('./randombox_head.php');
?>

<div class="rb-index-container">
    
    <!-- ===================================
     * 통계 바
     * =================================== -->
    <div class="rb-stats-bar">
        <div class="rb-stat">
            <span class="rb-stat-label">오늘 판매</span>
            <span class="rb-stat-value"><?php echo number_format($today_sales['cnt']); ?></span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">내 구매</span>
            <span class="rb-stat-value"><?php echo number_format($my_stats['total_count']); ?></span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">희귀 획득</span>
            <span class="rb-stat-value"><?php echo number_format($my_stats['rare_count']); ?></span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">사용 포인트</span>
            <span class="rb-stat-value text-danger"><?php echo number_format($my_stats['total_spent']); ?>P</span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">획득 포인트</span>
            <span class="rb-stat-value text-success"><?php echo number_format($my_stats['total_earned']); ?>P</span>
        </div>
        <div class="rb-stat">
            <span class="rb-stat-label">순손익</span>
            <span class="rb-stat-value <?php echo ($my_stats['total_earned'] - $my_stats['total_spent']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo number_format($my_stats['total_earned'] - $my_stats['total_spent']); ?>P
            </span>
        </div>
    </div>
    
    <!-- ===================================
     * 메인 레이아웃
     * =================================== -->
    <div class="rb-layout">
        
        <!-- ===================================
         * 박스 섹션
         * =================================== -->
        <div class="rb-box-section">
            <div class="rb-section-header">
                <h2 class="rb-section-title">랜덤박스 스토어</h2>
                <div class="rb-filter-tabs">
                    <button class="rb-filter-tab active" data-filter="all">전체</button>
                    <button class="rb-filter-tab" data-filter="normal">일반</button>
                    <button class="rb-filter-tab" data-filter="event">이벤트</button>
                    <button class="rb-filter-tab" data-filter="premium">프리미엄</button>
                </div>
            </div>
            
            <?php if (!$box_list) : ?>
            <div class="rb-empty">
                <i class="bi bi-inbox"></i>
                <p>판매 중인 상품이 없습니다</p>
            </div>
            <?php else : ?>
            
            <div class="rb-box-grid">
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
                    
                    // 남은 수량 (전체 수량 제한이 있는 경우)
                    $remaining_qty = null;
                    if ($box['rb_total_qty'] > 0) {
                        $remaining_qty = $box['rb_total_qty'] - $box['rb_sold_qty'];
                    }
                ?>
                
                <div class="rb-box-card" data-type="<?php echo $box['rb_type']; ?>">
                    <?php if ($box['rb_type'] != 'normal') : ?>
                    <span class="rb-box-badge <?php echo $box['rb_type']; ?>">
                        <?php echo get_box_type_name($box['rb_type']); ?>
                    </span>
                    <?php endif; ?>
                    
                    <!-- 박스 이미지를 클릭하면 상세 페이지로 -->
                    <a href="./box_detail.php?id=<?php echo $box['rb_id']; ?>" class="rb-box-link">
                        <div class="rb-box-image">
                            <img src="<?php echo $box_img; ?>" alt="<?php echo $box['rb_name']; ?>">
                            
                            <!-- 호버 시 상세보기 오버레이 -->
                            <div class="rb-box-overlay">
                                <i class="bi bi-eye"></i>
                                <span>상세보기</span>
                            </div>
                        </div>
                    </a>
                    
                    <div class="rb-box-info">
                        <h3 class="rb-box-name">
                            <a href="./box_detail.php?id=<?php echo $box['rb_id']; ?>">
                                <?php echo $box['rb_name']; ?>
                            </a>
                        </h3>
                        
                        <div class="rb-box-meta">
                            <div class="rb-box-meta-item">
                                <i class="bi bi-box2"></i>
                                <?php echo $item_count['cnt']; ?>종
                            </div>
                            
                            <?php if ($box['rb_limit_qty'] > 0) : ?>
                            <div class="rb-box-meta-item">
                                <i class="bi bi-calendar-day"></i>
                                일일 <?php echo $box['rb_limit_qty']; ?>개
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($remaining_qty !== null && $box['rb_show_remaining']) : ?>
                            <div class="rb-box-meta-item <?php echo $remaining_qty < 10 ? 'text-danger' : ''; ?>">
                                <i class="bi bi-archive"></i>
                                잔여 <?php echo number_format($remaining_qty); ?>개
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="rb-box-footer">
                            <div class="rb-box-price">
                                <?php echo number_format($box['rb_price']); ?><small>P</small>
                            </div>
                            
                            <div class="rb-box-actions">
                                <a href="./box_detail.php?id=<?php echo $box['rb_id']; ?>" class="rb-detail-btn" title="상세보기">
                                    <i class="bi bi-info-circle"></i>
                                </a>
                                
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
                </div>
                
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
        
        <!-- ===================================
         * 사이드바
         * =================================== -->
        <aside class="rb-sidebar">
            
            <!-- 내 통계 위젯 -->
            <div class="rb-widget">
                <div class="rb-widget-header">내 통계</div>
                <div class="rb-widget-body">
                    <div class="rb-my-stats">
                        <div class="rb-my-stat">
                            <span class="rb-my-stat-label">총 구매 횟수</span>
                            <span class="rb-my-stat-value"><?php echo number_format($my_stats['total_count']); ?>회</span>
                        </div>
                        <div class="rb-my-stat">
                            <span class="rb-my-stat-label">희귀 아이템</span>
                            <span class="rb-my-stat-value"><?php echo number_format($my_stats['rare_count']); ?>개</span>
                        </div>
                        <div class="rb-my-stat">
                            <span class="rb-my-stat-label">수익률</span>
                            <span class="rb-my-stat-value">
                                <?php 
                                $profit_rate = $my_stats['total_spent'] > 0 ? 
                                    round(($my_stats['total_earned'] / $my_stats['total_spent']) * 100, 1) : 0;
                                echo $profit_rate;
                                ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 실시간 당첨 위젯 -->
            <?php if (get_randombox_config('enable_realtime')) : ?>
            <div class="rb-widget">
                <div class="rb-widget-header">실시간 당첨</div>
                <div class="rb-widget-body">
                    <div class="rb-realtime-list" id="realtimeWinners">
                        <!-- AJAX로 로드 -->
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 인기 박스 위젯 -->
            <div class="rb-widget">
                <div class="rb-widget-header">인기 박스 TOP 5</div>
                <div class="rb-widget-body">
                    <?php
                    // 최근 7일간 가장 많이 팔린 박스
                    $sql = "SELECT rb_id, rb_name, COUNT(*) as sell_count 
                            FROM {$g5['g5_prefix']}randombox_history 
                            WHERE rbh_created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                            GROUP BY rb_id 
                            ORDER BY sell_count DESC 
                            LIMIT 5";
                    $popular_result = sql_query($sql);
                    ?>
                    
                    <div class="rb-popular-list">
                        <?php 
                        $rank = 1;
                        while ($popular = sql_fetch_array($popular_result)) : 
                        ?>
                        <a href="./box_detail.php?id=<?php echo $popular['rb_id']; ?>" class="rb-popular-item">
                            <span class="rb-popular-rank"><?php echo $rank; ?></span>
                            <span class="rb-popular-name"><?php echo $popular['rb_name']; ?></span>
                            <span class="rb-popular-count"><?php echo number_format($popular['sell_count']); ?>개</span>
                        </a>
                        <?php 
                        $rank++;
                        endwhile; 
                        ?>
                    </div>
                </div>
            </div>
            
        </aside>
        
    </div>
</div>

<!-- 모달 포함 -->
<?php include_once('./modals.php'); ?>

<script>
// 필터 기능
document.querySelectorAll('.rb-filter-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.rb-filter-tab').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        document.querySelectorAll('.rb-box-card').forEach(box => {
            if (filter === 'all' || box.dataset.type === filter) {
                box.style.display = '';
            } else {
                box.style.display = 'none';
            }
        });
    });
});

// 구매 버튼 클릭
$('.rb-buy-btn').on('click', function(e) {
    e.stopPropagation(); // 링크 클릭 방지
    
    const boxId = $(this).data('box-id');
    const boxName = $(this).data('box-name');
    const boxPrice = $(this).data('box-price');
    const boxImage = $(this).data('box-image');
    
    showPurchaseModal(boxId, boxName, boxPrice, boxImage);
});

// 실시간 당첨 현황
<?php if (get_randombox_config('enable_realtime')) : ?>
function loadRealtimeWinners() {
    $.get('./ajax/get_realtime_winners.php', function(data) {
        if (data.status && data.winners) {
            const html = data.winners.slice(0, 5).map(winner => `
                <div class="rb-realtime-item">
                    <div class="rb-realtime-avatar">${winner.display_name.charAt(0)}</div>
                    <div class="rb-realtime-info">
                        <div class="rb-realtime-user">${winner.display_name}</div>
                        <div class="rb-realtime-prize ${winner.rbi_grade}">${winner.rbi_name}</div>
                    </div>
                    <div class="rb-realtime-time">${winner.time_ago}</div>
                </div>
            `).join('');
            
            $('#realtimeWinners').html(html || '<p style="text-align:center;color:#999;font-size:12px;">아직 당첨자가 없습니다</p>');
        }
    });
}

loadRealtimeWinners();
setInterval(loadRealtimeWinners, 10000);
<?php endif; ?>
</script>

<?php
include_once('./randombox_tail.php');
?>