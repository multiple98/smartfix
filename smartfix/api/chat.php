<?php
// Chat API Endpoints
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once('../includes/db.php');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Response helper function
function sendResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => time()
    ]);
    exit;
}

// Authentication helper
function getCurrentUser() {
    if (isset($_SESSION['admin_id'])) {
        return [
            'id' => $_SESSION['admin_id'],
            'type' => 'admin',
            'name' => $_SESSION['admin_name'] ?? 'Admin'
        ];
    } elseif (isset($_SESSION['technician_id'])) {
        return [
            'id' => $_SESSION['technician_id'],
            'type' => 'technician',
            'name' => $_SESSION['technician_name'] ?? 'Technician'
        ];
    } elseif (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'type' => 'customer',
            'name' => $_SESSION['user_name'] ?? 'Customer'
        ];
    }
    return null;
}

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        default:
            sendResponse(false, null, 'Method not allowed', 405);
    }
} catch (Exception $e) {
    sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
}

function handleGetRequest($action) {
    global $pdo;
    
    $user = getCurrentUser();
    if (!$user) {
        sendResponse(false, null, 'Authentication required', 401);
    }
    
    switch ($action) {
        case 'rooms':
            getUserChatRooms($user);
            break;
        case 'messages':
            getChatMessages($_GET['room_id'] ?? '');
            break;
        case 'participants':
            getRoomParticipants($_GET['room_id'] ?? '');
            break;
        case 'unread_count':
            getUnreadCount($user);
            break;
        default:
            sendResponse(false, null, 'Invalid action');
    }
}

