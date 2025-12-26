<?php
/**
 * FireWeb Messenger - Chat Page (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

$chatController = new ChatController();
$userModel = new User();

$currentUser = $userModel->findById($_SESSION['user_id']);
$conversations = $chatController->getConversations($_SESSION['user_id']);

function generateAvatar($name) {
    $letter = strtoupper(substr($name, 0, 1));
    $colors = ['667eea', 'f5576c', '10b981', 'f59e0b', '8b5cf6', '3b82f6'];
    $color = $colors[ord($letter) % count($colors)];
    return "data:image/svg+xml," . urlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">' .
        '<circle cx="50" cy="50" r="50" fill="#' . $color . '"/>' .
        '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" ' .
        'font-size="40" fill="white" font-family="system-ui" font-weight="600">' . $letter . '</text>' .
        '</svg>'
    );
}

$currentUserAvatar = $currentUser['avatar']   
    ? 'public/api.php?file=' . urlencode($currentUser['avatar'])  
    : 'assets/images/default-avatar.png'; // ✅ Default avatar path


?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($currentUser['theme'] ?? 'light') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FireWeb">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/FireWebCHAT/manifest.json">

    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icon-16.png">
    <link rel="icon" href="/FireWebCHAT/assets/images/icon-96.png" type="image/png">
    <link rel="icon" href="/FireWebCHAT/assets/images/icon-144.png" type="image/png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icon-180.png">

    
    <title>FireWeb Messenger - Chat</title>
    <meta name="description" content="Modern messaging application with real-time chat">
    <meta name="theme-color" content="#667eea">
    <link rel="apple-touch-icon" href="assets/images/icon-192.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <div class="chat-header-user">

</head>

<body class="chat-page">
    <div class="chat-container">
        <!-- ==================== Sidebar ==================== -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="user-profile">
                    <div class="avatar-wrapper">
                        <img src="<?= $currentUserAvatar ?>"
                            alt="<?= htmlspecialchars($currentUser['display_name']) ?>" 
                            class="avatar"
                            onerror="this.src='assets/images/default-avatar.png'">
                        <span class="online-badge"></span>
                    </div>

                    <div class="user-info">
                        <h3><?= htmlspecialchars($currentUser['display_name']) ?></h3>
                        <p>@<?= htmlspecialchars($currentUser['username']) ?></p>
                    </div>
                </div>
                
                <div class="sidebar-actions">
                    <button class="icon-btn" id="themeToggle" title="Toggle Theme" aria-label="Toggle theme">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>
                    <button class="icon-btn" id="searchBtn" title="Search Users" aria-label="Search users">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                    <button class="icon-btn" id="profileBtn" title="Profile Settings" aria-label="Profile settings">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <button class="icon-btn" onclick="location.href='?route=admin'" title="Admin Panel" aria-label="Admin panel">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M12 1v6m0 6v6"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                    <button class="icon-btn" onclick="if(confirm('Are you sure you want to logout?')) location.href='?route=home'" title="Logout" aria-label="Logout">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="conversations-wrapper">
                <div class="conversations-list" id="conversationsList">
                    <?php if (empty($conversations)): ?>
                        <div style="padding: 60px 20px; text-align: center; color: var(--text-secondary);">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 16px; opacity: 0.5;">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <p style="font-weight: 600; font-size: 16px; margin-bottom: 8px;">No conversations yet</p>
                            <p style="font-size: 14px;">Search for users to start chatting</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): 
                            $title = $conv['type'] === 'dm' && isset($conv['other_user']) 
                                ? $conv['other_user']['display_name'] 
                                : ($conv['title'] ?? 'Group Chat');
                            
                            if ($conv['type'] === 'dm' && isset($conv['other_user']['avatar'])) {
                                $avatar = 'public/api.php?file=' . urlencode($conv['other_user']['avatar']);
                            } else {
                                $avatar = generateAvatar($title);
                            }
                            
                            $lastMessage = $conv['last_message'] ?? 'No messages yet';
                            $unread = $conv['unread_count'] ?? 0;
                        ?>
                            <div class="conversation-item" data-id="<?= $conv['id'] ?>" 
                                 oncontextmenu="conversationContextMenuHandler(event)" 
                                 ontouchstart="conversationHoldHandler(event)">
                                <div class="conversation-avatar">
                                    <img src="<?= $avatar ?>" 
                                         alt="<?= htmlspecialchars($title) ?>" 
                                         onerror="this.src='<?= generateAvatar($title) ?>'">
                                </div>
                                <div class="conversation-info">
                                    <h4><?= htmlspecialchars($title) ?></h4>
                                    <p><?= htmlspecialchars(substr($lastMessage, 0, 40)) ?></p>
                                </div>
                                <?php if ($unread > 0): ?>
                                    <span class="unread-badge"><?= $unread ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- ==================== Chat Area ==================== -->
        <div class="chat-area">
            <!-- Welcome Screen with Floating Menu Button -->
            <div class="welcome-screen" id="welcomeScreen">

                <div class="welcome-content">
                    <div class="welcome-icon">🔥</div>
                    <h2>Welcome to FireWeb Messenger</h2>
                    <p>Select a conversation or search for users to start chatting</p>
                </div>
            </div>
            
            <!-- Chat Header with Regular Menu Button -->
            <div class="chat-header" id="chatHeader" style="display: none;">
                <button class="icon-btn menu-btn" id="menuBtn" aria-label="Toggle sidebar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                
                <div class="chat-user-info">
                    <div class="avatar-wrapper">
                        <img id="chatAvatar" src="" alt="Chat Avatar" style="display: none;">
                    </div>
                    <div class="chat-user-details">
                        <h3 id="chatTitle">Chat</h3>
                        <div class="chat-status" id="chatStatus">
                            <span class="status-dot"></span>
                            <span>Offline</span>
                        </div>
                    </div>
                </div>
                
                <button class="icon-btn" id="chatMenuBtn" title="Chat options" aria-label="Chat menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
            </div>
            
            <!-- Messages Container -->
            <div class="messages-container" id="messagesContainer" style="display: none;">
                <div class="messages-list" id="messagesList"></div>
            </div>
            
            <!-- Message Composer -->
            <div class="message-composer" id="messageComposer" style="display: none;">
                <button class="icon-btn" id="attachBtn" title="Attach file" aria-label="Attach file">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                    </svg>
                </button>
                <input type="file" id="fileInput" style="display: none;" multiple 
                       accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.7z,.txt">
                
                <textarea id="messageInput" class="composer-textarea" 
                          placeholder="Type a message..." rows="1" aria-label="Message input"></textarea>
                
                <button class="icon-btn" id="voiceBtn" title="Voice message" aria-label="Voice message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                        <line x1="12" y1="19" x2="12" y2="23"></line>
                        <line x1="8" y1="23" x2="16" y2="23"></line>
                    </svg>
                </button>
                
                <button class="send-btn" id="sendBtn" title="Send message" disabled aria-label="Send">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- ==================== Search Modal ==================== -->
    <div class="modal" id="searchModal">
        <div class="modal-content liquid-glass-modal">
            <div class="modal-header">
                <h2>Search Users</h2>
                <button class="modal-close" onclick="closeSearchModal()" aria-label="Close">×</button>
            </div>
            <div class="search-bar">
                <div class="search-input-wrapper">
                    <span class="search-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </span>
                    <input type="text" id="userSearch" placeholder="Search by username or phone..." autocomplete="off">
                </div>
            </div>
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>
    
    <!-- ==================== Profile Modal ==================== -->
    <div class="modal" id="profileModal">
        <div class="modal-content liquid-glass-modal">
            <div class="modal-header">
                <h2>Profile Settings</h2>
                <button class="modal-close" onclick="closeProfileModal()" aria-label="Close">×</button>
            </div>
            <form id="profileForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" 
                           value="<?= htmlspecialchars($currentUser['display_name']) ?>" 
                           required minlength="2" maxlength="50">
                </div>
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="3" placeholder="Tell us about yourself..." 
                              maxlength="200"><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" 
                           placeholder="+[country][number]" pattern="\+[0-9]{6,15}">
                    <small>Format: +1234567890</small>
                </div>
                <div class="form-group">
                    <label for="avatar_upload">Change Avatar</label>
                    <input type="file" id="avatar_upload" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small>Max 5MB - JPG, PNG, WebP, GIF</small>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
            </form>
        </div>
    </div>
    
    <script>
        const currentUserId = <?= $_SESSION['user_id'] ?>;
        const currentUsername = '<?= htmlspecialchars($currentUser['username']) ?>';
    </script>
    <script src="assets/js/app.js"></script>
    <!-- Install Button (Only displayed when PWA is installable) -->
    <div id="installBanner" style="display:none;position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:16px 24px;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,0.2);z-index:9999;animation:slideUp 0.3s ease;">
        <div style="display:flex;align-items:center;gap:16px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <div>
                <div style="font-weight:700;font-size:16px;margin-bottom:4px;">Install FireWeb</div>
                <div style="font-size:13px;opacity:0.9;">Install as app for better experience</div>
            </div>
            <button id="installBtn" style="background:white;color:#667eea;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;margin-left:16px;">
                Install
            </button>
            <button id="dismissBtn" style="background:transparent;border:none;color:white;font-size:24px;cursor:pointer;padding:4px 8px;">×</button>
        </div>
    </div>

    <script>
    // PWA Install Prompt
    let deferredPrompt;
    const installBanner = document.getElementById('installBanner');
    const installBtn = document.getElementById('installBtn');
    const dismissBtn = document.getElementById('dismissBtn');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // نمایش banner بعد از 3 ثانیه
        setTimeout(() => {
            if (!window.matchMedia('(display-mode: standalone)').matches) {
                installBanner.style.display = 'block';
            }
        }, 3000);
    });

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            
            console.log(`User response: ${outcome}`);
            deferredPrompt = null;
            installBanner.style.display = 'none';
        });
    }

    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            installBanner.style.display = 'none';
            localStorage.setItem('pwa-dismissed', Date.now());
        });
    }

    // Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('?route=sw', {
                scope: '/FireWebCHAT/'
            })
                .then((registration) => {
                    console.log('✅ SW registered:', registration.scope);
                })
                .catch((error) => {
                    console.log('❌ SW registration failed:', error);
                });
        });
    }


    // Check if already installed
    window.addEventListener('appinstalled', () => {
        console.log('✅ PWA installed successfully!');
        installBanner.style.display = 'none';
    });
    </script>

    <style>
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translate(-50%, 20px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }
    </style>

</body>
</html>