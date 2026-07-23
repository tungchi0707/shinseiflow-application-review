<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Translations_Trait {
    private static function supported_languages() {
        return array(
            'ja' => '日本語',
            'en' => 'English',
            'zh-Hant' => '繁體中文',
            'zh-Hans' => '簡體中文',
            'ko' => '한국어',
        );
    }

    private static function get_default_enabled_languages() {
        $locale = function_exists('get_locale') ? (string) get_locale() : '';
        $lang = self::language_from_locale($locale);
        return array_values(array_unique(array($lang ?: 'en')));
    }

    private static function language_from_locale($locale) {
        $locale = trim((string) $locale);
        if ($locale === '') {
            return 'en';
        }
        $normalized = str_replace('-', '_', $locale);
        if (strpos($normalized, 'ja') === 0) {
            return 'ja';
        }
        if (strpos($normalized, 'en') === 0) {
            return 'en';
        }
        if (in_array($normalized, array('zh_TW', 'zh_HK', 'zh_Hant'), true)) {
            return 'zh-Hant';
        }
        if (in_array($normalized, array('zh_CN', 'zh_SG', 'zh_Hans'), true)) {
            return 'zh-Hans';
        }
        if (strpos($normalized, 'ko') === 0) {
            return 'ko';
        }
        return 'en';
    }

    private static function sanitize_enabled_languages($value) {
        $supported = self::supported_languages();
        $out = array();
        foreach ((array) $value as $lang) {
            $lang = (string) $lang;
            if (isset($supported[$lang])) {
                $out[] = $lang;
            }
        }
        $out = array_values(array_unique($out));
        return !empty($out) ? $out : array('en');
    }

    private static function get_enabled_languages($include_source = false) {
        $settings = get_option(self::OPTION_SETTINGS, array());
        $enabled = isset($settings['enabled_languages']) ? self::sanitize_enabled_languages($settings['enabled_languages']) : self::get_default_enabled_languages();
        if ($include_source && !in_array('ja', $enabled, true)) {
            array_unshift($enabled, 'ja');
        }
        $supported = self::supported_languages();
        $out = array();
        foreach ($enabled as $lang) {
            if (isset($supported[$lang])) {
                $out[$lang] = $supported[$lang];
            }
        }
        return $out;
    }

    private function normalize_language_code($lang) {
        $lang = trim((string) $lang);
        if ($lang === '') {
            return '';
        }
        $aliases = array(
            'jp' => 'ja',
            'zh' => 'zh-Hant',
            'zh_TW' => 'zh-Hant',
            'zh-tw' => 'zh-Hant',
            'zh_HK' => 'zh-Hant',
            'zh-hk' => 'zh-Hant',
            'zh_CN' => 'zh-Hans',
            'zh-cn' => 'zh-Hans',
            'zh_SG' => 'zh-Hans',
            'zh-sg' => 'zh-Hans',
            'ko_KR' => 'ko',
            'ko-kr' => 'ko',
            'en_US' => 'en',
            'en-us' => 'en',
            'ja_JP' => 'ja',
            'ja-jp' => 'ja',
        );
        if (isset($aliases[$lang])) {
            $lang = $aliases[$lang];
        }
        if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $lang)) {
            $lang = str_replace('_', '-', $lang);
        }
        $supported = self::supported_languages();
        return isset($supported[$lang]) ? $lang : '';
    }

    private function get_request_language() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Language selector is read-only display state and does not modify data.
        $posted = isset($_POST['tcarm_lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_POST['tcarm_lang']))) : '';
        if ($posted !== '') {
            return $posted;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Language query is read-only display state and does not modify data.
        $query = isset($_GET['lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_GET['lang']))) : '';
        if ($query !== '') {
            return $query;
        }
        if (function_exists('pll_current_language')) {
            $pll = $this->normalize_language_code((string) pll_current_language('slug'));
            if ($pll !== '') {
                return $pll;
            }
        }
        return 'ja';
    }

    private function set_current_language_from_shortcode($atts = array()) {
        $atts = shortcode_atts(array('lang' => ''), is_array($atts) ? $atts : array(), 'tcarm_application');
        $lang = $this->normalize_language_code(isset($atts['lang']) ? $atts['lang'] : '');
        if ($lang === '') {
            $lang = $this->get_request_language();
        }
        $this->current_frontend_lang = $lang ?: 'ja';
        return $this->current_frontend_lang;
    }

    private function normalize_shortcode_yes_no($value, $default = true) {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return (bool) $default;
        }
        if (in_array($value, array('yes', 'true', '1', 'on'), true)) {
            return true;
        }
        if (in_array($value, array('no', 'false', '0', 'off'), true)) {
            return false;
        }
        return (bool) $default;
    }

    private function set_current_form_options_from_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'lang' => '',
            'show_steps' => 'yes',
        ), is_array($atts) ? $atts : array(), 'tcarm_form');
        $this->set_current_language_from_shortcode($atts);
        $this->current_frontend_show_steps = $this->normalize_shortcode_yes_no(isset($atts['show_steps']) ? $atts['show_steps'] : 'yes', true);
    }

    private function should_show_frontend_steps() {
        return (bool) $this->current_frontend_show_steps;
    }

    private function current_language() {
        if ($this->current_frontend_lang !== '') {
            return $this->current_frontend_lang;
        }
        return $this->get_request_language();
    }

    private function translated_field_text($field, $prop, $lang = '') {
        $base = isset($field[$prop]) ? (string) $field[$prop] : '';
        $lang = $this->normalize_language_code($lang !== '' ? $lang : $this->current_language());
        if ($lang !== '' && $lang !== 'ja' && !empty($field['translations'][$lang]) && is_array($field['translations'][$lang])) {
            $translated = isset($field['translations'][$lang][$prop]) ? (string) $field['translations'][$lang][$prop] : '';
            if ($translated !== '') {
                return $translated;
            }
        }
        return $base;
    }

    private function apply_field_translation($field, $lang = '') {
        if (!is_array($field)) {
            return $field;
        }
        foreach (array('label', 'placeholder', 'description') as $prop) {
            $field[$prop] = $this->translated_field_text($field, $prop, $lang);
        }
        return $field;
    }

    private function translated_section_label($section_key, $lang = '') {
        $sections = self::get_sections();
        $key = self::normalize_section_key($section_key);
        $base = isset($sections[$key]['label']) ? (string) $sections[$key]['label'] : (string) $section_key;
        $lang = $this->normalize_language_code($lang !== '' ? $lang : $this->current_language());
        if ($lang !== '' && $lang !== 'ja' && !empty($sections[$key]['translations'][$lang]) && is_array($sections[$key]['translations'][$lang])) {
            $translated = isset($sections[$key]['translations'][$lang]['label']) ? (string) $sections[$key]['translations'][$lang]['label'] : '';
            if ($translated !== '') {
                return $translated;
            }
        }
        return $base;
    }

    private function should_add_lang_query($lang) {
        return $this->normalize_language_code($lang) !== '' && $lang !== 'ja';
    }

    private function add_lang_to_url($url, $lang = '') {
        $lang = $this->normalize_language_code($lang ?: $this->current_language());
        if (!$url || !$this->should_add_lang_query($lang)) {
            return $url;
        }
        return add_query_arg(array('lang' => $lang), $url);
    }

    private static function default_translation_strings() {
        return array(
            'common.next' => 'Next',
            'common.back' => 'Back',
            'common.submit' => 'Submit',
            'common.top' => 'Back to top',
            'common.check_status' => 'Check application status',
            'common.check_other_status' => 'Check another application status',
            'common.back_to_status' => 'Back to application status',
            'common.recheck_status' => 'Check application status again',
            'common.edit_and_resubmit' => 'Edit and resubmit',
            'common.view_submitted_content' => 'View submitted content',
            'common.review_input' => 'Review your input',
            'common.edit_content' => 'Edit',
            'common.resubmit' => 'Resubmit edited content',
            'common.move' => 'Move',
            'common.download' => 'Download',
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
            'form.upload_help_prefix' => 'Allowed uploads',
            'form.upload_help_max' => 'Maximum',
            'form.upload_help_until' => 'files',
            'form.title' => 'Application',
            'form.description' => 'Enter the required information, review your input, and submit the form.',
            'confirm.description' => 'Review your input and submit the form.',
            'complete.received_title' => 'Application received',
            'complete.received_description' => 'A confirmation email has been sent. We will contact you again after reviewing your application.',
            'complete.resubmitted_title' => 'Resubmission received',
            'complete.resubmitted_description' => 'Your changes have been saved and returned to pending review.',
            'status.pending' => 'Pending Review',
            'status.approved' => 'Approved',
            'status.rejected' => 'Rejected',
            'status.published' => 'Published',
            'status.needs_more' => 'Additional Information Requested',
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
            'edit.description' => 'You can edit and resubmit a rejected application. After submission, the status returns to pending review.',
            'edit.cannot_edit' => 'This application cannot be edited at this time.',
            'edit.confirmation_note' => 'Additional Information',
            'redirect.description' => 'Redirecting you now. If you are not redirected automatically, use the button below.',
            'download.title' => 'Download Files',
            'download.description' => 'Approved applicants can download available files.',
            'steps.step1.label' => 'STEP 1',
            'steps.step1.title' => 'Input',
            'steps.step2.label' => 'STEP 2',
            'steps.step2.title' => 'Review',
            'steps.step3.label' => 'STEP 3',
            'steps.step3.title' => 'Submission Complete',
            'steps.step4.label' => 'STEP 4',
            'steps.step4.title' => 'Admin Review',
        );
    }

    private function normalize_translation_strings($value) {
        $defaults = self::default_translation_strings();
        $out = array();
        foreach (self::supported_languages() as $lang => $label) {
            $row = isset($value[$lang]) && is_array($value[$lang]) ? $value[$lang] : array();
            foreach ($defaults as $key => $default) {
                $out[$lang][$key] = isset($row[$key]) ? (string) $row[$key] : ($lang === 'ja' ? $default : '');
            }
        }
        return $out;
    }

    public function sanitize_translation_strings($input) {
        $out = array();
        $defaults = self::default_translation_strings();
        $current = get_option(self::OPTION_TRANSLATIONS, array());
        $current = $this->normalize_translation_strings(is_array($current) ? $current : array());
        foreach (self::supported_languages() as $lang => $label) {
            if (!isset($input[$lang])) {
                $out[$lang] = isset($current[$lang]) ? $current[$lang] : array();
                continue;
            }
            $row = isset($input[$lang]) && is_array($input[$lang]) ? $input[$lang] : array();
            foreach ($defaults as $key => $default) {
                $out[$lang][$key] = isset($row[$key]) ? sanitize_text_field($row[$key]) : '';
            }
        }
        return $out;
    }

    private function get_translation_strings() {
        $stored = get_option(self::OPTION_TRANSLATIONS, array());
        return $this->normalize_translation_strings(is_array($stored) ? $stored : array());
    }

    private function t($key, $default = '', $lang = '') {
        $key = (string) $key;
        $lang = $this->normalize_language_code($lang ?: $this->current_language());
        $strings = $this->get_translation_strings();
        if ($lang !== '' && isset($strings[$lang][$key]) && trim((string) $strings[$lang][$key]) !== '') {
            return (string) $strings[$lang][$key];
        }
        if ($lang === 'ja' && isset($strings['ja'][$key]) && trim((string) $strings['ja'][$key]) !== '') {
            return (string) $strings['ja'][$key];
        }
        $defaults = self::default_translation_strings();
        if ($default === '' && isset($defaults[$key])) {
            return $defaults[$key];
        }
        return (string) $default;
    }

    private function frontend_status_label($status) {
        $map = array(
            'pending' => 'status.pending',
            'approved' => 'status.approved',
            'rejected' => 'status.rejected',
            'published' => 'status.published',
            'needs_more' => 'status.needs_more',
        );
        return isset($map[$status]) ? $this->t($map[$status]) : self::status_label($status);
    }
}
