<?php
/**
 * Plugin Name: HopLink Minimal
 * Plugin URI: https://github.com/yourusername/hoplink-minimal
 * Description: 最小限のアフィリエイトリンク自動生成プラグイン
 * Version: 1.4.0
 * Author: Your Name
 * License: GPL-2.0+
 * Text Domain: hoplink-minimal
 */

// 直接アクセス禁止
if (!defined('ABSPATH')) {
    exit;
}

// 定数定義
define('HOPLINK_VERSION', '1.4.0');
define('HOPLINK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HOPLINK_PLUGIN_URL', plugin_dir_url(__FILE__));

// 設定ファイルの読み込み（存在する場合）
if (file_exists(HOPLINK_PLUGIN_DIR . 'config.php')) {
    require_once HOPLINK_PLUGIN_DIR . 'config.php';
}

// 必要なファイルの読み込み
require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-api.php';
require_once HOPLINK_PLUGIN_DIR . 'includes/class-hoplink-shortcode.php';
require_once HOPLINK_PLUGIN_DIR . 'admin/class-hoplink-admin.php';

// プラグイン初期化
add_action('init', 'hoplink_init');
function hoplink_init() {
    // ショートコード登録
    $shortcode = new HopLink_Shortcode();
    $shortcode->register();
}

// 管理画面の初期化
if (is_admin()) {
    $admin = new HopLink_Admin();
    $admin->init();
}

// アクティベーション時の処理
register_activation_hook(__FILE__, 'hoplink_activate');
function hoplink_activate() {
    // config.phpから定数が定義されている場合は使用、なければ空文字
    add_option('hoplink_rakuten_app_id', defined('HOPLINK_RAKUTEN_APP_ID') ? HOPLINK_RAKUTEN_APP_ID : '');
    add_option('hoplink_rakuten_affiliate_id', defined('HOPLINK_RAKUTEN_AFFILIATE_ID') ? HOPLINK_RAKUTEN_AFFILIATE_ID : '');
    add_option('hoplink_amazon_access_key', defined('HOPLINK_AMAZON_ACCESS_KEY') ? HOPLINK_AMAZON_ACCESS_KEY : '');
    add_option('hoplink_amazon_secret_key', defined('HOPLINK_AMAZON_SECRET_KEY') ? HOPLINK_AMAZON_SECRET_KEY : '');
    add_option('hoplink_amazon_partner_tag', defined('HOPLINK_AMAZON_PARTNER_TAG') ? HOPLINK_AMAZON_PARTNER_TAG : '');
    add_option('hoplink_cache_enabled', true);
    add_option('hoplink_cache_duration', 86400); // 24時間
}

// デアクティベーション時の処理
register_deactivation_hook(__FILE__, 'hoplink_deactivate');
function hoplink_deactivate() {
    // キャッシュクリア
    wp_cache_flush();
}

// CSSの読み込み
add_action('wp_enqueue_scripts', 'hoplink_enqueue_styles');
function hoplink_enqueue_styles() {
    wp_enqueue_style(
        'hoplink-style',
        HOPLINK_PLUGIN_URL . 'public/css/hoplink.css',
        array(),
        HOPLINK_VERSION
    );
}