<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';

function admin_header($title = '') {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    $user = $auth->getCurrentUser();
    
    if (!$user) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ? $title . ' - ' : ''; ?>CCS Freshman Screening</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/metismenu/dist/metisMenu.min.css" rel="stylesheet">
    
    <style>
        /* Main Layout Structure */
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* Wrapper */
        .wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Styles */
        .sidebar-wrapper {
            width: 260px;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            background: #2b3a4a;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .sidebar-wrapper.collapsed {
            width: 70px;
        }

        /* Content Area Styles */
        .page-content-wrapper {
            position: relative;
            min-height: 100vh;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
            width: calc(100% - 260px);
            background-color: #f8f9fa;
            padding: 20px;
        }

        .page-content-wrapper.expanded {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        /* MetisMenu Styles */
        .metismenu {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .metismenu .menu-item {
            margin: 2px 0;
            position: relative;
        }

        .metismenu .menu-link {
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .metismenu .menu-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .metismenu .menu-link i {
            width: 20px;
            font-size: 1.2rem;
            margin-right: 10px;
            text-align: center;
        }

        .metismenu .has-arrow::after {
            content: '\e93c';
            font-family: 'boxicons';
            font-size: 1rem;
            position: absolute;
            right: 15px;
            transition: transform 0.3s ease;
        }

        .metismenu .mm-active > .has-arrow::after {
            transform: rotate(90deg);
        }

        .metismenu .submenu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: rgba(0, 0, 0, 0.1);
            display: none;
        }

        .metismenu .submenu.mm-show {
            display: block;
        }

        .metismenu .submenu a {
            padding: 10px 15px 10px 45px;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .metismenu .submenu a:hover,
        .metismenu .submenu a.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .metismenu .submenu i {
            font-size: 1rem;
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Active States */
        .metismenu .mm-active > .menu-link {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .metismenu .submenu .mm-active > a {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Collapsed State */
        .sidebar-wrapper.collapsed .logo-text,
        .sidebar-wrapper.collapsed .menu-link span,
        .sidebar-wrapper.collapsed .has-arrow::after {
            display: none;
        }

        .sidebar-wrapper.collapsed .menu-link i {
            margin: 0;
            width: 100%;
            text-align: center;
            font-size: 1.3rem;
        }

        .sidebar-wrapper.collapsed .submenu {
            position: absolute;
            left: 70px;
            top: 0;
            width: 200px;
            background: #2b3a4a;
            padding: 10px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 0 4px 4px 0;
            display: none !important;
        }

        .sidebar-wrapper.collapsed .menu-item:hover > .submenu {
            display: block !important;
        }

        /* Mobile Styles */
        @media (max-width: 991.98px) {
            .sidebar-wrapper {
                transform: translateX(-100%);
            }
            
            .sidebar-wrapper.mobile-show {
                transform: translateX(0);
            }
            
            .page-content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        /* Top Navbar Styles */
        .top-navbar {
            background: #fff;
            height: 60px;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        /* User Profile Styles */
        .user-profile {
            position: relative;
        }

        .user-profile-btn {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .user-profile-btn:hover {
            background-color: #f8f9fa;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #4e73df;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }

        .user-info {
            text-align: left;
            margin-right: 10px;
        }

        .user-name {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #2b3a4a;
        }

        .user-role {
            margin: 0;
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="page-content-wrapper">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <button class="btn btn-link text-secondary px-1 py-0" id="sidebarToggle">
                    <i class="bx bx-menu fs-4"></i>
                </button>

                <div class="user-profile dropdown">
                    <button class="user-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <h6 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                            <p class="user-role"><?php echo ucfirst($user['role']); ?></p>
                        </div>
                        <i class="bx bx-chevron-down text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">User Profile</h6></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/profile.php">
                                <i class="bx bx-user"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/settings.php">
                                <i class="bx bx-cog"></i> Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>admin/logout.php">
                                <i class="bx bx-log-out"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content-wrapper">
<?php
}

function admin_footer() {
?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/metismenu/dist/metisMenu.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize MetisMenu
        $('#side-menu').metisMenu();

        // Set active menu item based on current URL
        const currentPath = window.location.pathname;
        $('.metismenu a').each(function() {
            if (currentPath.includes($(this).attr('href'))) {
                $(this).addClass('mm-active');
                $(this).parents('li').addClass('mm-active');
                $(this).closest('.submenu').addClass('mm-show');
            }
        });
    });
    </script>
</body>
</html>
<?php } ?>
