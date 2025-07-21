<?php
/*
 * 파일명: index.php
 * 위치: /chat/index.php
 * 기능: 개선된 실시간 채팅방 목록 페이지 (관리자 기능 추가)
 * 작성일: 2025-07-12
 * 수정일: 2025-07-13
 */

include_once('./_common.php');
include_once('./lib/chat.lib.php');
include_once('./lib/chat_admin.lib.php');

// 로그인 체크
if (!$is_member) {
    alert('로그인 후 이용하세요.', G5_BBS_URL.'/login.php?url='.urlencode(G5_URL.'/chat/'));
}

$g5['title'] = '채팅';
include_once(G5_PATH.'/head.php');
?>

<!-- Bootstrap Icons 비동기 로드 -->
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></noscript>

<!-- 기본 채팅 스타일 -->
<style>
/* ===================================
 * 전역 스타일
 * ===================================
 */
/* 기본 설정 */
* {
    box-sizing: border-box;
}

.chat-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #ffffff;
    height: calc(100vh - 200px);
    min-height: 500px;
    max-height: 800px;
    display: flex;
    border: 1px solid #e0e0e0;
    position: relative;
}

/* ===================================
 * 사이드바 스타일
 * ===================================
 */
/* 사이드바 컨테이너 */
.chat-sidebar {
    width: 320px;
    background: #f8f9fa;
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
    transition: margin-left 0.3s ease;
    height: 100%;
}

.chat-sidebar.collapsed {
    margin-left: -320px;
}

/* 사이드바 헤더 */
.sidebar-header {
    padding: 24px;
    background: #000000;
    color: #ffffff;
    flex-shrink: 0;
}

.sidebar-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    letter-spacing: -0.5px;
}

/* 새 채팅방 버튼 */
.btn-new-room {
    margin: 16px;
    padding: 14px 20px;
    background: #000000;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.btn-new-room:hover {
    background: #333333;
    transform: translateY(-1px);
}

/* 채팅방 목록 */
.room-list {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px 16px;
    min-height: 0;
}

.room-list::-webkit-scrollbar {
    width: 6px;
}

.room-list::-webkit-scrollbar-track {
    background: transparent;
}

.room-list::-webkit-scrollbar-thumb {
    background: #d0d0d0;
    border-radius: 3px;
}

/* 채팅방 아이템 */
.room-item {
    padding: 16px;
    margin-bottom: 8px;
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.room-item:hover {
    border-color: #000000;
    transform: translateX(4px);
}

.room-item.active {
    background: #000000;
    color: #ffffff;
    border-color: #000000;
}

.room-item.active .room-meta {
    color: #cccccc;
}

.room-name {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.room-meta {
    font-size: 13px;
    color: #666666;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* ===================================
 * 메인 콘텐츠 영역
 * ===================================
 */
/* 콘텐츠 컨테이너 */
.chat-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    height: 100%;
    min-width: 0;
}

/* 웰컴 화면 */
.welcome-screen {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
}

.welcome-content {
    max-width: 400px;
}

.welcome-icon {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 36px;
}

.welcome-content h2 {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #000000;
}

.welcome-content p {
    font-size: 16px;
    color: #666666;
    line-height: 1.6;
}

/* ===================================
 * 채팅방 영역
 * ===================================
 */
#chatRoom {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}

/* 채팅방 헤더 */
.chat-header {
    padding: 16px 20px;
    background: #ffffff;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.chat-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.btn-toggle-sidebar {
    display: none;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
    color: #000000;
}

.chat-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
    color: #000000;
}

.chat-header-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-header {
    padding: 6px 12px;
    background: #f8f9fa;
    color: #000000;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-header:hover {
    background: #e9ecef;
    border-color: #000000;
}

.btn-admin {
    background: #dc3545;
    color: #ffffff;
    border-color: #dc3545;
}

.btn-admin:hover {
    background: #c82333;
    border-color: #c82333;
}

.btn-leave-room {
    background: #ffffff;
    color: #000000;
    border: 1px solid #000000;
}

.btn-leave-room:hover {
    background: #000000;
    color: #ffffff;
}

/* 공지사항 영역 */
.notice-area {
    background: #fff3cd;
    border-bottom: 1px solid #ffeaa7;
    padding: 12px 20px;
    display: none;
    flex-shrink: 0;
}

.notice-area.show {
    display: block;
}

.notice-content {
    display: flex;
    align-items: start;
    gap: 10px;
}

.notice-icon {
    color: #856404;
    flex-shrink: 0;
    margin-top: 2px;
}

.notice-text {
    flex: 1;
    color: #856404;
    font-size: 14px;
    line-height: 1.5;
}

.notice-meta {
    font-size: 12px;
    color: #856404;
    opacity: 0.8;
    margin-top: 4px;
}

/* 메시지 영역 */
.message-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #fafafa;
    scroll-behavior: smooth;
    min-height: 0;
}

