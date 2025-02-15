<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';

$auth = new Auth();
$auth->logout();

// Redirect to login page
header('Location: ' . BASE_URL . 'login.php');
exit;
