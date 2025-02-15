<?php
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../includes/utilities.php';

global $auth;
if (!isset($auth)) {
    $auth = new Auth();
}
$user = $auth->getCurrentUser();

// Function to check if a menu item is active
function is_active($page_name) {
    return strpos($_SERVER['SCRIPT_NAME'], $page_name) !== false ? 'active' : '';
}
?>

<!-- Sidebar -->
<nav class="sidebar bg-primary">
    <div class="d-flex flex-column">
        <a href="/php-ccs/admin/super/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-mortarboard-fill me-2"></i>
            <span class="fs-5">CCS Admin</span>
        </a>
        <hr class="text-white my-3">
        
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('dashboard'); ?>" href="/php-ccs/admin/super/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <!-- Applicants Section -->
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('applicants'); ?>" href="/php-ccs/admin/super/applicants_overview.php">
                    <i class="bi bi-people me-2"></i>
                    Applicants Overview
                </a>
            </li>
            
            <!-- Exam Management -->
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('manage_questions'); ?>" href="/php-ccs/admin/super/manage_questions.php">
                    <i class="bi bi-question-circle me-2"></i>
                    Exam Questions
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('exam_results'); ?>" href="/php-ccs/admin/super/exam_results.php">
                    <i class="bi bi-card-checklist me-2"></i>
                    Exam Results
                </a>
            </li>
            
            <!-- Interview Management -->
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('interview_list'); ?>" href="/php-ccs/admin/super/interview_list.php">
                    <i class="bi bi-calendar-event me-2"></i>
                    Interviews
                </a>
            </li>
            
            <!-- Analytics & Reports -->
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('analytics'); ?>" href="/php-ccs/admin/super/analytics.php">
                    <i class="bi bi-graph-up me-2"></i>
                    Analytics
                </a>
            </li>
            
            <!-- Admin Management -->
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('list_admins'); ?>" href="/php-ccs/admin/super/list_admins.php">
                    <i class="bi bi-person-badge me-2"></i>
                    Admins
                </a>
            </li>
            
            <!-- Settings -->
            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('system_settings'); ?>" href="/php-ccs/admin/super/system_settings.php">
                    <i class="bi bi-gear me-2"></i>
                    Settings
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('logs'); ?>" href="/php-ccs/admin/super/system_logs.php">
                    <i class="bi bi-journal-text me-2"></i>
                    System Logs
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white <?php echo is_active('backup'); ?>" href="/php-ccs/admin/super/backup.php">
                    <i class="bi bi-cloud-download me-2"></i>
                    Backup & Restore
                </a>
            </li>
        </ul>
        
        <hr class="text-white my-3">
        <div class="dropdown pb-3">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>
                <strong><?php echo htmlspecialchars($user['email']); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser">
                <li><a class="dropdown-item" href="/php-ccs/admin/super/profile_settings.php">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/php-ccs/logout.php">Sign out</a></li>
            </ul>
        </div>
    </div>
</nav>

<style>
.sidebar {
    width: 240px;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    padding: 1rem;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.sidebar .nav-link {
    padding: 0.5rem 1rem;
    margin: 0.2rem 0;
    border-radius: 0.25rem;
    font-weight: 500;
    transition: background-color 0.2s;
    position: relative;
    overflow: hidden;
}

.sidebar .nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: rgba(255,255,255,0.5);
    transition: width 0.3s ease;
}

.sidebar .nav-link:hover::after {
    width: 100%;
}

.sidebar .nav-link.active::after {
    width: 100%;
    background: rgba(255,255,255,0.8);
}

.sidebar .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-link.active {
    background: rgba(255, 255, 255, 0.2);
}

@media (max-width: 767.98px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    .sidebar.show {
        transform: translateX(0);
    }
}

.dropdown-menu {
    margin-top: 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add mobile toggle button
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'btn btn-link text-white d-md-none position-fixed';
    toggleBtn.style.top = '1rem';
    toggleBtn.style.right = '1rem';
    toggleBtn.style.zIndex = '1000';
    toggleBtn.innerHTML = '<i class="bi bi-list fs-4"></i>';
    document.body.appendChild(toggleBtn);

    // Toggle sidebar on mobile
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 768) {
            const isClickInside = sidebar.contains(event.target) || toggleBtn.contains(event.target);
            if (!isClickInside && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Add active state management
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.sidebar .nav-link');
    
    menuItems.forEach(item => {
        if (currentPath.includes(item.getAttribute('href'))) {
            item.classList.add('active');
            // Expand parent collapse if exists
            const parentCollapse = item.closest('.collapse');
            if (parentCollapse) {
                parentCollapse.classList.add('show');
                const trigger = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                if (trigger) trigger.classList.remove('collapsed');
            }
        }
    });

    // Add smooth transitions
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        contentWrapper.style.transition = 'margin-left 0.3s ease';
    }

    // Handle sidebar toggle smoothly
    toggleBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('show');
        document.body.classList.toggle('sidebar-open');
    });
});
</script>
