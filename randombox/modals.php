<?php
/*
 * 파일명: modals.php
 * 위치: /randombox/
 * 기능: 랜덤박스 모달 모음
 * 작성일: 2025-01-04
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
    <div class="rb-modal-dialog">
        <div class="rb-modal-content">
            <!-- 오픈 애니메이션 -->
            <div class="rb-opening" id="openingAnimation">
                <div class="rb-opening-box">
                    <i class="bi bi-box-seam"></i>
                </div>
                <p class="rb-opening-text">박스를 여는 중...</p>
            </div>
            
            <!-- 결과 표시 -->
            <div class="rb-result" id="resultContent" style="display:none;">
                <div class="rb-result-header">
                    <h4 class="rb-modal-title">획득 아이템</h4>
                </div>
                
                <div class="rb-result-body">
                    <div class="rb-result-item">
                        <img id="resultItemImage" src="" alt="" class="rb-result-img">
                        <h3 id="resultItemName" class="rb-result-name"></h3>
                        <div id="resultItemGrade" class="rb-result-grade"></div>
                        <div id="resultItemValue" class="rb-result-value"></div>
                    </div>
                </div>
                
                <div class="rb-modal-footer">
                    <button type="button" class="rb-btn rb-btn-primary" data-dismiss="modal">확인</button>
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
    background: rgba(0, 0, 0, 0.7);
    z-index: 1050;
    overflow: auto;
}

.rb-modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* 모달 다이얼로그 */
.rb-modal-dialog {
    width: 90%;
    max-width: 500px;
    margin: 20px;
}

/* 모달 콘텐츠 */
.rb-modal-content {
    background: #fff;
    border-radius: 0;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s;
}

@keyframes slideIn {
    from { 
        opacity: 0;
        transform: translateY(-30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

/* 모달 헤더 */
.rb-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.rb-modal-title {
    font-size: 18px;
    font-weight: 700;
    color: #000;
    margin: 0;
}

.rb-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rb-modal-close:hover {
    color: #000;
}

/* 모달 바디 */
.rb-modal-body {
    padding: 20px;
}

/* 모달 푸터 */
.rb-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* ===================================
 * 구매 확인 모달
 * =================================== */

/* 구매 정보 */
.rb-purchase-info {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.rb-purchase-img {
    width: 120px;
    height: 120px;
    object-fit: contain;
    background: #f8f8f8;
    padding: 10px;
}

.rb-purchase-details {
    flex: 1;
}

.rb-purchase-details h5 {
    font-size: 18px;
    font-weight: 700;
    color: #000;
    margin: 0 0 16px;
}

/* 구매 테이블 */
.rb-purchase-table {
    width: 100%;
    font-size: 14px;
}

.rb-purchase-table td {
    padding: 8px 0;
}

.rb-purchase-table .text-right {
    text-align: right;
}

.rb-purchase-total {
    border-top: 1px solid #ddd;
    font-weight: 700;
}

.rb-purchase-total td {
    padding-top: 12px;
}

/* 구매 메시지 */
.rb-purchase-msg {
    margin: 0;
    padding: 12px;
    background: #f8f8f8;
    font-size: 13px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ===================================
 * 결과 모달
 * =================================== */

/* 오픈 애니메이션 */
.rb-opening {
    padding: 80px 20px;
    text-align: center;
}

.rb-opening-box {
    font-size: 80px;
    color: #000;
    animation: shake 0.5s ease-in-out infinite;
}

@keyframes shake {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-5deg); }
    75% { transform: rotate(5deg); }
}

.rb-opening-text {
    margin-top: 20px;
    font-size: 16px;
    color: #666;
}

/* 결과 표시 */
.rb-result-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    text-align: center;
}

.rb-result-body {
    padding: 40px 20px;
}

.rb-result-item {
    text-align: center;
}

.rb-result-img {
    width: 200px;
    height: 200px;
    object-fit: contain;
    margin-bottom: 20px;
}

.rb-result-name {
    font-size: 24px;
    font-weight: 700;
    color: #000;
    margin: 0 0 12px;
}

.rb-result-grade {
    display: inline-block;
    padding: 6px 16px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
}

.rb-result-grade.grade-normal {
    background: #f0f0f0;
    color: #666;
}

.rb-result-grade.grade-rare {
    background: #e3f2fd;
    color: #1976d2;
}

.rb-result-grade.grade-epic {
    background: #f3e5f5;
    color: #7b1fa2;
}

.rb-result-grade.grade-legendary {
    background: #fff3e0;
    color: #f57c00;
}

.rb-result-value {
    font-size: 20px;
    font-weight: 700;
    color: #4caf50;
}

/* ===================================
 * 버튼 스타일
 * =================================== */

.rb-btn-secondary {
    background: #f0f0f0;
    color: #666;
    border: 1px solid #ddd;
}

.rb-btn-secondary:hover {
    background: #e0e0e0;
    color: #333;
}

.rb-btn-primary {
    background: #000;
    color: #fff;
    border: 1px solid #000;
}

.rb-btn-primary:hover {
    background: #222;
    border-color: #222;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ===================================
 * 반응형
 * =================================== */

@media (max-width: 768px) {
    .rb-purchase-info {
        flex-direction: column;
        align-items: center;
    }
    
    .rb-purchase-img {
        margin-bottom: 20px;
    }
}
</style>