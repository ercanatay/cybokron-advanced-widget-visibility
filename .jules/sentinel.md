# Sentinel Security Journal

## 2026-01-27 - Missing capability check in widget save callback
**Vulnerability:** `save_visibility_settings()` in `class-visibility-admin.php` lacked an independent `current_user_can()` check before saving widget visibility data.
**Learning:** WordPress's `widget_update_callback` filter relies on the calling code (typically `wp_ajax_save_widget`) to check capabilities. If any plugin or custom code triggers this filter outside the standard widget save flow, the capability check is bypassed.
**Prevention:** Always add defense-in-depth capability checks in WordPress filter/action callbacks that modify data, even when the standard WordPress flow already checks capabilities. Use `current_user_can('edit_theme_options')` for widget-related operations.
