<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Assets_Trait {
    public function admin_assets($hook) {
        if ($hook === 'plugins.php') {
            wp_enqueue_script('tcarm-admin-deactivation-notice', self::plugin_url() . 'assets/js/admin-deactivation-notice.js', array(), self::VERSION, true);
            wp_add_inline_script(
                'tcarm-admin-deactivation-notice',
                'window.tcarmDeactivationNotice = ' . wp_json_encode(
                    array(
                        'message' => __("Deactivating the plugin will not delete saved application data, uploaded files, review information, or settings.\n\nThis information may include personal data and application details.\n\nIf you want to delete data, enable data deletion under Privacy and Data Retention before deleting the plugin.\n\nDo you want to deactivate the plugin?", 'shinseiflow-application-review'),
                    )
                ) . ';',
                'before'
            );
        }

        $is_dashboard_page = $hook === 'toplevel_page_tcarm_dashboard';
        $is_applications_page = str_ends_with($hook, '_page_tcarm_applications');
        $is_form_settings_page = str_ends_with($hook, '_page_tcarm_form_settings');
        $is_download_settings_page = str_ends_with($hook, '_page_tcarm_download_files_settings');
        $is_mail_settings_page = str_ends_with($hook, '_page_tcarm_mail_settings');
        $is_security_settings_page = str_ends_with($hook, '_page_tcarm_security_settings');
        $is_general_settings_page = str_ends_with($hook, '_page_tcarm_settings');
        $is_translation_settings_page = str_ends_with($hook, '_page_tcarm_translation_settings');
        $is_privacy_settings_page = str_ends_with($hook, '_page_tcarm_privacy_settings');
        $is_about_page = strpos($hook, 'shinseiflow-about') !== false;
        $is_registered_admin_page = $is_dashboard_page
            || $is_applications_page
            || $is_form_settings_page
            || $is_download_settings_page
            || $is_mail_settings_page
            || $is_security_settings_page
            || $is_general_settings_page
            || $is_translation_settings_page
            || $is_privacy_settings_page
            || $is_about_page;
        $is_panel_page = $is_registered_admin_page && !$is_about_page;
        $has_fixed_savebar = $is_form_settings_page
            || $is_download_settings_page
            || $is_mail_settings_page
            || $is_security_settings_page
            || $is_general_settings_page
            || $is_translation_settings_page
            || $is_privacy_settings_page;
        $is_application_detail_page = false;
        if ($is_applications_page) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin asset detection; values are sanitized and do not modify data.
            $application_action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin asset detection; the application id is normalized before use.
            $application_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
            $is_application_detail_page = $application_action === 'view' && $application_id > 0;
        }

        if (strpos($hook, 'tcarm') !== false || $hook === 'toplevel_page_tcarm_dashboard' || $is_about_page) {
            wp_enqueue_style('tcarm-admin', self::plugin_url() . 'assets/css/admin.css', array(), self::VERSION);
            if ($is_about_page) {
                wp_enqueue_style(
                    'tcarm-admin-about',
                    self::plugin_url() . 'assets/css/admin-about.css',
                    array('tcarm-admin'),
                    self::VERSION
                );
            }
            if ($is_download_settings_page) {
                wp_enqueue_media();
            }

            $admin_scripts = array();
            if ($is_download_settings_page) {
                $admin_scripts['tcarm-admin-download-files'] = array(
                    'path' => 'assets/js/admin-download-files.js',
                    'dependencies' => array('jquery', 'jquery-ui-sortable'),
                );
            }
            if ($is_form_settings_page || $is_mail_settings_page || $is_general_settings_page) {
                $form_settings_dependencies = array('jquery');
                if ($is_form_settings_page) {
                    $form_settings_dependencies[] = 'jquery-ui-sortable';
                }
                $admin_scripts['tcarm-admin-form-settings'] = array(
                    'path' => 'assets/js/admin-form-settings.js',
                    'dependencies' => $form_settings_dependencies,
                );
            }
            if ($is_panel_page) {
                $admin_scripts['tcarm-admin-card-tooltips'] = array(
                    'path' => 'assets/js/admin-card-tooltips.js',
                    'dependencies' => array('jquery'),
                );
                $admin_scripts['tcarm-admin-material-ui'] = array(
                    'path' => 'assets/js/admin-material-ui.js',
                    'dependencies' => array('jquery'),
                );
            }
            if ($has_fixed_savebar) {
                $admin_scripts['tcarm-admin-fixed-savebar'] = array(
                    'path' => 'assets/js/admin-fixed-savebar.js',
                    'dependencies' => array('jquery'),
                );
            }
            if ($is_application_detail_page) {
                $admin_scripts['tcarm-admin-image-lightbox'] = array(
                    'path' => 'assets/js/admin-image-lightbox.js',
                    'dependencies' => array('jquery'),
                );
            }
            if ($is_registered_admin_page) {
                $admin_scripts['tcarm-admin-mobile-nav'] = array(
                    'path' => 'assets/js/admin-mobile-nav.js',
                    'dependencies' => array(),
                );
            }
            if ($is_general_settings_page) {
                $admin_scripts['tcarm-admin-application-number-rules'] = array(
                    'path' => 'assets/js/admin-application-number-rules.js',
                    'dependencies' => array('jquery', 'jquery-ui-sortable'),
                );
            }
            $admin_script_data = array(
                'save' => __('Save', 'shinseiflow-application-review'),
                'unsavedChanges' => __('Please save your changes.', 'shinseiflow-application-review'),
                'menu' => __('Menu', 'shinseiflow-application-review'),
                'close' => __('Close', 'shinseiflow-application-review'),
                'showDescription' => __('Show description', 'shinseiflow-application-review'),
                'dragToSort' => __('Drag to reorder', 'shinseiflow-application-review'),
                'newField' => __('New field', 'shinseiflow-application-review'),
                'type' => __('Type', 'shinseiflow-application-review'),
                'textType' => __('Text', 'shinseiflow-application-review'),
                'textareaType' => __('Textarea', 'shinseiflow-application-review'),
                'emailType' => __('Email', 'shinseiflow-application-review'),
                'phoneType' => __('Phone number', 'shinseiflow-application-review'),
                'dateType' => __('Date', 'shinseiflow-application-review'),
                'checkboxGroupType' => __('Checkbox', 'shinseiflow-application-review'),
                'radioType' => __('Radio Button Group', 'shinseiflow-application-review'),
                'fileUploadType' => __('File upload', 'shinseiflow-application-review'),
                'dropdownType' => __('Dropdown', 'shinseiflow-application-review'),
                'label' => __('Display label', 'shinseiflow-application-review'),
                'placeholder' => __('Placeholder', 'shinseiflow-application-review'),
                'placeholderExample' => __('Example: Enter placeholder text', 'shinseiflow-application-review'),
                'description' => __('Description', 'shinseiflow-application-review'),
                'descriptionExample' => __('Example: Enter helper text', 'shinseiflow-application-review'),
                'required' => __('Required', 'shinseiflow-application-review'),
                'dropdownChoices' => __('Dropdown Choices', 'shinseiflow-application-review'),
                'dropdownChoiceHelp' => __('Set the display labels shown on the frontend and the saved values. A blank first option is shown automatically as the placeholder.', 'shinseiflow-application-review'),
                'radioChoices' => __('Radio Button Choices', 'shinseiflow-application-review'),
                'radioChoiceHelp' => __('Set the display labels and saved values for the radio buttons.', 'shinseiflow-application-review'),
                'checkboxGroupChoices' => __('Checkbox Choices', 'shinseiflow-application-review'),
                'checkboxGroupChoiceHelp' => __('Set the display labels and saved values for the checkboxes.', 'shinseiflow-application-review'),
                'displayName' => __('Display name', 'shinseiflow-application-review'),
                'savedValue' => __('Saved value', 'shinseiflow-application-review'),
                'remove' => __('Delete', 'shinseiflow-application-review'),
                'addChoice' => __('Add choice', 'shinseiflow-application-review'),
                'deleteFieldTitle' => __('Delete field', 'shinseiflow-application-review'),
                'newSection' => __('New section', 'shinseiflow-application-review'),
                'editSectionName' => __('Edit section name', 'shinseiflow-application-review'),
                'deleteSectionTitle' => __('Delete section', 'shinseiflow-application-review'),
                'emptySection' => __('This section does not have any fields yet.', 'shinseiflow-application-review'),
                'addField' => __('Add field', 'shinseiflow-application-review'),
                'newConsent' => __('New consent item', 'shinseiflow-application-review'),
                'editConsentName' => __('Edit consent item name', 'shinseiflow-application-review'),
                'deleteConsentTitle' => __('Delete consent item', 'shinseiflow-application-review'),
                'checkboxText' => __('Checkbox text', 'shinseiflow-application-review'),
                'consentCheckboxDefault' => __('I agree to the terms.', 'shinseiflow-application-review'),
                'urlOrPathPlaceholder' => __('https://... or /privacy/', 'shinseiflow-application-review'),
                'linkText' => __('Link text', 'shinseiflow-application-review'),
                'linkTextExample' => __('Example: Terms of Use', 'shinseiflow-application-review'),
                'showConsentCheckbox' => __('Show consent checkbox', 'shinseiflow-application-review'),
                'consentBodyLabel' => __('Consent text to display', 'shinseiflow-application-review'),
                'consentBodyPlaceholder' => __('Enter consent text or an explanation. Leave blank if you only use a URL.', 'shinseiflow-application-review'),
                'sectionName' => __('Section name', 'shinseiflow-application-review'),
                'consentName' => __('Consent item name', 'shinseiflow-application-review'),
                'deleteFieldConfirm' => __('Delete this field?', 'shinseiflow-application-review'),
                'deleteSectionConfirm' => __('Delete this section?', 'shinseiflow-application-review'),
                'deleteSectionWithFieldsConfirm' => __('Fields in this section will also be deleted. Continue?', 'shinseiflow-application-review'),
                'deleteConsentConfirm' => __('Delete this consent item?', 'shinseiflow-application-review'),
                'sectionNameRequired' => __('Please enter a section name.', 'shinseiflow-application-review'),
                'copyPrompt' => __('Please copy this', 'shinseiflow-application-review'),
                'copiedSuffix' => __(' was copied.', 'shinseiflow-application-review'),
                'downloadMediaTitle' => __('Select download file', 'shinseiflow-application-review'),
                'downloadMediaButton' => __('Use this file', 'shinseiflow-application-review'),
                'applicationDeleteNone' => __('Please select applications to delete.', 'shinseiflow-application-review'),
                'bulkPermanentDeleteNone' => __('Please select applications to permanently delete.', 'shinseiflow-application-review'),
                'recipientPrefix' => __('Recipient: ', 'shinseiflow-application-review'),
                'dash' => __('—', 'shinseiflow-application-review'),
                'aiSelectTargetLanguage' => __('Please select the target language.', 'shinseiflow-application-review'),
                'aiNoEmptyTargets' => __('There are no empty fields to translate.', 'shinseiflow-application-review'),
                'aiTranslating' => __('Translating...', 'shinseiflow-application-review'),
                'aiFailed' => __('AI translation failed.', 'shinseiflow-application-review'),
                'aiFilledCurrentLanguage' => __('Translations were inserted into empty fields in the current language tab. Please review before saving.', 'shinseiflow-application-review'),
                'aiNoFillableTargets' => __('There were no empty fields available for translation.', 'shinseiflow-application-review'),
            );
            foreach ($admin_scripts as $handle => $script) {
                wp_enqueue_script($handle, self::plugin_url() . $script['path'], $script['dependencies'], self::VERSION, true);
                wp_add_inline_script(
                    $handle,
                    'window.tcarmAdminI18n = Object.assign({}, window.tcarmAdminI18n || {}, ' . wp_json_encode($admin_script_data) . ');',
                    'before'
                );
            }
            if ($is_security_settings_page) {
                wp_enqueue_script('tcarm-admin-security', self::plugin_url() . 'assets/js/admin-security.js', array('jquery'), self::VERSION, true);
            }
        }
    }

    private function current_page_has_tcarm_legacy_shortcode() {
        if (is_admin()) {
            return false;
        }
        foreach (array('tcarm_token', 'tcarm_submitted', 'tcarm_edit_updated', 'tcarm_download') as $query_key) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public shortcode asset detection only reads sanitized query state and does not modify data.
            if (isset($_GET[$query_key]) && sanitize_text_field(wp_unslash($_GET[$query_key])) !== '') {
                return true;
            }
        }
        if (!is_singular()) {
            return false;
        }
        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }
        foreach (array('tcarm_form', 'tcarm_status', 'tcarm_view', 'tcarm_edit') as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    public function frontend_assets() {
        wp_enqueue_style('tcarm-frontend', self::plugin_url() . 'assets/css/frontend.css', array(), self::VERSION);
        wp_enqueue_script('tcarm-frontend-validation', self::plugin_url() . 'assets/js/frontend-validation.js', array(), self::VERSION, true);
        wp_localize_script(
            'tcarm-frontend-validation',
            'tcarmFrontendValidationI18n',
            array(
                'requiredField' => __('Please complete this required field.', 'shinseiflow-application-review'),
                'requiredCheckbox' => __('Please select this checkbox.', 'shinseiflow-application-review'),
                'requiredRadioGroup' => __('Please select an option.', 'shinseiflow-application-review'),
                'requiredCheckboxGroup' => __('Please select at least one option.', 'shinseiflow-application-review'),
                'invalidEmail' => __('Please enter a valid email address.', 'shinseiflow-application-review'),
                'invalidUrl' => __('Please enter a valid URL.', 'shinseiflow-application-review'),
                'invalidPhone' => __('Please enter a valid phone number.', 'shinseiflow-application-review'),
            )
        );
    }

}