.message-area::-webkit-scrollbar {
    width: 8px;
}

.message-area::-webkit-scrollbar-track {
    background: transparent;
}

.message-area::-webkit-scrollbar-thumb {
    background: #d0d0d0;
    border-radius: 4px;
}

/* 메시지 아이템 */
.message-item {
    margin-bottom: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.message-item.my-message {
    flex-direction: row-reverse;
}

.message-item.system-message {
    justify-content: center;
    margin: 20px 0;
}

.system-message-content {
    background: #e9ecef;
    color: #666;
    padding: 6px 16px;
    border-radius: 16px;
    font-size: 13px;
}

.message-avatar {
    width: 36px;
    height: 36px;
    background: #000000;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    font-size: 14px;
    flex-shrink: 0;
    cursor: pointer;
}

.message-content {
    max-width: 70%;
}

.message-info {
    font-size: 12px;
    color: #999999;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.my-message .message-info {
    justify-content: flex-end;
}

.message-bubble {
    background: #ffffff;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    word-break: break-word;
    font-size: 15px;
    line-height: 1.5;
    position: relative;
}

.my-message .message-bubble {
    background: #000000;
    color: #ffffff;
    border-color: #000000;
}

.message-bubble.deleted {
    opacity: 0.5;
    font-style: italic;
}

/* 답글 표시 */
.reply-info {
    background: #f0f0f0;
    padding: 6px 10px;
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: 12px;
    color: #666666;
    display: flex;
    align-items: center;
    gap: 6px;
}

.my-message .reply-info {
    background: rgba(255, 255, 255, 0.2);
    color: #cccccc;
}

/* 답글 입력 표시 */
.reply-indicator {
    display: none;
    padding: 8px 16px;
    background: #f0f0f0;
    border-top: 1px solid #e0e0e0;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.reply-indicator.show {
    display: flex;
}

/* 입력 영역 */
.input-area {
    padding: 16px 20px;
    background: #ffffff;
    border-top: 1px solid #e0e0e0;
    flex-shrink: 0;
}

.input-disabled-message {
    text-align: center;
    color: #666;
    font-size: 14px;
    padding: 10px;
}

.input-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.input-wrapper {
    flex: 1;
    position: relative;
}

.input-wrapper input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 24px;
    font-size: 15px;
    outline: none;
    transition: border-color 0.2s ease;
}

.input-wrapper input:focus {
    border-color: #000000;
}

.input-wrapper input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

.btn-send {
    width: 44px;
    height: 44px;
    background: #000000;
    color: #ffffff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.btn-send:hover {
    background: #333333;
    transform: scale(1.05);
}

.btn-send:disabled {
    background: #cccccc;
    cursor: not-allowed;
    transform: scale(1);
}

/* 슬로우 모드 표시 */
.slow-mode-indicator {
    position: absolute;
    right: 60px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: #dc3545;
}

