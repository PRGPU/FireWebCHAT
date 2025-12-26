<?php
/**
 * FireWeb Messenger - Database Layer (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $dbPath;
    
    private function __construct() {
        $this->dbPath = STORAGE_PATH . '/app.db';
        
        try {
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // SQLite optimizations
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA synchronous = NORMAL');
            $this->pdo->exec('PRAGMA cache_size = -64000');
            $this->pdo->exec('PRAGMA temp_store = MEMORY');
            
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
    
    public function createTables() {
        $queries = [
            // Users Table
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL COLLATE NOCASE,
                display_name TEXT NOT NULL,
                phone TEXT,
                bio TEXT,
                avatar TEXT,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user' CHECK(role IN ('user', 'admin')),
                theme TEXT DEFAULT 'light' CHECK(theme IN ('light', 'dark')),
                is_active INTEGER DEFAULT 1,
                last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Conversations Table
            "CREATE TABLE IF NOT EXISTS conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL CHECK(type IN ('dm', 'group', 'saved')),
                title TEXT,
                avatar TEXT,
                owner_id INTEGER NOT NULL,
                last_message_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Conversation Members Table (✅ با last_read_message_id)
            "CREATE TABLE IF NOT EXISTS conversation_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                is_muted INTEGER DEFAULT 0,
                last_read_message_id INTEGER DEFAULT 0,
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(conversation_id, user_id)
            )",
            
            // Messages Table
            "CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                sender_id INTEGER NOT NULL,
                reply_to INTEGER,
                type TEXT NOT NULL CHECK(type IN ('text', 'image', 'file', 'voice', 'video')),
                body TEXT,
                encrypted_body TEXT,
                nonce TEXT,
                file_path TEXT,
                file_name TEXT,
                file_size INTEGER,
                mime_type TEXT,
                thumbnail TEXT,
                duration INTEGER,
                is_starred INTEGER DEFAULT 0,
                is_deleted INTEGER DEFAULT 0,
                is_edited INTEGER DEFAULT 0,
                deleted_at DATETIME,
                edited_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reply_to) REFERENCES messages(id) ON DELETE SET NULL
            )",
            
            // Message Status Table
            "CREATE TABLE IF NOT EXISTS message_status (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                message_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                status TEXT DEFAULT 'sent' CHECK(status IN ('sent', 'delivered', 'seen')),
                status_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(message_id, user_id)
            )",
            
            // Reactions Table
            "CREATE TABLE IF NOT EXISTS reactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                message_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                emoji TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(message_id, user_id, emoji)
            )",
            
            // Contacts Table
            "CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                contact_id INTEGER NOT NULL,
                added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (contact_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(user_id, contact_id)
            )",
            
            // Blocked Users Table
            "CREATE TABLE IF NOT EXISTS blocked_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                blocked_user_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(user_id, blocked_user_id)
            )",
            
            // Typing Status Table
            "CREATE TABLE IF NOT EXISTS typing_status (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(conversation_id, user_id)
            )",
            
            // Settings Table
            "CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT UNIQUE NOT NULL,
                value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Performance Indexes
            "CREATE INDEX IF NOT EXISTS idx_users_username ON users(username)",
            "CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone)",
            "CREATE INDEX IF NOT EXISTS idx_users_last_seen ON users(last_seen)",
            "CREATE INDEX IF NOT EXISTS idx_messages_conversation ON messages(conversation_id, created_at DESC)",
            "CREATE INDEX IF NOT EXISTS idx_messages_sender ON messages(sender_id)",
            "CREATE INDEX IF NOT EXISTS idx_messages_deleted ON messages(is_deleted, created_at DESC)",
            "CREATE INDEX IF NOT EXISTS idx_conversation_members_user ON conversation_members(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_conversation_members_conv ON conversation_members(conversation_id)",
            "CREATE INDEX IF NOT EXISTS idx_conversation_members_last_read ON conversation_members(conversation_id, last_read_message_id)",
            "CREATE INDEX IF NOT EXISTS idx_message_status ON message_status(message_id, user_id)",
            "CREATE INDEX IF NOT EXISTS idx_conversations_updated ON conversations(last_message_at DESC)",
            "CREATE INDEX IF NOT EXISTS idx_blocked_users_user ON blocked_users(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_blocked_users_blocked ON blocked_users(blocked_user_id)",
            "CREATE INDEX IF NOT EXISTS idx_contacts_user ON contacts(user_id)",
            
            // Trigger: Update conversation timestamp on new message
            "CREATE TRIGGER IF NOT EXISTS update_conversation_timestamp
             AFTER INSERT ON messages
             BEGIN
                 UPDATE conversations 
                 SET last_message_at = CURRENT_TIMESTAMP 
                 WHERE id = NEW.conversation_id;
             END",
            
            // Trigger: Clean up old typing status (older than 10 seconds)
            "CREATE TRIGGER IF NOT EXISTS cleanup_typing_status
             AFTER INSERT ON typing_status
             BEGIN
                 DELETE FROM typing_status 
                 WHERE updated_at < datetime('now', '-10 seconds');
             END"
        ];
        
        foreach ($queries as $query) {
            try {
                $this->pdo->exec($query);
            } catch (PDOException $e) {
                error_log('Create table error: ' . $e->getMessage());
                throw $e;
            }
        }
        
        // ✅ Migrate existing database to add last_read_message_id if missing
        $this->migrateDatabase();
        
        // Insert default settings
        $this->insertDefaultSettings();
        
        return true;
    }
    
    /**
     * ✅ Migrate existing databases to add new columns
     */
    private function migrateDatabase() {
        try {
            // Check if last_read_message_id exists
            $result = $this->pdo->query("PRAGMA table_info(conversation_members)")->fetchAll();
            $hasLastRead = false;
            
            foreach ($result as $column) {
                if ($column['name'] === 'last_read_message_id') {
                    $hasLastRead = true;
                    break;
                }
            }
            
            // Add column if not exists
            if (!$hasLastRead) {
                $this->pdo->exec("ALTER TABLE conversation_members ADD COLUMN last_read_message_id INTEGER DEFAULT 0");
                error_log('✅ Migration: Added last_read_message_id to conversation_members');
            }
            
        } catch (PDOException $e) {
            // Column might already exist or table doesn't exist yet
            error_log('Migration note: ' . $e->getMessage());
        }
    }
    
    private function insertDefaultSettings() {
        $defaultSettings = [
            ['app_name', 'FireWeb Messenger'],
            ['app_version', '4.0.0'],
            ['max_file_size', '20971520'], // 20MB
            ['max_voice_duration', '300'], // 5 minutes
            ['allow_registration', '1'],
            ['enable_notifications', '1']
        ];
        
        try {
            $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
        } catch (PDOException $e) {
            error_log('Insert settings error: ' . $e->getMessage());
        }
    }
    
    public function getStats() {
        $stats = [];
        
        try {
            // Total users
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
            $stats['users'] = $stmt->fetch()['count'];
            
            // Total conversations
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM conversations");
            $stats['conversations'] = $stmt->fetch()['count'];
            
            // Total messages
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM messages WHERE is_deleted = 0");
            $stats['messages'] = $stmt->fetch()['count'];
            
            // Database size
            if (file_exists($this->dbPath)) {
                $stats['db_size'] = filesize($this->dbPath);
                $stats['db_size_formatted'] = $this->formatBytes($stats['db_size']);
            }
            
            // Active users (last 5 minutes)
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE datetime(last_seen) > datetime('now', '-5 minutes')
                AND is_active = 1
            ");
            $stats['active_users'] = $stmt->fetch()['count'];
            
            // Total unread messages (across all users)
            $stmt = $this->pdo->query("
                SELECT COUNT(DISTINCT m.id) as count
                FROM messages m
                INNER JOIN conversation_members cm ON m.conversation_id = cm.conversation_id
                WHERE m.is_deleted = 0
                AND m.sender_id != cm.user_id
                AND (cm.last_read_message_id IS NULL OR m.id > cm.last_read_message_id)
            ");
            $stats['unread_messages'] = $stmt->fetch()['count'];
            
        } catch (PDOException $e) {
            error_log('Get stats error: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    public function vacuum() {
        try {
            $this->pdo->exec('VACUUM');
            error_log('✅ Database vacuumed successfully');
            return true;
        } catch (PDOException $e) {
            error_log('Vacuum error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function backup($backupPath) {
        try {
            // Simple file copy backup (more reliable than SQL ATTACH)
            if (!file_exists($this->dbPath)) {
                throw new Exception('Source database not found');
            }
            
            // Close WAL files
            $this->pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
            
            // Copy database file
            if (copy($this->dbPath, $backupPath)) {
                error_log('✅ Database backed up to: ' . $backupPath);
                return true;
            }
            
            throw new Exception('Failed to copy database file');
            
        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get database integrity check
     */
    public function checkIntegrity() {
        try {
            $result = $this->pdo->query("PRAGMA integrity_check")->fetch();
            return $result['integrity_check'] === 'ok';
        } catch (PDOException $e) {
            error_log('Integrity check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Optimize database
     */
    public function optimize() {
        try {
            $this->pdo->exec('PRAGMA optimize');
            $this->pdo->exec('ANALYZE');
            error_log('✅ Database optimized');
            return true;
        } catch (PDOException $e) {
            error_log('Optimize error: ' . $e->getMessage());
            return false;
        }
    }
}
