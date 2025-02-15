<?php
session_start();
echo "<pre>";
echo "Session Data:\n";
print_r($_SESSION);
echo "\nCurrent URL: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTP_REFERER: " . ($_SERVER['HTTP_REFERER'] ?? 'None') . "\n";
echo "</pre>";