/* 메시지 액션 버튼 */
.message-actions {
    position: absolute;
    top: -24px;
    right: 0;
    display: none;
    gap: 4px;
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.message-item:hover .message-actions {
    display: flex;
}

.btn-message-action {
    background: none;
    border: none;
    padding: 4px 8px;
    cursor: pointer;
    font-size: 14px;
    color: #666666;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-message-action:hover {
    background: #f0f0f0;
    color: #000000;
}

.btn-message-action.delete {
    color: #dc3545;
}

.btn-message-action.delete:hover {
    background: #f8d7da;
}

/* 사용자 정보 */
.message-user-info {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.message-user-info:hover .message-nick {
    text-decoration: underline;
}

.message-level {
    display: inline-block;
    padding: 1px 4px;
    background: #000000;
    color: #ffffff;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 500;
}

.message-nick {
    font-weight: 500;
}

.message-time {
    font-size: 12px;
    color: #999999;
}

/* 임시 메시지 */
.message-item.temp-message {
    opacity: 0.7;
}

.sending-indicator {
    color: #999;
    font-size: 11px;
    font-style: italic;
    margin-left: 5px;
}

/* 메시지 애니메이션 */
.message-item {
    animation: messageSlideIn 0.2s ease;
    transform-origin: bottom;
}

.message-item.my-message {
    animation: myMessageSlideIn 0.2s ease;
}

@keyframes messageSlideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes myMessageSlideIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

/* ===================================
 * 모달 스타일
 * ===================================
 */
/* 모달 배경 */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* 모달 컨텐츠 */
.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    padding: 32px;
    border-radius: 12px;
    width: 90%;
    max-width: 440px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translate(-50%, -45%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

/* 폼 그룹 */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #333333;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    outline: none;
    transition: border-color 0.2s ease;
}

.form-group input:focus {
    border-color: #000000;
}

/* 모달 버튼 */
.modal-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 28px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #000000;
    color: #ffffff;
}

.btn-primary:hover {
    background: #333333;
}

.btn-secondary {
    background: #ffffff;
    color: #000000;
    border: 1px solid #e0e0e0;
}

.btn-secondary:hover {
    background: #f8f9fa;
}

.btn-danger {
    background: #dc3545;
    color: #ffffff;
}

.btn-danger:hover {
    background: #c82333;
}

/* ===================================
 * 관리자 모달
 * ===================================
 */
.admin-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    animation: fadeIn 0.2s ease;
}

.admin-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.admin-modal-header {
    padding: 20px 24px;
    background: #dc3545;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.admin-modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(80vh - 140px);
}

.admin-section {
    margin-bottom: 30px;
}

.admin-section h4 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    padding-bottom: 8px;
    border-bottom: 2px solid #e0e0e0;
}

.admin-form-group {
    margin-bottom: 16px;
}

.admin-form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #555;
}

.admin-form-group input,
.admin-form-group textarea,
.admin-form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
}

.admin-form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.banned-user-list {
    margin-top: 12px;
}

.banned-user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
}

.banned-user-info {
    flex: 1;
}

.banned-user-nick {
    font-weight: 500;
    margin-bottom: 2px;
}

.banned-user-detail {
    font-size: 12px;
    color: #666;
}

.btn-unban {
    padding: 4px 12px;
    background: #28a745;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.btn-unban:hover {
    background: #218838;
}

/* 스위치 토글 */
.switch-group {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #dc3545;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* ===================================
 * 로딩 스피너
 * ===================================
 */
.loading-spinner {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2000;
    display: none;
}

.loading-spinner.active {
    display: block;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f0f0f0;
    border-top-color: #000000;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ===================================
 * 빈 상태
 * ===================================
 */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    display: block;
    opacity: 0.3;
}

.empty-state p {
    font-size: 15px;
    margin: 0;
}

/* ===================================
 * 반응형
 * ===================================
 */
@media (max-width: 768px) {
    .chat-wrapper {
        height: calc(100vh - 150px);
        max-height: none;
    }
    
    .chat-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100%;
        z-index: 999;
        margin-left: -320px;
    }
    
    .chat-sidebar.active {
        margin-left: 0;
    }
    
    .btn-toggle-sidebar {
        display: block;
    }
    
    .message-content {
        max-width: 85%;
    }
    
    .chat-header-right {
        flex-wrap: wrap;
    }
    
    .admin-modal-content {
        max-width: 95%;
    }
}
</style>

