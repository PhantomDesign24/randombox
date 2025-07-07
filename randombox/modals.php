<?php
/*
 * 파일명: modals.php
 * 위치: /randombox/
 * 기능: 랜덤박스 모달 모음
 * 작성일: 2025-01-04
 * 수정일: 2025-01-04
 */
?>

<!-- ===================================
 * 구매 확인 모달
 * =================================== -->
<div id="purchaseModal" class="rb-modal">
    <div class="rb-modal-dialog">
        <div class="rb-modal-content">
            <div class="rb-modal-header">
                <h4 class="rb-modal-title">구매 확인</h4>
                <button type="button" class="rb-modal-close" data-dismiss="modal">&times;</button>
            </div>
            
            <div class="rb-modal-body">
                <div class="rb-purchase-info">
                    <img id="modalBoxImage" src="" alt="" class="rb-purchase-img">
                    
                    <div class="rb-purchase-details">
                        <h5 id="modalBoxName"></h5>
                        
                        <table class="rb-purchase-table">
                            <tr>
                                <td>구매 가격</td>
                                <td class="text-right"><strong id="modalBoxPrice"></strong></td>
                            </tr>
                            <tr>
                                <td>보유 포인트</td>
                                <td class="text-right" id="modalCurrentPoint"></td>
                            </tr>
                            <tr class="rb-purchase-total">
                                <td>구매 후 잔액</td>
                                <td class="text-right"><strong id="modalAfterPoint"></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="rb-purchase-msg">
                    <i class="bi bi-info-circle"></i>
                    구매하시겠습니까? 구매 후 취소할 수 없습니다.
                </p>
            </div>
            
            <div class="rb-modal-footer">
                <button type="button" class="rb-btn rb-btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="rb-btn rb-btn-primary" id="confirmPurchase">
                    <span class="btn-text">구매하기</span>
                    <span class="btn-loading" style="display:none;">
                        <i class="bi bi-arrow-repeat spin"></i> 처리중...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
 * 결과 모달
 * =================================== -->
<div id="resultModal" class="rb-modal">
    <div class="rb-modal-dialog rb-modal-result">
        <div class="rb-modal-content">
            <!-- 오픈 애니메이션 -->
            <div class="rb-opening" id="openingAnimation">
                <div class="rb-modern-box">
                    <div class="rb-box-frame">
                        <div class="rb-box-inner">
                            <div class="rb-box-face rb-box-front"></div>
                            <div class="rb-box-face rb-box-back"></div>
                            <div class="rb-box-face rb-box-left"></div>
                            <div class="rb-box-face rb-box-right"></div>
                            <div class="rb-box-face rb-box-top"></div>
                            <div class="rb-box-face rb-box-bottom"></div>
                        </div>
                    </div>
                    <div class="rb-scan-line"></div>
                    <div class="rb-grid-overlay"></div>
                </div>
                <p class="rb-opening-text">OPENING<span class="rb-dots">...</span></p>
            </div>
            
            <!-- 결과 표시 -->
            <div class="rb-result" id="resultContent" style="display:none;">
                <div class="rb-result-header">
                    <div class="rb-hex-pattern"></div>
                    <h4 class="rb-modal-title">COMPLETE</h4>
                </div>
                
                <div class="rb-result-body">
                    <div class="rb-result-item">
                        <div class="rb-geometric-bg">
                            <div class="rb-geo-line"></div>
                            <div class="rb-geo-line"></div>
                            <div class="rb-geo-line"></div>
                            <div class="rb-geo-line"></div>
                        </div>
                        
                        <div class="rb-item-showcase">
                            <div class="rb-item-frame">
                                <img id="resultItemImage" src="" alt="" class="rb-result-img">
                                <div class="rb-corner rb-corner-tl"></div>
                                <div class="rb-corner rb-corner-tr"></div>
                                <div class="rb-corner rb-corner-bl"></div>
                                <div class="rb-corner rb-corner-br"></div>
                            </div>
                        </div>
                        
                        <div class="rb-item-info">
                            <div class="rb-info-line"></div>
                            <h3 id="resultItemName" class="rb-result-name"></h3>
                            <div id="resultItemGrade" class="rb-result-grade"></div>
                            <div id="resultItemValue" class="rb-result-value"></div>
                            <div class="rb-info-line"></div>
                        </div>
                    </div>
                </div>
                
                <div class="rb-modal-footer">
                    <button type="button" class="rb-btn-modern" data-dismiss="modal">
                        <span class="rb-btn-text">CONFIRM</span>
                        <span class="rb-btn-border"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===================================
 * 모달 기본
 * =================================== */

