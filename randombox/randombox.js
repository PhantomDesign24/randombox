/*
 * 파일명: randombox.js
 * 위치: /randombox/
 * 기능: 랜덤박스 사용자 페이지 자바스크립트
 * 작성일: 2025-01-04
 * 수정일: 2025-07-07
 */

// ===================================
// 전역 변수
// ===================================

var currentBoxId = null;
var currentBoxData = {};

// ===================================
// 초기화
// ===================================

$(document).ready(function() {
    // 구매 버튼 클릭 - 클래스명 수정
    $('.rb-buy-btn').on('click', function() {
        var boxId = $(this).data('box-id');
        var boxName = $(this).data('box-name');
        var boxPrice = $(this).data('box-price');
        var boxImage = $(this).data('box-image');
        
        showPurchaseModal(boxId, boxName, boxPrice, boxImage);
    });
    
    // 모달 닫기
    $(document).on('click', '.rb-modal-close-btn, [data-dismiss="modal"]', function() {
        closeModal();
    });
    
    // 모달 배경 클릭
    $('.rb-modal-overlay').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // 구매 확인
    $('#confirmPurchase').on('click', function() {
        processPurchase();
    });
    
    // ESC 키로 모달 닫기
    $(document).on('keyup', function(e) {
        if (e.key === "Escape") {
            closeModal();
        }
    });
});

// ===================================
// 구매 모달
// ===================================

/**
 * 구매 모달 표시
 */
function showPurchaseModal(boxId, boxName, boxPrice, boxImage) {
    currentBoxId = boxId;
    currentBoxData = {
        id: boxId,
        name: boxName,
        price: boxPrice,
        image: boxImage
    };
    
    // 모달에 정보 표시
    $('#modalBoxName').text(boxName);
    $('#modalBoxPrice').text(number_format(boxPrice) + 'P');
    $('#modalBoxImage').attr('src', boxImage).attr('alt', boxName);
    
    // 현재 포인트 가져오기
    var currentPoint = parseInt($('#userPoint').text().replace(/[^0-9]/g, ''));
    var afterPoint = currentPoint - boxPrice;
    
    // 현재 포인트와 구매 후 포인트 표시
    $('#modalCurrentPoint').text(number_format(currentPoint) + 'P');
    $('#modalAfterPoint').text(number_format(afterPoint) + 'P');
    
    // 포인트 부족 시 처리
    if (afterPoint < 0) {
        $('#modalAfterPoint').css('color', '#e74c3c');
        $('#confirmPurchase').prop('disabled', true);
        $('.btn-text').text('포인트 부족');
    } else {
        $('#modalAfterPoint').css('color', 'inherit');
        $('#confirmPurchase').prop('disabled', false);
        $('.btn-text').text('구매하기');
    }
    
    // 모달 표시
    $('#purchaseModal').addClass('show').css('display', 'flex');
}

// ===================================
// 구매 처리
// ===================================

/**
 * 구매 처리
 */
function processPurchase() {
    if (!currentBoxId) return;
    
    // 버튼 비활성화 및 로딩 표시
    $('#confirmPurchase').prop('disabled', true);
    $('.btn-text').hide();
    $('.btn-loading').show();
    
    // AJAX 요청
    $.ajax({
        url: './purchase.php',
        type: 'POST',
        data: {
            rb_id: currentBoxId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                // 구매 모달 닫기
                $('#purchaseModal').removeClass('show').hide();
                
                // 버튼 상태 초기화
                $('#confirmPurchase').prop('disabled', false);
                $('.btn-text').show().text('구매하기');
                $('.btn-loading').hide();
                
                // 결과 표시
                showResultModal(response.item);
                
                // 포인트 업데이트
                updateUserPoint();
                
            } else {
                alert(response.msg || '구매 처리 중 오류가 발생했습니다.');
                $('#confirmPurchase').prop('disabled', false);
                $('.btn-text').show().text('구매하기');
                $('.btn-loading').hide();
            }
        },
        error: function() {
            alert('통신 오류가 발생했습니다. 다시 시도해 주세요.');
            $('#confirmPurchase').prop('disabled', false);
            $('.btn-text').show().text('구매하기');
            $('.btn-loading').hide();
        }
    });
}

