<?php
/**
 * Autoloader for HopLink plugin classes
 */
class HopLink_Autoloader {
    
    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Autoload HopLink classes
     * 
     * @param string $class The class name
     */
    public static function autoload($class) {
        // Check if this is a HopLink class
        if (strpos($class, 'HopLink') !== 0) {
            return;
        }
        
        // Convert class name to file path
        $class_file = 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';
        
        // Search paths
        $paths = [
            HOPLINK_PLUGIN_PATH . 'includes/',
            HOPLINK_PLUGIN_PATH . 'includes/cross-platform/',
            HOPLINK_PLUGIN_PATH . 'admin/',
            HOPLINK_PLUGIN_PATH . 'public/',
        ];
        
        // Try to load the file
        foreach ($paths as $path) {
            $file = $path . $class_file;
            if (file_exists($file)) {
                require_once $file;
                break;
            }
        }
    }
}