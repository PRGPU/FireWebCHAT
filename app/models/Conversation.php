<?php
/**
 * FireWeb Messenger - Conversation Model (Premium UI v1.0.0)
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
                       cm.joined_at, cm.role
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
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM conversation_members
                WHERE conversation_id = ?
            ");
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
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM conversation_members
                WHERE conversation_id = ? AND user_id = ?
            ");
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

    public function getUserConversations($userId, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    c.*,
                    0 as unread_count,
                    (SELECT body FROM messages m2
                     WHERE m2.conversation_id = c.id AND m2.is_deleted = 0
                     ORDER BY m2.created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages m3
                     WHERE m3.conversation_id = c.id AND m3.is_deleted = 0
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

    public function markAsRead($conversationId, $userId) {
        try {
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

    // ==================== PATCH B: Group Methods ====================

    public function createGroup($creatorId, $title, $description = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO conversations (type, title, description, owner_id)
                VALUES ('group', ?, ?, ?)
            ");
            $result = $stmt->execute([$title, $description, $creatorId]);
            if (!$result) return false;
            $convId = $this->db->lastInsertId();
            $stmt2 = $this->db->prepare("
                INSERT OR IGNORE INTO conversation_members (conversation_id, user_id, role)
                VALUES (?, ?, 'admin')
            ");
            $stmt2->execute([$convId, $creatorId]);
            return $convId;
        } catch (PDOException $e) {
            error_log('createGroup error: ' . $e->getMessage());
            return false;
        }
    }

    public function addMemberWithRole($conversationId, $userId, $role = 'member') {
        try {
            $stmt = $this->db->prepare("
                INSERT OR IGNORE INTO conversation_members (conversation_id, user_id, role)
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$conversationId, $userId, $role]);
        } catch (PDOException $e) {
            error_log('addMemberWithRole error: ' . $e->getMessage());
            return false;
        }
    }

    public function getMemberRole($conversationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT role FROM conversation_members
                WHERE conversation_id = ? AND user_id = ?
            ");
            $stmt->execute([$conversationId, $userId]);
            $row = $stmt->fetch();
            return $row ? $row['role'] : null;
        } catch (PDOException $e) {
            error_log('getMemberRole error: ' . $e->getMessage());
            return null;
        }
    }

    public function isAdmin($conversationId, $userId) {
        return $this->getMemberRole($conversationId, $userId) === 'admin';
    }

    public function getGroupInfo($conversationId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                       (SELECT COUNT(*) FROM conversation_members
                        WHERE conversation_id = c.id) as member_count
                FROM conversations c
                WHERE c.id = ? AND c.type = 'group'
            ");
            $stmt->execute([$conversationId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('getGroupInfo error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateGroup($conversationId, $title, $description = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE conversations SET title = ?, description = ?
                WHERE id = ? AND type = 'group'
            ");
            return $stmt->execute([$title, $description, $conversationId]);
        } catch (PDOException $e) {
            error_log('updateGroup error: ' . $e->getMessage());
            return false;
        }
    }

    // ==================== Channel Methods ====================

    public function createChannel($creatorId, $title, $description = null, $username = null) {
        try {
            $this->db->beginTransaction();

            if ($username) {
                $username = strtolower(trim($username));
                $stmt = $this->db->prepare("
                    SELECT id FROM conversations
                    WHERE group_username = ? LIMIT 1
                ");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $this->db->rollBack();
                    return ['success' => false, 'error' => 'Username already taken'];
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO conversations (type, title, description, owner_id, group_username)
                VALUES ('channel', ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $creatorId, $username]);
            $channelId = $this->db->lastInsertId();

            $stmt2 = $this->db->prepare("
                INSERT INTO conversation_members (conversation_id, user_id, role)
                VALUES (?, ?, 'admin')
            ");
            $stmt2->execute([$channelId, $creatorId]);

            $this->db->commit();
            return ['success' => true, 'channel_id' => $channelId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('createChannel error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error'];
        }
    }

    public function searchChannels($query, $excludeUserId = null, $limit = 20) {
        try {
            $sql = "
                SELECT c.*,
                    (SELECT COUNT(*) FROM conversation_members
                        WHERE conversation_id = c.id) as member_count,
                    " . ($excludeUserId ? "
                    (SELECT COUNT(*) FROM conversation_members
                        WHERE conversation_id = c.id AND user_id = :uid2) as is_member
                    " : "0 as is_member") . "
                FROM conversations c
                WHERE c.type = 'channel'
                AND (c.title LIKE :query OR c.group_username LIKE :query2)
                ORDER BY member_count DESC
                LIMIT :limit
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':query',  '%' . $query . '%', PDO::PARAM_STR);
            $stmt->bindValue(':query2', '%' . $query . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit',  $limit,             PDO::PARAM_INT);
            if ($excludeUserId) {
                $stmt->bindValue(':uid2', $excludeUserId, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('searchChannels error: ' . $e->getMessage());
            return [];
        }
    }

    public function canPostInChannel($conversationId, $userId) {
        $role = $this->getMemberRole($conversationId, $userId);
        return in_array($role, ['admin', 'owner']);
    }

    public function joinChannel($channelId, $userId) {
        try {
            $conv = $this->getById($channelId);
            if (!$conv || $conv['type'] !== 'channel') {
                return ['success' => false, 'error' => 'Channel not found'];
            }
            if ($this->isMember($channelId, $userId)) {
                return ['success' => true, 'already_member' => true];
            }
            $result = $this->addMemberWithRole($channelId, $userId, 'member');
            return ['success' => $result];
        } catch (PDOException $e) {
            error_log('joinChannel error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error'];
        }
    }
}
