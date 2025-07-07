<?php
/*
 * 파일명: box_stats.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스별 상세 통계 페이지
 * 작성일: 2025-01-04
 */

$sub_menu = "300940";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$rb_id = (int)$_GET['rb_id'];
if (!$rb_id) {
    alert('박스를 선택해 주세요.', './box_list.php');
}

// 박스 정보
$box = get_randombox($rb_id);
if (!$box) {
    alert('존재하지 않는 박스입니다.', './box_list.php');
}

$g5['title'] = $box['rb_name'] . ' - 상세 통계';
include_once('./admin.head.php');

// ===================================
// 기간 설정
// ===================================

/* 기본 기간: 최근 30일 */
if (!isset($fr_date) || !$fr_date) $fr_date = date('Y-m-d', strtotime('-30 days'));
if (!isset($to_date) || !$to_date) $to_date = date('Y-m-d');

// ===================================
// 통계 데이터 조회
// ===================================

/* 전체 통계 */
$sql = "SELECT 
        COUNT(*) as total_count,
        COUNT(DISTINCT mb_id) as total_users,
        SUM(rb_price) as total_sales,
        SUM(rbi_value) as total_value_given
        FROM {$g5['g5_prefix']}randombox_history
        WHERE rb_id = '{$rb_id}'
        AND rbh_created_at >= '{$fr_date} 00:00:00' 
        AND rbh_created_at <= '{$to_date} 23:59:59'";
$total_stats = sql_fetch($sql);

// 데이터가 없는 경우 기본값 설정
if (!$total_stats['total_count']) {
    $total_stats['total_count'] = 0;
    $total_stats['total_users'] = 0;
    $total_stats['total_sales'] = 0;
    $total_stats['total_value_given'] = 0;
}

/* 아이템별 배출 통계 */
if ($total_stats['total_count'] > 0) {
    $sql = "SELECT 
            h.rbi_id,
            h.rbi_name,
            h.rbi_grade,
            i.rbi_probability,
            i.rbi_limit_qty,
            COUNT(*) as issued_count,
            (COUNT(*) / {$total_stats['total_count']} * 100) as actual_rate
            FROM {$g5['g5_prefix']}randombox_history h
            LEFT JOIN {$g5['g5_prefix']}randombox_items i ON h.rbi_id = i.rbi_id
            WHERE h.rb_id = '{$rb_id}'
            AND h.rbh_created_at >= '{$fr_date} 00:00:00' 
            AND h.rbh_created_at <= '{$to_date} 23:59:59'
            GROUP BY h.rbi_id
            ORDER BY issued_count DESC";
    $item_result = sql_query($sql);
} else {
    $item_result = false;
}

/* 등급별 통계 */
$grade_stats = array(
    'normal' => array('count' => 0, 'rate' => 0),
    'rare' => array('count' => 0, 'rate' => 0),
    'epic' => array('count' => 0, 'rate' => 0),
    'legendary' => array('count' => 0, 'rate' => 0)
);

if ($total_stats['total_count'] > 0) {
    $sql = "SELECT 
            rbi_grade,
            COUNT(*) as count,
            (COUNT(*) / {$total_stats['total_count']} * 100) as rate
            FROM {$g5['g5_prefix']}randombox_history
            WHERE rb_id = '{$rb_id}'
            AND rbh_created_at >= '{$fr_date} 00:00:00' 
            AND rbh_created_at <= '{$to_date} 23:59:59'
            GROUP BY rbi_grade";
    $grade_result = sql_query($sql);
    
    while ($row = sql_fetch_array($grade_result)) {
        if (isset($grade_stats[$row['rbi_grade']])) {
            $grade_stats[$row['rbi_grade']] = $row;
        }
    }
}

/* 일별 판매 추이 */
$sql = "SELECT 
        DATE(rbh_created_at) as date,
        COUNT(*) as count,
        SUM(rb_price) as sales
        FROM {$g5['g5_prefix']}randombox_history
        WHERE rb_id = '{$rb_id}'
        AND rbh_created_at >= '{$fr_date} 00:00:00' 
        AND rbh_created_at <= '{$to_date} 23:59:59'
        GROUP BY DATE(rbh_created_at)
        ORDER BY date";
$daily_result = sql_query($sql);

$daily_stats = array();
while ($row = sql_fetch_array($daily_result)) {
    $daily_stats[] = $row;
}

/* TOP 구매자 */
$sql = "SELECT 
        h.mb_id,
        m.mb_nick,
        COUNT(*) as purchase_count,
        SUM(h.rb_price) as total_spent
        FROM {$g5['g5_prefix']}randombox_history h
        LEFT JOIN {$g5['member_table']} m ON h.mb_id = m.mb_id
        WHERE h.rb_id = '{$rb_id}'
        AND h.rbh_created_at >= '{$fr_date} 00:00:00' 
        AND h.rbh_created_at <= '{$to_date} 23:59:59'
        GROUP BY h.mb_id
        ORDER BY purchase_count DESC
        LIMIT 10";
