<?php
/**
 * Cybokron Advanced Widget Visibility
 *
 * @package           CybokronAdvancedWidgetVisibility
 * @author            Ercan ATAY
 * @copyright         2025 Ercan ATAY
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Cybokron Advanced Widget Visibility
 * Plugin URI:        https://github.com/ercanatay/cybokron-advanced-widget-visibility
 * Description:       Control widget visibility based on pages, posts, categories with full descendant (grandchildren) support. A Jetpack-free alternative that includes ALL levels of nested pages.
 * Version:           1.8.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Ercan ATAY
 * Author URI:        https://www.ercanatay.com/en/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cybokron-advanced-widget-visibility
 * Domain Path:       /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CYBAWV_VERSION', '1.8.0');
define('CYBAWV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CYBAWV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CYBAWV_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class Cybawv_Plugin {

    /**
     * Single instance of the class
     *
     * @var Cybawv_Plugin
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @since 1.0.0
     * @return Cybawv_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        require_once CYBAWV_PLUGIN_DIR . 'includes/class-visibility-admin.php';
        require_once CYBAWV_PLUGIN_DIR . 'includes/class-visibility-frontend.php';
        require_once CYBAWV_PLUGIN_DIR . 'includes/class-admin-page.php';
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Initialize data handling hooks (must also run for REST widget updates).
        new Cybawv_Visibility_Admin();

        // Initialize frontend
        new Cybawv_Visibility_Frontend();

        // Initialize admin settings page
        if (is_admin()) {
            new Cybawv_Admin_Page();
        }
    }
}

/**
 * Initialize plugin
 *
 * @since 1.0.0
 * @return Cybawv_Plugin
 */
function cybawv_init() {
    return Cybawv_Plugin::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'cybawv_init');

/**
 * Activation hook
 *
 * @since 1.0.0
 */
register_activation_hook(__FILE__, function() {
    // No custom post types or rewrite rules to flush.
});

/**
 * Deactivation hook
 *
 * @since 1.0.0
 */
register_deactivation_hook(__FILE__, function() {
    // No custom post types or rewrite rules to flush.
});
