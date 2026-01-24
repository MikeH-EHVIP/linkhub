<?php
/**
 * Plugin Name: LinkHub
 * Plugin URI: https://github.com/elysevipatd/linkhub
 * Description: Create beautiful link-in-bio pages with CPT-based link management and click tracking
 * Version: 0.1.0
 * Author: ElyseVIP
 * Author URI: https://elysevipatd.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: linkhub
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

namespace ElyseVIP\LinkHub;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('LH_VERSION', '0.1.0');
define('LH_PLUGIN_FILE', __FILE__);
define('LH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LH_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require Composer autoloader or fallback
if (file_exists(LH_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once LH_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    require_once LH_PLUGIN_DIR . 'autoload.php';
}

/**
 * Main Plugin Class
 */
class Plugin {
    
    /**
     * Singleton instance
     *
     * @var Plugin
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init'], 0);
        
        // Activation/Deactivation hooks
        register_activation_hook(LH_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(LH_PLUGIN_FILE, [$this, 'deactivate']);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'linkhub',
            false,
            dirname(LH_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Register Custom Post Types
        PostTypes\TreePostType::register();
        PostTypes\LinkPostType::register();
        
        // Initialize redirect handler
        Tracking\RedirectHandler::instance();
        
        // Initialize link type renderer with block filters
        Rendering\LinkTypeRenderer::init();

        // Initialize tree renderer for frontend display
        Rendering\TreeRenderer::init();
        
        // Initialize admin components
        if (is_admin()) {
            Admin\MetaBoxes::instance();
            Admin\ClickwhaleImporter::instance();
            Admin\ExportImport::instance();
        }
        
        do_action('LH_init');
    }
    
    /**
     * Custom logging function
     */
    private function log($message) {
        $log_file = LH_PLUGIN_DIR . 'dtol-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Register post types
        PostTypes\TreePostType::register();
        PostTypes\LinkPostType::register();
        
        // Add redirect rewrite rules
        Tracking\RedirectHandler::add_rewrite_rules();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action('LH_activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action('LH_deactivated');
    }
}

// Initialize the plugin
Plugin::instance();
