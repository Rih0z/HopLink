<?php
/**
 * Define the internationalization functionality
 */
class HopLink_i18n {
    
    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'hoplink',
            false,
            dirname(HOPLINK_PLUGIN_BASENAME) . '/languages/'
        );
    }
}