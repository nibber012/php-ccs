<?php
if (!function_exists('get_current_page')) {
    function get_current_page() {
        $current_file = basename($_SERVER['PHP_SELF']);
        return str_replace('.php', '', $current_file);
    }
}

if (!function_exists('is_active')) {
    function is_active($page) {
        return get_current_page() === $page ? 'active' : '';
    }
}

if (!function_exists('is_menu_open')) {
    function is_menu_open($menu_items) {
        $current = get_current_page();
        return in_array($current, $menu_items) ? 'show' : '';
    }
}
?>