<!-- ===================================
     채팅 애플리케이션
     =================================== -->
<div class="chat-wrapper">
    <!-- 사이드바 -->
    <div class="chat-sidebar" id="chatSidebar">
        <!-- 사이드바 헤더 -->
        <div class="sidebar-header">
            <h1>채팅</h1>
        </div>
        
        <!-- 새 채팅방 버튼 -->
        <button class="btn-new-room" onclick="showCreateModal()">
            <i class="bi bi-plus-circle"></i>
            새 채팅방 만들기
        </button>
        
        <!-- 채팅방 목록 -->
        <div class="room-list" id="roomList">
            <div class="empty-state">
                <i class="bi bi-chat-square-dots"></i>
                <p>채팅방이 없습니다</p>
            </div>
        </div>
    </div>
    
    <!-- 메인 콘텐츠 -->
    <div class="chat-content" id="chatContent">
        <!-- 웰컴 화면 -->
        <div class="welcome-screen" id="welcomeScreen">
            <div class="welcome-content">
                <div class="welcome-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h2>환영합니다, <?php echo $member['mb_nick']; ?>님</h2>
                <p>왼쪽 목록에서 채팅방을 선택하거나<br>새 채팅방을 만들어 대화를 시작하세요.</p>
            </div>
        </div>
        
        <!-- 채팅방 화면 (숨김) -->
        <div id="chatRoom" style="display: none;">
            <!-- 동적으로 로드됨 -->
        </div>
    </div>
</div>

<!-- ===================================
     채팅방 생성 모달
     =================================== -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>새 채팅방 만들기</h3>
        </div>
        <form id="createRoomForm">
            <div class="form-group">
                <label for="room_name">채팅방 이름</label>
                <input type="text" id="room_name" name="room_name" required 
                       placeholder="채팅방 이름을 입력하세요" maxlength="100" autocomplete="off">
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="hideCreateModal()">취소</button>
                <button type="submit" class="btn btn-primary">만들기</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================================
     사용자 목록 모달
     =================================== -->
<div id="userListModal" class="user-list-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1001;">
    <div class="user-list-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #ffffff; padding: 0; border-radius: 12px; width: 90%; max-width: 480px; max-height: 80vh; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);">
        <div class="user-list-header" style="padding: 20px 24px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 600;">참여자 목록</h3>
            <button class="btn-close-modal" onclick="hideUserList()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="user-list-body" id="userListBody" style="padding: 16px; overflow-y: auto; max-height: calc(80vh - 80px);">
            <!-- 사용자 목록이 여기에 표시됩니다 -->
        </div>
    </div>
</div>

<!-- ===================================
     사용자 프로필 사이드바
     =================================== -->
<div id="profileSidebar" class="profile-sidebar" style="position: fixed; top: 0; right: -320px; width: 320px; height: 100%; background: #ffffff; border-left: 1px solid #e0e0e0; z-index: 1002; transition: right 0.3s ease; box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);">
    <div class="profile-header" style="padding: 20px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 18px; font-weight: 600;">사용자 정보</h3>
        <button class="btn-close-profile" onclick="hideUserProfile()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <div class="profile-content" id="profileContent" style="padding: 20px;">
        <!-- 프로필 정보가 여기에 표시됩니다 -->
    </div>
</div>

<!-- ===================================
     관리자 모달
     =================================== -->
