<?php
// Chat Widget Include
// This can be included in any page to add chat functionality

// Check if user is logged in
$chat_user = null;
if (isset($_SESSION['admin_id'])) {
    $chat_user = [
        'id' => $_SESSION['admin_id'],
        'type' => 'admin',
        'name' => $_SESSION['admin_name'] ?? 'Admin'
    ];
} elseif (isset($_SESSION['technician_id'])) {
    $chat_user = [
        'id' => $_SESSION['technician_id'],
        'type' => 'technician', 
        'name' => $_SESSION['technician_name'] ?? 'Technician'
    ];
} elseif (isset($_SESSION['user_id'])) {
    $chat_user = [
        'id' => $_SESSION['user_id'],
        'type' => 'customer',
        'name' => $_SESSION['user_name'] ?? 'Customer'
    ];
}

// Only show chat if user is logged in
if ($chat_user):
?>

<!-- Chat Widget -->
<div id="chat-widget" class="chat-widget">
    <!-- Chat Toggle Button -->
    <div id="chat-toggle" class="chat-toggle">
        <i class="fas fa-comments"></i>
        <span id="chat-unread-badge" class="chat-unread-badge" style="display: none;">0</span>
    </div>
    
    <!-- Chat Window -->
    <div id="chat-window" class="chat-window" style="display: none;">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-info">
                <h4 id="chat-title">SmartFix Support</h4>
                <span id="chat-status" class="chat-status">Online</span>
            </div>
            <div class="chat-header-actions">
                <button id="chat-minimize" class="chat-btn">
                    <i class="fas fa-minus"></i>
                </button>
                <button id="chat-close" class="chat-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Chat Rooms List -->
        <div id="chat-rooms" class="chat-rooms" style="display: block;">
            <div class="chat-rooms-header">
                <h5>Your Conversations</h5>
                <?php if ($chat_user['type'] === 'admin'): ?>
                <button id="new-chat-btn" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> New Chat
                </button>
                <?php endif; ?>
            </div>
            <div id="chat-rooms-list" class="chat-rooms-list">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Loading chats...
                </div>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div id="chat-messages-container" class="chat-messages-container" style="display: none;">
            <div class="chat-messages-header">
                <button id="back-to-rooms" class="chat-btn">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="chat-info">
                    <h5 id="current-chat-name">Chat</h5>
                    <span id="current-chat-participants" class="chat-participants"></span>
                </div>
            </div>
            <div id="chat-messages" class="chat-messages">
                <!-- Messages will be loaded here -->
            </div>
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <input type="text" id="chat-message-input" placeholder="Type a message..." maxlength="1000">
                    <button id="chat-send-btn" class="chat-send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="chat-actions">
                    <label for="chat-file-input" class="chat-file-btn" title="Attach file">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" id="chat-file-input" style="display: none;" accept="image/*,.pdf,.doc,.docx,.txt">
                    </label>
                </div>
            </div>
        </div>
        
        <!-- New Chat Modal (Admin only) -->
        <?php if ($chat_user['type'] === 'admin'): ?>
        <div id="new-chat-modal" class="chat-modal" style="display: none;">
            <div class="chat-modal-content">
                <div class="chat-modal-header">
                    <h5>Create New Chat</h5>
                    <button id="close-new-chat-modal" class="chat-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chat-modal-body">
                    <div class="form-group">
                        <label>Chat Type:</label>
                        <select id="new-chat-type">
                            <option value="general">General Support</option>
                            <option value="service_request">Service Request</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Chat Name:</label>
                        <input type="text" id="new-chat-name" placeholder="Enter chat name">
                    </div>
                    <div class="form-group" id="service-request-group" style="display: none;">
                        <label>Service Request ID:</label>
                        <input type="number" id="new-chat-service-id" placeholder="Enter service request ID">
                    </div>
                </div>
                <div class="chat-modal-footer">
                    <button id="create-new-chat" class="btn btn-primary">Create Chat</button>
                    <button id="cancel-new-chat" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chat Widget Styles -->
<style>
.chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.chat-toggle {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
    position: relative;
}

.chat-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0, 123, 255, 0.4);
}

.chat-toggle i {
    font-size: 24px;
}

.chat-unread-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
}

.chat-window {
    width: 400px;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    position: absolute;
    bottom: 80px;
    right: 0;
    overflow: hidden;
}

.chat-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header-info h4 {
    margin: 0;
    font-size: 16px;
}

.chat-status {
    font-size: 12px;
    opacity: 0.8;
}

.chat-header-actions {
    display: flex;
    gap: 10px;
}

.chat-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.chat-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.chat-rooms {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-rooms-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-rooms-header h5 {
    margin: 0;
    font-size: 14px;
    color: #495057;
}

.chat-rooms-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.chat-room-item {
    padding: 12px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
    border-bottom: 1px solid #f8f9fa;
    position: relative;
}

.chat-room-item:hover {
    background: #f8f9fa;
}

.chat-room-item.active {
    background: #e3f2fd;
    border-left: 3px solid #007bff;
}

.chat-room-info h6 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #212529;
}

.chat-room-last-message {
    font-size: 12px;
    color: #6c757d;
    margin: 0;
}

.chat-room-time {
    font-size: 11px;
    color: #adb5bd;
    position: absolute;
    top: 12px;
    right: 15px;
}

.chat-room-unread {
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    background: #dc3545;
    color: white;
    border-radius: 50%;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
}

.chat-messages-container {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-messages-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 15px;
}

.chat-info h5 {
    margin: 0;
    font-size: 14px;
    color: #212529;
}

.chat-participants {
    font-size: 12px;
    color: #6c757d;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-message {
    display: flex;
    margin-bottom: 10px;
}

.chat-message.own {
    justify-content: flex-end;
}

.chat-message-bubble {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 15px;
    font-size: 14px;
    line-height: 1.4;
}

.chat-message.own .chat-message-bubble {
    background: #007bff;
    color: white;
    border-bottom-right-radius: 5px;
}

.chat-message:not(.own) .chat-message-bubble {
    background: #f8f9fa;
    color: #212529;
    border-bottom-left-radius: 5px;
}

.chat-message.system .chat-message-bubble {
    background: #e3f2fd;
    color: #1976d2;
    font-style: italic;
    text-align: center;
    max-width: 100%;
}

.chat-message-info {
    font-size: 11px;
    margin-top: 5px;
    opacity: 0.7;
}

.chat-message.own .chat-message-info {
    text-align: right;
}

.chat-input-container {
    padding: 15px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.chat-input-wrapper {
    display: flex;
    gap: 10px;
    align-items: center;
}

#chat-message-input {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 25px;
    font-size: 14px;
    outline: none;
}

#chat-message-input:focus {
    border-color: #007bff;
}

.chat-send-btn {
    width: 40px;
    height: 40px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.chat-send-btn:hover {
    background: #0056b3;
}

.chat-actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.chat-file-btn {
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: color 0.2s;
}

.chat-file-btn:hover {
    color: #007bff;
}

.loading-spinner {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

.loading-spinner i {
    margin-right: 10px;
}

/* Modal Styles */
.chat-modal {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.chat-modal-content {
    background: white;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    overflow: hidden;
}

.chat-modal-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-modal-header h5 {
    margin: 0;
    font-size: 16px;
}

.chat-modal-body {
    padding: 20px;
}

.chat-modal-body .form-group {
    margin-bottom: 15px;
}

.chat-modal-body label {
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
    font-weight: 500;
}

.chat-modal-body input,
.chat-modal-body select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.chat-modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .chat-window {
        width: 100vw;
        height: 100vh;
        bottom: 0;
        right: 0;
        border-radius: 0;
        position: fixed;
    }
    
    .chat-toggle {
        width: 50px;
        height: 50px;
    }
    
    .chat-toggle i {
        font-size: 20px;
    }
}
</style>

