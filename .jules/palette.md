## 2025-02-10 - Icon-only Link Buttons
**Learning:** This codebase frequently uses `<a href="#">` for interactive elements that should be `<button>` tags, particularly for icon-only actions like "Remove". This breaks accessibility for screen readers and keyboard users.
**Action:** When touching UI components in this repo, check for anchor tags acting as buttons and convert them to semantic `<button type="button">` elements with appropriate `aria-label` attributes.
