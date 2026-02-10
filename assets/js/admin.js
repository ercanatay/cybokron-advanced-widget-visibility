/**
 * Widget Visibility with Descendants - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initVisibilityUI();
    });

    $(document).on('widget-updated widget-added', function(event, widget) {
        initVisibilityUI(widget);
    });

    function initVisibilityUI(container) {
        var $wrappers = container
            ? $(container).find('.wvd-visibility-wrapper')
            : $('.wvd-visibility-wrapper');

        $wrappers.each(function() {
            var $wrapper = $(this);
            if ($wrapper.data('wvd-initialized')) {
                return;
            }
            $wrapper.data('wvd-initialized', true);
            setupWidget($wrapper);
        });
    }

    function setupWidget($wrapper) {
        var $button = $wrapper.find('.wvd-visibility-button');
        var $panel = $wrapper.find('.wvd-visibility-panel');
        var $dataInput = $wrapper.find('.wvd-visibility-data');
        var $content = $wrapper.find('.wvd-visibility-content');

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

    function renderPanel($content, $dataInput) {
        var data = getVisibilityData($dataInput);
        var html = '';

        // Action row
        html += '<div class="wvd-action-row">';
        html += '<select class="wvd-action-select">';
        html += '<option value="show"' + (data.action === 'show' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.show) + '</option>';
        html += '<option value="hide"' + (data.action === 'hide' ? ' selected' : '') + '>' + escapeHtml(wvdData.i18n.hide) + '</option>';
        html += '</select>';
        html += '<span class="wvd-rule-label">' + escapeHtml(wvdData.i18n['if']) + ':</span>';
        html += '</div>';

        // Rules
        html += '<div class="wvd-rules">';
        if (data.rules && data.rules.length > 0) {
            data.rules.forEach(function(rule, index) {
                html += renderRule(rule, index);
            });
        }
        html += '</div>';

        // Add condition
        html += '<button type="button" class="wvd-add-rule">' + escapeHtml(wvdData.i18n.addCondition) + '</button>';

        // Match all
        html += '<div class="wvd-match-all">';
        html += '<label>';
        html += '<input type="checkbox" class="wvd-match-all-checkbox"' + (data.match_all ? ' checked' : '') + '>';
        html += ' ' + escapeHtml(wvdData.i18n.matchAll);
        html += '</label>';
        html += '</div>';

        // Presets section
        html += '<div class="wvd-presets-section">';
        html += '<button type="button" class="wvd-save-preset">' + escapeHtml(wvdData.i18n.savePreset) + '</button>';
        html += '<button type="button" class="wvd-load-preset">' + escapeHtml(wvdData.i18n.loadPreset) + '</button>';
        html += '<div class="wvd-preset-list" style="display:none;"></div>';
        html += '</div>';

        // Footer
        html += '<div class="wvd-panel-footer">';
        html += '<button type="button" class="wvd-delete-rules">' + escapeHtml(wvdData.i18n['delete']) + '</button>';
        html += '<button type="button" class="button wvd-done-button">' + escapeHtml(wvdData.i18n.done) + '</button>';
        html += '</div>';

        $content.off('.wvd');
        $content.html(html);
        bindPanelEvents($content, $dataInput);

        // Init datepickers
        $content.find('.wvd-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }

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
        html += '<option value="page"' + sel(normalizedRule.type, 'page') + '>' + escapeHtml(wvdData.i18n.page) + '</option>';
        html += '<option value="category"' + sel(normalizedRule.type, 'category') + '>' + escapeHtml(wvdData.i18n.category) + '</option>';
        html += '<option value="tag"' + sel(normalizedRule.type, 'tag') + '>' + escapeHtml(wvdData.i18n.tag) + '</option>';
        html += '<option value="author"' + sel(normalizedRule.type, 'author') + '>' + escapeHtml(wvdData.i18n.author) + '</option>';
        html += '<option value="post_type"' + sel(normalizedRule.type, 'post_type') + '>' + escapeHtml(wvdData.i18n.postType) + '</option>';
        html += '<option value="taxonomy"' + sel(normalizedRule.type, 'taxonomy') + '>' + escapeHtml(wvdData.i18n.taxonomy) + '</option>';
        html += '<option value="user_role"' + sel(normalizedRule.type, 'user_role') + '>' + escapeHtml(wvdData.i18n.userRole) + '</option>';
        html += '<option value="schedule"' + sel(normalizedRule.type, 'schedule') + '>' + escapeHtml(wvdData.i18n.schedule) + '</option>';
        html += '<option value="url_param"' + sel(normalizedRule.type, 'url_param') + '>' + escapeHtml(wvdData.i18n.urlParam) + '</option>';
        html += '<option value="device"' + sel(normalizedRule.type, 'device') + '>' + escapeHtml(wvdData.i18n.device) + '</option>';
        html += '<option value="front_page"' + sel(normalizedRule.type, 'front_page') + '>' + escapeHtml(wvdData.i18n.frontPage) + '</option>';
        html += '<option value="blog"' + sel(normalizedRule.type, 'blog') + '>' + escapeHtml(wvdData.i18n.blog) + '</option>';
        html += '<option value="archive"' + sel(normalizedRule.type, 'archive') + '>' + escapeHtml(wvdData.i18n.archive) + '</option>';
        html += '<option value="search"' + sel(normalizedRule.type, 'search') + '>' + escapeHtml(wvdData.i18n.search) + '</option>';
        html += '<option value="404"' + sel(normalizedRule.type, '404') + '>' + escapeHtml(wvdData.i18n.notFound) + '</option>';
        html += '<option value="single"' + sel(normalizedRule.type, 'single') + '>' + escapeHtml(wvdData.i18n.single) + '</option>';
        html += '<option value="logged_in"' + sel(normalizedRule.type, 'logged_in') + '>' + escapeHtml(wvdData.i18n.loggedIn) + '</option>';
        html += '<option value="logged_out"' + sel(normalizedRule.type, 'logged_out') + '>' + escapeHtml(wvdData.i18n.loggedOut) + '</option>';

        // WooCommerce options
        if (wvdData.hasWooCommerce) {
            html += '<option value="woo_shop"' + sel(normalizedRule.type, 'woo_shop') + '>' + escapeHtml(wvdData.i18n.wooShop) + '</option>';
            html += '<option value="woo_cart"' + sel(normalizedRule.type, 'woo_cart') + '>' + escapeHtml(wvdData.i18n.wooCart) + '</option>';
            html += '<option value="woo_checkout"' + sel(normalizedRule.type, 'woo_checkout') + '>' + escapeHtml(wvdData.i18n.wooCheckout) + '</option>';
            html += '<option value="woo_account"' + sel(normalizedRule.type, 'woo_account') + '>' + escapeHtml(wvdData.i18n.wooAccount) + '</option>';
            html += '<option value="woo_product_cat"' + sel(normalizedRule.type, 'woo_product_cat') + '>' + escapeHtml(wvdData.i18n.wooProductCat) + '</option>';
        }

        html += '</select>';

        // Label
        html += '<span class="wvd-rule-label">' + escapeHtml(wvdData.i18n.is) + '</span>';

        // Value control
        html += '<span class="wvd-rule-value-container">';
        html += renderValueControl(normalizedRule);
        html += '</span>';

        // Options
        if (ruleSupportsHierarchyOptions(normalizedRule.type)) {
            html += renderRuleOptions(normalizedRule);
        }

        html += '</div>';
        return html;
    }

    function renderValueControl(rule) {
        var items, placeholder;

        switch (rule.type) {
            case 'page':
                items = Array.isArray(wvdData.pages) ? wvdData.pages : [];
                placeholder = escapeHtml(wvdData.i18n.selectPage);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'category':
                items = Array.isArray(wvdData.categories) ? wvdData.categories : [];
                placeholder = escapeHtml(wvdData.i18n.selectCategory);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'tag':
                items = Array.isArray(wvdData.tags) ? wvdData.tags : [];
                placeholder = escapeHtml(wvdData.i18n.selectTag);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'author':
                items = Array.isArray(wvdData.authors) ? wvdData.authors : [];
                placeholder = escapeHtml(wvdData.i18n.selectAuthor);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'post_type':
                items = Array.isArray(wvdData.postTypes) ? wvdData.postTypes : [];
                placeholder = escapeHtml(wvdData.i18n.selectPostType);
                return renderSingleValueSelect(items, placeholder, rule.value);

            case 'taxonomy':
                return renderTaxonomyValueControl(rule);

            case 'user_role':
                return renderRoleValueControl(rule);

            case 'schedule':
                return renderScheduleControl(rule);

            case 'url_param':
                return renderUrlParamControl(rule);

            case 'device':
                return renderDeviceControl(rule);

            case 'woo_product_cat':
                items = (wvdData.wooProductCategories && Array.isArray(wvdData.wooProductCategories)) ? wvdData.wooProductCategories : [];
                placeholder = escapeHtml(wvdData.i18n.selectWooCategory);
                return renderSingleValueSelect(items, placeholder, rule.value);

            default:
                return '<span class="wvd-rule-value-na">&mdash;</span>';
        }
    }

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

    function renderScheduleControl(rule) {
        var startDate = rule.start_date || '';
        var endDate = rule.end_date || '';
        var html = '<span class="wvd-schedule-control">';
        html += '<label class="wvd-schedule-label">' + escapeHtml(wvdData.i18n.startDate) + '</label>';
        html += '<input type="text" class="wvd-datepicker wvd-schedule-start" value="' + escapeHtml(startDate) + '" placeholder="YYYY-MM-DD" autocomplete="off">';
        html += '<label class="wvd-schedule-label">' + escapeHtml(wvdData.i18n.endDate) + '</label>';
        html += '<input type="text" class="wvd-datepicker wvd-schedule-end" value="' + escapeHtml(endDate) + '" placeholder="YYYY-MM-DD" autocomplete="off">';
        html += '</span>';
        return html;
    }

    function renderUrlParamControl(rule) {
        var paramName = rule.param_name || '';
        var paramValue = rule.param_value || '';
        var html = '<span class="wvd-url-param-control">';
        html += '<input type="text" class="wvd-param-name" value="' + escapeHtml(paramName) + '" placeholder="' + escapeHtml(wvdData.i18n.paramName) + '">';
        html += '<span class="wvd-rule-label">=</span>';
        html += '<input type="text" class="wvd-param-value" value="' + escapeHtml(paramValue) + '" placeholder="' + escapeHtml(wvdData.i18n.paramValue) + '">';
        html += '</span>';
        return html;
    }

    function renderDeviceControl(rule) {
        var html = '<select class="wvd-rule-value">';
        html += '<option value="">' + escapeHtml(wvdData.i18n.selectDevice) + '</option>';
        html += '<option value="mobile"' + sel(rule.value, 'mobile') + '>' + escapeHtml(wvdData.i18n.mobile) + '</option>';
        html += '<option value="desktop"' + sel(rule.value, 'desktop') + '>' + escapeHtml(wvdData.i18n.desktop) + '</option>';
        html += '</select>';
        return html;
    }

    function renderRuleOptions(rule) {
        var html = '<div class="wvd-rule-options">';
        html += '<label>';
        html += '<input type="checkbox" class="wvd-include-children"' + (rule.include_children ? ' checked' : '') + '>';
        html += ' ' + escapeHtml(wvdData.i18n.includeChildren);
        html += '</label>';
        html += '<label class="wvd-descendants-option">';
        html += '<input type="checkbox" class="wvd-include-descendants"' + (rule.include_descendants ? ' checked' : '') + '>';
        html += ' ' + escapeHtml(wvdData.i18n.includeDescendants);
        html += '</label>';
        html += '</div>';
        return html;
    }

    function bindPanelEvents($content, $dataInput) {
        var $wrapper = $content.closest('.wvd-visibility-wrapper');
        var $panel = $wrapper.find('.wvd-visibility-panel');

        $content.on('change.wvd', '.wvd-action-select', function() {
            updateData($content, $dataInput);
        });

        $content.on('change.wvd', '.wvd-rule-type', function() {
            var $rule = $(this).closest('.wvd-rule');
            var type = $(this).val();
            var defaultRule = getDefaultRule(type);

            $rule.find('.wvd-rule-value-container').replaceWith(
                '<span class="wvd-rule-value-container">' + renderValueControl(defaultRule) + '</span>'
            );

            // Init datepickers for schedule
            if (type === 'schedule') {
                $rule.find('.wvd-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }

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

        $content.on('change.wvd', '.wvd-rule-taxonomy', function() {
            var $taxonomySelect = $(this);
            var taxonomy = $taxonomySelect.val() || '';
            var $taxonomyControl = $taxonomySelect.closest('.wvd-taxonomy-control');
            $taxonomyControl.find('.wvd-rule-value').replaceWith(renderTaxonomyTermSelect(taxonomy, ''));
            updateData($content, $dataInput);
        });

        $content.on('change.wvd', '.wvd-rule-value, .wvd-rule-values', function() {
            updateData($content, $dataInput);
        });

        // Schedule and URL param input changes
        $content.on('change.wvd input.wvd', '.wvd-schedule-start, .wvd-schedule-end, .wvd-param-name, .wvd-param-value', function() {
            updateData($content, $dataInput);
        });

        $content.on('change.wvd', '.wvd-include-children, .wvd-include-descendants', function() {
            var $this = $(this);
            var $rule = $this.closest('.wvd-rule');

            if ($this.hasClass('wvd-include-descendants') && $this.is(':checked')) {
                $rule.find('.wvd-include-children').prop('checked', true);
            }
            if ($this.hasClass('wvd-include-children') && !$this.is(':checked')) {
                $rule.find('.wvd-include-descendants').prop('checked', false);
            }

            updateData($content, $dataInput);
        });

        $content.on('change.wvd', '.wvd-match-all-checkbox', function() {
            updateData($content, $dataInput);
        });

        $content.on('click.wvd', '.wvd-add-rule', function() {
            var $rules = $content.find('.wvd-rules');
            var index = $rules.find('.wvd-rule').length;
            $rules.append(renderRule(getDefaultRule('page'), index));
            updateData($content, $dataInput);
        });

        $content.on('click.wvd', '.wvd-rule-remove', function() {
            $(this).closest('.wvd-rule').remove();
            updateData($content, $dataInput);
        });

        $content.on('click.wvd', '.wvd-delete-rules', function() {
            $content.find('.wvd-rules').empty();
            updateData($content, $dataInput);
            updateStatus($wrapper, false);
        });

        $content.on('click.wvd', '.wvd-done-button', function(e) {
            e.preventDefault();
            $panel.slideUp(200);
            var data = getVisibilityData($dataInput);
            updateStatus($wrapper, data.rules && data.rules.length > 0);
        });

        // Preset: Save
        $content.on('click.wvd', '.wvd-save-preset', function() {
            var name = prompt(wvdData.i18n.enterPresetName);
            if (!name || name.trim() === '') return;
            var data = $dataInput.val();
            $.post(wvdData.ajaxUrl, {
                action: 'wvd_save_preset',
                nonce: wvdData.presetsNonce,
                preset_name: name.trim(),
                preset_data: data
            }, function(response) {
                if (response.success) {
                    alert(wvdData.i18n.presetSaved);
                }
            });
        });

        // Preset: Load
        $content.on('click.wvd', '.wvd-load-preset', function() {
            var $list = $content.find('.wvd-preset-list');
            if ($list.is(':visible')) {
                $list.slideUp(200);
                return;
            }
            $list.html('<em>Loading...</em>').slideDown(200);
            $.post(wvdData.ajaxUrl, {
                action: 'wvd_load_presets',
                nonce: wvdData.presetsNonce
            }, function(response) {
                if (!response.success || !response.data.presets.length) {
                    $list.html('<em>' + escapeHtml(wvdData.i18n.noPresets) + '</em>');
                    return;
                }
                var html = '';
                response.data.presets.forEach(function(preset) {
                    html += '<div class="wvd-preset-item">';
                    html += '<button type="button" class="wvd-preset-apply" data-preset=\'' + escapeHtml(JSON.stringify(preset.data)) + '\'>' + escapeHtml(preset.name) + '</button>';
                    html += '<button type="button" class="wvd-preset-delete" data-name="' + escapeHtml(preset.name) + '">&times;</button>';
                    html += '</div>';
                });
                $list.html(html);
            });
        });

        // Preset: Apply
        $content.on('click.wvd', '.wvd-preset-apply', function() {
            var presetData = $(this).attr('data-preset');
            $dataInput.val(presetData);
            renderPanel($content, $dataInput);
        });

        // Preset: Delete
        $content.on('click.wvd', '.wvd-preset-delete', function() {
            var name = $(this).data('name');
            var $item = $(this).closest('.wvd-preset-item');
            $.post(wvdData.ajaxUrl, {
                action: 'wvd_delete_preset',
                nonce: wvdData.presetsNonce,
                preset_name: name
            }, function(response) {
                if (response.success) {
                    $item.remove();
                }
            });
        });
    }

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
                rule.values = roleValues.filter(function(v) { return v !== ''; });
                rule.value = '';
                rule.include_children = false;
                rule.include_descendants = false;
            } else if (type === 'schedule') {
                rule.start_date = $rule.find('.wvd-schedule-start').val() || '';
                rule.end_date = $rule.find('.wvd-schedule-end').val() || '';
                rule.value = '';
                rule.include_children = false;
                rule.include_descendants = false;
            } else if (type === 'url_param') {
                rule.param_name = $rule.find('.wvd-param-name').val() || '';
                rule.param_value = $rule.find('.wvd-param-value').val() || '';
                rule.value = '';
                rule.include_children = false;
                rule.include_descendants = false;
            } else if ($rule.find('.wvd-rule-value').length) {
                rule.value = $rule.find('.wvd-rule-value').val() || '';
            }

            data.rules.push(rule);
        });

        $dataInput.val(JSON.stringify(data));
        $dataInput.trigger('change');
    }

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
                normalized.values = rule.values.map(function(v) { return String(v); });
            } else if (typeof rule.value === 'string' && rule.value !== '') {
                normalized.values = [rule.value];
            } else {
                normalized.values = [];
            }
            normalized.value = '';
            normalized.include_children = false;
            normalized.include_descendants = false;
        }

        if (type === 'schedule') {
            normalized.start_date = (typeof rule.start_date === 'string') ? rule.start_date : '';
            normalized.end_date = (typeof rule.end_date === 'string') ? rule.end_date : '';
        }

        if (type === 'url_param') {
            normalized.param_name = (typeof rule.param_name === 'string') ? rule.param_name : '';
            normalized.param_value = (typeof rule.param_value === 'string') ? rule.param_value : '';
        }

        if (!ruleSupportsHierarchyOptions(type)) {
            normalized.include_children = false;
            normalized.include_descendants = false;
        }

        return normalized;
    }

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
        if (defaultType === 'schedule') {
            rule.start_date = '';
            rule.end_date = '';
        }
        if (defaultType === 'url_param') {
            rule.param_name = '';
            rule.param_value = '';
        }

        return rule;
    }

    function ruleSupportsHierarchyOptions(type) {
        return type === 'page' || type === 'category' || type === 'taxonomy' || type === 'woo_product_cat';
    }

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

    function sel(current, value) {
        return current === value ? ' selected' : '';
    }

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
