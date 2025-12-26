<?php
/**
 * FireWeb Messenger - Auth Controller (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        $errors = [];
        
        // Validate display name
        if (empty($data['display_name'])) {
            $errors[] = 'Display name is required';
        } elseif (strlen($data['display_name']) < 2) {
            $errors[] = 'Display name must be at least 2 characters';
        } elseif (strlen($data['display_name']) > 50) {
            $errors[] = 'Display name must not exceed 50 characters';
        }
        
        // Validate username
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
            $errors[] = 'Username must be 3-20 characters (letters, numbers, underscore only)';
        } else {
            // Check if username exists
            $existingUser = $this->userModel->findByUsername($data['username']);
            if ($existingUser) {
                $errors[] = 'Username already exists';
            }
        }
        
        // Validate phone (optional)
        if (!empty($data['phone'])) {
            if (!preg_match('/^\+[0-9]{6,15}$/', $data['phone'])) {
                $errors[] = 'Invalid phone number format. Use: +[country code][number]';
            } else {
                // Check if phone exists
                $existingPhone = $this->userModel->findByPhone($data['phone']);
                if ($existingPhone) {
                    $errors[] = 'Phone number already registered';
                }
            }
        }
        
        // Validate password
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } elseif (strlen($data['password']) > 128) {
            $errors[] = 'Password is too long (max 128 characters)';
        }
        
        // Validate password confirmation
        if (empty($data['confirm_password'])) {
            $errors[] = 'Password confirmation is required';
        } elseif ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        // Return errors if any
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Handle avatar upload
        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarResult = $this->handleAvatarUpload($_FILES['avatar']);
            if ($avatarResult['success']) {
                $avatarPath = $avatarResult['path'];
            } else {
                return ['success' => false, 'error' => $avatarResult['error']];
            }
        }
        
        // Prepare user data
        $userData = [
            'username' => strtolower(trim($data['username'])),
            'display_name' => trim($data['display_name']),
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'password' => $data['password'],
            'avatar' => $avatarPath,
            'role' => 'user'
        ];
        
        // Create user
        $userId = $this->userModel->create($userData);
        
        if ($userId) {
            // Log registration
            error_log("New user registered: ID=$userId, Username={$userData['username']}");
            
            return [
                'success' => true,
                'message' => 'Registration successful! You can now login.',
                'user_id' => $userId
            ];
        }
        
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        // Validate input
        if (empty($username)) {
            return ['success' => false, 'error' => 'Username is required'];
        }
        
        if (empty($password)) {
            return ['success' => false, 'error' => 'Password is required'];
        }
        
        // Find user
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            // Don't reveal if username exists
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Account is disabled. Please contact support.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Log failed attempt
            error_log("Failed login attempt for username: $username");
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Update last seen
        $this->userModel->updateLastSeen($user['id']);
        
        // Log successful login
        error_log("User logged in: ID={$user['id']}, Username={$user['username']}");
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role']
            ]
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Update last seen before logout
        if (isset($_SESSION['user_id'])) {
            $this->userModel->updateLastSeen($_SESSION['user_id']);
            error_log("User logged out: ID={$_SESSION['user_id']}");
        }
        
        // Clear session
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return ['success' => true];
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isAuthenticated() && 
               isset($_SESSION['role']) && 
               $_SESSION['role'] === 'admin';
    }
    
    /**
     * Handle avatar upload
     */
    private function handleAvatarUpload($file) {
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return [
                'success' => false, 
                'error' => 'Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.'
            ];
        }
        
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'File is too large. Maximum size is 5MB.'
            ];
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . uniqid() . '_' . time() . '.' . $ext;
        $destination = STORAGE_PATH . '/avatars/' . $filename;
        
        // Create avatars directory if not exists
        if (!is_dir(STORAGE_PATH . '/avatars')) {
            mkdir(STORAGE_PATH . '/avatars', 0755, true);
        }
        
        // Resize and save image
        try {
            $resized = $this->resizeImage($file['tmp_name'], $file['type'], 400, 400);
            
            if ($resized) {
                $saved = false;
                
                switch ($file['type']) {
                    case 'image/jpeg':
                        $saved = imagejpeg($resized, $destination, 90);
                        break;
                    case 'image/png':
                        $saved = imagepng($resized, $destination, 8);
                        break;
                    case 'image/webp':
                        $saved = imagewebp($resized, $destination, 90);
                        break;
                    case 'image/gif':
                        $saved = imagegif($resized, $destination);
                        break;
                }
                
                imagedestroy($resized);
                
                if ($saved) {
                    return [
                        'success' => true,
                        'path' => 'avatars/' . $filename
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('Avatar upload error: ' . $e->getMessage());
        }
        
        return [
            'success' => false,
            'error' => 'Failed to process image. Please try again.'
        ];
    }
    
    /**
     * Resize image
     */
    private function resizeImage($sourcePath, $mimeType, $maxWidth, $maxHeight) {
        // Load source image
        $source = null;
        
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($sourcePath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return null;
        }
        
        if (!$source) {
            return null;
        }
        
        // Get original dimensions
        $origWidth = imagesx($source);
        $origHeight = imagesy($source);
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
        $newWidth = (int)($origWidth * $ratio);
        $newHeight = (int)($origHeight * $ratio);
        
        // Create new image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        
        // Resize
        imagecopyresampled(
            $resized, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        );
        
        imagedestroy($source);
        
        return $resized;
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        $errors = [];
        
        // Get user
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $result = $this->userModel->updateProfile($userId, ['password_hash' => $passwordHash]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        }
        
        return ['success' => false, 'error' => 'Failed to change password'];
    }
    
    /**
     * Validate session
     */
    public function validateSession() {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Check session timeout (24 hours)
        if (isset($_SESSION['login_time'])) {
            $sessionAge = time() - $_SESSION['login_time'];
            if ($sessionAge > 86400) { // 24 hours
                $this->logout();
                return false;
            }
        }
        
        return true;
    }
}
