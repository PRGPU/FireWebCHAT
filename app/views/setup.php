<?php
/**
 * FireWeb Messenger - Setup Wizard (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

// Verify Database class is loaded
if (!class_exists('Database')) {
    die('Fatal Error: Database class not found. Please ensure the application is properly configured.');
}

if (file_exists(CONFIG_PATH . '/setup.lock')) {
    die('Setup has already been completed. Delete setup.lock to re-run.');
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Check requirements
        $requirements = [
            'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'PDO SQLite' => extension_loaded('pdo_sqlite'),
            'GD Library' => extension_loaded('gd'),
            'Mbstring' => extension_loaded('mbstring'),
            'Sodium' => extension_loaded('sodium')
        ];
        
        $allPassed = !in_array(false, $requirements, true);
        
        if ($allPassed) {
            header('Location: ?route=setup&step=2');
            exit;
        } else {
            $errors[] = 'Some requirements are not met';
        }
    }
    
    if ($step == 2) {
        // Create database and tables
        try {
            $dbPath = STORAGE_PATH . '/app.db';
            
            // Create storage directory if it doesn't exist
            if (!is_dir(STORAGE_PATH)) {
                mkdir(STORAGE_PATH, 0755, true);
            }
            
            // Create subdirectories only if they don't exist
            $subdirs = ['uploads', 'avatars', 'voices'];
            foreach ($subdirs as $dir) {
                $path = STORAGE_PATH . '/' . $dir;
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
            }
            
            // Create database
            $db = Database::getInstance();
            $db->createTables();
            
            // Set permissions on database file
            if (file_exists($dbPath)) {
                @chmod($dbPath, 0600);
            }
            
            // Generate encryption key
            $encryptionKey = sodium_crypto_secretbox_keygen();
            $encryptionKeyBase64 = base64_encode($encryptionKey);
            
            // Update config file
            $configPath = CONFIG_PATH . '/app.php';
            if (file_exists($configPath)) {
                $configTemplate = file_get_contents($configPath);
                $configTemplate = str_replace('%%ENCRYPTION_KEY%%', $encryptionKeyBase64, $configTemplate);
                file_put_contents($configPath, $configTemplate);
            }
            
            header('Location: ?route=setup&step=3');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database creation failed: ' . $e->getMessage();
        }
    }
    
    if ($step == 3) {
        // Create admin user
        $username = trim($_POST['username'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($displayName) || empty($password)) {
            $errors[] = 'All fields are required';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        } else {
            try {
                require_once APP_PATH . '/models/User.php';
                require_once APP_PATH . '/models/Conversation.php';
                
                $userModel = new User();
                $userModel->create([
                    'username' => $username,
                    'display_name' => $displayName,
                    'password' => $password,
                    'role' => 'admin',
                    'phone' => null,
                    'avatar' => null
                ]);
                
                // Get created user
                $user = $userModel->findByUsername($username);
                
                // Create "Saved Messages" conversation
                if ($user) {
                    $convModel = new Conversation();
                    $convId = $convModel->create('saved', $user['id'], 'Saved Messages');
                    $convModel->addMember($convId, $user['id']);
                }
                
                // Create lock file
                file_put_contents(CONFIG_PATH . '/setup.lock', date('Y-m-d H:i:s'));
                
                $success = true;
            } catch (Exception $e) {
                $errors[] = 'Failed to create admin user: ' . $e->getMessage();
            }
        }
    }
}

// Get requirements for step 1
$requirements = [];
if ($step == 1) {
    $requirements = [
        'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO SQLite' => extension_loaded('pdo_sqlite'),
        'GD Library' => extension_loaded('gd'),
        'Mbstring' => extension_loaded('mbstring'),
        'Sodium' => extension_loaded('sodium')
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - FireWeb Messenger</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            animation: slideUp 0.5s ease;
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
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9em;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #999;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            transform: scale(1.2);
        }
        
        .step.completed {
            background: #4caf50;
            color: white;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        
        .requirement {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f5f5f5;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .requirement:hover {
            background: #ececec;
        }
        
        .requirement.pass {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
        }
        
        .requirement.fail {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #4caf50;
        }
        
        .success h3 {
            margin-bottom: 10px;
            font-size: 1.5em;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9em;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #f5576c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <h1>🔥 FireWeb</h1>
            <p>Setup Wizard</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">2</div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?>">3</div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
            <h2>System Requirements</h2>
            <div class="info-box">
                Checking your server configuration...
            </div>
            <form method="POST">
                <?php foreach ($requirements as $name => $status): ?>
                    <div class="requirement <?= $status ? 'pass' : 'fail' ?>">
                        <span><?= $name ?></span>
                        <span><?= $status ? '✓ Passed' : '✗ Failed' ?></span>
                    </div>
                <?php endforeach; ?>
                
                <?php
                $allPassed = !in_array(false, $requirements, true);
                ?>
                
                <button type="submit" class="btn" <?= !$allPassed ? 'disabled' : '' ?> style="margin-top: 20px;">
                    <?= $allPassed ? 'Continue to Database Setup' : 'Cannot Continue - Fix Requirements' ?>
                </button>
            </form>
            
        <?php elseif ($step == 2): ?>
            <h2>Database Setup</h2>
            <div class="info-box">
                The system will create the SQLite database and all necessary tables.
            </div>
            <form method="POST">
                <button type="submit" class="btn">
                    Create Database & Tables
                </button>
            </form>
            
        <?php elseif ($step == 3 && !$success): ?>
            <h2>Create Admin Account</h2>
            <div class="info-box">
                Create your administrator account to manage FireWeb Messenger.
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="admin" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" required placeholder="Administrator"
                           value="<?= htmlspecialchars($_POST['display_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••" minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" minlength="6">
                </div>
                
                <button type="submit" class="btn">
                    Complete Setup
                </button>
            </form>
            
        <?php elseif ($success): ?>
            <div class="success">
                <h3>🎉 Setup Complete!</h3>
                <p>FireWeb Messenger has been successfully installed.</p>
                <p style="margin-top: 10px;">You can now login with your admin account.</p>
                <a href="?route=login" class="btn" style="display: inline-block; margin-top: 20px; text-decoration: none;">
                    Go to Login
                </a>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            Built with ❤️ by @Learn_launch | MIT License
        </div>
    </div>
</body>
</html>
