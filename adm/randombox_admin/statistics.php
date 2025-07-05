<?php
/*
 * 파일명: statistics.php
 * 위치: /adm/randombox_admin/
 * 기능: 랜덤박스 통계 페이지
 * 작성일: 2025-01-04
 */

$sub_menu = "300940";
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/randombox/randombox.lib.php');

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '랜덤박스 통계';
include_once('./admin.head.php');

// ===================================
// 기간 설정
// ===================================

/* 기본 기간: 최근 30일 */
if (!$fr_date) $fr_date = date('Y-m-d', strtotime('-30 days'));
if (!$to_date) $to_date = date('Y-m-d');

$qstr .= "&fr_date={$fr_date}&to_date={$to_date}";

// ===================================
// 전체 통계
// ===================================

/* 전체 판매 통계 */
$sql = "SELECT 
        COUNT(*) as total_count,
        COUNT(DISTINCT mb_id) as total_users,
        SUM(rb_price) as total_sales
        FROM {$g5['g5_prefix']}randombox_history
        WHERE rbh_created_at >= '{$fr_date} 00:00:00' 
        AND rbh_created_at <= '{$to_date} 23:59:59'";
$total_stats = sql_fetch($sql);

/* 오늘 판매 통계 */
$today = date('Y-m-d');
$sql = "SELECT 
        COUNT(*) as today_count,
        SUM(rb_price) as today_sales
        FROM {$g5['g5_prefix']}randombox_history
        WHERE DATE(rbh_created_at) = '{$today}'";
$today_stats = sql_fetch($sql);

// ===================================
// 박스별 통계
// ===================================

$sql = "SELECT 
        h.rb_id,
        h.rb_name,
        COUNT(*) as count,
        SUM(h.rb_price) as sales,
        b.rb_status,
        b.rb_type
        FROM {$g5['g5_prefix']}randombox_history h
        LEFT JOIN {$g5['g5_prefix']}randombox b ON h.rb_id = b.rb_id
        WHERE h.rbh_created_at >= '{$fr_date} 00:00:00' 
        AND h.rbh_created_at <= '{$to_date} 23:59:59'
        GROUP BY h.rb_id
        ORDER BY sales DESC";
$box_result = sql_query($sql);

// ===================================
// 등급별 통계
// ===================================

$sql = "SELECT 
        rbi_grade,
        COUNT(*) as count
        FROM {$g5['g5_prefix']}randombox_history
        WHERE rbh_created_at >= '{$fr_date} 00:00:00' 
        AND rbh_created_at <= '{$to_date} 23:59:59'
        GROUP BY rbi_grade";
$grade_result = sql_query($sql);

$grade_stats = array();
while ($row = sql_fetch_array($grade_result)) {
    $grade_stats[$row['rbi_grade']] = $row['count'];
}

// ===================================
// 일별 판매 추이 (최근 7일)
// ===================================

$daily_stats = array();
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $sql = "SELECT 
            COUNT(*) as count,
            SUM(rb_price) as sales
            FROM {$g5['g5_prefix']}randombox_history
            WHERE DATE(rbh_created_at) = '{$date}'";
    $row = sql_fetch($sql);
    
    $daily_stats[] = array(
        'date' => $date,
        'count' => $row['count'] ? $row['count'] : 0,
        'sales' => $row['sales'] ? $row['sales'] : 0
    );
}

// ===================================
// 페이지 출력
// ===================================
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01">
        <span class="ov_txt">오늘 판매</span>
        <span class="ov_num"><?php echo number_format($today_stats['today_count']); ?>건</span>
    </span>
    <span class="btn_ov01">
        <span class="ov_txt">오늘 매출</span>
        <span class="ov_num"><?php echo number_format($today_stats['today_sales']); ?>P</span>
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
</div>

<form name="fstatistics" method="get" class="local_sch01 local_sch">
<label for="fr_date" class="sound_only">시작일</label>
<input type="text" name="fr_date" value="<?php echo $fr_date; ?>" id="fr_date" class="frm_input" size="11" maxlength="10">
~
<label for="to_date" class="sound_only">종료일</label>
<input type="text" name="to_date" value="<?php echo $to_date; ?>" id="to_date" class="frm_input" size="11" maxlength="10">
<input type="submit" value="검색" class="btn_submit">
</form>

<!-- 일별 판매 추이 차트 -->
<section class="stat_section">
    <h2 class="h2_frm">일별 판매 추이 (최근 7일)</h2>
    <div class="chart_container">
        <canvas id="dailyChart" height="80"></canvas>
    </div>
</section>

