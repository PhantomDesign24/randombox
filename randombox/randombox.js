/*
 * 파일명: randombox.js
 * 위치: /randombox/
 * 기능: 랜덤박스 사용자 페이지 자바스크립트
 * 작성일: 2025-01-04
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
    // 구매 버튼 클릭
    $('.rb-purchase-trigger').on('click', function() {
        var boxId = $(this).data('box-id');
        showPurchaseModal(boxId);
    });
    
    // 모달 닫기 (모든 닫기 버튼)
    $('.rb-close-modal').on('click', function() {
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
function showPurchaseModal(boxId) {
    currentBoxId = boxId;
    
    // 박스 정보 가져오기
    var $boxCard = $('.rb-box-item[data-box-id="' + boxId + '"]');
    var boxName = $boxCard.find('.rb-box-title').text();
    var boxPrice = parseInt($boxCard.find('.rb-price-tag').text().replace(/[^0-9]/g, ''));
    var boxImage = $boxCard.find('.rb-box-visual img').attr('src');
    
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
    var currentPoint = parseInt($('.rb-point-amount').text().replace(/[^0-9]/g, ''));
    var afterPoint = currentPoint - boxPrice;
    
    // 현재 포인트와 구매 후 포인트 표시
    $('#modalCurrentPoint').text(number_format(currentPoint) + 'P');
    $('#modalAfterPoint').text(number_format(afterPoint) + 'P');
    
    // 포인트 부족 시 처리
    if (afterPoint < 0) {
        $('#modalAfterPoint').parent().addClass('rb-insufficient');
        $('#confirmPurchase').prop('disabled', true).text('포인트 부족');
        
        // 부족 메시지 추가
        if (!$('.rb-error-msg').length) {
            $('.rb-price-table').after('<p class="rb-error-msg">포인트가 부족합니다. 충전 후 이용해 주세요.</p>');
        }
    } else {
        $('#modalAfterPoint').parent().removeClass('rb-insufficient');
        $('#confirmPurchase').prop('disabled', false).text('구매하기');
        $('.rb-error-msg').remove();
    }
    
    // 모달 표시
    $('#purchaseModal').addClass('rb-active');
}

// ===================================
// 구매 처리
// ===================================

/**
 * 구매 처리
 */
function processPurchase() {
    if (!currentBoxId) return;
    
    // 버튼 비활성화
    $('#confirmPurchase').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> 처리중...');
    
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
                $('#purchaseModal').removeClass('rb-active');
                
                // 결과 표시
                showResultModal(response.item);
                
                // 포인트 업데이트
                updateUserPoint();
                
            } else {
                alert(response.msg || '구매 처리 중 오류가 발생했습니다.');
                $('#confirmPurchase').prop('disabled', false).text('구매하기');
            }
        },
        error: function() {
            alert('통신 오류가 발생했습니다. 다시 시도해 주세요.');
            $('#confirmPurchase').prop('disabled', false).text('구매하기');
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
    $('.rb-box-opening').show();
    $('.rb-result-display').hide();
    $('.rb-modal-foot').hide();
    
    // 결과 모달 헤더 텍스트 변경
    $('#resultModal .rb-modal-title').text('박스 오픈 중...');
    
    // 모달 표시
    $('#resultModal').addClass('rb-active');
    
    // 박스 오픈 애니메이션 (3초)
    setTimeout(function() {
        $('.rb-box-opening').fadeOut(500, function() {
            // 헤더 텍스트 변경
            $('#resultModal .rb-modal-title').text('축하합니다!');
            
            // 아이템 정보 설정
            $('#resultItemImage').attr('src', item.image || './img/item-default.png');
            $('#resultItemName').text(item.rbi_name);
            $('#resultItemGrade').text(getGradeName(item.rbi_grade))
                                  .attr('class', 'rb-result-grade rb-grade-' + item.rbi_grade);
            
            if (item.rbi_value > 0) {
                $('#resultItemValue').text(number_format(item.rbi_value) + 'P 획득!').show();
            } else {
                $('#resultItemValue').hide();
            }
            
            // 등급별 이펙트
            addGradeEffect(item.rbi_grade);
            
            // 아이템 표시
            $('.rb-result-display').fadeIn(500);
            $('.rb-modal-foot').fadeIn(500);
            
            // 등급별 효과음 (있다면)
            playGradeSound(item.rbi_grade);
        });
    }, 3000);
}

/**
 * 등급별 이펙트 추가
 */
