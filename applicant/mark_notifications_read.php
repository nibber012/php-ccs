<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user || $user['role'] !== 'applicant') {
    http_response_code(403);
    exit('Unauthorized');
}

$db = Database::getInstance();;
$db->query(
    "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
    [$user['id']]
);

http_response_code(200);
exit('Success');
