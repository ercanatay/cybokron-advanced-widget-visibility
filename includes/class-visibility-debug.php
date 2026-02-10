<?php
/**
 * Debug mode for Widget Visibility with Descendants
 *
 * @package Widget_Visibility_Descendants
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WVD_Visibility_Debug {

    private $debug_log = [];

    public function __construct() {
        if (!is_admin()) {
            add_filter('widget_display_callback', [$this, 'collect_debug_data'], 5, 3);
            add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 999);
            add_action('wp_footer', [$this, 'render_debug_panel']);
            add_action('wp_head', [$this, 'debug_styles']);
        }
    }

    public function collect_debug_data($instance, $widget, $args) {
        if (!$this->is_debug_active()) {
            return $instance;
        }

        if (!is_array($instance) || empty($instance['wvd_visibility']['rules'])) {
            $this->debug_log[] = [
                'widget_id' => $widget->id,
                'widget_name' => isset($widget->name) ? $widget->name : $widget->id,
                'has_rules' => false,
                'visible' => true,
                'rules' => [],
            ];
            return $instance;
        }

        $visibility = $instance['wvd_visibility'];
        $this->debug_log[] = [
            'widget_id' => $widget->id,
            'widget_name' => isset($widget->name) ? $widget->name : $widget->id,
            'has_rules' => true,
            'action' => isset($visibility['action']) ? $visibility['action'] : 'show',
            'match_all' => !empty($visibility['match_all']),
            'rules' => is_array($visibility['rules']) ? $visibility['rules'] : [],
            'visible' => null,
        ];

        return $instance;
    }

    public function add_admin_bar_menu($wp_admin_bar) {
        if (!$this->is_debug_active()) {
            return;
        }

        $count = count($this->debug_log);
        $wp_admin_bar->add_node([
            'id' => 'wvd-debug',
            'title' => sprintf('WVD Debug (%d)', $count),
            'meta' => ['class' => 'wvd-debug-bar-item'],
        ]);
    }

    public function debug_styles() {
        if (!$this->is_debug_active()) {
            return;
        }
        ?>
        <style>
            #wvd-debug-panel{display:none;position:fixed;bottom:32px;right:10px;width:420px;max-height:60vh;background:#fff;border:1px solid #ccc;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:99999;overflow-y:auto;font-size:13px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
            #wvd-debug-panel.wvd-debug-open{display:block}
            #wvd-debug-panel .wvd-debug-header{padding:10px 15px;background:#0073aa;color:#fff;font-weight:600;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0}
            #wvd-debug-panel .wvd-debug-close{background:none;border:0;color:#fff;font-size:18px;cursor:pointer;padding:0 5px}
            .wvd-debug-widget{padding:10px 15px;border-bottom:1px solid #eee}
            .wvd-debug-widget:last-child{border-bottom:0}
            .wvd-debug-widget-name{font-weight:600;margin-bottom:4px}
            .wvd-debug-visible{color:#46b450}
            .wvd-debug-hidden{color:#dc3232}
            .wvd-debug-no-rules{color:#999}
            .wvd-debug-rule{font-size:12px;color:#666;padding:2px 0 2px 10px;border-left:2px solid #ddd;margin:3px 0}
        </style>
        <?php
    }

    public function render_debug_panel() {
        if (!$this->is_debug_active()) {
            return;
        }
        ?>
        <div id="wvd-debug-panel">
            <div class="wvd-debug-header">
                <span><?php echo esc_html(sprintf(__('Widget Visibility Debug (%d widgets)', 'widget-visibility-descendants-main'), count($this->debug_log))); ?></span>
                <button class="wvd-debug-close" onclick="document.getElementById('wvd-debug-panel').classList.remove('wvd-debug-open')">&times;</button>
            </div>
            <?php if (empty($this->debug_log)): ?>
                <div class="wvd-debug-widget">
                    <em><?php esc_html_e('No widgets processed on this page.', 'widget-visibility-descendants-main'); ?></em>
                </div>
            <?php else: ?>
                <?php foreach ($this->debug_log as $entry): ?>
                    <div class="wvd-debug-widget">
                        <div class="wvd-debug-widget-name"><?php echo esc_html($entry['widget_name']); ?></div>
                        <?php if (!$entry['has_rules']): ?>
                            <span class="wvd-debug-no-rules"><?php esc_html_e('No visibility rules', 'widget-visibility-descendants-main'); ?></span>
                        <?php else: ?>
                            <div>
                                <?php echo esc_html(ucfirst($entry['action'])); ?>
                                <?php if ($entry['match_all']): ?>
                                    (<?php esc_html_e('AND', 'widget-visibility-descendants-main'); ?>)
                                <?php else: ?>
                                    (<?php esc_html_e('OR', 'widget-visibility-descendants-main'); ?>)
                                <?php endif; ?>
                            </div>
                            <?php foreach ($entry['rules'] as $rule): ?>
                                <div class="wvd-debug-rule">
                                    <?php
                                    $type = isset($rule['type']) ? $rule['type'] : '?';
                                    $value = isset($rule['value']) ? $rule['value'] : '';
                                    echo esc_html($type);
                                    if ($value !== '') {
                                        echo ': ' . esc_html($value);
                                    }
                                    if (!empty($rule['include_descendants'])) {
                                        echo ' (+descendants)';
                                    } elseif (!empty($rule['include_children'])) {
                                        echo ' (+children)';
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <script>
        (function(){
            var item = document.querySelector('#wp-admin-bar-wvd-debug > a, #wp-admin-bar-wvd-debug > .ab-item');
            if (item) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('wvd-debug-panel').classList.toggle('wvd-debug-open');
                });
            }
        })();
        </script>
        <?php
    }

    private function is_debug_active() {
        return is_user_logged_in() && current_user_can('edit_theme_options');
    }
}