// ===================================
// 결과 모달
// ===================================

/**
 * 결과 모달 표시
 */
function showResultModal(item) {
    // 모달 초기화
    $('#openingAnimation').show();
    $('#resultContent').hide();
    
    // 모달 표시
    $('#resultModal').addClass('show').css('display', 'flex');
    
    // 박스 오픈 애니메이션 (3초)
    setTimeout(function() {
        $('#openingAnimation').fadeOut(500, function() {
            // 아이템 정보 설정
            $('#resultItemImage').attr('src', item.image || './img/item-default.png');
            $('#resultItemName').text(item.rbi_name);
            $('#resultItemGrade').text(getGradeName(item.rbi_grade))
                                  .attr('class', 'rb-result-grade ' + getGradeClass(item.rbi_grade));
            
            // 등급별 글로우 효과
            $('#itemGlow').attr('class', 'rb-item-glow ' + getGradeClass(item.rbi_grade));
            
            if (item.rbi_value > 0) {
                $('#resultItemValue').text('+' + number_format(item.rbi_value) + 'P').show();
            } else {
                $('#resultItemValue').hide();
            }
            
            // 등급별 색종이 효과
            if (item.rbi_grade === 'legendary' || item.rbi_grade === 'epic') {
                createConfetti(item.rbi_grade);
            }
            
            // 결과 표시
            $('#resultContent').show();
            
            // 사운드 효과 (옵션)
            playGradeSound(item.rbi_grade);
        });
    }, 3000);
}

/**
 * 색종이 효과 생성
 */
function createConfetti(grade) {
    const colors = {
        legendary: ['#FFD700', '#FFA500', '#FFED4E', '#FFF8DC'],
        epic: ['#9B59B6', '#8E44AD', '#BF55EC', '#DDA0DD']
    };
    
    const selectedColors = colors[grade] || colors.epic;
    const confettiCount = grade === 'legendary' ? 50 : 30;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = $('<div>').addClass('rb-confetti');
        const color = selectedColors[Math.floor(Math.random() * selectedColors.length)];
        const left = Math.random() * 100;
        const animationDelay = Math.random() * 2;
        const animationDuration = 2 + Math.random() * 2;
        
        confetti.css({
            'background-color': color,
            'left': left + '%',
            'animation-delay': animationDelay + 's',
            'animation-duration': animationDuration + 's'
        });
        
        $('#resultModal').append(confetti);
        
        // 애니메이션 종료 후 제거
        setTimeout(function() {
            confetti.remove();
        }, (animationDelay + animationDuration) * 1000);
    }
}

// ===================================
// 유틸리티
// ===================================

/**
 * 모달 닫기
 */
function closeModal() {
    $('.rb-modal').removeClass('show').hide();
    
    // 버튼 상태 초기화
    $('#confirmPurchase').prop('disabled', false);
    $('.btn-text').show().text('구매하기');
    $('.btn-loading').hide();
    
    // 현재 박스 정보 초기화
    currentBoxId = null;
    currentBoxData = {};
}

/**
 * 사용자 포인트 업데이트
 */
function updateUserPoint() {
    $.ajax({
        url: g5_url + '/bbs/ajax.mb_point.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.point !== undefined) {
                $('#userPoint').text(number_format(response.point) + 'P');
            }
        }
    });
}

/**
 * 숫자 포맷
 */
function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * 등급명 반환
 */
function getGradeName(grade) {
    const grades = {
        'normal': '노멀',
        'rare': '레어',
        'epic': '에픽',
        'legendary': '레전더리'
    };
    return grades[grade] || '노멀';
}

/**
 * 등급 클래스명 반환
 */
function getGradeClass(grade) {
    return 'rb-grade-' + grade;
}