function handlePostRequest($action) {
    global $pdo;
    
    $user = getCurrentUser();
    if (!$user) {
        sendResponse(false, null, 'Authentication required', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'send_message':
            sendMessage($user, $input);
            break;
        case 'create_room':
            createChatRoom($user, $input);
            break;
        case 'join_room':
            joinChatRoom($user, $input);
            break;
        case 'mark_read':
            markMessagesAsRead($user, $input);
            break;
        case 'upload_file':
            uploadFile($user);
            break;
        default:
            sendResponse(false, null, 'Invalid action');
    }
}

function handlePutRequest($action) {
    global $pdo;
    
    $user = getCurrentUser();
    if (!$user) {
        sendResponse(false, null, 'Authentication required', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_online_status':
            updateOnlineStatus($user, $input);
            break;
        default:
            sendResponse(false, null, 'Invalid action');
    }
}

// Get user's chat rooms
function getUserChatRooms($user) {
    global $pdo;
    
    try {
        $query = "
            SELECT DISTINCT 
                cr.room_id,
                cr.room_name,
                cr.room_type,
                cr.service_request_id,
                cr.updated_at,
                cp.unread_count,
                (SELECT cm.message FROM chat_messages cm 
                 WHERE cm.room_id = cr.room_id 
                 ORDER BY cm.sent_at DESC LIMIT 1) as last_message,
                (SELECT cm.sent_at FROM chat_messages cm 
                 WHERE cm.room_id = cr.room_id 
                 ORDER BY cm.sent_at DESC LIMIT 1) as last_message_time,
                (SELECT cm.sender_name FROM chat_messages cm 
                 WHERE cm.room_id = cr.room_id 
                 ORDER BY cm.sent_at DESC LIMIT 1) as last_sender
            FROM chat_rooms cr
            LEFT JOIN chat_participants cp ON cr.room_id = cp.room_id 
                AND cp.user_id = ? AND cp.user_type = ?
            WHERE cr.is_active = 1
        ";
        
        // For admins, show all rooms
        if ($user['type'] === 'admin') {
            $query .= " ORDER BY cr.updated_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user['id'], $user['type']]);
        } else {
            // For customers/technicians, only show their rooms
            $query .= " AND cp.user_id IS NOT NULL ORDER BY cr.updated_at DESC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user['id'], $user['type']]);
        }
        
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the rooms data
        foreach ($rooms as &$room) {
            $room['unread_count'] = (int)($room['unread_count'] ?? 0);
            $room['last_message_time'] = $room['last_message_time'] ? 
                date('Y-m-d H:i:s', strtotime($room['last_message_time'])) : null;
        }
        
        sendResponse(true, $rooms);
        
    } catch (PDOException $e) {
        sendResponse(false, null, 'Database error: ' . $e->getMessage());
    }
}

// Get messages for a specific room
function getChatMessages($room_id) {
    global $pdo;
    
    if (!$room_id) {
        sendResponse(false, null, 'Room ID required');
    }
    
    try {
        $query = "
            SELECT 
                id,
                sender_id,
                sender_type,
                sender_name,
                message_type,
                message,
                file_path,
                file_name,
                is_read,
                sent_at
            FROM chat_messages 
            WHERE room_id = ? 
            ORDER BY sent_at ASC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$room_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format timestamps
        foreach ($messages as &$message) {
            $message['sent_at'] = date('Y-m-d H:i:s', strtotime($message['sent_at']));
            $message['is_read'] = (bool)$message['is_read'];
        }
        
        sendResponse(true, $messages);
        
    } catch (PDOException $e) {
        sendResponse(false, null, 'Database error: ' . $e->getMessage());
    }
}

// Send a new message
function sendMessage($user, $input) {
    global $pdo;
    
    $room_id = $input['room_id'] ?? '';
    $message = trim($input['message'] ?? '');
    $message_type = $input['message_type'] ?? 'text';
    
    if (!$room_id || !$message) {
        sendResponse(false, null, 'Room ID and message are required');
    }
    
    try {
        // Verify user has access to this room
        $access_query = "
            SELECT id FROM chat_participants 
            WHERE room_id = ? AND user_id = ? AND user_type = ?
        ";
        $access_stmt = $pdo->prepare($access_query);
        $access_stmt->execute([$room_id, $user['id'], $user['type']]);
        
        // For admins, always allow access
        if (!$access_stmt->fetch() && $user['type'] !== 'admin') {
            sendResponse(false, null, 'Access denied to this chat room', 403);
        }
        
        // Insert the message
        $insert_query = "
            INSERT INTO chat_messages (room_id, sender_id, sender_type, sender_name, message_type, message) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $insert_stmt = $pdo->prepare($insert_query);
        $insert_stmt->execute([
            $room_id,
            $user['id'],
            $user['type'],
            $user['name'],
            $message_type,
            $message
        ]);
        
        $message_id = $pdo->lastInsertId();
        
        // Update room's last activity
        $update_room = $pdo->prepare("UPDATE chat_rooms SET updated_at = NOW() WHERE room_id = ?");
        $update_room->execute([$room_id]);
        
        // Update unread counts for other participants
        $update_unread = $pdo->prepare("
            UPDATE chat_participants 
            SET unread_count = unread_count + 1 
            WHERE room_id = ? AND NOT (user_id = ? AND user_type = ?)
        ");
        $update_unread->execute([$room_id, $user['id'], $user['type']]);
        
        // Get the sent message details
        $get_message = $pdo->prepare("SELECT * FROM chat_messages WHERE id = ?");
        $get_message->execute([$message_id]);
        $sent_message = $get_message->fetch(PDO::FETCH_ASSOC);
        
        sendResponse(true, $sent_message, 'Message sent successfully');
        
    } catch (PDOException $e) {
        sendResponse(false, null, 'Database error: ' . $e->getMessage());
    }
}

// Create a new chat room
function createChatRoom($user, $input) {
    global $pdo;
    
    $room_type = $input['room_type'] ?? 'general';
    $room_name = $input['room_name'] ?? '';
    $service_request_id = $input['service_request_id'] ?? null;
    
    if (!$room_name) {
        sendResponse(false, null, 'Room name is required');
    }
    
    try {
        // Generate unique room ID
        $room_id = $room_type . '_' . uniqid();
        
        // Create the room
        $create_query = "
            INSERT INTO chat_rooms (room_id, room_type, room_name, service_request_id) 
            VALUES (?, ?, ?, ?)
        ";
        $create_stmt = $pdo->prepare($create_query);
        $create_stmt->execute([$room_id, $room_type, $room_name, $service_request_id]);
        
        // Add creator as participant
        $add_participant = $pdo->prepare("
            INSERT INTO chat_participants (room_id, user_id, user_type, user_name) 
            VALUES (?, ?, ?, ?)
        ");
        $add_participant->execute([$room_id, $user['id'], $user['type'], $user['name']]);
        
        // Add welcome message
        $welcome_message = $pdo->prepare("
            INSERT INTO chat_messages (room_id, sender_id, sender_type, sender_name, message_type, message) 
            VALUES (?, 0, 'system', 'System', 'system', ?)
        ");
        $welcome_message->execute([
            $room_id,
            "Welcome to {$room_name}! Start your conversation here."
        ]);
        
        sendResponse(true, ['room_id' => $room_id], 'Chat room created successfully');
        
    } catch (PDOException $e) {
        sendResponse(false, null, 'Database error: ' . $e->getMessage());
    }
}

// Mark messages as read
function markMessagesAsRead($user, $input) {
    global $pdo;
    
    $room_id = $input['room_id'] ?? '';
    
    if (!$room_id) {
        sendResponse(false, null, 'Room ID required');
    }
    
    try {
        // Mark messages as read
        $mark_read = $pdo->prepare("
            UPDATE chat_messages 
            SET is_read = 1 
            WHERE room_id = ? AND NOT (sender_id = ? AND sender_type = ?)
        ");
        $mark_read->execute([$room_id, $user['id'], $user['type']]);
        
        // Reset unread count for this user
        $reset_count = $pdo->prepare("
            UPDATE chat_participants 
            SET unread_count = 0, last_seen = NOW() 
            WHERE room_id = ? AND user_id = ? AND user_type = ?
        ");
        $reset_count->execute([$room_id, $user['id'], $user['type']]);
        
        sendResponse(true, null, 'Messages marked as read');
        
    } catch (PDOException $e) {
        sendResponse(false, null, 'Database error: ' . $e->getMessage());
    }
}

// Get total unread count for user
function getUnreadCount($user) {
    global $pdo;
    
    try {
        $query = "SELECT SUM(unread_count) as total FROM chat_participants WHERE user_id = ? AND user_type = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user['id'], $user['type']]);
        $result = $stmt->fetch();
        
        $unread_count = (int)($result['total'] ?? 0);
        
        sendResponse(true, ['unread_count' => $unread_count]);
        
    } catch (PDOException $e) {
        sendResponse(false, null, 'Database error: ' . $e->getMessage());
    }
}

// Update online status
function updateOnlineStatus($user, $input) {
    global $pdo;
    
    $is_online = $input['is_online'] ?? false;
    $room_id = $input['room_id'] ?? '';
    
    try {
        if ($room_id) {
            // Update status for specific room
            $update_query = "
                UPDATE chat_participants 
                SET is_online = ?, last_seen = NOW() 
                WHERE room_id = ? AND user_id = ? AND user_type = ?
            ";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$is_online, $room_id, $user['id'], $user['type']]);
        } else {
            // Update status for all rooms
            $update_query = "
                UPDATE chat_participants 
                SET is_online = ?, last_seen = NOW() 
                WHERE user_id = ? AND user_type = ?
            ";
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([$is_online, $user['id'], $user['type']]);
        }
        
        sendResponse(true, null, 'Online status updated');
        
    } catch (PDOException $e) {
        sendResponse(false, null, 'Database error: ' . $e->getMessage());
    }
}

?>