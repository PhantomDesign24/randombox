/*
 * 파일명: randombox.js
 * 위치: /randombox/
 * 기능: 랜덤박스 사용자 페이지 자바스크립트
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
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
        setTimeout(function() {
            const confetti = $('<div class="confetti"></div>');
            const color = selectedColors[Math.floor(Math.random() * selectedColors.length)];
            const left = Math.random() * 100;
            const animationDuration = Math.random() * 3 + 2;
            const animationDelay = Math.random() * 0.5;
            
            confetti.css({
                'background-color': color,
                'left': left + '%',
                'animation-duration': animationDuration + 's',
                'animation-delay': animationDelay + 's',
                'width': Math.random() * 10 + 5 + 'px',
                'height': Math.random() * 10 + 5 + 'px'
            });
            
            $('#confetti').append(confetti);
            
            // 애니메이션 종료 후 제거
            setTimeout(function() {
                confetti.remove();
            }, (animationDuration + animationDelay) * 1000);
        }, i * 50);
    }
}

/**
 * 등급별 사운드 재생 (옵션)
 */
function playGradeSound(grade) {
    // 사운드 파일이 있는 경우에만 실행
    const sounds = {
        normal: 'normal.mp3',
        rare: 'rare.mp3',
        epic: 'epic.mp3',
        legendary: 'legendary.mp3'
    };
    
    // 사운드 파일이 존재하는지 확인 후 재생
    // 실제 구현 시에는 사운드 파일을 추가해주세요
}

// ===================================
// 유틸리티 함수
// ===================================

/**
 * 모달 닫기
 */
function closeModal() {
    $('.rb-modal').removeClass('show').hide();
}

/**
 * 사용자 포인트 업데이트
 */
function updateUserPoint() {
    $.get('./ajax/get_user_point.php', function(data) {
        if (data.status) {
            $('#userPoint').text(number_format(data.point) + 'P');
        }
    });
}

/**
 * 숫자 포맷
 */
function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * 등급명 반환
 */
function getGradeName(grade) {
    var names = {
        'normal': '일반',
        'rare': '레어',
        'epic': '에픽',
        'legendary': '레전더리'
    };
    return names[grade] || '일반';
}

/**
 * 등급 클래스 반환
 */
function getGradeClass(grade) {
    return 'grade-' + grade;
}