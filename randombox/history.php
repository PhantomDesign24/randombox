<?php
/*
 * 파일명: history.php
 * 위치: /randombox/
 * 기능: 랜덤박스 구매내역 페이지 - 개선된 디자인
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */

include_once('./_common.php');

$g5['title'] = '구매내역';
include_once(G5_PATH.'/head.php');
?>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<style>
/* ===================================
 * 기본 설정
 * =================================== */
 
.rbh-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* ===================================
 * 헤더
 * =================================== */

/* 페이지 헤더 */
.rbh-header {
    background: #fff;
    border: 1px solid #ddd;
    padding: 24px;
    margin-bottom: 20px;
}

.rbh-title {
    font-size: 24px;
    font-weight: 900;
    color: #000;
    margin: 0 0 8px;
}

.rbh-subtitle {
    font-size: 14px;
    color: #666;
}

/* ===================================
 * 통계 카드
 * =================================== */

/* 통계 그리드 */
.rbh-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

/* 통계 카드 */
.rbh-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    text-align: center;
}

.rbh-stat-icon {
    font-size: 24px;
    color: #666;
    margin-bottom: 8px;
}

.rbh-stat-value {
    font-size: 28px;
    font-weight: 900;
    color: #000;
    line-height: 1;
}

.rbh-stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

/* ===================================
 * 필터
 * =================================== */

/* 필터 섹션 */
.rbh-filter {
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 20px;
}

.rbh-filter-form {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.rbh-filter-group {
    flex: 1;
    min-width: 150px;
}

.rbh-filter-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
    font-weight: 500;
}

.rbh-filter-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    font-size: 14px;
}

.rbh-filter-input:focus {
    outline: none;
    border-color: #000;
}

.rbh-filter-actions {
    display: flex;
    gap: 8px;
}

/* ===================================
 * 히스토리 리스트
 * =================================== */

/* 리스트 컨테이너 */
.rbh-list {
    background: #fff;
    border: 1px solid #ddd;
}

/* 리스트 헤더 */
.rbh-list-header {
    padding: 16px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
}

.rbh-list-title {
    font-size: 14px;
    font-weight: 700;
    color: #000;
}

.rbh-list-count {
    font-size: 13px;
    color: #666;
}

/* 리스트 바디 */
.rbh-list-body {
    max-height: 600px;
    overflow-y: auto;
}

/* 히스토리 아이템 */
.rbh-item {
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: background 0.2s;
}

.rbh-item:hover {
    background: #fafafa;
}

.rbh-item:last-child {
    border-bottom: none;
}

/* 아이템 이미지 */
.rbh-item-img {
    width: 60px;
    height: 60px;
    background: #f8f8f8;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rbh-item-img img {
    max-width: 50px;
    max-height: 50px;
    object-fit: contain;
}

/* 아이템 정보 */
.rbh-item-info {
    flex: 1;
}

.rbh-item-date {
    font-size: 12px;
    color: #999;
    margin-bottom: 4px;
}

.rbh-item-box {
    font-size: 14px;
    font-weight: 600;
    color: #000;
    margin-bottom: 4px;
}