$top_users_result = sql_query($sql);

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
        <span class="ov_txt">기간 총 판매</span>
        <span class="ov_num"><?php echo number_format($total_stats['total_count']); ?>건</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">기간 총 매출</span>
        <span class="ov_num"><?php echo number_format($total_stats['total_sales']); ?>P</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">구매 회원수</span>
        <span class="ov_num"><?php echo number_format($total_stats['total_users']); ?>명</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">지급된 포인트</span>
        <span class="ov_num"><?php echo number_format($total_stats['total_value_given']); ?>P</span>
    </span>
</div>

<form name="fstats" method="get" class="local_sch01 local_sch">
<input type="hidden" name="rb_id" value="<?php echo $rb_id; ?>">
<label for="fr_date" class="sound_only">시작일</label>
<input type="text" name="fr_date" value="<?php echo $fr_date; ?>" id="fr_date" class="frm_input" size="11" maxlength="10">
~
<label for="to_date" class="sound_only">종료일</label>
<input type="text" name="to_date" value="<?php echo $to_date; ?>" id="to_date" class="frm_input" size="11" maxlength="10">
<input type="submit" value="검색" class="btn_submit">
</form>

<!-- 일별 판매 추이 차트 -->
<section class="stat_section">
    <h2 class="h2_frm">일별 판매 추이</h2>
    <div class="chart_container">
        <canvas id="dailyChart" height="80"></canvas>
    </div>
</section>

<!-- 등급별 배출 현황 -->
<section class="stat_section">
    <h2 class="h2_frm">등급별 배출 현황</h2>
    <div class="stat_grid">
        <?php
        $grades = array('normal', 'rare', 'epic', 'legendary');
        foreach ($grades as $grade) :
            $count = isset($grade_stats[$grade]) ? $grade_stats[$grade]['count'] : 0;
            $rate = isset($grade_stats[$grade]) ? $grade_stats[$grade]['rate'] : 0;
        ?>
        <div class="stat_card grade_<?php echo $grade; ?>">
            <h3><?php echo get_grade_name($grade); ?></h3>
            <div class="stat_value"><?php echo number_format($count); ?>개</div>
            <div class="stat_percent"><?php echo number_format($rate, 2); ?>%</div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- 아이템별 배출 통계 -->
<section class="stat_section">
    <h2 class="h2_frm">아이템별 배출 통계</h2>
    
    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption>아이템별 배출 통계</caption>
        <thead>
        <tr>
            <th scope="col">아이템명</th>
            <th scope="col" width="80">등급</th>
            <th scope="col" width="100">설정 확률</th>
            <th scope="col" width="100">실제 배출률</th>
            <th scope="col" width="100">배출 수량</th>
            <th scope="col" width="120">수량 제한</th>
            <th scope="col" width="100">편차</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ($item_result) {
            $i = 0;
            while ($row = sql_fetch_array($item_result)) {
                $bg = ($i % 2) ? 'bg1' : 'bg0';
                
                // 편차 계산
                $deviation = $row['actual_rate'] - $row['rbi_probability'];
                $deviation_class = '';
                if (abs($deviation) > 1) {
                    $deviation_class = $deviation > 0 ? 'txt_over' : 'txt_under';
                }
        ?>
        <tr class="<?php echo $bg; ?>">
            <td class="td_left"><?php echo $row['rbi_name']; ?></td>
            <td class="td_grade">
                <span class="item_grade grade_<?php echo $row['rbi_grade']; ?>">
                    <?php echo get_grade_name($row['rbi_grade']); ?>
                </span>
            </td>
            <td class="td_num"><?php echo number_format($row['rbi_probability'], 2); ?>%</td>
            <td class="td_num"><?php echo number_format($row['actual_rate'], 2); ?>%</td>
            <td class="td_num"><?php echo number_format($row['issued_count']); ?></td>
            <td class="td_num">
                <?php if ($row['rbi_limit_qty'] > 0) : ?>
                    <?php echo number_format($row['issued_count']); ?> / <?php echo number_format($row['rbi_limit_qty']); ?>
                <?php else : ?>
                    무제한
                <?php endif; ?>
            </td>
            <td class="td_num <?php echo $deviation_class; ?>">
                <?php echo ($deviation > 0 ? '+' : '') . number_format($deviation, 2); ?>%p
            </td>
        </tr>
        <?php
                $i++;
            }
        }
        
        if (!$item_result || $i == 0) {
            echo '<tr><td colspan="7" class="empty_table">해당 기간에 배출된 아이템이 없습니다.</td></tr>';
        }
        ?>
        </tbody>
        </table>
    </div>
