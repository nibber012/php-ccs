<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';

function applicant_header($title = '') {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    $user = $auth->getCurrentUser();
    
    if (!$user || $user['role'] !== 'applicant') {
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
        }

        .metismenu .menu-link {
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
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

        /* Active States */
        .metismenu .active > .menu-link {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Notification Styles */
        .notifications-dropdown {
            min-width: 300px;
            padding: 0;
        }

        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1;
            border-radius: 0.25rem;
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

                <div class="d-flex align-items-center">
                    <!-- Notifications -->
                    <div class="dropdown me-3">
                        <button class="btn btn-link text-secondary position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-bell fs-4"></i>
                            <?php
                            require_once __DIR__ . '/../../config/database.php';
                            $db = Database::getInstance();;
                            $notifications = $db->query(
                                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
                                [$user['id']]
                            )->fetch(PDO::FETCH_ASSOC);
                            
                            if ($notifications['count'] > 0):
                            ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $notifications['count']; ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end notifications-dropdown">
                            <h6 class="dropdown-header">Notifications</h6>
                            <?php
                            $notifications = $db->query(
                                "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
                                [$user['id']]
                            )->fetchAll(PDO::FETCH_ASSOC);

                            if ($notifications):
                                foreach ($notifications as $notification):
                            ?>
                            <div class="notification-item">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="notification-time">
                                    <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <div class="notification-item">
                                <div class="text-muted">No new notifications</div>
                            </div>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="<?php echo BASE_URL; ?>applicant/notifications.php">
                                View All Notifications
                            </a>
                        </div>
                    </div>

                    <!-- User Profile -->
                    <div class="user-profile dropdown">
                        <button class="user-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <h6 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                <p class="user-role">Applicant</p>
                            </div>
                            <i class="bx bx-chevron-down text-secondary"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">User Profile</h6></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>applicant/profile.php">
                                    <i class="bx bx-user"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>applicant/settings.php">
                                    <i class="bx bx-cog"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>applicant/logout.php">
                                    <i class="bx bx-log-out"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content-wrapper">
<?php
}

function applicant_footer() {
?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/metismenu"></script>
    <script>
        // Initialize MetisMenu
        document.addEventListener('DOMContentLoaded', function() {
            new MetisMenu('.metismenu');
        });

        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar-wrapper').classList.toggle('collapsed');
            document.querySelector('.page-content-wrapper').classList.toggle('expanded');
        });

        // Mark notifications as read when dropdown is opened
        const notificationDropdown = document.querySelector('.notifications-dropdown');
        if (notificationDropdown) {
            notificationDropdown.addEventListener('show.bs.dropdown', function() {
                fetch('<?php echo BASE_URL; ?>applicant/mark_notifications_read.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                });
            });
        }
    </script>
</body>
</html>
<?php
}
?>
