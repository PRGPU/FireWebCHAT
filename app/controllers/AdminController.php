<?php
/**
 * FireWeb Messenger - Admin Controller (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

class AdminController {
    private $db;
    private $userModel;
    private $conversationModel;
    private $messageModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->conversationModel = new Conversation();
        $this->messageModel = new Message();
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStatistics() {
        $stats = [
            'users' => [],
            'conversations' => 0,
            'messages' => 0,
            'storage' => [],
            'system' => []
        ];
        
        // User statistics (direct query)
        $conn = $this->db->getConnection();
        
        // Total users
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $totalUsers = $stmt->fetch()['count'];
        
        // Active users (last 7 days)
        $stmt = $conn->query("
            SELECT COUNT(*) as count FROM users 
            WHERE is_active = 1 
            AND datetime(last_seen) > datetime('now', '-7 days')
        ");
        $activeUsers = $stmt->fetch()['count'];
        
        // Online users (last 3 minutes)
        $stmt = $conn->query("
            SELECT COUNT(*) as count FROM users 
            WHERE is_active = 1 
            AND datetime(last_seen) > datetime('now', '-3 minutes')
        ");
        $onlineUsers = $stmt->fetch()['count'];
        
        $stats['users'] = [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'online' => $onlineUsers
        ];
        
        // Get all users (WITHOUT email and phone)
        $stmt = $conn->prepare("
            SELECT 
                id, username, display_name, 
                role, is_active, avatar, created_at, last_seen
            FROM users
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $stmt->execute();
        $stats['users']['list'] = $stmt->fetchAll();
        
        // Conversation count (direct query)
        $stmt = $conn->query("SELECT COUNT(*) as count FROM conversations");
        $stats['conversations'] = $stmt->fetch()['count'] ?? 0;
        
        // Message count (direct query)
        $stmt = $conn->query("SELECT COUNT(*) as count FROM messages WHERE is_deleted = 0");
        $stats['messages'] = $stmt->fetch()['count'] ?? 0;
        
        // Storage usage
        $stats['storage'] = $this->getStorageUsage();
        
        // System info
        $stats['system'] = $this->getSystemInfo();
        
        return $stats;
    }
    
    /**
     * Get storage usage
     */
    private function getStorageUsage() {
        $folders = [
            'uploads' => STORAGE_PATH . '/uploads',
            'avatars' => STORAGE_PATH . '/avatars',
            'voices' => STORAGE_PATH . '/voices',
            'database' => STORAGE_PATH . '/app.db'
        ];
        
        $usage = [];
        $totalSize = 0;
        
        foreach ($folders as $name => $path) {
            if (is_file($path)) {
                $size = filesize($path);
            } elseif (is_dir($path)) {
                $size = $this->getFolderSize($path);
            } else {
                $size = 0;
            }
            
            $usage[$name] = [
                'size' => $size,
                'formatted' => $this->formatBytes($size)
            ];
            
            $totalSize += $size;
        }
        
        $usage['total'] = [
            'size' => $totalSize,
            'formatted' => $this->formatBytes($totalSize)
        ];
        
        return $usage;
    }
    
    /**
     * Get folder size recursively
     */
    private function getFolderSize($path) {
        $size = 0;
        
        if (!is_dir($path)) {
            return 0;
        }
        
        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (Exception $e) {
            error_log('Folder size error: ' . $e->getMessage());
        }
        
        return $size;
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2) {
        if ($bytes <= 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get system information
     */
    private function getSystemInfo() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'max_upload_size' => ini_get('upload_max_filesize'),
            'max_post_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'extensions' => [
                'pdo_sqlite' => extension_loaded('pdo_sqlite'),
                'gd' => extension_loaded('gd'),
                'mbstring' => extension_loaded('mbstring'),
                'sodium' => extension_loaded('sodium')
            ]
        ];
    }
    
    /**
     * Get all users with pagination (WITHOUT email and phone)
     */
    public function getUsers($page = 1, $limit = 50) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        
        $stmt = $conn->prepare("
            SELECT 
                id, username, display_name, 
                role, is_active, avatar, created_at, last_seen
            FROM users
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Toggle user status (direct query)
     */
    public function toggleUserStatus($userId) {
        // Don't allow deactivating yourself
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Cannot deactivate your own account'];
        }
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("
            UPDATE users 
            SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END 
            WHERE id = :user_id
        ");
        
        $result = $stmt->execute([':user_id' => $userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'User status updated'];
        }
        
        return ['success' => false, 'error' => 'Failed to update user status'];
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        // Don't allow deleting yourself
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Cannot delete your own account'];
        }
        
        try {
            $conn = $this->db->getConnection();
            $conn->beginTransaction();
            
            // Delete user's messages (soft delete)
            $stmt = $conn->prepare("
                UPDATE messages 
                SET is_deleted = 1 
                WHERE sender_id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            
            // Remove from conversation members
            $stmt = $conn->prepare("
                DELETE FROM conversation_members 
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            
            // Delete owned conversations
            $stmt = $conn->prepare("
                DELETE FROM conversations 
                WHERE owner_id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            
            // Delete user
            $stmt = $conn->prepare("
                DELETE FROM users 
                WHERE id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            
            $conn->commit();
            
            return ['success' => true, 'message' => 'User deleted successfully'];
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('Delete user error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete user'];
        }
    }
    
    /**
     * Update user role
     */
    public function updateUserRole($userId, $role) {
        if (!in_array($role, ['user', 'admin'])) {
            return ['success' => false, 'error' => 'Invalid role'];
        }
        
        // Don't allow changing your own role
        if ($userId == $_SESSION['user_id']) {
            return ['success' => false, 'error' => 'Cannot change your own role'];
        }
        
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                UPDATE users 
                SET role = :role 
                WHERE id = :user_id
            ");
            
            $result = $stmt->execute([
                ':role' => $role,
                ':user_id' => $userId
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'User role updated'];
            }
            
            return ['success' => false, 'error' => 'Failed to update role'];
        } catch (Exception $e) {
            error_log('Update role error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update role'];
        }
    }
    
    /**
     * Create database backup
     */
    public function backupDatabase() {
        try {
            $sourceFile = STORAGE_PATH . '/app.db';
            $backupDir = STORAGE_PATH . '/backups';
            
            // Create backups directory if not exists
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.db';
            
            if (copy($sourceFile, $backupFile)) {
                return [
                    'success' => true,
                    'file' => basename($backupFile),
                    'size' => $this->formatBytes(filesize($backupFile)),
                    'path' => $backupFile
                ];
            }
            
            return ['success' => false, 'error' => 'Backup failed'];
        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Backup failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get recent backups
     */
    public function getBackups() {
        $backupDir = STORAGE_PATH . '/backups';
        $backups = [];
        
        if (!is_dir($backupDir)) {
            return $backups;
        }
        
        $files = glob($backupDir . '/backup_*.db');
        rsort($files);
        
        foreach (array_slice($files, 0, 10) as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file
            ];
        }
        
        return $backups;
    }
    
    /**
     * Delete backup
     */
    public function deleteBackup($filename) {
        $backupFile = STORAGE_PATH . '/backups/' . basename($filename);
        
        if (file_exists($backupFile) && unlink($backupFile)) {
            return ['success' => true, 'message' => 'Backup deleted'];
        }
        
        return ['success' => false, 'error' => 'Failed to delete backup'];
    }
    
    /**
     * Clean old data
     */
    public function cleanOldData($days = 30) {
        try {
            $conn = $this->db->getConnection();
            $conn->beginTransaction();
            
            // Delete old deleted messages
            $stmt = $conn->prepare("
                DELETE FROM messages 
                WHERE is_deleted = 1 
                AND datetime(created_at) < datetime('now', '-' || :days || ' days')
            ");
            $stmt->execute([':days' => $days]);
            $deletedCount = $stmt->rowCount();
            
            // Clean up orphaned files
            $this->cleanOrphanedFiles();
            
            $conn->commit();
            
            return [
                'success' => true,
                'message' => 'Cleanup completed',
                'deleted_messages' => $deletedCount
            ];
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('Cleanup error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Cleanup failed'];
        }
    }
    
    /**
     * Clean orphaned files
     */
    private function cleanOrphanedFiles() {
        $folders = ['uploads', 'avatars', 'voices'];
        $conn = $this->db->getConnection();
        
        foreach ($folders as $folder) {
            $path = STORAGE_PATH . '/' . $folder;
            
            if (!is_dir($path)) {
                continue;
            }
            
            $files = glob($path . '/*');
            
            foreach ($files as $file) {
                $filename = $folder . '/' . basename($file);
                
                // Check if file is referenced in database
                if ($folder === 'avatars') {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE avatar = :filename");
                } else {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE file_path = :filename");
                }
                
                $stmt->execute([':filename' => $filename]);
                $result = $stmt->fetch();
                
                // Delete if not referenced
                if ($result['count'] == 0) {
                    @unlink($file);
                }
            }
        }
    }
    
    /**
     * Get activity logs
     */
    public function getActivityLogs($limit = 100) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT 
                    u.username,
                    u.display_name,
                    m.created_at,
                    m.type,
                    c.type as conversation_type
                FROM messages m
                INNER JOIN users u ON m.sender_id = u.id
                INNER JOIN conversations c ON m.conversation_id = c.id
                WHERE m.is_deleted = 0
                ORDER BY m.created_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Activity logs error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export statistics to CSV
     */
    public function exportStatistics() {
        $stats = $this->getStatistics();
        
        $csv = "FireWeb Messenger Statistics\n";
        $csv .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $csv .= "Users\n";
        $csv .= "Total," . $stats['users']['total'] . "\n";
        $csv .= "Active," . $stats['users']['active'] . "\n";
        $csv .= "Online," . $stats['users']['online'] . "\n\n";
        
        $csv .= "Conversations," . $stats['conversations'] . "\n";
        $csv .= "Messages," . $stats['messages'] . "\n\n";
        
        $csv .= "Storage\n";
        foreach ($stats['storage'] as $key => $value) {
            $csv .= ucfirst($key) . "," . $value['formatted'] . "\n";
        }
        
        return $csv;
    }
}
