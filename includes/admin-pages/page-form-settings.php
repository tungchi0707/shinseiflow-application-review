<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Form_Settings_Trait {
    public function render_form_settings() {
        $fields = self::get_fields();
        $sections = self::get_sections();
        $consent_items = self::get_consent_items();
        $type_labels = array(
            'text' => __('Text', 'shinseiflow-application-review'),
            'textarea' => __('Textarea', 'shinseiflow-application-review'),
            'email' => __('Email', 'shinseiflow-application-review'),
            'url' => 'URL',
            'tel' => __('Phone Number', 'shinseiflow-application-review'),
            'date' => __('Date', 'shinseiflow-application-review'),
            'checkbox' => __('Checkbox', 'shinseiflow-application-review'),
            'file' => __('File Upload', 'shinseiflow-application-review'),
            'dropdown' => __('Dropdown', 'shinseiflow-application-review'),
        );
        $supported_languages = self::supported_languages();
        $enabled_languages = self::get_enabled_languages(false);
        $form_languages = array('ja' => $supported_languages['ja']);
        foreach ($enabled_languages as $lang => $label) {
            if ($lang !== 'ja') {
                $form_languages[$lang] = $label;
            }
        }
        $translation_languages = $enabled_languages;
        unset($translation_languages['ja']);
        wp_enqueue_script('tcarm-admin-form-field-translation', self::plugin_url() . 'assets/js/admin-form-field-translation.js', array(), self::VERSION, true);
        wp_localize_script(
            'tcarm-admin-form-field-translation',
            'tcarmFormFieldAiTranslate',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('tcarm_ai_translate_strings'),
                'i18n'    => array(
                    'aiSelectTargetLanguage' => __('Please select the target language.', 'shinseiflow-application-review'),
                    'aiNoEmptyTargets' => __('There are no empty fields to translate.', 'shinseiflow-application-review'),
                    'aiTranslating' => __('Translating...', 'shinseiflow-application-review'),
                    'aiFailed' => __('AI translation failed.', 'shinseiflow-application-review'),
                    'aiFilledCurrentLanguage' => __('Translations were inserted into empty fields in the current language tab. Please review before saving.', 'shinseiflow-application-review'),
                    'aiNoFillableTargets' => __('There were no empty fields available for translation.', 'shinseiflow-application-review'),
                ),
            )
        );
        $grouped = array();
        foreach ($sections as $section_key => $section) {
            $grouped[$section_key] = array();
        }
        foreach ($fields as $key => $field) {
            $section_key = self::normalize_section_key(isset($field['section']) ? $field['section'] : 'event');
            if (!isset($grouped[$section_key])) {
                $grouped[$section_key] = array();
            }
            $grouped[$section_key][$key] = $field;
        }
        $this->open_admin_wrap(__('Form Settings', 'shinseiflow-application-review'));
        ?>
            <form method="post" action="options.php" class="tcarm-form-settings-form">
                <?php settings_fields('tcarm_fields_group'); ?>
                <div class="tcarm-panel tcarm-card-panel tcarm-full-panel">
                    <div class="tcarm-panel-header">
                        <div class="tcarm-panel-title-block">
                            <h2><?php echo esc_html__('Application Form Fields', 'shinseiflow-application-review'); ?></h2>
                            <p><?php echo esc_html__('Large cards are form sections, and small cards are individual fields. They are arranged to match the frontend flow with earlier items on the left.', 'shinseiflow-application-review'); ?></p>
                        </div>
                        <span class="tcarm-version-pill">v<?php echo esc_html(self::VERSION); ?></span>
                    </div>

                    <div class="tcarm-form-field-language-card tcarm-lang-page-settings-card">
                        <div class="tcarm-translation-toolbar tcarm-form-field-translation-toolbar">
                            <div class="tcarm-display-tabs tcarm-form-field-lang-tabs" role="tablist" aria-label="<?php echo esc_attr__('Form Field Language Switcher', 'shinseiflow-application-review'); ?>">
                                <?php $i = 0; foreach ($form_languages as $lang => $label): ?>
                                    <button type="button" class="<?php echo esc_attr('tcarm-display-tab tcarm-form-field-lang-tab' . ($i === 0 ? ' is-active' : '')); ?>" data-tcarm-form-field-lang="<?php echo esc_attr($lang); ?>" role="tab" aria-selected="<?php echo esc_attr($i === 0 ? 'true' : 'false'); ?>"><?php echo esc_html($label); ?></button>
                                <?php $i++; endforeach; ?>
                            </div>
                            <div class="tcarm-ai-translate-action" id="tcarm-form-field-ai-translate-action" hidden>
                                <button type="button" class="button button-secondary" id="tcarm-form-field-ai-translate-button"><?php echo esc_html__('Translate from Japanese', 'shinseiflow-application-review'); ?></button>
                                <span class="spinner" id="tcarm-form-field-ai-translate-spinner"></span>
                            </div>
                        </div>
                        <p class="description"><?php echo esc_html__('Japanese is the primary form configuration. Other languages only set section names, display labels, placeholders, and descriptions for the same field structure.', 'shinseiflow-application-review'); ?></p>
                        <p class="tcarm-ai-translate-message" id="tcarm-form-field-ai-translate-message" aria-live="polite"></p>
                    </div>
                    <div class="tcarm-form-field-lang-panel is-active" data-tcarm-form-field-panel="ja">
                    <div class="tcarm-section-editor tcarm-sortable-sections">
                        <?php if (empty($sections)): ?>
                            <div class="tcarm-empty-section"><?php echo esc_html__('No form fields have been configured yet.', 'shinseiflow-application-review'); ?></div>
                        <?php endif; ?>
                        <?php foreach ($sections as $section_key => $section): ?>
                            <details class="tcarm-section-group" data-section="<?php echo esc_attr($section_key); ?>" open>
                                <summary class="tcarm-section-group-summary">
                                    <div class="tcarm-section-summary-main">
                                        <span class="tcarm-section-drag" title="<?php echo esc_attr__('Drag to reorder', 'shinseiflow-application-review'); ?>" aria-hidden="true">☰</span><span class="tcarm-section-toggle-mark" aria-hidden="true"></span>
                                        <label class="tcarm-switch"><input type="checkbox" name="<?php echo esc_attr(self::OPTION_SECTIONS); ?>[<?php echo esc_attr($section_key); ?>][enabled]" value="1" <?php checked($section['enabled'], '1'); ?>><span></span></label>
                                        <strong class="tcarm-section-title-text"><?php echo esc_html($section['label']); ?></strong>
                                        <button type="button" class="tcarm-icon-button tcarm-edit-section" title="<?php echo esc_attr__('Edit Section Name', 'shinseiflow-application-review'); ?>" aria-label="<?php echo esc_attr__('Edit Section Name', 'shinseiflow-application-review'); ?>"><?php echo wp_kses($this->admin_icon_svg('edit'), $this->admin_icon_svg_allowed_tags()); ?></button>
                                        <code><?php echo esc_html($section_key); ?></code>
                                    </div>
                                    <div class="tcarm-section-summary-actions">
                                        <input class="tcarm-section-sort" type="hidden" name="<?php echo esc_attr(self::OPTION_SECTIONS); ?>[<?php echo esc_attr($section_key); ?>][sort_order]" value="<?php echo esc_attr($section['sort_order']); ?>">
                                        <input type="hidden" class="tcarm-section-label-input" name="<?php echo esc_attr(self::OPTION_SECTIONS); ?>[<?php echo esc_attr($section_key); ?>][label]" value="<?php echo esc_attr($section['label']); ?>">
                                        <input type="hidden" class="tcarm-delete-section-input" name="<?php echo esc_attr(self::OPTION_SECTIONS); ?>[<?php echo esc_attr($section_key); ?>][_delete]" value="0">
                                        <button type="button" class="tcarm-icon-button tcarm-delete-section" title="<?php echo esc_attr__('Delete Section', 'shinseiflow-application-review'); ?>" aria-label="<?php echo esc_attr__('Delete Section', 'shinseiflow-application-review'); ?>"><?php echo wp_kses($this->admin_icon_svg('delete'), $this->admin_icon_svg_allowed_tags()); ?></button>
                                    </div>
                                </summary>

                                <div class="tcarm-section-group-body tcarm-sortable-fields">
                                    <?php if (!empty($grouped[$section_key])): ?>
                                        <?php foreach ($grouped[$section_key] as $key => $field): ?>
                                            <div class="<?php echo esc_attr('tcarm-mini-field-card ' . ($field['enabled'] === '1' ? 'is-enabled' : 'is-disabled') . (isset($field['type']) && $field['type'] === 'dropdown' ? ' is-dropdown' : '')); ?>" data-field="<?php echo esc_attr($key); ?>">
                                                <div class="tcarm-mini-field-title">
                                                    <span class="tcarm-field-drag" title="<?php echo esc_attr__('Drag to reorder', 'shinseiflow-application-review'); ?>" aria-hidden="true">☰</span><input class="tcarm-mini-sort" type="hidden" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][sort_order]" value="<?php echo esc_attr($field['sort_order']); ?>">
                                                    <label class="tcarm-switch"><input type="checkbox" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked($field['enabled'], '1'); ?>><span></span></label>
                                                    <div class="tcarm-mini-name">
                                                        <strong><?php echo esc_html($field['label']); ?></strong>
                                                        <code><?php echo esc_html($key); ?></code>
                                                    </div>
                                                </div>
                                                <div class="tcarm-mini-field-controls">
                                                    <input class="tcarm-field-section-input" type="hidden" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][section]" value="<?php echo esc_attr($section_key); ?>">
                                                    <div class="tcarm-mini-field-controls-row tcarm-mini-field-controls-row-main">
                                                        <label><?php echo esc_html__('Type', 'shinseiflow-application-review'); ?>
                                                            <select name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][type]">
                                                                <?php foreach ($type_labels as $type => $label): ?><option value="<?php echo esc_attr($type); ?>" <?php selected($field['type'], $type); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                        <label><?php echo esc_html__('Display Label', 'shinseiflow-application-review'); ?>
                                                            <input type="text" data-tcarm-field-ja-source="1" data-tcarm-field-translation-key="<?php echo esc_attr($key . '__label'); ?>" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($field['label']); ?>">
                                                        </label>
                                                        <label><?php echo esc_html__('Placeholder', 'shinseiflow-application-review'); ?>
                                                            <input type="text" data-tcarm-field-ja-source="1" data-tcarm-field-translation-key="<?php echo esc_attr($key . '__placeholder'); ?>" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][placeholder]" value="<?php echo esc_attr(isset($field['placeholder']) ? $field['placeholder'] : ''); ?>" placeholder="<?php echo esc_attr__('Example: Enter placeholder text', 'shinseiflow-application-review'); ?>">
                                                        </label>
                                                    </div>
                                                    <div class="tcarm-mini-field-controls-row tcarm-mini-field-controls-row-description">
                                                        <label><?php echo esc_html__('Description', 'shinseiflow-application-review'); ?>
                                                            <input type="text" data-tcarm-field-ja-source="1" data-tcarm-field-translation-key="<?php echo esc_attr($key . '__description'); ?>" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][description]" value="<?php echo esc_attr(isset($field['description']) ? $field['description'] : ''); ?>" placeholder="<?php echo esc_attr__('Example: Enter helper text', 'shinseiflow-application-review'); ?>">
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="tcarm-mini-field-flags">
                                                    <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][required]" value="1" <?php checked($field['required'], '1'); ?>> <?php echo esc_html__('Required', 'shinseiflow-application-review'); ?></label>
                                                </div>
                                                <div class="tcarm-dropdown-settings">
                                                    <div class="tcarm-dropdown-settings-title"><?php echo esc_html__('Dropdown Choices', 'shinseiflow-application-review'); ?></div>
                                                    <p class="description"><?php echo esc_html__('Set the display labels shown on the frontend and the saved values. A blank first option is shown automatically as the placeholder.', 'shinseiflow-application-review'); ?></p>
                                                    <div class="tcarm-dropdown-choice-list">
                                                        <?php $dropdown_choices = !empty($field['choices']) && is_array($field['choices']) ? $field['choices'] : array(array('label' => '', 'value' => '')); ?>
                                                        <?php foreach ($dropdown_choices as $choice_index => $choice): ?>
                                                            <div class="tcarm-dropdown-choice-row">
                                                                <input type="text" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][choices][<?php echo esc_attr($choice_index); ?>][label]" value="<?php echo esc_attr(isset($choice['label']) ? $choice['label'] : ''); ?>" placeholder="<?php echo esc_attr__('Display Name', 'shinseiflow-application-review'); ?>">
                                                                <input type="text" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][choices][<?php echo esc_attr($choice_index); ?>][value]" value="<?php echo esc_attr(isset($choice['value']) ? $choice['value'] : ''); ?>" placeholder="<?php echo esc_attr__('Saved Value', 'shinseiflow-application-review'); ?>">
                                                                <button type="button" class="button tcarm-remove-dropdown-choice"><?php echo esc_html__('Delete', 'shinseiflow-application-review'); ?></button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <button type="button" class="button tcarm-add-dropdown-choice"><?php echo esc_html__('+ Add Choice', 'shinseiflow-application-review'); ?></button>
                                                </div>
                                                <div class="tcarm-row-actions">
                                                    <input type="hidden" class="tcarm-delete-field-input" name="<?php echo esc_attr(self::OPTION_FIELDS); ?>[<?php echo esc_attr($key); ?>][_delete]" value="0">
                                                    <button type="button" class="tcarm-icon-button tcarm-delete-field" title="<?php echo esc_attr__('Delete Field', 'shinseiflow-application-review'); ?>" aria-label="<?php echo esc_attr__('Delete Field', 'shinseiflow-application-review'); ?>"><?php echo wp_kses($this->admin_icon_svg('delete'), $this->admin_icon_svg_allowed_tags()); ?></button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="tcarm-empty-section"><?php echo esc_html__('This section does not have any fields yet.', 'shinseiflow-application-review'); ?></div>
                                    <?php endif; ?>
                                    <button type="button" class="button tcarm-add-field"><?php echo esc_html__('+ Add Field', 'shinseiflow-application-review'); ?></button>
                                </div>
                            </details>
                        <?php endforeach; ?>

                        <div class="tcarm-section-add-panel">
                            <div>
                                <strong><?php echo esc_html__('Add a New Section', 'shinseiflow-application-review'); ?></strong>
                                <p><?php echo esc_html__('Click the button to generate a section ID automatically. Editors only need to edit the section name.', 'shinseiflow-application-review'); ?></p>
                            </div>
                            <button type="button" class="button button-primary tcarm-add-section-main"><?php echo esc_html__('+ Add Section', 'shinseiflow-application-review'); ?></button>
                        </div>
                    </div>

                    <div id="tcarm-section-name-modal" class="tcarm-modal" aria-hidden="true">
                        <div class="tcarm-modal-backdrop"></div>
                        <div class="tcarm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="tcarm-section-name-modal-title">
                            <div class="tcarm-modal-header">
                                <h2 id="tcarm-section-name-modal-title"><?php echo esc_html__('Edit Section Name', 'shinseiflow-application-review'); ?></h2>
                                <button type="button" class="tcarm-icon-button tcarm-section-name-modal-close" aria-label="<?php echo esc_attr__('Close', 'shinseiflow-application-review'); ?>"><?php echo wp_kses($this->admin_icon_svg('delete'), $this->admin_icon_svg_allowed_tags()); ?></button>
                            </div>
                            <div class="tcarm-modal-body">
                                <label><?php echo esc_html__('Section Name', 'shinseiflow-application-review'); ?>
                                    <input type="text" class="tcarm-section-name-modal-input" value="">
                                </label>
                            </div>
                            <div class="tcarm-modal-footer">
                                <button type="button" class="button tcarm-section-name-modal-cancel"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></button>
                                <button type="button" class="button button-primary tcarm-section-name-modal-save"><?php echo esc_html__('Save', 'shinseiflow-application-review'); ?></button>
                            </div>
                        </div>
                    </div>

                    </div>
                    <?php foreach ($translation_languages as $lang => $label): ?>
                        <div class="tcarm-form-field-lang-panel" data-tcarm-form-field-panel="<?php echo esc_attr($lang); ?>">
                            <div class="tcarm-field-translation-list">
                                <?php foreach ($sections as $section_key => $section):
                                    $section_tr = isset($section['translations'][$lang]) && is_array($section['translations'][$lang]) ? $section['translations'][$lang] : array();
                                    $section_base_name = self::OPTION_SECTIONS . '[' . $section_key . '][translations][' . $lang . ']';
                                ?>
                                    <div class="tcarm-field-translation-section">
                                        <h3><?php echo esc_html($section['label']); ?></h3>
                                        <div class="tcarm-section-translation-row">
                                            <div class="tcarm-field-translation-source">
                                                <strong><?php echo esc_html__('Section Name', 'shinseiflow-application-review'); ?></strong>
                                                <code><?php echo esc_html($section_key); ?></code>
                                                <span class="description"><?php echo esc_html__('Japanese:', 'shinseiflow-application-review'); ?><?php echo esc_html($section['label']); ?></span>
                                            </div>
                                            <div class="tcarm-section-translation-control">
                                                <label><?php echo esc_html__('Translated Section Name', 'shinseiflow-application-review'); ?>
                                                    <input type="text" data-tcarm-field-translation-input="1" data-tcarm-field-translation-lang="<?php echo esc_attr($lang); ?>" data-tcarm-field-translation-key="<?php echo esc_attr('section__' . $section_key . '__label'); ?>" data-tcarm-field-source="<?php echo esc_attr($section['label']); ?>" name="<?php echo esc_attr($section_base_name); ?>[label]" value="<?php echo esc_attr(isset($section_tr['label']) ? $section_tr['label'] : ''); ?>" placeholder="<?php echo esc_attr($section['label']); ?>">
                                                </label>
                                            </div>
                                        </div>
                                        <?php if (!empty($grouped[$section_key])): ?>
                                            <?php foreach ($grouped[$section_key] as $key => $field):
                                                $tr = isset($field['translations'][$lang]) && is_array($field['translations'][$lang]) ? $field['translations'][$lang] : array();
                                                $base_name = self::OPTION_FIELDS . '[' . $key . '][translations][' . $lang . ']';
                                            ?>
                                                <div class="tcarm-field-translation-row">
                                                    <div class="tcarm-field-translation-source">
                                                        <strong><?php echo esc_html($field['label']); ?></strong>
                                                        <code><?php echo esc_html($key); ?></code>
                                                        <span class="description"><?php echo esc_html__('Field type:', 'shinseiflow-application-review'); ?><?php echo esc_html(isset($type_labels[$field['type']]) ? $type_labels[$field['type']] : $field['type']); ?></span>
                                                    </div>
                                                    <div class="tcarm-field-translation-controls">
                                                        <label><?php echo esc_html__('Translated Display Label', 'shinseiflow-application-review'); ?>
                                                            <input type="text" data-tcarm-field-translation-input="1" data-tcarm-field-translation-lang="<?php echo esc_attr($lang); ?>" data-tcarm-field-translation-key="<?php echo esc_attr($key . '__label'); ?>" data-tcarm-field-source="<?php echo esc_attr($field['label']); ?>" name="<?php echo esc_attr($base_name); ?>[label]" value="<?php echo esc_attr(isset($tr['label']) ? $tr['label'] : ''); ?>" placeholder="<?php echo esc_attr($field['label']); ?>">
                                                        </label>
                                                        <label><?php echo esc_html__('Translated Placeholder', 'shinseiflow-application-review'); ?>
                                                            <input type="text" data-tcarm-field-translation-input="1" data-tcarm-field-translation-lang="<?php echo esc_attr($lang); ?>" data-tcarm-field-translation-key="<?php echo esc_attr($key . '__placeholder'); ?>" data-tcarm-field-source="<?php echo esc_attr(isset($field['placeholder']) ? $field['placeholder'] : ''); ?>" name="<?php echo esc_attr($base_name); ?>[placeholder]" value="<?php echo esc_attr(isset($tr['placeholder']) ? $tr['placeholder'] : ''); ?>" placeholder="<?php echo esc_attr(isset($field['placeholder']) ? $field['placeholder'] : ''); ?>">
                                                        </label>
                                                        <label><?php echo esc_html__('Translated Description', 'shinseiflow-application-review'); ?>
                                                            <textarea data-tcarm-field-translation-input="1" data-tcarm-field-translation-lang="<?php echo esc_attr($lang); ?>" data-tcarm-field-translation-key="<?php echo esc_attr($key . '__description'); ?>" data-tcarm-field-source="<?php echo esc_attr(isset($field['description']) ? $field['description'] : ''); ?>" name="<?php echo esc_attr($base_name); ?>[description]" rows="2" placeholder="<?php echo esc_attr(isset($field['description']) ? $field['description'] : ''); ?>"><?php echo esc_textarea(isset($tr['description']) ? $tr['description'] : ''); ?></textarea>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="tcarm-empty-section"><?php echo esc_html__('This section does not have any fields yet.', 'shinseiflow-application-review'); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="tcarm-panel tcarm-card-panel tcarm-full-panel tcarm-consent-panel">
                    <div class="tcarm-panel-header">
                        <div class="tcarm-panel-title-block">
                            <h2><?php echo esc_html__('Consent Item Settings', 'shinseiflow-application-review'); ?></h2>
                            <p><?php echo esc_html__('Manage consent items shown at the bottom of the application form. Large cards are shown as consent sections, and each card body is displayed with scrolling on the frontend.', 'shinseiflow-application-review'); ?></p>
                        </div>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_partial]" value="form_terms">
                    <div class="tcarm-section-editor tcarm-consent-editor">
                        <div class="tcarm-sortable-consents">
                            <?php if (empty($consent_items)): ?>
                                <div class="tcarm-empty-section"><?php echo esc_html__('No consent items have been configured yet.', 'shinseiflow-application-review'); ?></div>
                            <?php endif; ?>
                            <?php foreach ($consent_items as $consent_key => $consent): ?>
                                <details class="tcarm-section-group tcarm-consent-group" data-consent="<?php echo esc_attr($consent_key); ?>" open>
                                    <summary class="tcarm-section-group-summary">
                                        <div class="tcarm-section-summary-main">
                                            <span class="tcarm-consent-drag" title="<?php echo esc_attr__('Drag to reorder', 'shinseiflow-application-review'); ?>" aria-hidden="true">☰</span>
                                            <span class="tcarm-section-toggle-mark" aria-hidden="true"></span>
                                            <label class="tcarm-switch"><input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][enabled]" value="1" <?php checked($consent['enabled'], '1'); ?>><span></span></label>
                                            <strong class="tcarm-consent-title-text"><?php echo esc_html($consent['label']); ?></strong>
                                            <button type="button" class="tcarm-icon-button tcarm-edit-consent" title="<?php echo esc_attr__('Edit Consent Item Name', 'shinseiflow-application-review'); ?>" aria-label="<?php echo esc_attr__('Edit Consent Item Name', 'shinseiflow-application-review'); ?>"><?php echo wp_kses($this->admin_icon_svg('edit'), $this->admin_icon_svg_allowed_tags()); ?></button>
                                            <code><?php echo esc_html($consent_key); ?></code>
                                        </div>
                                        <div class="tcarm-section-summary-actions">
                                            <input class="tcarm-consent-sort" type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][sort_order]" value="<?php echo esc_attr($consent['sort_order']); ?>">
                                            <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][id]" value="<?php echo esc_attr($consent_key); ?>">
                                            <input type="hidden" class="tcarm-consent-label-input" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][label]" value="<?php echo esc_attr($consent['label']); ?>">
                                            <input type="hidden" class="tcarm-delete-consent-input" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][_delete]" value="0">
                                            <button type="button" class="tcarm-icon-button tcarm-delete-consent" title="<?php echo esc_attr__('Delete Consent Item', 'shinseiflow-application-review'); ?>" aria-label="<?php echo esc_attr__('Delete Consent Item', 'shinseiflow-application-review'); ?>"><?php echo wp_kses($this->admin_icon_svg('delete'), $this->admin_icon_svg_allowed_tags()); ?></button>
                                        </div>
                                    </summary>
                                    <div class="tcarm-section-group-body tcarm-consent-body">
                                        <label class="tcarm-consent-textarea-label"><?php echo esc_html__('Consent Text to Display', 'shinseiflow-application-review'); ?>
                                            <textarea name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][body]" rows="8" placeholder="<?php echo esc_attr__('Enter consent text or an explanation. Leave blank if you only use a URL.', 'shinseiflow-application-review'); ?>"><?php echo esc_textarea($consent['body']); ?></textarea>
                                        </label>
                                        <div class="tcarm-consent-fields-row">
                                            <label class="tcarm-consent-checkbox-text"><?php echo esc_html__('Checkbox Text', 'shinseiflow-application-review'); ?>
                                                <input type="text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][checkbox_text]" value="<?php echo esc_attr($consent['checkbox_text']); ?>" placeholder="<?php echo esc_attr__('Example: I agree to the terms.', 'shinseiflow-application-review'); ?>">
                                            </label>
                                            <label>URL
                                                <input type="url" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][link_url]" value="<?php echo esc_attr(isset($consent['link_url']) ? $consent['link_url'] : ''); ?>" placeholder="<?php echo esc_attr__('https://... or /privacy/', 'shinseiflow-application-review'); ?>">
                                            </label>
                                            <label><?php echo esc_html__('Link Text', 'shinseiflow-application-review'); ?>
                                                <input type="text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][link_text]" value="<?php echo esc_attr(isset($consent['link_text']) ? $consent['link_text'] : ''); ?>" placeholder="<?php echo esc_attr__('Example: Open document', 'shinseiflow-application-review'); ?>">
                                            </label>
                                        </div>
                                        <div class="tcarm-consent-check-options">
                                            <label class="tcarm-consent-show-checkbox"><input type="checkbox" class="tcarm-consent-show-checkbox-input" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][show_checkbox]" value="1" <?php checked(isset($consent['show_checkbox']) ? $consent['show_checkbox'] : '1', '1'); ?>> <?php echo esc_html__('Show consent checkbox', 'shinseiflow-application-review'); ?></label>
                                            <label class="tcarm-consent-required"><input type="checkbox" class="tcarm-consent-required-input" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[consent_items][<?php echo esc_attr($consent_key); ?>][required]" value="1" <?php checked($consent['required'], '1'); ?> <?php disabled(isset($consent['show_checkbox']) ? $consent['show_checkbox'] : '1', '0'); ?>> <?php echo esc_html__('Required', 'shinseiflow-application-review'); ?></label>
                                        </div>
                                    </div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                        <div class="tcarm-section-add-panel tcarm-consent-add-panel">
                            <div>
                                <strong><?php echo esc_html__('Add a New Consent Section', 'shinseiflow-application-review'); ?></strong>
                                <p><?php echo esc_html__('Add items as needed for terms, privacy handling, consent to publish submitted content, and related confirmations.', 'shinseiflow-application-review'); ?></p>
                            </div>
                            <button type="button" class="button button-primary tcarm-add-consent-main"><?php echo esc_html__('+ Add Section', 'shinseiflow-application-review'); ?></button>
                        </div>
                    </div>
                </div>
                <?php submit_button(__('Save Form Settings', 'shinseiflow-application-review')); ?>
            </form>
        </div>
        <?php
    }
}
