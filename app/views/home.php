<?php
/**
 * FireWeb Messenger - Premium Bilingual Home (Premium UI v0.0.1)
 * 
 * @author Alion (@prgpu / @Learn_launch)
 * @license MIT
 */

session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$defaultLang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'fa';
?>
<!DOCTYPE html>
<html lang="<?php echo $defaultLang; ?>" dir="<?php echo $defaultLang === 'fa' ? 'rtl' : 'ltr'; ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#E63946">
    <meta name="description" content="FireWeb Messenger - Secure & Fast Messaging Platform for Iranians">
    
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <link rel="manifest" href="/FireWebCHAT/manifest.json">
    <link rel="icon" type="image/png" sizes="512x512" href="assets/images/icon-512.png">

    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/icon-192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icon-180.png">
    
    <title>FireWeb Messenger - Secure & Fast Messaging</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #E63946;
            --primary-dark: #d62839;
            --primary-light: #ff4d5a;
            --accent: #F77F00;
            --accent-dark: #e07200;
            --dark: #1d3557;
            --light: #f1faee;
            --gray: #457b9d;
            --gray-light: #a8c5da;
            --success: #06d6a0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
            background: var(--light);
            transition: all 0.3s ease;
        }
        
        [dir="rtl"] body,
        [dir="rtl"] * {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Language switching */
        [data-lang="fa"] { display: none; }
        [data-lang="en"] { display: inline; }
        [dir="rtl"] [data-lang="fa"] { display: inline; }
        [dir="rtl"] [data-lang="en"] { display: none; }
        [data-lang-block="fa"] { display: none; }
        [data-lang-block="en"] { display: block; }
        [dir="rtl"] [data-lang-block="fa"] { display: block; }
        [dir="rtl"] [data-lang-block="en"] { display: none; }
        
        /* ==================== LOADER ==================== */
        .loader-wrapper {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .loader-wrapper.hidden {
            opacity: 0;
            visibility: hidden;
            transform: scale(1.1);
        }
        
        .loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 45px;
        }
        
        .loader-icon {
            position: relative;
            width: 170px;
            height: 170px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(25px);
            border-radius: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 35px 90px rgba(0, 0, 0, 0.4);
            animation: iconPulse 2.5s ease-in-out infinite;
            overflow: hidden;
        }
        
        .loader-icon img {
            width: 125px;
            height: 125px;
            object-fit: contain;
            filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.35));
            animation: logoSpin 3.5s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }
        
        .loader-icon::before {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 47px;
            background: linear-gradient(45deg, #E63946, #ffffff, #F77F00, #E63946);
            background-size: 300% 300%;
            opacity: 0.65;
            filter: blur(15px);
            animation: gradientRotate 5s linear infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.09); }
        }
        
        @keyframes logoSpin {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(12deg) scale(1.05); }
        }
        
        @keyframes gradientRotate {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .loader-spinner {
            width: 85px;
            height: 85px;
            border: 7px solid rgba(255, 255, 255, 0.18);
            border-top-color: white;
            border-right-color: white;
            border-radius: 50%;
            animation: spin 1.2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.45);
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loader-text {
            color: white;
            font-size: 30px;
            font-weight: 900;
            text-align: center;
            animation: textGlow 2.5s ease-in-out infinite;
            letter-spacing: -0.5px;
        }
        
        @keyframes textGlow {
            0%, 100% { 
                text-shadow: 0 0 18px rgba(255, 255, 255, 0.65),
                             0 0 35px rgba(255, 255, 255, 0.35);
            }
            50% { 
                text-shadow: 0 0 32px rgba(255, 255, 255, 0.95),
                             0 0 60px rgba(255, 255, 255, 0.55);
            }
        }
        
        .loader-progress {
            width: 260px;
            height: 6px;
            background: rgba(255, 255, 255, 0.22);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 15px;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .loader-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #ffffff 0%, rgba(255, 255, 255, 0.85) 100%);
            border-radius: 12px;
            animation: progress 2.8s ease-in-out infinite;
            box-shadow: 0 0 18px rgba(255, 255, 255, 0.7);
        }
        
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 78%; }
            100% { width: 100%; }
        }
        
        /* ==================== NAVIGATION ==================== */
        .navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 20px 0;
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.07);
            z-index: 1000;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 1px solid rgba(230, 57, 70, 0.08);
        }
        
        .navbar.scrolled {
            padding: 16px 0;
            box-shadow: 0 4px 40px rgba(0, 0, 0, 0.14);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 28px;
            font-weight: 900;
            color: var(--dark);
            text-decoration: none;
            letter-spacing: -0.8px;
        }
        
        .nav-logo-img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(230, 57, 70, 0.25));
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-logo:hover .nav-logo-img {
            transform: scale(1.1) rotate(8deg);
            filter: drop-shadow(0 6px 15px rgba(230, 57, 70, 0.4));
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        
        /* Language Switcher */
        .lang-switcher {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, rgba(230, 57, 70, 0.08) 0%, rgba(247, 127, 0, 0.08) 100%);
            border-radius: 14px;
            padding: 7px;
            box-shadow: 0 4px 14px rgba(230, 57, 70, 0.12);
            border: 1px solid rgba(230, 57, 70, 0.12);
        }
        
        .lang-btn {
            padding: 5px 10px;
            border: none;
            background: transparent;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 800;
            color: var(--gray);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        
        .lang-btn.active {
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            color: white;
            box-shadow: 0 6px 18px rgba(230, 57, 70, 0.4);
            transform: scale(1.02);
        }
        
        .lang-btn:not(.active):hover {
            background: rgba(230, 57, 70, 0.15);
            color: var(--primary);
            transform: scale(1.02);
        }
        
        .flag-icon {
            font-size: 20px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
        }
        
        .nav-buttons {
            display: flex;
            gap: 16px;
        }
        
        .nav-btn {
            padding: 13px 30px;
            font-size: 17px;
            font-weight: 800;
            border-radius: 13px;
            text-decoration: none;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            letter-spacing: -0.3px;
        }
        
        .nav-btn-primary {
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            color: white;
            box-shadow: 0 6px 22px rgba(230, 57, 70, 0.4);
        }
        
        .nav-btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 32px rgba(230, 57, 70, 0.5);
        }
        
        .nav-btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .nav-btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* ==================== HERO ==================== */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            position: relative;
            overflow: hidden;
            padding-top: 100px;
        }
        
        .hero::before, .hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.55;
        }
        
        .hero::before {
            width: 1100px;
            height: 1100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, transparent 70%);
            top: -550px;
            animation: float1 12s ease-in-out infinite;
        }
        
        [dir="rtl"] .hero::before { left: -550px; }
        [dir="ltr"] .hero::before { right: -550px; }
        
        .hero::after {
            width: 900px;
            height: 900px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, transparent 70%);
            bottom: -450px;
            animation: float2 14s ease-in-out infinite reverse;
        }
        
        [dir="rtl"] .hero::after { right: -450px; }
        [dir="ltr"] .hero::after { left: -450px; }
        
        @keyframes float1 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(60px, -60px) rotate(180deg); }
        }
        
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-50px, 50px) rotate(-180deg); }
        }
        
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            pointer-events: none;
        }
        
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            opacity: 0.55;
            animation: particle-fall linear infinite;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }
        
        @keyframes particle-fall {
            to {
                transform: translateY(100vh);
                opacity: 0;
            }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 150px 24px 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .hero-logo {
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(35px);
            border-radius: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 60px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5);
            animation: heroLogoBounce 4s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }
        
        .hero-logo img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            filter: drop-shadow(0 6px 18px rgba(0, 0, 0, 0.45));
            position: relative;
            z-index: 2;
        }
        
        .hero-logo::before {
            content: '';
            position: absolute;
            inset: -6px;
            background: linear-gradient(45deg, #F77F00, #ffffff, #E63946, #F77F00);
            background-size: 300% 300%;
            border-radius: 56px;
            opacity: 0.7;
            filter: blur(12px);
            animation: gradientRotate 6s linear infinite;
        }
        
        @keyframes heroLogoBounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-35px) scale(1.07); }
        }
        
        h1 {
            font-size: 84px;
            font-weight: 900;
            color: white;
            margin-bottom: 36px;
            line-height: 1.05;
            text-shadow: 0 6px 30px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 1s ease-out;
            letter-spacing: -2px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-subtitle {
            font-size: 30px;
            color: rgba(255, 255, 255, 0.98);
            margin-bottom: 30px;
            max-width: 800px;
            line-height: 1.65;
            text-shadow: 0 4px 16px rgba(0, 0, 0, 0.22);
            animation: fadeInUp 1s ease-out 0.2s backwards;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .hero-mission {
            font-size: 22px;
            color: rgba(255, 255, 255, 0.94);
            margin-bottom: 65px;
            max-width: 850px;
            line-height: 1.75;
            text-shadow: 0 3px 12px rgba(0, 0, 0, 0.18);
            animation: fadeInUp 1s ease-out 0.4s backwards;
            padding: 24px 36px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            font-weight: 600;
        }
        
        .hero-buttons {
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 1s ease-out 0.6s backwards;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            padding: 24px 48px;
            font-size: 21px;
            font-weight: 900;
            border-radius: 18px;
            text-decoration: none;
            transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            letter-spacing: -0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.38);
            transform: translate(-50%, -50%);
            transition: width 0.8s, height 0.8s;
        }
        
        .btn:hover::before {
            width: 400px;
            height: 400px;
        }
        
        .btn-primary {
            background: white;
            color: #E63946;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.32);
        }
        
        .btn-primary:hover {
            transform: translateY(-6px);
            box-shadow: 0 28px 65px rgba(0, 0, 0, 0.42);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(20px);
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.5);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.42);
            border-color: rgba(255, 255, 255, 0.7);
            transform: translateY(-6px);
        }
        
        /* ==================== MISSION ==================== */
        .mission {
            padding: 120px 24px;
            background: var(--light);
        }
        
        .mission-content {
            max-width: 1100px;
            margin: 0 auto;
            text-align: center;
        }
        
        .mission-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 17px;
            font-weight: 900;
            margin-bottom: 35px;
            box-shadow: 0 8px 25px rgba(230, 57, 70, 0.4);
            animation: pulse 2.5s ease-in-out infinite;
            letter-spacing: 0.3px;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 8px 25px rgba(230, 57, 70, 0.4); }
            50% { transform: scale(1.06); box-shadow: 0 12px 35px rgba(230, 57, 70, 0.55); }
        }
        
        .mission-title {
            font-size: 56px;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 32px;
            line-height: 1.15;
            letter-spacing: -1.5px;
        }
        
        .mission-description {
            font-size: 24px;
            color: var(--gray);
            line-height: 1.85;
            margin-bottom: 60px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 35px;
            margin-top: 70px;
        }
        
        .mission-card {
            background: white;
            padding: 48px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.09);
            transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #E63946 0%, #F77F00 100%);
            transform: scaleX(0);
            transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .mission-card:hover::before {
            transform: scaleX(1);
        }
        
        .mission-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.18);
            border-color: var(--primary);
        }
        
        .mission-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(230, 57, 70, 0.1) 0%, rgba(247, 127, 0, 0.1) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .mission-icon svg {
            width: 42px;
            height: 42px;
            fill: var(--primary);
            transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .mission-card:hover .mission-icon {
            transform: scale(1.15) rotate(8deg);
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
        }
        
        .mission-card:hover .mission-icon svg {
            fill: white;
        }
        
        .mission-card h3 {
            font-size: 26px;
            color: var(--dark);
            margin-bottom: 18px;
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        
        .mission-card p {
            color: var(--gray);
            line-height: 1.75;
            font-size: 17px;
            margin: 0;
        }
        
        /* ==================== FOOTER ==================== */
        .footer {
            background: linear-gradient(135deg, #1d3557 0%, #0f1d2f 100%);
            color: rgba(255, 255, 255, 0.88);
            padding: 80px 24px 40px;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(230, 57, 70, 0.3) 50%, transparent 100%);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 60px;
            margin-bottom: 60px;
        }
        
        .footer-brand {
            flex: 1;
            min-width: 280px;
            max-width: 450px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
        }
        
        .footer-logo img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            filter: drop-shadow(0 5px 15px rgba(230, 57, 70, 0.4));
        }
        
        .footer-brand h3 {
            color: white;
            font-size: 34px;
            font-weight: 900;
            margin: 0;
            letter-spacing: -1px;
        }
        
        .footer-brand p {
            line-height: 1.85;
            margin: 0 0 32px 0;
            font-size: 17px;
            color: rgba(255, 255, 255, 0.75);
        }
        
        .footer-social {
            display: flex;
            gap: 16px;
        }
        
        .social-link {
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .social-link svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }
        
        .social-link:hover {
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(230, 57, 70, 0.4);
            border-color: transparent;
        }
        
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            text-align: center;
        }
        
        .copyright {
            color: rgba(255, 255, 255, 0.68);
            font-size: 16px;
            line-height: 1.7;
        }
        
        .copyright strong {
            color: white;
            font-weight: 900;
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .copyright .heart {
            color: #E63946;
            animation: heartbeat 1.8s ease-in-out infinite;
            display: inline-block;
        }
        
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(1); }
        }
        
        /* ==================== PWA BANNER ==================== */
        #installBanner {
            display: none;
            position: fixed;
            bottom: 110px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #E63946 0%, #F77F00 100%);
            color: white;
            padding: 24px 36px;
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
            z-index: 9999;
            animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        #installBanner .banner-content {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        #installBtn {
            background: white;
            color: #E63946;
            border: none;
            padding: 16px 32px;
            border-radius: 14px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 16px;
        }
        
        [dir="rtl"] #installBtn { margin-right: 24px; }
        [dir="ltr"] #installBtn { margin-left: 24px; }
        
        #installBtn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 22px rgba(255, 255, 255, 0.4);
        }
        
        #dismissBtn {
            background: transparent;
            border: none;
            color: white;
            font-size: 34px;
            cursor: pointer;
            padding: 8px 14px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            line-height: 1;
        }
        
        #dismissBtn:hover {
            transform: scale(1.3) rotate(90deg);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translate(-50%, 40px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
        
        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            h1 { font-size: 56px; }
            .hero-subtitle { font-size: 24px; }
            .hero-mission { font-size: 19px; padding: 20px 26px; }
            .hero-buttons { flex-direction: column; width: 100%; }
            .btn { width: 100%; justify-content: center; }
            .mission-title { font-size: 44px; }
            .mission-description { font-size: 20px; }
            .footer-content { flex-direction: column; align-items: center; text-align: center; }
            .footer-brand { max-width: 100%; }
            .footer-social { justify-content: center; }
            .nav-logo { font-size: 24px; }
            .nav-logo-img { width: 46px; height: 46px; }
            .nav-right { flex-direction: column; gap: 14px; }
            .mission-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 480px) {
            #installBanner {
                left: 16px;
                right: 16px;
                transform: none;
                padding: 20px;
            }
            #installBanner .banner-content {
                flex-direction: column;
                gap: 16px;
            }
            #installBtn {
                width: 100%;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader-wrapper" id="loader">
        <div class="loader">
            <div class="loader-icon">
                <img src="assets/images/icon-512.png" alt="FireWeb">
            </div>
            <div class="loader-spinner"></div>
            <div class="loader-text">
                <span data-lang="fa">در حال بارگذاری...</span>
                <span data-lang="en">Loading FireWeb...</span>
            </div>
            <div class="loader-progress">
                <div class="loader-progress-bar"></div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="/" class="nav-logo">
                <img src="assets/images/icon-192.png" alt="FireWeb" class="nav-logo-img">
                <span>FireWeb</span>
            </a>
            
            <div class="nav-right">
                <div class="lang-switcher">
                    <button class="lang-btn" data-lang-code="fa" onclick="switchLanguage('fa')">
                        <span class="flag-icon">🇮🇷</span>
                        <span>فارسی</span>
                    </button>
                    <button class="lang-btn" data-lang-code="en" onclick="switchLanguage('en')">
                        <span class="flag-icon">🇬🇧</span>
                        <span>English</span>
                    </button>
                </div>
                
                <div class="nav-buttons">
                    <?php if ($isLoggedIn): ?>
                        <button class="nav-btn nav-btn-primary" id="goToChat">
                            <span data-lang="fa">ورود به چت</span>
                            <span data-lang="en">Open Chat</span>
                        </button>
                    <?php else: ?>
                        <a href="?route=login" class="nav-btn nav-btn-secondary">
                            <span data-lang="fa">ورود</span>
                            <span data-lang="en">Sign In</span>
                        </a>
                        <a href="?route=register" class="nav-btn nav-btn-primary">
                            <span data-lang="fa">ثبت‌نام</span>
                            <span data-lang="en">Get Started</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero -->
    <section class="hero">
        <div class="particles" id="particles"></div>
        <div class="hero-content">
            <div class="hero-logo">
                <img src="assets/images/icon-512.png" alt="FireWeb">
            </div>
            
            <h1>
                <span data-lang="fa">فایروب مسنجر</span>
                <span data-lang="en">FireWeb Messenger</span>
            </h1>
            
            <p class="hero-subtitle">
                <span data-lang="fa">پیام‌رسان امن، سبک و سریع - ساخته شده با ❤️ برای مردم ایران</span>
                <span data-lang="en">Secure, Fast & Beautiful Messaging - Built with ❤️ for Iranian People</span>
            </p>
            
            <p class="hero-mission">
                <strong>
                    <span data-lang="fa">🇮🇷 برای ایرانیان، توسط ایرانی‌ها</span>
                    <span data-lang="en">🇮🇷 For Iranians, by Iranians</span>
                </strong><br>
                <span data-lang-block="fa">در شرایط قطع اینترنت، جنگ و بحران‌های احتمالی، با نصب این پیام‌رسان سبک و امن روی هر سرور داخلی، با خانواده و عزیزانتان در ارتباط بمانید. حتی اگر خارج از کشور هستید، می‌توانید از طریق این پلتفرم با افراد داخل ایران ارتباط داشته باشید.</span>
                <span data-lang-block="en">During internet shutdowns, wars, or crises, stay connected with your loved ones by deploying this lightweight, secure messenger on any local server. Even if you're abroad, you can communicate with people inside Iran through this platform.</span>
            </p>
            
            <div class="hero-buttons">
                <?php if ($isLoggedIn): ?>
                    <button class="btn btn-primary" id="goToChatHero">
                        <span data-lang="fa">ورود به چت‌های من</span>
                        <span data-lang="en">Open My Chats</span>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </button>
                <?php else: ?>
                    <a href="?route=register" class="btn btn-primary">
                        <span data-lang="fa">شروع رایگان</span>
                        <span data-lang="en">Get Started Free</span>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <a href="?route=login" class="btn btn-secondary">
                        <span data-lang="fa">ورود به حساب</span>
                        <span data-lang="en">Sign In</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Mission -->
    <section class="mission">
        <div class="mission-content">
            <div class="mission-badge">
                <span data-lang="fa">🇮🇷 ساخت ایران با عشق و افتخار</span>
                <span data-lang="en">🇮🇷 Made in Iran with Love & Pride</span>
            </div>
            
            <h2 class="mission-title">
                <span data-lang="fa">چرا فایروب را برای ایران ساختیم؟</span>
                <span data-lang="en">Why We Built FireWeb for Iran?</span>
            </h2>
            
            <p class="mission-description">
                <span data-lang-block="fa">ما می‌دانیم که در شرایط بحرانی، ارتباط با عزیزان چقدر حیاتی است. فایروب برای همین ساخته شد تا در هر شرایطی، حتی زمانی که اینترنت بین‌المللی قطع است، بتوانید با خانواده و دوستانتان در ارتباط باشید.</span>
                <span data-lang-block="en">We know how vital communication with loved ones is during crises. FireWeb was built for this purpose - to keep you connected with family and friends in any situation, even when international internet is cut off.</span>
            </p>
            
            <div class="mission-grid">
                <!-- Card 1: Iran National Network -->
                <div class="mission-card">
                    <div class="mission-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3>
                        <span data-lang="fa">شبکه ملی ایران</span>
                        <span data-lang="en">Iran National Network</span>
                    </h3>
                    <p>
                        <span data-lang="fa">حتی با قطع اینترنت خارجی، در شبکه داخلی کشور کاملاً فعال و پایدار</span>
                        <span data-lang="en">Fully functional on domestic network even when international internet is down</span>
                    </p>
                </div>
                
                <!-- Card 2: Deploy Anywhere -->
                <div class="mission-card">
                    <div class="mission-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3>
                        <span data-lang="fa">نصب روی هر سرور</span>
                        <span data-lang="en">Deploy Anywhere</span>
                    </h3>
                    <p>
                        <span data-lang="fa">قابل نصب روی هر هاست و سرور ایرانی، بدون نیاز به زیرساخت خاص</span>
                        <span data-lang="en">Can be installed on any Iranian host or server without special infrastructure</span>
                    </p>
                </div>
                
                <!-- Card 3: Connect from Abroad -->
                <div class="mission-card">
                    <div class="mission-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-9h10v2H7z" fill="currentColor"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3>
                        <span data-lang="fa">ارتباط با خارج</span>
                        <span data-lang="en">Connect from Abroad</span>
                    </h3>
                    <p>
                        <span data-lang="fa">برای ایرانیان خارج از کشور امکان ارتباط با عزیزان داخل ایران</span>
                        <span data-lang="en">Enables Iranians abroad to communicate with loved ones inside Iran</span>
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="assets/images/icon-192.png" alt="FireWeb">
                    <h3>FireWeb</h3>
                </div>
                <p>
                    <span data-lang="fa">پیام‌رسان امن و سریع ساخته شده با عشق برای مردم ایران. در هر شرایطی با عزیزانتان در ارتباط باشید - حتی در قطعی اینترنت.</span>
                    <span data-lang="en">Secure and fast messenger built with love for Iranian people. Stay connected with your loved ones in any situation - even during internet shutdowns.</span>
                </p>
                <div class="footer-social">
                    <!-- X (Twitter) -->
                    <a href="#" class="social-link" aria-label="X (Twitter)">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <!-- GitHub -->
                    <a href="#" class="social-link" aria-label="GitHub">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
                        </svg>
                    </a>
                    <!-- Telegram -->
                    <a href="#" class="social-link" aria-label="Telegram">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="copyright">
                <span data-lang="fa">© 1403-1404 فایروب مسنجر. ساخته شده با <span class="heart">♥</span> برای مردم ایران توسط <strong>آلیون (@Learn_launch)</strong>. تمامی حقوق محفوظ است.</span>
                <span data-lang="en">© 2024-2025 FireWeb Messenger. Made with <span class="heart">♥</span> for Iranian People by <strong>Alion (@Learn_launch)</strong>. All rights reserved.</span>
            </div>
        </div>
    </footer>
    
    <!-- PWA Banner -->
    <div id="installBanner">
        <div class="banner-content">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <div>
                <div style="font-weight:900;font-size:20px;margin-bottom:7px;">
                    <span data-lang="fa">نصب فایروب</span>
                    <span data-lang="en">Install FireWeb</span>
                </div>
                <div style="font-size:16px;opacity:0.94;">
                    <span data-lang="fa">برای تجربه بهتر به‌عنوان اپ نصب کنید</span>
                    <span data-lang="en">Install as app for better experience</span>
                </div>
            </div>
            <button id="installBtn">
                <span data-lang="fa">نصب</span>
                <span data-lang="en">Install</span>
            </button>
            <button id="dismissBtn">×</button>
        </div>
    </div>

    <script>
        // ========== LANGUAGE SWITCHER ==========
        function switchLanguage(lang) {
            const html = document.documentElement;
            html.setAttribute('lang', lang);
            html.setAttribute('dir', lang === 'fa' ? 'rtl' : 'ltr');
            
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-lang-code="${lang}"]`).classList.add('active');
            
            document.cookie = `lang=${lang};path=/;max-age=31536000`;
            
            // Recreate particles for RTL/LTR
            document.getElementById('particles').innerHTML = '';
            createParticles();
        }
        
        window.addEventListener('load', () => {
            const currentLang = document.documentElement.getAttribute('lang');
            document.querySelector(`[data-lang-code="${currentLang}"]`).classList.add('active');
        });
        
        // ========== PARTICLES ==========
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 70;
            const dir = document.documentElement.getAttribute('dir');
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                if (dir === 'rtl') {
                    particle.style.right = Math.random() * 100 + '%';
                } else {
                    particle.style.left = Math.random() * 100 + '%';
                }
                
                particle.style.animationDuration = (Math.random() * 3.5 + 2.5) + 's';
                particle.style.animationDelay = Math.random() * 5 + 's';
                particlesContainer.appendChild(particle);
            }
        }
        
        // ========== NAVBAR SCROLL ==========
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 60) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // ========== LOADER ==========
        window.addEventListener('load', () => {
            createParticles();
            setTimeout(() => {
                document.getElementById('loader').classList.add('hidden');
            }, 2000);
        });
        
        // ========== GO TO CHAT ==========
        <?php if ($isLoggedIn): ?>
        function goToChat() {
            const loader = document.getElementById('loader');
            const lang = document.documentElement.getAttribute('lang');
            loader.classList.remove('hidden');
            
            const loaderText = loader.querySelector('.loader-text');
            loaderText.querySelector('[data-lang="' + lang + '"]').textContent = 
                lang === 'fa' ? 'در حال ورود به چت...' : 'Opening chat...';
            
            setTimeout(() => {
                window.location.href = '?route=chat';
            }, 1000);
        }
        
        document.getElementById('goToChat')?.addEventListener('click', goToChat);
        document.getElementById('goToChatHero')?.addEventListener('click', goToChat);
        <?php endif; ?>
        
        // ========== PWA INSTALL ==========
        let deferredPrompt;
        const installBanner = document.getElementById('installBanner');
        const installBtn = document.getElementById('installBtn');
        const dismissBtn = document.getElementById('dismissBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            setTimeout(() => {
                if (!window.matchMedia('(display-mode: standalone)').matches) {
                    installBanner.style.display = 'block';
                }
            }, 5500);
        });

        installBtn?.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            
            console.log(`Result: ${outcome}`);
            deferredPrompt = null;
            installBanner.style.display = 'none';
        });

        dismissBtn?.addEventListener('click', () => {
            installBanner.style.display = 'none';
            localStorage.setItem('pwa-dismissed', Date.now());
        });

        // ========== SERVICE WORKER ==========
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('?route=sw', {
                    scope: '/FireWebCHAT/'
                })
                    .then(registration => console.log('✅ SW registered @Learn_Launch:', registration.scope))
                    .catch(error => console.log('❌ SW registration failed:', error));
            });
        }

        window.addEventListener('appinstalled', () => {
            console.log('✅ PWA installed successfully!');
            installBanner.style.display = 'none';
        });
    </script>
</body>
</html>
