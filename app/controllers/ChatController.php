<?php
/**
 * FireWeb Messenger - Chat Controller (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

class ChatController {
    private $db;
    private $conversationModel;
    private $messageModel;
    private $userModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
        $this->userModel = new User();
    }
    
    /**
     * Get user conversations with unread count and full other_user info
     */
    public function getConversations($userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT 
                    c.*,
                    cm.last_read_message_id,
                    CASE 
                        WHEN c.type = 'dm' THEN u.username
                        ELSE c.title
                    END as conversation_name,
                    CASE 
                        WHEN c.type = 'dm' THEN u.display_name
                        ELSE c.title
                    END as conversation_display_name,
                    CASE 
                        WHEN c.type = 'dm' THEN u.avatar
                        ELSE c.avatar
                    END as conversation_avatar,
                    (SELECT body FROM messages WHERE conversation_id = c.id AND is_deleted = 0 ORDER BY id DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE conversation_id = c.id AND is_deleted = 0 ORDER BY id DESC LIMIT 1) as last_message_time,
                    (SELECT sender_id FROM messages WHERE conversation_id = c.id AND is_deleted = 0 ORDER BY id DESC LIMIT 1) as last_sender_id,
                    (SELECT COUNT(*) 
                    FROM messages m 
                    WHERE m.conversation_id = c.id 
                    AND m.is_deleted = 0 
                    AND m.sender_id != :user_id
                    AND (cm.last_read_message_id IS NULL OR m.id > cm.last_read_message_id)
                    ) as unread_count
                FROM conversations c
                INNER JOIN conversation_members cm ON c.id = cm.conversation_id
                LEFT JOIN users u ON (c.type = 'dm' AND u.id = (
                    SELECT user_id FROM conversation_members 
                    WHERE conversation_id = c.id AND user_id != :user_id2
                    LIMIT 1
                ))
                WHERE cm.user_id = :user_id3
                ORDER BY last_message_time DESC NULLS LAST
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':user_id2' => $userId,
                ':user_id3' => $userId
            ]);
            
            $conversations = $stmt->fetchAll();
            
            // Add full other_user info and format unread_count
            foreach ($conversations as &$conv) {
                $conv['unread_count'] = (int)($conv['unread_count'] ?? 0);
                
                if ($conv['type'] === 'dm') {
                    $members = $this->conversationModel->getMembers($conv['id']);
                    foreach ($members as $member) {
                        if ($member['id'] != $userId) {
                            $conv['other_user'] = [
                                'id' => $member['id'],
                                'username' => $member['username'] ?? '',
                                'display_name' => $member['display_name'] ?? 'Unknown',
                                'avatar' => $member['avatar'] ?? null,
                                'bio' => $member['bio'] ?? '',
                                'phone' => $member['phone'] ?? '',
                                'email' => $member['email'] ?? '',
                                'last_seen' => $member['last_seen'] ?? null,
                                'is_blocked' => $this->userModel->isBlocked($userId, $member['id'])
                            ];
                            break;
                        }
                    }
                }
            }
            
            return $conversations;
        } catch (Exception $e) {
            error_log('ERROR in getConversations: ' . $e->getMessage());
            return [];
        }
    }

    
    /**
     * Mark conversation as read
     */
    public function markAsRead($conversationId, $userId) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return false;
        }
        
        try {
            $conn = $this->db->getConnection();
            
            // Get latest message ID in conversation
            $stmt = $conn->prepare("
                SELECT MAX(id) as last_message_id 
                FROM messages 
                WHERE conversation_id = :conversation_id 
                AND is_deleted = 0
            ");
            $stmt->execute([':conversation_id' => $conversationId]);
            $result = $stmt->fetch();
            $lastMessageId = $result['last_message_id'] ?? 0;
            
            // Update last_read_message_id
            $stmt = $conn->prepare("
                UPDATE conversation_members 
                SET last_read_message_id = :last_message_id 
                WHERE conversation_id = :conversation_id 
                AND user_id = :user_id
            ");
            
            return $stmt->execute([
                ':last_message_id' => $lastMessageId,
                ':conversation_id' => $conversationId,
                ':user_id' => $userId
            ]);
        } catch (Exception $e) {
            error_log('Mark as read error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start new conversation with block check
     */
    public function startConversation($userId, $contactId) {
        if ($userId == $contactId) {
            return ['success' => false, 'error' => 'Cannot chat with yourself'];
        }
        
        // Check if blocked
        if ($this->userModel->isBlocked($userId, $contactId) || 
            $this->userModel->isBlocked($contactId, $userId)) {
            return ['success' => false, 'error' => 'Cannot start conversation with blocked user'];
        }
        
        // Check if conversation already exists
        $existingConv = $this->conversationModel->findDirectConversation($userId, $contactId);
        
        if ($existingConv && is_array($existingConv) && isset($existingConv['id'])) {
            return ['success' => true, 'conversation_id' => $existingConv['id']];
        }
        
        // Create new conversation
        $convId = $this->conversationModel->create('dm', $userId);
        
        if ($convId) {
            $this->conversationModel->addMember($convId, $userId);
            $this->conversationModel->addMember($convId, $contactId);
            return ['success' => true, 'conversation_id' => $convId];
        }
        
        return ['success' => false, 'error' => 'Failed to create conversation'];
    }
    
    /**
     * Get messages with authorization check
     */
    public function getMessages($conversationId, $userId, $limit = 50, $offset = 0) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return [];
        }
        return $this->messageModel->getConversationMessages($conversationId, $limit, $offset);
    }
    
    /**
     * Get new messages after specific ID
     */
    public function getNewMessages($conversationId, $userId, $afterId) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return [];
        }
        return $this->messageModel->getNewMessages($conversationId, $afterId);
    }
    
    /**
     * Send message with block check
     */
    public function sendMessage($data) {
        if (empty($data['conversation_id']) || empty($data['sender_id']) || empty($data['type'])) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }
        
        if (!$this->conversationModel->isMember($data['conversation_id'], $data['sender_id'])) {
            return ['success' => false, 'error' => 'Not authorized'];
        }
        
        // ✅ Check if blocked - CRITICAL!
        $members = $this->conversationModel->getMembers($data['conversation_id']);
        foreach ($members as $member) {
            if ($member['id'] != $data['sender_id']) {
                // Check both ways: Did I block them or did they block me?
                if ($this->userModel->isBlocked($data['sender_id'], $member['id']) || 
                    $this->userModel->isBlocked($member['id'], $data['sender_id'])) {
                    return [
                        'success' => false,
                        'error' => 'Cannot send message. This user has blocked you or you have blocked them.',
                        'blocked' => true
                    ];
                }
            }
        }
        
        // Handle file upload
        if (isset($_FILES['file'])) {
            $fileData = $this->handleFileUpload($_FILES['file'], $data['type']);
            if ($fileData) {
                $data = array_merge($data, $fileData);
            } else {
                return ['success' => false, 'error' => 'File upload failed'];
            }
        }
        
        // Validate text messages
        if ($data['type'] === 'text' && empty($data['body'])) {
            return ['success' => false, 'error' => 'Message body is required'];
        }
        
        // Create message
        $messageId = $this->messageModel->create($data);
        
        if ($messageId) {
            return ['success' => true, 'message_id' => $messageId];
        }
        
        return ['success' => false, 'error' => 'Failed to send message'];
    }
    
    /**
     * Handle file upload with comprehensive type support
     */
    private function handleFileUpload($file, $type) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log('File upload error code: ' . $file['error']);
            return null;
        }

        // 20MB max
        $maxSize = 20 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            error_log('File too large: ' . $file['size'] . ' bytes');
            return null;
        }

        // Allowed MIME types
        $allowedTypes = [
            'image' => [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif'
            ],
            'file' => [
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed',
                'application/octet-stream', 'text/plain', 'text/csv',
                'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/webm',
                'video/mp4'
            ],
            'voice' => ['audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp3', 'audio/wav']
        ];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validExtensions = [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'zip', 'rar', '7z', 'txt', 'csv',
            'mp3', 'ogg', 'webm', 'wav', 'mp4'
        ];

        if (!in_array($ext, $validExtensions)) {
            error_log('Invalid file extension: ' . $ext);
            return null;
        }

        // Special handling for archives
        if (in_array($ext, ['zip', 'rar', '7z'])) {
            if (!in_array($file['type'], [
                'application/zip', 'application/x-zip-compressed',
                'application/x-rar-compressed', 'application/octet-stream',
                'application/x-7z-compressed'
            ])) {
                error_log('Invalid MIME type for archive: ' . $file['type']);
                return null;
            }
        } else {
            if (!isset($allowedTypes[$type]) || !in_array($file['type'], $allowedTypes[$type])) {
                error_log('Invalid MIME type: ' . $file['type'] . ' for type: ' . $type);
                return null;
            }
        }

        // Determine target folder
        $folderMap = ['image' => 'uploads', 'file' => 'uploads', 'voice' => 'voices'];
        $folder = $folderMap[$type] ?? 'uploads';
        $targetDir = STORAGE_PATH . '/' . $folder;

        // Create directory if not exists
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                error_log('Failed to create directory: ' . $targetDir);
                return null;
            }
        }

        // Generate unique filename
        $filename = $type . '_' . uniqid() . '_' . time() . '.' . $ext;
        $destination = $targetDir . '/' . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            error_log('File uploaded successfully: ' . $filename);
            return [
                'file_path' => $folder . '/' . $filename,
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $file['type']
            ];
        }

        error_log('Failed to move uploaded file to: ' . $destination);
        return null;
    }
    
    /**
     * Search users (excluding current user)
     */
    public function searchUsers($query, $userId) {
        return $this->userModel->search($query, $userId);
    }
    
    /**
     * Delete conversation (remove user from members)
     */
    public function deleteConversation($conversationId, $userId) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return ['success' => false, 'error' => 'Not authorized'];
        }
        
        try {
            $result = $this->conversationModel->removeMember($conversationId, $userId);
            return ['success' => $result];
        } catch (Exception $e) {
            error_log('Delete conversation error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete conversation'];
        }
    }
    
    /**
     * Get conversation details
     */
    public function getConversationDetails($conversationId, $userId) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return null;
        }
        return $this->conversationModel->getById($conversationId);
    }
    
    /**
     * Get conversation statistics
     */
    public function getConversationStats($conversationId, $userId) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return null;
        }
        
        try {
            return [
                'total_messages' => $this->messageModel->getConversationMessageCount($conversationId),
                'media_count' => $this->messageModel->getMediaCount($conversationId),
                'file_count' => $this->messageModel->getFileCount($conversationId),
                'members_count' => $this->conversationModel->getMembersCount($conversationId)
            ];
        } catch (Exception $e) {
            error_log('Get stats error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search messages in conversation
     */
    public function searchMessages($conversationId, $userId, $query) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return [];
        }
        return $this->messageModel->searchInConversation($conversationId, $query);
    }
    
    /**
     * Get shared media (images/files) in conversation
     */
    public function getSharedMedia($conversationId, $userId, $type = 'image', $limit = 50) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return [];
        }
        return $this->messageModel->getSharedMedia($conversationId, $type, $limit);
    }
}
