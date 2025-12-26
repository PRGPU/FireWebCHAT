<?php
/**
 * FireWeb Messenger - Registration Page (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if classes exist
        if (!class_exists('AuthController')) {
            throw new Exception('AuthController class not found');
        }
        
        if (!class_exists('User')) {
            throw new Exception('User class not found');
        }
        
        if (!class_exists('Database')) {
            throw new Exception('Database class not found');
        }
        
        $authController = new AuthController();
        $result = $authController->register($_POST);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors = $result['errors'] ?? [$result['error'] ?? 'Registration failed'];
        }
    } catch (Exception $e) {
        error_log('Registration error: ' . $e->getMessage());
        $errors[] = 'Registration error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<!-- rest of the file remains the same -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FireWeb Messenger</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            overflow-y: auto;
        }
        
        .auth-container {
            width: 100%;
            max-width: 600px;
            animation: slideUp 0.5s ease;
        }
        
        .auth-box {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .auth-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .auth-header p {
            font-size: 15px;
            color: #64748b;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            color: #1e293b;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 6px;
            font-size: 13px;
            color: #64748b;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            animation: slideUp 0.3s ease;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }
        
        .auth-footer p {
            font-size: 14px;
            color: #64748b;
        }
        
        .auth-footer a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .auth-branding {
            text-align: center;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 640px) {
            .auth-box {
                padding: 32px 24px;
            }
            
            .auth-header h1 {
                font-size: 24px;
            }
            
            .logo-icon {
                width: 64px;
                height: 64px;
                font-size: 36px;
            }
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="logo-icon">🔥</div>
                <h1>Join FireWeb</h1>
                <p>Create your account and start chatting!</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <div style="margin-top: 12px;">
                        <a href="?route=login" class="btn">Go to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" class="auth-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" name="display_name" required 
                               placeholder="Your full name"
                               value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required 
                               placeholder="username"
                               pattern="[a-zA-Z0-9_]{3,20}"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <small>3-20 characters, letters, numbers and underscore only</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number (Optional)</label>
                        <input type="tel" name="phone" 
                               placeholder="+[country code][number]"
                               pattern="\+[0-9]{6,15}"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        <small>Format: +[country code][number]</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required 
                               placeholder="Minimum 6 characters" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required 
                               placeholder="Re-enter password" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Profile Picture (Optional)</label>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
                        <small>Max 5MB - JPG, PNG, WebP, GIF</small>
                    </div>
                    
                    <button type="submit" class="btn">Create Account</button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="?route=login">Login here</a></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="auth-branding">
            <p>Built with ❤️ by @Learn_launch (Alion)</p>
        </div>
    </div>
</body>
</html>
