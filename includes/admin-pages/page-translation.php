<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Translation_Trait {
    private function render_translation_field($lang, $key, $label, $value) {
        ?>
        <label class="tcarm-settings-field tcarm-translation-field">
            <span class="tcarm-translation-field__source">
                <strong><?php echo esc_html($label); ?></strong>
                <code><?php echo esc_html($key); ?></code>
            </span>
            <span class="tcarm-translation-field__control">
                <input type="text" class="regular-text" data-tcarm-translation-input="1" data-tcarm-translation-lang="<?php echo esc_attr($lang); ?>" data-tcarm-translation-key="<?php echo esc_attr($key); ?>" data-tcarm-translation-source="<?php echo esc_attr($label); ?>" name="<?php echo esc_attr(self::OPTION_TRANSLATIONS); ?>[<?php echo esc_attr($lang); ?>][<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>">
            </span>
        </label>
        <?php
    }

    private function translation_groups() {
        return array(
            'common_buttons' => array(
                'label' => __('Common Buttons', 'shinseiflow-application-review'),
                'strings' => array(
                'common.review_input' => 'Review your input',
                'common.edit_content' => 'Edit',
                'common.submit' => 'Submit',
                'common.back' => 'Back',
                'common.top' => 'Back to top',
                'common.check_status' => 'Check application status',
                'common.check_other_status' => 'Check another application status',
                'common.back_to_status' => 'Back to application status',
                'common.recheck_status' => 'Check application status again',
                'common.view_submitted_content' => 'View submitted content',
                'common.edit_and_resubmit' => 'Edit and resubmit',
                'common.resubmit' => 'Resubmit edited content',
                'common.move' => 'Move',
                'common.download' => 'Download',
                ),
            ),
            'common_labels' => array(
                'label' => __('Common Labels', 'shinseiflow-application-review'),
                'strings' => array(
                'common.application_number' => 'Application Number',
                'common.current_status' => 'Current Status',
                'common.rejection_reason' => 'Rejection Reason',
                'common.application_status' => 'Application Status',
                'common.application_status_check' => 'Application Status Check',
                'common.application_edit' => 'Edit Application Content',
                'common.application_view' => 'View Application Content',
                'common.completed' => 'Submission Complete',
                'common.submitted_content' => 'Submitted Content',
                'common.contact_email' => 'Contact Email',
                'common.sent_at' => 'Submitted At',
                'common.updated_at' => 'Updated At',
                'common.resubmit_count' => 'Resubmissions',
                'common.times' => 'times',
                'common.consent_items' => 'Consent Items',
                'common.consent_agreed' => 'Agreed',
                'common.required' => 'Required',
                'common.select_placeholder' => 'Please select',
                ),
            ),
            'application_statuses' => array(
                'label' => __('Application Statuses', 'shinseiflow-application-review'),
                'strings' => array(
                'status.pending' => 'Pending Review',
                'status.approved' => 'Approved',
                'status.rejected' => 'Rejected',
                'status.published' => 'Published',
                'status.needs_more' => 'Additional Information Requested',
                ),
            ),
            'application_steps' => array(
                'label' => __('Application Steps', 'shinseiflow-application-review'),
                'strings' => array(
                'steps.step1.label' => 'STEP 1',
                'steps.step1.title' => 'Input',
                'steps.step2.label' => 'STEP 2',
                'steps.step2.title' => 'Review',
                'steps.step3.label' => 'STEP 3',
                'steps.step3.title' => 'Submission Complete',
                'steps.step4.label' => 'STEP 4',
                'steps.step4.title' => 'Admin Review',
                ),
            ),
            'application_form' => array(
                'label' => __('Application Form', 'shinseiflow-application-review'),
                'strings' => array(
                'form.title' => 'Application',
                'form.description' => 'Enter the required information, review your input, and submit the form.',
                'form.upload_help_prefix' => 'Allowed uploads',
                'form.upload_help_max' => 'Maximum',
                'form.upload_help_until' => 'files',
                'common.consent_items' => 'Consent Items',
                'common.consent_agreed' => 'Agreed',
                ),
            ),
            'application_review' => array(
                'label' => __('Application Review', 'shinseiflow-application-review'),
                'strings' => array(
                'common.application_view' => 'View Application Content',
                'confirm.description' => 'Review your input and submit the form.',
                'common.submitted_content' => 'Submitted Content',
                ),
            ),
            'submission_complete' => array(
                'label' => __('Submission Complete', 'shinseiflow-application-review'),
                'strings' => array(
                'complete.received_title' => 'Application received',
                'complete.received_description' => 'A confirmation email has been sent. We will contact you again after reviewing your application.',
                'complete.resubmitted_title' => 'Resubmission received',
                'complete.resubmitted_description' => 'Your changes have been saved and returned to pending review.',
                ),
            ),
            'application_status' => array(
                'label' => __('Application Status', 'shinseiflow-application-review'),
                'strings' => array(
                'status.check_result' => 'View result',
                'status.lookup_description' => 'Enter your application number and email address to check the current status.',
                'status.not_found' => 'No matching application was found.',
                'status.retry_later' => 'Please try again later.',
                'status.turnstile_failed' => 'Robot prevention verification failed. Please try again.',
                'status.view_empty_title' => 'View Submitted Content',
                'status.view_empty_description' => 'To view submitted content, access it from the application status page.',
                'status.edit_empty_title' => 'Edit and Resubmit',
                'status.edit_empty_description' => 'Please edit and resubmit from the application status page.',
                'status.token_expired' => 'The verification link has expired. Enter your application number and email address to check again.',
                ),
            ),
            'application_edit' => array(
                'label' => __('Application Edit', 'shinseiflow-application-review'),
                'strings' => array(
                'edit.description' => 'You can edit and resubmit a rejected application. After submission, the status returns to pending review.',
                'edit.cannot_edit' => 'This application cannot be edited at this time.',
                'edit.confirmation_note' => 'Additional Information',
                ),
            ),
            'downloads' => array(
                'label' => __('Downloads', 'shinseiflow-application-review'),
                'strings' => array(
                'download.title' => 'Download Files',
                'download.description' => 'Approved applicants can download available files.',
                'common.download' => 'Download',
                ),
            ),
        );
    }

    public function render_translation_settings_page() {
        $settings = self::get_settings();
        $supported_languages = self::supported_languages();
        $base_language = self::get_base_language($settings);
        $base_language_label = isset($supported_languages[$base_language]) ? $supported_languages[$base_language] : $supported_languages['en'];
        $enabled_language_keys = self::sanitize_enabled_languages(isset($settings['enabled_languages']) ? $settings['enabled_languages'] : self::get_default_enabled_languages());
        if (!in_array($base_language, $enabled_language_keys, true)) {
            $enabled_language_keys[] = $base_language;
        }
        $enabled_languages = array();
        foreach ($enabled_language_keys as $lang) {
            if (isset($supported_languages[$lang])) {
                $enabled_languages[$lang] = $supported_languages[$lang];
            }
        }
        $translation_languages = $enabled_languages;
        if (empty($translation_languages)) {
            $translation_languages = array('en' => $supported_languages['en']);
        }
        $target_languages = array_diff(array_keys($translation_languages), array($base_language));
        $strings = $this->get_translation_strings();
        $groups = $this->translation_groups();
        wp_enqueue_script('tcarm-admin-translation-settings', self::plugin_url() . 'assets/js/admin-translation-settings.js', array(), self::VERSION, true);
        wp_localize_script(
            'tcarm-admin-translation-settings',
            'tcarmAiTranslate',
            array(
                'ajaxUrl'    => admin_url('admin-ajax.php'),
                'nonce'      => wp_create_nonce('tcarm_ai_translate_strings'),
                'hasTargets' => !empty($target_languages),
                'baseLanguage' => $base_language,
                'baseLanguageLabel' => $base_language_label,
                'targetLanguages' => array_values($target_languages),
                'i18n'       => array(
                    'aiSelectTargetLanguage' => __('Please select the target language.', 'shinseiflow-application-review'),
                    'aiNoEmptyTargets' => __('There are no empty fields to translate.', 'shinseiflow-application-review'),
                    'aiTranslating' => __('Translating...', 'shinseiflow-application-review'),
                    'aiFailed' => __('AI translation failed.', 'shinseiflow-application-review'),
                    'aiFilledCurrentLanguage' => __('Translations were inserted into empty fields in the current language tab. Please review before saving.', 'shinseiflow-application-review'),
                    'aiNoFillableTargets' => __('There were no empty fields available for translation.', 'shinseiflow-application-review'),
                    'aiSourceEmpty' => __('The source language fields are empty. Enter content in the base language before translating.', 'shinseiflow-application-review'),
                    'aiBaseLanguageUnsaved' => __('Save the base language setting before using AI translation.', 'shinseiflow-application-review'),
                ),
            )
        );
        $this->open_admin_wrap(__('Multilingual Settings', 'shinseiflow-application-review'));
        ?>
            <form method="post" action="options.php" class="tcarm-translation-settings-form tcarm-admin-settings-page">
                <?php settings_fields('tcarm_translation_group'); ?>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_partial]" value="translation">
                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card">
                    <div class="tcarm-panel-header">
                        <h2><?php echo esc_html__('Display Language Settings', 'shinseiflow-application-review'); ?></h2>
                        <p><?php echo esc_html__('Select languages shown in admin multilingual input fields and AI translation targets.', 'shinseiflow-application-review'); ?></p>
                    </div>
                    <div class="tcarm-settings-card-body">
                        <div class="tcarm-settings-row-list">
                            <label class="tcarm-settings-field"><?php echo esc_html__('Base Language (Translation Source)', 'shinseiflow-application-review'); ?>
                                <select id="tcarm-base-language-select" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[base_language]">
                                    <?php foreach ($supported_languages as $lang => $label): ?>
                                        <option value="<?php echo esc_attr($lang); ?>" <?php selected($base_language, $lang); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="tcarm-base-language-enabled" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[enabled_languages][]" value="<?php echo esc_attr($base_language); ?>">
                                <span class="description"><?php echo esc_html__('This setting only selects the source language used by AI translation. It does not move, delete, or overwrite translation content. The base language must remain enabled.', 'shinseiflow-application-review'); ?></span>
                            </label>
                        </div>
                        <div class="tcarm-settings-inline-options">
                            <?php foreach ($supported_languages as $lang => $label): ?>
                                <label>
                                    <input type="checkbox" data-tcarm-enabled-language="<?php echo esc_attr($lang); ?>" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[enabled_languages][]" value="<?php echo esc_attr($lang); ?>" <?php checked(in_array($lang, $enabled_language_keys, true)); ?> <?php disabled($lang, $base_language); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php echo esc_html__('If no languages are selected, English is enabled when saving. Existing translation data for disabled languages is not deleted.', 'shinseiflow-application-review'); ?></p>
                    </div>
                </div>
                <?php
                $this->render_ai_translation_settings_card($settings);
                ?>
                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-translation-settings-card tcarm-lang-page-settings-card">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Translation String Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Configure the text shown on the frontend according to the shortcode', 'shinseiflow-application-review'); ?> <code>lang</code> <?php echo esc_html__('parameter.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <p class="description"><?php echo esc_html__('Empty items fall back to the English default strings.', 'shinseiflow-application-review'); ?></p>
                        <div class="tcarm-translation-toolbar">
                            <div class="tcarm-display-tabs tcarm-translation-lang-tabs" role="tablist" aria-label="<?php echo esc_attr__('Translation Language Switcher', 'shinseiflow-application-review'); ?>">
                                <?php $i = 0; foreach ($translation_languages as $lang => $label): ?>
                                    <button type="button" class="<?php echo esc_attr('tcarm-display-tab tcarm-translation-lang-tab' . ($i === 0 ? ' is-active' : '')); ?>" data-tcarm-translation-lang="<?php echo esc_attr($lang); ?>" role="tab" aria-selected="<?php echo esc_attr($i === 0 ? 'true' : 'false'); ?>"><?php echo esc_html($label); ?></button>
                                <?php $i++; endforeach; ?>
                            </div>
                            <div class="tcarm-ai-translate-action" id="tcarm-ai-translate-action" hidden>
                                <button type="button" class="button button-secondary" id="tcarm-ai-translate-button"><?php
                                /* translators: %s: name of the language used as the AI translation source. */
                                echo esc_html(sprintf(__('Translate from %s', 'shinseiflow-application-review'), $base_language_label));
                                ?></button>
                                <span class="spinner" id="tcarm-ai-translate-spinner"></span>
                            </div>
                        </div>
                        <p class="tcarm-ai-translate-message" id="tcarm-ai-translate-message" aria-live="polite"></p>
                        <?php if (empty($target_languages)): ?>
                            <p class="description"><?php echo esc_html__('There are no target languages for translation. Enable target languages in Display Language Settings.', 'shinseiflow-application-review'); ?></p>
                        <?php endif; ?>
                        <?php $i = 0; foreach ($translation_languages as $lang => $label): ?>
                            <section class="<?php echo esc_attr('tcarm-display-panel tcarm-translation-lang-panel' . ($i === 0 ? ' is-active' : '')); ?>" data-tcarm-translation-panel="<?php echo esc_attr($lang); ?>" role="tabpanel">
                                <?php foreach ($groups as $group): ?>
                                    <?php
                                    $group_label = isset($group['label']) ? $group['label'] : '';
                                    $keys = isset($group['strings']) && is_array($group['strings']) ? $group['strings'] : array();
                                    ?>
                                    <div class="tcarm-translation-group">
                                        <h3><?php echo esc_html($group_label); ?></h3>
                                        <div class="tcarm-settings-row-list tcarm-translation-field-list">
                                            <?php foreach ($keys as $key => $field_label): ?>
                                                <?php
                                                $this->render_translation_field($lang, $key, $field_label, isset($strings[$lang][$key]) ? $strings[$lang][$key] : '');
                                                ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </section>
                        <?php $i++; endforeach; ?>
                    </div>
                </div>
                <?php submit_button(__('Save Multilingual Settings', 'shinseiflow-application-review')); ?>
            </form>
        <?php
        $this->close_admin_wrap();
    }
}
