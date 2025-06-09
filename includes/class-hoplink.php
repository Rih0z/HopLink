<?php
/**
 * Main plugin class
 */
class HopLink {
    
    /**
     * Plugin version
     */
    protected $version;
    
    /**
     * Loader instance
     */
    protected $loader;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = HOPLINK_VERSION;
        $this->loader = new HopLink_Loader();
        
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->init_manual_products();
        $this->init_amazon_features();
        $this->init_cross_platform_features();
    }
    
    /**
     * Set plugin locale for internationalization
     */
    private function set_locale() {
        $plugin_i18n = new HopLink_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    /**
     * Register all admin-side hooks
     */
    private function define_admin_hooks() {
        $plugin_admin = new HopLink_Admin($this->get_version());
        
        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Settings link
        $this->loader->add_filter('plugin_action_links_' . HOPLINK_PLUGIN_BASENAME, $plugin_admin, 'add_action_links');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_hoplink_test_amazon_api', $plugin_admin, 'ajax_test_amazon_api');
        $this->loader->add_action('wp_ajax_hoplink_test_rakuten_api', $plugin_admin, 'ajax_test_rakuten_api');
        $this->loader->add_action('wp_ajax_hoplink_get_cache_info', $plugin_admin, 'ajax_get_cache_info');
    }
    
    /**
     * Register all public-facing hooks
     */
    private function define_public_hooks() {
        $plugin_public = new HopLink_Public($this->get_version());
        
        // Public scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Shortcodes
        add_shortcode('hoplink', [$plugin_public, 'hoplink_shortcode']);
        add_shortcode('hoplink_manual', [$plugin_public, 'hoplink_manual_shortcode']);
    }
    
    /**
     * Run the loader to execute all hooks
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Initialize manual products functionality
     */
    private function init_manual_products() {
        new HopLink_Manual_Products();
    }
    
    /**
     * Initialize Amazon URL features
     */
    private function init_amazon_features() {
        // URLショートコード
        new HopLink_URL_Shortcode();
        
        // 自動変換機能
        new HopLink_Auto_Converter();
        
        // Gutenbergブロック
        new HopLink_Blocks();
        
        // 管理画面Ajax
        if (is_admin()) {
            new HopLink_Admin_Ajax();
        }
    }
    
    /**
     * Initialize cross-platform features
     */
    private function init_cross_platform_features() {
        // クロスプラットフォームショートコード
        new HopLink_Cross_Shortcode();
        
        // 管理画面
        if (is_admin()) {
            new HopLink_Cross_Platform_Admin();
        }
    }
}