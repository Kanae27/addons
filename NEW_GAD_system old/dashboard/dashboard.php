<?php
session_start();

$isCentral = isset($_SESSION['username']) && $_SESSION['username'] === 'Central';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Theme transition styles */
        :root {
            transition: color-scheme 0.3s ease;
        }

        body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Prevent theme flash */
        html[data-bs-theme="dark"] {
            background-color: #1a1a1a !important;
        }

        /* Fade transition styles */
        body {
            opacity: 1;
            transition: opacity 0.05s ease-in-out;
        }

        .fade-out {
            opacity: 0;
        }
    </style>
    <script>
        // Immediate theme loading to prevent flash
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            document.documentElement.style.colorScheme = savedTheme;
        })();
    </script>
    <link rel="icon" type="image/x-icon" href="/images/Batangas_State_Logo.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 280px;
            --accent-color: #6a1b9a;
            --accent-hover: #4a148c;
            --scrollbar-thumb: #cccccc;
            --scrollbar-thumb-hover: #aaaaaa;
        }

        /* Light Theme Variables */
        [data-bs-theme="light"] {
            --bg-primary: #f0f0f0;
            --bg-secondary: #e5e5e5;
            --sidebar-bg: white;
            --text-primary: #444444;
            --text-secondary: #666666;
            --hover-color: #e1bee7;
            --card-bg: white;
            --border-color: #cccccc;
            --horizontal-bar: #cccccc;
        }

        /* Dark Theme Variables */
        [data-bs-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --sidebar-bg: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --hover-color: #8a4ebd;
            --card-bg: #2d2d2d;
            --border-color: #404040;
            --horizontal-bar: rgba(255, 255, 255, 0.1);
            --scrollbar-thumb: #6a1b9a;
            --scrollbar-thumb-hover: #9c27b0;
            --accent-color: #9c27b0;
            --accent-hover: #7b1fa2;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            padding: 20px;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - 40px);
            position: fixed;
            left: 20px;
            top: 20px;
            padding: 20px;
            background: var(--sidebar-bg);
            color: var(--text-primary);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.05), 0 5px 15px rgba(0, 0, 0, 0.05);
            z-index: 10;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: var(--sidebar-width);
            margin-top: -32px;
            background: var(--bg-primary);
            border-radius: 20px;
            position: relative;
            overflow-y: auto;
            max-height: 100vh;
            transition: margin-left 0.3s ease;
            display: flex;
            flex-direction: column;
            z-index: 5;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
        }

        .main-content::-webkit-scrollbar {
            width: 8px;
            background: transparent;
        }

        .main-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .main-content::-webkit-scrollbar-thumb {
            background-color: transparent;
            border-radius: 20px;
            transition: background-color 0.3s ease;
        }

        .main-content:hover::-webkit-scrollbar-thumb,
        .main-content:active::-webkit-scrollbar-thumb {
            background-color: var(--scrollbar-thumb);
        }

        .main-content:hover::-webkit-scrollbar-thumb:hover {
            background-color: var(--scrollbar-thumb-hover);
        }

        body::-webkit-scrollbar {
            width: 0;
            display: none;
        }

        body {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
            overflow: hidden;
        }

        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
                max-height: calc(100vh - 60px);
            }
        }

        .nav-link {
            color: var(--text-primary);
            padding: 10px 15px;
            border-radius: 12px;
            margin-bottom: 3px;
            position: relative;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 12px;
        }

        .nav-link:hover {
            background: var(--hover-color);
            color: white;
        }

        /* Restore light mode hover color */
        [data-bs-theme="light"] .nav-link:hover {
            color: var(--accent-color);
        }

        [data-bs-theme="light"] .nav-item .dropdown-menu .dropdown-item:hover {
            color: var(--accent-color);
        }

        [data-bs-theme="light"] .nav-item .dropdown-toggle[aria-expanded="true"] {
            color: var(--accent-color) !important;
        }

        .nav-link.active {
            color: var(--accent-color);
            position: relative;
        }

        /* Add hover state for active nav links in dark mode */
        [data-bs-theme="dark"] .nav-link.active:hover {
            color: white;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: var(--accent-color);
            border-radius: 0 2px 2px 0;
        }

        .nav-item {
            position: relative;
        }

        .nav-item .dropdown-menu {
            position: static !important;
            background: var(--sidebar-bg);
            border: 1px solid var(--border-color);
            padding: 8px 0;
            margin: 5px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            transform: none !important;
            display: none;
            overflow: visible;
            max-height: none;
        }

        .nav-item .dropdown-menu.show {
            display: block;
        }

        .nav-item .dropdown-menu .dropdown-item {
            padding: 6px 48px;
            color: var(--text-primary);
            position: relative;
            opacity: 0.85;
            background: transparent;
        }

        .nav-item .dropdown-menu .dropdown-item::before {
            content: '•';
            position: absolute;
            left: 35px;
            color: var(--accent-color);
        }

        .nav-item .dropdown-menu .dropdown-item:hover {
            background: var(--hover-color);
            color: white;
            opacity: 1;
        }

        .nav-item .dropdown-toggle[aria-expanded="true"] {
            color: white !important;
            background: var(--hover-color);
        }

        .nav-link.dropdown-toggle::after {
            transition: transform 0.3s ease;
        }

        .nav-link.dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }

        .logo-container {
            padding: 20px 0;
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 15px;
        }

        .logo-image {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            margin-bottom: -25px;
        }

        .logo-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .datetime-container {
            text-align: center;
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--horizontal-bar);
        }

        .datetime-container .date {
            font-size: 1.1rem;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .datetime-container .time {
            font-size: 1.4rem;
            font-weight: bold;
            color: var(--accent-color);
        }

        .analytics-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 290px;
            /* Increased from 260px */
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .analytics-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--card-accent), var(--card-accent-secondary));
        }

        /* Card color variations - First set */
        .analytics-card:nth-child(1) {
            --card-accent: #4361ee;
            --card-accent-secondary: #3a0ca3;
        }

        .analytics-card:nth-child(2) {
            --card-accent: #f72585;
            --card-accent-secondary: #7209b7;
        }

        .analytics-card:nth-child(3) {
            --card-accent: #06d6a0;
            --card-accent-secondary: #118ab2;
        }

        /* Second set for the second row */
        .analytics-row:nth-child(2) .analytics-card:nth-child(1) {
            --card-accent: #ff9e00;
            --card-accent-secondary: #ff0054;
        }

        .analytics-row:nth-child(2) .analytics-card:nth-child(2) {
            --card-accent: #8338ec;
            --card-accent-secondary: #3a86ff;
        }

        .analytics-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }

        .nav-content {
            flex-grow: 1;
            overflow-y: auto;
            max-height: calc(100vh - 470px);
            margin-bottom: 20px;
            padding-right: 5px;
            scrollbar-width: thin;
            scrollbar-color: rgba(106, 27, 154, 0.3) transparent;
            overflow-x: hidden;
        }

        .nav-content::-webkit-scrollbar {
            width: 2px;
        }

        .nav-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .nav-content::-webkit-scrollbar-thumb {
            background-color: rgba(106, 27, 154, 0.3);
            border-radius: 1px;
        }

        .nav-content::-webkit-scrollbar-thumb:hover {
            background-color: rgba(106, 27, 154, 0.5);
        }

        .nav-link:focus,
        .dropdown-toggle:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .dropdown-menu {
            outline: none !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        }

        .dropdown-item:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .logout-container {
            position: absolute;
            bottom: 20px;
            width: calc(var(--sidebar-width) - 40px);
        }

        .logout-button {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logout-button:hover {
            background: #6a1b9a;
            color: white !important;
            border-color: #6a1b9a;
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        .theme-switch {
            position: static;
            margin: 0 0 15px 0;
        }

        .theme-switch-button {
            width: 100%;
            height: auto;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .theme-switch-button .icon-container {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--accent-color);
            color: white;
        }

        .theme-switch-button .theme-text {
            flex: 1;
            text-align: left;
            font-weight: 500;
        }

        .theme-switch-button:hover {
            transform: translateY(-2px);
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .theme-switch-button:hover .icon-container {
            background: white;
            color: var(--accent-color);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
        }

        .page-title i {
            color: var(--accent-color);
            font-size: 2.2rem;
        }

        .page-title h2 {
            margin: 0;
            font-weight: 600;
        }

        .show>.nav-link {
            background: transparent !important;
            color: var(--accent-color) !important;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 991px) {
            :root {
                --sidebar-width: 240px;
            }

            body {
                padding: 0;
            }

            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
                left: 0;
                top: 0;
                height: 100vh;
                position: fixed;
                padding-top: 70px;
                border-radius: 0;
                box-shadow: 5px 0 25px rgba(0, 0, 0, 0.1);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 70px 15px 15px 15px;
                border-radius: 0;
                box-shadow: none;
            }

            .mobile-nav-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: var(--card-bg);
                border: none;
                border-radius: 8px;
                color: var(--text-primary);
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                cursor: pointer;
            }

            .mobile-nav-toggle:hover {
                background: var(--hover-color);
                color: var(--accent-color);
            }

            body.sidebar-open {
                overflow: hidden;
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .sidebar-backdrop.show {
                display: block;
            }

            .theme-switch {
                position: fixed;
                top: 20px;
                right: 30px;
            }

            .analytics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                margin-top: 10px;
            }

            .analytics-card {
                margin-bottom: 0;
            }
        }

        @media (max-width: 576px) {
            :root {
                --sidebar-width: 100%;
            }

            .sidebar {
                left: 0;
                top: 0;
                width: 100%;
                height: 100vh;
                padding-top: 60px;
            }

            .mobile-nav-toggle {
                width: 40px;
                height: 40px;
                top: 10px;
                left: 10px;
            }

            .theme-switch {
                top: 10px;
                right: 10px;
            }

            .theme-switch-button {
                padding: 8px 15px;
            }

            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                margin-top: 10px;
            }

            .page-title h2 {
                font-size: 1.5rem;
            }

            .welcome-card {
                height: 100px;
                margin-bottom: 1rem;
            }

            .welcome-content h1 {
                font-size: 1.5rem;
            }

            .welcome-subtitle {
                font-size: 1rem;
            }
        }

        @media (min-width: 768px) and (max-width: 1024px) {
            .main-content {
                padding: 1.5rem;
            }

            .analytics-grid {
                gap: 1rem;
            }

            .analytics-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .analytics-row:nth-child(2) {
                grid-template-columns: repeat(2, 1fr);
            }

            .welcome-card {
                height: 110px;
            }

            .welcome-content h1 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 767px) {

            .analytics-row,
            .analytics-row:nth-child(2) {
                grid-template-columns: 1fr;
            }

            .analytics-card {
                height: 300px;
            }
        }

        /* Theme-specific card styles */
        [data-bs-theme="light"] .analytics-card {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        [data-bs-theme="dark"] .analytics-card {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="light"] .analytics-card::before {
            opacity: 0.9;
        }

        [data-bs-theme="dark"] .analytics-card::before {
            opacity: 0.7;
        }

        /* Percentage indicators theme-specific styles */
        [data-bs-theme="light"] .percentage.up {
            background-color: rgba(40, 199, 111, 0.15);
        }

        [data-bs-theme="dark"] .percentage.up {
            background-color: rgba(40, 199, 111, 0.25);
        }

        [data-bs-theme="light"] .percentage.down {
            background-color: rgba(234, 84, 85, 0.15);
        }

        [data-bs-theme="dark"] .percentage.down {
            background-color: rgba(234, 84, 85, 0.25);
        }

        /* Modal styles for card popup */
        .card-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card-modal {
            background: var(--card-bg);
            border-radius: 20px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transform: scale(0.9);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            border-top: 5px solid var(--modal-accent, var(--accent-color));
        }

        .card-modal-filters {
            display: none;
            /* Hide the filter section */
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .filter-group select {
            width: 100%;
            padding: 0.5rem;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background: #2B3035;
            /* Light mode interactive color */
            color: var(--text-primary);
            height: 38px;
            /* Match the height of form-control */
        }

        .filter-group input.form-control {
            height: 38px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-primary);
        }

        .filter-group select:disabled {
            background-color: rgba(128, 128, 128, 0.1);
            opacity: 0.7;
            color: var(--text-secondary);
            cursor: not-allowed;
            border: 1px dotted var(--border-color);
            /* Add dotted border for disabled state */
        }

        [data-bs-theme="dark"] .filter-group select:disabled {
            background-color: #37383A;
            /* Dark mode disabled color as requested */
            border-color: rgba(255, 255, 255, 0.15);
            border-style: dotted;
            /* Dotted border in dark mode */
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(106, 27, 154, 0.2);
        }

        .card-modal.active {
            transform: scale(1);
            opacity: 1;
        }

        .card-modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            position: relative;
        }

        .card-modal-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.5rem;
            text-align: center;
        }

        .card-modal-close {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            position: absolute;
            right: 0;
            top: 0;
        }

        .card-modal-close:hover {
            background: transparent;
            color: var(--accent-color);
        }

        .card-modal-last-updated {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed var(--border-color);
        }

        .card-modal-body {
            margin-bottom: 1.5rem;
        }

        .card-modal-chart {
            height: 400px;
            margin-bottom: 1.5rem;
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .card-modal-chart canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .card-modal-footer {
            display: flex;
            justify-content: space-between;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
        }

        .card-modal::-webkit-scrollbar {
            width: 8px;
        }

        .card-modal::-webkit-scrollbar-track {
            background: var(--bg-primary);
            border-radius: 10px;
        }

        .card-modal::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 10px;
        }

        .card-modal::-webkit-scrollbar-thumb:hover {
            background: var(--scrollbar-thumb-hover);
        }

        .card-modal-details {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .detail-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        #modal-change.positive {
            color: #28c76f;
        }

        #modal-change.negative {
            color: #ea5455;
        }

        .card-modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1rem;
        }

        /* Add these styles to increase blur for modals */
        .modal-backdrop {
            backdrop-filter: blur(8px);
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-backdrop.show {
            opacity: 1;
        }

        /* Container for both buttons - reduced gap */
        .bottom-controls {
            position: absolute;
            bottom: 20px;
            width: calc(var(--sidebar-width) - 40px);
            display: flex;
            gap: 5px;
            align-items: center;
            margin-top: 15px;
        }

        /* Theme switch button - matched height */
        .theme-switch-button {
            width: 60px;
            height: 50px;
            /* Changed from fixed height to match logout button */
            padding: 12px 0;
            /* Added vertical padding to match logout button */
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        /* Hover effects */
        .logout-button:hover,
        .theme-switch-button:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }

        /* Updated blur effect for SweetAlert */
        .swal-blur-container {
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
        }

        /* Dropdown submenu styles */
        .dropdown-submenu {
            position: relative;
        }

        .dropdown-submenu .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -8px;
            margin-left: 1px;
            border-radius: 0 6px 6px 6px;
            display: none;
        }

        /* Add click-based display */
        .dropdown-submenu.show>.dropdown-menu {
            display: block;
        }

        .dropdown-submenu>a:after {
            display: block;
            content: " ";
            float: right;
            width: 0;
            height: 0;
            border-color: transparent;
            border-style: solid;
            border-width: 5px 0 5px 5px;
            border-left-color: var(--text-primary);
            margin-top: 5px;
            margin-right: -10px;
        }

        /* Update hover effect for arrow */
        .dropdown-submenu.show>a:after {
            border-left-color: var(--accent-color);
        }

        /* Mobile styles for dropdown submenu */
        @media (max-width: 991px) {
            .dropdown-submenu .dropdown-menu {
                position: static !important;
                left: 0;
                margin-left: 20px;
                margin-top: 0;
                border-radius: 0;
                border-left: 2px solid var(--accent-color);
            }

            .dropdown-submenu>a:after {
                transform: rotate(90deg);
                margin-top: 8px;
            }
        }

        .percentage {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
            min-width: 52px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .percentage.up {
            background-color: rgba(40, 199, 111, 0.2);
            color: #28c76f;
        }

        .percentage.down {
            background-color: rgba(234, 84, 85, 0.2);
            color: #ea5455;
        }

        .chart-container {
            flex: 1;
            min-height: 160px;
            max-height: 190px;
            position: relative;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Add specific styling for pie charts */
        #budgetChart,
        #activitiesChart,
        #beneficiariesChart {
            max-width: 60%;
            max-height: 160px;
            margin: 0;
            /* Left align charts */
        }

        .chart-values {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            width: 35%;
        }

        .chart-value-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 0.5rem;
            width: 100%;
        }

        .chart-value-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 0.2rem;
        }

        .chart-value-number {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .proposed-value {
            color: var(--card-accent-secondary);
        }

        .actual-value {
            color: var(--card-accent);
        }

        .placeholder-text {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--text-secondary);
            font-style: italic;
            opacity: 0.8;
        }

        /* Welcome Card Styles */
        .welcome-card {
            position: relative;
            height: 120px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            padding: 25px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .welcome-text {
            flex: 1;
            z-index: 2;
        }

        .welcome-text h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .welcome-text p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .welcome-icon {
            font-size: 4rem;
            opacity: 0.2;
            z-index: 1;
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Dashboard filters styling for modal only - kept for analytics cards */
        .dashboard-filters .filter-group {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.25);
            padding: 6px 12px;
            border-radius: 8px;
            margin-left: 10px;
        }

        .dashboard-filters .form-select {
            background-color: rgba(255, 255, 255, 0.25);
            border: none;
            color: white;
            padding: 5px 30px 5px 10px;
            font-size: 0.9rem;
            border-radius: 5px;
            cursor: pointer;
        }

        .dashboard-filters .form-select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
        }

        .dashboard-filters .form-select option {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .welcome-card {
                height: auto;
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }

            .dashboard-filters {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 15px;
                flex-direction: column;
                width: 100%;
            }

            .dashboard-filters .filter-group {
                width: 100%;
                margin-left: 0;
            }

            .welcome-icon {
                display: none;
            }
        }

        .detail-value.negative {
            color: #EA5455;
            background-color: rgba(234, 84, 85, 0.15);
        }

        /* Analytics Breakdown Styles */
        .card-modal-breakdown {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .breakdown-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }

        .breakdown-table-container {
            overflow-x: auto;
            margin-bottom: 1rem;
        }

        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
        }

        .breakdown-table th,
        .breakdown-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .breakdown-table th {
            font-weight: 600;
            color: var(--text-primary);
            background-color: rgba(0, 0, 0, 0.03);
        }

        .breakdown-table td {
            color: var(--text-secondary);
        }

        .breakdown-table tr:last-child td {
            border-bottom: none;
        }

        .breakdown-table .metric-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .breakdown-table .percentage-cell {
            font-weight: 600;
        }

        .breakdown-table .percentage-positive {
            color: #28C76F;
        }

        .breakdown-table .percentage-negative {
            color: #EA5455;
        }

        .breakdown-summary {
            margin-top: 1rem;
            padding: 1rem;
            background-color: rgba(0, 0, 0, 0.03);
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        [data-bs-theme="dark"] .breakdown-table th {
            background-color: rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="dark"] .breakdown-summary {
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Detailed Analysis Styles */
        .card-modal-detailed-analysis {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .detailed-analysis-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }

        .detailed-analysis-section {
            margin-bottom: 1.5rem;
            display: none;
            /* Hidden by default, will be shown based on chart type */
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .detailed-analysis-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .detailed-analysis-table th,
        .detailed-analysis-table td {
            padding: 0.75rem 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .detailed-analysis-table th {
            font-weight: 600;
            color: var(--text-primary);
            background-color: rgba(0, 0, 0, 0.03);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .detailed-analysis-table td {
            color: var(--text-secondary);
        }

        .detailed-analysis-table .summary-row {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 500;
        }

        .detailed-analysis-table .highlight-row {
            background-color: rgba(106, 27, 154, 0.05);
            color: var(--text-primary);
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 550px;
            /* Increased from 450px to 550px for more rows */
            overflow-y: auto;
        }

        .detailed-analysis-table tfoot {
            position: sticky;
            bottom: 0;
            background-color: var(--bg-secondary);
            z-index: 1;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
        }

        .completion-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            min-width: 60px;
        }

        .completion-high {
            background-color: rgba(40, 199, 111, 0.15);
            color: #28C76F;
        }

        .completion-medium {
            background-color: rgba(255, 159, 67, 0.15);
            color: #FF9F43;
        }

        .completion-low {
            background-color: rgba(234, 84, 85, 0.15);
            color: #EA5455;
        }

        [data-bs-theme="dark"] .detailed-analysis-table th {
            background-color: rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="dark"] .detailed-analysis-table .summary-row {
            background-color: rgba(255, 255, 255, 0.03);
        }

        [data-bs-theme="dark"] .detailed-analysis-table .highlight-row {
            background-color: rgba(106, 27, 154, 0.1);
        }

        [data-bs-theme="dark"] .detailed-analysis-table tfoot {
            background-color: var(--bg-secondary);
        }

        .data-empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
            font-style: italic;
        }

        .detailed-analysis-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .gender-breakdown {
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .gender-breakdown .male {
            color: #4681f6;
            margin-right: 12px;
        }

        .gender-breakdown .female {
            color: #f652a0;
        }

        .detailed-analysis-table tfoot tr {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .gender-breakdown .male {
            color: #4681f6;
            margin-right: 12px;
        }

        .gender-breakdown .female {
            color: #f652a0;
        }

        /* Add more specific and stronger color styling for male/female indicators */
        .chart-value-number .male,
        .chart-values .male {
            color: #007bff !important;
            font-weight: 600;
        }

        .chart-value-number .female,
        .chart-values .female {
            color: #ff1493 !important;
            font-weight: 600;
        }

        /* Responsive scaling for beneficiary numbers */
        .chart-value-number {
            word-break: break-word;
            line-height: 1.3;
            hyphens: auto;
        }

        /* Adjust for different number lengths */
        .chart-value-item:has(.chart-value-number > strong) {
            display: flex;
            flex-direction: column;
        }

        /* Handle long numbers better */
        .chart-value-number.proposed-value,
        .chart-value-number.actual-value {
            font-size: clamp(0.7rem, 2.5vw, 1rem);
        }

        /* Ensure the numbers don't crowd the chart */
        @media (max-width: 1400px) {

            .chart-value-number.proposed-value,
            .chart-value-number.actual-value {
                font-size: clamp(0.65rem, 2vw, 0.9rem);
            }
        }

        /* Ensure proper sizing on mobile */
        @media (max-width: 768px) {

            .chart-value-number.proposed-value,
            .chart-value-number.actual-value {
                font-size: 0.85rem;
            }
        }

        /* Special styling for the approval link - only visible to Central users */
        .approval-link {
            background-color: var(--accent-color);
            color: white !important;
            border-radius: 12px;
            margin-top: 5px;
            margin-bottom: 10px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .approval-link::before {
            content: '';
            position: absolute;
            right: -20px;
            top: 0;
            width: 40px;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            transform: skewX(-25deg);
            opacity: 0.7;
            transition: all 0.5s ease;
        }

        .approval-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background-color: var(--accent-hover) !important;
            color: white !important;
        }

        .approval-link:hover::before {
            right: 100%;
        }

        /* Ensure the icon in approval link stands out */
        .approval-link i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .approval-link:hover i {
            transform: scale(1.2);
        }

        /* Dark theme adjustments for approval link */
        [data-bs-theme="dark"] .approval-link {
            background-color: var(--accent-color);
        }

        [data-bs-theme="dark"] .approval-link:hover {
            background-color: var(--accent-hover) !important;
        }

        /* Revamped active state - distinctive but elegant */
        .approval-link.active {
            background-color: transparent !important;
            color: white !important;
            border: 2px solid white;
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: visible;
        }

        .approval-link.active::before {
            display: none;
        }

        .approval-link.active i {
            color: white;
        }

        /* Dark theme revamped active state */
        [data-bs-theme="dark"] .approval-link.active {
            background-color: transparent !important;
            color: white !important;
            border: 2px solid #e0b6ff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
        }

        [data-bs-theme="dark"] .approval-link.active i {
            color: #e0b6ff;
        }

        /* Fixed active state using accent color */
        .approval-link.active {
            background-color: transparent !important;
            color: var(--accent-color) !important;
            border: 2px solid var(--accent-color);
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .approval-link.active i {
            color: var(--accent-color);
        }

        /* Dark theme with accent color */
        [data-bs-theme="dark"] .approval-link.active {
            background-color: transparent !important;
            color: white !important;
            border: 2px solid var(--accent-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
        }

        [data-bs-theme="dark"] .approval-link.active i {
            color: var(--accent-color);
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Dark mode support */
        [data-bs-theme="dark"] .notification-badge {
            background-color: #ff5c6c;
        }

        /* Active state styling */
        .nav-link.active .notification-badge {
            background-color: white;
            color: var(--accent-color);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Function to initialize the detailed analysis HTML structure -->
    <script>
        function updateNotificationBadge(endpoint, action, badgeId) {
            const badge = document.getElementById(badgeId);
            if (!badge) return;

            const formData = new FormData();
            formData.append('action', action);

            fetch(endpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error fetching count:', error));
        }

        // Initial load and periodic updates
        document.addEventListener('DOMContentLoaded', function() {
            // For approval badge
            updateNotificationBadge('../approval/gbp_api.php', 'count_pending', 'approvalBadge');

            // Set interval for updates (only if not on the page with that badge active)
            const isApprovalPage = document.querySelector('.approval-link.active');
            if (!isApprovalPage) {
                setInterval(() => {
                    updateNotificationBadge('../approval/gbp_api.php', 'count_pending', 'approvalBadge');
                }, 30000); // Update every 30 seconds
            }
        });

        function initializeDetailedAnalysisHTML() {
            const detailedAnalysisContainer = document.querySelector('.card-modal-detailed-analysis');

            if (!detailedAnalysisContainer) {
                console.error('Detailed analysis container not found');
                return;
            }

            detailedAnalysisContainer.innerHTML = `
                <h3 class="detailed-analysis-title">Detailed Analysis</h3>
                
                <!-- Budget Utilization Analysis -->
                <div id="budget-utilization-analysis" class="detailed-analysis-section">
                    <h4 class="section-title">Budget Utilization by Activity</h4>
                    <div class="table-responsive">
                        <table class="detailed-analysis-table">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Campus</th>
                                    <th>Quarter</th>
                                    <th>Approved Budget</th>
                                    <th>PS Attribution</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="budget-activities-table-body">
                                <!-- Will be populated dynamically -->
                            </tbody>
                            <tfoot>
                                <tr class="summary-row">
                                    <td colspan="5"><strong>Total Budget Utilized</strong></td>
                                    <td id="total-budget-utilized">₱0.00</td>
                                </tr>
                                <tr class="summary-row">
                                    <td colspan="5"><strong>Total GAD Fund</strong></td>
                                    <td id="total-gad-fund">₱0.00</td>
                                </tr>
                                <tr class="summary-row highlight-row">
                                    <td colspan="5"><strong>Remaining Budget</strong></td>
                                    <td id="remaining-budget">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Activities Analysis -->
                <div id="activities-analysis" class="detailed-analysis-section">
                    <h4 class="section-title">Activities Analysis by Gender Issue</h4>
                    <div class="table-responsive">
                        <table class="detailed-analysis-table">
                            <thead>
                                <tr>
                                    <th>Gender Issue</th>
                                    <th>Campus</th>
                                    <th>Proposed Activities</th>
                                    <th>Actual Activities</th>
                                    <th>Remaining</th>
                                    <th>Completion</th>
                                </tr>
                            </thead>
                            <tbody id="activities-table-body">
                                <!-- Will be populated dynamically -->
                            </tbody>
                            <tfoot>
                                <tr class="summary-row highlight-row">
                                    <td colspan="2"><strong>Total</strong></td>
                                    <td id="total-proposed-activities">0</td>
                                    <td id="total-actual-activities">0</td>
                                    <td id="total-remaining-activities">0</td>
                                    <td id="total-activities-completion">0%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Beneficiaries Analysis -->
                <div id="beneficiaries-analysis" class="detailed-analysis-section">
                    <h4 class="section-title">Beneficiaries Analysis by Gender Issue</h4>
                    <div class="table-responsive">
                        <table class="detailed-analysis-table">
                            <thead>
                                <tr>
                                    <th>Gender Issue</th>
                                    <th>Campus</th>
                                    <th>Proposed</th>
                                    <th>Actual</th>
                                    <th>Remaining</th>
                                    <th>Completion</th>
                                </tr>
                            </thead>
                            <tbody id="beneficiaries-table-body">
                                <!-- Will be populated dynamically -->
                            </tbody>
                            <tfoot>
                                <tr class="summary-row highlight-row">
                                    <td colspan="2"><strong>Total</strong></td>
                                    <td id="total-proposed-beneficiaries">0</td>
                                    <td id="total-actual-beneficiaries">0</td>
                                    <td id="total-remaining-beneficiaries">0</td>
                                    <td id="total-beneficiaries-completion">0%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            `;
        }
    </script>
</head>

<body>
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle d-lg-none">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo-title">GAD SYSTEM</div>
            <div class="logo-image">
                <img src="../images/Batangas_State_Logo.png" alt="Batangas State Logo">
            </div>
        </div>
        <div class="datetime-container">
            <div class="date" id="current-date"></div>
            <div class="time" id="current-time"></div>
        </div>
        <div class="nav-content">
            <nav class="nav flex-column">
                <a href="../dashboard/dashboard.php" class="nav-link active">
                    <i class="fas fa-chart-line me-2"></i> Dashboard
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="staffDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-2"></i> Staff
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../academic_rank/academic.php">Academic Rank</a></li>
                        <li><a class="dropdown-item" href="../personnel_list/personnel_list.php">Personnel List</a></li>
                        <li><a class="dropdown-item" href="../signatory/sign.php">Signatory</a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="staffDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-alt me-2"></i> GPB
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../target_forms/target.php">Target</a></li>
                        <li><a class="dropdown-item" href="../gbp_forms/gbp.php">Data Entry</a></li>
                        <li><a class="dropdown-item" href="../gpb_reports/gbp_reports.php">Generate Form</a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="staffDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-invoice me-2"></i> PPAs
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../narrative_data_entry/data_entry.php">Data Entry</a></li>
                        <li><a class="dropdown-item" href="../ppas_proposal/gad_proposal.php">GAD Proposal</a></li>
                        <li><a class="dropdown-item" href="../narrative/narrative.php">GAD Narrative</a></li>
                        <li><a class="dropdown-item" href="../ppas_proposal/extension_proposal.php">Extension Proposal</a></li>
                        <li><a class="dropdown-item" href="../narrative/extension_narrative.php">Extension Narrative</a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <ul class="dropdown-menu">                       
                        <li><a class="dropdown-item" href="../ppas_reports/ppas_report.php">Quarterly Report</a></li>
                        <li><a class="dropdown-item" href="../ps_atrib_reports/ps.php">PS Attribution</a></li>
                        <li><a class="dropdown-item" href="../gpb_reports/gbp_reports.php">Annual Report</a></li>
                    </ul>
                </div>
                <?php
                $currentPage = basename($_SERVER['PHP_SELF']);
                if ($isCentral):
                ?>
                    <a href="../approval/approval.php" class="nav-link approval-link">
                        <i class="fas fa-check-circle me-2"></i> Approval
                        <span id="approvalBadge" class="notification-badge" style="display: none;">0</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="bottom-controls">
            <a href="../index.php" class="logout-button" onclick="handleLogout(event)">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
            <button class="theme-switch-button" onclick="toggleTheme()">
                <i class="fas fa-sun" id="theme-icon"></i>
            </buton>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-overlay"></div>
            <div class="welcome-content">
                <h1>Welcome, <span class="username"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; ?></span>.</h1>
                <div class="welcome-subtitle">Gender and Development Information System</div>
            </div>
        </div>

        <?php
        // Include database configuration
        include_once('../config.php');

        // Get the current user's campus from session
        $userCampus = isset($_SESSION['username']) ? $_SESSION['username'] : '';
        $isCentral = ($userCampus === 'Central');

        // Initialize variables
        $proposedBudget = 0;
        $actualBudget = 0;
        $proposedActivities = 0;
        $actualActivities = 0;
        $proposedBeneficiaries = 0;
        $actualBeneficiaries = 0;

        // Get current year
        $currentYear = date('Y');

        // Determine current quarter
        $currentMonth = date('n');
        $currentQuarter = ceil($currentMonth / 3);

        // Define quarters to include in cumulative data
        $quartersToInclude = array();
        for ($i = 1; $i <= $currentQuarter; $i++) {
            $quartersToInclude[] = "'Q$i'"; // Format as 'Q1', 'Q2', etc. with quotes for SQL
        }
        $quarterFilter = implode(',', $quartersToInclude);

        // Get GAD Fund from target table for current year
        if ($isCentral) {
            // For Central users, we want to get the SUM of all campuses' GAD funds
            $gadFundQuery = "SELECT SUM(total_gad_fund) as total_gad_fund FROM target WHERE year = '$currentYear'";
        } else {
            $gadFundQuery = "SELECT total_gad_fund FROM target WHERE year = '$currentYear' AND campus = '$userCampus'";
        }
        $gadFundResult = mysqli_query($conn, $gadFundQuery);
        if ($gadFundResult && mysqli_num_rows($gadFundResult) > 0) {
            $gadFundRow = mysqli_fetch_assoc($gadFundResult);
            $proposedBudget = floatval($gadFundRow['total_gad_fund']);
        } else {
            // Fallback to old method if no target found for current year
            if ($isCentral) {
                $budgetQuery = "SELECT SUM(gad_budget) as total_proposed_budget FROM gpb_entries";
            } else {
                $budgetQuery = "SELECT SUM(gad_budget) as total_proposed_budget FROM gpb_entries WHERE campus = '$userCampus'";
            }
            $budgetResult = mysqli_query($conn, $budgetQuery);
            if ($budgetResult && mysqli_num_rows($budgetResult) > 0) {
                $budgetRow = mysqli_fetch_assoc($budgetResult);
                $proposedBudget = floatval($budgetRow['total_proposed_budget']);
            }
        }

        // Get actual budget usage from ppas_forms for quarters up to current quarter
        if ($isCentral) {
            // For Central users, get data from all campuses
            $actualBudgetQuery = "SELECT SUM(approved_budget) as total_actual_budget, SUM(ps_attribution) as total_ps_attribution FROM ppas_forms WHERE quarter IN ($quarterFilter) AND year = '$currentYear'";
        } else {
            $actualBudgetQuery = "SELECT SUM(approved_budget) as total_actual_budget, SUM(ps_attribution) as total_ps_attribution FROM ppas_forms WHERE quarter IN ($quarterFilter) AND campus = '$userCampus' AND year = '$currentYear'";
        }
        $actualBudgetResult = mysqli_query($conn, $actualBudgetQuery);
        if ($actualBudgetResult && mysqli_num_rows($actualBudgetResult) > 0) {
            $actualBudgetRow = mysqli_fetch_assoc($actualBudgetResult);
            $actualBudget = floatval($actualBudgetRow['total_actual_budget']);
            $psAttribution = floatval($actualBudgetRow['total_ps_attribution']);
            $actualBudget += $psAttribution; // Include PS Attribution in the actual budget
        }

        // Get proposed activities from gpb_entries
        if ($isCentral) {
            // For Central users, get activities from all campuses
            $activitiesQuery = "SELECT SUM(total_activities) as total_proposed_activities FROM gpb_entries WHERE year = '$currentYear'";
        } else {
            $activitiesQuery = "SELECT SUM(total_activities) as total_proposed_activities FROM gpb_entries WHERE campus = '$userCampus' AND year = '$currentYear'";
        }
        $activitiesResult = mysqli_query($conn, $activitiesQuery);
        if ($activitiesResult && mysqli_num_rows($activitiesResult) > 0) {
            $activitiesRow = mysqli_fetch_assoc($activitiesResult);
            $proposedActivities = intval($activitiesRow['total_proposed_activities']);
        }

        // Get actual activities (count of rows in ppas_forms) for quarters up to current quarter
        if ($isCentral) {
            // For Central users, count activities from all campuses
            $actualActivitiesQuery = "SELECT COUNT(*) as total_actual_activities FROM ppas_forms WHERE quarter IN ($quarterFilter) AND year = '$currentYear'";
        } else {
            $actualActivitiesQuery = "SELECT COUNT(*) as total_actual_activities FROM ppas_forms WHERE quarter IN ($quarterFilter) AND campus = '$userCampus' AND year = '$currentYear'";
        }
        $actualActivitiesResult = mysqli_query($conn, $actualActivitiesQuery);
        if ($actualActivitiesResult && mysqli_num_rows($actualActivitiesResult) > 0) {
            $actualActivitiesRow = mysqli_fetch_assoc($actualActivitiesResult);
            $actualActivities = intval($actualActivitiesRow['total_actual_activities']);
        }

        // Get proposed beneficiaries from gpb_entries
        if ($isCentral) {
            // For Central users, sum beneficiaries from all campuses
            $beneficiariesQuery = "SELECT SUM(total_participants) as total_proposed_beneficiaries FROM gpb_entries WHERE year = '$currentYear'";
        } else {
            $beneficiariesQuery = "SELECT SUM(total_participants) as total_proposed_beneficiaries FROM gpb_entries WHERE campus = '$userCampus' AND year = '$currentYear'";
        }
        $beneficiariesResult = mysqli_query($conn, $beneficiariesQuery);
        if ($beneficiariesResult && mysqli_num_rows($beneficiariesResult) > 0) {
            $beneficiariesRow = mysqli_fetch_assoc($beneficiariesResult);
            $proposedBeneficiaries = intval($beneficiariesRow['total_proposed_beneficiaries']);
        }

        // Get actual beneficiaries from ppas_forms for quarters up to current quarter
        if ($isCentral) {
            // For Central users, sum beneficiaries from all campuses
            $actualBeneficiariesQuery = "SELECT SUM(total_beneficiaries) as total_actual_beneficiaries 
                FROM ppas_forms 
                WHERE quarter IN ($quarterFilter) AND year = '$currentYear'";
        } else {
            $actualBeneficiariesQuery = "SELECT SUM(total_beneficiaries) as total_actual_beneficiaries 
                FROM ppas_forms 
                WHERE quarter IN ($quarterFilter) AND campus = '$userCampus' AND year = '$currentYear'";
        }
        $actualBeneficiariesResult = mysqli_query($conn, $actualBeneficiariesQuery);
        if ($actualBeneficiariesResult && mysqli_num_rows($actualBeneficiariesResult) > 0) {
            $actualBeneficiariesRow = mysqli_fetch_assoc($actualBeneficiariesResult);
            $actualBeneficiaries = intval($actualBeneficiariesRow['total_actual_beneficiaries']);
        }

        // Calculate percentages for indicators
        $budgetPercentage = ($proposedBudget > 0) ? round(($actualBudget / $proposedBudget) * 100) : 0;
        $activitiesPercentage = ($proposedActivities > 0) ? round(($actualActivities / $proposedActivities) * 100) : 0;
        $beneficiariesPercentage = ($proposedBeneficiaries > 0) ? round(($actualBeneficiaries / $proposedBeneficiaries) * 100) : 0;

        // Calculate the relative percentage for display (0% is baseline where values are equal)
        $budgetRelativePercentage = ($proposedBudget > 0) ? round((($actualBudget - $proposedBudget) / $proposedBudget) * 100) : 0;
        $activitiesRelativePercentage = ($proposedActivities > 0) ? round((($actualActivities - $proposedActivities) / $proposedActivities) * 100) : 0;
        $beneficiariesRelativePercentage = ($proposedBeneficiaries > 0) ? round((($actualBeneficiaries - $proposedBeneficiaries) / $proposedBeneficiaries) * 100) : 0;

        // Determine if trends are met or not met based on relative percentages
        $budgetTrend = ($budgetRelativePercentage >= 0) ? "Met" : "Not Met";
        $activitiesTrend = ($activitiesRelativePercentage >= 0) ? "Met" : "Not Met";
        $beneficiariesTrend = ($beneficiariesRelativePercentage >= 0) ? "Met" : "Not Met";

        // Pass data to JavaScript
        echo "<script>
            const budgetData = {
                proposed: $proposedBudget,
                actual: $actualBudget,
                percentage: $budgetPercentage
            };
            
            const activitiesData = {
                proposed: $proposedActivities,
                actual: $actualActivities,
                percentage: $activitiesPercentage
            };
            
            const beneficiariesData = {
                proposed: $proposedBeneficiaries,
                actual: $actualBeneficiaries,
                percentage: $beneficiariesPercentage
            };
            
            // Current quarter information for JavaScript
            const systemCurrentQuarter = $currentQuarter;
            const systemCurrentYear = $currentYear;
        </script>";
        ?>

        <!-- JavaScript function to update quarter labels -->
        <script>
            // Function to update current quarter labels
            function updateQuarterLabels() {
                // Use the PHP-passed current quarter and year
                const quarter = systemCurrentQuarter;
                const year = systemCurrentYear;

                let quarterText = `Q${quarter} ${year}`;

                // Update quarter text with cumulative information
                let cumulativeText = "";
                if (quarter > 1) {
                    cumulativeText = ` (Q1-Q${quarter})`;
                }
                quarterText += cumulativeText;

                // Update all quarter labels
                document.getElementById('current-quarter-budget').textContent = quarterText;
                document.getElementById('current-quarter-activities').textContent = quarterText;
                document.getElementById('current-quarter-beneficiaries').textContent = quarterText;
                document.getElementById('current-quarter-quarterly').textContent = quarterText;
                document.getElementById('current-quarter-annual').textContent = quarterText;
            }
        </script>

        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <!-- First Row of Cards -->
            <div class="analytics-row">
                <!-- Budget Usage Card -->
                <div class="analytics-card" data-chart="budgetChart" onclick="openCardModal('budgetChart', 'Budget Utilization Analysis')">
                    <div class="card-header">
                        <h3>GAD Fund vs Budget Utilization</h3>
                        <span class="percentage <?php echo ($budgetRelativePercentage >= 0) ? 'up' : 'down'; ?>">
                            <?php echo ($budgetRelativePercentage > 0 ? '+' : '') . $budgetRelativePercentage . '%'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="budgetChart"></canvas>
                            <div class="chart-values">
                                <div class="chart-value-item">
                                    <div class="chart-value-label">GAD Fund (<?php echo $currentYear; ?>):</div>
                                    <div class="chart-value-number proposed-value">₱<?php echo number_format($proposedBudget, 2); ?></div>
                                </div>
                                <div class="chart-value-item">
                                    <div class="chart-value-label">Budget Utilization:</div>
                                    <div class="chart-value-number actual-value">₱<?php echo number_format($actualBudget, 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="trend-label">Trend: <?php echo $budgetTrend; ?></span>
                        <span class="quarter-label" id="current-quarter-budget"></span>
                    </div>
                </div>

                <!-- Activities Card -->
                <div class="analytics-card" data-chart="activitiesChart" onclick="openCardModal('activitiesChart', 'Activities Analysis')">
                    <div class="card-header">
                        <h3>Proposed vs Actual Activities</h3>
                        <span class="percentage <?php echo ($activitiesRelativePercentage >= 0) ? 'up' : 'down'; ?>">
                            <?php echo ($activitiesRelativePercentage > 0 ? '+' : '') . $activitiesRelativePercentage . '%'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="activitiesChart"></canvas>
                            <div class="chart-values">
                                <div class="chart-value-item">
                                    <div class="chart-value-label">Proposed Activities:</div>
                                    <div class="chart-value-number proposed-value"><?php echo number_format($proposedActivities); ?></div>
                                </div>
                                <div class="chart-value-item">
                                    <div class="chart-value-label">Actual Activities:</div>
                                    <div class="chart-value-number actual-value"><?php echo number_format($actualActivities); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="trend-label">Trend: <?php echo $activitiesTrend; ?></span>
                        <span class="quarter-label" id="current-quarter-activities"></span>
                    </div>
                </div>

                <!-- Beneficiaries Card -->
                <div class="analytics-card" data-chart="beneficiariesChart" onclick="openCardModal('beneficiariesChart', 'Beneficiaries Analysis')">
                    <div class="card-header">
                        <h3>Proposed vs Actual Beneficiaries</h3>
                        <span class="percentage <?php echo ($beneficiariesRelativePercentage >= 0) ? 'up' : 'down'; ?>">
                            <?php echo ($beneficiariesRelativePercentage > 0 ? '+' : '') . $beneficiariesRelativePercentage . '%'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="beneficiariesChart"></canvas>
                            <div class="chart-values">
                                <div class="chart-value-item">
                                    <div class="chart-value-label">Proposed Beneficiaries:</div>
                                    <div class="chart-value-number proposed-value">
                                        <?php
                                        // Query to get male and female proposed beneficiaries
                                        $proposedGenderQuery = "SELECT SUM(male_participants) as male, SUM(female_participants) as female 
                                                            FROM gpb_entries WHERE " . ($isCentral ? "" : "campus = '$userCampus' AND ") . "year = '$currentYear'";
                                        $proposedGenderResult = mysqli_query($conn, $proposedGenderQuery);
                                        $proposedMale = 0;
                                        $proposedFemale = 0;

                                        if ($proposedGenderResult && mysqli_num_rows($proposedGenderResult) > 0) {
                                            $genderRow = mysqli_fetch_assoc($proposedGenderResult);
                                            $proposedMale = intval($genderRow['male']);
                                            $proposedFemale = intval($genderRow['female']);
                                        }

                                        echo number_format($proposedBeneficiaries) . "<br>";
                                        echo "<strong class='male'>" . number_format($proposedMale) . " <i class='fas fa-male'></i></strong> | <strong class='female'>" . number_format($proposedFemale) . " <i class='fas fa-female'></i></strong>";
                                        ?>
                                    </div>
                                </div>
                                <div class="chart-value-item">
                                    <div class="chart-value-label">Actual Beneficiaries:</div>
                                    <div class="chart-value-number actual-value">
                                        <?php
                                        // Query to get male and female actual beneficiaries
                                        $actualGenderQuery = "SELECT SUM(total_male) as male, SUM(total_female) as female 
                                                          FROM ppas_forms WHERE quarter IN ($quarterFilter) AND " .
                                            ($isCentral ? "" : "campus = '$userCampus' AND ") . "year = '$currentYear'";
                                        $actualGenderResult = mysqli_query($conn, $actualGenderQuery);
                                        $actualMale = 0;
                                        $actualFemale = 0;

                                        if ($actualGenderResult && mysqli_num_rows($actualGenderResult) > 0) {
                                            $genderRow = mysqli_fetch_assoc($actualGenderResult);
                                            $actualMale = intval($genderRow['male']);
                                            $actualFemale = intval($genderRow['female']);
                                        }

                                        echo number_format($actualBeneficiaries) . "<br>";
                                        echo "<strong class='male'>" . number_format($actualMale) . " <i class='fas fa-male'></i></strong> | <strong class='female'>" . number_format($actualFemale) . " <i class='fas fa-female'></i></strong>";
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="trend-label">Trend: <?php echo $beneficiariesTrend; ?></span>
                        <span class="quarter-label" id="current-quarter-beneficiaries"></span>
                    </div>
                </div>
            </div>

            <!-- Second Row of Cards -->
            <div class="analytics-row">
                <!-- GAD Fund Quarterly Card -->
                <div class="analytics-card" onclick="openCardModal('gadQuarterlyChart', 'GAD Fund Quarterly')">
                    <div class="card-header">
                        <h3>Quarterly Report vs GAD Fund</h3>
                        <span class="percentage up">0%</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <div class="placeholder-text">Placeholder</div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="trend-label">Trend: Met</span>
                        <span class="quarter-label" id="current-quarter-quarterly"></span>
                    </div>
                </div>

                <!-- Annual GAD Fund Card -->
                <div class="analytics-card" onclick="openCardModal('gadAnnualChart', 'GAD Fund Annual')">
                    <div class="card-header">
                        <h3>Annual Report vs GAD Fund</h3>
                        <span class="percentage up">0%</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <div class="placeholder-text">Placeholder</div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <span class="trend-label">Trend: Met</span>
                        <span class="quarter-label" id="current-quarter-annual"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Modal Backdrop -->
    <div class="card-modal-backdrop" id="card-modal-backdrop">
        <!-- Card Modal -->
        <div class="card-modal" id="card-modal" onclick="event.stopPropagation()">
            <div class="card-modal-header">
                <h2 id="card-modal-title"></h2>
                <button class="card-modal-close" onclick="closeCardModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Campus filter removed as requested -->
            <input type="hidden" id="modal-campus-filter" value="<?php echo ($isCentral ? '' : $userCampus); ?>" />
            <div class="card-modal-last-updated">
                <span id="card-modal-footer-text"></span>
            </div>
            <div class="card-modal-body">
                <div class="card-modal-chart" id="card-modal-chart"></div>
                <div class="card-modal-details">
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value" id="modal-status">Positive Growth</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Change:</div>
                        <div class="detail-value" id="modal-change">+24%</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Period:</div>
                        <div class="detail-value" id="modal-period">Q1 2025</div>
                    </div>
                </div>

                <!-- Analytics Breakdown Section -->
                <div class="card-modal-breakdown">
                    <h3 class="breakdown-title">Analytics Breakdown</h3>
                    <div class="breakdown-table-container">
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Proposed</th>
                                    <th>Actual</th>
                                    <th>% Achieved</th>
                                </tr>
                            </thead>
                            <tbody id="breakdown-table-body">
                                <!-- Table rows will be populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="breakdown-summary" id="breakdown-summary">
                        <!-- Summary will be populated dynamically -->
                    </div>
                </div>

                <!-- Detailed Analysis Section - NEW -->
                <div class="card-modal-detailed-analysis">
                    <h3 class="detailed-analysis-title">Detailed Analysis</h3>

                    <!-- Budget Utilization Analysis -->
                    <div id="budget-utilization-analysis" class="detailed-analysis-section">
                        <h4 class="section-title">Budget Utilization by Activity</h4>
                        <div class="table-responsive">
                            <table class="detailed-analysis-table">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Campus</th>
                                        <th>Quarter</th>
                                        <th>Approved Budget</th>
                                        <th>PS Attribution</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="budget-activities-table-body">
                                    <!-- Will be populated dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr class="summary-row">
                                        <td colspan="5"><strong>Total Budget Utilized</strong></td>
                                        <td id="total-budget-utilized">₱0.00</td>
                                    </tr>
                                    <tr class="summary-row">
                                        <td colspan="5"><strong>Total GAD Fund</strong></td>
                                        <td id="total-gad-fund">₱0.00</td>
                                    </tr>
                                    <tr class="summary-row highlight-row">
                                        <td colspan="5"><strong>Remaining Budget</strong></td>
                                        <td id="remaining-budget">₱0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Activities Analysis -->
                    <div id="activities-analysis" class="detailed-analysis-section">
                        <h4 class="section-title">Activities Analysis by Gender Issue</h4>
                        <div class="table-responsive">
                            <table class="detailed-analysis-table">
                                <thead>
                                    <tr>
                                        <th>Gender Issue</th>
                                        <th>Campus</th>
                                        <th>Proposed Activities</th>
                                        <th>Actual Activities</th>
                                        <th>Remaining</th>
                                        <th>Completion</th>
                                    </tr>
                                </thead>
                                <tbody id="activities-table-body">
                                    <!-- Will be populated dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr class="summary-row highlight-row">
                                        <td colspan="2"><strong>Total</strong></td>
                                        <td id="total-proposed-activities">0</td>
                                        <td id="total-actual-activities">0</td>
                                        <td id="total-remaining-activities">0</td>
                                        <td id="total-activities-completion">0%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Beneficiaries Analysis -->
                    <div id="beneficiaries-analysis" class="detailed-analysis-section">
                        <h4 class="section-title">Beneficiaries Analysis by Gender Issue</h4>
                        <div class="table-responsive">
                            <table class="detailed-analysis-table">
                                <thead>
                                    <tr>
                                        <th>Gender Issue</th>
                                        <th>Campus</th>
                                        <th>Proposed</th>
                                        <th>Actual</th>
                                        <th>Remaining</th>
                                        <th>Completion</th>
                                    </tr>
                                </thead>
                                <tbody id="beneficiaries-table-body">
                                    <!-- Will be populated dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr class="summary-row highlight-row">
                                        <td colspan="2"><strong>Total</strong></td>
                                        <td id="total-proposed-beneficiaries">0</td>
                                        <td id="total-actual-beneficiaries">0</td>
                                        <td id="total-remaining-beneficiaries">0</td>
                                        <td id="total-beneficiaries-completion">0%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-modal-footer">
            </div>
        </div>
    </div>

    <style>
        /* Welcome Card Styles */
        .welcome-card {
            position: relative;
            height: 120px;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 1rem;
            width: 100%;
            min-height: 120px;
            display: block;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -310px;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../images/campus3.jpg') center center/cover no-repeat;
            z-index: 1;
            opacity: 0.9;
        }

        .welcome-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(106, 27, 154, 0.85), rgba(74, 20, 140, 0.7));
            z-index: 2;
        }

        .welcome-content {
            position: relative;
            z-index: 3;
            padding: 1.5rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .welcome-content h1 {
            font-size: 2rem;
            margin: 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            line-height: 1.2;
        }

        .welcome-subtitle {
            color: white;
            font-size: 1.2rem;
            margin-top: 0.5rem;
        }

        .username {
            color: #ffff00;
            /* Bright yellow */
            font-weight: 600;
        }

        /* Analytics Grid Styles */
        .analytics-grid {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .analytics-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            width: 100%;
        }

        .analytics-row:nth-child(2) {
            grid-template-columns: repeat(2, 1fr);
        }

        .analytics-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 290px;
            /* Increased from 260px */
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .analytics-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--card-accent), var(--card-accent-secondary));
        }

        /* Card color variations - First set */
        .analytics-card:nth-child(1) {
            --card-accent: #4361ee;
            --card-accent-secondary: #3a0ca3;
        }

        .analytics-card:nth-child(2) {
            --card-accent: #f72585;
            --card-accent-secondary: #7209b7;
        }

        .analytics-card:nth-child(3) {
            --card-accent: #06d6a0;
            --card-accent-secondary: #118ab2;
        }

        /* Second set for the second row */
        .analytics-row:nth-child(2) .analytics-card:nth-child(1) {
            --card-accent: #ff9e00;
            --card-accent-secondary: #ff0054;
        }

        .analytics-row:nth-child(2) .analytics-card:nth-child(2) {
            --card-accent: #8338ec;
            --card-accent-secondary: #3a86ff;
        }

        .analytics-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-header h3 {
            font-size: 1rem;
            /* Reduced from 1.1rem */
            color: var(--text-primary);
            margin: 0;
            flex: 1;
            padding-right: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .percentage {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
            min-width: 52px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .percentage.up {
            background-color: rgba(40, 199, 111, 0.2);
            color: #28c76f;
        }

        .percentage.down {
            background-color: rgba(234, 84, 85, 0.2);
            color: #ea5455;
        }

        .chart-container {
            flex: 1;
            min-height: 160px;
            max-height: 190px;
            position: relative;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Add specific styling for pie charts */
        #budgetChart,
        #activitiesChart,
        #beneficiariesChart {
            max-width: 60%;
            max-height: 160px;
            margin: 0;
            /* Left align charts */
        }

        .chart-values {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            width: 35%;
        }

        .chart-value-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 0.5rem;
            width: 100%;
        }

        .chart-value-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 0.2rem;
        }

        .chart-value-number {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .proposed-value {
            color: var(--card-accent-secondary);
        }

        .actual-value {
            color: var(--card-accent);
        }

        .placeholder-text {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--text-secondary);
            font-style: italic;
            opacity: 0.8;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            /* Smaller font size */
            color: var(--text-secondary);
            margin-top: auto;
        }

        .trend-label,
        .date-label,
        .quarter-label {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quarter-label {
            font-weight: 500;
            color: var(--accent-color);
        }

        @media (max-width: 1400px) {
            .analytics-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .analytics-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }


        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .welcome-card {
                height: 100px;
            }

            .welcome-content h1 {
                font-size: 1.5rem;
            }

            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .analytics-row {
                grid-template-columns: 1fr;
            }

            .analytics-card {
                height: 290px;
                /* Increased height for mobile to fit values */
                padding: 1rem;
                /* Reduced padding */
            }

            .card-header h3 {
                font-size: 0.9rem;
                /* Smaller font on mobile */
            }

            .chart-container {
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                gap: 0.5rem;
            }

            #budgetChart,
            #activitiesChart,
            #beneficiariesChart {
                max-width: 100%;
                max-height: 120px;
            }

            .chart-values {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
            }

            .chart-value-item {
                width: 48%;
            }
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Theme Switch and DateTime Script -->
    <script>
        // Theme switching functionality
        function updateThemeIcon(theme) {
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                themeIcon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            }
        }

        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            // Update theme
            html.setAttribute('data-bs-theme', newTheme);
            html.style.colorScheme = newTheme;
            localStorage.setItem('theme', newTheme);

            // Update icon
            updateThemeIcon(newTheme);

            // Update charts if they exist
            if (typeof updateChartsTheme === 'function') {
                updateChartsTheme(newTheme);
            }
        }

        // Function to adjust font size based on content length
        function adjustBeneficiaryFontSizes() {
            const numberElements = document.querySelectorAll('.chart-value-number.proposed-value, .chart-value-number.actual-value');

            numberElements.forEach(el => {
                const text = el.textContent.trim();
                const totalLength = text.length;

                // Apply different font sizes based on content length
                if (totalLength > 30) {
                    el.style.fontSize = '0.75rem';
                } else if (totalLength > 20) {
                    el.style.fontSize = '0.85rem';
                }

                // Make sure numbers with long values wrap properly
                if (el.innerHTML.includes('fa-male') || el.innerHTML.includes('fa-female')) {
                    el.style.wordBreak = 'break-word';
                    el.style.hyphens = 'auto';
                }
            });
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Handle dropdown submenu click behavior
            const dropdownSubmenus = document.querySelectorAll('.dropdown-submenu > a');
            dropdownSubmenus.forEach(submenu => {
                submenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Close other open submenus
                    const otherSubmenus = document.querySelectorAll('.dropdown-submenu.show');
                    otherSubmenus.forEach(menu => {
                        if (menu !== this.parentElement) {
                            menu.classList.remove('show');
                        }
                    });

                    // Toggle current submenu
                    this.parentElement.classList.toggle('show');
                });
            });

            // Close submenus when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown-submenu')) {
                    const openSubmenus = document.querySelectorAll('.dropdown-submenu.show');
                    openSubmenus.forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });

            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            document.documentElement.style.colorScheme = savedTheme;
            updateThemeIcon(savedTheme);

            // Initialize charts with current theme if they exist
            if (typeof updateChartsTheme === 'function') {
                updateChartsTheme(savedTheme);
            }
        });

        function updateDateTime() {
            const now = new Date();
            const dateOptions = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const timeOptions = {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            };

            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
        }

        // Update time every second
        updateDateTime();
        setInterval(updateDateTime, 1000);
    </script>

    <!-- Mobile Navigation Toggle Script -->
    <script>
        // Mobile Navigation Toggle
        const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.querySelector('.sidebar-backdrop');
        const body = document.body;

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
            body.classList.toggle('sidebar-open');
        }

        mobileNavToggle.addEventListener('click', toggleSidebar);
        backdrop.addEventListener('click', toggleSidebar);

        // Close sidebar when clicking a link on mobile
        const mobileNavLinks = document.querySelectorAll('.sidebar .nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    toggleSidebar();
                }
            });
        });

        // Function to update current quarter labels
        function updateQuarterLabels() {
            const now = new Date();
            const month = now.getMonth(); // 0-11
            const year = now.getFullYear();

            // Determine current quarter
            let quarter;
            if (month <= 2) quarter = "Q1";
            else if (month <= 5) quarter = "Q2";
            else if (month <= 8) quarter = "Q3";
            else quarter = "Q4";

            const quarterText = `${quarter} ${year}`;

            // Update all quarter labels
            document.getElementById('current-quarter-budget').textContent = quarterText;
            document.getElementById('current-quarter-activities').textContent = quarterText;
            document.getElementById('current-quarter-beneficiaries').textContent = quarterText;
            document.getElementById('current-quarter-quarterly').textContent = quarterText;
            document.getElementById('current-quarter-annual').textContent = quarterText;
        }

        // Update quarter labels on load and periodically
        document.addEventListener('DOMContentLoaded', () => {
            updateQuarterLabels();
            // Update quarter label every minute (though quarters don't change that frequently)
            setInterval(updateQuarterLabels, 60000);
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Function to create pie charts
        function createPieChart(chartId, proposed, actual, label) {
            const ctx = document.getElementById(chartId).getContext('2d');
            // Set high-DPI rendering
            ctx.canvas.style.width = '100%';
            ctx.canvas.style.height = '100%';

            // Scale for higher resolution
            const dpr = window.devicePixelRatio || 1;
            const rect = ctx.canvas.getBoundingClientRect();
            ctx.canvas.width = rect.width * dpr;
            ctx.canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);

            const isDarkTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const cardElement = document.getElementById(chartId).closest('.analytics-card');

            // Get the card's accent colors
            const cardStyles = getComputedStyle(cardElement);
            const proposedColor = cardStyles.getPropertyValue('--card-accent-secondary').trim();
            const actualColor = cardStyles.getPropertyValue('--card-accent').trim();

            // Create rgba versions with transparency
            const proposedColorRGBA = proposedColor ? `${proposedColor}BB` : 'rgba(54, 162, 235, 0.7)';
            const actualColorRGBA = actualColor ? `${actualColor}BB` : 'rgba(75, 192, 192, 0.7)';

            // Set text color based on theme
            const textColor = isDarkTheme ? '#ffffff' : '#666666';

            return new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Proposed', 'Actual'],
                    datasets: [{
                        data: [proposed, actual],
                        backgroundColor: [
                            proposedColorRGBA,
                            actualColorRGBA
                        ],
                        borderColor: [
                            proposedColor || 'rgba(54, 162, 235, 1)',
                            actualColor || 'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    devicePixelRatio: dpr,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    if (label === 'Proposed' || label === 'Actual') {
                                        if (chartId === 'budgetChart') {
                                            return label + ': ₱' + value.toLocaleString();
                                        } else {
                                            return label + ': ' + value.toLocaleString();
                                        }
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Function to create line charts
        function createLineChart(chartId, label) {
            const ctx = document.getElementById(chartId).getContext('2d');
            // Set high-DPI rendering
            ctx.canvas.style.width = '100%';
            ctx.canvas.style.height = '100%';

            // Scale for higher resolution
            const dpr = window.devicePixelRatio || 1;
            const rect = ctx.canvas.getBoundingClientRect();
            ctx.canvas.width = rect.width * dpr;
            ctx.canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);

            const isDarkTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark';

            // Set text color based on theme
            const textColor = isDarkTheme ? '#ffffff' : '#666666';

            // Sample data for line charts
            let labels, data;

            if (chartId === 'gadQuarterlyChart') {
                labels = ['Q1', 'Q2', 'Q3', 'Q4'];
                data = [25, 35, 45, 30];
            } else {
                labels = ['2020', '2021', '2022', '2023', '2024'];
                data = [120, 150, 180, 200, 230];
            }

            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        fill: true,
                        backgroundColor: 'rgba(106, 27, 154, 0.2)',
                        borderColor: 'rgba(106, 27, 154, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(106, 27, 154, 1)',
                        pointBorderColor: isDarkTheme ? '#2d2d2d' : '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    devicePixelRatio: dpr,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        x: {
                            grid: {
                                color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Function to create modal comparison charts
        function createModalComparisonChart(chartId, title, data) {
            const ctx = document.getElementById(chartId).getContext('2d');
            // Set high-DPI rendering
            ctx.canvas.style.width = '100%';
            ctx.canvas.style.height = '100%';

            // Scale for higher resolution
            const dpr = window.devicePixelRatio || 1;
            const rect = ctx.canvas.getBoundingClientRect();
            ctx.canvas.width = rect.width * dpr;
            ctx.canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);

            const isDarkTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const originalChartId = chartId.replace('modal-', '');
            const cardElement = document.getElementById(originalChartId).closest('.analytics-card');

            // Get the card's accent colors
            const cardStyles = getComputedStyle(cardElement);
            const proposedColor = cardStyles.getPropertyValue('--card-accent-secondary').trim();
            const actualColor = cardStyles.getPropertyValue('--card-accent').trim();

            // Create rgba versions with transparency
            const proposedColorRGBA = proposedColor ? `${proposedColor}BB` : 'rgba(54, 162, 235, 0.7)';
            const actualColorRGBA = actualColor ? `${actualColor}BB` : 'rgba(75, 192, 192, 0.7)';

            // Set text color based on theme
            const textColor = isDarkTheme ? '#ffffff' : '#666666';

            let chartType, chartData, chartOptions;

            // Define chart based on which data we're showing
            if (title.includes('Budget')) {
                chartType = 'bar';
                chartData = {
                    labels: ['Proposed', 'Actual'],
                    datasets: [{
                        label: 'Budget Amount',
                        data: [data.proposed, data.actual],
                        backgroundColor: [
                            proposedColorRGBA,
                            actualColorRGBA
                        ],
                        borderColor: [
                            proposedColor || 'rgba(54, 162, 235, 1)',
                            actualColor || 'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                };
                chartOptions = {
                    responsive: true,
                    maintainAspectRatio: true,
                    devicePixelRatio: dpr,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                };
            } else {
                chartType = 'bar';
                chartData = {
                    labels: ['Proposed', 'Actual'],
                    datasets: [{
                        label: title.includes('Activities') ? 'Number of Activities' : 'Number of Beneficiaries',
                        data: [data.proposed, data.actual],
                        backgroundColor: [
                            proposedColorRGBA,
                            actualColorRGBA
                        ],
                        borderColor: [
                            proposedColor || 'rgba(54, 162, 235, 1)',
                            actualColor || 'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                };
                chartOptions = {
                    responsive: true,
                    maintainAspectRatio: true,
                    devicePixelRatio: dpr,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                color: textColor
                            }
                        },
                        x: {
                            grid: {
                                color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                };
            }

            return new Chart(ctx, {
                type: chartType,
                data: chartData,
                options: chartOptions
            });
        }

        // Missing function needed for modal charts
        function createModalChart(chartId, title, sourceChartId) {
            const ctx = document.getElementById(chartId).getContext('2d');
            // Set high-DPI rendering
            ctx.canvas.style.width = '100%';
            ctx.canvas.style.height = '100%';

            // Scale for higher resolution
            const dpr = window.devicePixelRatio || 1;
            const rect = ctx.canvas.getBoundingClientRect();
            ctx.canvas.width = rect.width * dpr;
            ctx.canvas.height = rect.height * dpr;
            ctx.scale(dpr, dpr);

            const isDarkTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark';

            // Set text color based on theme
            const textColor = isDarkTheme ? '#ffffff' : '#666666';

            // For line charts, create a more detailed version
            if (sourceChartId === 'gadQuarterlyChart') {
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        datasets: [{
                            label: 'Quarterly Budget Usage',
                            data: [10, 15, 20, 25, 30, 35, 40, 42, 45, 50, 55, 60],
                            fill: true,
                            backgroundColor: 'rgba(255, 158, 0, 0.2)',
                            borderColor: 'rgba(255, 158, 0, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        devicePixelRatio: dpr,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: textColor
                                }
                            },
                            x: {
                                grid: {
                                    color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: textColor
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: textColor
                                }
                            }
                        }
                    }
                });
            } else if (sourceChartId === 'gadAnnualChart') {
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['2019', '2020', '2021', '2022', '2023', '2024'],
                        datasets: [{
                            label: 'Annual Budget',
                            data: [100, 120, 150, 180, 200, 230],
                            fill: true,
                            backgroundColor: 'rgba(131, 56, 236, 0.2)',
                            borderColor: 'rgba(131, 56, 236, 1)',
                            borderWidth: 2,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        devicePixelRatio: dpr,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: textColor
                                }
                            },
                            x: {
                                grid: {
                                    color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    color: textColor
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: textColor
                                }
                            }
                        }
                    }
                });
            }

            // Fallback to a basic chart
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Data 1', 'Data 2', 'Data 3'],
                    datasets: [{
                        label: title,
                        data: [10, 20, 30],
                        backgroundColor: 'rgba(106, 27, 154, 0.7)',
                        borderColor: 'rgba(106, 27, 154, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    devicePixelRatio: dpr
                }
            });
        }
    </script>

    <script>
        // Function to open card modal
        function openCardModal(chartId, title) {
            const cardModal = document.getElementById('card-modal');
            const cardModalBackdrop = document.getElementById('card-modal-backdrop');
            const cardModalTitle = document.getElementById('card-modal-title');
            const cardModalChart = document.getElementById('card-modal-chart');
            const cardModalFooterText = document.getElementById('card-modal-footer-text');
            const modalStatus = document.getElementById('modal-status');
            const modalChange = document.getElementById('modal-change');
            const modalPeriod = document.getElementById('modal-period');
            const campusFilter = document.getElementById('modal-campus-filter');

            // Get the parent card element that was clicked
            const cardElement = document.getElementById(chartId).closest('.analytics-card');

            // Get the percentage and trend from the card
            const percentageElement = cardElement.querySelector('.percentage');
            const trendElement = cardElement.querySelector('.trend-label');
            const quarterLabel = cardElement.querySelector('.quarter-label');

            // Get computed styles to access the CSS variables
            const cardStyles = getComputedStyle(cardElement);
            const accentColor = cardStyles.getPropertyValue('--card-accent').trim();

            // Set modal accent color
            cardModal.style.setProperty('--modal-accent', accentColor);

            // Update modal title with the cumulative quarter information
            const quarter = systemCurrentQuarter;
            const cumulativeInfo = quarter > 1 ? ` (Cumulative Q1-Q${quarter})` : '';
            cardModalTitle.textContent = title + cumulativeInfo;
            cardModalChart.innerHTML = `<canvas id="modal-${chartId}"></canvas>`;

            // Calculate relative percentages for each data type
            let relativePercentage = 0;
            let data;

            // Update modal details based on which chart was clicked
            if (chartId === 'budgetChart') {
                data = budgetData;
                relativePercentage = data.proposed > 0 ? Math.round(((data.actual - data.proposed) / data.proposed) * 100) : 0;
                modalStatus.textContent = relativePercentage >= 0 ? 'Met' : 'Not Met';
                modalChange.textContent = (relativePercentage > 0 ? '+' : '') + relativePercentage + '%';
                modalChange.className = 'detail-value ' + (relativePercentage >= 0 ? 'positive' : 'negative');
            } else if (chartId === 'activitiesChart') {
                data = activitiesData;
                relativePercentage = data.proposed > 0 ? Math.round(((data.actual - data.proposed) / data.proposed) * 100) : 0;
                modalStatus.textContent = relativePercentage >= 0 ? 'Met' : 'Not Met';
                modalChange.textContent = (relativePercentage > 0 ? '+' : '') + relativePercentage + '%';
                modalChange.className = 'detail-value ' + (relativePercentage >= 0 ? 'positive' : 'negative');
            } else if (chartId === 'beneficiariesChart') {
                data = beneficiariesData;
                relativePercentage = data.proposed > 0 ? Math.round(((data.actual - data.proposed) / data.proposed) * 100) : 0;
                modalStatus.textContent = relativePercentage >= 0 ? 'Met' : 'Not Met';
                modalChange.textContent = (relativePercentage > 0 ? '+' : '') + relativePercentage + '%';
                modalChange.className = 'detail-value ' + (relativePercentage >= 0 ? 'positive' : 'negative');
            } else {
                modalStatus.textContent = 'Met';
                modalChange.textContent = percentageElement.textContent;
                modalChange.className = 'detail-value positive';
            }

            // Use quarter label instead of dateElement
            modalPeriod.textContent = quarterLabel ? quarterLabel.textContent : '';

            // Setup campus filter based on stored value or selected campus
            if (campusFilter) {
                // Get saved filter preference from sessionStorage if available
                const savedCampusFilter = sessionStorage.getItem(`dashboard_campus_${chartId}`);
                if (savedCampusFilter && campusFilter.querySelector(`option[value="${savedCampusFilter}"]`)) {
                    campusFilter.value = savedCampusFilter;
                }
            }

            // Show modal and backdrop
            cardModal.classList.add('active');
            cardModalBackdrop.style.display = 'flex';

            // Create appropriate chart in modal based on which card was clicked
            let modalChart;
            if (chartId === 'budgetChart' || chartId === 'activitiesChart' || chartId === 'beneficiariesChart') {
                modalChart = createModalComparisonChart(`modal-${chartId}`, title, data);

                // Get the current campus filter value (now from hidden input)
                const currentCampusFilter = document.getElementById('modal-campus-filter').value;

                // Load data using the selected campus filter
                fetchAndPopulateBreakdown(chartId, currentCampusFilter);
            } else {
                // Use the existing modal chart creation for other charts
                modalChart = createModalChart(`modal-${chartId}`, title, chartId);

                // Hide breakdown and detailed analysis for placeholder charts
                document.querySelector('.card-modal-breakdown').style.display = 'none';
                document.querySelector('.card-modal-detailed-analysis').style.display = 'none';
            }

            // Update modal footer text with quarter info
            cardModalFooterText.textContent = `Data shown is cumulative from Q1 to Q${systemCurrentQuarter}, ${systemCurrentYear}`;

            // Add click event to backdrop
            cardModalBackdrop.onclick = closeCardModal;
        }

        // Helper function to fetch and populate breakdown data
        function fetchAndPopulateBreakdown(chartType, campusFilter) {
            // Initialize the detailed analysis HTML structure
            initializeDetailedAnalysisHTML();

            // Fetch data with the selected campus filter
            fetch(`get_filtered_dashboard_data.php?chart_type=${encodeURIComponent(chartType)}&campus=${encodeURIComponent(campusFilter)}`)
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        // Populate the breakdown and detailed analysis with the data
                        populateBreakdown(chartType, response.data);

                        // Store the campus selection in sessionStorage
                        sessionStorage.setItem(`dashboard_campus_${chartType}`, campusFilter);

                        // Also populate detailed analysis if data is available
                        if (response.data.detailed_data) {
                            populateDetailedAnalysis(chartType, response.data);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading breakdown data:', error);
                    document.querySelector('.card-modal-breakdown').innerHTML = `
                        <h3 class="breakdown-title">Analytics Breakdown</h3>
                        <div class="alert alert-danger">Failed to load breakdown data. Please try again.</div>
                    `;
                });
        }

        // Function to close card modal
        function closeCardModal() {
            const cardModal = document.getElementById('card-modal');
            const cardModalBackdrop = document.getElementById('card-modal-backdrop');

            // Hide modal and backdrop
            cardModal.classList.remove('active');
            cardModalBackdrop.style.display = 'none';
        }

        // Function to update modal chart based on filters
        function updateModalChart() {
            try {
                const campusFilter = document.getElementById('modal-campus-filter')?.value || '';
                const chartContainer = document.getElementById('card-modal-chart');
                const modalTitle = document.getElementById('card-modal-title')?.textContent || '';
                const isCentralUser = "<?php echo $userCampus; ?>" === "Central";

                if (!chartContainer) {
                    console.error('Chart container not found');
                    return;
                }

                // Determine chart type from title to avoid canvas dependency
                let chartType = '';
                if (modalTitle.includes('Budget')) {
                    chartType = 'budgetChart';
                } else if (modalTitle.includes('Activities')) {
                    chartType = 'activitiesChart';
                } else if (modalTitle.includes('Beneficiaries')) {
                    chartType = 'beneficiariesChart';
                } else {
                    // Fallback to canvas if needed
                    const canvas = chartContainer.querySelector('canvas');
                    if (canvas && canvas.id) {
                        chartType = canvas.id.replace('modal-', '');
                    } else {
                        console.error('Could not determine chart type');
                        return;
                    }
                }

                // Create canvas ID
                const canvasId = `modal-${chartType}`;
                console.log('Processing chart:', chartType, 'canvasId:', canvasId);

                // Show loading indicator
                chartContainer.innerHTML = '<div class="text-center my-5"><i class="fas fa-spinner fa-spin fa-3x"></i><div class="mt-3">Loading data...</div></div>';

                // Save the campus filter selection to sessionStorage
                sessionStorage.setItem(`dashboard_campus_${chartType}`, campusFilter);

                // Fetch and update data
                fetchAndPopulateBreakdown(chartType, campusFilter);

                // Now update the chart itself
                fetch(`get_filtered_dashboard_data.php?chart_type=${encodeURIComponent(chartType)}&campus=${encodeURIComponent(campusFilter)}`)
                    .then(response => response.json())
                    .then(response => {
                        if (response.success) {
                            const chartData = response.data;

                            // Make sure chart data is valid
                            if (chartData) {
                                // Clear the loading indicator
                                chartContainer.innerHTML = `<canvas id="${canvasId}"></canvas>`;

                                // Create the appropriate chart type
                                let newChart;
                                let title = '';

                                if (chartType === 'budgetChart') {
                                    title = 'Budget';
                                    newChart = createModalComparisonChart(canvasId, title, chartData);
                                } else if (chartType === 'activitiesChart') {
                                    title = 'Activities';
                                    newChart = createModalComparisonChart(canvasId, title, chartData);
                                } else if (chartType === 'beneficiariesChart') {
                                    title = 'Beneficiaries';
                                    newChart = createModalComparisonChart(canvasId, title, chartData);
                                } else {
                                    // For other charts, use the existing creation function
                                    title = modalTitle.split('(')[0].trim();
                                    newChart = createModalChart(canvasId, title, chartType);
                                }

                                // Update the modal details
                                let relativePercentage = chartData.proposed > 0 ? Math.round(((chartData.actual - chartData.proposed) / chartData.proposed) * 100) : 0;
                                const modalStatus = document.getElementById('modal-status');
                                const modalChange = document.getElementById('modal-change');

                                if (modalStatus && modalChange) {
                                    modalStatus.textContent = relativePercentage >= 0 ? 'Met' : 'Not Met';
                                    modalChange.textContent = (relativePercentage > 0 ? '+' : '') + relativePercentage + '%';
                                    modalChange.className = 'detail-value ' + (relativePercentage >= 0 ? 'positive' : 'negative');
                                }
                            } else {
                                console.error('Invalid chart data response:', response);
                                chartContainer.innerHTML = '<div class="alert alert-danger">Invalid data received. Please try again.</div>';
                            }
                        } else {
                            console.error('Error in chart data response:', response);
                            chartContainer.innerHTML = '<div class="alert alert-danger">Failed to load chart data. Please try again.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading chart data:', error);
                        chartContainer.innerHTML = '<div class="alert alert-danger">Failed to load chart data. Please try again.</div>';
                    });
            } catch (error) {
                console.error('Error in updateModalChart:', error);
            }
        }

        // Function to update all preview charts on the dashboard
        function updateAllPreviewCharts(campusFilter) {
            const chartTypes = ['budgetChart', 'activitiesChart', 'beneficiariesChart'];
            const dataNames = {
                'budgetChart': 'budgetData',
                'activitiesChart': 'activitiesData',
                'beneficiariesChart': 'beneficiariesData'
            };

            // Show loading indicators for all charts
            chartTypes.forEach(chartType => {
                const chartContainer = document.querySelector(`.analytics-card[data-chart="${chartType}"] .chart-container`);
                if (chartContainer) {
                    chartContainer.innerHTML = `<div class="text-center my-3"><i class="fas fa-spinner fa-spin fa-2x"></i></div>`;
                }
            });

            // Create an array to store fetch promises
            const fetchPromises = chartTypes.map(chartType => {
                // Use empty campus filter for All Campuses, which will use the server-side logic to get all data
                const finalCampusFilter = campusFilter === 'All Campuses' ? '' : campusFilter;

                return fetch(`get_filtered_dashboard_data.php?chart_type=${encodeURIComponent(chartType)}&campus=${encodeURIComponent(finalCampusFilter)}`)
                    .then(response => response.json())
                    .then(response => {
                        if (response.success) {
                            return {
                                chartType,
                                data: response.data
                            };
                        }
                        console.error('Error in chart data:', response.message || 'Unknown error');
                        return null;
                    })
                    .catch(error => {
                        console.error('Error fetching chart data:', error);
                        return null;
                    });
            });

            // Wait for all fetches to complete
            Promise.all(fetchPromises)
                .then(results => {
                    // Filter out any null results
                    const validResults = results.filter(result => result !== null);

                    // Process each result
                    validResults.forEach(result => {
                        const {
                            chartType,
                            data
                        } = result;

                        // Update the global data variable
                        window[dataNames[chartType]] = data;

                        // Update chart display
                        const chartContainer = document.querySelector(`.analytics-card[data-chart="${chartType}"] .chart-container`);
                        if (chartContainer) {
                            chartContainer.innerHTML = `<canvas id="${chartType}"></canvas>
                            <div class="chart-values">
                                <div class="chart-value-item">
                                    <div class="chart-value-label">${chartType === 'budgetChart' ? 'GAD Fund' : (chartType === 'activitiesChart' ? 'Proposed Activities' : 'Proposed Beneficiaries')}:</div>
                                    <div class="chart-value-number proposed-value">${
                                        chartType === 'budgetChart' 
                                            ? '₱' + new Intl.NumberFormat().format(data.proposed.toFixed(2)) 
                                            : chartType === 'beneficiariesChart'
                                                ? `${new Intl.NumberFormat().format(data.proposed)}<br>
                                                  <strong class='male'>${new Intl.NumberFormat().format(data.proposed_male)} <i class='fas fa-male'></i></strong> | <strong class='female'>${new Intl.NumberFormat().format(data.proposed_female)} <i class='fas fa-female'></i></strong>`
                                                : new Intl.NumberFormat().format(data.proposed)
                                    }</div>
                                </div>
                                <div class="chart-value-item">
                                    <div class="chart-value-label">${chartType === 'budgetChart' ? 'Budget Utilized' : (chartType === 'activitiesChart' ? 'Actual Activities' : 'Actual Beneficiaries')}:</div>
                                    <div class="chart-value-number actual-value">${
                                        chartType === 'budgetChart' 
                                            ? '₱' + new Intl.NumberFormat().format(data.actual.toFixed(2)) 
                                            : chartType === 'beneficiariesChart'
                                                ? `${new Intl.NumberFormat().format(data.actual)}<br>
                                                  <strong class='male'>${new Intl.NumberFormat().format(data.actual_male)} <i class='fas fa-male'></i></strong> | <strong class='female'>${new Intl.NumberFormat().format(data.actual_female)} <i class='fas fa-female'></i></strong>`
                                                : new Intl.NumberFormat().format(data.actual)
                                    }</div>
                                </div>
                            </div>`;

                            // Update percentage indicator
                            const card = chartContainer.closest('.analytics-card');
                            if (card) {
                                const percentageElement = card.querySelector('.percentage');
                                const trendElement = card.querySelector('.trend-label');

                                if (percentageElement) {
                                    percentageElement.textContent = (data.relativePercentage > 0 ? '+' : '') + data.relativePercentage + '%';
                                    percentageElement.className = 'percentage ' + (data.relativePercentage >= 0 ? 'up' : 'down');
                                }

                                if (trendElement) {
                                    trendElement.textContent = data.trend;
                                }
                            }

                            // Create new chart
                            window.charts[chartType] = createPieChart(
                                chartType,
                                window[dataNames[chartType]].proposed,
                                window[dataNames[chartType]].actual,
                                chartType === 'budgetChart' ? 'Budget Usage' :
                                (chartType === 'activitiesChart' ? 'Activities' : 'Beneficiaries')
                            );
                        }
                    });

                    // Update campus display elements
                    const campusDisplayElements = document.querySelectorAll('.campus-display');
                    const campusText = campusFilter ? campusFilter : 'All Campuses';
                    campusDisplayElements.forEach(el => {
                        el.textContent = campusText;
                    });

                    // Adjust font sizes for beneficiary numbers after chart updates
                    adjustBeneficiaryFontSizes();
                })
                .catch(error => {
                    console.error('Error updating preview charts:', error);
                });
        }
    </script>

    <script>
        function handleLogout(event) {
            event.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the system",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel',
                backdrop: `
                    rgba(0,0,0,0.7)
                `,
                allowOutsideClick: true,
                customClass: {
                    container: 'swal-blur-container'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.body.classList.add('fade-out');

                    setTimeout(() => {
                        window.location.href = '../loading_screen.php?redirect=index.php';
                    }, 50);
                }
            });
        }
    </script>

    <!-- Create all charts when DOM is loaded -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Make charts global for access by other functions
            window.charts = {
                budgetChart: createPieChart('budgetChart', budgetData.proposed, budgetData.actual, 'Budget Usage'),
                activitiesChart: createPieChart('activitiesChart', activitiesData.proposed, activitiesData.actual, 'Activities'),
                beneficiariesChart: createPieChart('beneficiariesChart', beneficiariesData.proposed, beneficiariesData.actual, 'Beneficiaries')
                // gadQuarterlyChart and gadAnnualChart removed as they're now placeholders
            };

            // Function to update all charts when theme changes
            window.updateChartsTheme = function() {
                // Destroy existing charts
                Object.values(window.charts).forEach(chart => chart.destroy());

                // Recreate charts with new theme colors
                window.charts = {
                    budgetChart: createPieChart('budgetChart', budgetData.proposed, budgetData.actual, 'Budget Usage'),
                    activitiesChart: createPieChart('activitiesChart', activitiesData.proposed, activitiesData.actual, 'Activities'),
                    beneficiariesChart: createPieChart('beneficiariesChart', beneficiariesData.proposed, beneficiariesData.actual, 'Beneficiaries')
                    // gadQuarterlyChart and gadAnnualChart removed as they're now placeholders
                };
            };

            // Update charts when theme changes
            const themeSwitch = document.querySelector('.theme-switch-button');
            themeSwitch.addEventListener('click', () => {
                // Give time for the theme to update
                setTimeout(() => {
                    if (typeof updateChartsTheme === 'function') {
                        updateChartsTheme();
                    }
                }, 100);
            });

            // Update quarter labels on load
            updateQuarterLabels();

            // Update quarter label every minute (though quarters don't change that frequently)
            setInterval(updateQuarterLabels, 60000);

            // Adjust font sizes for beneficiary numbers
            adjustBeneficiaryFontSizes();
        });
    </script>

    <!-- Function to populate the analytics breakdown section -->
    <script>
        function populateBreakdown(chartType, data) {
            const breakdownTable = document.getElementById('breakdown-table-body');
            const breakdownSummary = document.getElementById('breakdown-summary');

            if (!breakdownTable || !breakdownSummary) {
                console.error('Breakdown elements not found');
                return;
            }

            // Show breakdown section
            document.querySelector('.card-modal-breakdown').style.display = 'block';

            // Clear previous content
            breakdownTable.innerHTML = '';

            // Format numbers based on chart type
            const formatValue = (value, type) => {
                if (type === 'budgetChart') {
                    return '₱' + new Intl.NumberFormat().format(value.toFixed(2));
                } else {
                    return new Intl.NumberFormat().format(value);
                }
            };

            // Get chart-specific labels
            let metricLabel, proposedLabel, actualLabel;
            if (chartType === 'budgetChart') {
                metricLabel = 'Budget';
                proposedLabel = 'GAD Fund';
                actualLabel = 'Budget Utilized';
            } else if (chartType === 'activitiesChart') {
                metricLabel = 'Activities';
                proposedLabel = 'Proposed Activities';
                actualLabel = 'Actual Activities';
            } else if (chartType === 'beneficiariesChart') {
                metricLabel = 'Beneficiaries';
                proposedLabel = 'Proposed Beneficiaries';
                actualLabel = 'Actual Beneficiaries';
            } else {
                metricLabel = 'Metric';
                proposedLabel = 'Proposed';
                actualLabel = 'Actual';
            }

            // Calculate percentage
            const percentage = data.proposed > 0 ? Math.round((data.actual / data.proposed) * 100) : 0;
            const percentageClass = percentage >= 100 ? 'percentage-positive' : 'percentage-negative';

            // Add main row
            breakdownTable.innerHTML += `
                <tr>
                    <td class="metric-name">${metricLabel}</td>
                    <td>${formatValue(data.proposed, chartType)}</td>
                    <td>${formatValue(data.actual, chartType)}</td>
                    <td class="percentage-cell ${percentageClass}">${percentage}%</td>
                </tr>
            `;

            // Add additional metrics based on chart type
            if (chartType === 'budgetChart') {
                const approvedBudget = data.approved_budget || 0;
                const psAttribution = data.ps_attribution || 0;
                const remaining = data.proposed - data.actual;
                const remainingPercentage = data.proposed > 0 ? Math.round((remaining / data.proposed) * 100) : 0;

                // Add PS Attribution breakdown row
                breakdownTable.innerHTML += `
                    <tr>
                        <td class="metric-name">Approved Budget</td>
                        <td>-</td>
                        <td>${formatValue(approvedBudget, chartType)}</td>
                        <td class="percentage-cell">${approvedBudget > 0 ? Math.round((approvedBudget / data.actual) * 100) : 0}%</td>
                    </tr>
                    <tr>
                        <td class="metric-name">PS Attribution</td>
                        <td>-</td>
                        <td>${formatValue(psAttribution, chartType)}</td>
                        <td class="percentage-cell">${psAttribution > 0 ? Math.round((psAttribution / data.actual) * 100) : 0}%</td>
                    </tr>
                    <tr>
                        <td class="metric-name">Remaining Budget</td>
                        <td>-</td>
                        <td>${formatValue(remaining, chartType)}</td>
                        <td class="percentage-cell">${remainingPercentage}%</td>
                    </tr>
                `;
            } else if (chartType === 'beneficiariesChart') {
                // Add gender breakdown rows
                const malePercentage = data.proposed_male > 0 ? Math.round((data.actual_male / data.proposed_male) * 100) : 0;
                const femalePercentage = data.proposed_female > 0 ? Math.round((data.actual_female / data.proposed_female) * 100) : 0;

                breakdownTable.innerHTML += `
                    <tr>
                        <td class="metric-name">Male Beneficiaries</td>
                        <td>${new Intl.NumberFormat().format(data.proposed_male)}</td>
                        <td>${new Intl.NumberFormat().format(data.actual_male)}</td>
                        <td class="percentage-cell ${malePercentage >= 100 ? 'percentage-positive' : 'percentage-negative'}">${malePercentage}%</td>
                    </tr>
                    <tr>
                        <td class="metric-name">Female Beneficiaries</td>
                        <td>${new Intl.NumberFormat().format(data.proposed_female)}</td>
                        <td>${new Intl.NumberFormat().format(data.actual_female)}</td>
                        <td class="percentage-cell ${femalePercentage >= 100 ? 'percentage-positive' : 'percentage-negative'}">${femalePercentage}%</td>
                    </tr>
                `;
            }

            // Add per quarter estimated values
            const currentQuarter = parseInt(systemCurrentQuarter);
            if (currentQuarter > 1) {
                const perQuarter = data.proposed / 4; // Assuming equal distribution across quarters
                const expectedForCurrentQuarter = perQuarter * currentQuarter;
                const quarterEfficiency = expectedForCurrentQuarter > 0 ? Math.round((data.actual / expectedForCurrentQuarter) * 100) : 0;
                const quarterEfficiencyClass = quarterEfficiency >= 100 ? 'percentage-positive' : 'percentage-negative';

                breakdownTable.innerHTML += `
                    <tr>
                        <td class="metric-name">Expected by Q${currentQuarter}</td>
                        <td>${formatValue(expectedForCurrentQuarter, chartType)}</td>
                        <td>${formatValue(data.actual, chartType)}</td>
                        <td class="percentage-cell ${quarterEfficiencyClass}">${quarterEfficiency}%</td>
                    </tr>
                `;
            }

            // Add projected annual completion rate
            if (currentQuarter < 4) {
                const annualProjection = (data.actual / currentQuarter) * 4; // Project to full year
                const projectedPercentage = data.proposed > 0 ? Math.round((annualProjection / data.proposed) * 100) : 0;
                const projectionClass = projectedPercentage >= 100 ? 'percentage-positive' : 'percentage-negative';

                breakdownTable.innerHTML += `
                    <tr>
                        <td class="metric-name">Year-end Projection</td>
                        <td>${formatValue(data.proposed, chartType)}</td>
                        <td>${formatValue(annualProjection, chartType)}</td>
                        <td class="percentage-cell ${projectionClass}">${projectedPercentage}%</td>
                    </tr>
                `;
            }

            // Generate summary text
            let summaryText = '';

            if (chartType === 'budgetChart') {
                const approvedBudget = data.approved_budget || 0;
                const psAttribution = data.ps_attribution || 0;

                if (percentage >= 100) {
                    summaryText = `The budget utilization has exceeded the allocated GAD Fund by ${Math.abs(data.relativePercentage)}%. This includes ₱${new Intl.NumberFormat().format(approvedBudget.toFixed(2))} from approved budgets and ₱${new Intl.NumberFormat().format(psAttribution.toFixed(2))} from PS Attribution.`;
                } else if (percentage >= 75) {
                    summaryText = `Budget utilization is at ${percentage}% of the allocated GAD Fund, which shows good progress towards financial targets. This comprises ₱${new Intl.NumberFormat().format(approvedBudget.toFixed(2))} from approved budgets and ₱${new Intl.NumberFormat().format(psAttribution.toFixed(2))} from PS Attribution.`;
                } else if (percentage >= 50) {
                    summaryText = `Budget utilization is at ${percentage}% of the allocated GAD Fund. This includes ₱${new Intl.NumberFormat().format(approvedBudget.toFixed(2))} from approved budgets and ₱${new Intl.NumberFormat().format(psAttribution.toFixed(2))} from PS Attribution. Consider reviewing the implementation timeline to ensure complete utilization.`;
                } else {
                    summaryText = `Budget utilization is currently at ${percentage}% of the allocated GAD Fund. This comprises ₱${new Intl.NumberFormat().format(approvedBudget.toFixed(2))} from approved budgets and ₱${new Intl.NumberFormat().format(psAttribution.toFixed(2))} from PS Attribution. This may indicate delayed implementation or potential underspending.`;
                }
            } else if (chartType === 'activitiesChart') {
                if (percentage >= 100) {
                    summaryText = `All planned activities have been completed, with an additional ${Math.abs(data.relativePercentage)}% more activities than initially proposed.`;
                } else if (percentage >= 75) {
                    summaryText = `${percentage}% of planned activities have been completed, showing good progress in implementation.`;
                } else if (percentage >= 50) {
                    summaryText = `${percentage}% of planned activities have been completed. Review the implementation schedule to ensure timely completion.`;
                } else {
                    summaryText = `Only ${percentage}% of planned activities have been completed. This may indicate implementation delays.`;
                }
            } else if (chartType === 'beneficiariesChart') {
                const malePercentage = data.proposed_male > 0 ? Math.round((data.actual_male / data.proposed_male) * 100) : 0;
                const femalePercentage = data.proposed_female > 0 ? Math.round((data.actual_female / data.proposed_female) * 100) : 0;

                if (percentage >= 100) {
                    summaryText = `The program has reached ${Math.abs(data.relativePercentage)}% more beneficiaries than initially targeted, indicating excellent outreach.`;
                    if (malePercentage >= 100 && femalePercentage >= 100) {
                        summaryText += ` Both male and female beneficiaries have exceeded their targets.`;
                    } else if (malePercentage >= 100) {
                        summaryText += ` Male beneficiaries have exceeded their target, while female beneficiaries are at ${femalePercentage}% of their target.`;
                    } else if (femalePercentage >= 100) {
                        summaryText += ` Female beneficiaries have exceeded their target, while male beneficiaries are at ${malePercentage}% of their target.`;
                    } else {
                        summaryText += ` Male beneficiaries are at ${malePercentage}% and female beneficiaries at ${femalePercentage}% of their respective targets.`;
                    }
                } else if (percentage >= 75) {
                    summaryText = `${percentage}% of targeted beneficiaries have been reached, showing good progress in program outreach.`;
                    summaryText += ` Male beneficiaries are at ${malePercentage}% and female beneficiaries at ${femalePercentage}% of their respective targets.`;
                } else if (percentage >= 50) {
                    summaryText = `${percentage}% of targeted beneficiaries have been reached. Consider reviewing outreach strategies.`;
                    summaryText += ` Male beneficiaries are at ${malePercentage}% and female beneficiaries at ${femalePercentage}% of their respective targets.`;
                } else {
                    summaryText = `Only ${percentage}% of targeted beneficiaries have been reached. Evaluate outreach strategies to improve engagement.`;
                    summaryText += ` Male beneficiaries are at ${malePercentage}% and female beneficiaries at ${femalePercentage}% of their respective targets.`;
                }
            }

            // Add context for quarter progress
            summaryText += ` Data shown is cumulative from Q1 to Q${systemCurrentQuarter} of ${systemCurrentYear}.`;

            // Update summary section
            breakdownSummary.textContent = summaryText;

            // Call function to populate detailed analysis section
            populateDetailedAnalysis(chartType, data);
        }

        // Function to populate the detailed analysis section
        function populateDetailedAnalysis(chartType, data) {
            // First, check if detailed analysis elements exist, initialize if not
            const budgetAnalysisSection = document.getElementById('budget-utilization-analysis');
            const activitiesAnalysisSection = document.getElementById('activities-analysis');
            const beneficiariesAnalysisSection = document.getElementById('beneficiaries-analysis');

            if (!budgetAnalysisSection || !activitiesAnalysisSection || !beneficiariesAnalysisSection) {
                console.log('Detailed analysis sections not found, initializing HTML structure first');
                initializeDetailedAnalysisHTML();
            }

            // Hide all analysis sections first
            document.querySelectorAll('.detailed-analysis-section').forEach(section => {
                section.style.display = 'none';
            });

            // Show detailed analysis container
            document.querySelector('.card-modal-detailed-analysis').style.display = 'block';

            // Check if we have detailed data
            if (!data.detailed_data || data.detailed_data.length === 0) {
                // Show empty state message in all sections
                const emptyStateHtml = '<div class="data-empty-state">No detailed data available for the current selection.</div>';
                document.getElementById('budget-utilization-analysis').innerHTML = emptyStateHtml;
                document.getElementById('activities-analysis').innerHTML = emptyStateHtml;
                document.getElementById('beneficiaries-analysis').innerHTML = emptyStateHtml;
                return;
            }

            // Process based on chart type
            if (chartType === 'budgetChart') {
                populateBudgetAnalysis(data);
            } else if (chartType === 'activitiesChart') {
                populateActivitiesAnalysis(data);
            } else if (chartType === 'beneficiariesChart') {
                populateBeneficiariesAnalysis(data);
            }
        }

        // Function to populate budget utilization analysis
        function populateBudgetAnalysis(data) {
            const tableBody = document.getElementById('budget-activities-table-body');
            const totalBudgetUtilized = document.getElementById('total-budget-utilized');
            const totalGadFund = document.getElementById('total-gad-fund');
            const remainingBudget = document.getElementById('remaining-budget');

            // Show this section
            document.getElementById('budget-utilization-analysis').style.display = 'block';

            // Clear previous content
            tableBody.innerHTML = '';

            // Format number as currency
            const formatCurrency = (value) => {
                return '₱' + new Intl.NumberFormat().format(value.toFixed(2));
            };

            // Update column headers
            const headerRow = document.querySelector('#budget-utilization-analysis thead tr');
            if (headerRow) {
                headerRow.innerHTML = `
                    <th>Activity</th>
                    <th>Campus</th>
                    <th>Quarter</th>
                    <th>Approved Budget</th>
                    <th>PS Attribution</th>
                    <th>Total</th>
                `;
            }

            // Populate table with activities
            data.detailed_data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.activity}</td>
                    <td>${item.campus}</td>
                    <td>${item.quarter}</td>
                    <td>${formatCurrency(item.approved_budget)}</td>
                    <td>${formatCurrency(item.ps_attribution)}</td>
                    <td>${formatCurrency(item.total_budget)}</td>
                `;
                tableBody.appendChild(row);
            });

            // Update summary values
            totalBudgetUtilized.textContent = formatCurrency(data.actual);
            totalGadFund.textContent = formatCurrency(data.proposed);

            const remaining = data.proposed - data.actual;
            remainingBudget.textContent = formatCurrency(remaining);

            // Add class based on remaining budget
            if (remaining < 0) {
                remainingBudget.classList.add('text-danger');
            } else {
                remainingBudget.classList.remove('text-danger');
            }

            // Update breakdown summary with PS Attribution information
            const breakdownSummary = document.getElementById('breakdown-summary');
            if (breakdownSummary) {
                breakdownSummary.innerHTML = `
                    <p>The total budget utilization of ${formatCurrency(data.actual)} includes ${formatCurrency(data.approved_budget)} from approved budgets and ${formatCurrency(data.ps_attribution)} from PS Attribution.</p>
                `;
            }
        }

        // Function to populate activities analysis
        function populateActivitiesAnalysis(data) {
            const tableBody = document.getElementById('activities-table-body');
            const totalProposedActivities = document.getElementById('total-proposed-activities');
            const totalActualActivities = document.getElementById('total-actual-activities');
            const totalRemainingActivities = document.getElementById('total-remaining-activities');
            const totalActivitiesCompletion = document.getElementById('total-activities-completion');

            // Show this section
            document.getElementById('activities-analysis').style.display = 'block';

            // Clear previous content
            tableBody.innerHTML = '';

            // Helper function to get completion badge
            const getCompletionBadge = (percentage) => {
                let badgeClass = '';
                if (percentage >= 75) {
                    badgeClass = 'completion-high';
                } else if (percentage >= 50) {
                    badgeClass = 'completion-medium';
                } else {
                    badgeClass = 'completion-low';
                }
                return `<span class="completion-badge ${badgeClass}">${percentage}%</span>`;
            };

            // Populate table with gender issues and activities
            let totalProposed = 0;
            let totalActual = 0;

            data.detailed_data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.gender_issue}</td>
                    <td>${item.campus}</td>
                    <td>${item.proposed_activities}</td>
                    <td>${item.actual_activities}</td>
                    <td>${item.remaining}</td>
                    <td>${getCompletionBadge(item.completion)}</td>
                `;
                tableBody.appendChild(row);

                // Add to totals
                totalProposed += item.proposed_activities;
                totalActual += item.actual_activities;
            });

            // Calculate totals
            const totalRemaining = Math.max(0, totalProposed - totalActual);
            const totalCompletion = totalProposed > 0 ? Math.round((totalActual / totalProposed) * 100) : 0;

            // Update summary values
            totalProposedActivities.textContent = totalProposed;
            totalActualActivities.textContent = totalActual;
            totalRemainingActivities.textContent = totalRemaining;
            totalActivitiesCompletion.innerHTML = getCompletionBadge(totalCompletion);
        }

        // Function to populate beneficiaries analysis
        function populateBeneficiariesAnalysis(data) {
            const tableBody = document.getElementById('beneficiaries-table-body');
            const totalProposedBeneficiaries = document.getElementById('total-proposed-beneficiaries');
            const totalActualBeneficiaries = document.getElementById('total-actual-beneficiaries');
            const totalRemainingBeneficiaries = document.getElementById('total-remaining-beneficiaries');
            const totalBeneficiariesCompletion = document.getElementById('total-beneficiaries-completion');

            // Show this section
            document.getElementById('beneficiaries-analysis').style.display = 'block';

            // Clear previous content
            tableBody.innerHTML = '';

            // Helper function to get completion badge
            const getCompletionBadge = (percentage) => {
                let badgeClass = '';
                if (percentage >= 75) {
                    badgeClass = 'completion-high';
                } else if (percentage >= 50) {
                    badgeClass = 'completion-medium';
                } else {
                    badgeClass = 'completion-low';
                }
                return `<span class="completion-badge ${badgeClass}">${percentage}%</span>`;
            };

            // Populate table with gender issues and beneficiaries
            let totalProposed = 0;
            let totalActual = 0;

            data.detailed_data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.gender_issue}</td>
                    <td>${item.campus}</td>
                    <td>
                        <div>Total: ${item.proposed_beneficiaries}</div>
                        <div class="gender-breakdown">
                            <span class="male">Male: ${item.proposed_male}</span>
                            <span class="female">Female: ${item.proposed_female}</span>
                        </div>
                    </td>
                    <td>
                        <div>Total: ${item.actual_beneficiaries}</div>
                        <div class="gender-breakdown">
                            <span class="male">Male: ${item.actual_male}</span>
                            <span class="female">Female: ${item.actual_female}</span>
                        </div>
                    </td>
                    <td>${item.remaining}</td>
                    <td>${getCompletionBadge(item.completion)}</td>
                `;
                tableBody.appendChild(row);

                // Add to totals
                totalProposed += item.proposed_beneficiaries;
                totalActual += item.actual_beneficiaries;
            });

            // Calculate totals
            const totalRemaining = Math.max(0, totalProposed - totalActual);
            const totalCompletion = totalProposed > 0 ? Math.round((totalActual / totalProposed) * 100) : 0;

            // Update summary values
            totalProposedBeneficiaries.innerHTML = `
                <div>Total: ${totalProposed}</div>
                <div class="gender-breakdown">
                    <span class="male">Male: ${data.proposed_male}</span>
                    <span class="female">Female: ${data.proposed_female}</span>
                </div>
            `;
            totalActualBeneficiaries.innerHTML = `
                <div>Total: ${totalActual}</div>
                <div class="gender-breakdown">
                    <span class="male">Male: ${data.actual_male}</span>
                    <span class="female">Female: ${data.actual_female}</span>
                </div>
            `;
            totalRemainingBeneficiaries.textContent = totalRemaining;
            totalBeneficiariesCompletion.innerHTML = getCompletionBadge(totalCompletion);
        }
    </script>

    <!-- On document load, also ensure correct campus is selected for Central users -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isCentralUser = "<?php echo $userCampus; ?>" === "Central";

            if (isCentralUser) {
                // Set the campus display elements to "All Campuses" by default
                const campusDisplayElements = document.querySelectorAll('.campus-display');
                campusDisplayElements.forEach(el => {
                    el.textContent = "All Campuses";
                });

                // Also set the modal filter to "All Campuses" by default
                const modalCampusFilter = document.getElementById('modal-campus-filter');
                if (modalCampusFilter) {
                    modalCampusFilter.value = ""; // Empty value means all campuses
                }
            }
        });
    </script>
</body>

</html>