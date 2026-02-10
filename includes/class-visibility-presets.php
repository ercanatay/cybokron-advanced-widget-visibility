<?php
/**
 * Preset management for Widget Visibility with Descendants
 *
 * @package Widget_Visibility_Descendants
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WVD_Visibility_Presets {

    private $option_key = 'wvd_presets';

    public function __construct() {
        add_action('wp_ajax_wvd_save_preset', [$this, 'ajax_save_preset']);
        add_action('wp_ajax_wvd_load_presets', [$this, 'ajax_load_presets']);
        add_action('wp_ajax_wvd_delete_preset', [$this, 'ajax_delete_preset']);
    }

    public function ajax_save_preset() {
        check_ajax_referer('wvd_presets_nonce', 'nonce');

        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $name = isset($_POST['preset_name']) ? sanitize_text_field(wp_unslash($_POST['preset_name'])) : '';
        if ($name === '' || strlen($name) > 100) {
            wp_send_json_error(['message' => 'Invalid preset name']);
        }

        $data = isset($_POST['preset_data']) ? wp_unslash($_POST['preset_data']) : '';
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            wp_send_json_error(['message' => 'Invalid preset data']);
        }

        $presets = $this->get_presets();
        if (count($presets) >= 50) {
            wp_send_json_error(['message' => 'Maximum 50 presets allowed']);
        }

        $presets[$name] = [
            'data' => $data,
            'created' => current_time('mysql'),
        ];

        update_option($this->option_key, $presets);
        wp_send_json_success(['message' => 'Preset saved', 'presets' => $this->get_preset_list()]);
    }

    public function ajax_load_presets() {
        check_ajax_referer('wvd_presets_nonce', 'nonce');

        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $presets = $this->get_presets();
        $result = [];
        foreach ($presets as $name => $preset) {
            $result[] = [
                'name' => $name,
                'data' => isset($preset['data']) ? $preset['data'] : [],
                'created' => isset($preset['created']) ? $preset['created'] : '',
            ];
        }

        wp_send_json_success(['presets' => $result]);
    }

    public function ajax_delete_preset() {
        check_ajax_referer('wvd_presets_nonce', 'nonce');

        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $name = isset($_POST['preset_name']) ? sanitize_text_field(wp_unslash($_POST['preset_name'])) : '';
        if ($name === '') {
            wp_send_json_error(['message' => 'Invalid preset name']);
        }

        $presets = $this->get_presets();
        if (isset($presets[$name])) {
            unset($presets[$name]);
            update_option($this->option_key, $presets);
        }

        wp_send_json_success(['message' => 'Preset deleted', 'presets' => $this->get_preset_list()]);
    }

    private function get_presets() {
        $presets = get_option($this->option_key, []);
        return is_array($presets) ? $presets : [];
    }

    private function get_preset_list() {
        $presets = $this->get_presets();
        $list = [];
        foreach ($presets as $name => $preset) {
            $list[] = [
                'name' => $name,
                'data' => isset($preset['data']) ? $preset['data'] : [],
                'created' => isset($preset['created']) ? $preset['created'] : '',
            ];
        }
        return $list;
    }
}