<!-- Chat Widget JavaScript -->
<script>
class SmartFixChat {
    constructor() {
        this.currentUser = <?php echo json_encode($chat_user); ?>;
        this.currentRoom = null;
        this.refreshInterval = null;
        this.lastMessageId = 0;
        this.unreadCount = 0;
        
        this.initializeEventListeners();
        this.loadChatRooms();
        this.startAutoRefresh();
        this.updateUnreadCount();
    }
    
    initializeEventListeners() {
        // Chat toggle
        document.getElementById('chat-toggle').addEventListener('click', () => {
            this.toggleChat();
        });
        
        // Close/minimize chat
        document.getElementById('chat-close').addEventListener('click', () => {
            this.closeChat();
        });
        
        document.getElementById('chat-minimize').addEventListener('click', () => {
            this.minimizeChat();
        });
        
        // Back to rooms
        document.getElementById('back-to-rooms').addEventListener('click', () => {
            this.showRoomsList();
        });
        
        // Send message
        document.getElementById('chat-send-btn').addEventListener('click', () => {
            this.sendMessage();
        });
        
        document.getElementById('chat-message-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
        
        // New chat modal (Admin only)
        <?php if ($chat_user['type'] === 'admin'): ?>
        document.getElementById('new-chat-btn').addEventListener('click', () => {
            this.showNewChatModal();
        });
        
        document.getElementById('close-new-chat-modal').addEventListener('click', () => {
            this.hideNewChatModal();
        });
        
        document.getElementById('cancel-new-chat').addEventListener('click', () => {
            this.hideNewChatModal();
        });
        
        document.getElementById('create-new-chat').addEventListener('click', () => {
            this.createNewChat();
        });
        
        document.getElementById('new-chat-type').addEventListener('change', (e) => {
            const serviceGroup = document.getElementById('service-request-group');
            serviceGroup.style.display = e.target.value === 'service_request' ? 'block' : 'none';
        });
        <?php endif; ?>
        
        // File upload
        document.getElementById('chat-file-input').addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.uploadFile(e.target.files[0]);
            }
        });
    }
    
    toggleChat() {
        const chatWindow = document.getElementById('chat-window');
        if (chatWindow.style.display === 'none') {
            chatWindow.style.display = 'flex';
            this.loadChatRooms();
        } else {
            chatWindow.style.display = 'none';
        }
    }
    
    closeChat() {
        document.getElementById('chat-window').style.display = 'none';
    }
    
    minimizeChat() {
        document.getElementById('chat-window').style.display = 'none';
    }
    
    showRoomsList() {
        document.getElementById('chat-rooms').style.display = 'block';
        document.getElementById('chat-messages-container').style.display = 'none';
        this.currentRoom = null;
    }
    
    showMessagesContainer() {
        document.getElementById('chat-rooms').style.display = 'none';
        document.getElementById('chat-messages-container').style.display = 'flex';
    }
    
    async loadChatRooms() {
        try {
            const response = await fetch('/smartfix/api/chat.php?action=rooms');
            const data = await response.json();
            
            if (data.success) {
                this.renderChatRooms(data.data);
            } else {
                console.error('Failed to load chat rooms:', data.message);
            }
        } catch (error) {
            console.error('Error loading chat rooms:', error);
        }
    }
    
    renderChatRooms(rooms) {
        const roomsList = document.getElementById('chat-rooms-list');
        
        if (rooms.length === 0) {
            roomsList.innerHTML = '<div class="loading-spinner">No conversations yet</div>';
            return;
        }
        
        roomsList.innerHTML = rooms.map(room => `
            <div class="chat-room-item" data-room-id="${room.room_id}" onclick="chat.openRoom('${room.room_id}')">
                <div class="chat-room-info">
                    <h6>${this.escapeHtml(room.room_name)}</h6>
                    <p class="chat-room-last-message">${this.escapeHtml(room.last_message || 'No messages yet')}</p>
                </div>
                ${room.last_message_time ? `<div class="chat-room-time">${this.formatTime(room.last_message_time)}</div>` : ''}
                ${room.unread_count > 0 ? `<div class="chat-room-unread">${room.unread_count}</div>` : ''}
            </div>
        `).join('');
    }
    
    async openRoom(roomId) {
        this.currentRoom = roomId;
        this.showMessagesContainer();
        
        // Update room name
        const roomItem = document.querySelector(`[data-room-id="${roomId}"]`);
        if (roomItem) {
            const roomName = roomItem.querySelector('h6').textContent;
            document.getElementById('current-chat-name').textContent = roomName;
        }
        
        // Load messages
        await this.loadMessages(roomId);
        
        // Mark messages as read
        this.markMessagesAsRead(roomId);
    }
    
    async loadMessages(roomId) {
        try {
            const response = await fetch(`/smartfix/api/chat.php?action=messages&room_id=${roomId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderMessages(data.data);
                this.scrollToBottom();
            } else {
                console.error('Failed to load messages:', data.message);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }
    
    renderMessages(messages) {
        const messagesContainer = document.getElementById('chat-messages');
        
        messagesContainer.innerHTML = messages.map(message => {
            const isOwn = message.sender_id == this.currentUser.id && message.sender_type === this.currentUser.type;
            const isSystem = message.message_type === 'system';
            
            return `
                <div class="chat-message ${isOwn ? 'own' : ''} ${isSystem ? 'system' : ''}">
                    <div class="chat-message-bubble">
                        ${this.escapeHtml(message.message)}
                        ${!isOwn && !isSystem ? `<div class="chat-message-info">${this.escapeHtml(message.sender_name)}</div>` : ''}
                        <div class="chat-message-info">${this.formatTime(message.sent_at)}</div>
                    </div>
                </div>
            `;
        }).join('');
        
        if (messages.length > 0) {
            this.lastMessageId = Math.max(...messages.map(m => m.id));
        }
    }
    
    async sendMessage() {
        const input = document.getElementById('chat-message-input');
        const message = input.value.trim();
        
        if (!message || !this.currentRoom) return;
        
        try {
            const response = await fetch('/smartfix/api/chat.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    room_id: this.currentRoom,
                    message: message
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                input.value = '';
                this.loadMessages(this.currentRoom);
            } else {
                alert('Failed to send message: ' + data.message);
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message');
        }
    }
    
    async markMessagesAsRead(roomId) {
        try {
            await fetch('/smartfix/api/chat.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    room_id: roomId
                })
            });
            
            // Update UI
            this.updateUnreadCount();
            this.loadChatRooms();
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    }
    
    async updateUnreadCount() {
        try {
            const response = await fetch('/smartfix/api/chat.php?action=unread_count');
            const data = await response.json();
            
            if (data.success) {
                this.unreadCount = data.data.unread_count;
                const badge = document.getElementById('chat-unread-badge');
                
                if (this.unreadCount > 0) {
                    badge.textContent = this.unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Error updating unread count:', error);
        }
    }
    
    <?php if ($chat_user['type'] === 'admin'): ?>
    showNewChatModal() {
        document.getElementById('new-chat-modal').style.display = 'flex';
    }
    
    hideNewChatModal() {
        document.getElementById('new-chat-modal').style.display = 'none';
        // Clear form
        document.getElementById('new-chat-name').value = '';
        document.getElementById('new-chat-service-id').value = '';
        document.getElementById('new-chat-type').value = 'general';
        document.getElementById('service-request-group').style.display = 'none';
    }
    
    async createNewChat() {
        const roomType = document.getElementById('new-chat-type').value;
        const roomName = document.getElementById('new-chat-name').value.trim();
        const serviceId = document.getElementById('new-chat-service-id').value;
        
        if (!roomName) {
            alert('Please enter a chat name');
            return;
        }
        
        try {
            const response = await fetch('/smartfix/api/chat.php?action=create_room', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    room_type: roomType,
                    room_name: roomName,
                    service_request_id: serviceId || null
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.hideNewChatModal();
                this.loadChatRooms();
                alert('Chat room created successfully!');
            } else {
                alert('Failed to create chat room: ' + data.message);
            }
        } catch (error) {
            console.error('Error creating chat room:', error);
            alert('Failed to create chat room');
        }
    }
    <?php endif; ?>
    
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            if (this.currentRoom) {
                this.loadMessages(this.currentRoom);
            }
            this.updateUnreadCount();
        }, 5000); // Refresh every 5 seconds
    }
    
    scrollToBottom() {
        const messagesContainer = document.getElementById('chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) { // Less than 1 minute
            return 'now';
        } else if (diff < 3600000) { // Less than 1 hour
            return Math.floor(diff / 60000) + 'm';
        } else if (date.toDateString() === now.toDateString()) { // Today
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else {
            return date.toLocaleDateString();
        }
    }
}

// Initialize chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.chat = new SmartFixChat();
});
</script>

<?php endif; ?>