</section>

<!-- TOP 구매자 -->
<section class="stat_section">
    <h2 class="h2_frm">TOP 10 구매자</h2>
    
    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption>TOP 10 구매자</caption>
        <thead>
        <tr>
            <th scope="col" width="60">순위</th>
            <th scope="col">회원 ID</th>
            <th scope="col">닉네임</th>
            <th scope="col" width="100">구매 횟수</th>
            <th scope="col" width="120">사용 포인트</th>
            <th scope="col" width="100">평균 구매</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $rank = 0;
        while ($row = sql_fetch_array($top_users_result)) {
            $rank++;
            $bg = ($rank % 2) ? 'bg1' : 'bg0';
            $avg_purchase = $row['purchase_count'] > 0 ? $row['total_spent'] / $row['purchase_count'] : 0;
        ?>
        <tr class="<?php echo $bg; ?>">
            <td class="td_num"><?php echo $rank; ?></td>
            <td class="td_center"><?php echo $row['mb_id']; ?></td>
            <td class="td_center"><?php echo $row['mb_nick'] ? $row['mb_nick'] : '-'; ?></td>
            <td class="td_num"><?php echo number_format($row['purchase_count']); ?>회</td>
            <td class="td_num"><?php echo number_format($row['total_spent']); ?>P</td>
            <td class="td_num"><?php echo number_format($avg_purchase); ?>P</td>
        </tr>
        <?php
        }
        
        if ($rank == 0) {
            echo '<tr><td colspan="6" class="empty_table">해당 기간에 구매 내역이 없습니다.</td></tr>';
        }
        ?>
        </tbody>
        </table>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 일별 판매 추이 차트
var ctx = document.getElementById('dailyChart').getContext('2d');
var dailyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php 
            $labels = array();
            foreach ($daily_stats as $stat) {
                $labels[] = "'" . $stat['date'] . "'";
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            label: '판매수량',
            data: [<?php 
                $counts = array();
                foreach ($daily_stats as $stat) {
                    $counts[] = $stat['count'];
                }
                echo implode(',', $counts);
            ?>],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            yAxisID: 'y-count'
        }, {
            label: '매출(포인트)',
            data: [<?php 
                $sales = array();
                foreach ($daily_stats as $stat) {
                    $sales[] = $stat['sales'];
                }
                echo implode(',', $sales);
            ?>],
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46, 204, 113, 0.1)',
            yAxisID: 'y-sales'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            'y-count': {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: '판매수량'
                }
            },
            'y-sales': {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: '매출(포인트)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});

// 달력 UI
$(function() {
    $("#fr_date, #to_date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd",
        showButtonPanel: true
    });
});
</script>

<style>
/* 통계 페이지 전용 스타일 */
.stat_section {
    margin: 30px 0;
}

.h2_frm {
    margin: 30px 0 20px;
    padding: 10px 0;
    border-bottom: 2px solid #2c3e50;
    font-size: 1.3em;
    color: #2c3e50;
}

.chart_container {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.stat_grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat_card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    text-align: center;
}

.stat_card h3 {
    margin: 0 0 15px;
    font-size: 1.1em;
}

.stat_value {
    font-size: 2em;
    font-weight: bold;
    margin: 10px 0;
}

.stat_percent {
    font-size: 1.2em;
    color: #666;
}

/* 등급별 색상 */
.grade_normal { border-top: 4px solid #95a5a6; }
.grade_rare { border-top: 4px solid #3498db; }
.grade_epic { border-top: 4px solid #9b59b6; }
.grade_legendary { border-top: 4px solid #e74c3c; }

/* 아이템 등급 스타일 */
.item_grade {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 3px;
    font-size: 0.9em;
    font-weight: bold;
}

.item_grade.grade_normal {
    background: rgba(149, 165, 166, 0.2);
    color: #7f8c8d;
}

.item_grade.grade_rare {
    background: rgba(52, 152, 219, 0.2);
    color: #2980b9;
}

.item_grade.grade_epic {
    background: rgba(155, 89, 182, 0.2);
    color: #8e44ad;
}

.item_grade.grade_legendary {
    background: rgba(231, 76, 60, 0.2);
    color: #c0392b;
}

/* 편차 표시 */
.txt_over {
    color: #e74c3c;
    font-weight: bold;
}

.txt_under {
    color: #3498db;
    font-weight: bold;
}

/* 테이블 스타일 */
.td_grade {
    text-align: center;
}

.td_num {
    text-align: right;
}

.td_center {
    text-align: center;
}

.td_left {
    text-align: left;
}

.bg0 {
    background-color: #fafafa;
}

.bg1 {
    background-color: #f5f5f5;
}

.empty_table {
    padding: 50px 0;
    text-align: center;
    color: #999;
}
</style>

<?php
include_once('./admin.tail.php');
?>