<div id="adminModal" class="admin-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h3>채팅방 관리</h3>
            <button class="btn-close-modal" onclick="hideAdminModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #fff; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="admin-modal-body">
            <!-- 공지사항 설정 -->
            <div class="admin-section">
                <h4>공지사항</h4>
                <div class="admin-form-group">
                    <textarea id="noticeContent" placeholder="공지사항을 입력하세요. 비워두면 공지사항이 제거됩니다."></textarea>
                </div>
                <button class="btn btn-primary" onclick="setNotice()">공지사항 설정</button>
            </div>
            
            <!-- 사용자 차단 -->
            <div class="admin-section">
                <h4>사용자 차단</h4>
                <div class="admin-form-group">
                    <label>차단할 사용자</label>
                    <select id="banUserId">
                        <option value="">사용자 선택</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>차단 유형</label>
                    <select id="banType">
                        <option value="mute">음소거</option>
                        <option value="kick">강퇴</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>차단 시간 (분, 비워두면 영구)</label>
                    <input type="number" id="banDuration" min="1" placeholder="예: 60">
                </div>
                <div class="admin-form-group">
                    <label>차단 사유</label>
                    <input type="text" id="banReason" placeholder="선택사항">
                </div>
                <button class="btn btn-danger" onclick="banUser()">사용자 차단</button>
                
                <!-- 차단된 사용자 목록 -->
                <div class="banned-user-list" id="bannedUserList">
                    <!-- 차단된 사용자 목록이 여기에 표시됩니다 -->
                </div>
            </div>
            
            <!-- 채팅방 설정 -->
            <div class="admin-section">
                <h4>채팅방 설정</h4>
                <div class="switch-group">
                    <label>읽기 전용 모드</label>
                    <label class="switch">
                        <input type="checkbox" id="readonlyMode">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="admin-form-group">
                    <label>슬로우 모드 (초, 0은 비활성화)</label>
                    <input type="number" id="slowMode" min="0" max="300" value="0">
                </div>
                <div class="admin-form-group">
                    <label>최소 참여 레벨</label>
                    <input type="number" id="minLevel" min="1" max="10" value="1">
                </div>
                <button class="btn btn-primary" onclick="saveRoomSettings()">설정 저장</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================================
     로딩 스피너
     =================================== -->
<div class="loading-spinner" id="loadingSpinner">
    <div class="spinner"></div>
</div>

<!-- 전역 변수 설정 -->
<script>
const myId = '<?php echo $member['mb_id']; ?>';
const myNick = '<?php echo $member['mb_nick']; ?>';
const myLevel = '<?php echo $member['mb_level']; ?>';
const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
</script>

<!-- 개선된 채팅 스크립트 -->
<script src="./js/chat.js?v=<?php echo time(); ?>"></script>

<!-- 관리자 기능 스크립트 -->
<script src="./js/chat_admin.js?v=<?php echo time(); ?>"></script>

<!-- 추가 이벤트 핸들러 -->
<script>
// ===================================
// 모달 및 UI 관련 함수
// ===================================

/* 채팅방 생성 모달 표시 */
window.showCreateModal = function() {
    document.getElementById('createModal').style.display = 'block';
    document.getElementById('room_name').focus();
};

/* 채팅방 생성 모달 숨기기 */
window.hideCreateModal = function() {
    document.getElementById('createModal').style.display = 'none';
    document.getElementById('createRoomForm').reset();
};

/* 사용자 목록 표시 */
window.showUserList = async function() {
    if (!chatState.currentRoomId) return;
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_users',
                room_id: chatState.currentRoomId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            renderUserList(data.users);
            document.getElementById('userListModal').style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading user list:', error);
    } finally {
        hideLoading();
    }
};

