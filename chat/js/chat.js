/*
 * 파일명: chat.js
 * 위치: /chat/js/chat.js
 * 기능: 개선된 실시간 채팅 스크립트
 * 작성일: 2025-07-13
 * 수정일: 2025-07-13
 */

// ===================================
// 전역 변수 및 설정
// ===================================
const CHAT_CONFIG = {
    POLLING_INTERVAL: 500,      // 고정 0.5초 폴링
    HEARTBEAT_INTERVAL: 10000,  // 10초마다 하트비트
    USER_UPDATE_INTERVAL: 5000, // 5초마다 사용자 목록 업데이트
    MESSAGE_BATCH_SIZE: 50,     // 한 번에 가져올 메시지 수
    RETRY_DELAY: 1000,         // 재시도 지연 시간
    MAX_RETRIES: 3            // 최대 재시도 횟수
};

// 전역 상태 관리
const chatState = {
    currentRoomId: null,
    currentRoomInfo: null,
    lastMsgId: 0,
    messageQueue: new Map(), // 메시지 중복 방지용
    isPolling: false,
    pollingTimer: null,
    heartbeatTimer: null,
    userUpdateTimer: null,
    roomListTimer: null,
    replyToMsgId: null,
    replyToNick: null,
    retryCount: 0
};

// ===================================
// 유틸리티 함수
// ===================================

/* HTML 이스케이프 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* 메시지 처리 */
function processMessage(message) {
    // @멘션 처리
    message = message.replace(/@(\S+)/g, '<span class="mention" onclick="mentionClick(\'$1\')">@$1</span>');
    
    // HTML 이스케이프 후 멘션 태그 복원
    return escapeHtml(message).replace(/&lt;span class="mention".*?&gt;(.*?)&lt;\/span&gt;/g, (match, p1) => {
        return match.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"');
    });
}

/* 재시도 기능이 있는 fetch */
async function fetchWithRetry(url, options, retries = CHAT_CONFIG.MAX_RETRIES) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error('Network response was not ok');
        return response;
    } catch (error) {
        if (retries > 0) {
            await new Promise(resolve => setTimeout(resolve, CHAT_CONFIG.RETRY_DELAY));
            return fetchWithRetry(url, options, retries - 1);
        }
        throw error;
    }
}

// ===================================
// 채팅방 목록 관리
// ===================================

/* 채팅방 목록 로드 */
async function loadRoomList() {
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_room_list'
            })
        });
        
        const data = await response.json();
        if (data.success) {
            renderRoomList(data.rooms);
        }
    } catch (error) {
        console.error('Error loading room list:', error);
    }
}

