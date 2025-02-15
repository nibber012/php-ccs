<?php

class Logger {
    private static $logFile = __DIR__ . '/../logs/error.log';
    private static $maxLogSize = 5242880; // 5MB

    public static function log($message, $level = 'ERROR') {
        if (!file_exists(dirname(self::$logFile))) {
            mkdir(dirname(self::$logFile), 0777, true);
        }

        // Rotate log if too large
        if (file_exists(self::$logFile) && filesize(self::$logFile) > self::$maxLogSize) {
            self::rotateLog();
        }

        $timestamp = date('Y-m-d H:i:s');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0];
        $file = basename($backtrace['file']);
        $line = $backtrace['line'];
        
        $logMessage = sprintf(
            "[%s] [%s] [%s:%d] %s%s",
            $timestamp,
            $level,
            $file,
            $line,
            $message,
            PHP_EOL
        );
        
        error_log($logMessage, 3, self::$logFile);
    }

    private static function rotateLog() {
        $backup = self::$logFile . '.' . date('Y-m-d-H-i-s');
        rename(self::$logFile, $backup);
        
        // Keep only last 5 backups
        $backups = glob(self::$logFile . '.*');
        if (count($backups) > 5) {
            usort($backups, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            unlink($backups[0]);
        }
    }

    public static function error($message) {
        self::log($message, 'ERROR');
    }

    public static function info($message) {
        self::log($message, 'INFO');
    }
}