/* 사용자 목록 렌더링 */
function renderUserList(users) {
    const userListBody = document.getElementById('userListBody');
    
    if (users.length === 0) {
        userListBody.innerHTML = '<p style="text-align: center; color: #999;">접속 중인 사용자가 없습니다.</p>';
        return;
    }
    
    userListBody.innerHTML = users.map(user => {
        const firstChar = (user.member_nick || user.mb_nick).charAt(0).toUpperCase();
        const isOwner = user.role === 'owner';
        const isAdmin = user.role === 'admin';
        const isMe = user.mb_id === myId;
        const canManage = chatState.currentRoomInfo && 
            (chatState.currentRoomInfo.created_by === myId || 
             chatState.currentRoomInfo.user_role === 'owner' || 
             chatState.currentRoomInfo.user_role === 'admin');
        
        return `
            <div class="user-item" style="padding: 12px 16px; margin-bottom: 8px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                <div class="user-info" style="display: flex; align-items: center; gap: 12px;">
                    <div class="user-avatar" style="width: 40px; height: 40px; background: #000000; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 500; font-size: 16px;">${firstChar}</div>
                    <div class="user-details">
                        <div class="user-name" style="font-weight: 500; font-size: 15px; display: flex; align-items: center; gap: 6px;">
                            <span class="user-level" style="display: inline-block; padding: 2px 6px; background: #000000; color: #ffffff; border-radius: 4px; font-size: 11px; font-weight: 500;">Lv.${user.mb_level || '1'}</span>
                            ${escapeHtml(user.member_nick || user.mb_nick)}
                            ${isOwner ? '<span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; margin-left: 6px; background: #ffd700; color: #000000;">소유자</span>' : ''}
                            ${isAdmin ? '<span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; margin-left: 6px; background: #007bff; color: #ffffff;">관리자</span>' : ''}
                            ${isMe ? '<span style="color: #666; font-size: 12px;">(나)</span>' : ''}
                        </div>
                        <div class="user-role" style="font-size: 12px; color: #666666;">
                            ${user.mb_id}
                        </div>
                    </div>
                </div>
                ${canManage && !isMe && !isOwner ? `
                    <div class="user-actions">
                        ${isAdmin ? 
                            `<button class="btn-user-action" onclick="setUserRole('${user.mb_id}', 'member')" style="padding: 6px 12px; background: #ffffff; color: #000000; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 13px; cursor: pointer;">관리자 해제</button>` :
                            `<button class="btn-user-action" onclick="setUserRole('${user.mb_id}', 'admin')" style="padding: 6px 12px; background: #ffffff; color: #000000; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 13px; cursor: pointer;">관리자 지정</button>`
                        }
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');
}

/* 사용자 목록 숨기기 */
window.hideUserList = function() {
    document.getElementById('userListModal').style.display = 'none';
};

/* 사용자 프로필 표시 */
window.showUserProfile = function(mbId, mbNick, mbLevel) {
    const profileContent = document.getElementById('profileContent');
    const firstChar = mbNick.charAt(0).toUpperCase();
    
    const canBan = chatState.currentRoomInfo && 
        (chatState.currentRoomInfo.created_by === myId || 
         chatState.currentRoomInfo.user_role === 'owner' || 
         chatState.currentRoomInfo.user_role === 'admin') && 
        mbId !== myId;
    
    profileContent.innerHTML = `
        <div class="profile-info" style="text-align: center; margin-bottom: 30px;">
            <div class="profile-avatar-large" style="width: 80px; height: 80px; background: #000000; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 500; margin: 0 auto 16px;">${firstChar}</div>
            <div class="profile-name" style="font-size: 20px; font-weight: 600; margin-bottom: 4px;">${escapeHtml(mbNick)}</div>
            <div class="profile-id" style="font-size: 14px; color: #666666; margin-bottom: 8px;">${mbId}</div>
            <div class="profile-level" style="display: inline-block; padding: 4px 12px; background: #000000; color: #ffffff; border-radius: 16px; font-size: 14px; font-weight: 500;">레벨 ${mbLevel}</div>
        </div>
        <div class="profile-actions" style="display: flex; flex-direction: column; gap: 10px;">
            <button class="btn-profile-action primary" onclick="startPrivateChat('${mbId}', '${escapeHtml(mbNick)}')" style="padding: 12px 20px; background: #000000; color: #ffffff; border: 1px solid #000000; border-radius: 8px; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-chat-dots"></i> 1:1 채팅
            </button>
            <button class="btn-profile-action" onclick="mentionUser('${mbId}', '${escapeHtml(mbNick)}')" style="padding: 12px 20px; background: #ffffff; color: #000000; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-at"></i> 멘션하기
            </button>
            ${canBan ? `
                <button class="btn-profile-action" onclick="quickBanUser('${mbId}', '${escapeHtml(mbNick)}')" style="padding: 12px 20px; background: #ffffff; color: #dc3545; border: 1px solid #dc3545; border-radius: 8px; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="bi bi-slash-circle"></i> 차단하기
                </button>
            ` : ''}
        </div>
    `;
    
    document.getElementById('profileSidebar').style.right = '0';
};

/* 프로필 사이드바 숨기기 */
window.hideUserProfile = function() {
    document.getElementById('profileSidebar').style.right = '-320px';
};

/* 사이드바 토글 */
window.toggleSidebar = function() {
    const sidebar = document.getElementById('chatSidebar');
    sidebar.classList.toggle('active');
};

/* 채팅방 나가기 */
window.leaveRoom = async function() {
    if (!chatState.currentRoomId) return;
    
    if (confirm('채팅방을 나가시겠습니까?')) {
        await leaveCurrentRoom();
        
        // UI 초기화
        document.getElementById('chatRoom').style.display = 'none';
        document.getElementById('welcomeScreen').style.display = 'flex';
        
        // 채팅방 목록 갱신
        loadRoomList();
    }
};

/* 멘션 사용자 */
window.mentionUser = function(mbId, mbNick) {
    const input = document.getElementById('messageInput');
    if (input) {
        input.value += `@${mbNick} `;
        input.focus();
    }
    hideUserProfile();
};

/* 멘션 클릭 */
window.mentionClick = function(mbNick) {
    alert(`@${mbNick} 사용자 정보`);
};

/* 1:1 채팅 시작 */
window.startPrivateChat = async function(mbId, mbNick) {
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_private_chat',
                target_mb_id: mbId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            hideUserProfile();
            enterRoom(data.room_id, mbNick + '님과의 대화', null);
        } else {
            alert(data.message || '개인 채팅방 생성에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error creating private chat:', error);
    } finally {
        hideLoading();
    }
};

/* 사용자 역할 설정 */
window.setUserRole = async function(mb_id, role) {
    if (!chatState.currentRoomId) return;
    
    if (!confirm(role === 'admin' ? '이 사용자를 관리자로 지정하시겠습니까?' : '관리자 권한을 해제하시겠습니까?')) {
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_admin',
                room_id: chatState.currentRoomId,
                target_mb_id: mb_id,
                role: role
            })
        });
        
        const data = await response.json();
        if (data.success) {
            showUserList(); // 목록 새로고침
        } else {
            alert(data.message || '권한 설정에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error setting user role:', error);
    } finally {
        hideLoading();
    }
};

