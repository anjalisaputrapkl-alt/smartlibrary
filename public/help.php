<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$pageTitle = 'Bantuan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan - Perpustakaan Digital</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/school-profile.css">

    <style>
        :root {
            --primary: #3A7FF2;
            --primary-2: #7AB8F5;
            --primary-dark: #0A1A4F;
            --bg: #F6F9FF;
            --muted: #F3F7FB;
            --card: #FFFFFF;
            --surface: #FFFFFF;
            --muted-surface: #F7FAFF;
            --border: #E6EEF8;
            --text: #0F172A;
            --text-muted: #50607A;
            --accent: #3A7FF2;
            --accent-light: #e0f2fe;
            --success: #10B981;
            --warning: #f59e0b;
            --danger: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes entrance {
            from {
                opacity: 0;
                transform: translateX(-60px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ===== LAYOUT ===== */
        .nav-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 240px;
            background: linear-gradient(135deg, #0b3d61 0%, #062d4a 100%);
            color: white;
            padding: 24px 0;
            z-index: 1002;
            overflow-y: auto;
            animation: slideInLeft 0.6s ease-out;
        }

        .nav-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .nav-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .nav-sidebar-header {
            padding: 0 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }

        .nav-sidebar-header-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }

        .nav-sidebar-header-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: white;
        }

        .nav-sidebar-header h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .nav-sidebar-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 16px 0;
        }

        .nav-sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-sidebar-menu li {
            margin: 0;
        }

        .nav-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left-color: white;
            font-weight: 600;
        }

        .nav-sidebar-menu-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        iconify-icon {
            display: inline-block;
            vertical-align: middle;
        }

        .nav-sidebar-menu iconify-icon {
            font-size: 18px;
            width: 24px;
            height: 24px;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-sidebar-menu a:hover iconify-icon,
        .nav-sidebar-menu a.active iconify-icon {
            color: white;
        }

        /* Hamburger Menu Button */
        .nav-toggle {
            display: none;
            position: fixed;
            top: 6px;
            left: 12px;
            z-index: 999;
            background: var(--card);
            color: var(--text);
            cursor: pointer;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            padding: 0;
            transition: all 0.2s ease;
            border: none;
        }

        .nav-toggle:hover {
            background: var(--bg);
        }

        .nav-toggle:active {
            transform: scale(0.95);
        }

        .nav-toggle iconify-icon {
            width: 24px;
            height: 24px;
            color: var(--accent);
        }

        /* ===== HEADER ===== */
        .header {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            animation: slideDown 0.6s ease-out;
            margin-left: 240px;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
            margin-left: 7px;
        }

        .header-brand-icon {
            font-size: 32px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-light);
            border-radius: 8px;
        }

        .header-brand-icon iconify-icon {
            width: 32px;
            height: 32px;
            color: var(--accent);
        }

        .header-brand-text h2 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .header-brand-text p {
            font-size: 12px;
            color: var(--text-muted);
            margin: 2px 0 0 0;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-user-info {
            text-align: right;
        }

        .header-user-info p {
            font-size: 13px;
            margin: 0;
        }

        .header-user-info .name {
            font-weight: 600;
            color: var(--text);
        }

        .header-user-info .role {
            color: var(--text-muted);
        }

        .header-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent), #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }

        .header-logout {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--bg);
            color: var(--text);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .header-logout:hover {
            background: var(--muted-surface);
            border-color: var(--accent);
            color: var(--accent);
        }

        /* ===== MAIN CONTAINER ===== */
        .container-main {
            margin-left: 240px;
            padding: 24px;
            max-width: 1400px;
            margin-right: auto;
            animation: fadeInUp 0.6s ease-out;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 16px;
            color: var(--text);
        }

        .page-header h1 iconify-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
        }

        .page-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* ===== HELP SECTIONS ===== */
        .help-section {
            background: var(--card);
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out 0.1s backwards;
        }

        .help-section h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--text);
        }

        .help-section h2 iconify-icon {
            width: 26px;
            height: 26px;
            color: var(--accent);
            flex-shrink: 0;
        }

        .help-section p {
            margin: 0 0 16px 0;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .help-section p:last-child {
            margin-bottom: 0;
        }

        /* ===== FAQ SECTION ===== */
        .faq-container {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out 0.2s backwards;
            margin-bottom: 25px;
        }

        .faq-item {
            border-bottom: 1px solid var(--border);
            transition: all 0.2s ease;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-item:hover .faq-question {
            background: var(--muted-surface);
        }

        .faq-question {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            gap: 12px;
            transition: background 0.2s ease;
            background: var(--surface);
        }

        .faq-question-text {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            font-weight: 600;
            color: var(--text);
            font-size: 15px;
        }

        .faq-question-text iconify-icon {
            width: 22px;
            height: 22px;
            color: var(--accent);
            flex-shrink: 0;
        }

        .faq-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            color: var(--accent);
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }

        .faq-toggle iconify-icon {
            width: 24px;
            height: 24px;
        }

        .faq-item.active .faq-toggle {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: var(--muted-surface);
        }

        .faq-item.active .faq-answer {
            max-height: 500px;
        }

        .faq-answer-content {
            padding: 16px 24px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .faq-answer-content p {
            margin: 0 0 12px 0;
        }

        .faq-answer-content p:last-child {
            margin-bottom: 0;
        }

        .faq-answer-content ul {
            margin: 12px 0;
            padding-left: 20px;
        }

        .faq-answer-content li {
            margin: 8px 0;
        }

        /* ===== CONTACT SECTION ===== */
        .contact-section {
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary-2) 100%);
            color: white;
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease-out 0.3s backwards;
        }

        .contact-section h2 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: white;
        }

        .contact-section p {
            margin: 0 0 24px 0;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .contact-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .contact-btn {
            padding: 10px 20px;
            background: white;
            color: var(--accent);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .contact-btn iconify-icon {
            width: 18px;
            height: 18px;
        }

        /* ===== FEATURE CARDS ===== */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .feature-card {
            background: var(--card);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            animation: fadeInUp 0.6s ease-out backwards;
        }

        .feature-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .feature-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .feature-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .feature-card-icon {
            width: 52px;
            height: 52px;
            background: var(--accent-light);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            flex-shrink: 0;
        }

        .feature-card-icon iconify-icon {
            width: 30px;
            height: 30px;
            color: var(--accent);
        }

        .feature-card h3 {
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: var(--text);
        }

        .feature-card p {
            margin: 0;
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ===== STEPS SECTION ===== */
        .steps-section {
            background: var(--card);
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.6s ease-out 0.15s backwards;
        }

        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
        }

        .step {
            position: relative;
            padding-left: 50px;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 30px;
            right: -12px;
            width: 1px;
            height: 50px;
            background: var(--border);
        }

        @media (max-width: 640px) {
            .step:not(:last-child)::after {
                display: none;
            }
        }

        .step-number {
            position: absolute;
            left: 0;
            top: 2px;
            width: 36px;
            height: 36px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            flex-shrink: 0;
        }

        .step-title {
            font-weight: 700;
            margin: 0 0 6px 0;
            color: var(--text);
            font-size: 16px;
        }

        .step-description {
            margin: 0;
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ===== TIPS SECTION ===== */
        .tips-container {
            display: grid;
            gap: 12px;
        }

        .tip {
            background: linear-gradient(135deg, var(--accent-light), rgba(58, 127, 242, 0.05));
            border-left: 4px solid var(--accent);
            padding: 16px 16px;
            border-radius: 10px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .tip-icon {
            width: 22px;
            height: 22px;
            color: var(--accent);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .tip-content {
            flex: 1;
        }

        .tip-title {
            font-weight: 700;
            color: var(--text);
            margin: 0 0 4px 0;
            font-size: 14px;
        }

        .tip-text {
            margin: 0;
            color: var(--text-muted);
            font-size: 13px;
            line-height: 1.6;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .header {
                margin-left: 240px;
            }

            .container-main {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .nav-toggle {
                display: flex;
            }

            .nav-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 240px;
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            }

            .nav-sidebar.active {
                transform: translateX(0);
            }

            .header {
                margin-left: 0;
                padding: 12px 0;
                padding-left: 12px;
            }

            .header-container {
                flex-wrap: wrap;
                padding: 0 16px 0 60px;
                gap: 12px;
            }

            .header-brand {
                flex: 0 1 auto;
                min-width: auto;
                margin-left: 0;
            }

            .header-brand-icon {
                font-size: 24px;
                width: 32px;
                height: 32px;
            }

            .header-brand-text h2 {
                font-size: 14px;
            }

            .header-brand-text p {
                font-size: 11px;
            }

            .header-user {
                flex: 1;
                justify-content: flex-end;
                gap: 12px;
                order: 3;
                width: 100%;
            }

            .header-user-info {
                display: none;
            }

            .header-user-avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }

            .header-logout {
                padding: 6px 12px;
                font-size: 12px;
            }

            .container-main {
                margin-left: 0;
                padding: 16px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .page-header h1 iconify-icon {
                width: 28px;
                height: 28px;
            }

            .feature-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
            }

            .feature-card {
                padding: 20px;
            }

            .feature-card-icon {
                width: 48px;
                height: 48px;
                border-radius: 9px;
            }

            .feature-card-icon iconify-icon {
                width: 28px;
                height: 28px;
            }

            .steps-container {
                grid-template-columns: 1fr;
            }

            .step:not(:last-child)::after {
                display: none;
            }

            .help-section {
                padding: 20px;
            }

            .help-section h2 {
                font-size: 18px;
            }

            .faq-container {
                border-radius: 12px;
            }

            .faq-question {
                padding: 14px 16px;
            }

            .faq-answer-content {
                padding: 14px 16px;
            }

            .contact-section {
                padding: 20px;
            }

            .contact-section h2 {
                font-size: 18px;
            }

            .contact-buttons {
                flex-direction: column;
            }

            .contact-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .nav-toggle {
                width: 40px;
                height: 40px;
                left: 10px;
                top: 6px;
            }

            .nav-toggle iconify-icon {
                width: 20px;
                height: 20px;
            }

            .nav-sidebar {
                width: 200px;
            }

            .header {
                margin-left: 0;
                padding: 10px 0;
                padding-left: 10px;
            }

            .header-container {
                padding: 0 12px 0 50px;
                gap: 8px;
            }

            .header-brand {
                flex: 0;
                min-width: auto;
                margin-left: 0;
            }

            .header-brand-icon {
                font-size: 20px;
                width: 28px;
                height: 28px;
            }

            .header-brand-text {
                display: none;
            }

            .header-user-avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .header-logout {
                padding: 5px 10px;
                font-size: 11px;
            }

            .container-main {
                padding: 12px;
                margin-left: 0;
            }

            .page-header h1 {
                font-size: 20px;
                gap: 8px;
            }

            .page-header h1 iconify-icon {
                width: 24px;
                height: 24px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .feature-card {
                padding: 16px;
            }

            .feature-card-icon {
                width: 44px;
                height: 44px;
                border-radius: 8px;
            }

            .feature-card-icon iconify-icon {
                width: 24px;
                height: 24px;
            }

            .feature-card h3 {
                font-size: 15px;
                margin-bottom: 8px;
            }

            .feature-card p {
                font-size: 12px;
            }

            .steps-section {
                padding: 16px;
            }

            .step {
                padding-left: 44px;
            }

            .step-number {
                width: 32px;
                height: 32px;
                font-size: 13px;
                top: 1px;
            }

            .step-title {
                font-size: 15px;
                margin-bottom: 5px;
            }

            .step-description {
                font-size: 13px;
            }

            .help-section {
                padding: 16px;
                margin-bottom: 16px;
            }

            .help-section h2 {
                font-size: 16px;
                gap: 11px;
            }

            .help-section h2 iconify-icon {
                width: 22px;
                height: 22px;
            }

            .tip {
                padding: 12px 14px;
                gap: 12px;
            }

            .tip-icon {
                width: 20px;
                height: 20px;
                margin-top: 1px;
            }

            .tip-title {
                font-size: 12px;
            }

            .tip-text {
                font-size: 12px;
            }

            .faq-question {
                padding: 12px 14px;
            }

            .faq-question-text {
                gap: 10px;
                font-size: 14px;
            }

            .faq-question-text iconify-icon {
                width: 20px;
                height: 20px;
            }

            .faq-toggle {
                width: 20px;
                height: 20px;
            }

            .faq-answer-content {
                padding: 12px 14px;
            }

            .contact-section {
                padding: 16px;
            }

            .contact-section h2 {
                font-size: 16px;
            }

            .contact-section p {
                font-size: 12px;
            }

            .contact-btn {
                padding: 8px 16px;
                font-size: 12px;
                gap: 8px;
            }

            .contact-btn iconify-icon {
                width: 16px;
                height: 16px;
            }
        }

        iconify-icon {
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <!-- Navigation Sidebar -->
    <?php include 'partials/student-sidebar.php'; ?>

    <!-- Hamburger Menu Button -->
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <!-- Main Container -->
    <div class="container-main">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:help-circle" width="28" height="28"></iconify-icon>
                Bantuan & Panduan
            </h1>
            <p>Temukan jawaban untuk pertanyaan umum dan pelajari cara menggunakan perpustakaan digital kami</p>
        </div>

        <!-- Features Section -->
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-card-icon">
                    <iconify-icon icon="mdi:book-open-variant"></iconify-icon>
                </div>
                <h3>Jelajahi Koleksi</h3>
                <p>Cari dan temukan ribuan buku dari berbagai kategori yang tersedia di perpustakaan kami</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon">
                    <iconify-icon icon="mdi:calendar-check"></iconify-icon>
                </div>
                <h3>Kelola Peminjaman</h3>
                <p>Pinjam, kembalikan, dan pantau durasi peminjaman buku dengan mudah</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon">
                    <iconify-icon icon="mdi:heart"></iconify-icon>
                </div>
                <h3>Favorit & Wishlist</h3>
                <p>Tandai buku favorit Anda dan buat daftar buku yang ingin dipinjam nanti</p>
            </div>
        </div>

        <!-- Getting Started Section -->
        <div class="steps-section">
            <h2 style="margin: 0 0 24px 0;">
                <iconify-icon icon="mdi:lightning-bolt"></iconify-icon>
                Memulai
            </h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4 class="step-title">Jelajahi Dashboard</h4>
                    <p class="step-description">Mulai dari dashboard untuk melihat rekomendasi buku dan statistik
                        peminjaman Anda</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4 class="step-title">Cari Buku</h4>
                    <p class="step-description">Gunakan kolom pencarian untuk menemukan buku berdasarkan judul, penulis,
                        atau kategori</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4 class="step-title">Pinjam Buku</h4>
                    <p class="step-description">Pilih buku yang ingin dipinjam dan ikuti proses konfirmasi peminjaman
                    </p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h4 class="step-title">Pantau Peminjaman</h4>
                    <p class="step-description">Lihat riwayat peminjaman dan tanggal pengembalian di halaman riwayat</p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-container">
            <h2
                style="padding: 24px 24px 16px 24px; font-size: 20px; margin: 0; display: flex; align-items: center; gap: 14px;">
                <iconify-icon icon="mdi:frequently-asked-questions"
                    style="width: 26px; height: 26px; color: var(--accent);"></iconify-icon>
                Pertanyaan yang Sering Diajukan
            </h2>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Berapa lama saya dapat meminjam buku?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Durasi peminjaman standar adalah <strong>7 hari</strong> dari tanggal peminjaman. Anda dapat
                            melihat tanggal pengembalian yang tepat di halaman riwayat peminjaman. Jika Anda ingin
                            memperpanjang peminjaman, hubungi pustakawan melalui halaman bantuan atau datang langsung ke
                            perpustakaan.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana cara mengembalikan buku?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Ada beberapa cara untuk mengembalikan buku:</p>
                        <ul>
                            <li><strong>Secara langsung:</strong> Bawa buku ke perpustakaan dan serahkan kepada petugas
                            </li>
                            <li><strong>Melalui aplikasi:</strong> Tandai sebagai dikembalikan di halaman riwayat
                                peminjaman (jika tersedia)</li>
                            <li><strong>Informasi:</strong> Pastikan buku dalam kondisi baik dan kembalikan sesuai
                                tanggal yang ditentukan</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Apakah ada biaya denda jika saya terlambat mengembalikan?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Ya, terdapat denda keterlambatan untuk buku yang tidak dikembalikan tepat waktu. Besarnya
                            denda dapat dilihat pada peraturan perpustakaan atau dengan menghubungi petugas. Anda akan
                            menerima notifikasi otomatis jika peminjaman Anda mendekati batas waktu pengembalian.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Berapa jumlah maksimal buku yang dapat saya pinjam?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Jumlah maksimal buku yang dapat dipinjam secara bersamaan adalah <strong>5 buku</strong>.
                            Setelah mengembalikan salah satu buku, Anda dapat meminjam buku lainnya. Jika ada kebutuhan
                            khusus untuk meminjam lebih banyak, silakan diskusikan dengan petugas perpustakaan.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana cara menambahkan buku ke favorit?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Untuk menambahkan buku ke favorit:</p>
                        <ul>
                            <li>Buka detail buku yang ingin disimpan</li>
                            <li>Klik tombol hati (<iconify-icon icon="mdi:heart-outline"
                                    style="width: 16px; height: 16px;"></iconify-icon>) di halaman detail</li>
                            <li>Buku akan ditambahkan ke daftar favorit Anda</li>
                            <li>Akses daftar favorit dari menu sidebar di halaman "Koleksi Favorit"</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana cara mengubah profil saya?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Untuk mengubah profil Anda:</p>
                        <ul>
                            <li>Klik menu "Profil Saya" di sidebar navigasi</li>
                            <li>Pilih tombol "Edit Profil"</li>
                            <li>Ubah informasi yang diinginkan (nama, email, foto, dll)</li>
                            <li>Klik "Simpan Perubahan" untuk menyimpan</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Apakah saya bisa mengubah tema tampilan?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Ya, Anda dapat mengubah tema tampilan aplikasi. Untuk melakukannya:</p>
                        <ul>
                            <li>Buka halaman "Pengaturan" dari sidebar navigasi</li>
                            <li>Cari opsi "Preferensi Tampilan" atau "Tema"</li>
                            <li>Pilih tema yang Anda inginkan (Terang/Gelap)</li>
                            <li>Perubahan akan diterapkan secara otomatis</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana saya mendapat notifikasi reminder?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Sistem akan otomatis mengirimkan notifikasi untuk:</p>
                        <ul>
                            <li><strong>Reminder pengembalian:</strong> Dikirim 2-3 hari sebelum batas waktu
                                pengembalian</li>
                            <li><strong>Notifikasi keterlambatan:</strong> Dikirim jika Anda tidak mengembalikan buku
                                tepat waktu</li>
                            <li><strong>Notifikasi sistem:</strong> Untuk update penting dan informasi perpustakaan</li>
                            <li>Anda dapat melihat semua notifikasi di halaman "Notifikasi" atau di icon bell di header
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips Section -->
        <div class="help-section">
            <h2>
                <iconify-icon icon="mdi:lightbulb-on"></iconify-icon>
                Tips & Trik
            </h2>
            <div class="tips-container">
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Gunakan Filter Pencarian</div>
                        <div class="tip-text">Manfaatkan filter kategori dan opsi pengurutan untuk menemukan buku yang
                            Anda cari dengan lebih cepat</div>
                    </div>
                </div>
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Baca Detail Buku</div>
                        <div class="tip-text">Selalu baca deskripsi dan review buku sebelum meminjam untuk memastikan
                            sesuai dengan minat Anda</div>
                    </div>
                </div>
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Perhatikan Batas Waktu</div>
                        <div class="tip-text">Catat tanggal pengembalian buku Anda untuk menghindari denda keterlambatan
                        </div>
                    </div>
                </div>
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Jaga Kondisi Buku</div>
                        <div class="tip-text">Perlakukan buku dengan hati-hati untuk menjaga kualitas dan memastikan
                            tersedia untuk pembaca lainnya</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Need Help Section -->
        <div class="contact-section">
            <h2>Butuh Bantuan Lebih Lanjut?</h2>
            <p>Jika Anda tidak menemukan jawaban yang Anda cari, jangan ragu untuk menghubungi kami</p>
            <div class="contact-buttons">
                <a href="mailto:library@school.id" class="contact-btn">
                    <iconify-icon icon="mdi:email"></iconify-icon>
                    Email Kami
                </a>
                <a href="#" class="contact-btn" onclick="window.print(); return false;">
                    <iconify-icon icon="mdi:printer"></iconify-icon>
                    Cetak Halaman
                </a>
                <a href="student-dashboard.php" class="contact-btn"
                    style="background: rgba(255,255,255,0.2); color: white; border: 1px solid white;">
                    <iconify-icon icon="mdi:home"></iconify-icon>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const navToggle = document.getElementById('navToggle');
        const navSidebar = document.querySelector('.nav-sidebar');

        if (navToggle) {
            navToggle.addEventListener('click', function () {
                navSidebar.classList.toggle('active');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function (event) {
                if (!event.target.closest('.nav-sidebar') && !event.target.closest('.nav-toggle')) {
                    navSidebar.classList.remove('active');
                }
            });
        }

        // FAQ Toggle
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', function () {
                // Close other open items
                faqItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
                // Toggle current item
                item.classList.toggle('active');
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>
</body>

</html>