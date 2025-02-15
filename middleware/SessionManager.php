<?php

class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Basic session security
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            
            // Only set secure cookie if HTTPS is being used
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            session_start();
            
            // Check session timeout only if user is logged in
            if (isset($_SESSION['user_id'])) {
                if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
                    self::destroy();
                    if (!strpos($_SERVER['REQUEST_URI'], 'login.php')) {
                        header("Location: /php-ccs/login.php?timeout=1");
                        exit;
                    }
                }
                $_SESSION['LAST_ACTIVITY'] = time();
            }
        }
    }

    public static function regenerate() {
        session_regenerate_id(true);
    }

    public static function destroy() {
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }

    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function getSessionData($key) {
        return $_SESSION[$key] ?? null;
    }
}
