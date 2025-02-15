<?php
// Base URL Configuration
$base_path = '/php-ccs/';
define('BASE_URL', $base_path);

// Session Configuration - Must be before session_start()
@ini_set('session.cookie_httponly', 1);
@ini_set('session.use_only_cookies', 1);
@ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start the session
session_start();

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time Zone
date_default_timezone_set('Asia/Manila');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ccs_screening');
define('DB_USER', 'root');
define('DB_PASS', '');

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Security Configuration
define('HASH_ALGO', PASSWORD_DEFAULT);
define('MIN_PASSWORD_LENGTH', 8);

// Application Settings
define('APP_NAME', 'CCS Freshman Screening');
define('ITEMS_PER_PAGE', 10);
define('DATE_FORMAT', 'Y-m-d H:i:s');

// Application Status Configuration
define('APP_STATUS', [
    'registered' => 'Registered',
    'screening' => 'Screening',
    'interview_scheduled' => 'Interview Scheduled',
    'interview_completed' => 'Interview Completed',
    'accepted' => 'Accepted',
    'rejected' => 'Rejected'
]);

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-specific-password');
define('MAIL_FROM', 'your-email@gmail.com');
define('MAIL_FROM_NAME', APP_NAME);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