<!-- 등급별 배출 현황 -->
<section class="stat_section">
    <h2 class="h2_frm">등급별 배출 현황</h2>
    <div class="stat_grid">
        <div class="stat_card grade_normal">
            <h3>일반 (Normal)</h3>
            <div class="stat_value"><?php echo number_format($grade_stats['normal'] ?? 0); ?>개</div>
            <div class="stat_percent">
                <?php echo $total_stats['total_count'] > 0 ? number_format(($grade_stats['normal'] ?? 0) / $total_stats['total_count'] * 100, 2) : 0; ?>%
            </div>
        </div>
        <div class="stat_card grade_rare">
            <h3>레어 (Rare)</h3>
            <div class="stat_value"><?php echo number_format($grade_stats['rare'] ?? 0); ?>개</div>
            <div class="stat_percent">
                <?php echo $total_stats['total_count'] > 0 ? number_format(($grade_stats['rare'] ?? 0) / $total_stats['total_count'] * 100, 2) : 0; ?>%
            </div>
        </div>
        <div class="stat_card grade_epic">
            <h3>에픽 (Epic)</h3>
            <div class="stat_value"><?php echo number_format($grade_stats['epic'] ?? 0); ?>개</div>
            <div class="stat_percent">
                <?php echo $total_stats['total_count'] > 0 ? number_format(($grade_stats['epic'] ?? 0) / $total_stats['total_count'] * 100, 2) : 0; ?>%
            </div>
        </div>
        <div class="stat_card grade_legendary">
            <h3>레전더리 (Legendary)</h3>
            <div class="stat_value"><?php echo number_format($grade_stats['legendary'] ?? 0); ?>개</div>
            <div class="stat_percent">
                <?php echo $total_stats['total_count'] > 0 ? number_format(($grade_stats['legendary'] ?? 0) / $total_stats['total_count'] * 100, 2) : 0; ?>%
            </div>
        </div>
    </div>
</section>

<!-- 박스별 판매 현황 -->
<section class="stat_section">
    <h2 class="h2_frm">박스별 판매 현황</h2>
    
    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption>박스별 판매 현황</caption>
        <thead>
        <tr>
            <th scope="col">박스명</th>
            <th scope="col" width="80">타입</th>
            <th scope="col" width="80">상태</th>
            <th scope="col" width="100">판매수량</th>
            <th scope="col" width="120">매출</th>
            <th scope="col" width="100">비율</th>
            <th scope="col" width="100">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 0;
        while ($row = sql_fetch_array($box_result)) {
            $bg = ($i % 2) ? 'bg1' : 'bg0';
            $sales_ratio = $total_stats['total_sales'] > 0 ? ($row['sales'] / $total_stats['total_sales'] * 100) : 0;
        ?>
        <tr class="<?php echo $bg; ?>">
            <td class="td_left"><?php echo $row['rb_name']; ?></td>
            <td class="td_type">
                <span class="box_type_<?php echo $row['rb_type']; ?>"><?php echo get_box_type_name($row['rb_type']); ?></span>
            </td>
            <td class="td_status">
                <?php if ($row['rb_status']) : ?>
                    <span class="txt_active">활성</span>
                <?php else : ?>
                    <span class="txt_inactive">비활성</span>
                <?php endif; ?>
            </td>
            <td class="td_num"><?php echo number_format($row['count']); ?></td>
            <td class="td_num"><?php echo number_format($row['sales']); ?>P</td>
            <td class="td_num"><?php echo number_format($sales_ratio, 1); ?>%</td>
            <td class="td_mng">
                <a href="./box_stats.php?rb_id=<?php echo $row['rb_id']; ?>" class="btn btn_02">상세통계</a>
            </td>
        </tr>
        <?php
            $i++;
        }
        
        if ($i == 0) {
            echo '<tr><td colspan="7" class="empty_table">해당 기간에 판매 내역이 없습니다.</td></tr>';
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
        labels: [<?php echo "'" . implode("','", array_column($daily_stats, 'date')) . "'"; ?>],
        datasets: [{
            label: '판매수량',
            data: [<?php echo implode(',', array_column($daily_stats, 'count')); ?>],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            yAxisID: 'y-count'
        }, {
            label: '매출(포인트)',
            data: [<?php echo implode(',', array_column($daily_stats, 'sales')); ?>],
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
/* 통계 페이지 스타일 */
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

/* 박스 타입 스타일 */
.box_type_normal { 
    display: inline-block; 
    padding: 2px 8px; 
    background: #95a5a6; 
    color: #fff; 
    border-radius: 3px; 
    font-size: 0.9em; 
}
.box_type_event { 
    display: inline-block; 
    padding: 2px 8px; 
    background: #e74c3c; 
    color: #fff; 
    border-radius: 3px; 
    font-size: 0.9em; 
}
.box_type_premium { 
    display: inline-block; 
    padding: 2px 8px; 
    background: #f39c12; 
    color: #fff; 
    border-radius: 3px; 
    font-size: 0.9em; 
}

.txt_active { color: #27ae60; font-weight: bold; }
.txt_inactive { color: #e74c3c; }

.td_type { text-align: center; }
.td_status { text-align: center; }
.td_mng { text-align: center; }
.td_mng .btn { padding: 3px 8px; font-size: 0.9em; }
</style>

<?php
include_once('./admin.tail.php');
?>