.rbh-item-result {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.rbh-item-grade {
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
}

.grade-normal {
    background: #f0f0f0;
    color: #666;
}

.grade-rare {
    background: #e3f2fd;
    color: #1976d2;
}

.grade-epic {
    background: #f3e5f5;
    color: #7b1fa2;
}

.grade-legendary {
    background: #fff3e0;
    color: #f57c00;
}

/* 아이템 값 */
.rbh-item-values {
    text-align: right;
    font-size: 14px;
}

.rbh-item-cost {
    color: #e74c3c;
    font-weight: 600;
}

.rbh-item-reward {
    color: #27ae60;
    font-size: 12px;
}

/* ===================================
 * 페이지네이션
 * =================================== */

/* 페이지네이션 */
.rbh-pagination {
    padding: 20px;
    text-align: center;
    border-top: 1px solid #ddd;
}

.rbh-page-btn {
    display: inline-block;
    padding: 6px 12px;
    margin: 0 2px;
    background: #fff;
    border: 1px solid #ddd;
    color: #666;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.2s;
}

.rbh-page-btn:hover {
    border-color: #000;
    color: #000;
}

.rbh-page-btn.active {
    background: #000;
    border-color: #000;
    color: #fff;
}

.rbh-page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ===================================
 * 빈 상태
 * =================================== */

.rbh-empty {
    padding: 80px 20px;
    text-align: center;
    color: #999;
}

.rbh-empty i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

/* ===================================
 * 로딩
 * =================================== */

.rbh-loading {
    padding: 60px;
    text-align: center;
}

.rbh-spinner {
    display: inline-block;
    width: 24px;
    height: 24px;
    border: 3px solid #f0f0f0;
    border-top-color: #000;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ===================================
 * 반응형
 * =================================== */

@media (max-width: 768px) {
    .rbh-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .rbh-filter-form {
        flex-direction: column;
    }
    
    .rbh-filter-group {
        width: 100%;
    }
    
    .rbh-item {
        flex-wrap: wrap;
    }
    
    .rbh-item-values {
        width: 100%;
        text-align: left;
        margin-top: 8px;
    }
}
</style>

<div class="rbh-wrapper">
    
    <!-- ===================================
     * 페이지 헤더
     * =================================== -->
    <div class="rbh-header">
        <h1 class="rbh-title">구매내역</h1>
        <p class="rbh-subtitle">랜덤박스 구매 기록을 확인하세요</p>
    </div>
    
    <!-- ===================================
     * 통계 카드
     * =================================== -->
    <div class="rbh-stats">
        <div class="rbh-stat-card">
            <div class="rbh-stat-icon">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="rbh-stat-value" id="totalCount">0</div>
            <div class="rbh-stat-label">총 구매</div>
        </div>
        
        <div class="rbh-stat-card">
            <div class="rbh-stat-icon">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="rbh-stat-value" id="totalSpent">0P</div>
            <div class="rbh-stat-label">사용 포인트</div>
        </div>
        
        <div class="rbh-stat-card">
            <div class="rbh-stat-icon">
                <i class="bi bi-gift"></i>
            </div>
            <div class="rbh-stat-value" id="totalEarned">0P</div>
            <div class="rbh-stat-label">획득 포인트</div>
        </div>
        
        <div class="rbh-stat-card">
            <div class="rbh-stat-icon">
                <i class="bi bi-gem"></i>
            </div>
            <div class="rbh-stat-value" id="rareCount">0</div>
            <div class="rbh-stat-label">희귀 아이템</div>
        </div>
    </div>
    
    <!-- ===================================
     * 필터
     * =================================== -->
    <div class="rbh-filter">
        <form id="filterForm" class="rbh-filter-form">
            <div class="rbh-filter-group">
                <label class="rbh-filter-label">시작일</label>
                <input type="date" id="startDate" class="rbh-filter-input" 
                       value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            </div>
            
            <div class="rbh-filter-group">
                <label class="rbh-filter-label">종료일</label>
                <input type="date" id="endDate" class="rbh-filter-input" 
                       value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="rbh-filter-group">
                <label class="rbh-filter-label">박스</label>
                <select id="filterBox" class="rbh-filter-input">
                    <option value="">전체</option>
                </select>
            </div>
            
            <div class="rbh-filter-group">
                <label class="rbh-filter-label">등급</label>
                <select id="filterGrade" class="rbh-filter-input">
                    <option value="">전체</option>
                    <option value="normal">일반</option>
                    <option value="rare">레어</option>
                    <option value="epic">에픽</option>
                    <option value="legendary">레전더리</option>
                </select>
            </div>
            
            <div class="rbh-filter-actions">
                <button type="submit" class="rb-btn rb-btn-primary">
                    <i class="bi bi-search"></i> 검색
                </button>
                <button type="button" class="rb-btn rb-btn-outline" onclick="resetFilter()">
                    초기화
                </button>
            </div>
        </form>
    </div>
    
    <!-- ===================================
     * 히스토리 리스트
     * =================================== -->
    <div class="rbh-list">
        <div class="rbh-list-header">
            <div class="rbh-list-title">구매 내역</div>
            <div class="rbh-list-count" id="listCount">총 0건</div>
        </div>
        
        <div class="rbh-list-body" id="historyList">
            <div class="rbh-loading">
                <div class="rbh-spinner"></div>
                <p style="margin-top:12px;">데이터를 불러오는 중...</p>
            </div>
        </div>
        
        <div class="rbh-pagination" id="pagination" style="display:none;">
            <!-- 페이지네이션 -->
        </div>
    </div>
    
    <!-- 하단 버튼 -->
    <div style="text-align:center;margin-top:20px;">
        <a href="./" class="rb-btn rb-btn-primary">
            <i class="bi bi-box-seam"></i> 랜덤박스 메인
        </a>
    </div>
    
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
/* ===================================
 * 전역 변수
 * =================================== */

let currentPage = 1;
let totalPages = 1;
let isLoading = false;

/* ===================================
 * 초기화
 * =================================== */

$(document).ready(function() {
    loadBoxList();
    loadHistory();
    
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadHistory();
    });
});

/* ===================================
 * 데이터 로드
 * =================================== */

