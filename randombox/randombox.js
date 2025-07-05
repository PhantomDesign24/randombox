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
    $('.btn-purchase').on('click', function() {
        var boxId = $(this).data('box-id');
        showPurchaseModal(boxId);
    });
    
    // 모달 닫기
    $('.modal-close').on('click', function() {
        closeModal();
    });
    
    // 모달 배경 클릭
    $('.rb-modal').on('click', function(e) {
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
    var $boxCard = $('.rb-box-card[data-box-id="' + boxId + '"]');
    var boxName = $boxCard.find('.box-name').text();
    var boxPrice = parseInt($boxCard.find('.box-price').text().replace(/[^0-9]/g, ''));
    var boxImage = $boxCard.find('.box-image img').attr('src');
    
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
    
    // 구매 후 포인트 계산
    var currentPoint = parseInt($('.rb-user-info .value').text().replace(/[^0-9]/g, ''));
    var afterPoint = currentPoint - boxPrice;
    
    $('#modalAfterPoint').text(number_format(afterPoint) + 'P');
    
    // 포인트 부족 시 버튼 비활성화
    if (afterPoint < 0) {
        $('#modalAfterPoint').css('color', '#e74c3c');
        $('#confirmPurchase').prop('disabled', true).text('포인트 부족');
    } else {
        $('#modalAfterPoint').css('color', '#27ae60');
        $('#confirmPurchase').prop('disabled', false).text('구매하기');
    }
    
    // 모달 표시
    $('#purchaseModal').addClass('active');
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
                $('#purchaseModal').removeClass('active');
                
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
    $('.box-opening').show();
    $('.result-item').hide();
    $('.modal-footer').hide();
    
    // 모달 표시
    $('#resultModal').addClass('active');
    
    // 박스 오픈 애니메이션 (3초)
    setTimeout(function() {
        $('.box-opening').fadeOut(500, function() {
            // 아이템 정보 설정
            $('#resultItemImage').attr('src', item.image || './img/item-default.png');
            $('#resultItemName').text(item.rbi_name);
            $('#resultItemGrade').text(getGradeName(item.rbi_grade))
                                  .attr('class', 'item-grade grade-' + item.rbi_grade);
            
            if (item.rbi_value > 0) {
                $('#resultItemValue').html('<i class="bi bi-coin"></i> ' + number_format(item.rbi_value) + 'P 획득!').show();
            } else {
                $('#resultItemValue').hide();
            }
            
            // 등급별 이펙트
            addGradeEffect(item.rbi_grade);
            
            // 아이템 표시
            $('.result-item').fadeIn(500);
            $('.modal-footer').fadeIn(500);
            
            // 등급별 효과음 (있다면)
            playGradeSound(item.rbi_grade);
        });
    }, 3000);
}

/**
 * 등급별 이펙트 추가
 */
function addGradeEffect(grade) {
    var $effect = $('.item-grade-effect');
    $effect.removeClass('effect-normal effect-rare effect-epic effect-legendary');
    
    switch(grade) {
        case 'legendary':
            $effect.addClass('effect-legendary');
            // 화려한 효과 추가
            createParticles('gold');
            break;
        case 'epic':
            $effect.addClass('effect-epic');
            createParticles('purple');
            break;
        case 'rare':
            $effect.addClass('effect-rare');
            createParticles('blue');
            break;
        default:
            $effect.addClass('effect-normal');
    }
}

/**
 * 파티클 효과 생성
 */
function createParticles(color) {
    var colors = {
        gold: ['#FFD700', '#FFA500', '#FF8C00'],
        purple: ['#9b59b6', '#8e44ad', '#663399'],
        blue: ['#3498db', '#2980b9', '#1abc9c']
    };
    
    var particleColors = colors[color] || colors.blue;
    
    for (var i = 0; i < 30; i++) {
        setTimeout(function() {
            var $particle = $('<div class="particle"></div>');
            $particle.css({
                position: 'absolute',
                width: Math.random() * 10 + 5 + 'px',
                height: Math.random() * 10 + 5 + 'px',
                background: particleColors[Math.floor(Math.random() * particleColors.length)],
                borderRadius: '50%',
                left: '50%',
                top: '50%',
                transform: 'translate(-50%, -50%)',
                pointerEvents: 'none'
            });
            
            $('.result-item').append($particle);
            
            // 애니메이션
            var angle = Math.random() * Math.PI * 2;
            var distance = Math.random() * 200 + 100;
            var duration = Math.random() * 2000 + 1000;
            
            $particle.animate({
                left: 50 + Math.cos(angle) * distance + '%',
                top: 50 + Math.sin(angle) * distance + '%',
                opacity: 0
            }, duration, function() {
                $(this).remove();
            });
        }, i * 50);
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
    $('.rb-modal').removeClass('active');
    
    // 결과 모달 초기화
    $('.particle').remove();
    $('.item-grade-effect').removeClass('effect-normal effect-rare effect-epic effect-legendary');
}

/**
 * 사용자 포인트 업데이트
 */
function updateUserPoint() {
    $.get('./ajax/get_user_point.php', function(data) {
        if (data.status) {
            $('.rb-user-info .value').text(number_format(data.point) + 'P');
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

@keyframes pulse-gold {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}

@keyframes pulse-purple {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
}

@keyframes pulse-blue {
    0%, 100% { opacity: 0.5; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.02); }
}
</style>
`;

$('head').append(effectStyles);