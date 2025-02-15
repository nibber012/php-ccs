<?php
function get_header($title = '') {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/Auth.php';
    
    $auth = new Auth();
    $user = $auth->getCurrentUser();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title ? $title . ' - ' : ''; ?>CCS Screening</title>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
        
        <style>
            body {
                min-height: 100vh;
                background-color: #f8f9fa;
            }
            .content {
                margin-left: 0;
                transition: margin-left 0.3s ease-in-out;
            }
            @media (min-width: 768px) {
                .content {
                    margin-left: 16.666667%;
                }
            }
            .navbar {
                transition: margin-left 0.3s ease-in-out;
            }
            @media (min-width: 768px) {
                .navbar {
                    margin-left: 16.666667%;
                }
            }
        </style>
    </head>
    <body>
        <!-- Fixed Navbar -->
        <nav class="navbar navbar-expand-md navbar-dark bg-primary fixed-top">
            <div class="container-fluid">
                <span class="navbar-brand"><?php echo $title; ?></span>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="text-white text-decoration-none dropdown-toggle" id="navbarDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell me-2"></i>
                            <span class="badge bg-danger">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#">No new notifications</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="d-flex">
    <?php
}

function get_footer() {
    ?>
        </div>
        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

function get_sidebar($type = 'applicant') {
    if ($type === 'applicant') {
        require_once __DIR__ . '/../applicant/includes/sidebar.php';
    } else {
        require_once __DIR__ . '/../admin/includes/sidebar.php';
    }
}
?>
