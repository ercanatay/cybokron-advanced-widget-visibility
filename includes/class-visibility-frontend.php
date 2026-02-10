<?php
/**
 * Frontend functionality for Widget Visibility with Descendants
 *
 * @package Widget_Visibility_Descendants
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Class
 */
class WVD_Visibility_Frontend {

    /**
     * Cached term ancestors to avoid repeated taxonomy lookups.
     *
     * @var array<string, int[]>
     */
    private $term_ancestor_cache = [];

    /**
     * Constructor
     */
    public function __construct() {
        add_filter('widget_display_callback', [$this, 'filter_widget_display'], 10, 3);
    }

    /**
     * Filter widget display based on visibility rules
     */
    public function filter_widget_display($instance, $widget, $args) {
        if (!is_array($instance) || empty($instance['wvd_visibility']['rules'])) {
            return $instance;
        }

        $visibility = $instance['wvd_visibility'];
        $action = isset($visibility['action']) ? $visibility['action'] : 'show';
        $match_all = !empty($visibility['match_all']);
        $rules = is_array($visibility['rules']) ? $visibility['rules'] : [];
        $supported_rule_types = $this->get_supported_rule_types();

        $rules = array_values(array_filter($rules, function($rule) use ($supported_rule_types) {
            return is_array($rule)
                && !empty($rule['type'])
                && in_array($rule['type'], $supported_rule_types, true);
        }));

        if (empty($rules)) {
            return $instance;
        }

        $results = [];
        foreach ($rules as $rule) {
            $results[] = $this->evaluate_rule($rule);
        }

        $conditions_met = $match_all ? !in_array(false, $results, true) : in_array(true, $results, true);

        if ($action === 'show') {
            return $conditions_met ? $instance : false;
        } else {
            return $conditions_met ? false : $instance;
        }
    }

    /**
     * Supported visibility rule types.
     */
    private function get_supported_rule_types() {
        return [
            'page',
            'category',
            'tag',
            'author',
            'post_type',
            'front_page',
            'blog',
            'archive',
            'search',
            '404',
            'single',
            'logged_in',
            'logged_out',
            'taxonomy',
            'user_role',
            'schedule',
            'url_param',
            'device',
            'woo_shop',
            'woo_cart',
            'woo_checkout',
            'woo_account',
            'woo_product_cat',
        ];
    }

    /**
     * Evaluate a single rule
     */
    private function evaluate_rule($rule) {
        $type = isset($rule['type']) ? $rule['type'] : '';
        $value = isset($rule['value']) ? $rule['value'] : '';
        $taxonomy = isset($rule['taxonomy']) ? sanitize_key((string) $rule['taxonomy']) : '';
        $values = isset($rule['values']) && is_array($rule['values']) ? $rule['values'] : [];
        $include_children = !empty($rule['include_children']);
        $include_descendants = !empty($rule['include_descendants']);

        switch ($type) {
            case 'page':
                return $this->evaluate_page_rule($value, $include_children, $include_descendants);
            case 'category':
                return $this->evaluate_category_rule($value, $include_children, $include_descendants);
            case 'tag':
                return $this->evaluate_tag_rule($value);
            case 'author':
                return $this->evaluate_author_rule($value);
            case 'post_type':
                return $this->evaluate_post_type_rule($value);
            case 'taxonomy':
                return $this->evaluate_taxonomy_rule($taxonomy, $value, $include_children, $include_descendants);
            case 'front_page':
                return is_front_page();
            case 'blog':
                return is_home();
            case 'archive':
                return is_archive();
            case 'search':
                return is_search();
            case '404':
                return is_404();
            case 'single':
                return is_single();
            case 'logged_in':
                return is_user_logged_in();
            case 'logged_out':
                return !is_user_logged_in();
            case 'user_role':
                return $this->evaluate_user_role_rule($values);
            case 'schedule':
                $start_date = isset($rule['start_date']) ? $rule['start_date'] : '';
                $end_date = isset($rule['end_date']) ? $rule['end_date'] : '';
                return $this->evaluate_schedule_rule($start_date, $end_date);
            case 'url_param':
                $param_name = isset($rule['param_name']) ? $rule['param_name'] : '';
                $param_value = isset($rule['param_value']) ? $rule['param_value'] : '';
                return $this->evaluate_url_param_rule($param_name, $param_value);
            case 'device':
                return $this->evaluate_device_rule($value);
            case 'woo_shop':
                return $this->evaluate_woo_shop_rule();
            case 'woo_cart':
                return $this->evaluate_woo_cart_rule();
            case 'woo_checkout':
                return $this->evaluate_woo_checkout_rule();
            case 'woo_account':
                return $this->evaluate_woo_account_rule();
            case 'woo_product_cat':
                return $this->evaluate_woo_product_cat_rule($value, $include_children, $include_descendants);
            default:
                return false;
        }
    }

