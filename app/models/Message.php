<?php
/**
 * FireWeb Messenger - Message Model (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */
class Message {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, u.display_name, u.avatar 
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.id = :id
            ");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get message by ID error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO messages (conversation_id, sender_id, type, body, file_path, file_name, file_size, duration) 
                VALUES (:conversation_id, :sender_id, :type, :body, :file_path, :file_name, :file_size, :duration)
            ");
            $result = $stmt->execute([
                ':conversation_id' => $data['conversation_id'],
                ':sender_id' => $data['sender_id'],
                ':type' => $data['type'] ?? 'text',
                ':body' => $data['body'] ?? null,
                ':file_path' => $data['file_path'] ?? null,
                ':file_name' => $data['file_name'] ?? null,
                ':file_size' => $data['file_size'] ?? null,
                ':duration' => $data['duration'] ?? null
            ]);
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log('Create message error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $body) {
        try {
            $stmt = $this->db->prepare("
                UPDATE messages 
                SET body = :body, is_edited = 1, edited_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $id, ':body' => $body]);
        } catch (PDOException $e) {
            error_log('Update message error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function edit($messageId, $userId, $newBody) {
        try {
            // Check if user owns the message
            $message = $this->getById($messageId);
            if (!$message || $message['sender_id'] != $userId) {
                return false;
            }
            return $this->update($messageId, $newBody);
        } catch (PDOException $e) {
            error_log('Edit message error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($messageId, $userId = null) {
        try {
            if ($userId) {
                // Check if user owns the message
                $message = $this->getById($messageId);
                if (!$message || $message['sender_id'] != $userId) {
                    return false;
                }
            }
            $stmt = $this->db->prepare("DELETE FROM messages WHERE id = :id");
            return $stmt->execute([':id' => $messageId]);
        } catch (PDOException $e) {
            error_log('Delete message error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByConversation($conversationId, $limit = 50, $beforeId = null) {
        try {
            $sql = "
                SELECT m.*, u.display_name, u.avatar 
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = :conversation_id
            ";
            if ($beforeId) {
                $sql .= " AND m.id < :before_id";
            }
            $sql .= " ORDER BY m.created_at DESC LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
            if ($beforeId) {
                $stmt->bindValue(':before_id', $beforeId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll();
            return array_reverse($messages);
        } catch (PDOException $e) {
            error_log('Get messages error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getConversationMessages($conversationId, $limit = 50) {
        return $this->getByConversation($conversationId, $limit);
    }
    
    public function getNewMessages($conversationId, $afterId) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, u.display_name, u.avatar 
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = :conversation_id
                AND m.id > :after_id
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([':conversation_id' => $conversationId, ':after_id' => $afterId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get new messages error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getConversationMessageCount($conversationId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = :conversation_id");
            $stmt->execute([':conversation_id' => $conversationId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get message count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getMediaCount($conversationId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = :conversation_id AND type = 'image'");
            $stmt->execute([':conversation_id' => $conversationId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get media count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getFileCount($conversationId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = :conversation_id AND type = 'file'");
            $stmt->execute([':conversation_id' => $conversationId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get file count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getSharedMedia($conversationId, $type = 'image', $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM messages 
                WHERE conversation_id = :conversation_id AND type = :type 
                ORDER BY created_at DESC LIMIT :limit
            ");
            $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get shared media error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function searchInConversation($conversationId, $query) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, u.display_name, u.avatar 
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = :conversation_id AND m.body LIKE :query
                ORDER BY m.created_at DESC LIMIT 50
            ");
            $stmt->execute([':conversation_id' => $conversationId, ':query' => '%' . $query . '%']);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Search messages error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateStatus($messageId, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE messages SET status = :status WHERE id = :id");
            return $stmt->execute([':id' => $messageId, ':status' => $status]);
        } catch (PDOException $e) {
            error_log('Update message status error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTotalCount() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM messages");
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get total count error: ' . $e->getMessage());
            return 0;
        }
    }
}