/**
 * 등급별 사운드 재생 (옵션)
 */
function playGradeSound(grade) {
    // 사운드 재생 기능 (필요시 구현)
}

// ===================================
// 구매내역 페이지 (history.php용)
// ===================================

/* 구매내역 관련 변수 */
var historyPage = 1;
var historyLoading = false;
var historyHasMore = true;

/* 구매내역 필터 및 조회 */
function loadHistory(reset) {
    if (historyLoading) return;
    
    if (reset) {
        historyPage = 1;
        historyHasMore = true;
    }
    
    if (!historyHasMore) return;
    
    historyLoading = true;
    
    const filters = {
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val(),
        rb_id: $('#filterBox').val(),
        grade: $('#filterGrade').val(),
        page: historyPage
    };
    
    $.ajax({
        url: './ajax.history.php',
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.status) {
                // 통계 업데이트
                updateHistoryStats(response.stats);
                
                // 리스트 표시
                if (historyPage === 1) {
                    displayHistoryList(response.list);
                } else {
                    appendHistoryList(response.list);
                }
                
                // 더보기 버튼 표시 여부
                if (response.list.length < 20 || historyPage >= response.total_pages) {
                    historyHasMore = false;
                    $('#historyMore').hide();
                } else {
                    $('#historyMore').show();
                }
            }
        },
        error: function() {
            if (historyPage === 1) {
                $('#historyList').html('<div class="rb-history-empty"><i class="bi bi-exclamation-circle"></i><p>데이터를 불러올 수 없습니다.</p></div>');
            }
        },
        complete: function() {
            historyLoading = false;
        }
    });
}

/**
 * 통계 업데이트
 */
function updateHistoryStats(stats) {
    $('#historyTotalCount').text(number_format(stats.total_count || 0) + '회');
    $('#historyTotalSpent').text(number_format(stats.total_spent || 0) + 'P');
    $('#historyTotalEarned').text(number_format(stats.total_earned || 0) + 'P');
    $('#historyRareCount').text(number_format(stats.rare_count || 0) + '개');
}

/**
 * 내역 리스트 표시
 */
function displayHistoryList(list) {
    if (!list || list.length === 0) {
        $('#historyList').html('<div class="rb-history-empty"><i class="bi bi-inbox"></i><p>구매 내역이 없습니다.</p></div>');
        return;
    }
    
    const html = list.map(item => createHistoryItemHtml(item)).join('');
    $('#historyList').html(html);
}

/**
 * 내역 리스트 추가
 */
function appendHistoryList(list) {
    if (!list || list.length === 0) return;
    
    const html = list.map(item => createHistoryItemHtml(item)).join('');
    $('#historyList').append(html);
}

/**
 * 내역 아이템 HTML 생성
 */
function createHistoryItemHtml(item) {
    const itemImage = item.item_image || './img/item-default.png';
    const date = new Date(item.purchase_date);
    const dateStr = (date.getMonth() + 1).toString().padStart(2, '0') + '.' 
                  + date.getDate().toString().padStart(2, '0');
    const timeStr = date.getHours().toString().padStart(2, '0') + ':' 
                  + date.getMinutes().toString().padStart(2, '0');
    
    return `
        <div class="rb-history-item">
            <div class="rb-history-date">
                <div>${dateStr}</div>
                <div class="rb-history-time">${timeStr}</div>
            </div>
            <div class="rb-history-info">
                <div class="rb-history-box">${item.box_name}</div>
                <div class="rb-history-result">
                    <img src="${itemImage}" alt="${item.item_name}">
                    <span class="${getGradeClass(item.item_grade)}">${item.item_name}</span>
                </div>
            </div>
            <div class="rb-history-point">
                <div class="rb-history-spent">-${number_format(item.box_price)}P</div>
                ${item.item_value > 0 ? `<div class="rb-history-earned">+${number_format(item.item_value)}P</div>` : ''}
            </div>
        </div>
    `;
}