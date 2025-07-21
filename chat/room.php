<?php
/*
 * 파일명: room.php
 * 위치: /chat/room.php
 * 기능: 개선된 실시간 채팅방 화면
 * 작성일: 2025-07-12
 * 수정일: 2025-07-13
 */

include_once('./_common.php');
include_once('./lib/chat.lib.php');

// 로그인 체크
if (!$is_member) {
    alert('로그인 후 이용하세요.', G5_BBS_URL.'/login.php');
}

$room_id = (int)$_GET['id'];
if (!$room_id) {
    alert('잘못된 접근입니다.', './index.php');
}

// 채팅방 정보 조회
$sql = " SELECT * FROM g5_chat_room WHERE room_id = '".(int)$room_id."' AND is_active = 1 ";
$room = sql_fetch($sql);

if (!$room) {
    alert('존재하지 않는 채팅방입니다.', './index.php');
}

// 채팅방 입장
join_chat_room($room_id, $member['mb_id'], $member['mb_nick']);

$g5['title'] = $room['room_name'];
include_once(G5_PATH.'/head.php');
?>

<!-- Bootstrap Icons 비동기 로드 -->
<link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></noscript>

<style>
/* ===================================
 * 채팅방 스타일
 * ===================================
 */
/* 채팅방 컨테이너 */
.chat-room-container {
    max-width: 1200px;
    margin: 0 auto;
    height: calc(100vh - 200px);
    display: flex;
    gap: 20px;
    padding: 20px;
}

/* 채팅 영역 */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

/* 채팅방 헤더 */
.chat-room-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-room-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #000;
}

.header-buttons {
    display: flex;
    gap: 10px;
}

.btn-leave {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-leave:hover {
    background-color: #c82333;
}

/* 메시지 영역 */
.message-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f5f5f5;
    scroll-behavior: smooth;
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
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    animation: messageSlideIn 0.2s ease;
}

