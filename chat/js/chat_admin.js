/*
 * 파일명: chat_admin.js
 * 위치: /chat/js/chat_admin.js
 * 기능: 채팅방 관리자 기능 스크립트
 * 작성일: 2025-07-13
 */

// ===================================
// 관리자 모달 관련
// ===================================

/* 관리자 모달 표시 */
window.showAdminModal = async function() {
    if (!chatState.currentRoomId || !chatState.currentRoomInfo) return;
    
    // 권한 확인
    const userRole = chatState.currentRoomInfo.user_role;
    if (!['owner', 'admin'].includes(userRole) && !isAdmin) {
        alert('관리자 권한이 필요합니다.');
        return;
    }
    
    // 현재 설정 로드
    await loadRoomSettings();
    await loadBannedUsers();
    await loadUserListForBan();
    
    document.getElementById('adminModal').style.display = 'block';
};

/* 관리자 모달 숨기기 */
window.hideAdminModal = function() {
    document.getElementById('adminModal').style.display = 'none';
};

// ===================================
// 공지사항 관리
// ===================================

/* 공지사항 설정 */
window.setNotice = async function() {
    const noticeContent = document.getElementById('noticeContent').value.trim();
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_notice',
                room_id: chatState.currentRoomId,
                notice_content: noticeContent
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert(noticeContent ? '공지사항이 설정되었습니다.' : '공지사항이 제거되었습니다.');
            // 공지사항 즉시 업데이트
            updateNoticeDisplay(noticeContent);
        } else {
            alert(data.message || '공지사항 설정에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error setting notice:', error);
        alert('오류가 발생했습니다.');
    } finally {
        hideLoading();
    }
};

/* 공지사항 표시 업데이트 */
function updateNoticeDisplay(content) {
    const noticeArea = document.querySelector('.notice-area');
    if (!noticeArea) return;
    
    if (content) {
        const noticeText = noticeArea.querySelector('.notice-text');
        noticeText.innerHTML = escapeHtml(content).replace(/\n/g, '<br>');
        noticeArea.classList.add('show');
    } else {
        noticeArea.classList.remove('show');
    }
}

// ===================================
// 사용자 차단 관리
// ===================================

/* 사용자 차단 */
window.banUser = async function() {
    const userId = document.getElementById('banUserId').value;
    const banType = document.getElementById('banType').value;
    const duration = document.getElementById('banDuration').value;
    const reason = document.getElementById('banReason').value.trim();
    
    if (!userId) {
        alert('차단할 사용자를 선택하세요.');
        return;
    }
    
    if (!confirm(`정말로 이 사용자를 ${banType === 'mute' ? '음소거' : '강퇴'}하시겠습니까?`)) {
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'ban_user',
                room_id: chatState.currentRoomId,
                target_mb_id: userId,
                ban_type: banType,
                duration: duration || null,
                reason: reason
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('사용자가 차단되었습니다.');
            // 차단 목록 새로고침
            await loadBannedUsers();
            // 입력 초기화
            document.getElementById('banUserId').value = '';
            document.getElementById('banDuration').value = '';
            document.getElementById('banReason').value = '';
        } else {
            alert(data.message || '사용자 차단에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error banning user:', error);
        alert('오류가 발생했습니다.');
    } finally {
        hideLoading();
    }
};

/* 빠른 차단 (프로필에서) */
window.quickBanUser = async function(mbId, mbNick) {
    hideUserProfile();
    
    const banType = prompt(`${mbNick}님을 어떻게 차단하시겠습니까?\n\n1. 음소거 (채팅 금지)\n2. 강퇴 (채팅방에서 퇴장)\n\n번호를 입력하세요:`, '1');
    
    if (!banType || !['1', '2'].includes(banType)) return;
    
    const type = banType === '1' ? 'mute' : 'kick';
    const duration = prompt('차단 시간을 입력하세요 (분 단위, 비워두면 영구차단):', '60');
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'ban_user',
                room_id: chatState.currentRoomId,
                target_mb_id: mbId,
                ban_type: type,
                duration: duration || null,
                reason: '관리자 조치'
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert(`${mbNick}님이 ${type === 'mute' ? '음소거' : '강퇴'}되었습니다.`);
        } else {
            alert(data.message || '차단에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error quick banning user:', error);
        alert('오류가 발생했습니다.');
    } finally {
        hideLoading();
    }
};

