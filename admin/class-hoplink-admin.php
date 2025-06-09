<?php
/**
 * The admin-specific functionality of the plugin
 */
class HopLink_Admin {
    
    /**
     * Plugin version
     */
    private $version;
    
    /**
     * Constructor
     */
    public function __construct($version) {
        $this->version = $version;
    }
    
    /**
     * Register the stylesheets for the admin area
     */
    public function enqueue_styles() {
        wp_enqueue_style('hoplink-admin', HOPLINK_PLUGIN_URL . 'admin/assets/css/hoplink-admin.css', [], $this->version, 'all');
    }
    
    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts() {
        wp_enqueue_script('hoplink-admin', HOPLINK_PLUGIN_URL . 'admin/assets/js/hoplink-admin.js', ['jquery'], $this->version, false);
        
        // Localize script for AJAX
        wp_localize_script('hoplink-admin', 'hoplink_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hoplink_ajax_nonce')
        ]);
    }
    
    /**
     * Register the administration menu
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            'HopLink',
            'HopLink',
            'manage_options',
            'hoplink',
            [$this, 'display_plugin_dashboard'],
            'dashicons-cart',
            30
        );
        
        // Submenu - Dashboard
        add_submenu_page(
            'hoplink',
            'HopLink Dashboard',
            'Dashboard',
            'manage_options',
            'hoplink',
            [$this, 'display_plugin_dashboard']
        );
        
        // Submenu - Settings
        add_submenu_page(
            'hoplink',
            'HopLink Settings',
            'Settings',
            'manage_options',
            'hoplink-settings',
            [$this, 'display_plugin_settings']
        );
        
        // Submenu - API Test
        add_submenu_page(
            'hoplink',
            'API Test',
            'API Test',
            'manage_options',
            'hoplink-api-test',
            [$this, 'display_api_test_page']
        );
        
        // Submenu - Manual Products
        add_submenu_page(
            'hoplink',
            'Manual Products',
            'Manual Products',
            'manage_options',
            'hoplink-manual-products',
            [$this, 'display_manual_products_page']
        );
        
        // Submenu - URL Converter
        add_submenu_page(
            'hoplink',
            'URL変換ツール',
            'URL変換',
            'manage_options',
            'hoplink-url-converter',
            [$this, 'display_url_converter_page']
        );
    }
    
    /**
     * Add settings link on plugin page
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=hoplink-settings') . '">' . __('Settings', 'hoplink') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Display the plugin dashboard
     */
    public function display_plugin_dashboard() {
        include_once HOPLINK_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Display the plugin settings page
     */
    public function display_plugin_settings() {
        // Handle form submission
        if (isset($_POST['hoplink_save_settings']) && check_admin_referer('hoplink_settings_nonce')) {
            $this->save_settings();
        }
        
        include_once HOPLINK_PLUGIN_PATH . 'admin/views/settings.php';
    }
    
    /**
     * Display the API test page
     */
    public function display_api_test_page() {
        include_once HOPLINK_PLUGIN_PATH . 'admin/views/api-test.php';
    }
    
    /**
     * Display the manual products page
     */
    public function display_manual_products_page() {
        include_once HOPLINK_PLUGIN_PATH . 'admin/views/manual-products.php';
    }
    
    /**
     * Display the URL converter page
     */
    public function display_url_converter_page() {
        include_once HOPLINK_PLUGIN_PATH . 'admin/views/url-converter.php';
    }
    
    /**
     * Save plugin settings
     */
    private function save_settings() {
        // Amazon API settings
        update_option('hoplink_amazon_access_key', sanitize_text_field($_POST['hoplink_amazon_access_key'] ?? ''));
        update_option('hoplink_amazon_secret_key', sanitize_text_field($_POST['hoplink_amazon_secret_key'] ?? ''));
        update_option('hoplink_amazon_associate_tag', sanitize_text_field($_POST['hoplink_amazon_associate_tag'] ?? ''));
        update_option('hoplink_amazon_mode', sanitize_text_field($_POST['hoplink_amazon_mode'] ?? 'api'));
        
        // Rakuten API settings
        update_option('hoplink_rakuten_application_id', sanitize_text_field($_POST['hoplink_rakuten_application_id'] ?? ''));
        update_option('hoplink_rakuten_affiliate_id', sanitize_text_field($_POST['hoplink_rakuten_affiliate_id'] ?? ''));
        
        // API mode settings
        update_option('hoplink_api_mode', sanitize_text_field($_POST['hoplink_api_mode'] ?? 'hybrid'));
        update_option('hoplink_api_limit_per_day', intval($_POST['hoplink_api_limit_per_day'] ?? 100));
        update_option('hoplink_cache_duration', intval($_POST['hoplink_cache_duration'] ?? 604800));
        update_option('hoplink_fallback_enabled', isset($_POST['hoplink_fallback_enabled']));
        
        // Auto conversion settings
        update_option('hoplink_auto_convert_enabled', isset($_POST['hoplink_auto_convert_enabled']));
        update_option('hoplink_preserve_existing_links', isset($_POST['hoplink_preserve_existing_links']));
        
        // Display settings
        update_option('hoplink_max_products_per_article', intval($_POST['hoplink_max_products_per_article'] ?? 5));
        update_option('hoplink_auto_pr_label', isset($_POST['hoplink_auto_pr_label']));
        update_option('hoplink_affiliate_disclosure', isset($_POST['hoplink_affiliate_disclosure']));
        
        // Performance settings
        update_option('hoplink_cache_enabled', isset($_POST['hoplink_cache_enabled']));
        update_option('hoplink_debug_mode', isset($_POST['hoplink_debug_mode']));
        
        // Show success message
        add_settings_error('hoplink_messages', 'hoplink_message', __('Settings saved successfully!', 'hoplink'), 'updated');
    }
    
    /**
     * AJAX handler for Amazon API test
     */
    public function ajax_test_amazon_api() {
        // Check nonce
        if (!check_ajax_referer('hoplink_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        // Get test parameters
        $test_type = sanitize_text_field($_POST['test_type'] ?? 'connection');
        $keyword = sanitize_text_field($_POST['keyword'] ?? 'beer');
        
        // Initialize Amazon API
        $amazon_api = new HopLink_Amazon_API();
        
        $response = [];
        
        switch ($test_type) {
            case 'connection':
                $response = $amazon_api->test_connection();
                break;
                
            case 'search':
                $start_time = microtime(true);
                $results = $amazon_api->search_items($keyword, ['item_count' => 5]);
                $end_time = microtime(true);
                
                if (is_wp_error($results)) {
                    $response = [
                        'success' => false,
                        'message' => $results->get_error_message(),
                        'error_data' => $results->get_error_data(),
                        'execution_time' => round(($end_time - $start_time) * 1000, 2) . 'ms'
                    ];
                } else {
                    $response = [
                        'success' => true,
                        'message' => sprintf('Found %d products for "%s"', count($results), $keyword),
                        'execution_time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
                        'results' => $results
                    ];
                }
                break;
                
            case 'cache_clear':
                global $wpdb;
                $table_name = $wpdb->prefix . 'hoplink_products_cache';
                $deleted = $wpdb->query("DELETE FROM $table_name");
                
                $response = [
                    'success' => true,
                    'message' => sprintf('Cache cleared successfully. %d entries removed.', $deleted)
                ];
                break;
        }
        
        wp_send_json($response);
    }
    
    /**
     * AJAX handler for Rakuten API test
     */
    public function ajax_test_rakuten_api() {
        // Check nonce
        if (!check_ajax_referer('hoplink_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        // Get test parameters
        $test_type = sanitize_text_field($_POST['test_type'] ?? 'connection');
        $keyword = sanitize_text_field($_POST['keyword'] ?? 'ビール');
        
        // Initialize Rakuten API
        $rakuten_api = new HopLink_Rakuten_API();
        
        $response = [];
        
        switch ($test_type) {
            case 'connection':
                $response = $rakuten_api->test_connection();
                break;
                
            case 'search':
                $start_time = microtime(true);
                $results = $rakuten_api->search_items($keyword, ['hits' => 5]);
                $end_time = microtime(true);
                
                if (is_wp_error($results)) {
                    $response = [
                        'success' => false,
                        'message' => $results->get_error_message(),
                        'error_data' => $results->get_error_data(),
                        'execution_time' => round(($end_time - $start_time) * 1000, 2) . 'ms'
                    ];
                } else {
                    $response = [
                        'success' => true,
                        'message' => sprintf('%d 件の商品が見つかりました: "%s"', count($results), $keyword),
                        'execution_time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
                        'results' => $results
                    ];
                }
                break;
        }
        
        wp_send_json($response);
    }
    
    /**
     * AJAX handler for cache info
     */
    public function ajax_get_cache_info() {
        // Check nonce
        if (!check_ajax_referer('hoplink_ajax_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'hoplink_products_cache';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            wp_send_json([
                'success' => false,
                'message' => 'Cache table does not exist'
            ]);
            return;
        }
        
        $cache_info = [
            'amazon' => ['cached' => false],
            'rakuten' => ['cached' => false]
        ];
        
        // Check Amazon cache
        $amazon_cache = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE platform = 'amazon' AND search_keyword = %s AND created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)",
            $keyword,
            get_option('hoplink_cache_duration', 604800)
        ));
        
        if ($amazon_cache) {
            $cache_info['amazon'] = [
                'cached' => true,
                'created' => $amazon_cache->created_at,
                'expires' => date('Y-m-d H:i:s', strtotime($amazon_cache->created_at) + get_option('hoplink_cache_duration', 604800))
            ];
        }
        
        // Check Rakuten cache
        $rakuten_cache = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE platform = 'rakuten' AND search_keyword = %s AND created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)",
            $keyword,
            get_option('hoplink_cache_duration', 604800)
        ));
        
        if ($rakuten_cache) {
            $cache_info['rakuten'] = [
                'cached' => true,
                'created' => $rakuten_cache->created_at,
                'expires' => date('Y-m-d H:i:s', strtotime($rakuten_cache->created_at) + get_option('hoplink_cache_duration', 604800))
            ];
        }
        
        wp_send_json([
            'success' => true,
            'cache_info' => $cache_info
        ]);
    }
}