.message-item.my-message {
    flex-direction: row-reverse;
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

.message-avatar {
    width: 40px;
    height: 40px;
    background: #000;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.message-avatar:hover {
    transform: scale(1.1);
}

.message-content {
    max-width: 70%;
}

.message-info {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.my-message .message-info {
    text-align: right;
    justify-content: flex-end;
}

.message-nick {
    font-weight: 600;
    cursor: pointer;
}

.message-nick:hover {
    text-decoration: underline;
}

.message-time {
    color: #999;
}

.message-bubble {
    background: white;
    padding: 10px 15px;
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    word-break: break-word;
    position: relative;
}

.my-message .message-bubble {
    background: #000;
    color: white;
}

/* 답글 정보 */
.reply-info {
    background: #f0f0f0;
    padding: 6px 10px;
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 6px;
}

.my-message .reply-info {
    background: rgba(255, 255, 255, 0.2);
    color: #ccc;
}

/* 메시지 액션 */
.message-actions {
    position: absolute;
    top: -24px;
    right: 0;
    display: none;
    gap: 4px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 2px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
    color: #666;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-message-action:hover {
    background: #f0f0f0;
    color: #000;
}

/* 답글 표시 */
.reply-indicator {
    display: none;
    padding: 8px 16px;
    background: #f0f0f0;
    border-top: 1px solid #e0e0e0;
    align-items: center;
    justify-content: space-between;
}

.reply-indicator.show {
    display: flex;
}

.reply-text {
    font-size: 13px;
    color: #666;
}

.btn-cancel-reply {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 13px;
}

/* 입력 영역 */
.input-area {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
    background: white;
}

.input-form {
    display: flex;
    gap: 10px;
}

.input-form input {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 25px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease;
}

.input-form input:focus {
    border-color: #000;
}

.btn-send {
    background-color: #000;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-send:hover {
    background-color: #333;
    transform: scale(1.05);
}

.btn-send:disabled {
    background-color: #ccc;
    cursor: not-allowed;
    transform: scale(1);
}

/* 사용자 목록 */
.user-list-area {
    width: 250px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.user-list-header {
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    font-weight: bold;
}

.user-list {
    padding: 15px;
    overflow-y: auto;
    max-height: calc(100vh - 300px);
}

.user-item {
    padding: 8px 12px;
    margin-bottom: 5px;
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.user-item:hover {
    background: #e9ecef;
    transform: translateX(2px);
}

.user-status {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.user-nick {
    font-size: 14px;
    font-weight: 500;
}

.user-level {
    font-size: 11px;
    color: #666;
    margin-left: auto;
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

/* 로딩 스피너 */
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
    border-top-color: #000;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 반응형 */
@media (max-width: 768px) {
    .chat-room-container {
        flex-direction: column;
        height: calc(100vh - 150px);
        padding: 10px;
    }
    
    .user-list-area {
        width: 100%;
        order: -1;
        max-height: 150px;
    }
    
    .message-content {
        max-width: 85%;
    }
    
    .message-actions {
        display: flex !important;
        opacity: 0.8;
    }
}
</style>

<!-- ===================================
     채팅방 화면
     =================================== -->
<div class="chat-room-container">
    <!-- 채팅 영역 -->
    <div class="chat-area">
        <!-- 채팅방 헤더 -->
        <div class="chat-room-header">
            <h3><?php echo htmlspecialchars($room['room_name']); ?></h3>
            <div class="header-buttons">
                <button class="btn-leave" onclick="leaveRoom()">나가기</button>
            </div>
        </div>
        
        <!-- 메시지 영역 -->
        <div class="message-area" id="messageArea">
            <!-- 메시지가 여기에 표시됩니다 -->
        </div>
        
        <!-- 답글 표시 -->
        <div class="reply-indicator" id="replyIndicator">
            <div class="reply-text">
                <i class="bi bi-reply"></i> <span id="replyToText"></span>님에게 답글
            </div>
            <button class="btn-cancel-reply" onclick="cancelReply()">
                <i class="bi bi-x"></i> 취소
            </button>
        </div>
        
        <!-- 입력 영역 -->
        <div class="input-area">
            <form class="input-form" id="messageForm">
                <input type="text" id="messageInput" placeholder="메시지를 입력하세요..." 
                       autocomplete="off" maxlength="500">
                <button type="submit" class="btn-send" id="btnSend">
                    <i class="bi bi-send"></i> 전송
                </button>
            </form>
        </div>
    </div>
    
    <!-- 사용자 목록 -->
    <div class="user-list-area">
        <div class="user-list-header">
            참여자 목록 (<span id="userCount">0</span>)
        </div>
        <div class="user-list" id="userList">
            <!-- 사용자 목록이 여기에 표시됩니다 -->
        </div>
    </div>
</div>

<!-- 로딩 스피너 -->
<div class="loading-spinner" id="loadingSpinner">
    <div class="spinner"></div>
</div>

<script>
// ===================================
// 전역 변수 및 설정
// ===================================
const CHAT_CONFIG = {
    POLLING_INTERVAL: 500,      // 0.5초 고정 폴링
    HEARTBEAT_INTERVAL: 10000,  // 10초마다 하트비트
    USER_UPDATE_INTERVAL: 5000, // 5초마다 사용자 목록 업데이트
    MESSAGE_BATCH_SIZE: 50      // 한 번에 가져올 메시지 수
};

// 상태 관리
const chatState = {
    roomId: <?php echo $room_id; ?>,
    roomName: '<?php echo addslashes($room['room_name']); ?>',
    myId: '<?php echo $member['mb_id']; ?>',
    myNick: '<?php echo addslashes($member['mb_nick']); ?>',
    myLevel: '<?php echo $member['mb_level']; ?>',
    lastMsgId: 0,
    messageQueue: new Map(),
    isPolling: false,
    pollingTimer: null,
    heartbeatTimer: null,
    userUpdateTimer: null,
    replyToMsgId: null,
    replyToNick: null
};

// ===================================
// 초기화
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    // 초기 데이터 로드
    getMessages();
    getUsers();
    
    // 실시간 폴링 시작
    startRealTimePolling();
    
    // 메시지 입력 폼 이벤트
    document.getElementById('messageForm').addEventListener('submit', sendMessage);
    
    // 엔터키 전송
    document.getElementById('messageInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(e);
        }
    });
    
    // 입력창 포커스
    document.getElementById('messageInput').focus();
});

// ===================================
// 실시간 폴링
// ===================================
function startRealTimePolling() {
    // 고정 간격 메시지 폴링 (0.5초)
    chatState.pollingTimer = setInterval(() => {
        if (!chatState.isPolling) {
            getMessages();
        }
    }, CHAT_CONFIG.POLLING_INTERVAL);
    
    // 하트비트
    chatState.heartbeatTimer = setInterval(sendHeartbeat, CHAT_CONFIG.HEARTBEAT_INTERVAL);
    
    // 사용자 목록 업데이트
    chatState.userUpdateTimer = setInterval(getUsers, CHAT_CONFIG.USER_UPDATE_INTERVAL);
}

// ===================================
// 메시지 관련
// ===================================

/* 메시지 전송 */
async function sendMessage(e) {
    e.preventDefault();
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    const btnSend = document.getElementById('btnSend');
    
    if (!message) return;
    
    // 메시지 데이터
    const messageData = {
        action: 'send_message',
        room_id: chatState.roomId,
        message: message
    };
    
    if (chatState.replyToMsgId) {
        messageData.reply_to = chatState.replyToMsgId;
    }
    
    // UI 즉시 업데이트
    input.value = '';
    btnSend.disabled = true;
    
    // Optimistic UI - 임시 메시지 표시
    const tempId = `temp_${Date.now()}`;
    const tempMsg = {
        msg_id: tempId,
        mb_id: chatState.myId,
        mb_nick: chatState.myNick,
        mb_level: chatState.myLevel,
        message: message,
        created_at: new Date().toISOString(),
        reply_to_msg_id: chatState.replyToMsgId,
        reply_to_nick: chatState.replyToNick,
        is_temp: true
    };
    
    addMessage(tempMsg);
    chatState.messageQueue.set(tempId, true);
    
    // 답글 모드 취소
    cancelReply();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(messageData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 임시 메시지 제거
            removeTempMessage(tempId);
            
            // 실제 메시지로 교체
            const realMsg = { ...tempMsg, msg_id: data.msg_id, is_temp: false };
            chatState.messageQueue.set(data.msg_id, true);
            addMessage(realMsg);
            
            // 마지막 메시지 ID 업데이트
            if (data.msg_id > chatState.lastMsgId) {
                chatState.lastMsgId = data.msg_id;
            }
        } else {
            removeTempMessage(tempId);
            alert(data.message || '메시지 전송에 실패했습니다.');
        }
    } catch (error) {
        removeTempMessage(tempId);
        console.error('Error sending message:', error);
        alert('메시지 전송 중 오류가 발생했습니다.');
    } finally {
        btnSend.disabled = false;
        input.focus();
    }
}

/* 메시지 가져오기 */
async function getMessages() {
    if (chatState.isPolling) return;
    
    chatState.isPolling = true;
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_messages',
                room_id: chatState.roomId,
                last_msg_id: chatState.lastMsgId
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.messages?.length > 0) {
            data.messages.forEach(msg => {
                // 중복 체크
                if (!chatState.messageQueue.has(msg.msg_id)) {
                    chatState.messageQueue.set(msg.msg_id, true);
                    addMessage(msg);
                    
                    // 마지막 메시지 ID 업데이트
                    if (msg.msg_id > chatState.lastMsgId) {
                        chatState.lastMsgId = msg.msg_id;
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error getting messages:', error);
    } finally {
        chatState.isPolling = false;
    }
}

/* 메시지 추가 */
function addMessage(msg) {
    const messageArea = document.getElementById('messageArea');
    
    // 이미 표시된 메시지인지 확인
    if (!msg.is_temp && document.querySelector(`[data-msg-id="${msg.msg_id}"]`)) {
        return;
    }
    
    const messageItem = createMessageElement(msg);
    
    // 스크롤 위치 확인
    const isScrolledToBottom = messageArea.scrollHeight - messageArea.scrollTop <= messageArea.clientHeight + 100;
    
    messageArea.appendChild(messageItem);
    
    // 자동 스크롤
    if (isScrolledToBottom || msg.mb_id === chatState.myId) {
        messageArea.scrollTop = messageArea.scrollHeight;
    }
}

/* 메시지 엘리먼트 생성 */
function createMessageElement(msg) {
    const messageItem = document.createElement('div');
    const isMyMessage = msg.mb_id === chatState.myId;
    messageItem.className = 'message-item' + (isMyMessage ? ' my-message' : '');
    if (msg.is_temp) messageItem.className += ' temp-message';
    messageItem.setAttribute('data-msg-id', msg.msg_id);
    
    const firstChar = msg.mb_nick.charAt(0).toUpperCase();
    const time = new Date(msg.created_at).toLocaleTimeString('ko-KR', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // 답글 정보
    let replyInfo = '';
    if (msg.reply_to_msg_id && msg.reply_to_nick) {
        replyInfo = `
            <div class="reply-info">
                <i class="bi bi-reply"></i>
                ${escapeHtml(msg.reply_to_nick)}님에게 답글
            </div>
        `;
    }
    
    messageItem.innerHTML = `
        <div class="message-avatar" onclick="showUserInfo('${msg.mb_id}', '${escapeHtml(msg.mb_nick)}', '${msg.mb_level || '1'}')">${firstChar}</div>
        <div class="message-content">
            <div class="message-info">
                <span class="message-nick" onclick="showUserInfo('${msg.mb_id}', '${escapeHtml(msg.mb_nick)}', '${msg.mb_level || '1'}')">${escapeHtml(msg.mb_nick)}</span>
                <span class="message-time">${time}</span>
                ${msg.is_temp ? '<span class="sending-indicator">전송중...</span>' : ''}
            </div>
            <div class="message-bubble">
                ${replyInfo}
                ${processMessage(msg.message)}
                ${!msg.is_temp ? `
                <div class="message-actions">
                    <button class="btn-message-action" onclick="replyToMessage(${msg.msg_id}, '${escapeHtml(msg.mb_nick)}')">
                        <i class="bi bi-reply"></i>
                    </button>
                </div>
                ` : ''}
            </div>
        </div>
    `;
    
    return messageItem;
}

/* 임시 메시지 제거 */
function removeTempMessage(tempId) {
    const tempMsg = document.querySelector(`[data-msg-id="${tempId}"]`);
    if (tempMsg) {
        tempMsg.remove();
    }
    chatState.messageQueue.delete(tempId);
}

/* 메시지 처리 */
function processMessage(message) {
    // @멘션 처리
    message = message.replace(/@(\S+)/g, '<span style="color: #007bff; font-weight: 500;">@$1</span>');
    
    // HTML 이스케이프 후 멘션 태그 복원
    return escapeHtml(message).replace(/&lt;span style="color: #007bff; font-weight: 500;"&gt;(.*?)&lt;\/span&gt;/g, (match, p1) => {
        return match.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"');
    });
}

// ===================================
// 사용자 관련
// ===================================

/* 사용자 목록 가져오기 */
async function getUsers() {
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_users',
                room_id: chatState.roomId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            updateUserList(data.users);
        }
    } catch (error) {
        console.error('Error getting users:', error);
    }
}

/* 사용자 목록 업데이트 */
function updateUserList(users) {
    const userList = document.getElementById('userList');
    const userCount = document.getElementById('userCount');
    
    userCount.textContent = users.length;
    
    userList.innerHTML = users.map(user => {
        const firstChar = (user.member_nick || user.mb_nick).charAt(0).toUpperCase();
        const userLevel = user.mb_level || '1';
        const isMe = user.mb_id === chatState.myId;
        
        return `
            <div class="user-item" onclick="showUserInfo('${user.mb_id}', '${escapeHtml(user.member_nick || user.mb_nick)}', '${userLevel}')">
                <div class="user-status"></div>
                <div class="user-nick">${escapeHtml(user.member_nick || user.mb_nick)}${isMe ? ' (나)' : ''}</div>
                <div class="user-level">Lv.${userLevel}</div>
            </div>
        `;
    }).join('');
}

/* 사용자 정보 표시 */
function showUserInfo(mbId, mbNick, mbLevel) {
    if (mbId === chatState.myId) return;
    
    if (confirm(`${mbNick}님에게 1:1 채팅을 요청하시겠습니까?`)) {
        // 1:1 채팅 구현 (추후 개발)
        alert('1:1 채팅 기능은 준비 중입니다.');
    }
}

// ===================================
// 답글 기능
// ===================================

/* 답글 기능 */
function replyToMessage(msgId, nick) {
    chatState.replyToMsgId = msgId;
    chatState.replyToNick = nick;
    
    document.getElementById('replyToText').textContent = nick;
    document.getElementById('replyIndicator').classList.add('show');
    document.getElementById('messageInput').focus();
}

/* 답글 취소 */
function cancelReply() {
    chatState.replyToMsgId = null;
    chatState.replyToNick = null;
    document.getElementById('replyIndicator').classList.remove('show');
}

// ===================================
// 유틸리티
// ===================================

/* HTML 이스케이프 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* 하트비트 전송 */
async function sendHeartbeat() {
    try {
        await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'heartbeat',
                room_id: chatState.roomId
            })
        });
    } catch (error) {
        console.error('Heartbeat error:', error);
    }
}

/* 채팅방 나가기 */
async function leaveRoom() {
    if (!confirm('채팅방을 나가시겠습니까?')) return;
    
    // 타이머 정리
    if (chatState.pollingTimer) clearInterval(chatState.pollingTimer);
    if (chatState.heartbeatTimer) clearInterval(chatState.heartbeatTimer);
    if (chatState.userUpdateTimer) clearInterval(chatState.userUpdateTimer);
    
    try {
        await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'leave_room',
                room_id: chatState.roomId
            })
        });
    } catch (error) {
        console.error('Error leaving room:', error);
    }
    
    location.href = './index.php';
}

/* 페이지 나갈 때 처리 */
window.addEventListener('beforeunload', function() {
    // FormData로 전송
    const formData = new FormData();
    formData.append('data', JSON.stringify({
        action: 'leave_room',
        room_id: chatState.roomId
    }));
    
    navigator.sendBeacon('./ajax/chat_handler.php', formData);
});

/* 로딩 표시 */
function showLoading() {
    document.getElementById('loadingSpinner')?.classList.add('active');
}

/* 로딩 숨기기 */
function hideLoading() {
    document.getElementById('loadingSpinner')?.classList.remove('active');
}
</script>

<?php
include_once(G5_PATH.'/tail.php');
?>