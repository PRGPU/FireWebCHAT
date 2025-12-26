<?php
/**
 * FireWeb Messenger - Conversation Model (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */
class Conversation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM conversations WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Get conversation by ID error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function create($type, $creatorId, $title = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO conversations (type, title, owner_id) 
                VALUES (?, ?, ?)
            ");
            $result = $stmt->execute([$type, $title, $creatorId]);
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log('Create conversation error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function addMember($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT OR IGNORE INTO conversation_members (conversation_id, user_id) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$conversationId, $userId]);
        } catch (PDOException $e) {
            error_log('Add member error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function removeMember($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM conversation_members 
                WHERE conversation_id = ? AND user_id = ?
            ");
            return $stmt->execute([$conversationId, $userId]);
        } catch (PDOException $e) {
            error_log('Remove member error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getMembers($conversationId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, u.display_name, u.avatar, u.bio, u.phone, u.last_seen,
                       cm.joined_at
                FROM conversation_members cm
                INNER JOIN users u ON cm.user_id = u.id
                WHERE cm.conversation_id = ?
                ORDER BY cm.joined_at
            ");
            $stmt->execute([$conversationId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get members error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getMembersCount($conversationId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM conversation_members WHERE conversation_id = ?");
            $stmt->execute([$conversationId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get members count error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function isMember($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM conversation_members WHERE conversation_id = ? AND user_id = ?");
            $stmt->execute([$conversationId, $userId]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log('Check member error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function findDirectConversation($userId1, $userId2) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.* FROM conversations c
                INNER JOIN conversation_members cm1 ON c.id = cm1.conversation_id
                INNER JOIN conversation_members cm2 ON c.id = cm2.conversation_id
                WHERE c.type = 'dm'
                AND cm1.user_id = ?
                AND cm2.user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$userId1, $userId2]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Find direct conversation error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user conversations (unread_count always 0 for simplicity)
     */
    public function getUserConversations($userId, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    0 as unread_count,
                    (SELECT body FROM messages m2 
                     WHERE m2.conversation_id = c.id 
                     AND m2.is_deleted = 0
                     ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages m3 
                     WHERE m3.conversation_id = c.id 
                     AND m3.is_deleted = 0
                     ORDER BY m3.created_at DESC LIMIT 1) as last_message_time
                FROM conversations c
                INNER JOIN conversation_members cm ON c.id = cm.conversation_id
                WHERE cm.user_id = ?
                ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Get user conversations error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateTitle($conversationId, $title) {
        try {
            $stmt = $this->db->prepare("UPDATE conversations SET title = ? WHERE id = ?");
            return $stmt->execute([$title, $conversationId]);
        } catch (PDOException $e) {
            error_log('Update title error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($conversationId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM conversations WHERE id = ?");
            return $stmt->execute([$conversationId]);
        } catch (PDOException $e) {
            error_log('Delete conversation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark conversation as read (simplified - only updates timestamp)
     */
    public function markAsRead($conversationId, $userId) {
        try {
            // In SQLite, only the timestamp is updated for now.
            // For full read tracking, a separate table is required.
            $stmt = $this->db->prepare("
                UPDATE conversation_members 
                SET joined_at = datetime('now')
                WHERE conversation_id = ? AND user_id = ?
            ");
            return $stmt->execute([$conversationId, $userId]);
        } catch (PDOException $e) {
            error_log('Mark as read error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread count (always 0 for simplicity)
     */
    public function getUnreadCount($conversationId, $userId) {
        return 0;
    }
    
    public function getTotalCount() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM conversations");
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get total count error: ' . $e->getMessage());
            return 0;
        }
    }
}
