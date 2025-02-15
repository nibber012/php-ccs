<?php
require_once __DIR__ . '/../../config/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
$user = isset($auth) ? $auth->getCurrentUser() : null;

// Redirect if not logged in or not an admin
if (!$user || $user['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}
?>

<div class="sidebar-wrapper">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="logo">
            <i class='bx bx-code-alt'></i>
            <span class="logo-text">CCS Admin</span>
        </a>
    </div>
    
    <div class="sidebar-scroll">
        <ul class="metismenu" id="side-menu">
            <!-- Dashboard -->
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="menu-link">
                    <i class='bx bx-home-circle'></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Applicants Section -->
            <li class="menu-item">
                <a href="#" class="menu-link has-arrow">
                    <i class='bx bx-user'></i>
                    <span>Applicants</span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/manage_applicants.php">
                            <i class='bx bx-list-check'></i> Manage Applicants
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/applicant_status.php">
                            <i class='bx bx-stats'></i> Application Status
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Exams Section -->
            <li class="menu-item">
                <a href="#" class="menu-link has-arrow">
                    <i class='bx bx-book'></i>
                    <span>Exams</span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/list_exams.php">
                            <i class='bx bx-list-ul'></i> List Exams
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/exam_results.php">
                            <i class='bx bx-spreadsheet'></i> Exam Results
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Interviews Section -->
            <li class="menu-item">
                <a href="#" class="menu-link has-arrow">
                    <i class='bx bx-calendar'></i>
                    <span>Interviews</span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/interview_list.php">
                            <i class='bx bx-list-ul'></i> Interview List
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/schedule_interview.php">
                            <i class='bx bx-calendar-plus'></i> Schedule Interview
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/interview_results.php">
                            <i class='bx bx-spreadsheet'></i> Interview Results
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Settings -->
            <li class="menu-item">
                <a href="#" class="menu-link has-arrow">
                    <i class='bx bx-cog'></i>
                    <span>Settings</span>
                </a>
                <ul class="submenu">
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/profile_settings.php">
                            <i class='bx bx-user-circle'></i> Profile Settings
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Logout -->
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>admin/logout.php" class="menu-link">
                    <i class='bx bx-log-out'></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
/* Sidebar Core Styles */
.sidebar-wrapper {
    width: 260px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 999;
    background: #2b3a4a;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

/* Sidebar Header */
.sidebar-header {
    height: 70px;
    padding: 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
    color: #fff;
    text-decoration: none;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo i {
    font-size: 1.5rem;
}

/* Sidebar Scroll Area */
.sidebar-scroll {
    height: calc(100vh - 70px);
    overflow-y: auto;
    overflow-x: hidden;
    padding: 15px 0;
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

/* Mobile Responsive */
@media (max-width: 991.98px) {
    .sidebar-wrapper {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar-wrapper.show {
        transform: translateX(0);
    }

    .page-content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }
}

/* Scrollbar Styles */
.sidebar-scroll::-webkit-scrollbar {
    width: 5px;
}

.sidebar-scroll::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>
