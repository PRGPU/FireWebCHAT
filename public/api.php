<?php
/**
 * FireWeb Messenger - Complete API (Premium UI v1.0.0)
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
    $file        = STORAGE_PATH . '/' . $_GET['file'];
    $realPath    = realpath($file);
    $storagePath = realpath(STORAGE_PATH);

    if ($realPath && $storagePath && strpos($realPath, $storagePath) === 0 && file_exists($realPath)) {
        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));

        $mimeMap = [
            'webm' => 'audio/webm',
            'ogg'  => 'audio/ogg',
            'oga'  => 'audio/ogg',
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'm4a'  => 'audio/mp4',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'mp4'  => 'video/mp4',
            'pdf'  => 'application/pdf',
            'zip'  => 'application/zip',
            'txt'  => 'text/plain',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $mimeType = $mimeMap[$ext] ?? mime_content_type($realPath) ?? 'application/octet-stream';
        $fileSize = filesize($realPath);

        header('Content-Type: ' . $mimeType);
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=86400');

        // Range request — for seek and duration
        if (isset($_SERVER['HTTP_RANGE'])) {
            preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m);
            $start  = intval($m[1]);
            $end    = isset($m[2]) && $m[2] !== '' ? intval($m[2]) : $fileSize - 1;
            $end    = min($end, $fileSize - 1);
            $length = $end - $start + 1;

            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
            header("Content-Length: {$length}");

            $fp = fopen($realPath, 'rb');
            fseek($fp, $start);
            $remaining = $length;
            while ($remaining > 0 && !feof($fp)) {
                $chunk = min(8192, $remaining);
                echo fread($fp, $chunk);
                $remaining -= $chunk;
            }
            fclose($fp);
        } else {
            header('Content-Length: ' . $fileSize);
            readfile($realPath);
        }
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

$action  = $_GET['action'] ?? '';
$userId  = $_SESSION['user_id'];

$chatController = new ChatController();
$userModel      = new User();
$messageModel   = new Message();

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
            if ($userModel->isBlocked($userId, $data['contact_id']) ||
                $userModel->isBlocked($data['contact_id'], $userId)) {
                throw new Exception('Cannot start conversation with blocked user');
            }
            $result = $chatController->startConversation($userId, $data['contact_id']);
            echo json_encode($result);
            break;

        case 'create_channel':
            $data = json_decode(file_get_contents('php://input'), true);
            $title = trim($data['title'] ?? '');
            if (strlen($title) < 2 || strlen($title) > 50) {
                throw new Exception('Channel title must be 2-50 characters');
            }
            $username = trim($data['username'] ?? '');
            if ($username && !preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
                throw new Exception('Username: 3-32 chars, letters/numbers/underscore only');
            }
            $convModel = new Conversation();
            $result    = $convModel->createChannel(
                $userId,
                $title,
                $data['description'] ?? null,
                $username ?: null
            );
            echo json_encode($result);
            break;

        case 'search_channels':
            $query = trim($_GET['query'] ?? '');
            if (strlen($query) < 1) { echo json_encode([]); break; }
            $convModel = new Conversation();
            $channels  = $convModel->searchChannels($query, $userId);
            echo json_encode($channels);
            break;

        case 'join_channel':
            $data      = json_decode(file_get_contents('php://input'), true);
            $channelId = intval($data['channel_id'] ?? 0);
            if (!$channelId) throw new Exception('Channel ID required');
            $convModel = new Conversation();
            $result    = $convModel->joinChannel($channelId, $userId);
            echo json_encode($result);
            break;

        case 'leave_channel':
            $data      = json_decode(file_get_contents('php://input'), true);
            $channelId = intval($data['channel_id'] ?? 0);
            if (!$channelId) throw new Exception('Channel ID required');
            $convModel = new Conversation();
            if (!$convModel->isMember($channelId, $userId)) {
                throw new Exception('Not a member');
            }
            // Owner cannot leave
            if ($convModel->isAdmin($channelId, $userId)) {
                $conv = $convModel->getById($channelId);
                if ($conv['owner_id'] == $userId) {
                    throw new Exception('Owner cannot leave channel. Delete it instead.');
                }
            }
            $result = $convModel->removeMember($channelId, $userId);
            echo json_encode(['success' => $result]);
            break;


        // ==================== Group Actions (PATCH C) ====================

        case 'create_group':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['title'])) {
                throw new Exception('Group title required');
            }
            $title = trim($data['title']);
            if (strlen($title) < 2 || strlen($title) > 50) {
                throw new Exception('Group title must be 2-50 characters');
            }
            $convModel = new Conversation();
            $groupId   = $convModel->createGroup($userId, $title, $data['description'] ?? null);
            if (!$groupId) {
                throw new Exception('Failed to create group');
            }
            if (!empty($data['member_ids']) && is_array($data['member_ids'])) {
                foreach ($data['member_ids'] as $memberId) {
                    $memberId = intval($memberId);
                    if ($memberId && $memberId !== $userId) {
                        if (!$userModel->isBlocked($userId, $memberId) &&
                            !$userModel->isBlocked($memberId, $userId)) {
                            $convModel->addMemberWithRole($groupId, $memberId, 'member');
                        }
                    }
                }
            }
            echo json_encode(['success' => true, 'conversation_id' => $groupId]);
            break;

        case 'get_group_info':
            $convId = intval($_GET['conversation_id'] ?? 0);
            if (!$convId) throw new Exception('Conversation ID required');
            $convModel = new Conversation();
            if (!$convModel->isMember($convId, $userId)) {
                throw new Exception('Access denied');
            }

            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("SELECT * FROM conversations WHERE id = ?");
            $stmt->execute([$convId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$info) {
                throw new Exception('Conversation not found');
            }

            $members = $convModel->getMembers($convId);
            foreach ($members as &$member) {
                $member['role']       = $convModel->getMemberRole($convId, $member['id']);
                $member['avatar_url'] = $member['avatar']
                    ? 'public/api.php?file=' . urlencode($member['avatar'])
                    : null;
            }
            unset($member);

            echo json_encode(['info' => $info, 'members' => $members]);
            break;


        case 'add_group_member':
            $data     = json_decode(file_get_contents('php://input'), true);
            $convId   = intval($data['conversation_id'] ?? 0);
            $targetId = intval($data['user_id'] ?? 0);
            if (!$convId || !$targetId) throw new Exception('Missing parameters');
            $convModel = new Conversation();
            if (!$convModel->isAdmin($convId, $userId)) {
                throw new Exception('Only admins can add members');
            }
            if ($userModel->isBlocked($userId, $targetId) ||
                $userModel->isBlocked($targetId, $userId)) {
                throw new Exception('Cannot add blocked user');
            }
            if ($convModel->isMember($convId, $targetId)) {
                throw new Exception('User is already a member');
            }
            $result = $convModel->addMemberWithRole($convId, $targetId, 'member');
            echo json_encode(['success' => $result]);
            break;

        case 'remove_group_member':
            $data     = json_decode(file_get_contents('php://input'), true);
            $convId   = intval($data['conversation_id'] ?? 0);
            $targetId = intval($data['user_id'] ?? 0);
            if (!$convId || !$targetId) throw new Exception('Missing parameters');
            $convModel = new Conversation();
            if (!$convModel->isMember($convId, $userId)) {
                throw new Exception('Access denied');
            }
            if ($targetId !== $userId && !$convModel->isAdmin($convId, $userId)) {
                throw new Exception('Only admins can remove members');
            }
            $result = $convModel->removeMember($convId, $targetId);
            echo json_encode(['success' => $result]);
            break;

        case 'leave_group':
            $data   = json_decode(file_get_contents('php://input'), true);
            $convId = intval($data['conversation_id'] ?? 0);
            if (!$convId) throw new Exception('Conversation ID required');
            $convModel = new Conversation();
            if (!$convModel->isMember($convId, $userId)) {
                throw new Exception('Access denied');
            }
            $result = $convModel->removeMember($convId, $userId);
            echo json_encode(['success' => $result]);
            break;

        case 'update_group':
            $data   = json_decode(file_get_contents('php://input'), true);
            $convId = intval($data['conversation_id'] ?? 0);
            if (!$convId || empty($data['title'])) {
                throw new Exception('Missing parameters');
            }
            $convModel = new Conversation();
            if (!$convModel->isAdmin($convId, $userId)) {
                throw new Exception('Only admins can update group info');
            }
            $title  = trim($data['title']);
            if (strlen($title) < 2 || strlen($title) > 50) {
                throw new Exception('Group title must be 2-50 characters');
            }
            $result = $convModel->updateGroup($convId, $title, $data['description'] ?? null);
            echo json_encode(['success' => $result]);
            break;
        
        case 'update_group_avatar':
            $convId = intval($_POST['conversation_id'] ?? 0);
            if (!$convId) throw new Exception('Conversation ID required');

            $convModel = new Conversation();
            if (!$convModel->isMember($convId, $userId)) {
                throw new Exception('Access denied');
            }
            // Only admins can change
            if (!$convModel->isAdmin($convId, $userId)) {
                throw new Exception('Only admins can change group avatar');
            }

            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded');
            }

            $file         = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg','image/png','image/webp','image/gif'];
            $maxSize      = 5 * 1024 * 1024;

            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type');
            }
            if ($file['size'] > $maxSize) {
                throw new Exception('File too large (max 5MB)');
            }

            $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename    = 'group_' . $convId . '_' . uniqid() . '.' . $ext;
            $targetDir   = STORAGE_PATH . '/uploads/groups';

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $destination = $targetDir . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Failed to save file');
            }

            $conn = Database::getInstance()->getConnection();
            $stmt = $conn->prepare("UPDATE conversations SET avatar = ? WHERE id = ?");
            $stmt->execute(['uploads/groups/' . $filename, $convId]);

            echo json_encode([
                'success'    => true,
                'avatar_url' => 'public/api.php?file=' . urlencode('uploads/groups/' . $filename)
            ]);
            break;


        // ==================== Messages ====================

        case 'get_messages':
            $conversationId = $_GET['conversation_id'] ?? 0;
            $limit          = $_GET['limit'] ?? 50;
            $offset         = $_GET['offset'] ?? 0;
            if (!$conversationId) throw new Exception('Conversation ID required');
            $messages = $chatController->getMessages($conversationId, $userId, $limit, $offset);
            echo json_encode($messages);
            break;

        case 'get_new_messages':
            $conversationId = $_GET['conversation_id'] ?? 0;
            $afterId        = $_GET['after_id'] ?? 0;
            if (!$conversationId) throw new Exception('Conversation ID required');
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

        // ==================== Block / Unblock ====================

        case 'block_user':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['user_id'])) throw new Exception('User ID required');
            if ($data['user_id'] == $userId) throw new Exception('Cannot block yourself');
            $result = $userModel->blockUser($userId, $data['user_id']);
            echo json_encode(['success' => $result]);
            break;

        case 'unblock_user':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['user_id'])) throw new Exception('User ID required');
            $result = $userModel->unblockUser($userId, $data['user_id']);
            echo json_encode(['success' => $result]);
            break;

        // ==================== Profile ====================

        case 'update_profile':
            $data = $_POST;
            if (empty($data)) {
                $data = json_decode(file_get_contents('php://input'), true);
            }
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $maxSize      = 5 * 1024 * 1024;
                if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                    throw new Exception('Invalid avatar file type');
                }
                if ($_FILES['avatar']['size'] > $maxSize) {
                    throw new Exception('Avatar too large (max 5MB)');
                }
                $ext         = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename    = 'avatar_' . uniqid() . '_' . time() . '.' . $ext;
                $destination = STORAGE_PATH . '/avatars/' . $filename;

                if (!is_dir(STORAGE_PATH . '/avatars')) {
                    mkdir(STORAGE_PATH . '/avatars', 0755, true);
                }

                $image = null;
                switch ($_FILES['avatar']['type']) {
                    case 'image/jpeg': $image = imagecreatefromjpeg($_FILES['avatar']['tmp_name']); break;
                    case 'image/png':  $image = imagecreatefrompng($_FILES['avatar']['tmp_name']);  break;
                    case 'image/webp': $image = imagecreatefromwebp($_FILES['avatar']['tmp_name']); break;
                    case 'image/gif':  $image = imagecreatefromgif($_FILES['avatar']['tmp_name']);  break;
                }
                if ($image) {
                    list($width, $height) = getimagesize($_FILES['avatar']['tmp_name']);
                    $newSize   = 400;
                    $ratio     = min($newSize / $width, $newSize / $height);
                    $newWidth  = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);
                    $resized   = imagecreatetruecolor($newWidth, $newHeight);
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    switch ($_FILES['avatar']['type']) {
                        case 'image/jpeg': imagejpeg($resized, $destination, 90); break;
                        case 'image/png':  imagepng($resized, $destination, 8);   break;
                        case 'image/webp': imagewebp($resized, $destination, 90); break;
                        case 'image/gif':  imagegif($resized, $destination);      break;
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
            $profile       = $userModel->findById($profileUserId);
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
