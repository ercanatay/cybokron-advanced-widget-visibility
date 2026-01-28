## 2025-02-17 - Inconsistent Input Sanitization
**Vulnerability:** Usage of generic `sanitize_text_field` for typed inputs (integers, slugs) and native PHP `stripslashes` instead of WordPress `wp_unslash`.
**Learning:** The codebase relies on generic text sanitization even for structured data like IDs, which allows potentially malicious strings to persist in database (though sanitized of HTML). Also, `taxonomy` and `author` rule types are defined in Admin but unimplemented in Frontend, creating a disconnect.
**Prevention:** Enforce strict type validation (intval for IDs, sanitize_key for slugs) at the input boundary (`sanitize_visibility_data`). Use `wp_unslash` for consistency with WP environment.