/* 모달 백드롭 */
.rb-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    z-index: 1050;
    overflow: auto;
    align-items: center;
    justify-content: center;
}

.rb-modal.show {
    display: flex !important;
    animation: fadeIn 0.2s;
}

/* 모달 다이얼로그 */
.rb-modal-dialog {
    width: 90%;
    max-width: 500px;
    margin: 20px;
}

.rb-modal-dialog.rb-modal-result {
    max-width: 600px;
}

/* 모달 콘텐츠 */
.rb-modal-content {
    background: #fff;
    border-radius: 0;
    box-shadow: 0 0 0 1px #000, 0 20px 40px rgba(0, 0, 0, 0.8);
    animation: slideIn 0.3s;
    overflow: hidden;
}

/* ===================================
 * 블랙&화이트 모던 오픈 애니메이션
 * =================================== */

/* 오픈 애니메이션 컨테이너 */
.rb-opening {
    padding: 100px 20px;
    text-align: center;
    background: #000;
    position: relative;
    overflow: hidden;
}

/* 모던 박스 */
.rb-modern-box {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto;
    perspective: 1000px;
}

/* 3D 박스 프레임 */
.rb-box-frame {
    width: 100%;
    height: 100%;
    position: relative;
    transform-style: preserve-3d;
    animation: boxRotate 3s linear infinite;
}

@keyframes boxRotate {
    0% { transform: rotateX(0deg) rotateY(0deg); }
    100% { transform: rotateX(360deg) rotateY(360deg); }
}

.rb-box-inner {
    width: 100%;
    height: 100%;
    position: relative;
    transform-style: preserve-3d;
}

/* 박스 면 */
.rb-box-face {
    position: absolute;
    width: 100px;
    height: 100px;
    border: 2px solid #fff;
    background: rgba(0, 0, 0, 0.9);
}

.rb-box-front { transform: translateZ(50px); }
.rb-box-back { transform: translateZ(-50px) rotateY(180deg); }
.rb-box-left { transform: translateX(-50px) rotateY(-90deg); }
.rb-box-right { transform: translateX(50px) rotateY(90deg); }
.rb-box-top { transform: translateY(-50px) rotateX(90deg); }
.rb-box-bottom { transform: translateY(50px) rotateX(-90deg); }

/* 스캔 라인 효과 */
.rb-scan-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: #fff;
    animation: scanLine 2s linear infinite;
}

@keyframes scanLine {
    0% { top: 0; opacity: 0; }
    50% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}

/* 그리드 오버레이 */
.rb-grid-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
}

/* 오프닝 텍스트 */
.rb-opening-text {
    margin-top: 40px;
    font-size: 14px;
    color: #fff;
    font-weight: 300;
    letter-spacing: 4px;
    text-transform: uppercase;
    font-family: 'Courier New', monospace;
}

.rb-dots {
    display: inline-block;
    animation: dotsBlink 1.5s infinite;
}

@keyframes dotsBlink {
    0%, 20% { opacity: 0; }
    40% { opacity: 1; }
    60% { opacity: 0; }
    80%, 100% { opacity: 1; }
}

/* ===================================
 * 블랙&화이트 결과 표시
 * =================================== */

/* 결과 헤더 */
.rb-result-header {
    padding: 40px 20px;
    text-align: center;
    background: #000;
    position: relative;
    overflow: hidden;
}

