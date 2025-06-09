<?php
/**
 * Admin settings view
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Show any messages
settings_errors('hoplink_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('hoplink_settings_nonce'); ?>
        
        <h2 class="nav-tab-wrapper">
            <a href="#amazon-api" class="nav-tab nav-tab-active" data-tab="amazon-api">Amazon PA-API</a>
            <a href="#rakuten-api" class="nav-tab" data-tab="rakuten-api">楽天API</a>
            <a href="#api-mode" class="nav-tab" data-tab="api-mode">API Mode</a>
            <a href="#display" class="nav-tab" data-tab="display">Display</a>
            <a href="#url-conversion" class="nav-tab" data-tab="url-conversion">URL変換</a>
            <a href="#performance" class="nav-tab" data-tab="performance">Performance</a>
        </h2>
        
        <!-- Amazon PA-API Settings -->
        <div id="amazon-api" class="tab-content">
            <h2>Amazon PA-API Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hoplink_amazon_access_key">Access Key ID</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="hoplink_amazon_access_key" 
                               name="hoplink_amazon_access_key" 
                               value="<?php echo esc_attr(get_option('hoplink_amazon_access_key')); ?>" 
                               class="regular-text" />
                        <p class="description">Your Amazon PA-API Access Key (20 characters)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hoplink_amazon_secret_key">Secret Access Key</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="hoplink_amazon_secret_key" 
                               name="hoplink_amazon_secret_key" 
                               value="<?php echo esc_attr(get_option('hoplink_amazon_secret_key')); ?>" 
                               class="regular-text" />
                        <p class="description">Your Amazon PA-API Secret Key (40 characters)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hoplink_amazon_associate_tag">Associate Tag</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="hoplink_amazon_associate_tag" 
                               name="hoplink_amazon_associate_tag" 
                               value="<?php echo esc_attr(get_option('hoplink_amazon_associate_tag')); ?>" 
                               class="regular-text" />
                        <p class="description">Your Amazon Associate tag (e.g., yourname-22)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Region</th>
                    <td>
                        <p><strong>Japan (ap-northeast-1)</strong></p>
                        <p class="description">Fixed for Amazon Japan marketplace</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Amazon Mode</th>
                    <td>
                        <select id="hoplink_amazon_mode" name="hoplink_amazon_mode">
                            <option value="api" <?php selected(get_option('hoplink_amazon_mode', 'api'), 'api'); ?>>PA-API (Automatic)</option>
                            <option value="manual" <?php selected(get_option('hoplink_amazon_mode'), 'manual'); ?>>Manual (No API)</option>
                            <option value="hybrid" <?php selected(get_option('hoplink_amazon_mode'), 'hybrid'); ?>>Hybrid (API + Manual)</option>
                        </select>
                        <p class="description">Choose how to manage Amazon products</p>
                        <div id="amazon-mode-info" style="margin-top: 10px; padding: 10px; background: #f1f1f1; display: none;">
                            <p><strong>PA-API Mode:</strong> Automatically fetch product data using Amazon PA-API</p>
                            <p><strong>Manual Mode:</strong> Manage products manually without API (no API key required)</p>
                            <p><strong>Hybrid Mode:</strong> Use both API and manual products, prioritizing manual entries</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Rakuten API Settings -->
        <div id="rakuten-api" class="tab-content" style="display:none;">
            <h2>楽天API設定</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hoplink_rakuten_application_id">アプリケーションID</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="hoplink_rakuten_application_id" 
                               name="hoplink_rakuten_application_id" 
                               value="<?php echo esc_attr(get_option('hoplink_rakuten_application_id')); ?>" 
                               class="regular-text" />
                        <p class="description">楽天ウェブサービスのアプリケーションID</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hoplink_rakuten_affiliate_id">アフィリエイトID</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="hoplink_rakuten_affiliate_id" 
                               name="hoplink_rakuten_affiliate_id" 
                               value="<?php echo esc_attr(get_option('hoplink_rakuten_affiliate_id')); ?>" 
                               class="regular-text" />
                        <p class="description">楽天アフィリエイトID（任意）</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API情報</th>
                    <td>
                        <p><strong>エンドポイント:</strong> app.rakuten.co.jp</p>
                        <p class="description">楽天市場商品検索API（Ichiba Item Search API）を使用</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- API Mode Settings -->
        <div id="api-mode" class="tab-content" style="display:none;">
            <h2>API Mode Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hoplink_api_mode">API Mode</label>
                    </th>
                    <td>
                        <select id="hoplink_api_mode" name="hoplink_api_mode">
                            <option value="full_api" <?php selected(get_option('hoplink_api_mode'), 'full_api'); ?>>Full API</option>
                            <option value="hybrid" <?php selected(get_option('hoplink_api_mode'), 'hybrid'); ?>>Hybrid (Recommended)</option>
                            <option value="manual" <?php selected(get_option('hoplink_api_mode'), 'manual'); ?>>Manual Only</option>
                        </select>
                        <p class="description">Hybrid mode minimizes API calls by using cached data</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hoplink_api_limit_per_day">Daily API Limit</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="hoplink_api_limit_per_day" 
                               name="hoplink_api_limit_per_day" 
                               value="<?php echo esc_attr(get_option('hoplink_api_limit_per_day', 100)); ?>" 
                               min="1" 
                               max="10000" />
                        <p class="description">Maximum API calls per day (for hybrid mode)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="hoplink_cache_duration">Cache Duration</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="hoplink_cache_duration" 
                               name="hoplink_cache_duration" 
                               value="<?php echo esc_attr(get_option('hoplink_cache_duration', 604800)); ?>" 
                               min="3600" /> seconds
                        <p class="description">How long to cache product data (default: 7 days = 604800 seconds)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Fallback Mode</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="hoplink_fallback_enabled" 
                                   value="1" 
                                   <?php checked(get_option('hoplink_fallback_enabled', true)); ?> />
                            Enable fallback to manual products when API fails
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Display Settings -->
        <div id="display" class="tab-content" style="display:none;">
            <h2>Display Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hoplink_max_products_per_article">Max Products per Article</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="hoplink_max_products_per_article" 
                               name="hoplink_max_products_per_article" 
                               value="<?php echo esc_attr(get_option('hoplink_max_products_per_article', 5)); ?>" 
                               min="1" 
                               max="20" />
                        <p class="description">Maximum number of products to display per article</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Compliance</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="hoplink_auto_pr_label" 
                                   value="1" 
                                   <?php checked(get_option('hoplink_auto_pr_label', true)); ?> />
                            Automatically add PR labels
                        </label>
                        <br><br>
                        <label>
                            <input type="checkbox" 
                                   name="hoplink_affiliate_disclosure" 
                                   value="1" 
                                   <?php checked(get_option('hoplink_affiliate_disclosure', true)); ?> />
                            Show affiliate disclosure
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- URL Conversion Settings -->
        <div id="url-conversion" class="tab-content" style="display:none;">
            <h2>URL変換設定</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">自動変換</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="hoplink_auto_convert_enabled" 
                                   value="1" 
                                   <?php checked(get_option('hoplink_auto_convert_enabled', true)); ?> />
                            記事保存時にAmazon URLを自動でアフィリエイトリンクに変換する
                        </label>
                        <p class="description">
                            記事の保存時に、Amazon商品URLを自動的にアフィリエイトリンクに変換します。
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">既存リンクの処理</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="hoplink_preserve_existing_links" 
                                   value="1" 
                                   <?php checked(get_option('hoplink_preserve_existing_links', true)); ?> />
                            既存のアフィリエイトリンクを保持する
                        </label>
                        <p class="description">
                            すでにアフィリエイトタグが付いているリンクは変更しません。
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">対応URLパターン</th>
                    <td>
                        <p><strong>自動変換される Amazon URL パターン:</strong></p>
                        <ul style="list-style-type: disc; margin-left: 20px;">
                            <li>https://www.amazon.co.jp/dp/ASIN</li>
                            <li>https://www.amazon.co.jp/gp/product/ASIN</li>
                            <li>https://www.amazon.co.jp/商品名/dp/ASIN</li>
                            <li>https://amzn.to/xxxxx (短縮URL)</li>
                            <li>https://amzn.asia/d/ASIN</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <th scope="row">一括変換ツール</th>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=hoplink-url-converter'); ?>" class="button">
                            URL変換ツールを開く
                        </a>
                        <p class="description">
                            既存の記事内のAmazon URLを一括で変換できます。
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Performance Settings -->
        <div id="performance" class="tab-content" style="display:none;">
            <h2>Performance Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Cache</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="hoplink_cache_enabled" 
                                   value="1" 
                                   <?php checked(get_option('hoplink_cache_enabled', true)); ?> />
                            Enable caching (recommended)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Debug Mode</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="hoplink_debug_mode" 
                                   value="1" 
                                   <?php checked(get_option('hoplink_debug_mode', false)); ?> />
                            Enable debug logging
                        </label>
                        <p class="description">Logs will be saved to wp-content/debug.log</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="hoplink_save_settings" class="button-primary" value="Save Settings" />
        </p>
    </form>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.form-table th {
    width: 200px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        
        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding content
        $('.tab-content').hide();
        $('#' + tab).show();
    });
    
    // Amazon mode info toggle
    $('#hoplink_amazon_mode').on('change', function() {
        $('#amazon-mode-info').slideDown();
    });
});
</script>