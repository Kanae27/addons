<?php
session_start();

// Debug session information
error_log("Session data in ppas.php: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    error_log("User not logged in - redirecting to login");
    header("Location: ../login.php");
    exit();
}

// Check if user is Central or a specific campus user
$isCentral = isset($_SESSION['username']) && $_SESSION['username'] === 'Central';

// For non-Central users, their username is their campus
$userCampus = $isCentral ? '' : $_SESSION['username'];

// Store campus in session for consistency
$_SESSION['campus'] = $userCampus;

// Add this function before the HTML section
function getSignatories($campus) {
    try {
        $conn = getConnection();
        $sql = "SELECT * FROM signatories WHERE campus = :campus";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':campus', $campus);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Error fetching signatories: ' . $e->getMessage());
        return null;
    }
}

// Get signatories for the current campus
$signatories = getSignatories($_SESSION['username']);

// Add this function at the top of the file, after any existing includes
function getConnection() {
    try {
        $conn = new PDO(
            "mysql:host=localhost;dbname=gad_db;charset=utf8mb4",
            "root",
            "",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Reports - GAD System</title>
    <link rel="icon" type="image/x-icon" href="../images/Batangas_State_Logo.ico">
    <script src="../js/common.js"></script>
    <!-- Immediate theme loading to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                themeIcon.className = savedTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            }
        })();
    </script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="js/checkbox_handler_new.js"></script>
    <style>
        :root {
            --sidebar-width: 280px;
            --accent-color: #6a1b9a;
            --accent-hover: #4a148c;
        }
        
        /* Light Theme Variables */
        [data-bs-theme="light"] {
            --bg-primary: #f0f0f0;
            --bg-secondary: #e9ecef;
            --sidebar-bg: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --hover-color: rgba(106, 27, 154, 0.1);
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --horizontal-bar: rgba(33, 37, 41, 0.125);
            --input-placeholder: rgba(33, 37, 41, 0.75);
            --input-bg: #ffffff;
            --input-text: #212529;
            --card-title: #212529;
            --scrollbar-thumb: rgba(156, 39, 176, 0.4);
            --scrollbar-thumb-hover: rgba(156, 39, 176, 0.7);
            --gantt-empty-cell: white;
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
            --input-placeholder: rgba(255, 255, 255, 0.7);
            --input-bg: #404040;
            --input-text: #ffffff;
            --card-title: #ffffff;
            --scrollbar-thumb: #6a1b9a;
            --scrollbar-thumb-hover: #9c27b0;
            --accent-color: #9c27b0;
            --accent-hover: #7b1fa2;
            --gantt-empty-cell: #2d2d2d;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            padding: 20px;
            opacity: 1;
            transition: opacity 0.05s ease-in-out; /* Changed from 0.05s to 0.01s - make it super fast */
        }

        body.fade-out {
    opacity: 0;
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
            box-shadow: 5px 0 15px rgba(0,0,0,0.05), 0 5px 15px rgba(0,0,0,0.05);
            z-index: 1;
        }

        .main-content {
    margin-left: calc(var(--sidebar-width) + 20px);
    padding: 15px;
    height: calc(100vh - 30px);
    max-height: calc(100vh - 30px);
    background: var(--bg-primary);
    border-radius: 20px;
    position: relative;
    overflow-y: auto;
    scrollbar-width: none;  /* Firefox */
    -ms-overflow-style: none;  /* IE and Edge */
}

/* Hide scrollbar for Chrome, Safari and Opera */
.main-content::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for Chrome, Safari and Opera */
body::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for Firefox */
html {
    scrollbar-width: none;
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

        /* Add hover state for active nav links in dark mode */
        [data-bs-theme="dark"] .nav-link.active:hover {
            color: white;
        }

        .nav-item {
            position: relative;
        }
        
        .nav-item .dropdown-menu .dropdown-item {
            padding: 6px 48px; 
        }

        .nav-item .dropdown-menu {
            position: static !important;
            background: var(--sidebar-bg);
            border: 1px solid var(--border-color);
            padding: 8px 0;
            margin: 5px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-width: 200px;
            transform: none !important;
            display: none;
            overflow: visible;
            max-height: none;
        }

        /* Dropdown submenu styles */
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
.dropdown-submenu.show > .dropdown-menu {
    display: block;
}

.approval-link {
    margin-top: 5px; 
    margin-bottom: 10px; 
}

.bottom-controls {
    margin-top: 15px; 
}

.dropdown-submenu > a:after {
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
.dropdown-submenu.show > a:after {
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
    
    .dropdown-submenu > a:after {
        transform: rotate(90deg);
        margin-top: 8px;
    }
}
        
        /* End of dropdown submenu styles */

        .nav-item .dropdown-menu.show {
            display: block;
        }

        .nav-item .dropdown-menu .dropdown-item {
            padding: 8px 48px;
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

        [data-bs-theme="light"] .nav-item .dropdown-menu .dropdown-item:hover {
            color: var(--accent-color);
        }

        .nav-item .dropdown-toggle[aria-expanded="true"] {
            color: white !important;
            background: var(--hover-color);
        }

        [data-bs-theme="light"] .nav-item .dropdown-toggle[aria-expanded="true"] {
            color: var(--accent-color) !important;
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

        .nav-content {
            flex-grow: 1;
            overflow-y: auto;
            max-height: calc(100vh - 470px);
            margin-bottom: 20px;
            padding-right: 5px;
            scrollbar-width: thin;
            scrollbar-color: rgba(106, 27, 154, 0.4) transparent;
            overflow-x: hidden; 
        }

        .nav-content::-webkit-scrollbar {
            width: 5px;
        }

        .nav-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .nav-content::-webkit-scrollbar-thumb {
            background-color: rgba(106, 27, 154, 0.4);
            border-radius: 1px;
        }

        .nav-content::-webkit-scrollbar-thumb:hover {
            background-color: rgba(106, 27, 154, 0.7);
        }

        .nav-link:focus,
        .dropdown-toggle:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .dropdown-menu {
            outline: none !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
        }

        .dropdown-item:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Bottom controls container */
        .bottom-controls {
            position: absolute;
            bottom: 20px;
            width: calc(var(--sidebar-width) - 40px);
            display: flex;
            gap: 5px;
            align-items: center;
        }

        /* Logout button styles */
        .logout-button {
            flex: 1;
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Theme switch button */
        .theme-switch-button {
            width: 46.5px;
            height: 50px;
            padding: 12px 0;
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

                /* Light theme specific styles for bottom controls */
                [data-bs-theme="light"] .logout-button,
        [data-bs-theme="light"] .theme-switch-button {
            background: #f2f2f2;
            border-width: 1.5px;
        }

        /* Hover effects */
        .logout-button:hover,
        .theme-switch-button:hover {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        .theme-switch {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .theme-switch-button:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 8px 12px rgba(0, 0, 0, 0.15),
                0 3px 6px rgba(0, 0, 0, 0.1),
                inset 0 1px 2px rgba(255, 255, 255, 0.2);
        }

        .theme-switch-button:active {
            transform: translateY(0);
            box-shadow: 
                0 4px 6px rgba(0, 0, 0, 0.1),
                0 2px 4px rgba(0, 0, 0, 0.06),
                inset 0 1px 2px rgba(255, 255, 255, 0.2);
        }

        /* Theme switch button icon size */
        .theme-switch-button i {
            font-size: 1rem; 
        }

        .theme-switch-button:hover i {
            transform: scale(1.1);
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
                box-shadow: 5px 0 25px rgba(0,0,0,0.1);
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
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }

            .sidebar-backdrop.show {
                display: block;
            }

            .theme-switch {
                position: fixed;
                bottom: 30px;
                right: 30px;
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
        }

        /* Modern Card Styles */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            position: relative;
            min-height: 465px;
        }

        .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        #ppasForm {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        #ppasForm.row {
            flex: 1;
        }

        #ppasForm .col-12.text-end {
            margin-top: auto !important;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* Dark Theme Colors */
        [data-bs-theme="dark"] {
            --dark-bg: #212529;
            --dark-input: #2b3035;
            --dark-text: #e9ecef;
            --dark-border: #495057;
            --dark-sidebar: #2d2d2d;
        }

        /* Dark mode card */
        [data-bs-theme="dark"] .card {
            background-color: var(--dark-sidebar) !important;
            border-color: var(--dark-border) !important;
        }

        [data-bs-theme="dark"] .card-header {
            background-color: var(--dark-input) !important;
            border-color: var(--dark-border) !important;
            overflow: hidden;
        }

        /* Fix for card header corners */
        .card-header {
            border-top-left-radius: inherit !important;
            border-top-right-radius: inherit !important;
            padding-bottom: 0.5rem !important;
        }

        .card-title {
            margin-bottom: 0;
        }

        /* Form Controls */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1 1 200px;
        }


        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 25px;
            margin-bottom: 20px;
        }

        .btn-icon {
            width: 45px;
            height: 45px;
            padding: 0;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-icon i {
            font-size: 1.2rem;
        }

        /* Add button */
        #addBtn {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        #addBtn:hover {
            background: #198754;
            color: white;
        }

        /* Edit button */
        #editBtn {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        #editBtn:hover {
            background: #ffc107;
            color: white;
        }

        /* Edit button in cancel mode */
        #editBtn.editing {
            background: rgba(220, 53, 69, 0.1) !important;
            color: #dc3545 !important;
            border-color: #dc3545 !important;
        }

        #editBtn.editing:hover {
            background: #dc3545 !important;
            color: white !important;
        }

        /* Delete button */
        #deleteBtn {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        #deleteBtn:hover {
            background: #dc3545;
            color: white;
        }

        /* Delete button disabled state */
        #deleteBtn.disabled {
            background: rgba(108, 117, 125, 0.1) !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }

        /* Update button state */
        #addBtn.btn-update {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        #addBtn.btn-update:hover {
            background: #198754;
            color: white;
        }