/* 채팅방 목록 렌더링 */
function renderRoomList(rooms) {
    const roomList = document.getElementById('roomList');
    if (!roomList) return;
    
    if (rooms.length === 0) {
        roomList.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-chat-square-dots"></i>
                <p>채팅방이 없습니다</p>
            </div>
        `;
        return;
    }
    
    roomList.innerHTML = rooms.map(room => {
        const isActive = room.room_id == chatState.currentRoomId;
        const lastTime = room.last_message_time ? 
            new Date(room.last_message_time).toLocaleTimeString('ko-KR', {
                hour: '2-digit',
                minute: '2-digit'
            }) : '메시지 없음';
        
        return `
            <div class="room-item ${isActive ? 'active' : ''}" 
                 onclick="enterRoom(${room.room_id}, '${escapeHtml(room.room_name).replace(/'/g, "\\'")}', this)">
                <div class="room-name">
                    <i class="bi bi-hash"></i>
                    ${escapeHtml(room.room_name)}
                </div>
                <div class="room-meta">
                    <span><i class="bi bi-people"></i> ${room.user_count}</span>
                    <span><i class="bi bi-clock"></i> ${lastTime}</span>
                </div>
            </div>
        `;
    }).join('');
}

// ===================================
// 채팅방 관리
// ===================================

/* 채팅방 UI 업데이트 */
function updateRoomUI(roomName) {
    document.getElementById('welcomeScreen').style.display = 'none';
    document.getElementById('chatRoom').style.display = 'flex';
    document.getElementById('chatRoom').innerHTML = `
        <div class="chat-header">
            <div class="chat-header-left">
                <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <h3 class="chat-title">${escapeHtml(roomName)}</h3>
            </div>
            <div class="chat-header-right">
                <button class="btn-users" onclick="showUserList()">
                    <i class="bi bi-people"></i> <span id="userCount">0</span>
                </button>
                <button class="btn-leave-room" onclick="leaveRoom()">나가기</button>
            </div>
        </div>
        <div class="message-area" id="messageArea">
            <!-- 메시지가 여기에 표시됩니다 -->
        </div>
        <div class="reply-indicator" id="replyIndicator"></div>
        <div class="input-area">
            <form class="input-form" id="messageForm" onsubmit="sendMessage(event)">
                <div class="input-wrapper">
                    <input type="text" id="messageInput" placeholder="메시지를 입력하세요..." 
                           autocomplete="off" maxlength="500">
                </div>
                <button type="submit" class="btn-send" id="btnSend">
                    <i class="bi bi-send"></i>
                </button>
            </form>
        </div>
    `;
    
    // 채팅방 스타일 설정
    document.getElementById('chatRoom').style.cssText = 'flex: 1; display: flex; flex-direction: column;';
}

/* 채팅방 정보 가져오기 */
async function getRoomInfo(roomId) {
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_room_info',
                room_id: roomId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            chatState.currentRoomInfo = data.room;
        }
    } catch (error) {
        console.error('Error getting room info:', error);
    }
}

/* 채팅방 입장 */
async function enterRoom(roomId, roomName, element) {
    // 이전 채팅방 정리
    if (chatState.currentRoomId && chatState.currentRoomId !== roomId) {
        await leaveCurrentRoom();
    }
    
    chatState.currentRoomId = roomId;
    chatState.lastMsgId = 0;
    chatState.messageQueue.clear();
    chatState.retryCount = 0;
    
    showLoading();
    
    // UI 업데이트
    updateRoomUI(roomName);
    
    // 활성 채팅방 표시
    document.querySelectorAll('.room-item').forEach(item => {
        item.classList.remove('active');
    });
    if (element) element.classList.add('active');
    
    try {
        // 채팅방 입장
        const response = await fetchWithRetry('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'join_room',
                room_id: roomId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 채팅방 정보 가져오기
            await getRoomInfo(roomId);
            
            // 실시간 폴링 시작
            startRealTimePolling();
            
            // 입력창 포커스
            document.getElementById('messageInput')?.focus();
        }
    } catch (error) {
        console.error('Error joining room:', error);
        alert('채팅방 입장에 실패했습니다.');
    } finally {
        hideLoading();
    }
}

/* 현재 채팅방 나가기 */
async function leaveCurrentRoom() {
    if (!chatState.currentRoomId) return;
    
    // 타이머 정리
    cleanupRoom();
    
    try {
        await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'leave_room',
                room_id: chatState.currentRoomId
            })
        });
    } catch (error) {
        console.error('Error leaving room:', error);
    }
    
    chatState.currentRoomId = null;
    chatState.currentRoomInfo = null;
}

/* 채팅방 정리 */
function cleanupRoom() {
    // 모든 타이머 정리
    if (chatState.pollingTimer) {
        clearInterval(chatState.pollingTimer);
        chatState.pollingTimer = null;
    }
    if (chatState.heartbeatTimer) {
        clearInterval(chatState.heartbeatTimer);
        chatState.heartbeatTimer = null;
    }
    if (chatState.userUpdateTimer) {
        clearInterval(chatState.userUpdateTimer);
        chatState.userUpdateTimer = null;
    }
    
    // 채팅방 나가기 알림
    if (chatState.currentRoomId) {
        navigator.sendBeacon('./ajax/chat_handler.php', JSON.stringify({
            action: 'leave_room',
            room_id: chatState.currentRoomId
        }));
    }
}

/* 재연결 */
async function reconnectToRoom() {
    if (!chatState.currentRoomId) return;
    
    console.log('Attempting to reconnect...');
    const roomId = chatState.currentRoomId;
    const roomName = document.querySelector('.chat-title')?.textContent || '채팅방';
    
    // 상태 초기화
    chatState.retryCount = 0;
    
    // 재입장 시도
    await enterRoom(roomId, roomName, null);
}

// ===================================
// 실시간 폴링
// ===================================

/* 실시간 폴링 시작 */
function startRealTimePolling() {
    // 기존 타이머 정리
    if (chatState.pollingTimer) clearInterval(chatState.pollingTimer);
    if (chatState.heartbeatTimer) clearInterval(chatState.heartbeatTimer);
    if (chatState.userUpdateTimer) clearInterval(chatState.userUpdateTimer);
    
    // 초기 데이터 로드
    getMessages();
    updateUserCount();
    
    // 고정 간격 폴링 (0.5초)
    chatState.pollingTimer = setInterval(() => {
        if (!chatState.isPolling) {
            getMessages();
        }
    }, CHAT_CONFIG.POLLING_INTERVAL);
    
    // 하트비트
    chatState.heartbeatTimer = setInterval(sendHeartbeat, CHAT_CONFIG.HEARTBEAT_INTERVAL);
    
    // 사용자 목록 업데이트
    chatState.userUpdateTimer = setInterval(updateUserCount, CHAT_CONFIG.USER_UPDATE_INTERVAL);
}

/* 하트비트 전송 */
async function sendHeartbeat() {
    if (!chatState.currentRoomId) return;
    
    try {
        await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'heartbeat',
                room_id: chatState.currentRoomId
            })
        });
    } catch (error) {
        console.error('Heartbeat error:', error);
    }
}

// ===================================
// 메시지 처리
// ===================================

/* 메시지 가져오기 */
async function getMessages() {
    if (!chatState.currentRoomId || chatState.isPolling) return;
    
    chatState.isPolling = true;
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_messages',
                room_id: chatState.currentRoomId,
                last_msg_id: chatState.lastMsgId
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.messages?.length > 0) {
            // 새 메시지 처리
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
            
            // 연속 실패 카운트 리셋
            chatState.retryCount = 0;
        }
    } catch (error) {
        console.error('Error getting messages:', error);
        chatState.retryCount++;
        
        // 연속 실패 시 재연결 시도
        if (chatState.retryCount > CHAT_CONFIG.MAX_RETRIES) {
            console.log('Reconnecting...');
            await reconnectToRoom();
        }
    } finally {
        chatState.isPolling = false;
    }
}

/* 메시지 전송 */
async function sendMessage(e) {
    e.preventDefault();
    
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    const btnSend = document.getElementById('btnSend');
    
    if (!message || !chatState.currentRoomId) return;
    
    // 메시지 데이터
    const messageData = {
        action: 'send_message',
        room_id: chatState.currentRoomId,
        message: message
    };
    
    if (chatState.replyToMsgId) {
        messageData.reply_to = chatState.replyToMsgId;
    }
    
    // UI 즉시 업데이트
    input.value = '';
    btnSend.disabled = true;
    
    // Optimistic UI - 즉시 표시
    const tempId = `temp_${Date.now()}`;
    const tempMsg = {
        msg_id: tempId,
        mb_id: myId,
        mb_nick: myNick,
        message: message,
        created_at: new Date().toISOString(),
        mb_level: typeof myLevel !== 'undefined' ? myLevel : '1',
        reply_to_msg_id: chatState.replyToMsgId,
        reply_to_nick: chatState.replyToNick,
        is_temp: true
    };
    
    // 임시 메시지 표시
    addMessage(tempMsg);
    chatState.messageQueue.set(tempId, true);
    
    // 답글 모드 취소
    cancelReply();
    
    try {
        const response = await fetchWithRetry('./ajax/chat_handler.php', {
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

/* 메시지 추가 */
function addMessage(msg) {
    const messageArea = document.getElementById('messageArea');
    if (!messageArea) return;
    
    // 이미 표시된 메시지인지 확인
    if (!msg.is_temp && document.querySelector(`[data-msg-id="${msg.msg_id}"]`)) {
        return;
    }
    
    const messageItem = createMessageElement(msg);
    
    // 스크롤 위치 확인
    const isScrolledToBottom = messageArea.scrollHeight - messageArea.scrollTop <= messageArea.clientHeight + 100;
    
    messageArea.appendChild(messageItem);
    
    // 자동 스크롤
    if (isScrolledToBottom || msg.mb_id === myId) {
        messageArea.scrollTop = messageArea.scrollHeight;
    }
}

/* 메시지 엘리먼트 생성 */
function createMessageElement(msg) {
    const messageItem = document.createElement('div');
    const isMyMessage = msg.mb_id === myId;
    messageItem.className = 'message-item' + (isMyMessage ? ' my-message' : '');
    if (msg.is_temp) messageItem.className += ' temp-message';
    messageItem.setAttribute('data-msg-id', msg.msg_id);
    
    const firstChar = msg.mb_nick.charAt(0).toUpperCase();
    const time = new Date(msg.created_at).toLocaleTimeString('ko-KR', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const userLevel = msg.mb_level || '1';
    
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
        <div class="message-avatar" onclick="showUserProfile('${msg.mb_id}', '${escapeHtml(msg.mb_nick)}', '${userLevel}')">${firstChar}</div>
        <div class="message-content">
            <div class="message-info">
                <span class="message-user-info" onclick="showUserProfile('${msg.mb_id}', '${escapeHtml(msg.mb_nick)}', '${userLevel}')">
                    <span class="message-level">Lv.${userLevel}</span>
                    <span class="message-nick">${escapeHtml(msg.mb_nick)}</span>
                </span>
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
                    <button class="btn-message-action" onclick="mentionUser('${msg.mb_id}', '${escapeHtml(msg.mb_nick)}')">
                        <i class="bi bi-at"></i>
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

// ===================================
// 사용자 관련
// ===================================

/* 사용자 수 업데이트 */
async function updateUserCount() {
    if (!chatState.currentRoomId) return;
    
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
            const countElement = document.getElementById('userCount');
            if (countElement) {
                countElement.textContent = data.users.length;
            }
        }
    } catch (error) {
        console.error('Error updating user count:', error);
    }
}

// ===================================
// 초기화 및 이벤트 리스너
// ===================================

/* 이벤트 리스너 설정 */
function setupEventListeners() {
    // 메시지 입력 이벤트
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey && e.target.id === 'messageInput') {
            e.preventDefault();
            sendMessage(e);
        }
    });
    
    // 페이지 벗어날 때
    window.addEventListener('beforeunload', function() {
        if (chatState.currentRoomId) {
            cleanupRoom();
        }
    });
    
    // 페이지 포커스 이벤트
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && chatState.currentRoomId) {
            // 페이지가 다시 활성화되면 즉시 메시지 체크
            getMessages();
        }
    });
}

/* 채팅 초기화 */
function initChat() {
    loadRoomList();
    
    // 채팅방 목록 주기적 업데이트
    chatState.roomListTimer = setInterval(loadRoomList, 5000);
    
    // 이벤트 리스너 등록
    setupEventListeners();
}

// ===================================
// 전역 함수 (HTML에서 호출)
// ===================================

/* 답글 기능 */
window.replyToMessage = function(msgId, nick) {
    chatState.replyToMsgId = msgId;
    chatState.replyToNick = nick;
    
    // 답글 표시 UI 업데이트
    const replyIndicator = document.getElementById('replyIndicator');
    if (replyIndicator) {
        replyIndicator.innerHTML = `
            <div class="reply-text">
                <i class="bi bi-reply"></i> ${escapeHtml(nick)}님에게 답글
            </div>
            <button class="btn-cancel-reply" onclick="cancelReply()">
                <i class="bi bi-x"></i>
            </button>
        `;
        replyIndicator.classList.add('show');
    }
    
    document.getElementById('messageInput')?.focus();
};

/* 답글 취소 */
window.cancelReply = function() {
    chatState.replyToMsgId = null;
    chatState.replyToNick = null;
    
    const replyIndicator = document.getElementById('replyIndicator');
    if (replyIndicator) {
        replyIndicator.classList.remove('show');
    }
};

/* 로딩 표시/숨기기 */
window.showLoading = function() {
    document.getElementById('loadingSpinner')?.classList.add('active');
};

window.hideLoading = function() {
    document.getElementById('loadingSpinner')?.classList.remove('active');
};

/* 전역 함수로 노출 */
window.enterRoom = enterRoom;
window.sendMessage = sendMessage;

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', initChat);