/* 차단 해제 */
window.unbanUser = async function(mbId) {
    if (!confirm('차단을 해제하시겠습니까?')) return;
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'unban_user',
                room_id: chatState.currentRoomId,
                target_mb_id: mbId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('차단이 해제되었습니다.');
            await loadBannedUsers();
        } else {
            alert(data.message || '차단 해제에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error unbanning user:', error);
        alert('오류가 발생했습니다.');
    } finally {
        hideLoading();
    }
};

/* 차단된 사용자 목록 로드 */
async function loadBannedUsers() {
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'get_banned_users',
                room_id: chatState.currentRoomId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            renderBannedUsers(data.users);
        }
    } catch (error) {
        console.error('Error loading banned users:', error);
    }
}

/* 차단된 사용자 목록 렌더링 */
function renderBannedUsers(users) {
    const bannedUserList = document.getElementById('bannedUserList');
    
    if (users.length === 0) {
        bannedUserList.innerHTML = '<p style="text-align: center; color: #999; margin-top: 20px;">차단된 사용자가 없습니다.</p>';
        return;
    }
    
    bannedUserList.innerHTML = '<h5 style="margin-top: 20px; margin-bottom: 12px;">차단된 사용자 목록</h5>' + 
        users.map(user => {
            const expireText = user.expire_at ? 
                `만료: ${new Date(user.expire_at).toLocaleString()}` : 
                '영구 차단';
            
            return `
                <div class="banned-user-item">
                    <div class="banned-user-info">
                        <div class="banned-user-nick">${escapeHtml(user.mb_nick)} (${user.mb_id})</div>
                        <div class="banned-user-detail">
                            ${user.ban_type === 'mute' ? '음소거' : '강퇴'} | ${expireText}
                            ${user.ban_reason ? ` | 사유: ${escapeHtml(user.ban_reason)}` : ''}
                        </div>
                    </div>
                    <button class="btn-unban" onclick="unbanUser('${user.mb_id}')">해제</button>
                </div>
            `;
        }).join('');
}

/* 차단 가능한 사용자 목록 로드 */
async function loadUserListForBan() {
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
            const select = document.getElementById('banUserId');
            select.innerHTML = '<option value="">사용자 선택</option>' +
                data.users
                    .filter(user => user.mb_id !== myId && user.role !== 'owner')
                    .map(user => `<option value="${user.mb_id}">${escapeHtml(user.mb_nick || user.member_nick)} (${user.mb_id})</option>`)
                    .join('');
        }
    } catch (error) {
        console.error('Error loading user list:', error);
    }
}

// ===================================
// 채팅방 설정 관리
// ===================================

/* 채팅방 설정 저장 */
window.saveRoomSettings = async function() {
    const readonlyMode = document.getElementById('readonlyMode').checked;
    const slowMode = parseInt(document.getElementById('slowMode').value) || 0;
    const minLevel = parseInt(document.getElementById('minLevel').value) || 1;
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_room_settings',
                room_id: chatState.currentRoomId,
                settings: {
                    is_readonly: readonlyMode ? 1 : 0,
                    slow_mode: slowMode,
                    min_level: minLevel
                }
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('설정이 저장되었습니다.');
            // 현재 채팅방 정보 업데이트
            chatState.currentRoomInfo.is_readonly = readonlyMode ? 1 : 0;
            chatState.currentRoomInfo.slow_mode = slowMode;
            chatState.currentRoomInfo.min_level = minLevel;
            
            // UI 업데이트
            updateRoomSettingsUI();
        } else {
            alert(data.message || '설정 저장에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        alert('오류가 발생했습니다.');
    } finally {
        hideLoading();
    }
};

/* 채팅방 설정 로드 */
async function loadRoomSettings() {
    if (!chatState.currentRoomInfo) return;
    
    document.getElementById('readonlyMode').checked = chatState.currentRoomInfo.is_readonly == 1;
    document.getElementById('slowMode').value = chatState.currentRoomInfo.slow_mode || 0;
    document.getElementById('minLevel').value = chatState.currentRoomInfo.min_level || 1;
}

/* 채팅방 설정 UI 업데이트 */
function updateRoomSettingsUI() {
    const inputArea = document.querySelector('.input-area');
    if (!inputArea) return;
    
    // 읽기 전용 모드 체크
    if (chatState.currentRoomInfo.is_readonly == 1) {
        const isOwnerOrAdmin = ['owner', 'admin'].includes(chatState.currentRoomInfo.user_role) || isAdmin;
        
        if (!isOwnerOrAdmin) {
            inputArea.innerHTML = '<div class="input-disabled-message">읽기 전용 모드입니다. 메시지를 보낼 수 없습니다.</div>';
            return;
        }
    }
    
    // 슬로우 모드 표시
    if (chatState.currentRoomInfo.slow_mode > 0) {
        const indicator = inputArea.querySelector('.slow-mode-indicator');
        if (!indicator) {
            const wrapper = inputArea.querySelector('.input-wrapper');
            if (wrapper) {
                const span = document.createElement('span');
                span.className = 'slow-mode-indicator';
                span.textContent = `슬로우 모드: ${chatState.currentRoomInfo.slow_mode}초`;
                wrapper.appendChild(span);
            }
        }
    }
}

