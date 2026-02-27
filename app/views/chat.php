<?php
/**
 * FireWeb Messenger - Chat Page (Premium UI v1.0.0)
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

$chatController = new ChatController();
$userModel      = new User();

$currentUser   = $userModel->findById($_SESSION['user_id']);
$conversations = $chatController->getConversations($_SESSION['user_id']);

function generateAvatar($name) {
    $letter = strtoupper(substr($name, 0, 1));
    $colors = ['667eea', 'f5576c', '10b981', 'f59e0b', '8b5cf6', '3b82f6'];
    $color  = $colors[ord($letter) % count($colors)];
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
    : 'assets/images/default-avatar.png';
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
    <link rel="manifest" href="/FireWebCHAT/manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icon-16.png">
    <link rel="icon" href="/FireWebCHAT/assets/images/icon-96.png"  type="image/png">
    <link rel="icon" href="/FireWebCHAT/assets/images/icon-144.png" type="image/png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icon-180.png">
    <link rel="apple-touch-icon"               href="assets/images/icon-192.png">
    <title>FireWeb Messenger</title>
    <meta name="description" content="Modern messaging application with real-time chat">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

    <style>
    /* ========== Voice Player Premium ========== */
    .voice-player {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 18px;
        min-width: 220px;
        max-width: 280px;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(8px);
        position: relative;
        overflow: hidden;
    }
    .voice-player::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(102,126,234,0.15), rgba(118,75,162,0.1));
        pointer-events: none;
    }
    .voice-play-btn {
        width: 38px; height: 38px;
        border-radius: 50%;
        border: none;
        background: var(--primary, #3b82f6);
        color: white;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(59,130,246,0.35);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .voice-play-btn:active { transform: scale(0.92); }
    .voice-play-btn:hover  { box-shadow: 0 6px 18px rgba(59,130,246,0.45); }
    .voice-player-body { flex: 1; min-width: 0; }
    .voice-waveform {
        display: flex;
        align-items: center;
        gap: 3px;
        height: 28px;
        cursor: pointer;
        margin-bottom: 4px;
    }
    .voice-waveform-bar {
        width: 3px;
        border-radius: 3px;
        background: var(--primary, #3b82f6);
        opacity: 0.45;
        transition: opacity 0.2s, height 0.2s;
        flex-shrink: 0;
    }
    .voice-waveform-bar.played { opacity: 1; }
    .voice-waveform-bar.active {
        opacity: 1;
        animation: barPulse 0.4s ease infinite alternate;
    }
    @keyframes barPulse {
        from { transform: scaleY(1); }
        to   { transform: scaleY(1.4); }
    }
    .voice-time {
        font-size: 11px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.03em;
    }
    .voice-speed-btn {
        font-size: 11px;
        font-weight: 700;
        color: var(--primary);
        background: rgba(59,130,246,0.12);
        border: none;
        border-radius: 8px;
        padding: 2px 7px;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .voice-speed-btn:hover { background: rgba(59,130,246,0.22); }

    /* ========== Composer Premium ========== */
    .message-composer {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        padding: 10px 14px 12px;
        background: var(--bg-secondary);
        border-top: 1px solid var(--border);
        position: relative;
    }
    .composer-textarea {
        flex: 1;
        resize: none;
        border: 1.5px solid var(--border);
        border-radius: 22px;
        padding: 10px 16px;
        font-size: 15px;
        background: var(--bg-primary);
        color: var(--text-primary);
        line-height: 1.4;
        max-height: 120px;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
    }
    .composer-textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    }

    /* Voice record button states */
    .voice-record-active {
        color: #ef4444 !important;
        background: rgba(239,68,68,0.12) !important;
        border-radius: 50%;
        animation: recordPulse 1s ease infinite;
    }
    @keyframes recordPulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50%      { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
    }

    /* ========== Sidebar pill tabs ========== */
    .search-tabs {
        display: flex;
        gap: 6px;
        padding: 0 4px 12px;
    }
    .search-tab-btn {
        flex: 1;
        padding: 8px;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        background: transparent;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        color: var(--text-secondary);
        transition: all 0.2s;
    }
    .search-tab-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    /* ========== Channel badge in sidebar ========== */
    .conv-type-badge {
        position: absolute;
        bottom: 0; right: 0;
        width: 18px; height: 18px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid var(--bg-primary);
        font-size: 9px;
    }
    .conv-type-badge.group   { background: var(--primary); }
    .conv-type-badge.channel { background: linear-gradient(135deg,#f5576c,#f093fb); }

    /* ========== PWA slideUp ========== */
    @keyframes slideUp {
        from { opacity:0; transform:translate(-50%,20px); }
        to   { opacity:1; transform:translate(-50%,0);    }
    }

    /* ========== Welcome screen ========== */
    .welcome-icon {
        font-size: 64px;
        margin-bottom: 16px;
        animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
        0%,100% { transform: translateY(0); }
        50%      { transform: translateY(-10px); }
    }
    </style>
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
                <button class="icon-btn" id="searchBtn" title="Search" aria-label="Search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
                <button class="icon-btn" id="newGroupBtn" title="New Group" aria-label="New group">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        <line x1="21" y1="10" x2="21" y2="16"></line>
                        <line x1="18" y1="13" x2="24" y2="13"></line>
                    </svg>
                </button>
                <button class="icon-btn" id="newChannelBtn" title="New Channel" aria-label="New channel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </button>
                <button class="icon-btn" id="profileBtn" title="Profile" aria-label="Profile settings">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </button>
                <?php if ($currentUser['role'] === 'admin'): ?>
                <button class="icon-btn" onclick="location.href='?route=admin'" title="Admin Panel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                    </svg>
                </button>
                <?php endif; ?>
                <button class="icon-btn"
                        onclick="if(confirm('Are you sure you want to logout?')) location.href='?route=home'"
                        title="Logout" aria-label="Logout">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Conversations List -->
        <div class="conversations-wrapper">
            <div class="conversations-list" id="conversationsList">
                <?php if (empty($conversations)): ?>
                    <div style="padding:60px 20px;text-align:center;color:var(--text-secondary);">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" style="margin:0 auto 16px;opacity:0.5;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <p style="font-weight:600;font-size:16px;margin-bottom:8px;">No conversations yet</p>
                        <p style="font-size:14px;">Search for users to start chatting</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv):
                        $isGroup   = $conv['type'] === 'group';
                        $isChannel = $conv['type'] === 'channel';
                        $isSaved   = $conv['type'] === 'saved';

                        if ($isSaved) {
                            $title  = 'Saved Messages';
                            $avatar = generateAvatar('S');
                        } elseif ($isGroup || $isChannel) {
                            $title  = $conv['title'] ?? ($isChannel ? 'Channel' : 'Group Chat');
                            $avatar = isset($conv['conversation_avatar']) && $conv['conversation_avatar']
                                ? 'public/api.php?file=' . urlencode($conv['conversation_avatar'])
                                : generateAvatar($title);
                        } else {
                            $title  = $conv['other_user']['display_name'] ?? ($conv['title'] ?? 'Chat');
                            $avatar = isset($conv['other_user']['avatar']) && $conv['other_user']['avatar']
                                ? 'public/api.php?file=' . urlencode($conv['other_user']['avatar'])
                                : generateAvatar($title);
                        }

                        $lastMessage = $conv['last_message'] ?? null;
                        $memberCount = $conv['member_count'] ?? 0;
                        $unread      = $conv['unread_count'] ?? 0;
                    ?>
                    <div class="conversation-item"
                         data-id="<?= $conv['id'] ?>"
                         data-type="<?= htmlspecialchars($conv['type']) ?>"
                         oncontextmenu="conversationContextMenuHandler(event)"
                         ontouchstart="conversationHoldHandler(event)">

                        <div class="conversation-avatar" style="position:relative;">
                            <img src="<?= $avatar ?>"
                                 alt="<?= htmlspecialchars($title) ?>"
                                 onerror="this.src='<?= generateAvatar($title) ?>'">

                            <?php if ($isGroup): ?>
                            <span class="conv-type-badge group">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="white">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </span>
                            <?php elseif ($isChannel): ?>
                            <span class="conv-type-badge channel" style="font-size:10px;">📢</span>
                            <?php endif; ?>
                        </div>

                        <div class="conversation-info">
                            <h4><?= htmlspecialchars($title) ?></h4>
                            <p>
                                <?php if (($isGroup || $isChannel) && !$lastMessage): ?>
                                    <?= $memberCount ?> members
                                <?php else: ?>
                                    <?= htmlspecialchars(substr($lastMessage ?? 'No messages yet', 0, 40)) ?>
                                <?php endif; ?>
                            </p>
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

        <!-- Welcome Screen -->
        <div class="welcome-screen" id="welcomeScreen">
            <div class="welcome-content">
                <div class="welcome-icon">🔥</div>
                <h2>Welcome to FireWeb Messenger</h2>
                <p>Select a conversation or search for users to start chatting</p>
            </div>
        </div>

        <!-- Chat Header -->
        <div class="chat-header" id="chatHeader" style="display:none;">
            <button class="icon-btn menu-btn" id="menuBtn" aria-label="Toggle sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6"  x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <div class="chat-user-info" id="chatUserInfo" style="cursor:pointer;"
                 onclick="document.getElementById('chatMenuBtn').click()">
                <div class="avatar-wrapper">
                    <img id="chatAvatar" src="" alt="Chat Avatar" style="display:none;">
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
                    <circle cx="12" cy="5"  r="1"></circle>
                    <circle cx="12" cy="19" r="1"></circle>
                </svg>
            </button>
        </div>

        <!-- Messages Container -->
        <div class="messages-container" id="messagesContainer" style="display:none;">
            <div class="messages-list" id="messagesList"></div>
        </div>

        <!-- Message Composer -->
        <div class="message-composer" id="messageComposer" style="display:none;">
            <button class="icon-btn" id="attachBtn" title="Attach file" aria-label="Attach file">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                </svg>
            </button>
            <input type="file" id="fileInput" style="display:none;" multiple
                   accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.7z,.txt">

            <textarea id="messageInput" class="composer-textarea"
                      placeholder="Type a message..." rows="1" aria-label="Message input"></textarea>

            <button class="icon-btn" id="voiceBtn" title="Voice message" aria-label="Voice message"
                    style="transition: all 0.2s;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                    <line x1="12" y1="19" x2="12" y2="23"></line>
                    <line x1="8"  y1="23" x2="16" y2="23"></line>
                </svg>
            </button>

            <button class="send-btn" id="sendBtn" title="Send" disabled aria-label="Send">
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
            <h2>Search</h2>
            <button class="modal-close" onclick="closeSearchModal()" aria-label="Close">×</button>
        </div>

        <!-- Tabs -->
        <div class="search-tabs">
            <button class="search-tab-btn active" id="tabUsers"
                    onclick="switchSearchTab('users')">👤 Users</button>
            <button class="search-tab-btn" id="tabChannels"
                    onclick="switchSearchTab('channels')">📢 Channels</button>
        </div>

        <div class="search-bar">
            <div class="search-input-wrapper">
                <span class="search-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </span>
                <input type="text" id="userSearch" placeholder="Search..." autocomplete="off">
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
                <textarea id="bio" name="bio" rows="3"
                          placeholder="Tell us about yourself..."
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
                <input type="file" id="avatar_upload" name="avatar"
                       accept="image/jpeg,image/png,image/webp,image/gif">
                <small>Max 5MB — JPG, PNG, WebP, GIF</small>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
        </form>
    </div>
</div>

<!-- ==================== New Group Modal ==================== -->
<div class="modal" id="newGroupModal">
    <div class="modal-content liquid-glass-modal">
        <div class="modal-header">
            <h2>New Group</h2>
            <button class="modal-close" onclick="closeGroupModal()" aria-label="Close">×</button>
        </div>
        <form id="newGroupForm">
            <div class="form-group">
                <label for="groupTitle">Group Name</label>
                <input type="text" id="groupTitle" name="title"
                       required minlength="2" maxlength="50"
                       placeholder="Enter group name...">
            </div>
            <div class="form-group">
                <label>Group Avatar
                    <span style="font-weight:400;color:var(--text-tertiary);">(optional)</span>
                </label>
                <div style="display:flex;align-items:center;gap:14px;margin-top:4px;">
                    <div id="groupAvatarPreview"
                         style="width:64px;height:64px;border-radius:50%;overflow:hidden;
                                background:linear-gradient(135deg,#667eea,#764ba2);
                                display:flex;align-items:center;justify-content:center;
                                font-size:28px;flex-shrink:0;cursor:pointer;
                                border:2px solid var(--border);"
                         onclick="document.getElementById('groupAvatar').click()">👥</div>
                    <div>
                        <input type="file" id="groupAvatar" name="avatar"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               style="display:none;">
                        <button type="button" class="btn"
                                style="padding:8px 16px;font-size:13px;"
                                onclick="document.getElementById('groupAvatar').click()">
                            Choose Image
                        </button>
                        <div style="font-size:12px;color:var(--text-tertiary);margin-top:4px;">Max 5MB</div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="groupDesc">Description
                    <span style="font-weight:400;color:var(--text-tertiary);">(optional)</span>
                </label>
                <textarea id="groupDesc" name="description" rows="2"
                          maxlength="200" placeholder="What's this group about?"></textarea>
            </div>
            <div class="form-group">
                <label>Add Members</label>
                <div class="search-input-wrapper" style="margin-bottom:8px;">
                    <span class="search-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </span>
                    <input type="text" id="groupMemberSearch"
                           placeholder="Search users to add..." autocomplete="off">
                </div>
                <div id="groupMemberResults"
                     style="max-height:160px;overflow-y:auto;
                            border:1px solid var(--border);border-radius:10px;"></div>
                <div id="groupSelectedMembers"
                     style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="createGroupBtn">
                Create Group
            </button>
        </form>
    </div>
</div>

<!-- ==================== New Channel Modal ==================== -->
<div class="modal" id="newChannelModal">
    <div class="modal-content liquid-glass-modal">
        <div class="modal-header">
            <h2>New Channel</h2>
            <button class="modal-close" onclick="closeChannelModal()" aria-label="Close">×</button>
        </div>
        <form id="newChannelForm">
            <div class="form-group">
                <label for="channelTitle">Channel Name</label>
                <input type="text" id="channelTitle" name="title"
                       required minlength="2" maxlength="50"
                       placeholder="Enter channel name...">
            </div>
            <div class="form-group">
                <label for="channelUsername">Username
                    <span style="font-weight:400;color:var(--text-tertiary);">(optional)</span>
                </label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:var(--text-tertiary);font-size:16px;font-weight:600;">#</span>
                    <input type="text" id="channelUsername" name="username"
                           maxlength="32" placeholder="channelname"
                           pattern="[a-zA-Z0-9_]{3,32}" style="flex:1;">
                </div>
                <small>3-32 chars, letters/numbers/underscore</small>
            </div>
            <div class="form-group">
                <label for="channelDesc">Description
                    <span style="font-weight:400;color:var(--text-tertiary);">(optional)</span>
                </label>
                <textarea id="channelDesc" name="description" rows="2"
                          maxlength="200" placeholder="What's this channel about?"></textarea>
            </div>
            <div class="form-group">
                <label>Channel Avatar
                    <span style="font-weight:400;color:var(--text-tertiary);">(optional)</span>
                </label>
                <div style="display:flex;align-items:center;gap:14px;margin-top:4px;">
                    <div id="channelAvatarPreview"
                         style="width:64px;height:64px;border-radius:50%;overflow:hidden;
                                background:linear-gradient(135deg,#f5576c,#f093fb);
                                display:flex;align-items:center;justify-content:center;
                                font-size:28px;flex-shrink:0;cursor:pointer;
                                border:2px solid var(--border);"
                         onclick="document.getElementById('channelAvatar').click()">📢</div>
                    <div>
                        <input type="file" id="channelAvatar" name="avatar"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               style="display:none;">
                        <button type="button" class="btn"
                                style="padding:8px 16px;font-size:13px;"
                                onclick="document.getElementById('channelAvatar').click()">
                            Choose Image
                        </button>
                        <div style="font-size:12px;color:var(--text-tertiary);margin-top:4px;">Max 5MB</div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="createChannelBtn">
                Create Channel
            </button>
        </form>
    </div>
</div>

<!-- ==================== Group Info Modal ==================== -->
<div class="modal" id="groupInfoModal">
    <div class="modal-content liquid-glass-modal">
        <div class="modal-header">
            <h2 id="groupInfoTitle">Group Info</h2>
            <button class="modal-close"
                    onclick="document.getElementById('groupInfoModal').classList.remove('active')"
                    aria-label="Close">×</button>
        </div>
        <div id="groupInfoBody" style="padding:4px 0;"></div>
    </div>
</div>

<!-- ==================== PWA Install Banner ==================== -->
<div id="installBanner"
     style="display:none;position:fixed;bottom:80px;left:50%;transform:translateX(-50%);
            background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:white;padding:16px 24px;border-radius:16px;
            box-shadow:0 8px 24px rgba(0,0,0,0.2);z-index:9999;animation:slideUp 0.3s ease;">
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
        <button id="installBtn"
                style="background:white;color:#667eea;border:none;
                       padding:10px 20px;border-radius:8px;font-weight:700;
                       cursor:pointer;margin-left:16px;">Install</button>
        <button id="dismissBtn"
                style="background:transparent;border:none;color:white;
                       font-size:24px;cursor:pointer;padding:4px 8px;">×</button>
    </div>
</div>

<script>
    const currentUserId   = <?= (int)$_SESSION['user_id'] ?>;
    const currentUsername = '<?= htmlspecialchars($currentUser['username'], ENT_QUOTES) ?>';
</script>
<script src="assets/js/app.js"></script>

<script>
// ==================== Voice Player Premium ====================
function buildVoicePlayer(src) {
    const id    = 'vp_' + Math.random().toString(36).slice(2, 8);
    const bars  = 28;
    const heights = Array.from({length: bars}, () => Math.random() * 14 + 6);

    const wrapper = document.createElement('div');
    wrapper.className = 'voice-player';
    wrapper.innerHTML = `
        <button class="voice-play-btn" id="${id}_btn" onclick="voiceToggle('${id}')">
            <svg id="${id}_icon" width="16" height="16" viewBox="0 0 24 24" fill="white">
                <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
        </button>
        <div class="voice-player-body">
            <div class="voice-waveform" id="${id}_wf">
                ${heights.map((h, i) =>
                    `<div class="voice-waveform-bar" id="${id}_b${i}"
                          style="height:${h}px;"
                          onclick="voiceSeekBar('${id}',${i},${bars})"></div>`
                ).join('')}
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span class="voice-time" id="${id}_time">0:00</span>
                <button class="voice-speed-btn" id="${id}_speed"
                        onclick="voiceCycleSpeed('${id}')">1×</button>
            </div>
        </div>
        <audio id="${id}_audio" src="${src}" preload="metadata"></audio>`;

    const audio = wrapper.querySelector(`#${id}_audio`);

    audio.addEventListener('timeupdate', () => voiceUpdateWave(id, bars));
    audio.addEventListener('ended', () => {
        wrapper.querySelector(`#${id}_icon`).innerHTML =
            '<polygon points="5 3 19 12 5 21 5 3"/>';
        voiceResetBars(id, bars);
    });
    audio.addEventListener('loadedmetadata', () => {
        wrapper.querySelector(`#${id}_time`).textContent =
            voiceFmtTime(audio.duration);
    });

    return wrapper;
}

const _vpState = {};

function voiceToggle(id) {
    const audio   = document.getElementById(`${id}_audio`);
    const iconEl  = document.getElementById(`${id}_icon`);
    if (!audio) return;

    document.querySelectorAll('.voice-player audio').forEach(a => {
        if (a.id !== `${id}_audio` && !a.paused) {
            a.pause();
            const otherId = a.id.replace('_audio','');
            const otherIcon = document.getElementById(`${otherId}_icon`);
            if (otherIcon) otherIcon.innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
        }
    });

    if (audio.paused) {
        audio.play();
        iconEl.innerHTML = `
            <rect x="6" y="4" width="4" height="16" rx="1"/>
            <rect x="14" y="4" width="4" height="16" rx="1"/>`;
    } else {
        audio.pause();
        iconEl.innerHTML = '<polygon points="5 3 19 12 5 21 5 3"/>';
    }
}

function voiceUpdateWave(id, bars) {
    const audio = document.getElementById(`${id}_audio`);
    const time  = document.getElementById(`${id}_time`);
    if (!audio) return;

    const progress = audio.duration ? audio.currentTime / audio.duration : 0;
    const played   = Math.floor(progress * bars);

    for (let i = 0; i < bars; i++) {
        const bar = document.getElementById(`${id}_b${i}`);
        if (!bar) continue;
        bar.className = 'voice-waveform-bar' +
            (i < played ? ' played' : i === played ? ' active' : '');
    }
    time.textContent = voiceFmtTime(audio.currentTime);
}

function voiceResetBars(id, bars) {
    for (let i = 0; i < bars; i++) {
        const bar = document.getElementById(`${id}_b${i}`);
        if (bar) bar.className = 'voice-waveform-bar';
    }
}

function voiceSeekBar(id, barIndex, bars) {
    const audio = document.getElementById(`${id}_audio`);
    if (!audio || !audio.duration) return;
    audio.currentTime = (barIndex / bars) * audio.duration;
}

const _vpSpeeds = [1, 1.5, 2, 0.5];
function voiceCycleSpeed(id) {
    const audio = document.getElementById(`${id}_audio`);
    const btn   = document.getElementById(`${id}_speed`);
    if (!audio || !btn) return;
    _vpState[id] = _vpState[id] ?? 0;
    _vpState[id] = (_vpState[id] + 1) % _vpSpeeds.length;
    const spd = _vpSpeeds[_vpState[id]];
    audio.playbackRate = spd;
    btn.textContent = spd + '×';
}

function voiceFmtTime(s) {
    if (!s || isNaN(s)) return '0:00';
    const m = Math.floor(s / 60);
    const sec = Math.floor(s % 60);
    return `${m}:${sec.toString().padStart(2,'0')}`;
}

// ==================== PWA ====================
let deferredPrompt;
const installBanner = document.getElementById('installBanner');
const installBtn    = document.getElementById('installBtn');
const dismissBtn    = document.getElementById('dismissBtn');

window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    setTimeout(() => {
        if (!window.matchMedia('(display-mode: standalone)').matches &&
            !localStorage.getItem('pwa-dismissed')) {
            installBanner.style.display = 'block';
        }
    }, 3000);
});
installBtn?.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
    installBanner.style.display = 'none';
});
dismissBtn?.addEventListener('click', () => {
    installBanner.style.display = 'none';
    localStorage.setItem('pwa-dismissed', Date.now());
});
window.addEventListener('appinstalled', () => {
    installBanner.style.display = 'none';
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('?route=sw', { scope: '/FireWebCHAT/' })
            .then(r  => console.log('✅ SW:', r.scope))
            .catch(e => console.log('❌ SW:', e));
    });
}
</script>
</body>
</html>
