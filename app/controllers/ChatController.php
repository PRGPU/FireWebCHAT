<?php
/**
 * FireWeb Messenger - Chat Controller (Premium UI v1.0.0)
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

class ChatController {
    private $db;
    private $conversationModel;
    private $messageModel;
    private $userModel;

    public function __construct() {
        $this->db                = Database::getInstance();
        $this->conversationModel = new Conversation();
        $this->messageModel      = new Message();
        $this->userModel         = new User();
    }

    /**
     * Get user conversations with unread count and full other_user / group info
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
                    (SELECT body FROM messages
                     WHERE conversation_id = c.id AND is_deleted = 0
                     ORDER BY id DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages
                     WHERE conversation_id = c.id AND is_deleted = 0
                     ORDER BY id DESC LIMIT 1) as last_message_time,
                    (SELECT sender_id FROM messages
                     WHERE conversation_id = c.id AND is_deleted = 0
                     ORDER BY id DESC LIMIT 1) as last_sender_id,
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
                ':user_id'  => $userId,
                ':user_id2' => $userId,
                ':user_id3' => $userId
            ]);

            $conversations = $stmt->fetchAll();

            foreach ($conversations as &$conv) {
                $conv['unread_count'] = (int)($conv['unread_count'] ?? 0);
                $conv['member_role'] = $this->conversationModel->getMemberRole($conv['id'], $userId);
 


                if ($conv['type'] === 'dm') {
                    $members = $this->conversationModel->getMembers($conv['id']);
                    foreach ($members as $member) {
                        if ($member['id'] != $userId) {
                            $conv['other_user'] = [
                                'id'           => $member['id'],
                                'username'     => $member['username']     ?? '',
                                'display_name' => $member['display_name'] ?? 'Unknown',
                                'avatar'       => $member['avatar']       ?? null,
                                'bio'          => $member['bio']          ?? '',
                                'phone'        => $member['phone']        ?? '',
                                'last_seen'    => $member['last_seen']    ?? null,
                                'is_blocked'   => $this->userModel->isBlocked($userId, $member['id'])
                            ];
                            break;
                        }
                    }
                }
                
                // PATCH F1: Group & Channel 
                if ($conv['type'] === 'group' || $conv['type'] === 'channel') {
                    $conv['member_count'] = $this->conversationModel->getMembersCount($conv['id']);
                    $conv['description']  = $conv['description'] ?? null;
                }
                
                // Saved Messages
                if ($conv['type'] === 'saved') {
                    $conv['conversation_display_name'] = 'Saved Messages';
                    $conv['conversation_avatar']       = null;
                }

            }
            unset($conv);

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
            $stmt = $conn->prepare("
                SELECT MAX(id) as last_message_id
                FROM messages
                WHERE conversation_id = :conversation_id AND is_deleted = 0
            ");
            $stmt->execute([':conversation_id' => $conversationId]);
            $result        = $stmt->fetch();
            $lastMessageId = $result['last_message_id'] ?? 0;

            $stmt = $conn->prepare("
                UPDATE conversation_members
                SET last_read_message_id = :last_message_id
                WHERE conversation_id = :conversation_id AND user_id = :user_id
            ");
            return $stmt->execute([
                ':last_message_id'  => $lastMessageId,
                ':conversation_id'  => $conversationId,
                ':user_id'          => $userId
            ]);
        } catch (Exception $e) {
            error_log('Mark as read error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Start new DM conversation
     */
    public function startConversation($userId, $contactId) {
        if ($userId == $contactId) {
            return ['success' => false, 'error' => 'Cannot chat with yourself'];
        }
        if ($this->userModel->isBlocked($userId, $contactId) ||
            $this->userModel->isBlocked($contactId, $userId)) {
            return ['success' => false, 'error' => 'Cannot start conversation with blocked user'];
        }
        $existingConv = $this->conversationModel->findDirectConversation($userId, $contactId);
        if ($existingConv && is_array($existingConv) && isset($existingConv['id'])) {
            return ['success' => true, 'conversation_id' => $existingConv['id']];
        }
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
        return $this->messageModel->getConversationMessages($conversationId, $limit);
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
     * Send message — PATCH F2
     */
    public function sendMessage($data) {
        if (empty($data['conversation_id']) || empty($data['sender_id']) || empty($data['type'])) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }
        if (!$this->conversationModel->isMember($data['conversation_id'], $data['sender_id'])) {
            return ['success' => false, 'error' => 'Not authorized'];
        }

        $convInfo = $this->conversationModel->getById($data['conversation_id']);
        $convType = $convInfo['type'] ?? 'dm';

        if ($convType === 'channel') {
            $convModel = new Conversation();
            if (!$convModel->canPostInChannel($data['conversation_id'], $data['sender_id'])) {
                return [
                    'success'  => false,
                    'error'    => 'Only admins can post in channels.',
                    'readonly' => true
                ];
            }
        }

        if ($convType === 'dm') {
            $members = $this->conversationModel->getMembers($data['conversation_id']);
            foreach ($members as $member) {
                if ($member['id'] != $data['sender_id']) {
                    if ($this->userModel->isBlocked($data['sender_id'], $member['id']) ||
                        $this->userModel->isBlocked($member['id'], $data['sender_id'])) {
                        return [
                            'success' => false,
                            'error'   => 'Cannot send message. This user has blocked you or you have blocked them.',
                            'blocked' => true
                        ];
                    }
                }
            }
        }
        // Group: block check

        // Handle file upload
        if (isset($_FILES['file'])) {
            $fileData = $this->handleFileUpload($_FILES['file'], $data['type']);
            if ($fileData) {
                $data = array_merge($data, $fileData);
            } else {
                return ['success' => false, 'error' => 'File upload failed'];
            }
        }

        if ($data['type'] === 'text' && empty($data['body'])) {
            return ['success' => false, 'error' => 'Message body is required'];
        }

        $messageId = $this->messageModel->create($data);
        if ($messageId) {
            return ['success' => true, 'message_id' => $messageId];
        }
        return ['success' => false, 'error' => 'Failed to send message'];
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload($file, $type) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log('File upload error code: ' . $file['error']);
            return null;
        }
        $maxSize = 20 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            error_log('File too large: ' . $file['size'] . ' bytes');
            return null;
        }
        $allowedTypes = [
            'image' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'file'  => [
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
        $ext             = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validExtensions = [
            'jpg','jpeg','png','gif','webp',
            'pdf','doc','docx','xls','xlsx',
            'zip','rar','7z','txt','csv',
            'mp3','ogg','webm','wav','mp4'
        ];
        if (!in_array($ext, $validExtensions)) {
            error_log('Invalid file extension: ' . $ext);
            return null;
        }
        if (in_array($ext, ['zip','rar','7z'])) {
            if (!in_array($file['type'], [
                'application/zip','application/x-zip-compressed',
                'application/x-rar-compressed','application/octet-stream',
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
        $folderMap = ['image' => 'uploads', 'file' => 'uploads', 'voice' => 'voices'];
        $folder    = $folderMap[$type] ?? 'uploads';
        $targetDir = STORAGE_PATH . '/' . $folder;
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                error_log('Failed to create directory: ' . $targetDir);
                return null;
            }
        }
        $filename    = $type . '_' . uniqid() . '_' . time() . '.' . $ext;
        $destination = $targetDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
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

    public function searchUsers($query, $userId) {
        return $this->userModel->search($query, $userId);
    }

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

    public function getConversationDetails($conversationId, $userId) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return null;
        }
        return $this->conversationModel->getById($conversationId);
    }

    public function getConversationStats($conversationId, $userId) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return null;
        }
        try {
            return [
                'total_messages' => $this->messageModel->getConversationMessageCount($conversationId),
                'media_count'    => $this->messageModel->getMediaCount($conversationId),
                'file_count'     => $this->messageModel->getFileCount($conversationId),
                'members_count'  => $this->conversationModel->getMembersCount($conversationId)
            ];
        } catch (Exception $e) {
            error_log('Get stats error: ' . $e->getMessage());
            return null;
        }
    }

    public function searchMessages($conversationId, $userId, $query) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return [];
        }
        return $this->messageModel->searchInConversation($conversationId, $query);
    }

    public function getSharedMedia($conversationId, $userId, $type = 'image', $limit = 50) {
        if (!$this->conversationModel->isMember($conversationId, $userId)) {
            return [];
        }
        return $this->messageModel->getSharedMedia($conversationId, $type, $limit);
    }
}
