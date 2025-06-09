<?php
/**
 * Plugin Name: HopLink
 * Plugin URI: https://github.com/yourusername/hoplink
 * Description: クラフトビール記事から自動的に最適なアフィリエイトリンクを生成するWordPressプラグイン
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hoplink
 * Domain Path: /languages
 */

// セキュリティ：直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグイン定数の定義
define('HOPLINK_VERSION', '1.0.0');
define('HOPLINK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HOPLINK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HOPLINK_PLUGIN_BASENAME', plugin_basename(__FILE__));

// オートローダーの設定
require_once HOPLINK_PLUGIN_PATH . 'includes/class-hoplink-autoloader.php';
HopLink_Autoloader::register();

// メインプラグインクラスのインスタンス化
function hoplink_init() {
    $plugin = new HopLink();
    $plugin->run();
}
add_action('plugins_loaded', 'hoplink_init');

// アクティベーション・ディアクティベーションフック
register_activation_hook(__FILE__, ['HopLink_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['HopLink_Deactivator', 'deactivate']);