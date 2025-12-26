<?php
/**
 * FireWeb Messenger - User Model (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

class User {
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log('User model init error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, display_name, phone, password_hash, avatar, role, bio)
                VALUES (:username, :display_name, :phone, :password_hash, :avatar, :role, :bio)
            ");
            
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $result = $stmt->execute([
                ':username' => strtolower(trim($data['username'])),
                ':display_name' => trim($data['display_name']),
                ':phone' => $data['phone'] ?? null,
                ':password_hash' => $passwordHash,
                ':avatar' => $data['avatar'] ?? null,
                ':role' => $data['role'] ?? 'user',
                ':bio' => $data['bio'] ?? null
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('User create error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE username = :username 
                AND is_active = 1
            ");
            
            $stmt->execute([':username' => strtolower(trim($username))]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Find user error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find user by phone
     */
    public function findByPhone($phone) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE phone = :phone 
                AND is_active = 1
            ");
            
            $stmt->execute([':phone' => trim($phone)]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Find user by phone error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, display_name, phone, bio, avatar, role, theme, last_seen, created_at
                FROM users 
                WHERE id = :id 
                AND is_active = 1
            ");
            
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Find user by ID error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search users (excluding blocked users)
     */
    public function search($query, $excludeUserId = null, $limit = 20) {
        try {
            $sql = "
                SELECT u.id, u.username, u.display_name, u.phone, u.avatar, u.bio
                FROM users u
                WHERE (
                    u.username LIKE :query 
                    OR u.display_name LIKE :query 
                    OR u.phone LIKE :query
                )
                AND u.is_active = 1
            ";
            
            $params = [':query' => "%$query%"];
            
            if ($excludeUserId) {
                $sql .= " AND u.id != :exclude_id";
                $params[':exclude_id'] = $excludeUserId;
                
                // Exclude blocked users
                $sql .= " AND u.id NOT IN (
                    SELECT blocked_user_id FROM blocked_users WHERE user_id = :exclude_id2
                )";
                $params[':exclude_id2'] = $excludeUserId;
                
                // Exclude users who blocked current user
                $sql .= " AND u.id NOT IN (
                    SELECT user_id FROM blocked_users WHERE blocked_user_id = :exclude_id3
                )";
                $params[':exclude_id3'] = $excludeUserId;
            }
            
            $sql .= " ORDER BY u.display_name ASC LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Search users error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['display_name', 'bio', 'avatar', 'phone', 'theme'];
            $updates = [];
            $params = [':id' => $userId];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updates[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Update profile error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last seen timestamp
     */
    public function updateLastSeen($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET last_seen = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            
            return $stmt->execute([':id' => $userId]);
        } catch (PDOException $e) {
            error_log('Update last seen error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Block user
     */
    public function blockUser($userId, $blockedUserId) {
        try {
            $stmt = $this->db->prepare("
                INSERT OR IGNORE INTO blocked_users (user_id, blocked_user_id)
                VALUES (:user_id, :blocked_user_id)
            ");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':blocked_user_id' => $blockedUserId
            ]);
        } catch (PDOException $e) {
            error_log('Block user error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unblock user
     */
    public function unblockUser($userId, $blockedUserId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM blocked_users 
                WHERE user_id = :user_id 
                AND blocked_user_id = :blocked_user_id
            ");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':blocked_user_id' => $blockedUserId
            ]);
        } catch (PDOException $e) {
            error_log('Unblock user error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is blocked
     */
    public function isBlocked($userId, $blockedUserId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM blocked_users 
                WHERE user_id = :user_id 
                AND blocked_user_id = :blocked_user_id
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':blocked_user_id' => $blockedUserId
            ]);
            
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log('Check blocked error: ' . $e->getMessage());
            return false;
        }
    }
}