/* 육각형 패턴 배경 */
.rb-hex-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.1;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M30 0l25.98 15v30L30 60 4.02 45V15z' fill='none' stroke='%23fff' stroke-width='1'/%3E%3C/svg%3E");
    background-size: 60px 60px;
}

.rb-result-header .rb-modal-title {
    font-size: 20px;
    font-weight: 300;
    color: #fff;
    margin: 0;
    letter-spacing: 6px;
    font-family: 'Courier New', monospace;
    animation: typeWriter 0.5s steps(8) forwards;
}

@keyframes typeWriter {
    from { width: 0; }
    to { width: 100%; }
}

/* 결과 바디 */
.rb-result-body {
    padding: 60px 20px;
    background: #fff;
    position: relative;
}

/* 기하학적 배경 */
.rb-geometric-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    opacity: 0.05;
}

.rb-geo-line {
    position: absolute;
    background: #000;
    transform-origin: center;
}

.rb-geo-line:nth-child(1) {
    width: 200%;
    height: 1px;
    top: 20%;
    left: -50%;
    transform: rotate(45deg);
}

.rb-geo-line:nth-child(2) {
    width: 200%;
    height: 1px;
    top: 40%;
    left: -50%;
    transform: rotate(-45deg);
}

.rb-geo-line:nth-child(3) {
    width: 1px;
    height: 200%;
    left: 30%;
    top: -50%;
}

.rb-geo-line:nth-child(4) {
    width: 1px;
    height: 200%;
    right: 30%;
    top: -50%;
}

/* 아이템 쇼케이스 */
.rb-item-showcase {
    position: relative;
    margin-bottom: 40px;
    animation: itemReveal 0.6s ease-out;
}

@keyframes itemReveal {
    0% {
        transform: scale(0.8);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* 아이템 프레임 */
.rb-item-frame {
    position: relative;
    width: 200px;
    height: 200px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f8f8;
    border: 1px solid #000;
}

/* 모서리 장식 */
.rb-corner {
    position: absolute;
    width: 20px;
    height: 20px;
    border: 2px solid #000;
}

.rb-corner-tl {
    top: -1px;
    left: -1px;
    border-right: none;
    border-bottom: none;
}

.rb-corner-tr {
    top: -1px;
    right: -1px;
    border-left: none;
    border-bottom: none;
}

.rb-corner-bl {
    bottom: -1px;
    left: -1px;
    border-right: none;
    border-top: none;
}

.rb-corner-br {
    bottom: -1px;
    right: -1px;
    border-left: none;
    border-top: none;
}

/* 결과 이미지 */
.rb-result-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

/* 아이템 정보 */
.rb-item-info {
    text-align: center;
    position: relative;
}

.rb-info-line {
    width: 60px;
    height: 1px;
    background: #000;
    margin: 20px auto;
    position: relative;
}

.rb-info-line::before,
.rb-info-line::after {
    content: '';
    position: absolute;
    width: 6px;
    height: 6px;
    background: #000;
    top: 50%;
    transform: translateY(-50%);
}

.rb-info-line::before { left: -10px; }
.rb-info-line::after { right: -10px; }

/* 아이템 이름 */
.rb-result-name {
    font-size: 24px;
    font-weight: 900;
    color: #000;
    margin: 0 0 16px;
    letter-spacing: -1px;
    text-transform: uppercase;
}

/* 등급 표시 */
.rb-result-grade {
    display: inline-block;
    padding: 8px 24px;
    font-size: 12px;
    font-weight: 300;
    margin-bottom: 16px;
    letter-spacing: 2px;
    text-transform: uppercase;
    border: 1px solid #000;
    position: relative;
    background: #fff;
}

.rb-result-grade.grade-normal {
    background: #fff;
    color: #000;
}

.rb-result-grade.grade-rare {
    background: #000;
    color: #fff;
}

.rb-result-grade.grade-epic {
    background: #000;
    color: #fff;
    box-shadow: inset 0 0 0 2px #fff;
}

.rb-result-grade.grade-legendary {
    background: #000;
    color: #fff;
    animation: legendaryBlink 2s ease-in-out infinite;
}

@keyframes legendaryBlink {
    0%, 100% { 
        background: #000;
        color: #fff;
    }
    50% { 
        background: #fff;
        color: #000;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }
}

/* 포인트 획득 표시 */
.rb-result-value {
    font-size: 16px;
    font-weight: 700;
    color: #000;
    margin-top: 12px;
}

/* 모던 확인 버튼 */
.rb-btn-modern {
    position: relative;
    padding: 16px 48px;
    background: #000;
    color: #fff;
    border: none;
    font-size: 12px;
    font-weight: 300;
    letter-spacing: 3px;
    text-transform: uppercase;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.3s;
    margin: 0 auto;
    display: block;
}

.rb-btn-modern:hover {
    background: #fff;
    color: #000;
}

.rb-btn-border {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 1px solid #000;
    pointer-events: none;
}

.rb-btn-modern:hover .rb-btn-border {
    animation: borderRotate 0.6s linear;
}

@keyframes borderRotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(90deg); }
}

/* ===================================
 * 구매 확인 모달 스타일 수정
 * =================================== */

/* 모달 헤더 */
.rb-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e0e0e0;
    background: #fff;
}

