<?php
require_once 'classes/Auth.php';

$auth = new Auth();
$auth->logout();

// Redirect to login page
header('Location: login.php');
exit();
?>