#viewBtn {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

#viewBtn:hover {
    background: #0d6efd;
    color: white;
}

/* Optional: Add disabled state for view button */
#viewBtn.disabled {
    background: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

/* Add these styles for disabled buttons */
.btn-disabled {
    border-color: #6c757d !important;
    background: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
    opacity: 0.65 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

/* Dark mode styles */
[data-bs-theme="dark"] .btn-disabled {
    background-color: #495057 !important;
    border-color: #495057 !important;
    color: #adb5bd !important;
}

.swal-blur-container {
    backdrop-filter: blur(5px);
}

/* Add print-specific styles */
@media print {
    @page {
        size: 8.5in 13in;
        margin-top: 1.52cm;
        margin-bottom: 2cm;
        margin-left: 1.78cm;
        margin-right: 2.03cm;
        border-top: 1px solid black !important;
        border-bottom: 1px solid black !important;
    }
    
    /* Force ALL colors to black - no exceptions */
    *, p, span, div, td, th, li, ul, ol, strong, em, b, i, a, h1, h2, h3, h4, h5, h6,
    [style*="color:"], [style*="color="], [style*="color :"], [style*="color ="],
    [style*="color: brown"], [style*="color: blue"], [style*="color: red"], 
    .brown-text, .blue-text, .sustainability-plan, .sustainability-plan p, .sustainability-plan li,
    .signature-label, .signature-position {
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    /* First page footer with tracking number */
    @page:first {
        margin-top: 1.52cm;
        margin-bottom: 2cm;
        margin-left: 1.78cm;
        margin-right: 2.03cm;
        border-top: 1px solid black !important;
        border-bottom: 1px solid black !important;
    }
    
    /* Ensure proper spacing for the footer */
    .proposal-container {
        margin-bottom: 1.5cm !important;
    }
    
    /* Make table borders thin in print */
    .header-table td,
    .data-table th,
    .data-table td,
    .signatures-table td {
        border: 0.5px solid black !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        /* Remove border */
        border: none;
        box-sizing: border-box;
        min-height: calc(100% - 2cm);
        width: calc(100% - 3.81cm);
        margin-top: 1.52cm !important;
        margin-bottom: 2cm !important;
        margin-left: 1.78cm !important;
        margin-right: 2.03cm !important;
        background-clip: padding-box;
        box-shadow: none;
    }
}

/* Add these styles for compact form */
.compact-form .form-group {
    margin-bottom: 0.5rem !important;
}

.compact-form label {
    margin-bottom: 0.25rem !important;
    font-size: 0.85rem !important;
}

.compact-form .form-control-sm {
    height: calc(1.5em + 0.5rem + 2px);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.compact-form .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Additional styles to match get_gpb_report.php */
.compact-form select.form-control-sm,
.compact-form input.form-control-sm {
    font-size: 1rem !important;
    height: 38px !important;
    padding: 0.375rem 0.75rem !important;
}

#campus, #year, #proposal {
    font-size: 1rem !important;
    height: 38px !important;
}

.compact-form .btn-sm {
    font-size: 1rem !important;
    height: 38px !important;
    padding: 0.375rem 0.75rem !important;
}

.form-group label, .form-label {
    font-size: 1rem !important;
    margin-bottom: 0.5rem !important;
}

/* Make the card more compact */
.card {
    min-height: auto !important;
}
    </style>
    <style>
        /* Specific styles for GAD Proposal preview */
        .proposal-container {
            border: 1px solid #000;
            padding: 20px;
            margin: 20px auto;
            max-width: 1100px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            background-color: #fff;
            color: #000;
        }

        /* Header table styles */
        .header-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 5px;
        }

        .header-table td {
            border: 0.5px solid #000;
            padding: 5px;
            text-align: center;
        }

        /* Section heading styles */
        .section-heading {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        /* Table styles */
        .data-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 10px;
        }

        .data-table th, .data-table td {
            border: 0.5px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        /* Checkbox styles */
        .checkbox-container {
            display: flex;
            justify-content: center;
            margin: 10px 0;
        }

        .checkbox-option {
            margin: 0 20px;
            font-size: 12pt;
        }

        /* Signature table styles */
        .signatures-table {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            border-collapse: collapse !important;
            page-break-inside: avoid !important;
            position: relative !important;
            left: 0 !important;
            right: 0 !important;
        }

        .signatures-table td {
            border: 0.5px solid #000;
            padding: 10px;
            text-align: center;
            vertical-align: top;
            height: 80px;
        }

        /* Heading styles */
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* List styles */
        .proposal-container ol, .proposal-container ul {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 25px;
        }

        .proposal-container li {
            margin-bottom: 2px;
        }

        /* Responsibilities section */
        .responsibilities {
            margin-left: 20px;
        }

        /* Sustainability Plan - blue text */
        .sustainability-plan {
            color: blue;
        }

        .sustainability-plan ol li {
            color: blue;
        }

        /* Signature name styles */
        .signature-name {
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 0;
        }

        .signature-position {
            color: blue !important;
            margin-top: 0;
        }

        .signature-label {
            font-weight: bold;
            color: brown !important;
        }

        /* Page numbering and tracking */
        .page-footer {
            text-align: right;
            margin-top: 20px;
            font-size: 10pt;
        }

        /* Gantt chart cell styling */
        .gantt-filled {
            background-color: black !important;
        }

        /* Brown text for labels */
        .brown-text {
            color: brown !important;
        }

        /* Add page break styles */
        .page-break {
            page-break-before: always;
        }
        
        /* Print-specific styles */
        @media print {
            @page {
                size: 8.5in 13in;
                margin-top: 1.52cm;
                margin-bottom: 2cm;
                margin-left: 1.78cm;
                margin-right: 2.03cm;
                border-top: 1px solid black !important;
                border-bottom: 1px solid black !important;
            }
            
            /* Force ALL colors to black - no exceptions */
            *, p, span, div, td, th, li, ul, ol, strong, em, b, i, a, h1, h2, h3, h4, h5, h6,
            [style*="color:"], [style*="color="], [style*="color :"], [style*="color ="],
            [style*="color: brown"], [style*="color: blue"], [style*="color: red"], 
            .brown-text, .blue-text, .sustainability-plan, .sustainability-plan p, .sustainability-plan li,
            .signature-label, .signature-position {
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            /* First page footer with tracking number */
            @page:first {
                margin-top: 1.52cm;
                margin-bottom: 2cm;
                margin-left: 1.78cm;
                margin-right: 2.03cm;
                border-top: 1px solid black !important;
                border-bottom: 1px solid black !important;
            }
            
            /* Ensure proper spacing for the footer */
            .proposal-container {
                margin-bottom: 1.5cm !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                /* Remove border */
                border: none;
                box-sizing: border-box;
                min-height: calc(100% - 2cm);
                width: calc(100% - 3.81cm);
                margin-top: 1.52cm !important;
                margin-bottom: 2cm !important;
                margin-left: 1.78cm !important;
                margin-right: 2.03cm !important;
                background-clip: padding-box;
                box-shadow: none;
            }
        }

        /* Specific dark mode styles */
        [data-bs-theme="dark"] .proposal-container {
            background-color: #333 !important;
            color: #fff !important;
            border: 1px solid #555 !important;
        }

        [data-bs-theme="dark"] .header-table td,
        [data-bs-theme="dark"] .data-table th,
        [data-bs-theme="dark"] .data-table td,
        [data-bs-theme="dark"] .signatures-table td {
            border-color: #555 !important;
            background-color: #333 !important;
            color: #fff !important;
        }

        /* Override colors for dark mode */
        @media (prefers-color-scheme: dark) {
            [data-bs-theme="dark"] .sustainability-plan,
            [data-bs-theme="dark"] .sustainability-plan * {
                color: #5eb5ff !important;
            }
            
            [data-bs-theme="dark"] .signature-position {
                color: #5eb5ff !important;
            }
            
            [data-bs-theme="dark"] .signature-label,
            [data-bs-theme="dark"] .brown-text {
                color: #ff9d7d !important;
            }
        }

        /* Remove page number line on last page */
        @page:last {
            border-bottom: none !important;
        }

        /* Special styling for the approval link - only visible to Central users */
        .approval-link {
            background-color: var(--accent-color);
            color: white !important;
            border-radius: 12px;
            margin-top: 10px;
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
</head>
<body>
    <script>
        // Immediately disable all buttons as soon as the page loads
        window.onload = function() {
            for (let quarter = 1; quarter <= 4; quarter++) {
                const printBtn = document.getElementById(`printBtn${quarter}`);
                const exportBtn = document.getElementById(`exportBtn${quarter}`);
                if (printBtn) printBtn.disabled = true;
                if (exportBtn) exportBtn.disabled = true;
            }
        };
    </script>

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
                <a href="../dashboard/dashboard.php" class="nav-link">
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
                    <a class="nav-link  active dropdown-toggle" href="#" id="staffDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-invoice me-2"></i> PPAs
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../ppas_form/ppas.php">Data Entry</a></li>
                        <li><a class="dropdown-item" href="../ppas_proposal_reports/print_proposal.php">GAD Proposal</a></li>
                        <li><a class="dropdown-item" href="../narrative_reports/print_narrative.php">GAD Narrative</a></li>
                        <li><a class="dropdown-item" href="../extension_proposal/extension_proposal.php">Extension Proposal</a></li>
                        <li><a class="dropdown-item" href="../extension_narrative/extension_narrative.php">Extension Narrative</a></li>
                    </ul>
                </div>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-chart-bar me-2"></i> Reports
                    </a>
                    <ul class="dropdown-menu">                       
                        <li><a class="dropdown-item" href="../ppas_reports/ppas_report.php">Quarterly Report</a></li>
                        <li><a class="dropdown-item" href="../ps_atrib_reports/ps.php">PS Attribution</a></li>
                        <li><a class="dropdown-item" href="../annual_reports/annual_report.php">Annual Report</a></li>
                    </ul>
                </div>
                <?php 
$currentPage = basename($_SERVER['PHP_SELF']);
if($isCentral): 
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
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-title">
            <i class="fas fa-file-alt"></i>
            <h2>External Print GAD Proposal</h2>
        </div>

        <!-- Report Generation Form -->
        <div class="card mb-4" style="min-height: auto; max-height: fit-content;">
            <div class="card-body py-3">
                <form id="reportForm" class="compact-form">
                    <div class="row align-items-start">
                        <div class="col-md-3">
                            <label for="campus" class="form-label"><i class="fas fa-university me-1"></i> Campus</label>
                            <select class="form-control" id="campus" required style="height: 38px;">
                                <option value="">Select Campus</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label"><i class="fas fa-calendar-alt me-1"></i> Year</label>
                            <select class="form-control" id="year" required disabled style="height: 38px;">
                                <option value="">Select Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="prepared_by" class="form-label"><i class="fas fa-user-edit me-1"></i> Prepared By Position</label>
                            <select class="form-control" id="prepared_by" disabled style="height: 38px;">
                                <option value="">Select Position</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="proposal" class="form-label"><i class="fas fa-file-alt me-1"></i> Proposal</label>
                            <div class="position-relative">
                                <input type="text" 
                                      class="form-control" 
                                      id="proposal" 
                                      placeholder="Search for a proposal..." 
                                      autocomplete="off"
                                      style="height: 38px;"
                                      disabled
                                      required>
                                <div id="proposalDropdown" class="dropdown-menu w-100" style="display:none; max-height: 150px; overflow-y: auto;"></div>
                                <input type="hidden" id="proposal_id">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary d-inline-block" style="height: 38px;">
                                <i class="fas fa-print"></i> Generate
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Preview -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title">Proposal Preview</h5>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="printReport()">
                            <i class="fas fa-print"></i> Print
                            </button>
                       
                                </div>
                            </div>
                <div id="reportPreview" class="table-responsive">
                    <!-- Proposal content will be loaded here -->
                    <div class="text-center text-muted py-5" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p>Select a campus, year, and proposal to generate the preview</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            // Hide the generate button since we're auto-generating
            $('.row.mt-2').hide();
            
            loadCampusOptions();
            
            // Handle form submission
            $('#reportForm').on('submit', function(e) {
                    e.preventDefault();
                const selectedProposalId = $('#proposal_id').val();
                console.log('Form submitted. Proposal ID:', selectedProposalId);
                
                if (!selectedProposalId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please select a proposal first.'
                    });
                    return;
                }
                
                generateReport();
            });

            // Handle proposal search input
            let searchTimeout;
            $('#proposal').on('input', function() {
                const searchTerm = $(this).val();
                const selectedCampus = $('#campus').val();
                const selectedYear = $('#year').val();
                const selectedPosition = $('#prepared_by').val();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Clear proposal ID when input changes
                $('#proposal_id').val('');
                
                if (!selectedCampus || !selectedYear || !selectedPosition) {
                    console.log('Campus, Year, or Prepared By not selected');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Selection Required',
                        text: 'Please complete all previous selections first.'
                    });
                    return;
                }
                
                if (searchTerm.length < 1) {
                    $('#proposalDropdown').hide().empty();
                    return;
                }
                
                // Set new timeout
                searchTimeout = setTimeout(() => {
                    console.log('Searching for:', searchTerm);
                    $.ajax({
                        url: 'api/get_proposals.php',
                        method: 'GET',
                        data: {
                            search: searchTerm,
                            campus: selectedCampus,
                            year: selectedYear
                        },
                        dataType: 'json',
                        success: function(response) {
                            try {
                                console.log('Search response:', response);
                                const dropdown = $('#proposalDropdown');
                                dropdown.empty();
                                
                                // Make sure response is an object if it's a string
                                if (typeof response === 'string') {
                                    response = JSON.parse(response);
                                }
                                
                                if (response && response.status === 'success' && Array.isArray(response.data) && response.data.length > 0) {
                                    // Store proposals globally
                                    window.proposals = response.data;
                                    
                                    console.log('Found', response.data.length, 'proposals');
                                    
                                    // Add proposals to dropdown
                                    response.data.forEach(function(proposal) {
                                        const item = $('<div class="dropdown-item"></div>')
                                            .text(proposal.activity_title)
                                            .attr('data-id', proposal.id)
                                            .click(function() {
                                                // Set input value
                                                $('#proposal').val(proposal.activity_title);
                                                // Set hidden proposal_id
                                                $('#proposal_id').val(proposal.id);
                                                // Hide dropdown
                                                dropdown.hide();
                                                console.log('Selected proposal:', proposal.activity_title, 'with ID:', proposal.id);
                                                
                                                // Auto-generate report when proposal is selected
                                                generateReport();
                                            });
                                        
                                        dropdown.append(item);
                                    });
                                    
                                    // Show dropdown
                                    dropdown.show();
                                    console.log('Updated dropdown with', response.data.length, 'options');
                        } else {
                                    console.log('No proposals found - Response data:', JSON.stringify(response));
                                    // Show "no results" message
                                    dropdown.append('<div class="dropdown-item disabled">No proposals found</div>');
                                    dropdown.show();
                                }
                            } catch (error) {
                                console.error('Error processing response:', error);
                                const dropdown = $('#proposalDropdown');
                                dropdown.empty();
                                dropdown.append('<div class="dropdown-item disabled">Error processing response</div>');
                                dropdown.show();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Search error:', error);
                            const dropdown = $('#proposalDropdown');
                            dropdown.empty();
                            dropdown.append('<div class="dropdown-item disabled">Error loading proposals</div>');
                            dropdown.show();
                        }
                    });
                }, 300);
            });

            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#proposal, #proposalDropdown').length) {
                    $('#proposalDropdown').hide();
                }
            });

            // Clear form button (optional - you can add this to your HTML)
            function clearProposalForm() {
                $('#proposal').val('');
                $('#proposal_id').val('');
                $('#proposalDropdown').hide();
            }

            // Handle proposal selection
            $('#proposal').on('change', function() {
                const selectedTitle = $(this).val();
                console.log('Selected title:', selectedTitle);
                
                const proposals = window.proposals || [];
                console.log('Available proposals:', proposals);
                
                const selectedProposal = proposals.find(p => p.activity_title === selectedTitle);
                console.log('Found proposal:', selectedProposal);

                if (selectedProposal) {
                    $('#proposal_id').val(selectedProposal.id);
                    console.log('Set proposal ID to:', selectedProposal.id);
                } else {
                    $('#proposal_id').val('');
                    if (selectedTitle) {
                        console.log('No matching proposal found for title:', selectedTitle);
                    }
                }
            });

            // Handle campus change
            $('#campus').on('change', function() {
                const selectedCampus = $(this).val();
                if (selectedCampus) {
                    loadYearOptions();
                    $('#year').prop('disabled', false);
                    
                    // Reset subsequent fields
                    $('#prepared_by').val('').prop('disabled', true);
                    $('#proposal').val('').prop('disabled', true);
                    $('#proposal_id').val('');
                    
                    // Only show the placeholder if there's no existing preview content
                    if ($('#reportPreview').is(':empty')) {
                        $('#reportPreview').html(`
                            <div class="text-center text-muted py-5" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;">
                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                <p>Select a campus, year, and proposal to generate the preview</p>
                            </div>
                        `);
                    }
                } else {
                    $('#year').val('').prop('disabled', true);
                    $('#prepared_by').val('').prop('disabled', true);
                    $('#proposal').val('').prop('disabled', true);
                    $('#proposal_id').val('');
                }
            });
            
            // Handle year change
            $('#year').on('change', function() {
                const selectedYear = $(this).val();
                if (selectedYear) {
                    const preparedBySelect = $('#prepared_by');
                    preparedBySelect.empty();
                    
                    const selectedCampus = $('#campus').val();
                    
                    if (selectedCampus === "Central") {
                        // Central positions
                        preparedBySelect.append(`
                            <option value="">Select Position</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Extension Coordinator">Extension Coordinator</option>
                            <option value="Director, Extension Services">Director, Extension Services</option>
                            <option value="Vice President for RDES">Vice President for RDES</option>
                            <option value="Vice President for AF">Vice President for AF</option>
                        `);
                    } else if (selectedCampus) {
                        // Campus positions
                        preparedBySelect.append(`
                            <option value="">Select Position</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Extension Coordinator">Extension Coordinator</option>
                            <option value="GAD Head Secretariat">GAD Head Secretariat</option>
                            <option value="Vice Chancellor for AF">Vice Chancellor for AF</option>
                        `);
                    }
                    
                    preparedBySelect.prop('disabled', false);
                    
                    // Reset subsequent fields
                    $('#proposal').val('').prop('disabled', true);
                    $('#proposal_id').val('');
                } else {
                    $('#prepared_by').val('').prop('disabled', true);
                    $('#proposal').val('').prop('disabled', true);
                    $('#proposal_id').val('');
                }
            });
            
            // Handle prepared by change
            $('#prepared_by').on('change', function() {
                const selectedPosition = $(this).val();
                if (selectedPosition) {
                    $('#proposal').prop('disabled', false);
                    
                    // If a proposal is already selected, regenerate the report with the new position
                    const selectedProposalId = $('#proposal_id').val();
                    if (selectedProposalId) {
                        console.log('Prepared By changed, regenerating report with new position:', selectedPosition);
                        // Show loading indicator
                        $('#reportPreview').html(`
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Refreshing proposal report...</p>
                            </div>
                        `);
                        
                        // Regenerate report with new position
                        setTimeout(() => {
                            generateReport();
                        }, 300);
                    }
                } else {
                    $('#proposal').val('').prop('disabled', true);
                    $('#proposal_id').val('');
                }
            });
        });
        
        // Load campus options
        function loadCampusOptions() {
            const campusSelect = $('#campus');
            campusSelect.prop('disabled', true);
            
            const isCentral = <?php echo $isCentral ? 'true' : 'false' ?>;
            const userCampus = "<?php echo $userCampus ?>";
            
            if (isCentral) {
                $.ajax({
                    url: 'api/get_campuses.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        campusSelect.empty().append('<option value="">Select Campus</option>');
                        if (response.status === 'success' && response.data) {
                            console.log('Available campuses:', response.data);
                            response.data.forEach(function(campus) {
                                if (campus.name && campus.name !== 'null' && campus.name !== 'Default Campus') {
                                    campusSelect.append(`<option value="${campus.name}">${campus.name}</option>`);
                                }
                            });
                        }
                        campusSelect.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading campuses:', error);
                        campusSelect.empty().append('<option value="">Error loading campuses</option>');
                    }
                });
            } else {
                campusSelect.empty().append(`<option value="${userCampus}" selected>${userCampus}</option>`);
                campusSelect.prop('disabled', true);
                loadYearOptions();
            }
        }

        // Load year options
        function loadYearOptions() {
            const yearSelect = $('#year');
            const selectedCampus = $('#campus').val();
            
            yearSelect.prop('disabled', true);
            yearSelect.html('<option value="">Loading years...</option>');
            
            $.ajax({
                url: 'api/get_proposal_years.php',
                method: 'GET',
                data: { campus: selectedCampus },
                dataType: 'json',
                success: function(response) {
                    console.log('Year response:', response);
                    yearSelect.empty().append('<option value="">Select Year</option>');
                    
                    if (response.status === 'success' && response.data && response.data.length > 0) {
                        response.data.sort((a, b) => b.year - a.year).forEach(function(yearData) {
                            yearSelect.append(`<option value="${yearData.year}">${yearData.year}</option>`);
                        });
                        yearSelect.prop('disabled', false);
                    } else {
                        yearSelect.html('<option value="">No years available</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading years:', error);
                    yearSelect.html(`<option value="">Error loading years</option>`);
                }
            });
        }

        // Generate proposal report
        function generateReport() {
            const selectedCampus = $('#campus').val();
            const selectedYear = $('#year').val();
            const selectedPosition = $('#prepared_by').val();
            const selectedProposalId = $('#proposal_id').val();
            
            if (!selectedCampus || !selectedYear || !selectedPosition || !selectedProposalId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selection Required',
                    text: 'Please select all required fields to generate the proposal.'
                });
                return;
            }
            
            // Show loading state
            $('#reportPreview').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading proposal...</p>
                </div>
            `);
            
            // First, if central user, fetch the campus signatories
            const isCentral = <?php echo $isCentral ? 'true' : 'false' ?>;
            if (isCentral) {
                console.log("Central user, fetching signatories for campus:", selectedCampus);
                
                // Reset window.campusSignatories
                window.campusSignatories = null;
                
                // Don't block the whole process on the debugging API call
                try {
            $.ajax({
                        url: 'api/check_campus_names.php',
                method: 'GET',
                        dataType: 'json',
                        timeout: 5000, // 5 second timeout
                success: function(response) {
                            console.log("Campus names in database:", response.data);
                            if (response.all_signatories) {
                                console.log("All signatories in database:", response.all_signatories);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error checking campus names:", error);
                            console.error("Response:", xhr.responseText);
                        }
                    });
                            } catch (e) {
                    console.error("Exception in check_campus_names call:", e);
                }
                
                // First, fetch the signatories
                $.ajax({
                    url: 'api/get_campus_signatories.php',
                    method: 'GET',
                    data: { 
                        campus: selectedCampus,
                        position: selectedPosition 
                    },
                    dataType: 'json',
                    timeout: 10000, // 10 second timeout
                    success: function(sigResponse) {
                        console.log("Signatories response:", sigResponse);
                        if (sigResponse.status === 'success') {
                            // Store the signatories in the window object for use in the displayProposal function
                            window.campusSignatories = sigResponse.data;
                            console.log("Successfully set campusSignatories:", window.campusSignatories);
                            
                            // Verify the structure
                            console.log("Signatory name1:", window.campusSignatories.name1);
                            console.log("Signatory name2:", window.campusSignatories.name2);
                            console.log("Signatory name3:", window.campusSignatories.name3);
                            console.log("Signatory name4:", window.campusSignatories.name4);
                        } else {
                            console.error('Error loading signatories:', sigResponse.message);
                            window.campusSignatories = null;
                        }
                        
                        // After fetching signatories (success or failure), fetch the proposal
                        fetchProposalDetails(selectedCampus, selectedYear, selectedProposalId, selectedPosition);
                },
                error: function(xhr, status, error) {
                        console.error('AJAX Error loading signatories:', error);
                        console.error('Response:', xhr.responseText);
                        
                        // Create dummy signatories with "API Error" marker
                        window.campusSignatories = {
                            name1: 'API Error - Check Console',
                            name2: 'API Error - Check Console',
                            name3: 'API Error - Check Console',
                            name4: 'API Error - Check Console',
                            name5: 'API Error - Check Console',
                            campus: selectedCampus
                        };
                        
                        // Even if signatories fetch fails, continue with proposal
                        fetchProposalDetails(selectedCampus, selectedYear, selectedProposalId, selectedPosition);
                    }
                });
            } else {
                // Non-central users can directly fetch proposal
                fetchProposalDetails(selectedCampus, selectedYear, selectedProposalId, selectedPosition);
            }
        }

        // Print report function
        function printReport() {
            // Create a print window with a specific title
            const printWindow = window.open('', '_blank', 'width=1200,height=800');
            
            // Set window properties immediately to prevent about:blank
            printWindow.document.open();
            printWindow.document.title = "GAD Proposal";
            
            let reportContent = $('#reportPreview').html();
            
            // SPECIAL FIX: Remove any empty divs or spaces that might cause empty boxes
            reportContent = reportContent.replace(/<div[^>]*>\s*<\/div>/g, '');
            reportContent = reportContent.replace(/<pre[\s\S]*?<\/pre>/g, '');
            
            // Make sure Unicode checkboxes print properly
            reportContent = reportContent.replace(/☐/g, '☐');
            reportContent = reportContent.replace(/☑/g, '☑');
            
            // Always force print to be in light mode for consistent output
            const printStyles = `
                <style>
                    @page {
                        size: 8.5in 13in;
                        margin-top: 1.52cm;
                        margin-bottom: 2cm;
                        margin-left: 1.78cm;
                        margin-right: 2.03cm;
                        border-top: 1px solid black !important;
                        border-bottom: 1px solid black !important;
                    }
                    
                    /* First page footer with tracking number */
                    @page:first {
                        @bottom-left {
                            content: "Tracking Number:___________________" !important;
                            font-family: 'Times New Roman', Times, serif !important;
                            font-size: 10pt !important;
                            color: black !important;
                        }
                        
                        @bottom-right {
                            content: "Page " counter(page) " of " counter(pages);
                            font-family: 'Times New Roman', Times, serif;
                            font-size: 10pt;
                            color: black;
                        }
                    }
                    
                    /* Remove any inline tracking numbers */
                    div[style*="Tracking Number"] {
                        display: none !important;
                    }
                    
                    body {
                        background-color: white !important;
                        color: black !important;
                        font-family: 'Times New Roman', Times, serif !important;
                        font-size: 12pt !important;
                        line-height: 1.2 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    
                    /* Explicit tracking number at bottom of page */
                    .tracking-footer {
                        position: fixed !important;
                        bottom: 0.5cm !important;
                        left: 0 !important;
                        width: 100% !important;
                        text-align: center !important;
                        font-family: 'Times New Roman', Times, serif !important;
                        font-size: 10pt !important;
                        color: black !important;
                        z-index: 1000 !important;
                    }
                    
                    /* Proposal container */
                    .proposal-container {
                        background-color: white !important;
                        color: black !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        border: none !important;
                    }
                    
                    /* Container for signatures with no margins */
                    div[style*="width: 100%"] {
                        margin: 0 !important;
                        padding: 0 !important;
                        width: 100% !important;
                        max-width: 100% !important;
                    }
                    
                    table {
                        width: 100% !important;
                        border-collapse: collapse !important;
                        page-break-inside: auto !important;
                    }
                    
                    td, th {
                        border: 1px solid black !important;
                        padding: 5px !important;
                        page-break-inside: avoid !important;
                        background-color: white !important;
                        color: black !important;
                    }
                    
                    /* Force specific colors */
                    [style*="color: blue"], .sustainability-plan, .sustainability-plan *,
                    [style*="color: blue;"], ol[style*="color: blue"] li, li[style*="color: blue"],
                    [style*="GAD Head"], [style*="Extension Services"],
                    [style*="Vice Chancellor"], [style*="Chancellor"] {
                        color: blue !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    
                    /* Force browns */
                    [style*="color: brown"], [style*="color: brown;"],
                    div[style*="color: brown"], div[style*="color: brown;"] {
                        color: brown !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }
                    
                    /* Ensure black cells in Gantt chart */
                    td[style*="background-color: black"] {
                        background-color: black !important;
                        color: white !important;
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                    }

                    /* Show tracking number only on first page */
                    .tracking-number {
                        position: absolute;
                        bottom: 20px;
                        left: 20px;
                        font-size: 10pt;
                    }
                    
                    /* Page breaks */
                    .page-break-before {
                        page-break-before: always !important;
                    }
                    
                    .page-break-after {
                        page-break-after: always !important;
                    }
                    
                    /* Page numbers - show on all pages */
                            @page {
                                @bottom-right {
                                    content: "Page " counter(page) " of " counter(pages);
                                    font-family: 'Times New Roman', Times, serif;
                                    font-size: 10pt;
                            }
                        }
                    </style>
            `;
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>GAD Proposal</title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    ${printStyles}
                    <style>
                        @page {
                            size: 8.5in 13in;
                            margin-top: 1.52cm;
                            margin-bottom: 2cm;
                            margin-left: 1.78cm;
                            margin-right: 2.03cm;
                            border: 1px solid black !important;
                        }
                        
                        /* Force all colors to be black */
                        * { color: black !important; }
                        
                        /* The only exception is black background cells for Gantt chart */
                        td[style*="background-color: black"] {
                            background-color: black !important;
                        }
                    </style>
                </head>
                <body>
                    <div class="WordSection1">
                        ${reportContent}
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            setTimeout(() => {
                printWindow.print();
                // Add event listener to close the window after printing is complete
                printWindow.addEventListener('afterprint', function() {
                    printWindow.close();
                });
            }, 500);
        }

        // Function to check proposal information directly
        function checkProposalDirectly(proposalId) {
            if (!proposalId) {
                Swal.fire({
                    icon: 'error',
                    title: 'No Proposal ID',
                    text: 'No proposal ID provided to check.'
                });
                return;
            }
            
            const selectedPosition = $('#prepared_by').val() || 'GAD Head Secretariat';
            
            // Show loading
            $('#reportPreview').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Checking proposal in database...</p>
                </div>
            `);
            
            // Use an existing API endpoint instead of a specialized debugging endpoint
            $.ajax({
                url: 'api/get_proposal_details.php',
                method: 'GET',
                data: {
                    proposal_id: proposalId,
                    campus: $('#campus').val(),
                    year: $('#year').val(),
                    position: selectedPosition
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Debug response:', response);
                    
                    if (response.status === 'success') {
                        // If proposal found, show a mockup of the GAD proposal with the data
                        let mockupProposal = `
                            <div class="proposal-container">
                                <!-- Header Section -->
                                <table class="header-table">
                                    <tr>
                                        <td style="width: 15%; text-align: center;">
                                            <img src="../images/BatStateU-NEU-Logo.png" alt="BatState-U Logo" style="max-width: 80px;">
                                        </td>
                                        <td style="width: 70%; text-align: center;">
                                            <div style="font-size: 14pt; font-weight: bold;">BATANGAS STATE UNIVERSITY</div>
                                            <div style="font-size: 12pt;">THE NATIONAL ENGINEERING UNIVERSITY</div>
                                            <div style="font-size: 11pt; font-style: italic;">${response.data.campus || 'Unknown Campus'}</div>
                                            <div style="font-size: 12pt; font-weight: bold; margin-top: 10px;">GAD PROPOSAL (INTERNAL PROGRAM/PROJECT/ACTIVITY)</div>
                                        </td>
                                        <td style="width: 15%; text-align: center;">
                                            <div style="font-size: 10pt;">Reference No.: BatStateU-FO-ESU-09</div>
                                            <div style="font-size: 10pt;">Effectivity Date: August 25, 2023</div>
                                            <div style="font-size: 10pt;">Revision No.: 00</div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Add tracking number to first page -->
                                <div style="text-align: left; margin-top: 5px; margin-bottom: 5px; font-size: 10pt;">
                                    Tracking Number:___________________
                                </div>

                                <!-- Activity Type Checkboxes -->
                                <div style="width: 100%; text-align: center; margin: 10px 0;">
                                    <span style="display: inline-block; margin: 0 20px;">☐ Program</span>
                                    <span style="display: inline-block; margin: 0 20px;">☐ Project</span>
                                    <span style="display: inline-block; margin: 0 20px;">☒ Activity</span>
                                </div>

                                <!-- Proposal Details -->
                                <table class="data-table">
                                    <tr>
                                        <td style="width: 25%; font-weight: bold;">I. Title:</td>
                                        <td style="width: 75%;">${response.data.activity_title || response.data.title || response.data.activity || 'Test Activity'}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">II. Date and Venue:</td>
                                        <td>${response.data.date_venue ? response.data.date_venue.venue + '<br>' + response.data.date_venue.date : 'Not specified'}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold;">III. Mode of Delivery:</td>
                                        <td>${response.data.delivery_mode || 'Not specified'}</td>
                                    </tr>
                                </table>
                                
                                <div class="section-heading">IV. Project Team:</div>
                                <div style="margin-left: 20px;">
                                    <div><strong>Project Leader/s:</strong> ${response.data.project_team ? response.data.project_team.project_leaders.names : 'Not specified'}</div>
                                    <div class="responsibilities">
                                        <div><strong>Responsibilities:</strong></div>
                                        <ol>
                                            ${response.data.project_team && response.data.project_team.project_leaders.responsibilities ? 
                                              response.data.project_team.project_leaders.responsibilities.map(resp => `<li>${resp}</li>`).join('') : 
                                              '<li>No responsibilities specified</li>'}
                                        </ol>
                                    </div>
                                </div>

                                <h5 class="mt-4">Debug Information</h5>
                                <div class="alert alert-success">
                                    <p><strong>The proposal was found in the database!</strong></p>
                                    <p>Prepared By Position: ${selectedPosition}</p>
                                    <p>Try using the "Generate Proposal" button again to view the complete proposal.</p>
                                </div>
                            </div>
                        `;
                        
                        // Display the HTML
                        $('#reportPreview').html(mockupProposal);
                        
                        // Store request_type and activity_type as data attributes for checkbox handling
                        $('#reportPreview').data('request-type', requestType);
                        $('#reportPreview').data('activity-type', activityType);
                        
                        // Now let's try to generate the full report
                        setTimeout(() => {
                            generateReport();
                        }, 1000);
                    } else {
                        let errorOutput = `
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i> Proposal Not Found</h5>
                                <p>${response.message || 'The proposal could not be found in the database.'}</p>
                                <div class="card mb-3">
                                    <div class="card-header">Troubleshooting Information</div>
                                    <div class="card-body">
                                        <p><strong>Proposal ID:</strong> ${proposalId}</p>
                                        <p><strong>Campus:</strong> ${$('#campus').val()}</p>
                                        <p><strong>Year:</strong> ${$('#year').val()}</p>
                                        <p><strong>Position:</strong> ${selectedPosition}</p>
                                        <p>Please verify these values are correct in the database.</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-primary" onclick="$('#proposal').val(''); $('#proposal_id').val(''); $('#proposalDropdown').hide();">
                                        Clear Selection
                                    </button>
                                </div>
                            </div>
                        `;
                        $('#reportPreview').html(errorOutput);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Debug error:', error);
                    
                    $('#reportPreview').html(`
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-circle"></i> Error Checking Proposal</h5>
                            <p>Could not check the proposal information: ${error}</p>
                            <pre>${xhr.responseText || 'No response details available'}</pre>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-primary" onclick="generateReport()">
                                    Try Again
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        }

        function updateDateTime() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
            
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        function updateThemeIcon(theme) {
            const themeIcon = document.getElementById('theme-icon');
            themeIcon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
            
            // Update proposal preview container styling based on theme
            const previewContent = $('#reportPreview .proposal-container');
            if (previewContent.length > 0) {
                if (newTheme === 'dark') {
                    previewContent.addClass('dark-mode-proposal').removeClass('light-mode-proposal');
                } else {
                    previewContent.addClass('light-mode-proposal').removeClass('dark-mode-proposal');
                }
            }
        }

        // Apply saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            updateThemeIcon(savedTheme);

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
        });

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
                    container: 'swal-blur-container',
                    popup: 'logout-swal'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.body.classList.add('fade-out');
                    
                    setTimeout(() => {
                        window.location.href = '../loading_screen.php?redirect=index.php';
                    }, 10); // Changed from 50 to 10 - make it super fast
                }
            });
        }

        function fetchProposalDetails(selectedCampus, selectedYear, selectedProposalId, selectedPosition) {
            $.ajax({
                url: 'api/get_proposal_details.php',
                method: 'GET',
                data: {
                    campus: selectedCampus,
                    year: selectedYear,
                    proposal_id: selectedProposalId,
                    position: selectedPosition
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        // Create a counter for pending requests
                        let pendingRequests = 0;
                        
                        // Check if we need to fetch any missing fields
                        if (!response.data.sections.request_type) {
                            pendingRequests++;
                            // Fetch the request type from the database
                            $.ajax({
                                url: 'api/get_proposal_request_type.php',
                                method: 'GET',
                                data: { proposal_id: selectedProposalId },
                                dataType: 'json',
                                success: function(typeResponse) {
                                    if (typeResponse.status === 'success') {
                                        response.data.sections.request_type = typeResponse.request_type || 'client';
                                    } else {
                                        response.data.sections.request_type = 'client';
                                    }
                                    
                                    // Decrement pending requests and check if we can display
                                    pendingRequests--;
                                    if (pendingRequests === 0) {
                        displayProposal(response.data, selectedPosition);
                        // Fix checkbox display
                        setTimeout(fixCheckboxDisplay, 500);
                                    }
                                },
                                error: function() {
                                    response.data.sections.request_type = 'client';
                                    
                                    // Decrement pending requests and check if we can display
                                    pendingRequests--;
                                    if (pendingRequests === 0) {
                                        displayProposal(response.data, selectedPosition);
                                    }
                                }
                            });
                        }
                        
                        // Also fetch the activity type (program/project/activity) if missing
                        if (!response.data.sections.type) {
                            pendingRequests++;
                            // Fetch the activity type from the database - use GET method since we're just retrieving data
                            $.ajax({
                                url: 'api/get_proposal_type.php',
                                method: 'GET',
                                data: { proposal_id: selectedProposalId },
                                dataType: 'json',
                                success: function(activityTypeResponse) {
                                    if (activityTypeResponse.status === 'success') {
                                        response.data.sections.type = activityTypeResponse.type || 'activity';
                                    } else {
                                        response.data.sections.type = 'activity';
                                    }
                                    
                                    // Decrement pending requests and check if we can display
                                    pendingRequests--;
                                    if (pendingRequests === 0) {
                                        displayProposal(response.data, selectedPosition);
                                    }
                                },
                                error: function() {
                                    response.data.sections.type = 'activity';
                                    
                                    // Decrement pending requests and check if we can display
                                    pendingRequests--;
                                    if (pendingRequests === 0) {
                                        displayProposal(response.data, selectedPosition);
                                    }
                                }
                            });
                        }
                        
                        // If there were no additional requests needed, display immediately
                        if (pendingRequests === 0) {
                            displayProposal(response.data, selectedPosition);
                            // Add this call to fix checkbox display after proposal is loaded
                            setTimeout(fixCheckboxDisplay, 500);
                        }
                    } else {
                        // Handle API error with more details
                        console.error('API Error:', response);
                        $('#reportPreview').html(`
                            <div class="text-center text-danger py-5">
                                <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                                <p><strong>Error:</strong> ${response.message || 'Failed to load proposal data'}</p>
                                ${response.code ? `<p><small>Error code: ${response.code}</small></p>` : ''}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    
                    $('#reportPreview').html(`
                        <div class="text-center text-danger py-5">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p><strong>Error:</strong> Error loading proposal. Please try again.</p>
                            <p><small>Status: ${xhr.status} ${status}</small></p>
                        </div>
                    `);
                }
            });
        }
        
        // Debug helper function
        function showDebugInfo(data) {
            Swal.fire({
                title: 'Technical Details',
                html: `<pre style="text-align: left; max-height: 300px; overflow-y: auto;"><code>${JSON.stringify(data, null, 2)}</code></pre>`,
                width: '60%',
                confirmButtonText: 'Close'
            });
        }

        function displayProposal(data, selectedPosition) {
            if (!data || !data.sections) {
                $('#reportPreview').html('<p>No proposal data available</p>');
                return;
            }

            const sections = data.sections;
            
            // Dynamically check the current theme state
            const isDarkMode = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            const themeClass = isDarkMode ? 'dark-mode-proposal' : 'light-mode-proposal';
            
            // Get the selected campus and position
            const selectedCampus = $('#campus').val();
            const preparedByPosition = selectedPosition || $('#prepared_by').val() || 'GAD Head Secretariat';
            
            // Fetch signatories for the selected campus when in central mode
            const isCentral = <?php echo $isCentral ? 'true' : 'false' ?>;
            
            // Get the request type from sections or default to 'client'
            const requestType = sections.request_type || 'client';
            console.log('Request type from data:', requestType);
            
            // Get the activity type from sections or default to 'activity'
            const activityType = sections.type || 'activity';
            console.log('Activity type from data:', activityType);
            
            // Use theme class without inline styling to allow CSS to control colors
            let html = `
            <div class="proposal-container ${themeClass}" style="margin-top: 0; padding-top: 0;">
                <!-- Header Section -->
                <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                    <tr>
                        <td style="width: 15%; text-align: center; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-bottom: 0.1px solid black;">
                            <img src="../images/BatStateU-NEU-Logo.png" alt="BatStateU Logo" style="width: 60px;">
                        </td>
                        <td style="width: 30%; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-bottom: 0.1px solid black;">
                            Reference No.: BatStateU-FO-ESO-09
                        </td>
                        <td style="width: 30%; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-bottom: 0.1px solid black;">
                            Effectivity Date: August 25, 2023
                        </td>
                        <td style="width: 25%; padding: 10px; border-top: 0.1px solid black; border-left: 0.1px solid black; border-right: 0.1px solid black; border-bottom: 0.1px solid black;">
                            Revision No.: 00
                        </td>
                    </tr>
                </table>

                <!-- Title Section -->
                <table style="width: 100%; border-collapse: collapse; margin: 0;">
                    <tr>
                        <td style="text-align: center; padding: 10px; border-left: 0.1px solid black; border-right: 0.1px solid black; border-bottom: 0.1px solid black;">
                            <strong>EXTENSION PROGRAM PLAN / PROPOSAL</strong>
                        </td>
                    </tr>
                </table>

                <!-- Extension Service Type Checkbox Section - Fixed to match image exactly -->
                <table style="width: 100%; border-collapse: collapse; margin: 0; border: 0.5px solid black;">
                    <tr>
                        <td style="padding: 5px 15px;">
                            <div style="display: flex; align-items: flex-start; margin-bottom: 5px;">
                                <div style="font-size: 12pt; padding-right: 5px;">${requestType === 'client' ? '☑' : '☐'}</div>
                                <span style="display: inline-block;">Extension Service Program/Project/Activity is requested by clients.</span>
                            </div>
                            
                            <div style="display: flex; align-items: flex-start;">
                                <div style="font-size: 12pt; padding-right: 5px;">${requestType === 'department' ? '☑' : '☐'}</div>
                                <span style="display: inline-block;">Extension Service Program/Project/Activity is Department's initiative.</span>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Program/Project/Activity Checkbox Section - Completely redesigned to match image exactly -->
                <table style="width: 100%; border-collapse: collapse; border-left: 0.5px solid black; border-right: 0.5px solid black; border-bottom: 0.5px solid black;">
                    <tr>
                        <td style="width: 33.33%; text-align: center; border-right: 0.5px solid black; padding: 5px;">
                            <div style="display: flex; justify-content: center; align-items: center;">
                                <div style="font-size: 12pt; padding-right: 5px;">${activityType === 'program' ? '☑' : '☐'}</div>
                                <span>Program</span>
                            </div>
                        </td>
                        <td style="width: 33.33%; text-align: center; border-right: 0.5px solid black; padding: 5px;">
                            <div style="display: flex; justify-content: center; align-items: center;">
                                <div style="font-size: 12pt; padding-right: 5px;">${activityType === 'project' ? '☑' : '☐'}</div>
                                <span>Project</span>
                            </div>
                        </td>
                        <td style="width: 33.33%; text-align: center; padding: 5px;">
                            <div style="display: flex; justify-content: center; align-items: center;">
                                <div style="font-size: 12pt; padding-right: 5px;">${activityType === 'activity' ? '☑' : '☐'}</div>
                                <span>Activity</span>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Main Content -->
                <div style="padding: 20px; border: 0.1px solid black; border-top: none;">
                    <p><strong>I. Title:</strong> ${sections.title || 'N/A'}</p>

                    <p><strong>II. Location:</strong> ${sections.date_venue && sections.date_venue.venue ? sections.date_venue.venue : sections.location || 'N/A'}</p>

                    <p><strong>III. Duration (Date and Time):</strong> ${sections.date_venue && sections.date_venue.date ? sections.date_venue.date : sections.duration || 'N/A'}</p>

                    <p><strong>IV. Type of Extension Service Agenda:</strong><br>
                    <em>Choose the MOST (only one) applicable Extension Agenda from the following:</em></p>
                    <div style="margin-left: 20px;">
                        <div><input type="checkbox" ${sections.agenda_type === 'BISIG' ? 'checked' : ''}> BatStateU Inclusive Social Innovation for Regional Growth (BISIG) Program</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'LEAP' ? 'checked' : ''}> Livelihood and other Entrepreneurship related or Agriculture Projects (LEAP)</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'EPRP' ? 'checked' : ''}> Environment and Natural resources Conservation, Protection and Rehabilitation Program</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'SAE' ? 'checked' : ''}> Sugar Analytics and Engineering Innovation</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'ADOPT' ? 'checked' : ''}> Adopt-A-Barangay/School/Social Development Thru BIDANI Implementation</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'OUTREACH' ? 'checked' : ''}> Community Outreach</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'TVET' ? 'checked' : ''}> Technical/Vocational Education and Training (TVET) Program</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'ADOPTION' ? 'checked' : ''}> Technology Transfer and Adoption/Utilization Program</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'TECHNICAL' ? 'checked' : ''}> Technical Assistance and Advisory Services Program</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'PESDEV' ? 'checked' : ''}> Parents' Empowerment through Social Development (PESDEV)</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'GAD' ? 'checked' : ''}> Gender and Development</div>
                        <div><input type="checkbox" ${sections.agenda_type === 'DRRM' ? 'checked' : ''}> Disaster Risk Reduction and Management and Disaster Preparedness and Response/Climate Change Adaptation (DRRM and DPCCA)</div>
                        </div>

                <p><strong>V. Sustainable Development Goals (SDG):</strong><br>
                <em>Choose the applicable SDG for your extension project.</em></p>
                <div style="display: flex; flex-wrap: wrap; margin-left: 20px;">
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('No Poverty') ? 'checked' : ''}> No Poverty</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Reduced Inequalities') ? 'checked' : ''}> Reduced Inequalities</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Zero Hunger') ? 'checked' : ''}> Zero Hunger</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Sustainable Cities and Communities') ? 'checked' : ''}> Sustainable Cities and Communities</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Good Health and Well-Being') ? 'checked' : ''}> Good Health and Well-Being</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Responsible Consumption and Production') ? 'checked' : ''}> Responsible Consumption and Production</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Quality Education') ? 'checked' : ''}> Quality Education</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Climate Action') ? 'checked' : ''}> Climate Action</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Gender Equality') ? 'checked' : ''}> Gender Equality</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Life Below Water') ? 'checked' : ''}> Life Below Water</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Clean Water and Sanitation') ? 'checked' : ''}> Clean Water and Sanitation</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Life on Land') ? 'checked' : ''}> Life on Land</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Affordable and Clean Energy') ? 'checked' : ''}> Affordable and Clean Energy</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Peace, Justice and Strong Institutions') ? 'checked' : ''}> Peace, Justice and Strong Institutions</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Decent Work and Economic Growth') ? 'checked' : ''}> Decent Work and Economic Growth</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Partnerships for the Goals') ? 'checked' : ''}> Partnerships for the Goals</div>
                        <div style="width: 33%; margin-bottom: 5px;"><input type="checkbox" ${(sections.sdgs || []).includes('Industry, Innovation and Infrastructure') ? 'checked' : ''}> Industry, Innovation and Infrastructure</div>
                    </div>

                <p><strong>VI. Offices / Colleges / Organizations Involved:</strong><br>
                    ${sections.offices_involved || 'N/A'}</p>

                <p><strong>VII. Programs Involved <em>(specify the programs under the college implementing the project)</em>:</strong><br>
                    ${sections.programs_involved || 'N/A'}</p>

                <p><strong>VIII. Project Leader, Assistant Project Leader and Coordinators:</strong><br>
                    <strong>Project Leader:</strong> ${sections.project_leader || 'N/A'}</p>
                
                <p><strong>Assistant Project Leader:</strong><br>
                    ${Array.isArray(sections.assistant_leaders) ? 
                        sections.assistant_leaders.map(leader => `${leader}<br>`).join('') : 
                        (sections.assistant_leaders || 'N/A')}
                </p>

                <p><strong>IX. Assigned Tasks:</strong></p>
                <table class="data-table" style="width: 100%; border-collapse: collapse; margin-top: 5px;">
                            <tr>
                        <th style="border: 0.5px solid black; padding: 5px; width: 30%;">Personnel Name</th>
                        <th style="border: 0.5px solid black; padding: 5px; width: 70%;">Task</th>
                                </tr>
                        ${Array.isArray(sections.assigned_tasks) ? 
                            sections.assigned_tasks.map(task => `
                                <tr>
                                <td style="border: 0.5px solid black; padding: 5px; vertical-align: top;">${task.name || ''}</td>
                                <td style="border: 0.5px solid black; padding: 5px; vertical-align: top;">${task.tasks || ''}</td>
                                </tr>
                        `).join('') : 
                        `<tr>
                            <td style="border: 0.5px solid black; padding: 5px; vertical-align: top;">N/A</td>
                            <td style="border: 0.5px solid black; padding: 5px; vertical-align: top;">N/A</td>
                        </tr>`
                    }
                            </table>

                <p><strong>X. Partner Agencies:</strong><br>
                    ${sections.partner_agencies || 'N/A'}</p>

                <p><strong>XI. Beneficiaries <em>(Type and Number of Male and Female)</em>:</strong></p>
                <p><strong>Participants of Nangka Batangas:</strong><br>
                    Male: ${sections.beneficiaries ? sections.beneficiaries.male || '0' : '0'} Participants<br>
                    Female: ${sections.beneficiaries ? sections.beneficiaries.female || '0' : '0'} Participants</p>
                
                <p><strong>Participants from BatStateU-TNEU:</strong><br>
                    Male: ${sections.batstateu_participants ? sections.batstateu_participants.male || '0' : '0'} BatStateU-TNEU Lipa Extensionists and DICT Officials<br>
                    Female: ${sections.batstateu_participants ? sections.batstateu_participants.female || '0' : '0'} BatStateU-TNEU Lipa Extensionists and DICT Officials</p>

                <p><strong>XII. Total Cost:</strong><br>
                    ${sections.total_cost || 'The total cost for the implementation of this will be shoulder from DICT and Nasugbu Municipality'}</p>

                <p><strong>XIII. Source of fund:</strong></p>
                <div style="margin-left: 20px;">
                        <div><input type="checkbox" ${sections.funding_source === 'STF' ? 'checked' : ''}> STF</div>
                        <div><input type="checkbox" ${sections.funding_source === 'MDS' ? 'checked' : ''}> MDS</div>
                        <div><input type="checkbox" ${sections.funding_source === 'Others' ? 'checked' : ''}> Others, (Please specify): ${sections.funding_source_other || ''}</div>
                    </div>
                    
                <p><strong>XIV. Rationale <em>(brief description of the situation)</em>:</strong></p>
                <div style="text-align: justify;">
                        ${sections.rationale || 'N/A'}
                </div>

                <p><strong>XV. Objectives <em>(General and Specific)</em>:</strong></p>
                <p><strong>TechEdu aims to establish a robust and comprehensive data governance framework for educational institutions to effectively manage and leverage data while ensuring privacy, compliance, and data-driven decision-making.</strong></p>
                
                <p><strong>Specific Objectives:</strong></p>
                <ul>
                        ${Array.isArray(sections.specific_objectives) ? 
                            sections.specific_objectives.map(obj => `<li>${obj}</li>`).join('') : 
                            `<li>${sections.specific_objectives || 'N/A'}</li>`
                        }
                    </ul>

                <p><strong>XVI. Program/Project Expected Output:</strong></p>
                <div style="text-align: justify;">
                        ${sections.expected_output || 'N/A'}
                </div>

                <p><strong>XVII. Description, Strategies and Methods (Activities / Schedule):</strong></p>
                <div style="text-align: justify;">
                        ${sections.description || 'N/A'}
                </div>

                <!-- Add page break before next sections -->
                <div class="page-break"></div>

                <p><strong>XVIII. Financial Plan:</strong></p>
                <div style="text-align: justify;">
                        ${sections.financial_plan || 'N/A'}
                    </div>

                <p><strong>XIX. Monitoring and Evaluation Mechanics / Plan:</strong></p>
                <table class="data-table" style="width: 100%; border-collapse: collapse; font-size: 10pt;">
                    <tr>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Objectives</th>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Performance Indicators</th>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Baseline Data</th>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Performance Target</th>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Data Source</th>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Collection Method</th>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Frequency of Data Collection</th>
                        <th style="border: 0.5px solid black; padding: 3px; width: 11%;">Office/Person Responsible</th>
                        </tr>
                        ${Array.isArray(sections.monitoring_evaluation) ? 
                            sections.monitoring_evaluation.map((item, index) => {
                                if (Array.isArray(item) && item.length >= 8) {
                                    return `
                                        <tr>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[0] || 'Objectives ' + (index + 1)}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[1] || 'Perf. Indicators ' + (index + 1)}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[2] || 'Baseline Data ' + (index + 1)}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[3] || 'Perf. Target ' + (index + 1)}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[4] || 'Data Source'}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[5] || 'Collection Method'}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[6] || 'Frequency'}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">${item[7] || 'Responsible Person'}</td>
                                        </tr>
                                    `;
                                } else {
                                    return `
                                        <tr>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Objectives ${index + 1}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Perf. Indicators ${index + 1}</td>
                                        <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Baseline Data ${index + 1}</td>
                                            <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Perf. Target ${index + 1}</td>
                                            <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Data Source</td>
                                            <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Collection Method</td>
                                            <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Frequency</td>
                                            <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Responsible Person</td>
                                        </tr>
                                    `;
                                }
                            }).join('') :
                            `<tr>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Impact</td>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Percentage decrease in reported data breaches</td>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">This target can be measured</td>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Properly organized data governance</td>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Questionnaire</td>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">Focus Group Discussion</td>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">1 year</td>
                                <td style="border: 0.5px solid black; padding: 3px; vertical-align: top;">ESO, DICT, College of Informatics and Computing Sciences</td>
                            </tr>`
                        }
                    </table>

                    <p><strong>XX. Sustainability Plan:</strong></p>
                    <div style="text-align: justify;">
                        ${sections.sustainability_plan || '<p>Creating a sustainability plan for TechEdu with a focus on data governance is crucial for ensuring responsible and ethical use of data. Below is a comprehensive plan outlining key steps and strategies:</p>'}
                    </div>
                    <ul>
                        ${Array.isArray(sections.sustainability_items) ? 
                            sections.sustainability_items.map(item => `<li>${item}</li>`).join('') : 
                            `<li>Specify roles and responsibilities for data stewards, data owners, and other relevant stakeholders.</li>
                            <li>Conduct a thorough inventory of all data collected, processed, and stored by TechEdu.</li>
                            <li>Implement robust security protocols to protect data from unauthorized access, breaches, or leaks.</li>
                            <li>Utilize encryption, access controls, and multi-factor authentication to safeguard sensitive information.</li>
                            <li>Establish procedures for data validation, cleaning, and quality assurance to ensure accuracy and reliability.</li>
                            <li>Regularly monitor and audit data to identify and rectify any discrepancies or anomalies.</li>`
                        }
                    </ul>
                    </div>

                <!-- Signatures -->
                <table class="signatures-table" style="width: 100%; border-collapse: collapse; margin-top: 20px; page-break-inside: avoid;">
                    <tr>
                        <td style="width: 50%; padding: 10px; border: 0.5px solid black; vertical-align: top;">
                            <p style="text-align: center; margin-bottom: 60px;"><strong>Prepared by:</strong></p>
                            <p style="text-align: center; margin: 0;"><strong>${sections.prepared_by || 'DR. RYNDEL V. AMORADO'}</strong></p>
                            <p style="text-align: center; margin: 0;">${sections.preparer_position || 'Dean, CICS'}</p>
                            <p style="text-align: center; margin: 0;">Date Signed: ${sections.prepared_date || 'NOV 2 4 2023'}</p>
                        </td>
                        <td style="width: 50%; padding: 10px; border: 0.5px solid black; vertical-align: top;">
                            <p style="text-align: center; margin-bottom: 60px;"><strong>Reviewed by:</strong></p>
                            <p style="text-align: center; margin: 0;"><strong>${sections.reviewed_by || 'Ms. BABY LIEZEL R. ROSALES'}</strong></p>
                            <p style="text-align: center; margin: 0;">${sections.reviewer_position || 'Head, Extension Services'}</p>
                            <p style="text-align: center; margin: 0;">Date Signed: ${sections.reviewed_date || 'NOV 2 4 2023'}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 0.5px solid black; vertical-align: top;">
                            <p style="text-align: center; margin-bottom: 60px;"><strong>Recommending Approval:</strong></p>
                            <p style="text-align: center; margin: 0;"><strong>${sections.recommending_approval || 'DR. FRANCES G. BALAZON'}</strong></p>
                            <p style="text-align: center; margin: 0;">${sections.recommending_position || 'Vice Chancellor for Research, Development and Extension Services'}</p>
                            <p style="text-align: center; margin: 0;">Date Signed: ${sections.recommending_date || 'N/A'}</p>
                        </td>
                        <td style="padding: 10px; border: 0.5px solid black; vertical-align: top;">
                            <p style="text-align: center; margin-bottom: 60px;"><strong>Approved by:</strong></p>
                            <p style="text-align: center; margin: 0;"><strong>${sections.approved_by || 'Atty. ALVIN R. DE SILVA'}</strong></p>
                            <p style="text-align: center; margin: 0;">${sections.approver_position || 'Chancellor'}</p>
                            <p style="text-align: center; margin: 0;">Date Signed: ${sections.approved_date || ''}</p>
                        </td>
                    </tr>
                </table>
                
                <!-- Footer Note -->
                <p style="font-size: 9pt; margin-top: 10px;">
                    <em>Required Attachment: If Extension Service Program/Project/Activity is requested by clients, attach the letter of request with endorsement from the University President.</em>
                </p>
                <p style="font-size: 9pt; margin-top: 5px;">
                    <strong>Cc: </strong>(1) Office of the College Dean/Head, Academic Affairs for CUCSC
                </p>
            
            <!-- Add tracking number at bottom of first page -->
               
            </div>`;

            // Display the HTML
            $('#reportPreview').html(html);
            
            // Store request_type and activity_type as data attributes for checkbox handling
            $('#reportPreview').data('request-type', requestType);
            $('#reportPreview').data('activity-type', activityType);
            
            // Update page numbers dynamically
            const totalPages = 8; // or calculate based on content
            document.querySelectorAll('.total-pages').forEach(el => {
                el.textContent = totalPages;
            });
            
            // Set current page numbers sequentially
            document.querySelectorAll('.page-number').forEach((el, index) => {
                el.textContent = index + 1;
            });
        }
        
        // ... existing code ...
    </script>
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

    </script>
    <script>
        // Function to handle checkbox click in the proposal preview for request type
        function handleRequestTypeChange(type) {
            const selectedProposalId = $('#proposal_id').val();
            
            if (!selectedProposalId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No proposal selected',
                    text: 'Please select a proposal first.',
                    confirmButtonColor: '#6a1b9a'
                });
                return;
            }
            
            console.log(`Updating request type to ${type} for proposal ID ${selectedProposalId}`);
            
            // Show loading indicator
            Swal.fire({
                title: 'Updating...',
                text: 'Saving your changes',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send AJAX request to update the request type
            $.ajax({
                url: 'api/update_proposal_request_type.php',
                type: 'POST',
                data: {
                    proposal_id: selectedProposalId,
                    request_type: type
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Update response:', response);
                    
                    if (response.status === 'success') {
                        // Update checkboxes in UI
                        const clientCheckbox = document.querySelector('[data-request-type="client"]');
                        const departmentCheckbox = document.querySelector('[data-request-type="department"]');
                        
                        if (clientCheckbox && departmentCheckbox) {
                            if (type === 'client') {
                                clientCheckbox.innerHTML = '<span style="position: absolute; top: -4px; left: 2px; font-size: 18px;">×</span>';
                                departmentCheckbox.innerHTML = '';
                            } else {
                                clientCheckbox.innerHTML = '';
                                departmentCheckbox.innerHTML = '<span style="position: absolute; top: -4px; left: 2px; font-size: 18px;">×</span>';
                            }
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: 'Request type has been updated successfully.',
                            confirmButtonColor: '#6a1b9a'
                        });
                    } else {
                        console.error('Error updating request type:', response.message || 'Unknown error');
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to update request type. Please try again.',
                            confirmButtonColor: '#6a1b9a'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    console.error('Response text:', xhr.responseText);
                    
                    try {
                        const errorObj = JSON.parse(xhr.responseText);
                        console.error('Error details:', errorObj);
                    } catch (e) {
                        console.error('Could not parse error response as JSON');
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to connect to server. Please check your connection and try again.',
                        confirmButtonColor: '#6a1b9a'
                    });
                }
            });
        }
        
        // Function to handle activity type changes (program/project/activity)
        function handleActivityTypeChange(type) {
            const selectedProposalId = $('#proposal_id').val();
            
            if (!selectedProposalId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No proposal selected',
                    text: 'Please select a proposal first.',
                    confirmButtonColor: '#6a1b9a'
                });
                return;
            }
            
            console.log(`Updating activity type to ${type} for proposal ID ${selectedProposalId}`);
            
            // Show loading indicator
            Swal.fire({
                title: 'Updating...',
                text: 'Saving your changes',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send AJAX request to update the activity type
            $.ajax({
                url: 'api/update_proposal_type.php',
                type: 'POST',
                data: {
                    proposal_id: selectedProposalId,
                    type: type
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Update response:', response);
                    
                    if (response.status === 'success') {
                        // Refresh the proposal preview to show updates
                        fetchProposalDetails();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: 'Activity type has been updated successfully.',
                            confirmButtonColor: '#6a1b9a'
                        });
            } else {
                        console.error('Error updating activity type:', response.message || 'Unknown error');
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to update activity type. Please try again.',
                            confirmButtonColor: '#6a1b9a'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    console.error('Response text:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to connect to server. Please check your connection and try again.',
                        confirmButtonColor: '#6a1b9a'
                    });
                }
            });
        }
        
        // Add click event listeners for the checkboxes when the document is ready
        $(document).ready(function() {
            // Handle request type checkboxes
            $(document).on('click', '[data-request-type]', function() {
                const type = $(this).attr('data-request-type');
                handleRequestTypeChange(type);
            });
            
            // Also handle clicks on the parent elements
            $(document).on('click', '.proposal-container div:has([data-request-type])', function(e) {
                // Only trigger if the clicked element isn't the checkbox itself
                if (!$(e.target).is('[data-request-type]')) {
                    const type = $(this).find('[data-request-type]').attr('data-request-type');
                    handleRequestTypeChange(type);
                }
            });
            
            // Handle Program/Project/Activity checkboxes click events 
            $(document).on('click', '[data-activity-type]', function() {
                const type = $(this).attr('data-activity-type');
                handleActivityTypeChange(type);
            });
            
            // Also handle clicks on the parent elements
            $(document).on('click', '.proposal-container div:has([data-activity-type])', function(e) {
                // Only trigger if the clicked element isn't the checkbox itself
                if (!$(e.target).is('[data-activity-type]')) {
                    const type = $(this).find('[data-activity-type]').attr('data-activity-type');
                    handleActivityTypeChange(type);
                }
            });
            
            // Add hover effect to checkboxes
            $(document).on('mouseover', '[data-request-type], [data-activity-type], div:has([data-request-type]), div:has([data-activity-type])', function() {
                $(this).css('cursor', 'pointer');
                $(this).css('background-color', 'rgba(106, 27, 154, 0.1)');
            });
            
            $(document).on('mouseout', '[data-request-type], [data-activity-type], div:has([data-request-type]), div:has([data-activity-type])', function() {
                $(this).css('background-color', '');
            });
        });
    </script>
</body>
</html>
</html>