.rb-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #000;
    margin: 0;
    letter-spacing: -0.5px;
}

.rb-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    line-height: 1;
}

.rb-modal-close:hover {
    color: #000;
    background: #f0f0f0;
    border-radius: 50%;
}

/* 모달 바디 */
.rb-modal-body {
    padding: 30px;
    background: #fff;
}

/* 구매 정보 */
.rb-purchase-info {
    display: flex;
    gap: 24px;
    margin-bottom: 24px;
}

.rb-purchase-img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    background: #f8f8f8;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    flex-shrink: 0;
}

.rb-purchase-details {
    flex: 1;
}

.rb-purchase-details h5 {
    font-size: 20px;
    font-weight: 700;
    color: #000;
    margin: 0 0 16px;
    letter-spacing: -0.5px;
}

/* 구매 테이블 */
.rb-purchase-table {
    width: 100%;
    font-size: 14px;
    border-collapse: collapse;
}

.rb-purchase-table td {
    padding: 8px 0;
    vertical-align: middle;
}

.rb-purchase-table .text-right {
    text-align: right;
}

.rb-purchase-table strong {
    font-weight: 700;
    color: #000;
}

.rb-purchase-total {
    border-top: 1px solid #e0e0e0;
}

.rb-purchase-total td {
    padding-top: 12px;
    font-weight: 600;
}

/* 구매 메시지 */
.rb-purchase-msg {
    margin: 0;
    padding: 12px 16px;
    background: #f8f8f8;
    border-radius: 6px;
    font-size: 13px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rb-purchase-msg i {
    font-size: 16px;
    color: #999;
}

/* 모달 푸터 */
.rb-modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #fff;
}

/* 버튼 스타일 */
.rb-btn {
    padding: 10px 24px;
    border: 1px solid #000;
    border-radius: 0;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 100px;
}

.rb-btn-primary {
    background: #000;
    color: #fff;
}

.rb-btn-primary:hover:not(:disabled) {
    background: #fff;
    color: #000;
}

.rb-btn-primary:disabled {
    background: #ccc;
    border-color: #ccc;
    color: #999;
    cursor: not-allowed;
}

.rb-btn-secondary {
    background: #fff;
    color: #000;
}

.rb-btn-secondary:hover {
    background: #f0f0f0;
}

/* 로딩 스피너 */
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.btn-loading {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* 포인트 부족 시 스타일 */
#modalAfterPoint.insufficient {
    color: #e74c3c;
    font-weight: 700;
}

/* 반응형 */
@media (max-width: 768px) {
    .rb-purchase-info {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .rb-purchase-details h5 {
        margin-top: 16px;
    }
    
    .rb-purchase-table {
        text-align: left;
    }
    
    .rb-modal-footer {
        flex-direction: column;
    }
    
    .rb-btn {
        width: 100%;
    }
}
</style>