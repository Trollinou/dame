<?php
define('WPINC', 'wp-includes');
define('WP_PLUGIN_DIR', '/wp-content/plugins');
define('DAME_PLUGIN_DIR', '/wp-content/plugins/dame/');
define('COOKIEPATH', '/');
define('COOKIE_DOMAIN', '');

// Mock WordPress functions that are not available to PHPStan
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        // Do nothing
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return $text;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return 1;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'nonce';
    }
}

if (!function_exists('wp_nonce_url')) {
    function wp_nonce_url($actionurl, $action = -1, $name = '_wpnonce') {
        return $actionurl;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'https://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('wp_get_referer')) {
    function wp_get_referer() {
        return false;
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302) {
        // Do nothing
    }
}

if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link($post, $context = 'display') {
        return '';
    }
}

if (!function_exists('wp_delete_post')) {
    function wp_delete_post($postid = 0, $force_delete = false) {
        return false;
    }
}

if (!function_exists('wp_trash_post')) {
    function wp_trash_post($postid = 0) {
        return false;
    }
}

if (!function_exists('wp_untrash_post')) {
    function wp_untrash_post($postid = 0) {
        return false;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct($code = '', $message = '', $data = '') {}
        public function get_error_message() {
            return '';
        }
        public function get_error_data($code = '') {
            return array();
        }
    }
}
