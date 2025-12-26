<?php
/**
 * FireWeb Messenger - 404 Error Page (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

// Set 404 status
http_response_code(404);

// Get current URL for logging
$requestedUrl = $_SERVER['REQUEST_URI'] ?? '/';
$referer = $_SERVER['HTTP_REFERER'] ?? 'Direct Access';

// Log error (optional - for debugging)
error_log("404 Error: {$requestedUrl} - Referer: {$referer}");
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>404 - Page Not Found | FireWeb Messenger</title>
    <link rel="icon" href="/FireWebCHAT/assets/images/icon-96.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --text: #2d3748;
            --text-light: #718096;
            --bg: #f7fafc;
            --surface: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --text: #e2e8f0;
                --text-light: #a0aec0;
                --bg: #1a202c;
                --surface: #2d3748;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: var(--surface);
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 550px;
            width: 100%;
            animation: fadeInUp 0.6s ease;
        }

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

        .error-code {
            font-size: 120px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            opacity: 0.9;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 12px;
            color: var(--text);
            font-weight: 700;
        }

        p {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin: 5px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text);
            border: 2px solid var(--primary);
            box-shadow: none;
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .requested-url {
            margin-top: 20px;
            padding: 12px;
            background: rgba(0,0,0,0.05);
            border-radius: 8px;
            font-size: 13px;
            color: var(--text-light);
            word-break: break-all;
            font-family: monospace;
        }

        @media (max-width: 600px) {
            .container {
                padding: 35px 25px;
            }
            .error-code {
                font-size: 90px;
            }
            h1 {
                font-size: 22px;
            }
            p {
                font-size: 14px;
            }
            .btn {
                padding: 12px 28px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" fill="url(#gradient)" opacity="0.2"/>
            <path d="M12 8v4m0 4h.01M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" 
                  stroke="url(#gradient)" stroke-width="2" stroke-linecap="round"/>
            <defs>
                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#667eea"/>
                    <stop offset="100%" style="stop-color:#764ba2"/>
                </linearGradient>
            </defs>
        </svg>
        
        <div class="error-code">404</div>
        
        <h1>Page Not Found</h1>
        
        <p>
            The page you're looking for doesn't exist or has been moved.
            <br>Let's get you back on track!
        </p>
        
        <div style="margin: 20px 0;">
            <a href="/FireWebCHAT/" class="btn">🏠 Go Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">← Go Back</a>
        </div>

        <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
        <div class="requested-url">
            <strong>Requested:</strong> <?= htmlspecialchars($requestedUrl) ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Optional: Auto-redirect after 15 seconds
        let countdown = 15;
        const redirectTimer = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(redirectTimer);
                window.location.href = '/FireWebCHAT/';
            }
        }, 1000);
        
        // Cancel redirect if user interacts
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', () => clearInterval(redirectTimer));
        });
    </script>
</body>
</html>
