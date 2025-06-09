<?php
/**
 * Admin dashboard view
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="hoplink-dashboard">
        <div class="hoplink-welcome">
            <h2>Welcome to HopLink</h2>
            <p>Craft beer affiliate link automation for WordPress.</p>
        </div>
        
        <div class="hoplink-cards">
            <div class="hoplink-card">
                <h3>Quick Stats</h3>
                <?php
                global $wpdb;
                $cache_table = $wpdb->prefix . 'hoplink_products_cache';
                $clicks_table = $wpdb->prefix . 'hoplink_clicks';
                
                $cache_count = $wpdb->get_var("SELECT COUNT(*) FROM $cache_table");
                $clicks_today = $wpdb->get_var("SELECT COUNT(*) FROM $clicks_table WHERE DATE(clicked_at) = CURDATE()");
                $active_products = $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM $clicks_table");
                ?>
                <ul>
                    <li>Cached Products: <strong><?php echo intval($cache_count); ?></strong></li>
                    <li>Clicks Today: <strong><?php echo intval($clicks_today); ?></strong></li>
                    <li>Active Products: <strong><?php echo intval($active_products); ?></strong></li>
                </ul>
            </div>
            
            <div class="hoplink-card">
                <h3>API Status</h3>
                <?php
                $amazon_key = get_option('hoplink_amazon_access_key');
                $amazon_configured = !empty($amazon_key);
                ?>
                <ul>
                    <li>Amazon PA-API: 
                        <?php if ($amazon_configured): ?>
                            <span style="color: green;">✓ Configured</span>
                        <?php else: ?>
                            <span style="color: red;">✗ Not configured</span>
                        <?php endif; ?>
                    </li>
                    <li>API Mode: <strong><?php echo esc_html(get_option('hoplink_api_mode', 'hybrid')); ?></strong></li>
                    <li>Cache: 
                        <?php if (get_option('hoplink_cache_enabled', true)): ?>
                            <span style="color: green;">✓ Enabled</span>
                        <?php else: ?>
                            <span style="color: orange;">✗ Disabled</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
            
            <div class="hoplink-card">
                <h3>Quick Actions</h3>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=hoplink-settings'); ?>" class="button button-primary">Configure Settings</a>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=hoplink-api-test'); ?>" class="button">Test API Connection</a>
                </p>
            </div>
        </div>
        
        <div class="hoplink-info">
            <h3>Getting Started</h3>
            <ol>
                <li>Configure your Amazon PA-API credentials in the <a href="<?php echo admin_url('admin.php?page=hoplink-settings'); ?>">Settings</a> page</li>
                <li>Test your API connection using the <a href="<?php echo admin_url('admin.php?page=hoplink-api-test'); ?>">API Test</a> tool</li>
                <li>Use the <code>[hoplink keyword="beer"]</code> shortcode in your posts</li>
                <li>Monitor performance and clicks from this dashboard</li>
            </ol>
        </div>
    </div>
</div>

<style>
.hoplink-dashboard {
    margin-top: 20px;
}

.hoplink-welcome {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
}

.hoplink-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.hoplink-card {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.hoplink-card h3 {
    margin-top: 0;
}

.hoplink-card ul {
    list-style: none;
    padding: 0;
}

.hoplink-card li {
    padding: 5px 0;
}

.hoplink-info {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.hoplink-info h3 {
    margin-top: 0;
}
</style>