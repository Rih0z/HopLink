<?php
/**
 * Fired during plugin activation
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

class HopLink_Activator {
    
    /**
     * Plugin activation tasks
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create necessary database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Products cache table
        $table_name = $wpdb->prefix . 'hoplink_products_cache';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            platform varchar(50) NOT NULL,
            product_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY cache_key (cache_key),
            KEY platform (platform),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Click tracking table
        $table_name_clicks = $wpdb->prefix . 'hoplink_clicks';
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_name_clicks (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id varchar(255) NOT NULL,
            platform varchar(50) NOT NULL,
            post_id bigint(20) UNSIGNED,
            user_id bigint(20) UNSIGNED,
            clicked_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            PRIMARY KEY  (id),
            KEY product_platform (product_id, platform),
            KEY post_id (post_id),
            KEY clicked_at (clicked_at)
        ) $charset_collate;";
        
        // Manual ASIN products table
        $table_name_manual = $wpdb->prefix . 'hoplink_manual_products';
        $sql3 = "CREATE TABLE IF NOT EXISTS $table_name_manual (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            asin varchar(20) NOT NULL,
            product_name varchar(255) NOT NULL,
            price decimal(10,2),
            image_url text,
            description text,
            features text,
            category varchar(100),
            keywords text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY asin (asin),
            KEY status (status),
            KEY category (category)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        // Amazon PA-API settings
        add_option('hoplink_amazon_access_key', '');
        add_option('hoplink_amazon_secret_key', '');
        add_option('hoplink_amazon_associate_tag', '');
        add_option('hoplink_amazon_region', 'ap-northeast-1');
        add_option('hoplink_amazon_marketplace', 'www.amazon.co.jp');
        
        // API mode settings
        add_option('hoplink_api_mode', 'hybrid');
        add_option('hoplink_api_limit_per_day', 100);
        add_option('hoplink_cache_duration', 604800); // 7 days
        add_option('hoplink_fallback_enabled', true);
        
        // Display settings
        add_option('hoplink_max_products_per_article', 5);
        add_option('hoplink_auto_pr_label', true);
        add_option('hoplink_affiliate_disclosure', true);
        
        // Performance settings
        add_option('hoplink_cache_enabled', true);
        add_option('hoplink_debug_mode', false);
    }
    
    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs() {
        if (!wp_next_scheduled('hoplink_cleanup_cache')) {
            wp_schedule_event(time(), 'daily', 'hoplink_cleanup_cache');
        }
    }
}