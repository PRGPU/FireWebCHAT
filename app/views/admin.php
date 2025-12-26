<?php
/**
 * FireWeb Messenger - Admin Dashboard (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ?route=login');
    exit;
}

$adminController = new AdminController();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'backup':
            $result = $adminController->backupDatabase();
            $backupMessage = $result['success'] ? 'Backup created: ' . $result['file'] : $result['error'];
            break;
            
        case 'toggle_user':
            if (isset($_POST['user_id'])) {
                $adminController->toggleUserStatus($_POST['user_id']);
                header('Location: ?route=admin');
                exit;
            }
            break;
            
        case 'delete_user':
            if (isset($_POST['user_id'])) {
                $result = $adminController->deleteUser($_POST['user_id']);
                $deleteMessage = $result['success'] ? $result['message'] : $result['error'];
            }
            break;
    }
}

$stats = $adminController->getStatistics();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FireWeb Messenger</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-solid: #667eea;
            --secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-tertiary: #94a3b8;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border: #334155;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            padding: 32px 24px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        
        .sidebar-logo {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .sidebar-title {
            font-size: 20px;
            font-weight: 700;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .nav-item {
            padding: 14px 16px;
            border-radius: 12px;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .nav-item:hover {
            background: var(--bg-tertiary);
            transform: translateX(4px);
        }
        
        .nav-item:hover::before {
            transform: scaleY(1);
        }
        
        .nav-item.active {
            background: var(--primary);
            color: white;
        }
        
        .nav-item svg {
            width: 20px;
            height: 20px;
        }
        
        /* Content */
        .admin-content {
            flex: 1;
            margin-left: 280px;
            padding: 40px;
            min-height: 100vh;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .content-header h1 {
            font-size: 36px;
            font-weight: 800;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .theme-toggle {
            padding: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: rotate(180deg);
            box-shadow: var(--shadow-md);
        }
        
        /* Alert */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            padding: 28px;
            border-radius: 20px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: var(--primary);
            opacity: 0.1;
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            background: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 28px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* Section */
        .admin-section {
            background: var(--bg-secondary);
            padding: 32px;
            border-radius: 20px;
            margin-bottom: 32px;
            box-shadow: var(--shadow-md);
            animation: fadeInUp 0.5s ease;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table thead {
            background: var(--bg-tertiary);
        }
        
        .admin-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-table td {
            padding: 16px;
            border-top: 1px solid var(--border);
            color: var(--text-primary);
        }
        
        .admin-table tbody tr {
            transition: background 0.2s ease;
        }
        
        .admin-table tbody tr:hover {
            background: var(--bg-tertiary);
        }
        
        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }
        
        .badge-danger {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }
        
        /* Button */
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        }
        
        .btn-sm {
            padding: 8px 14px;
            font-size: 13px;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f5576c 0%, #ef4444 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        
        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: var(--primary);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-xl);
            cursor: pointer;
            z-index: 1001;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.open {
                transform: translateX(0);
            }
            
            .admin-content {
                margin-left: 0;
                padding: 24px;
            }
            
            .mobile-toggle {
                display: flex;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 16px;
            }
            
            .content-header h1 {
                font-size: 28px;
            }
        }
        
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-section {
                padding: 20px;
            }
            
            .admin-table {
                font-size: 14px;
            }
            
            .admin-table th,
            .admin-table td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">🔥</div>
                <div class="sidebar-title">FireWeb Admin</div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#dashboard" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Dashboard
                </a>
                <a href="#users" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Users
                </a>
                <a href="#settings" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m6.5-11.5l-4.24 4.24m-4.24 4.24L5.5 20.5M23 12h-6M7 12H1"/>
                    </svg>
                    Settings
                </a>
                <a href="?route=chat" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Back to Chat
                </a>
                <a href="?route=logout" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </a>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="content-header">
                <h1>Dashboard</h1>
                <button class="theme-toggle" onclick="toggleTheme()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/>
                        <line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/>
                        <line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </button>
            </div>
            
            <?php if (isset($backupMessage)): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <?= htmlspecialchars($backupMessage) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($deleteMessage)): ?>
                <div class="alert <?= strpos($deleteMessage, 'success') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= htmlspecialchars($deleteMessage) ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['users']['total'] ?? 0 ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['conversations'] ?? 0 ?></div>
                    <div class="stat-label">Conversations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['messages'] ?? 0 ?></div>
                    <div class="stat-label">Messages</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        </svg>
                    </div>
                    <div class="stat-value"><?= $stats['storage']['total']['formatted'] ?? '0 B' ?></div>
                    <div class="stat-label">Storage Used</div>
                </div>
            </div>

            
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">Users Management</h2>
                </div>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Display Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['users']['list']) && is_array($stats['users']['list'])): ?>
                                <?php foreach ($stats['users']['list'] as $user): ?>
                                    <tr>
                                        <td><strong>#<?= $user['id'] ?></strong></td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <img src="<?= $user['avatar'] ? 'public/api.php?file='.urlencode($user['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($user['display_name']).'&background=667eea&color=fff' ?>" 
                                                     alt="<?= htmlspecialchars($user['display_name']) ?>" 
                                                     style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                                <span>@<?= htmlspecialchars($user['username']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['display_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $user['role'] === 'admin' ? 'badge-success' : 'badge-primary' ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $user['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                                <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor">
                                                    <circle cx="5" cy="5" r="5"/>
                                                </svg>
                                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <div style="display:flex;gap:8px;">
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_user">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                                <circle cx="12" cy="12" r="3"/>
                                                            </svg>
                                                            <?= $user['is_active'] ? 'Disable' : 'Enable' ?>
                                                        </button>
                                                    </form>
                                                    
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge badge-success">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:60px;color:var(--text-secondary);">
                                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;opacity:0.3;">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                                        </svg>
                                        <div style="font-size:16px;font-weight:600;">No users found</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">Storage Details</h2>
                </div>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['storage']) && is_array($stats['storage'])): ?>
                                <?php 
                                $totalSize = $stats['storage']['total']['size'] ?? 1;
                                foreach ($stats['storage'] as $category => $data): 
                                    if (!is_array($data) || !isset($data['formatted'])) continue;
                                    $percentage = $totalSize > 0 ? ($data['size'] / $totalSize * 100) : 0;
                                ?>
                                    <tr>
                                        <td><strong><?= ucfirst($category) ?></strong></td>
                                        <td><?= $data['formatted'] ?></td>
                                        <td>
                                            <div style="width:100%;background:var(--bg-tertiary);height:8px;border-radius:4px;overflow:hidden;">
                                                <div style="width:<?= number_format($percentage, 1) ?>%;background:var(--primary);height:100%;transition:width 0.5s ease;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">Database Management</h2>
                </div>
                <form method="POST" style="margin-bottom:24px;">
                    <input type="hidden" name="action" value="backup">
                    <button type="submit" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Create Backup
                    </button>
                </form>
                
                <?php 
                $backups = $adminController->getBackups();
                if (!empty($backups)):
                ?>
                    <h3 style="margin-bottom:16px;font-size:16px;font-weight:600;color:var(--text-primary);">Recent Backups</h3>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Size</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                </svg>
                                                <code style="font-size:13px;"><?= htmlspecialchars($backup['name']) ?></code>
                                            </div>
                                        </td>
                                        <td><?= $backup['size'] ?></td>
                                        <td><?= $backup['date'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="admin-section">
                <div class="section-header">
                    <h2 class="section-title">System Information</h2>
                </div>
                <div class="table-container">
                    <table class="admin-table">
                        <tbody>
                            <?php if (!empty($stats['system']) && is_array($stats['system'])): ?>
                                <tr>
                                    <td><strong>PHP Version</strong></td>
                                    <td><?= $stats['system']['php_version'] ?? 'Unknown' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Software</strong></td>
                                    <td><?= $stats['system']['server_software'] ?? 'Unknown' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Max Upload Size</strong></td>
                                    <td><?= $stats['system']['max_upload_size'] ?? 'Unknown' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Memory Limit</strong></td>
                                    <td><?= $stats['system']['memory_limit'] ?? 'Unknown' ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Extensions</strong></td>
                                    <td>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                            <?php foreach ($stats['system']['extensions'] ?? [] as $ext => $loaded): ?>
                                                <span class="badge <?= $loaded ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $ext ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div style="text-align:center;padding:40px 0;color:var(--text-tertiary);font-size:14px;">
                <p style="margin-bottom:8px;">Built with ❤️ by <strong>@Learn_launch (Alion)</strong></p>
                <p>FireWeb Messenger • MIT License</p>
            </div>
        </div>
    </div>
    
    <div class="mobile-toggle" id="mobileToggle" onclick="toggleSidebar()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
        }
        
        // Load saved theme
        const savedTheme = localStorage.getItem('admin-theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobileToggle');
            
            if (window.innerWidth <= 1024 && 
                sidebar.classList.contains('open') && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
        
        // Add badge for role
        const badgePrimary = document.createElement('style');
        badgePrimary.textContent = '.badge-primary { background: rgba(102, 126, 234, 0.15); color: var(--primary-solid); }';
        document.head.appendChild(badgePrimary);
    </script>
</body>
</html>