    /**
     * Evaluate page rule with descendant support
     */
    private function evaluate_page_rule($page_id, $include_children, $include_descendants) {
        $page_id = absint($page_id);
        if ($page_id <= 0 || !is_page()) {
            return false;
        }

        $current_page_id = get_queried_object_id();

        if ($current_page_id === $page_id) {
            return true;
        }

        if ($include_descendants) {
            $ancestors = get_post_ancestors($current_page_id);
            $ancestors = array_map('absint', $ancestors);
            return in_array($page_id, $ancestors, true);
        }

        if ($include_children) {
            $current_page = get_post($current_page_id);
            return $current_page && absint($current_page->post_parent) === $page_id;
        }

        return false;
    }

    /**
     * Evaluate category rule with descendant support
     */
    private function evaluate_category_rule($cat_id, $include_children, $include_descendants) {
        $cat_id = absint($cat_id);
        if ($cat_id <= 0) {
            return false;
        }

        if (is_category()) {
            $current_cat = get_queried_object();
            if (!($current_cat instanceof WP_Term)) {
                return false;
            }

            if ($current_cat->term_id === $cat_id) {
                return true;
            }

            if ($include_descendants) {
                $ancestors = get_ancestors($current_cat->term_id, 'category');
                $ancestors = array_map('absint', $ancestors);
                return in_array($cat_id, $ancestors, true);
            }

            if ($include_children) {
                return absint($current_cat->parent) === $cat_id;
            }
        }

        if (is_single()) {
            $post_id = get_queried_object_id();
            if (!$post_id) {
                return false;
            }

            $post_categories = wp_get_post_categories($post_id);
            if (is_wp_error($post_categories) || !is_array($post_categories) || empty($post_categories)) {
                return false;
            }

            $post_categories = array_map('absint', $post_categories);
            if (in_array($cat_id, $post_categories, true)) {
                return true;
            }

            if ($include_children || $include_descendants) {
                foreach ($post_categories as $post_cat_id) {
                    $ancestors = get_ancestors($post_cat_id, 'category');
                    $ancestors = array_map('absint', $ancestors);

                    if ($include_descendants && in_array($cat_id, $ancestors, true)) {
                        return true;
                    }

                    if ($include_children) {
                        $cat = get_category($post_cat_id);
                        if ($cat && !is_wp_error($cat) && absint($cat->parent) === $cat_id) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Evaluate tag rule
     *
     * @since 2.0.0
     */
    private function evaluate_tag_rule($tag_id) {
        $tag_id = absint($tag_id);
        if ($tag_id <= 0) {
            return false;
        }

        if (is_tag()) {
            $current_tag = get_queried_object();
            if ($current_tag instanceof WP_Term && $current_tag->term_id === $tag_id) {
                return true;
            }
        }

        if (is_single()) {
            $post_id = get_queried_object_id();
            if ($post_id && has_tag($tag_id, $post_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate author rule
     *
     * @since 2.0.0
     */
    private function evaluate_author_rule($author_id) {
        $author_id = absint($author_id);
        if ($author_id <= 0) {
            return false;
        }

        if (is_author($author_id)) {
            return true;
        }

        if (is_singular()) {
            $post = get_queried_object();
            if ($post instanceof WP_Post && absint($post->post_author) === $author_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate schedule rule
     *
     * @since 2.0.0
     */
    private function evaluate_schedule_rule($start_date, $end_date) {
        $today = current_time('Y-m-d');

        if ($start_date !== '' && $today < $start_date) {
            return false;
        }

        if ($end_date !== '' && $today > $end_date) {
            return false;
        }

        if ($start_date === '' && $end_date === '') {
            return false;
        }

        return true;
    }

    /**
     * Evaluate URL parameter rule
     *
     * @since 2.0.0
     */
    private function evaluate_url_param_rule($param_name, $param_value) {
        if (!is_string($param_name) || $param_name === '') {
            return false;
        }

        $param_name = sanitize_text_field($param_name);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET[$param_name])) {
            return false;
        }

        if ($param_value === '') {
            return true;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $actual_value = sanitize_text_field(wp_unslash($_GET[$param_name]));
        return $actual_value === $param_value;
    }

    /**
     * Evaluate device rule
     *
     * @since 2.0.0
     */
    private function evaluate_device_rule($device) {
        if (!function_exists('wp_is_mobile')) {
            return false;
        }

        switch ($device) {
            case 'mobile':
                return wp_is_mobile();
            case 'desktop':
                return !wp_is_mobile();
            default:
                return false;
        }
    }

    /**
     * Evaluate WooCommerce Shop rule
     *
     * @since 2.0.0
     */
    private function evaluate_woo_shop_rule() {
        return function_exists('is_shop') && is_shop();
    }

    /**
     * Evaluate WooCommerce Cart rule
     *
     * @since 2.0.0
     */
    private function evaluate_woo_cart_rule() {
        return function_exists('is_cart') && is_cart();
    }

    /**
     * Evaluate WooCommerce Checkout rule
     *
     * @since 2.0.0
     */
    private function evaluate_woo_checkout_rule() {
        return function_exists('is_checkout') && is_checkout();
    }

    /**
     * Evaluate WooCommerce My Account rule
     *
     * @since 2.0.0
     */
    private function evaluate_woo_account_rule() {
        return function_exists('is_account_page') && is_account_page();
    }

    /**
     * Evaluate WooCommerce Product Category rule with descendant support
     *
     * @since 2.0.0
     */
    private function evaluate_woo_product_cat_rule($term_id, $include_children, $include_descendants) {
        $term_id = absint($term_id);
        if ($term_id <= 0 || !taxonomy_exists('product_cat')) {
            return false;
        }

        if (function_exists('is_product_category') && is_product_category()) {
            $current_term = get_queried_object();
            if (!($current_term instanceof WP_Term) || $current_term->taxonomy !== 'product_cat') {
                return false;
            }

            if ((int) $current_term->term_id === $term_id) {
                return true;
            }

            if ($include_descendants) {
                $ancestors = $this->get_term_ancestors($current_term->term_id, 'product_cat');
                if (in_array($term_id, $ancestors, true)) {
                    return true;
                }
            }

            if ($include_children && (int) $current_term->parent === $term_id) {
                return true;
            }
        }

        if (function_exists('is_product') && is_product()) {
            $post_id = get_queried_object_id();
            if (!$post_id) {
                return false;
            }

            $post_terms = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'ids']);
            if (is_wp_error($post_terms) || !is_array($post_terms) || empty($post_terms)) {
                return false;
            }

            $post_terms = array_map('intval', $post_terms);
            if (in_array($term_id, $post_terms, true)) {
                return true;
            }

            if ($include_children || $include_descendants) {
                foreach ($post_terms as $post_term_id) {
                    if ($include_descendants) {
                        $ancestors = $this->get_term_ancestors($post_term_id, 'product_cat');
                        if (in_array($term_id, $ancestors, true)) {
                            return true;
                        }
                    }
                    if ($include_children) {
                        $post_term = get_term($post_term_id, 'product_cat');
                        if ($post_term instanceof WP_Term && !is_wp_error($post_term) && (int) $post_term->parent === $term_id) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Evaluate post type rule
     */
    private function evaluate_post_type_rule($post_type) {
        if (!is_string($post_type) || $post_type === '' || !post_type_exists($post_type)) {
            return false;
        }

        return is_singular($post_type) || is_post_type_archive($post_type);
    }

    /**
     * Evaluate custom taxonomy rule with descendant support.
     */
    private function evaluate_taxonomy_rule($taxonomy, $term_id, $include_children, $include_descendants) {
        if (!is_string($taxonomy) || $taxonomy === '' || !taxonomy_exists($taxonomy)) {
            return false;
        }

        $term_id = absint($term_id);
        if ($term_id <= 0) {
            return false;
        }

        if (is_tax($taxonomy)) {
            $current_term = get_queried_object();
            if (!($current_term instanceof WP_Term) || $current_term->taxonomy !== $taxonomy) {
                return false;
            }

            if ((int) $current_term->term_id === $term_id) {
                return true;
            }

            if ($include_descendants) {
                $ancestors = $this->get_term_ancestors($current_term->term_id, $taxonomy);
                if (in_array($term_id, $ancestors, true)) {
                    return true;
                }
            }

            if ($include_children && (int) $current_term->parent === $term_id) {
                return true;
            }
        }

        if (is_singular()) {
            $post_id = get_queried_object_id();
            if (!$post_id) {
                return false;
            }

            $post_terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);
            if (is_wp_error($post_terms) || !is_array($post_terms) || empty($post_terms)) {
                return false;
            }

            $post_terms = array_map('intval', $post_terms);
            if (in_array($term_id, $post_terms, true)) {
                return true;
            }

            if ($include_children || $include_descendants) {
                foreach ($post_terms as $post_term_id) {
                    if ($include_descendants) {
                        $ancestors = $this->get_term_ancestors($post_term_id, $taxonomy);
                        if (in_array($term_id, $ancestors, true)) {
                            return true;
                        }
                    }

                    if ($include_children) {
                        $post_term = get_term($post_term_id, $taxonomy);
                        if ($post_term instanceof WP_Term && !is_wp_error($post_term) && (int) $post_term->parent === $term_id) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Evaluate user role rule (any selected role).
     */
    private function evaluate_user_role_rule($selected_roles) {
        if (!is_user_logged_in() || !is_array($selected_roles) || empty($selected_roles)) {
            return false;
        }

        $user = wp_get_current_user();
        if (!($user instanceof WP_User) || empty($user->ID) || !is_array($user->roles)) {
            return false;
        }

        $user_roles = array_map('sanitize_key', $user->roles);
        foreach ($selected_roles as $selected_role) {
            if (!is_scalar($selected_role)) {
                continue;
            }

            $selected_role = sanitize_key((string) $selected_role);
            if ($selected_role !== '' && in_array($selected_role, $user_roles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cached term ancestors as integers.
     *
     * @return int[]
     */
    private function get_term_ancestors($term_id, $taxonomy) {
        $term_id = absint($term_id);
        $taxonomy = sanitize_key((string) $taxonomy);

        if ($term_id <= 0 || $taxonomy === '') {
            return [];
        }

        $cache_key = $taxonomy . ':' . $term_id;
        if (isset($this->term_ancestor_cache[$cache_key])) {
            return $this->term_ancestor_cache[$cache_key];
        }

        $ancestors = get_ancestors($term_id, $taxonomy, 'taxonomy');
        $ancestors = is_array($ancestors) ? array_map('intval', $ancestors) : [];

        $this->term_ancestor_cache[$cache_key] = $ancestors;
        return $ancestors;
    }
}
