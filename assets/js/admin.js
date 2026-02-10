/**
 * Widget Visibility with Descendants - Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initVisibilityUI();
    });

    // Re-initialize when widgets are updated (AJAX)
    $(document).on('widget-updated widget-added', function(event, widget) {
        initVisibilityUI(widget);
    });

    /**
     * Initialize visibility UI for all widgets or a specific widget
     */
    function initVisibilityUI(container) {
        var $wrappers = container
            ? $(container).find('.wvd-visibility-wrapper')
            : $('.wvd-visibility-wrapper');

        $wrappers.each(function() {
            var $wrapper = $(this);

            // Skip if already initialized
            if ($wrapper.data('wvd-initialized')) {
                return;
            }

            $wrapper.data('wvd-initialized', true);
            setupWidget($wrapper);
        });
    }

    /**
     * Setup a single widget's visibility UI
     */
    function setupWidget($wrapper) {
        var $button = $wrapper.find('.wvd-visibility-button');
        var $panel = $wrapper.find('.wvd-visibility-panel');
        var $dataInput = $wrapper.find('.wvd-visibility-data');
        var $content = $wrapper.find('.wvd-visibility-content');

        // Toggle panel
        $button.on('click', function(e) {
            e.preventDefault();
            if ($panel.is(':visible')) {
                $panel.slideUp(200);
            } else {
                renderPanel($content, $dataInput);
                $panel.slideDown(200);
            }
        });
    }

    /**
     * Render the visibility panel content
     */
    function renderPanel($content, $dataInput) {
        var data = getVisibilityData($dataInput);
        var html = '';

        // Action row (Show/Hide)
        html += '<div class="wvd-action-row">';
        html += '<select class="wvd-action-select">';
        html += '<option value="show"' + (data.action === 'show' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.show) + '</option>';
        html += '<option value="hide"' + (data.action === 'hide' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.hide) + '</option>';
        html += '</select>';
        html += '<span class="wvd-rule-label">' + escapeHtml(wvdData.i18n.if) + ':</span>';
        html += '</div>';

        // Rules container
        html += '<div class="wvd-rules">';
        if (data.rules && data.rules.length > 0) {
            data.rules.forEach(function(rule, index) {
                html += renderRule(rule, index);
            });
        }
        html += '</div>';

        // Add condition link
        html += '<a href="#" class="wvd-add-rule">' + escapeHtml(wvdData.i18n.addCondition) + '</a>';

        // Match all checkbox
        html += '<div class="wvd-match-all">';
        html += '<label>';
        html += '<input type="checkbox" class="wvd-match-all-checkbox"' + (data.match_all ? ' checked' : '') + '>';
        html += ' ' + escapeHtml(wvdData.i18n.matchAll);
        html += '</label>';
        html += '</div>';

        // Footer
        html += '<div class="wvd-panel-footer">';
        html += '<a href="#" class="wvd-delete-rules">' + escapeHtml(wvdData.i18n.delete) + '</a>';
        html += '<button type="button" class="button wvd-done-button">' + escapeHtml(wvdData.i18n.done) + '</button>';
        html += '</div>';

        $content.html(html);

        // Bind events
        bindPanelEvents($content, $dataInput);
    }

    /**
     * Render a single rule
     */
    function renderRule(rule, index) {
        var normalizedRule = normalizeRule(rule);
        if (!normalizedRule) {
            normalizedRule = getDefaultRule('page');
        }

        var html = '<div class="wvd-rule" data-index="' + index + '">';

        // Remove button
        html += '<button type="button" class="wvd-rule-remove" aria-label="' + escapeHtml(wvdData.i18n.remove) + '" title="' + escapeHtml(wvdData.i18n.remove) + '">&times;</button>';

        // Type select
        html += '<select class="wvd-rule-type">';
        html += '<option value="page"' + (normalizedRule.type === 'page' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.page) + '</option>';
        html += '<option value="category"' + (normalizedRule.type === 'category' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.category) + '</option>';
        html += '<option value="post_type"' + (normalizedRule.type === 'post_type' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.postType) + '</option>';
        html += '<option value="taxonomy"' + (normalizedRule.type === 'taxonomy' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.taxonomy) + '</option>';
        html += '<option value="user_role"' + (normalizedRule.type === 'user_role' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.userRole) + '</option>';
        html += '<option value="front_page"' + (normalizedRule.type === 'front_page' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.frontPage) + '</option>';
        html += '<option value="blog"' + (normalizedRule.type === 'blog' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.blog) + '</option>';
        html += '<option value="archive"' + (normalizedRule.type === 'archive' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.archive) + '</option>';
        html += '<option value="search"' + (normalizedRule.type === 'search' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.search) + '</option>';
        html += '<option value="404"' + (normalizedRule.type === '404' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.notFound) + '</option>';
        html += '<option value="single"' + (normalizedRule.type === 'single' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.single) + '</option>';
        html += '<option value="logged_in"' + (normalizedRule.type === 'logged_in' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.loggedIn) + '</option>';
        html += '<option value="logged_out"' + (normalizedRule.type === 'logged_out' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.loggedOut) + '</option>';
        html += '</select>';

        // Label
        html += '<span class="wvd-rule-label">' + escapeHtml(wvdData.i18n.is) + '</span>';

        // Value control (depends on type)
        html += '<span class="wvd-rule-value-container">';
        html += renderValueControl(normalizedRule);
        html += '</span>';

        // Options (checkboxes)
        if (ruleSupportsHierarchyOptions(normalizedRule.type)) {
            html += renderRuleOptions(normalizedRule);
        }

        html += '</div>';
        return html;
    }

    /**
     * Render value control based on rule type
     */
    function renderValueControl(rule) {
        var items = [];
        var placeholder = '';

        switch (rule.type) {
            case 'page':
                items = Array.isArray(wvdData.pages) ? wvdData.pages : [];
                placeholder = escapeHtml(wvdData.i18n.selectPage);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'category':
                items = Array.isArray(wvdData.categories) ? wvdData.categories : [];
                placeholder = escapeHtml(wvdData.i18n.selectCategory);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'post_type':
                items = Array.isArray(wvdData.postTypes) ? wvdData.postTypes : [];
                placeholder = escapeHtml(wvdData.i18n.selectPostType);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'taxonomy':
                return renderTaxonomyValueControl(rule);

            case 'user_role':
                return renderRoleValueControl(rule);

            default:
                return '<span class="wvd-rule-value-na">—</span>';
        }
    }

    /**
     * Render single-select value control
     */
    function renderSingleValueSelect(items, placeholder, selectedValue) {
        var html = '<select class="wvd-rule-value">';
        html += '<option value="">' + placeholder + '</option>';

        items.forEach(function(item) {
            var selected = (String(selectedValue) === String(item.id)) ? ' selected' : '';
            var hasChildren = item.hasChildren ? '1' : '0';
            html += '<option value="' + escapeHtml(item.id) + '"' + selected + ' data-has-children="' + hasChildren + '">';
            html += escapeHtml(item.title);
            html += '</option>';
        });

        html += '</select>';
        return html;
    }

    /**
     * Render taxonomy selector + term selector.
     */
    function renderTaxonomyValueControl(rule) {
        var taxonomies = Array.isArray(wvdData.taxonomies) ? wvdData.taxonomies : [];
        var selectedTaxonomy = (typeof rule.taxonomy === 'string') ? rule.taxonomy : '';
        var selectedTerm = rule.value || '';
        var html = '<span class="wvd-taxonomy-control">';

        html += '<select class="wvd-rule-taxonomy">';
        html += '<option value="">' + escapeHtml(wvdData.i18n.selectTaxonomy) + '</option>';
        taxonomies.forEach(function(taxonomy) {
            var selected = (selectedTaxonomy === taxonomy.id) ? ' selected' : '';
            html += '<option value="' + escapeHtml(taxonomy.id) + '"' + selected + '>';
            html += escapeHtml(taxonomy.title);
            html += '</option>';
        });
        html += '</select>';

        html += renderTaxonomyTermSelect(selectedTaxonomy, selectedTerm);
        html += '</span>';

        return html;
    }

    /**
     * Render term selector for selected taxonomy.
     */
    function renderTaxonomyTermSelect(taxonomy, selectedTerm) {
        var termsMap = (wvdData && wvdData.taxonomyTerms) ? wvdData.taxonomyTerms : {};
        var terms = (taxonomy && Array.isArray(termsMap[taxonomy])) ? termsMap[taxonomy] : [];
        var html = '<select class="wvd-rule-value">';
        html += '<option value="">' + escapeHtml(wvdData.i18n.selectTerm) + '</option>';

        terms.forEach(function(term) {
            var selected = (String(selectedTerm) === String(term.id)) ? ' selected' : '';
            var hasChildren = term.hasChildren ? '1' : '0';
            html += '<option value="' + escapeHtml(term.id) + '"' + selected + ' data-has-children="' + hasChildren + '">';
            html += escapeHtml(term.title);
            html += '</option>';
        });

        html += '</select>';
        return html;
    }

    /**
     * Render multi-role selector.
     */
    function renderRoleValueControl(rule) {
        var roles = Array.isArray(wvdData.roles) ? wvdData.roles : [];
        var selectedRoles = Array.isArray(rule.values) ? rule.values.map(String) : [];
        var html = '<select class="wvd-rule-values" multiple="multiple" size="4">';

        if (roles.length === 0) {
            html += '<option value="" disabled="disabled">' + escapeHtml(wvdData.i18n.selectRoles) + '</option>';
        }

        roles.forEach(function(role) {
            var selected = selectedRoles.indexOf(String(role.id)) !== -1 ? ' selected' : '';
            html += '<option value="' + escapeHtml(role.id) + '"' + selected + '>';
            html += escapeHtml(role.title);
            html += '</option>';
        });

        html += '</select>';
        return html;
    }

    /**
     * Render rule options (checkboxes)
     */
    function renderRuleOptions(rule) {
        var html = '<div class="wvd-rule-options">';

        // Include children
        html += '<label>';
        html += '<input type="checkbox" class="wvd-include-children"' + (rule.include_children ? ' checked' : '') + '>';
        html += ' ' + escapeHtml(wvdData.i18n.includeChildren);
        html += '</label>';

        // Include all descendants
        html += '<label class="wvd-descendants-option">';
        html += '<input type="checkbox" class="wvd-include-descendants"' + (rule.include_descendants ? ' checked' : '') + '>';
        html += ' ' + escapeHtml(wvdData.i18n.includeDescendants);
        html += '</label>';

        html += '</div>';
        return html;
    }

    /**
     * Bind events to panel elements
     */
    function bindPanelEvents($content, $dataInput) {
        var $wrapper = $content.closest('.wvd-visibility-wrapper');
        var $panel = $wrapper.find('.wvd-visibility-panel');

        // Action change
        $content.on('change', '.wvd-action-select', function() {
            updateData($content, $dataInput);
        });

        // Rule type change
        $content.on('change', '.wvd-rule-type', function() {
            var $rule = $(this).closest('.wvd-rule');
            var type = $(this).val();
            var defaultRule = getDefaultRule(type);

            // Update value control
            $rule.find('.wvd-rule-value-container').replaceWith(
                '<span class="wvd-rule-value-container">' + renderValueControl(defaultRule) + '</span>'
            );

            // Update options
            var $optionsContainer = $rule.find('.wvd-rule-options');
            if (ruleSupportsHierarchyOptions(type)) {
                if ($optionsContainer.length === 0) {
                    $rule.append(renderRuleOptions(defaultRule));
                } else {
                    $optionsContainer.replaceWith(renderRuleOptions(defaultRule));
                }
            } else {
                $optionsContainer.remove();
            }

            updateData($content, $dataInput);
        });

        // Taxonomy selector change
        $content.on('change', '.wvd-rule-taxonomy', function() {
            var $taxonomySelect = $(this);
            var taxonomy = $taxonomySelect.val() || '';
            var $taxonomyControl = $taxonomySelect.closest('.wvd-taxonomy-control');
            $taxonomyControl.find('.wvd-rule-value').replaceWith(renderTaxonomyTermSelect(taxonomy, ''));
            updateData($content, $dataInput);
        });

        // Value changes
        $content.on('change', '.wvd-rule-value, .wvd-rule-values', function() {
            updateData($content, $dataInput);
        });

        // Checkbox changes
        $content.on('change', '.wvd-include-children, .wvd-include-descendants', function() {
            var $this = $(this);
            var $rule = $this.closest('.wvd-rule');

            // If descendants is checked, also check children
            if ($this.hasClass('wvd-include-descendants') && $this.is(':checked')) {
                $rule.find('.wvd-include-children').prop('checked', true);
            }

            // If children is unchecked, also uncheck descendants
            if ($this.hasClass('wvd-include-children') && !$this.is(':checked')) {
                $rule.find('.wvd-include-descendants').prop('checked', false);
            }

            updateData($content, $dataInput);
        });

        // Match all change
        $content.on('change', '.wvd-match-all-checkbox', function() {
            updateData($content, $dataInput);
        });

        // Add rule
        $content.on('click', '.wvd-add-rule', function(e) {
            e.preventDefault();
            var $rules = $content.find('.wvd-rules');
            var index = $rules.find('.wvd-rule').length;
            $rules.append(renderRule(getDefaultRule('page'), index));
            updateData($content, $dataInput);
        });

        // Remove rule
        $content.on('click', '.wvd-rule-remove', function(e) {
            e.preventDefault();
            $(this).closest('.wvd-rule').remove();
            updateData($content, $dataInput);
        });

        // Delete all rules
        $content.on('click', '.wvd-delete-rules', function(e) {
            e.preventDefault();
            $content.find('.wvd-rules').empty();
            updateData($content, $dataInput);
            updateStatus($wrapper, false);
        });

        // Done button
        $content.on('click', '.wvd-done-button', function(e) {
            e.preventDefault();
            $panel.slideUp(200);
            var data = getVisibilityData($dataInput);
            updateStatus($wrapper, data.rules && data.rules.length > 0);
        });
    }

    /**
     * Update the hidden data input
     */
    function updateData($content, $dataInput) {
        var data = {
            action: $content.find('.wvd-action-select').val() || 'show',
            match_all: $content.find('.wvd-match-all-checkbox').is(':checked'),
            rules: []
        };

        $content.find('.wvd-rule').each(function() {
            var $rule = $(this);
            var type = $rule.find('.wvd-rule-type').val();
            var rule = {
                type: type,
                value: '',
                include_children: $rule.find('.wvd-include-children').is(':checked'),
                include_descendants: $rule.find('.wvd-include-descendants').is(':checked')
            };

            if (type === 'taxonomy') {
                rule.taxonomy = $rule.find('.wvd-rule-taxonomy').val() || '';
                rule.value = $rule.find('.wvd-rule-value').val() || '';
            } else if (type === 'user_role') {
                var roleValues = $rule.find('.wvd-rule-values').val();
                roleValues = Array.isArray(roleValues) ? roleValues : [];
                rule.values = roleValues.filter(function(roleValue) {
                    return roleValue !== '';
                });
                rule.value = '';
                rule.include_children = false;
                rule.include_descendants = false;
            } else if ($rule.find('.wvd-rule-value').length) {
                rule.value = $rule.find('.wvd-rule-value').val() || '';
            }

            data.rules.push(rule);
        });

        $dataInput.val(JSON.stringify(data));

        // Trigger change to mark widget as needing save
        $dataInput.trigger('change');
    }

    /**
     * Get visibility data from input and normalize for rendering.
     */
    function getVisibilityData($dataInput) {
        var fallback = { action: 'show', match_all: false, rules: [] };
        var val = $dataInput.val();

        if (!val) {
            return fallback;
        }

        try {
            var parsed = JSON.parse(val);
            if (!parsed || typeof parsed !== 'object') {
                return fallback;
            }

            var normalized = {
                action: parsed.action === 'hide' ? 'hide' : 'show',
                match_all: !!parsed.match_all,
                rules: []
            };

            if (Array.isArray(parsed.rules)) {
                parsed.rules.forEach(function(rule) {
                    var normalizedRule = normalizeRule(rule);
                    if (normalizedRule) {
                        normalized.rules.push(normalizedRule);
                    }
                });
            }

            return normalized;
        } catch (e) {
            return fallback;
        }
    }

    /**
     * Normalize any stored rule object for UI rendering.
     */
    function normalizeRule(rule) {
        if (!rule || typeof rule !== 'object') {
            return null;
        }

        var type = (typeof rule.type === 'string' && rule.type !== '') ? rule.type : 'page';
        var normalized = getDefaultRule(type);

        if (Object.prototype.hasOwnProperty.call(rule, 'value') && rule.value !== null && typeof rule.value !== 'undefined') {
            normalized.value = String(rule.value);
        }

        normalized.include_children = !!rule.include_children;
        normalized.include_descendants = !!rule.include_descendants;

        if (normalized.include_descendants) {
            normalized.include_children = true;
        }

        if (type === 'taxonomy') {
            normalized.taxonomy = (typeof rule.taxonomy === 'string') ? rule.taxonomy : '';
        }

        if (type === 'user_role') {
            if (Array.isArray(rule.values)) {
                normalized.values = rule.values.map(function(value) {
                    return String(value);
                });
            } else if (typeof rule.value === 'string' && rule.value !== '') {
                normalized.values = [rule.value];
            } else {
                normalized.values = [];
            }
            normalized.value = '';
            normalized.include_children = false;
            normalized.include_descendants = false;
        }

        if (!ruleSupportsHierarchyOptions(type)) {
            normalized.include_children = false;
            normalized.include_descendants = false;
        }

        return normalized;
    }

    /**
     * Create default rule payload.
     */
    function getDefaultRule(type) {
        var defaultType = type || 'page';
        var rule = {
            type: defaultType,
            value: '',
            include_children: false,
            include_descendants: false
        };

        if (defaultType === 'taxonomy') {
            rule.taxonomy = '';
        }

        if (defaultType === 'user_role') {
            rule.values = [];
        }

        return rule;
    }

    /**
     * Rule types that support child/descendant options.
     */
    function ruleSupportsHierarchyOptions(type) {
        return type === 'page' || type === 'category' || type === 'taxonomy';
    }

    /**
     * Update status indicator
     */
    function updateStatus($wrapper, hasRules) {
        var $status = $wrapper.find('.wvd-visibility-status');
        if (hasRules) {
            if ($status.length === 0) {
                $wrapper.find('.wvd-visibility-toggle').append(
                    '<span class="wvd-visibility-status wvd-has-rules">' + escapeHtml(wvdData.i18n.configured) + '</span>'
                );
            } else {
                $status.addClass('wvd-has-rules').text(wvdData.i18n.configured);
            }
        } else {
            $status.remove();
        }
    }

    /**
     * Escape HTML securely
     */
    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

})(jQuery);