/* 박스 목록 로드 */
function loadBoxList() {
    $.get('./ajax/get_box_list.php', function(data) {
        if (data.status && data.boxes) {
            const options = '<option value="">전체</option>' + 
                data.boxes.map(box => `<option value="${box.rb_id}">${box.rb_name}</option>`).join('');
            $('#filterBox').html(options);
        }
    });
}

/* 히스토리 로드 */
function loadHistory() {
    if (isLoading) return;
    isLoading = true;
    
    const params = {
        page: currentPage,
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        rb_id: $('#filterBox').val(),
        grade: $('#filterGrade').val()
    };
    
    $.ajax({
        url: './ajax/get_history.php',
        type: 'GET',
        data: params,
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                updateStats(response.stats);
                
                if (response.list && response.list.length > 0) {
                    displayHistory(response.list);
                    $('#listCount').text(`총 ${response.total_count}건`);
                } else {
                    displayEmptyState();
                }
                
                totalPages = response.total_pages;
                updatePagination();
            }
        },
        error: function() {
            alert('데이터를 불러오는데 실패했습니다.');
        },
        complete: function() {
            isLoading = false;
        }
    });
}

/* ===================================
 * 통계 업데이트
 * =================================== */

function updateStats(stats) {
    $('#totalCount').text(numberFormat(stats.total_count || 0));
    $('#totalSpent').text(numberFormat(stats.total_spent || 0) + 'P');
    $('#totalEarned').text(numberFormat(stats.total_earned || 0) + 'P');
    $('#rareCount').text(numberFormat(stats.rare_count || 0));
}

/* ===================================
 * 히스토리 표시
 * =================================== */

function displayHistory(list) {
    const html = list.map(item => {
        const itemImage = item.item_image || './img/item-default.png';
        
        return `
            <div class="rbh-item">
                <div class="rbh-item-img">
                    <img src="${itemImage}" alt="${item.rbi_name}">
                </div>
                
                <div class="rbh-item-info">
                    <div class="rbh-item-date">${item.purchase_date}</div>
                    <div class="rbh-item-box">${item.rb_name}</div>
                    <div class="rbh-item-result">
                        <span class="rbh-item-grade ${getGradeClass(item.rbi_grade)}">
                            ${getGradeName(item.rbi_grade)}
                        </span>
                        <span>${item.rbi_name}</span>
                    </div>
                </div>
                
                <div class="rbh-item-values">
                    <div class="rbh-item-cost">-${numberFormat(item.rb_price)}P</div>
                    ${item.rbi_value > 0 ? `<div class="rbh-item-reward">+${numberFormat(item.rbi_value)}P</div>` : ''}
                </div>
            </div>
        `;
    }).join('');
    
    $('#historyList').html(html);
}

function displayEmptyState() {
    const html = `
        <div class="rbh-empty">
            <i class="bi bi-inbox"></i>
            <p>구매 내역이 없습니다</p>
        </div>
    `;
    
    $('#historyList').html(html);
    $('#pagination').hide();
}

/* ===================================
 * 페이지네이션
 * =================================== */

function updatePagination() {
    if (totalPages <= 1) {
        $('#pagination').hide();
        return;
    }
    
    let html = '';
    
    // 이전 버튼
    html += `<a href="#" class="rbh-page-btn ${currentPage === 1 ? 'disabled' : ''}" onclick="goToPage(${currentPage - 1});return false;">
        <i class="bi bi-chevron-left"></i>
    </a>`;
    
    // 페이지 번호
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, startPage + 4);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<a href="#" class="rbh-page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i});return false;">${i}</a>`;
    }
    
    // 다음 버튼
    html += `<a href="#" class="rbh-page-btn ${currentPage === totalPages ? 'disabled' : ''}" onclick="goToPage(${currentPage + 1});return false;">
        <i class="bi bi-chevron-right"></i>
    </a>`;
    
    $('#pagination').html(html).show();
}

function goToPage(page) {
    if (page < 1 || page > totalPages || page === currentPage) return;
    
    currentPage = page;
    loadHistory();
    window.scrollTo(0, 0);
}

/* ===================================
 * 유틸리티
 * =================================== */

function resetFilter() {
    $('#startDate').val('<?php echo date('Y-m-d', strtotime('-30 days')); ?>');
    $('#endDate').val('<?php echo date('Y-m-d'); ?>');
    $('#filterBox').val('');
    $('#filterGrade').val('');
    
    currentPage = 1;
    loadHistory();
}

function numberFormat(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function getGradeClass(grade) {
    return 'grade-' + grade;
}

function getGradeName(grade) {
    const names = {
        'normal': '일반',
        'rare': '레어',
        'epic': '에픽',
        'legendary': '레전더리'
    };
    return names[grade] || '일반';
}
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>