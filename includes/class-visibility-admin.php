<?php
/**
 * Admin functionality for Widget Visibility with Descendants
 *
 * @package Widget_Visibility_Descendants
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Class
 */
class WVD_Visibility_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('in_widget_form', [$this, 'render_visibility_ui'], 10, 3);
        add_filter('widget_update_callback', [$this, 'save_visibility_settings'], 10, 4);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ('widgets.php' !== $hook && 'customize.php' !== $hook) {
            return;
        }

        $taxonomies = $this->get_hierarchical_taxonomies();

        // Enqueue jQuery UI Datepicker for scheduling feature
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('wvd-jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css', [], '1.13.2');

        wp_enqueue_style(
            'wvd-admin-css',
            WVD_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WVD_VERSION
        );

        wp_enqueue_script(
            'wvd-admin-js',
            WVD_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-datepicker'],
            WVD_VERSION,
            true
        );

        // Build localized data
        $localize_data = [
            'pages' => $this->get_hierarchical_pages(),
            'categories' => $this->get_categories(),
            'tags' => $this->get_tags(),
            'authors' => $this->get_authors(),
            'postTypes' => $this->get_post_types(),
            'taxonomies' => $taxonomies,
            'taxonomyTerms' => $this->get_taxonomy_terms($taxonomies),
            'roles' => $this->get_user_roles(),
            'hasWooCommerce' => class_exists('WooCommerce'),
            'presetsNonce' => wp_create_nonce('wvd_presets_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'i18n' => [
                'visibility' => __('Visibility', 'widget-visibility-descendants-main'),
                'show' => __('Show', 'widget-visibility-descendants-main'),
                'hide' => __('Hide', 'widget-visibility-descendants-main'),
                'if' => __('if', 'widget-visibility-descendants-main'),
                'is' => __('is', 'widget-visibility-descendants-main'),
                'page' => __('Page', 'widget-visibility-descendants-main'),
                'category' => __('Category', 'widget-visibility-descendants-main'),
                'tag' => __('Tag', 'widget-visibility-descendants-main'),
                'author' => __('Author', 'widget-visibility-descendants-main'),
                'postType' => __('Post Type', 'widget-visibility-descendants-main'),
                'taxonomy' => __('Taxonomy', 'widget-visibility-descendants-main'),
                'userRole' => __('User Role', 'widget-visibility-descendants-main'),
                'schedule' => __('Schedule', 'widget-visibility-descendants-main'),
                'urlParam' => __('URL Parameter', 'widget-visibility-descendants-main'),
                'device' => __('Device', 'widget-visibility-descendants-main'),
                'frontPage' => __('Front Page', 'widget-visibility-descendants-main'),
                'blog' => __('Blog', 'widget-visibility-descendants-main'),
                'archive' => __('Archive', 'widget-visibility-descendants-main'),
                'search' => __('Search', 'widget-visibility-descendants-main'),
                'notFound' => __('404', 'widget-visibility-descendants-main'),
                'single' => __('Single Post', 'widget-visibility-descendants-main'),
                'loggedIn' => __('Logged In', 'widget-visibility-descendants-main'),
                'loggedOut' => __('Logged Out', 'widget-visibility-descendants-main'),
                'wooShop' => __('WooCommerce: Shop', 'widget-visibility-descendants-main'),
                'wooCart' => __('WooCommerce: Cart', 'widget-visibility-descendants-main'),
                'wooCheckout' => __('WooCommerce: Checkout', 'widget-visibility-descendants-main'),
                'wooAccount' => __('WooCommerce: My Account', 'widget-visibility-descendants-main'),
                'wooProductCat' => __('WooCommerce: Product Category', 'widget-visibility-descendants-main'),
                'selectPostType' => __('Select a post type...', 'widget-visibility-descendants-main'),
                'selectTaxonomy' => __('Select a taxonomy...', 'widget-visibility-descendants-main'),
                'selectTerm' => __('Select a term...', 'widget-visibility-descendants-main'),
                'selectRoles' => __('Select one or more roles...', 'widget-visibility-descendants-main'),
                'selectPage' => __('Select a page...', 'widget-visibility-descendants-main'),
                'selectCategory' => __('Select a category...', 'widget-visibility-descendants-main'),
                'selectTag' => __('Select a tag...', 'widget-visibility-descendants-main'),
                'selectAuthor' => __('Select an author...', 'widget-visibility-descendants-main'),
                'selectWooCategory' => __('Select a product category...', 'widget-visibility-descendants-main'),
                'selectDevice' => __('Select device...', 'widget-visibility-descendants-main'),
                'startDate' => __('Start date', 'widget-visibility-descendants-main'),
                'endDate' => __('End date', 'widget-visibility-descendants-main'),
                'paramName' => __('Parameter name', 'widget-visibility-descendants-main'),
                'paramValue' => __('Parameter value', 'widget-visibility-descendants-main'),
                'mobile' => __('Mobile', 'widget-visibility-descendants-main'),
                'desktop' => __('Desktop', 'widget-visibility-descendants-main'),
                'configured' => __('Configured', 'widget-visibility-descendants-main'),
                'includeChildren' => __('Include children', 'widget-visibility-descendants-main'),
                'includeDescendants' => __('Include all descendants', 'widget-visibility-descendants-main'),
                'matchAll' => __('Match all conditions', 'widget-visibility-descendants-main'),
                'addCondition' => __('Add condition', 'widget-visibility-descendants-main'),
                'remove' => __('Remove', 'widget-visibility-descendants-main'),
                'done' => __('Done', 'widget-visibility-descendants-main'),
                'delete' => __('Delete', 'widget-visibility-descendants-main'),
                'presets' => __('Presets', 'widget-visibility-descendants-main'),
                'savePreset' => __('Save as Preset', 'widget-visibility-descendants-main'),
                'loadPreset' => __('Load Preset', 'widget-visibility-descendants-main'),
                'presetName' => __('Preset name', 'widget-visibility-descendants-main'),
                'deletePreset' => __('Delete Preset', 'widget-visibility-descendants-main'),
                'noPresets' => __('No presets saved', 'widget-visibility-descendants-main'),
                'presetSaved' => __('Preset saved!', 'widget-visibility-descendants-main'),
                'presetDeleted' => __('Preset deleted!', 'widget-visibility-descendants-main'),
                'enterPresetName' => __('Enter preset name:', 'widget-visibility-descendants-main'),
                'importExport' => __('Import/Export', 'widget-visibility-descendants-main'),
            ]
        ];

        // Add WooCommerce product categories if available
        if (class_exists('WooCommerce')) {
            $localize_data['wooProductCategories'] = $this->get_woo_product_categories();
        }

        wp_localize_script('wvd-admin-js', 'wvdData', $localize_data);
    }

    /**
     * Get hierarchical pages with depth indicator
     */
    private function get_hierarchical_pages() {
        $pages = get_pages([
            'sort_column' => 'menu_order,post_title',
            'hierarchical' => true,
        ]);

        $parent_ids = [];
        foreach ($pages as $page) {
            if ($page->post_parent > 0) {
                $parent_ids[$page->post_parent] = true;
            }
        }

        $options = [];
        foreach ($pages as $page) {
            $depth = count(get_post_ancestors($page->ID));
            $prefix = str_repeat('— ', $depth);
            $title = sanitize_text_field($page->post_title);
            $options[] = [
                'id' => $page->ID,
                'title' => $prefix . $title,
                'parent' => $page->post_parent,
                'hasChildren' => isset($parent_ids[$page->ID]),
            ];
        }

        return $options;
    }

    /**
     * Get categories
     */
    private function get_categories() {
        $categories = get_categories([
            'hide_empty' => false,
            'hierarchical' => true,
        ]);

        $cat_by_id = [];
        $parent_ids = [];
        foreach ($categories as $cat) {
            $cat_by_id[$cat->term_id] = $cat;
            if ($cat->parent > 0) {
                $parent_ids[$cat->parent] = true;
            }
        }

        $options = [];
        foreach ($categories as $cat) {
            $depth = 0;
            $parent = $cat->parent;
            $seen = [];
            while ($parent > 0 && isset($cat_by_id[$parent]) && !isset($seen[$parent])) {
                $seen[$parent] = true;
                $depth++;
                $parent = $cat_by_id[$parent]->parent;
            }
            $prefix = str_repeat('— ', $depth);
            $name = sanitize_text_field($cat->name);
            $options[] = [
                'id' => $cat->term_id,
                'title' => $prefix . $name,
                'parent' => $cat->parent,
                'hasChildren' => isset($parent_ids[$cat->term_id]),
            ];
        }

        return $options;
    }

    /**
     * Get tags
     *
     * @since 2.0.0
     */
    private function get_tags() {
        $tags = get_tags([
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($tags) || !is_array($tags)) {
            return [];
        }

        $options = [];
        foreach ($tags as $tag) {
            $options[] = [
                'id' => $tag->term_id,
                'title' => sanitize_text_field($tag->name),
            ];
        }

        return $options;
    }

    /**
     * Get authors
     *
     * @since 2.0.0
     */
    private function get_authors() {
        $users = get_users([
            'who' => 'authors',
            'has_published_posts' => true,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => ['ID', 'display_name'],
        ]);

        $options = [];
        foreach ($users as $user) {
            $options[] = [
                'id' => $user->ID,
                'title' => sanitize_text_field($user->display_name),
            ];
        }

        return $options;
    }

    /**
     * Get WooCommerce product categories
     *
     * @since 2.0.0
     */
    private function get_woo_product_categories() {
        if (!class_exists('WooCommerce') || !taxonomy_exists('product_cat')) {
            return [];
        }

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms) || !is_array($terms)) {
            return [];
        }

        $term_by_id = [];
        $parent_ids = [];
        foreach ($terms as $term) {
            $term_by_id[$term->term_id] = $term;
            if ($term->parent > 0) {
                $parent_ids[$term->parent] = true;
            }
        }

        $options = [];
        foreach ($terms as $term) {
            $depth = 0;
            $parent = $term->parent;
            $seen = [];
            while ($parent > 0 && isset($term_by_id[$parent]) && !isset($seen[$parent])) {
                $seen[$parent] = true;
                $depth++;
                $parent = $term_by_id[$parent]->parent;
            }
            $prefix = str_repeat('— ', $depth);
            $options[] = [
                'id' => $term->term_id,
                'title' => $prefix . sanitize_text_field($term->name),
                'parent' => $term->parent,
                'hasChildren' => isset($parent_ids[$term->term_id]),
            ];
        }

        return $options;
    }

    /**
     * Get public post types
     */
    private function get_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        $options = [];

        foreach ($post_types as $pt) {
            if ($pt->name === 'attachment') continue;
            $options[] = [
                'id' => $pt->name,
                'title' => sanitize_text_field($pt->labels->singular_name),
            ];
        }

        return $options;
    }

    /**
     * Get hierarchical public taxonomies, excluding built-in category.
     */
    private function get_hierarchical_taxonomies() {
        $taxonomies = get_taxonomies([
            'public' => true,
            'hierarchical' => true,
        ], 'objects');

        $options = [];
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->name === 'category') {
                continue;
            }
            if ($taxonomy->name === 'product_cat' && class_exists('WooCommerce')) {
                continue;
            }

            $label = isset($taxonomy->labels->singular_name)
                ? $taxonomy->labels->singular_name
                : $taxonomy->label;

            $options[] = [
                'id' => sanitize_key($taxonomy->name),
                'title' => sanitize_text_field($label),
            ];
        }

        usort($options, static function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        return $options;
    }

    /**
     * Get taxonomy terms grouped by taxonomy slug.
     */
    private function get_taxonomy_terms($taxonomies) {
        $terms_by_taxonomy = [];

        if (!is_array($taxonomies)) {
            return $terms_by_taxonomy;
        }

        foreach ($taxonomies as $taxonomy_option) {
            if (empty($taxonomy_option['id']) || !is_scalar($taxonomy_option['id'])) {
                continue;
            }

            $taxonomy = sanitize_key((string) $taxonomy_option['id']);
            if (!$this->is_valid_hierarchical_taxonomy($taxonomy)) {
                continue;
            }

            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);

            if (is_wp_error($terms) || !is_array($terms)) {
                $terms_by_taxonomy[$taxonomy] = [];
                continue;
            }

            $options = [];
            foreach ($terms as $term) {
                if (!($term instanceof WP_Term)) {
                    continue;
                }

                $depth = count(get_ancestors($term->term_id, $taxonomy, 'taxonomy'));
                $prefix = str_repeat('— ', $depth);

                $children = get_terms([
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                    'parent' => $term->term_id,
                    'number' => 1,
                    'fields' => 'ids',
                ]);

                $options[] = [
                    'id' => $term->term_id,
                    'title' => $prefix . sanitize_text_field($term->name),
                    'parent' => $term->parent,
                    'hasChildren' => is_array($children) && !empty($children),
                ];
            }

            $terms_by_taxonomy[$taxonomy] = $options;
        }

        return $terms_by_taxonomy;
    }

    /**
     * Get user roles.
     */
    private function get_user_roles() {
        $wp_roles = wp_roles();
        $options = [];

        if (!($wp_roles instanceof WP_Roles) || !is_array($wp_roles->roles)) {
            return $options;
        }

        foreach ($wp_roles->roles as $slug => $role_data) {
            if (!is_string($slug) || !is_array($role_data)) {
                continue;
            }

            $label = isset($role_data['name']) && is_string($role_data['name'])
                ? translate_user_role($role_data['name'])
                : $slug;

            $options[] = [
                'id' => sanitize_key($slug),
                'title' => sanitize_text_field($label),
            ];
        }

        usort($options, static function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        return $options;
    }

    /**
     * Get available role slugs.
     */
    private function get_role_slugs() {
        $roles = $this->get_user_roles();
        $role_slugs = [];

        foreach ($roles as $role) {
            if (!empty($role['id']) && is_scalar($role['id'])) {
                $role_slugs[] = sanitize_key((string) $role['id']);
            }
        }

        return array_values(array_unique($role_slugs));
    }

    /**
     * Validate hierarchical public taxonomy slug.
     */
    private function is_valid_hierarchical_taxonomy($taxonomy) {
        if (!is_string($taxonomy) || $taxonomy === '' || $taxonomy === 'category') {
            return false;
        }

        $taxonomy_obj = get_taxonomy($taxonomy);

        if (!is_object($taxonomy_obj)) {
            return false;
        }

        return !empty($taxonomy_obj->public) && !empty($taxonomy_obj->hierarchical);
    }

    /**
     * Render visibility UI in widget form
     */
    public function render_visibility_ui($widget, $return, $instance) {
        $visibility = isset($instance['wvd_visibility']) ? $instance['wvd_visibility'] : [];
        $widget_id = $widget->id;
        ?>
        <div class="wvd-visibility-wrapper" data-widget-id="<?php echo esc_attr($widget_id); ?>">
            <p class="wvd-visibility-toggle">
                <button type="button" class="button wvd-visibility-button">
                    <?php esc_html_e('Visibility', 'widget-visibility-descendants-main'); ?>
                </button>
                <?php if (!empty($visibility['rules'])): ?>
                    <span class="wvd-visibility-status wvd-has-rules"><?php esc_html_e('Configured', 'widget-visibility-descendants-main'); ?></span>
                <?php endif; ?>
            </p>

            <div class="wvd-visibility-panel" style="display: none;">
                <input type="hidden"
                       name="<?php echo esc_attr($widget->get_field_name('wvd_visibility')); ?>"
                       class="wvd-visibility-data"
                       value="<?php echo esc_attr(wp_json_encode($visibility)); ?>">

                <div class="wvd-visibility-content">
                    <!-- JavaScript will render the UI here -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save visibility settings
     */
    public function save_visibility_settings($instance, $new_instance, $old_instance, $widget) {
        if (!is_array($instance)) {
            return $instance;
        }

        if (!current_user_can('edit_theme_options')) {
            return $instance;
        }

        if (isset($new_instance['wvd_visibility'])) {
            $data = $new_instance['wvd_visibility'];
            if (is_string($data)) {
                $data = json_decode(wp_unslash($data), true);
            }
            $instance['wvd_visibility'] = $this->sanitize_visibility_data($data);
        }
        return $instance;
    }

    /**
     * Sanitize visibility data
     */
    private function sanitize_visibility_data($data) {
        if (!is_array($data)) {
            return [];
        }

        $sanitized = [
            'action' => isset($data['action']) && in_array($data['action'], ['show', 'hide'], true) ? $data['action'] : 'show',
            'match_all' => !empty($data['match_all']),
            'rules' => [],
        ];

        $allowed_types = [
            'page', 'category', 'tag', 'author', 'post_type', 'front_page', 'blog',
            'archive', 'search', '404', 'single', 'logged_in', 'logged_out',
            'taxonomy', 'user_role', 'schedule', 'url_param', 'device',
            'woo_shop', 'woo_cart', 'woo_checkout', 'woo_account', 'woo_product_cat',
        ];

        $max_rules = 50;
        $max_value_length = 100;
        $max_role_values = 20;

        $valid_post_types = array_map('sanitize_key', get_post_types(['public' => true], 'names'));
        $valid_roles = $this->get_role_slugs();
        $valid_devices = ['mobile', 'desktop'];

        if (!empty($data['rules']) && is_array($data['rules'])) {
            $count = 0;
            foreach ($data['rules'] as $rule) {
                if ($count >= $max_rules) {
                    break;
                }

                if (!is_array($rule) || !isset($rule['type']) || !is_scalar($rule['type'])) {
                    continue;
                }

                $type = sanitize_key((string) $rule['type']);
                if (!in_array($type, $allowed_types, true)) {
                    continue;
                }

                $value = '';
                if (isset($rule['value']) && is_scalar($rule['value'])) {
                    $value = sanitize_text_field((string) $rule['value']);
                }
                if (strlen($value) > $max_value_length) {
                    $value = substr($value, 0, $max_value_length);
                }

                $sanitized_rule = [
                    'type' => $type,
                    'include_children' => !empty($rule['include_children']),
                    'include_descendants' => !empty($rule['include_descendants']),
                ];

                // User Role
                if ('user_role' === $type) {
                    $candidate_roles = [];
                    if (isset($rule['values']) && is_array($rule['values'])) {
                        $candidate_roles = $rule['values'];
                    } elseif ($value !== '') {
                        $candidate_roles = [$value];
                    }
                    $roles = [];
                    foreach ($candidate_roles as $candidate_role) {
                        if (count($roles) >= $max_role_values) {
                            break;
                        }
                        if (!is_scalar($candidate_role)) {
                            continue;
                        }
                        $role_slug = sanitize_key((string) $candidate_role);
                        if ($role_slug !== '' && in_array($role_slug, $valid_roles, true)) {
                            $roles[] = $role_slug;
                        }
                    }
                    $roles = array_values(array_unique($roles));
                    if (empty($roles)) {
                        continue;
                    }
                    $sanitized_rule['values'] = $roles;
                    $sanitized_rule['value'] = '';
                    $sanitized_rule['include_children'] = false;
                    $sanitized_rule['include_descendants'] = false;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Taxonomy
                if ('taxonomy' === $type) {
                    if (!isset($rule['taxonomy']) || !is_scalar($rule['taxonomy'])) {
                        continue;
                    }
                    $taxonomy = sanitize_key((string) $rule['taxonomy']);
                    if (!$this->is_valid_hierarchical_taxonomy($taxonomy)) {
                        continue;
                    }
                    $term_id = absint($value);
                    if ($term_id <= 0) {
                        continue;
                    }
                    $term = get_term($term_id, $taxonomy);
                    if (!($term instanceof WP_Term) || is_wp_error($term)) {
                        continue;
                    }
                    $sanitized_rule['taxonomy'] = $taxonomy;
                    $sanitized_rule['value'] = (string) $term_id;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Post Type
                if ('post_type' === $type) {
                    $value = sanitize_key($value);
                    if ($value === '' || !in_array($value, $valid_post_types, true)) {
                        continue;
                    }
                    $sanitized_rule['value'] = $value;
                    $sanitized_rule['include_children'] = false;
                    $sanitized_rule['include_descendants'] = false;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Page, Category
                if (in_array($type, ['page', 'category'], true)) {
                    $entity_id = absint($value);
                    if ($entity_id <= 0) {
                        continue;
                    }
                    $sanitized_rule['value'] = (string) $entity_id;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Tag
                if ('tag' === $type) {
                    $tag_id = absint($value);
                    if ($tag_id <= 0) {
                        continue;
                    }
                    $sanitized_rule['value'] = (string) $tag_id;
                    $sanitized_rule['include_children'] = false;
                    $sanitized_rule['include_descendants'] = false;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Author
                if ('author' === $type) {
                    $author_id = absint($value);
                    if ($author_id <= 0) {
                        continue;
                    }
                    $sanitized_rule['value'] = (string) $author_id;
                    $sanitized_rule['include_children'] = false;
                    $sanitized_rule['include_descendants'] = false;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Schedule
                if ('schedule' === $type) {
                    $start_date = '';
                    $end_date = '';
                    if (isset($rule['start_date']) && is_scalar($rule['start_date'])) {
                        $start_date = sanitize_text_field((string) $rule['start_date']);
                        if ($start_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
                            $start_date = '';
                        }
                    }
                    if (isset($rule['end_date']) && is_scalar($rule['end_date'])) {
                        $end_date = sanitize_text_field((string) $rule['end_date']);
                        if ($end_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                            $end_date = '';
                        }
                    }
                    if ($start_date === '' && $end_date === '') {
                        continue;
                    }
                    $sanitized_rule['start_date'] = $start_date;
                    $sanitized_rule['end_date'] = $end_date;
                    $sanitized_rule['value'] = '';
                    $sanitized_rule['include_children'] = false;
                    $sanitized_rule['include_descendants'] = false;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // URL Parameter
                if ('url_param' === $type) {
                    $param_name = '';
                    $param_value = '';
                    if (isset($rule['param_name']) && is_scalar($rule['param_name'])) {
                        $param_name = sanitize_text_field((string) $rule['param_name']);
                    }
                    if (isset($rule['param_value']) && is_scalar($rule['param_value'])) {
                        $param_value = sanitize_text_field((string) $rule['param_value']);
                    }
                    if ($param_name === '') {
                        continue;
                    }
                    if (strlen($param_name) > $max_value_length) {
                        $param_name = substr($param_name, 0, $max_value_length);
                    }
                    if (strlen($param_value) > $max_value_length) {
                        $param_value = substr($param_value, 0, $max_value_length);
                    }
                    $sanitized_rule['param_name'] = $param_name;
                    $sanitized_rule['param_value'] = $param_value;
                    $sanitized_rule['value'] = '';
                    $sanitized_rule['include_children'] = false;
                    $sanitized_rule['include_descendants'] = false;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Device
                if ('device' === $type) {
                    $device_value = sanitize_key($value);
                    if (!in_array($device_value, $valid_devices, true)) {
                        continue;
                    }
                    $sanitized_rule['value'] = $device_value;
                    $sanitized_rule['include_children'] = false;
                    $sanitized_rule['include_descendants'] = false;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // WooCommerce Product Category
                if ('woo_product_cat' === $type) {
                    $term_id = absint($value);
                    if ($term_id <= 0) {
                        continue;
                    }
                    $sanitized_rule['value'] = (string) $term_id;
                    $sanitized['rules'][] = $sanitized_rule;
                    $count++;
                    continue;
                }

                // Boolean types (no value needed)
                $sanitized_rule['value'] = $value;
                $sanitized_rule['include_children'] = false;
                $sanitized_rule['include_descendants'] = false;
                $sanitized['rules'][] = $sanitized_rule;
                $count++;
            }
        }

        return $sanitized;
    }
}