// ===================================
// 채팅방 생성 폼 처리
// ===================================
document.getElementById('createRoomForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const roomName = document.getElementById('room_name').value.trim();
    if (!roomName) {
        alert('채팅방 이름을 입력하세요.');
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_room',
                room_name: roomName
            })
        });
        
        const data = await response.json();
        if (data.success) {
            hideCreateModal();
            loadRoomList();
            // 생성된 채팅방으로 입장
            setTimeout(() => {
                enterRoom(data.room_id, roomName, null);
            }, 100);
        } else {
            alert(data.message || '채팅방 생성에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    } finally {
        hideLoading();
    }
});

// ===================================
// 모달 외부 클릭 처리
// ===================================
window.onclick = function(event) {
    const createModal = document.getElementById('createModal');
    const userListModal = document.getElementById('userListModal');
    const profileSidebar = document.getElementById('profileSidebar');
    const adminModal = document.getElementById('adminModal');
    
    if (event.target === createModal) {
        hideCreateModal();
    } else if (event.target === userListModal) {
        hideUserList();
    } else if (event.target === adminModal) {
        hideAdminModal();
    }
    
    // 프로필 사이드바 외부 클릭 시 닫기
    if (profileSidebar.style.right === '0px' && 
        !profileSidebar.contains(event.target) && 
        !event.target.closest('.message-avatar') &&
        !event.target.closest('.message-user-info')) {
        hideUserProfile();
    }
};
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>