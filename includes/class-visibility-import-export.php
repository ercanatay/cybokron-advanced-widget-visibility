<?php
/**
 * Import/Export for Widget Visibility with Descendants
 *
 * @package Widget_Visibility_Descendants
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WVD_Visibility_Import_Export {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'handle_export']);
        add_action('admin_init', [$this, 'handle_import']);
    }

    public function add_admin_page() {
        add_management_page(
            __('Widget Visibility Import/Export', 'widget-visibility-descendants-main'),
            __('Widget Visibility', 'widget-visibility-descendants-main'),
            'edit_theme_options',
            'wvd-import-export',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        $message = '';
        if (isset($_GET['wvd_imported'])) {
            $count = absint($_GET['wvd_imported']);
            $message = sprintf(
                __('Successfully imported visibility rules for %d widget(s).', 'widget-visibility-descendants-main'),
                $count
            );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Widget Visibility Import/Export', 'widget-visibility-descendants-main'); ?></h1>

            <?php if ($message): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <div class="card" style="max-width:600px;margin-top:20px;">
                <h2><?php esc_html_e('Export', 'widget-visibility-descendants-main'); ?></h2>
                <p><?php esc_html_e('Download all widget visibility rules as a JSON file.', 'widget-visibility-descendants-main'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('wvd_export', 'wvd_export_nonce'); ?>
                    <input type="hidden" name="wvd_action" value="export">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Export Rules', 'widget-visibility-descendants-main'); ?>
                    </button>
                </form>
            </div>

            <div class="card" style="max-width:600px;margin-top:20px;">
                <h2><?php esc_html_e('Import', 'widget-visibility-descendants-main'); ?></h2>
                <p><?php esc_html_e('Upload a JSON file to import widget visibility rules.', 'widget-visibility-descendants-main'); ?></p>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('wvd_import', 'wvd_import_nonce'); ?>
                    <input type="hidden" name="wvd_action" value="import">
                    <p>
                        <input type="file" name="wvd_import_file" accept=".json">
                    </p>
                    <button type="submit" class="button button-secondary">
                        <?php esc_html_e('Import Rules', 'widget-visibility-descendants-main'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_export() {
        if (!isset($_POST['wvd_action']) || $_POST['wvd_action'] !== 'export') {
            return;
        }

        if (!check_admin_referer('wvd_export', 'wvd_export_nonce')) {
            return;
        }

        if (!current_user_can('edit_theme_options')) {
            return;
        }

        $export_data = $this->collect_visibility_data();

        $filename = 'wvd-visibility-rules-' . gmdate('Y-m-d') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    public function handle_import() {
        if (!isset($_POST['wvd_action']) || $_POST['wvd_action'] !== 'import') {
            return;
        }

        if (!check_admin_referer('wvd_import', 'wvd_import_nonce')) {
            return;
        }

        if (!current_user_can('edit_theme_options')) {
            return;
        }

        if (!isset($_FILES['wvd_import_file']) || $_FILES['wvd_import_file']['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $file = $_FILES['wvd_import_file'];
        if ($file['size'] > 512000) {
            return;
        }

        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['widgets']) || !is_array($data['widgets'])) {
            return;
        }

        $count = $this->apply_visibility_data($data['widgets']);

        wp_safe_redirect(admin_url('tools.php?page=wvd-import-export&wvd_imported=' . $count));
        exit;
    }

    private function collect_visibility_data() {
        global $wp_registered_widgets;

        $sidebars = wp_get_sidebars_widgets();
        $export = [
            'plugin' => 'widget-visibility-descendants',
            'version' => WVD_VERSION,
            'exported' => current_time('mysql'),
            'widgets' => [],
        ];

        if (!is_array($sidebars)) {
            return $export;
        }

        foreach ($sidebars as $sidebar_id => $widgets) {
            if (!is_array($widgets) || $sidebar_id === 'wp_inactive_widgets') {
                continue;
            }
            foreach ($widgets as $widget_id) {
                $id_base = $this->get_widget_id_base($widget_id);
                $number = $this->get_widget_number($widget_id);
                if (!$id_base || $number === false) {
                    continue;
                }
                $option = get_option('widget_' . $id_base);
                if (!is_array($option) || !isset($option[$number]['wvd_visibility'])) {
                    continue;
                }
                $visibility = $option[$number]['wvd_visibility'];
                if (!empty($visibility['rules'])) {
                    $export['widgets'][] = [
                        'widget_id' => $widget_id,
                        'id_base' => $id_base,
                        'number' => $number,
                        'sidebar' => $sidebar_id,
                        'visibility' => $visibility,
                    ];
                }
            }
        }

        return $export;
    }

    private function apply_visibility_data($widgets) {
        $count = 0;
        foreach ($widgets as $widget_data) {
            if (!is_array($widget_data) || empty($widget_data['id_base']) || !isset($widget_data['number'])) {
                continue;
            }
            $id_base = sanitize_key($widget_data['id_base']);
            $number = absint($widget_data['number']);
            $visibility = isset($widget_data['visibility']) ? $widget_data['visibility'] : [];

            if (!is_array($visibility) || empty($visibility['rules'])) {
                continue;
            }

            $option = get_option('widget_' . $id_base);
            if (!is_array($option) || !isset($option[$number])) {
                continue;
            }

            $option[$number]['wvd_visibility'] = $visibility;
            update_option('widget_' . $id_base, $option);
            $count++;
        }
        return $count;
    }

    private function get_widget_id_base($widget_id) {
        if (preg_match('/^(.+)-(\d+)$/', $widget_id, $matches)) {
            return $matches[1];
        }
        return false;
    }

    private function get_widget_number($widget_id) {
        if (preg_match('/^(.+)-(\d+)$/', $widget_id, $matches)) {
            return (int) $matches[2];
        }
        return false;
    }
}
