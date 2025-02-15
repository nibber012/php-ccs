<?php
/**
 * Common utility functions used across the application
 */

if (!function_exists('getScoreBadgeClass')) {
    function getScoreBadgeClass($score) {
        if ($score >= 90) return 'badge-excellent';
        if ($score >= 80) return 'badge-good';
        if ($score >= 70) return 'badge-average';
        return 'badge-poor';
    }
}

if (!function_exists('safe_string')) {
    function safe_string($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('safe_ucwords')) {
    function safe_ucwords($str) {
        return ucwords(str_replace('_', ' ', $str ?? ''));
    }
}
?>
