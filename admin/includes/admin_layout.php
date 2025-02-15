<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';

function admin_header($title = '') {
    global $auth;
    if (!isset($auth)) {
        $auth = new Auth();
    }
    $user = $auth->getCurrentUser();
    
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ? $title . ' - ' : ''; ?>CCS Admin Panel</title>
    
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

        /* Mobile Toggle Button */
        .mobile-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            display: none;
            background: #2b3a4a;
            border: none;
            color: white;
            padding: 8px;
            border-radius: 4px;
        }

        @media (max-width: 991.98px) {
            .mobile-toggle {
                display: block;
            }

            .page-content-wrapper {
                margin-left: 0;
                width: 100%;
                padding-top: 70px;
            }
        }

        /* Card Styles */
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1rem;
        }

        /* Table Styles */
        .table thead th {
            border-top: none;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #4a5568;
        }

        .table td {
            vertical-align: middle;
        }

        /* Form Controls */
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" type="button">
        <i class='bx bx-menu'></i>
    </button>

    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'admin_sidebar.php'; ?>

        <!-- Page Content -->
        <div class="page-content-wrapper">
<?php
}

function admin_footer() {
?>
        </div>
    </div>

    <!-- Scripts -->
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
            const href = $(this).attr('href');
            if (href && currentPath.includes(href)) {
                $(this).addClass('mm-active');
                $(this).parents('li').addClass('mm-active');
                $(this).closest('.submenu').addClass('mm-show');
            }
        });

        // Mobile menu toggle
        $('.mobile-toggle').on('click', function() {
            $('.sidebar-wrapper').toggleClass('show');
            $('.page-content-wrapper').toggleClass('expanded');
        });

        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() < 992) {
                if (!$(e.target).closest('.sidebar-wrapper').length && 
                    !$(e.target).closest('.mobile-toggle').length) {
                    $('.sidebar-wrapper').removeClass('show');
                    $('.page-content-wrapper').removeClass('expanded');
                }
            }
        });

        // Handle window resize
        $(window).on('resize', function() {
            if ($(window).width() >= 992) {
                $('.sidebar-wrapper').removeClass('show');
                $('.page-content-wrapper').removeClass('expanded');
            }
        });
    });
    </script>
</body>
</html>
<?php
}
?>
