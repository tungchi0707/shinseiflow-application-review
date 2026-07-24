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
                    ),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) . ';',
                'before'
            );
        }

        if (strpos($hook, 'tcarm') !== false || $hook === 'toplevel_page_tcarm_dashboard' || strpos($hook, 'shinseiflow-about') !== false) {
            wp_enqueue_style('tcarm-admin', self::plugin_url() . 'assets/css/admin.css', array(), self::VERSION);
            $settings = self::get_settings();
            $admin_custom_css = isset($settings['admin_custom_css']) ? trim((string) $settings['admin_custom_css']) : '';
            if ($admin_custom_css !== '') {
                wp_add_inline_style('tcarm-admin', $admin_custom_css);
            }
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            $admin_scripts = array(
                'tcarm-admin-download-files' => 'assets/js/admin-download-files.js',
                'tcarm-admin-form-settings' => 'assets/js/admin-form-settings.js',
                'tcarm-admin-card-tooltips' => 'assets/js/admin-card-tooltips.js',
                'tcarm-admin-material-ui' => 'assets/js/admin-material-ui.js',
                'tcarm-admin-fixed-savebar' => 'assets/js/admin-fixed-savebar.js',
                'tcarm-admin-image-lightbox' => 'assets/js/admin-image-lightbox.js',
                'tcarm-admin-mobile-nav' => 'assets/js/admin-mobile-nav.js',
                'tcarm-admin-application-number-rules' => 'assets/js/admin-application-number-rules.js',
            );
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
                'checkboxType' => __('Checkbox', 'shinseiflow-application-review'),
                'fileUploadType' => __('File upload', 'shinseiflow-application-review'),
                'dropdownType' => __('Dropdown', 'shinseiflow-application-review'),
                'label' => __('Display label', 'shinseiflow-application-review'),
                'placeholder' => __('Placeholder', 'shinseiflow-application-review'),
                'placeholderExample' => __('Example: Enter placeholder text', 'shinseiflow-application-review'),
                'description' => __('Description', 'shinseiflow-application-review'),
                'descriptionExample' => __('Example: Enter helper text', 'shinseiflow-application-review'),
                'required' => __('Required', 'shinseiflow-application-review'),
                'dropdownChoices' => __('Dropdown choices', 'shinseiflow-application-review'),
                'dropdownChoiceHelp' => __('Set display labels and saved values. A blank first option is shown automatically as the placeholder.', 'shinseiflow-application-review'),
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
            foreach ($admin_scripts as $handle => $path) {
                wp_enqueue_script($handle, self::plugin_url() . $path, array('jquery', 'jquery-ui-sortable'), self::VERSION, true);
                wp_add_inline_script(
                    $handle,
                    'window.tcarmAdminI18n = Object.assign({}, window.tcarmAdminI18n || {}, ' . wp_json_encode($admin_script_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ');',
                    'before'
                );
            }
            if (in_array($hook, array('shinseiflow-application-review_page_tcarm_security_settings', 'tcarm_dashboard_page_tcarm_security_settings'), true) || strpos($hook, 'tcarm_security_settings') !== false) {
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
        $settings = self::get_settings();
        $frontend_custom_css = isset($settings['frontend_custom_css']) ? trim((string) $settings['frontend_custom_css']) : '';
        if ($frontend_custom_css !== '' && $this->current_page_has_tcarm_legacy_shortcode()) {
            wp_add_inline_style('tcarm-frontend', $frontend_custom_css);
        }
        wp_enqueue_script('tcarm-frontend-validation', self::plugin_url() . 'assets/js/frontend-validation.js', array(), self::VERSION, true);
    }

    public function frontend_resource_hints($urls, $relation_type) {
        return $urls;
    }

}
