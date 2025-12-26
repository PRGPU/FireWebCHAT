<?php
/**
 * FireWeb Messenger - Application Configuration (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

return [
    // Application Settings
    'app_name' => 'FireWeb Messenger',
    'app_version' => '0.0.1',
    'app_url' => 'http://localhost',
    
    // Security Settings
    'encryption_key' => 'ulTCptam8dslpO6yzmg0UJLoUiP8kMVdHRyz2S50Ggk=', // Will be generated during setup
    'session_lifetime' => 86400, // 24 hours
    'csrf_token_lifetime' => 3600, // 1 hour
    
    // File Upload Settings
    'max_file_size' => 20 * 1024 * 1024, // 20MB
    'max_voice_duration' => 300, // 5 minutes
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'allowed_file_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'text/plain'
    ],
    
    // Messaging Settings
    'message_load_limit' => 50,
    'poll_interval' => 2000, // milliseconds
    'typing_timeout' => 5000, // milliseconds
    
    // User Settings
    'default_theme' => 'light',
    'max_avatar_size' => 5 * 1024 * 1024, // 5MB
    'username_min_length' => 3,
    'username_max_length' => 20,
    'password_min_length' => 6,
    
    // Feature Flags
    'enable_voice_messages' => true,
    'enable_video_messages' => true,
    'enable_file_sharing' => true,
    'enable_reactions' => true,
    'enable_message_editing' => false,
    'enable_message_deletion' => true,
    
    // Database Settings
    'database_path' => STORAGE_PATH . '/app.db',
    'enable_wal_mode' => true,
    
    // Logging
    'log_errors' => true,
    'error_log_path' => STORAGE_PATH . '/logs/error.log',
    
    // Localization
    'timezone' => 'UTC',
    'locale' => 'en_US',
    
    // API Settings
    'api_rate_limit' => 100, // requests per minute
    'enable_cors' => true,
    
    // PWA Settings
    'pwa_enabled' => true,
    'offline_mode' => true,
    
    // Maintenance
    'maintenance_mode' => false,
    'maintenance_message' => 'We are currently performing scheduled maintenance.',
];
