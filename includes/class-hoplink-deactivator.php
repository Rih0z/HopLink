<?php
/**
 * Fired during plugin deactivation
 */
class HopLink_Deactivator {
    
    /**
     * Plugin deactivation tasks
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('hoplink_cleanup_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}