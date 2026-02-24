<?php
/**
 * Admin settings page for Cybokron Advanced Widget Visibility
 *
 * @package CybokronAdvancedWidgetVisibility
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Page Class
 *
 * @since 1.7.0
 */
class Cybawv_Admin_Page {

    /**
     * Option name for plugin settings.
     *
     * @var string
     */
    private $option_name = 'cybawv_settings';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add top-level admin menu page with dashicon.
     */
    public function add_menu_page() {
        add_menu_page(
            __('Widget Visibility', 'cybokron-advanced-widget-visibility'),
            __('Widget Visibility', 'cybokron-advanced-widget-visibility'),
            'edit_theme_options',
            'cybawv-settings',
            [$this, 'render_page'],
            'dashicons-visibility',
            59
        );
    }

    /**
     * Register settings using the Settings API.
     */
    public function register_settings() {
        register_setting(
            'cybawv_settings_group',
            $this->option_name,
            ['sanitize_callback' => [$this, 'sanitize_settings']]
        );

        add_settings_section(
            'cybawv_main_section',
            __('Settings', 'cybokron-advanced-widget-visibility'),
            '__return_false',
            'cybawv-settings'
        );

        add_settings_field(
            'cybawv_global_bypass',
            __('Global Bypass', 'cybokron-advanced-widget-visibility'),
            [$this, 'render_global_bypass_field'],
            'cybawv-settings',
            'cybawv_main_section'
        );

        add_settings_field(
            'cybawv_max_rules',
            __('Maximum Rules Per Widget', 'cybokron-advanced-widget-visibility'),
            [$this, 'render_max_rules_field'],
            'cybawv-settings',
            'cybawv_main_section'
        );

        add_settings_field(
            'cybawv_delete_data',
            __('Uninstall', 'cybokron-advanced-widget-visibility'),
            [$this, 'render_delete_data_field'],
            'cybawv-settings',
            'cybawv_main_section'
        );
    }

    /**
     * Sanitize settings before saving.
     *
     * @param mixed $input Raw input from the form.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($input) {
        if (!is_array($input)) {
            $input = [];
        }

        $sanitized = [];

        $sanitized['global_bypass'] = !empty($input['global_bypass']);

        $max_rules = isset($input['max_rules']) ? absint($input['max_rules']) : 50;
        $sanitized['max_rules'] = max(1, min(200, $max_rules));

        $sanitized['delete_data_on_uninstall'] = !empty($input['delete_data_on_uninstall']);

        return $sanitized;
    }

    /**
     * Render the global bypass field.
     */
    public function render_global_bypass_field() {
        $settings = get_option($this->option_name, []);
        $checked = !empty($settings['global_bypass']);
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr($this->option_name); ?>[global_bypass]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Temporarily disable all visibility rules', 'cybokron-advanced-widget-visibility'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('All rules are ignored and widgets are shown everywhere. Useful for debugging.', 'cybokron-advanced-widget-visibility'); ?>
        </p>
        <?php
    }

    /**
     * Render the max rules field.
     */
    public function render_max_rules_field() {
        $settings = get_option($this->option_name, []);
        $max_rules = isset($settings['max_rules']) ? absint($settings['max_rules']) : 50;
        $max_rules = max(1, min(200, $max_rules));
        ?>
        <input type="number"
               name="<?php echo esc_attr($this->option_name); ?>[max_rules]"
               value="<?php echo esc_attr($max_rules); ?>"
               min="1"
               max="200"
               step="1"
               class="small-text">
        <p class="description">
            <?php esc_html_e('Maximum number of visibility rules allowed per widget.', 'cybokron-advanced-widget-visibility'); ?>
        </p>
        <?php
    }

    /**
     * Render the delete data on uninstall field.
     */
    public function render_delete_data_field() {
        $settings = get_option($this->option_name, []);
        $checked = !empty($settings['delete_data_on_uninstall']);
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr($this->option_name); ?>[delete_data_on_uninstall]"
                   value="1"
                   <?php checked($checked); ?>>
            <?php esc_html_e('Delete visibility data when plugin is uninstalled', 'cybokron-advanced-widget-visibility'); ?>
        </label>
        <p class="description" style="color: #d63638;">
            <strong>&#9888; <?php esc_html_e('Warning: This action cannot be undone.', 'cybokron-advanced-widget-visibility'); ?></strong>
        </p>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_page() {
        if (!current_user_can('edit_theme_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>
                <img src="<?php echo esc_url(CYBAWV_PLUGIN_URL . 'assets/images/icon-128x128.png'); ?>"
                     alt=""
                     width="48"
                     height="48"
                     style="vertical-align: middle; margin-right: 10px;">
                <?php echo esc_html('Cybokron Advanced Widget Visibility'); ?>
            </h1>
            <p><?php
                /* translators: %s: version number */
                printf(esc_html__('Version %s', 'cybokron-advanced-widget-visibility'), esc_html(CYBAWV_VERSION));
            ?></p>

            <form method="post" action="options.php">
                <?php
                settings_fields('cybawv_settings_group');
                do_settings_sections('cybawv-settings');
                submit_button();
                ?>
            </form>

            <hr>
            <h2><?php esc_html_e('Quick Links', 'cybokron-advanced-widget-visibility'); ?></h2>
            <ul>
                <li>
                    &rarr; <a href="<?php echo esc_url(admin_url('widgets.php')); ?>">
                        <?php esc_html_e('Manage Widgets', 'cybokron-advanced-widget-visibility'); ?>
                    </a>
                </li>
                <li>
                    &rarr; <a href="https://github.com/ercanatay/cybokron-advanced-widget-visibility/issues" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e('Support', 'cybokron-advanced-widget-visibility'); ?>
                    </a>
                </li>
            </ul>
        </div>
        <?php
    }
}