// ===================================
// 메시지 삭제
// ===================================

/* 메시지 삭제 */
window.deleteMessage = async function(msgId) {
    if (!confirm('이 메시지를 삭제하시겠습니까?')) return;
    
    showLoading();
    
    try {
        const response = await fetch('./ajax/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_message',
                msg_id: msgId
            })
        });
        
        // 응답 텍스트 먼저 확인
        const responseText = await response.text();
        console.log('Delete response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON 파싱 오류:', responseText);
            
            // 500 에러인 경우 임시로 UI만 업데이트
            if (response.status === 500) {
                // 메시지 UI 업데이트 (임시)
                const msgElement = document.querySelector(`[data-msg-id="${msgId}"] .message-bubble`);
                if (msgElement) {
                    msgElement.classList.add('deleted');
                    msgElement.innerHTML = '<em style="color: #999;">삭제된 메시지입니다</em>';
                }
                alert('메시지가 삭제 처리되었습니다. (서버 오류로 인해 완전히 삭제되지 않을 수 있습니다)');
                return;
            }
            
            throw new Error('서버 응답을 처리할 수 없습니다.');
        }
        
        if (data.success) {
            // 메시지 UI 업데이트
            const msgElement = document.querySelector(`[data-msg-id="${msgId}"] .message-bubble`);
            if (msgElement) {
                msgElement.classList.add('deleted');
                msgElement.innerHTML = '<em style="color: #999;">삭제된 메시지입니다</em>';
            }
        } else {
            alert(data.message || '메시지 삭제에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error deleting message:', error);
        alert('오류가 발생했습니다: ' + error.message);
    } finally {
        hideLoading();
    }
};

// ===================================
// UI 업데이트 확장
// ===================================

/* 기존 함수 확장 - updateRoomUI */
if (typeof window.updateRoomUI === 'function') {
    const _originalUpdateRoomUI = window.updateRoomUI;
    window.updateRoomUI = function(roomName) {
        _originalUpdateRoomUI(roomName);
        
        // 관리자 버튼 추가
        setTimeout(() => {
            const headerRight = document.querySelector('.chat-header-right');
            if (headerRight && chatState.currentRoomInfo && 
                (['owner', 'admin'].includes(chatState.currentRoomInfo.user_role) || isAdmin)) {
                
                // 이미 관리 버튼이 있는지 확인
                if (!headerRight.querySelector('.btn-admin')) {
                    const adminBtn = document.createElement('button');
                    adminBtn.className = 'btn-header btn-admin';
                    adminBtn.innerHTML = '<i class="bi bi-gear"></i> 관리';
                    adminBtn.onclick = showAdminModal;
                    
                    // 사용자 목록 버튼 앞에 삽입
                    const usersBtn = headerRight.querySelector('.btn-users');
                    if (usersBtn) {
                        headerRight.insertBefore(adminBtn, usersBtn);
                    }
                }
            }
            
            // 공지사항 영역 추가
            const chatHeader = document.querySelector('.chat-header');
            if (chatHeader && !document.querySelector('.notice-area')) {
                const noticeArea = document.createElement('div');
                noticeArea.className = 'notice-area';
                noticeArea.innerHTML = `
                    <div class="notice-content">
                        <i class="bi bi-megaphone-fill notice-icon"></i>
                        <div>
                            <div class="notice-text"></div>
                            <div class="notice-meta"></div>
                        </div>
                    </div>
                `;
                chatHeader.parentNode.insertBefore(noticeArea, chatHeader.nextSibling);
            }
        }, 100);
    };
}

