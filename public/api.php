<?php
/**
 * FireWeb Messenger - Complete API (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');

if (file_exists(CONFIG_PATH . '/app.php')) {
    require_once CONFIG_PATH . '/app.php';
}
if (file_exists(CONFIG_PATH . '/database.php')) {
    require_once CONFIG_PATH . '/database.php';
}

spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// File serving
if (isset($_GET['file'])) {
    $file = STORAGE_PATH . '/' . $_GET['file'];
    $realPath = realpath($file);
    $storagePath = realpath(STORAGE_PATH);
    
    if ($realPath && $storagePath && strpos($realPath, $storagePath) === 0 && file_exists($realPath)) {
        $mimeType = mime_content_type($realPath);
        $fileSize = filesize($realPath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: public, max-age=31536000');
        
        readfile($realPath);
        exit;
    }
    
    http_response_code(404);
    exit(json_encode(['error' => 'File not found']));
}

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

$chatController = new ChatController();
$userModel = new User();
$messageModel = new Message();

try {
    switch ($action) {
        // ==================== Conversations ====================
        case 'get_conversations':
            $conversations = $chatController->getConversations($userId);
            echo json_encode($conversations);
            break;
            
        case 'start_conversation':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['contact_id'])) {
                throw new Exception('Contact ID required');
            }
            
            // Check if blocked
            if ($userModel->isBlocked($userId, $data['contact_id']) || 
                $userModel->isBlocked($data['contact_id'], $userId)) {
                throw new Exception('Cannot start conversation with blocked user');
            }
            
            $result = $chatController->startConversation($userId, $data['contact_id']);
            echo json_encode($result);
            break;
            
        // ==================== Messages ====================
        case 'get_messages':
            $conversationId = $_GET['conversation_id'] ?? 0;
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            if (!$conversationId) {
                throw new Exception('Conversation ID required');
            }
            
            $messages = $chatController->getMessages($conversationId, $userId, $limit, $offset);
            echo json_encode($messages);
            break;
            
        case 'get_new_messages':
            $conversationId = $_GET['conversation_id'] ?? 0;
            $afterId = $_GET['after_id'] ?? 0;
            
            if (!$conversationId) {
                throw new Exception('Conversation ID required');
            }
            
            $messages = $chatController->getNewMessages($conversationId, $userId, $afterId);
            echo json_encode($messages);
            break;
            
        case 'send_message':
            $data = $_POST;
            
            if (empty($data)) {
                $data = json_decode(file_get_contents('php://input'), true);
            }
            
            if (!isset($data['conversation_id']) || !isset($data['type'])) {
                throw new Exception('Conversation ID and type required');
            }
            
            $data['sender_id'] = $userId;
            
            $result = $chatController->sendMessage($data);
            echo json_encode($result);
            break;
            
        case 'edit_message':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['message_id']) || !isset($data['body'])) {
                throw new Exception('Message ID and body required');
            }
            
            $result = $messageModel->edit($data['message_id'], $userId, $data['body']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'delete_message':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['message_id'])) {
                throw new Exception('Message ID required');
            }
            
            $result = $messageModel->delete($data['message_id'], $userId);
            echo json_encode(['success' => $result]);
            break;
            
        case 'mark_read':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['conversation_id'])) {
                throw new Exception('Conversation ID required');
            }
            $result = $chatController->markAsRead($data['conversation_id'], $userId);
            echo json_encode(['success' => $result]);
            break;
            
        // ==================== Search ====================
        case 'search_users':
            $query = $_GET['query'] ?? '';
            if (strlen($query) < 2) {
                echo json_encode([]);
                break;
            }
            $users = $userModel->search($query, $userId);
            echo json_encode($users);
            break;
            
        // ==================== Block/Unblock ====================
        case 'block_user':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['user_id'])) {
                throw new Exception('User ID required');
            }
            
            if ($data['user_id'] == $userId) {
                throw new Exception('Cannot block yourself');
            }
            
            $result = $userModel->blockUser($userId, $data['user_id']);
            echo json_encode(['success' => $result]);
            break;
            
        case 'unblock_user':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['user_id'])) {
                throw new Exception('User ID required');
            }
            
            $result = $userModel->unblockUser($userId, $data['user_id']);
            echo json_encode(['success' => $result]);
            break;
            
        // ==================== Profile ====================
        case 'update_profile':
            $data = $_POST;
            
            if (empty($data)) {
                $data = json_decode(file_get_contents('php://input'), true);
            }
            
            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $maxSize = 5 * 1024 * 1024;
                
                if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                    throw new Exception('Invalid avatar file type');
                }
                
                if ($_FILES['avatar']['size'] > $maxSize) {
                    throw new Exception('Avatar too large (max 5MB)');
                }
                
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . uniqid() . '_' . time() . '.' . $ext;
                $destination = STORAGE_PATH . '/avatars/' . $filename;
                
                // Resize image
                $image = null;
                switch ($_FILES['avatar']['type']) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($_FILES['avatar']['tmp_name']);
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($_FILES['avatar']['tmp_name']);
                        break;
                    case 'image/webp':
                        $image = imagecreatefromwebp($_FILES['avatar']['tmp_name']);
                        break;
                    case 'image/gif':
                        $image = imagecreatefromgif($_FILES['avatar']['tmp_name']);
                        break;
                }
                
                if ($image) {
                    list($width, $height) = getimagesize($_FILES['avatar']['tmp_name']);
                    $newSize = 400;
                    $ratio = min($newSize / $width, $newSize / $height);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);
                    
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                    
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    switch ($_FILES['avatar']['type']) {
                        case 'image/jpeg':
                            imagejpeg($resized, $destination, 90);
                            break;
                        case 'image/png':
                            imagepng($resized, $destination, 8);
                            break;
                        case 'image/webp':
                            imagewebp($resized, $destination, 90);
                            break;
                        case 'image/gif':
                            imagegif($resized, $destination);
                            break;
                    }
                    
                    imagedestroy($image);
                    imagedestroy($resized);
                    
                    $data['avatar'] = 'avatars/' . $filename;
                }
            }
            
            $result = $userModel->updateProfile($userId, $data);
            
            if ($result && isset($data['display_name'])) {
                $_SESSION['display_name'] = $data['display_name'];
            }
            
            echo json_encode(['success' => $result]);
            break;
            
        case 'get_profile':
            $profileUserId = $_GET['user_id'] ?? $userId;
            $profile = $userModel->findById($profileUserId);
            
            if ($profile && $profileUserId != $userId) {
                $profile['is_blocked'] = $userModel->isBlocked($userId, $profileUserId);
            }
            
            echo json_encode($profile);
            break;
            
        // ==================== Heartbeat ====================
        case 'heartbeat':
            $userModel->updateLastSeen($userId);
            echo json_encode(['success' => true, 'timestamp' => time()]);
            break;
            
        // ==================== Default ====================
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