function addGradeEffect(grade) {
    var $effect = $('.rb-grade-effect');
    $effect.removeClass('rb-effect-normal rb-effect-rare rb-effect-epic rb-effect-legendary');
    
    switch(grade) {
        case 'legendary':
            $effect.addClass('rb-effect-legendary');
            createParticles('gold');
            break;
        case 'epic':
            $effect.addClass('rb-effect-epic');
            createParticles('purple');
            break;
        case 'rare':
            $effect.addClass('rb-effect-rare');
            createParticles('blue');
            break;
        default:
            $effect.addClass('rb-effect-normal');
            createParticles('gray');
    }
}

/**
 * 파티클 효과 생성
 */
function createParticles(color) {
    var colors = {
        gold: ['#FFD700', '#FFA500', '#FF8C00'],
        purple: ['#9b59b6', '#8e44ad', '#663399'],
        blue: ['#3498db', '#2980b9', '#1abc9c'],
        gray: ['#95a5a6', '#7f8c8d', '#bdc3c7']
    };
    
    var particleColors = colors[color] || colors.gray;
    
    for (var i = 0; i < 20; i++) {
        setTimeout(function() {
            var $particle = $('<div class="rb-particle"></div>');
            $particle.css({
                width: Math.random() * 8 + 4 + 'px',
                height: Math.random() * 8 + 4 + 'px',
                background: particleColors[Math.floor(Math.random() * particleColors.length)],
                left: '50%',
                top: '50%',
                transform: 'translate(-50%, -50%)'
            });
            
            $('.rb-result-display').append($particle);
            
            // 애니메이션
            var angle = Math.random() * Math.PI * 2;
            var distance = Math.random() * 150 + 80;
            var duration = Math.random() * 1500 + 1000;
            
            $particle.animate({
                left: 50 + Math.cos(angle) * distance + '%',
                top: 50 + Math.sin(angle) * distance + '%',
                opacity: 0
            }, duration, function() {
                $(this).remove();
            });
        }, i * 30);
    }
}

/**
 * 등급별 효과음 재생
 */
function playGradeSound(grade) {
    // 효과음 파일이 있다면 재생
    var audio = new Audio();
    switch(grade) {
        case 'legendary':
            audio.src = './sound/legendary.mp3';
            break;
        case 'epic':
            audio.src = './sound/epic.mp3';
            break;
        case 'rare':
            audio.src = './sound/rare.mp3';
            break;
        default:
            audio.src = './sound/normal.mp3';
    }
    
    audio.volume = 0.5;
    audio.play().catch(function(e) {
        // 자동 재생 정책으로 인한 오류 무시
    });
}

// ===================================
// 유틸리티 함수
// ===================================

/**
 * 모달 닫기
 */
function closeModal() {
    $('.rb-modal-overlay').removeClass('rb-active');
    
    // 결과 모달 초기화
    $('.rb-particle').remove();
    $('.rb-grade-effect').removeClass('rb-effect-normal rb-effect-rare rb-effect-epic rb-effect-legendary');
}

/**
 * 사용자 포인트 업데이트
 */
function updateUserPoint() {
    $.get('./ajax/get_user_point.php', function(data) {
        if (data.status) {
            $('.rb-point-amount').text(number_format(data.point) + 'P');
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

// ===================================
// CSS 애니메이션 추가
// ===================================

// 등급별 배경 효과 CSS 동적 추가
var effectStyles = `
<style>
.effect-legendary {
    background: radial-gradient(circle at center, rgba(255,215,0,0.3) 0%, transparent 70%);
    animation: pulse-gold 2s infinite;
}

.effect-epic {
    background: radial-gradient(circle at center, rgba(155,89,182,0.3) 0%, transparent 70%);
    animation: pulse-purple 2s infinite;
}

.effect-rare {
    background: radial-gradient(circle at center, rgba(52,152,219,0.3) 0%, transparent 70%);
    animation: pulse-blue 2s infinite;
}

.effect-normal {
    background: radial-gradient(circle at center, rgba(149,165,166,0.2) 0%, transparent 70%);
}

@keyframes pulse-gold {
    0%, 100% { 
        opacity: 0.5; 
        transform: scale(1);
    }
    50% { 
        opacity: 0.8; 
        transform: scale(1.1);
    }
}

@keyframes pulse-purple {
    0%, 100% { 
        opacity: 0.5; 
        transform: scale(1);
    }
    50% { 
        opacity: 0.8; 
        transform: scale(1.05);
    }
}

@keyframes pulse-blue {
    0%, 100% { 
        opacity: 0.5; 
        transform: scale(1);
    }
    50% { 
        opacity: 0.8; 
        transform: scale(1.02);
    }
}
</style>
`;

$('head').append(effectStyles);