/* 기존 함수 확장 - createMessageElement */
if (typeof window.createMessageElement === 'function') {
    const _originalCreateMessageElement = window.createMessageElement;
    window.createMessageElement = function(msg) {
        // 시스템 메시지 처리를 먼저
        if (msg.mb_id === 'system' || msg.msg_type === 'system') {
            const messageItem = document.createElement('div');
            messageItem.className = 'message-item system-message';
            messageItem.innerHTML = `<div class="system-message-content">${escapeHtml(msg.message)}</div>`;
            return messageItem;
        }
        
        // 일반 메시지 생성
        const messageItem = _originalCreateMessageElement(msg);
        
        // 삭제된 메시지 처리 (is_deleted가 1이거나 '1'인 경우만)
        if (msg.is_deleted && (msg.is_deleted == 1 || msg.is_deleted === '1')) {
            const bubble = messageItem.querySelector('.message-bubble');
            if (bubble) {
                bubble.classList.add('deleted');
                bubble.innerHTML = '<em style="color: #999;">삭제된 메시지입니다</em>';
            }
            return messageItem;
        }
        
        // 관리자 메시지 삭제 버튼 추가
        if (chatState.currentRoomInfo && 
            (['owner', 'admin'].includes(chatState.currentRoomInfo.user_role) || isAdmin)) {
            
            const actions = messageItem.querySelector('.message-actions');
            if (actions && !msg.is_temp) {
                // 이미 삭제 버튼이 있는지 확인
                if (!actions.querySelector('.delete')) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'btn-message-action delete';
                    deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                    deleteBtn.onclick = () => deleteMessage(msg.msg_id);
                    actions.appendChild(deleteBtn);
                }
            }
        }
        
        return messageItem;
    };
}

/* 기존 함수 확장 - enterRoom */
if (typeof window.enterRoom === 'function') {
    const _originalEnterRoom = window.enterRoom;
    window.enterRoom = async function(roomId, roomName, element) {
        await _originalEnterRoom(roomId, roomName, element);
        
        // 공지사항 로드
        setTimeout(async () => {
            try {
                const response = await fetch('./ajax/chat_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_notice',
                        room_id: roomId
                    })
                });
                
                const data = await response.json();
                if (data.success && data.notice) {
                    updateNoticeDisplay(data.notice.notice_content);
                    
                    // 공지사항 메타 정보 업데이트
                    const noticeMeta = document.querySelector('.notice-meta');
                    if (noticeMeta) {
                        const date = new Date(data.notice.created_at).toLocaleString();
                        noticeMeta.textContent = `${data.notice.mb_nick}님이 ${date}에 작성`;
                    }
                }
            } catch (error) {
                console.error('Error loading notice:', error);
            }
            
            // 채팅방 설정 UI 업데이트
            updateRoomSettingsUI();
        }, 500);
    };
}

/* 기존 함수 확장 - sendMessage */
if (typeof window.sendMessage === 'function') {
    const _originalSendMessage = window.sendMessage;
    window.sendMessage = async function(e) {
        e.preventDefault();
        
        // 차단 상태 확인
        if (chatState.userBanStatus) {
            if (chatState.userBanStatus.ban_type === 'mute') {
                alert('음소거 상태입니다. 메시지를 보낼 수 없습니다.');
                return;
            }
        }
        
        // 읽기 전용 모드 확인
        if (chatState.currentRoomInfo && chatState.currentRoomInfo.is_readonly == 1) {
            const isOwnerOrAdmin = ['owner', 'admin'].includes(chatState.currentRoomInfo.user_role) || isAdmin;
            if (!isOwnerOrAdmin) {
                alert('읽기 전용 모드입니다. 메시지를 보낼 수 없습니다.');
                return;
            }
        }
        
        // 슬로우 모드 확인
        if (chatState.currentRoomInfo && chatState.currentRoomInfo.slow_mode > 0) {
            const now = Date.now();
            const lastSent = chatState.lastMessageSent || 0;
            const diff = (now - lastSent) / 1000;
            
            if (diff < chatState.currentRoomInfo.slow_mode) {
                const remaining = Math.ceil(chatState.currentRoomInfo.slow_mode - diff);
                alert(`슬로우 모드: ${remaining}초 후에 메시지를 보낼 수 있습니다.`);
                return;
            }
            
            chatState.lastMessageSent = now;
        }
        
        // 원래 sendMessage 함수 호출
        await _originalSendMessage(e);
    };
}

// 초기화 시 차단 상태 확인
document.addEventListener('DOMContentLoaded', function() {
    // 주기적으로 차단 상태 확인 (30초마다)
    setInterval(async () => {
        if (chatState.currentRoomId) {
            try {
                const response = await fetch('./ajax/chat_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'check_ban_status',
                        room_id: chatState.currentRoomId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    chatState.userBanStatus = data.ban_status;
                    
                    // 강퇴된 경우 채팅방에서 나가기
                    if (data.ban_status && data.ban_status.ban_type === 'kick') {
                        alert('채팅방에서 강퇴되었습니다.');
                        if (typeof leaveRoom === 'function') {
                            leaveRoom();
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking ban status:', error);
            }
        }
    }, 30000);
});