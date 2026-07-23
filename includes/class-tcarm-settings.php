<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Settings_Trait {
    public static function default_settings() {
        return array(
            'recipient_email' => get_option('admin_email'),
            'cc_email' => '',
            'bcc_email' => '',
            'from_name' => get_bloginfo('name'),
            'from_email' => get_option('admin_email'),
            'mail_send_method' => 'wordpress',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_auth' => '1',
            'smtp_username' => '',
            'smtp_password' => '',
            'turnstile_enabled' => '0',
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
            'turnstile_theme' => 'auto',
            'turnstile_size' => 'normal',
            'turnstile_apply_form' => '1',
            'turnstile_apply_status' => '0',
            'turnstile_apply_edit' => '0',
            'honeypot_enabled' => '1',
            'rate_limit_enabled' => '1',
            'file_upload_enabled' => '1',
            'file_allowed_extensions' => 'jpg,jpeg,png,pdf',
            'file_max_size_mb' => '5',
            'file_max_uploads' => '3',
            'delete_data_on_uninstall' => '0',
            'allowed_roles' => array('administrator'),
            'application_number_rule' => self::default_application_number_rule(),
            'terms_text' => '',
            'terms_url' => '',
            'consent_items' => self::default_consent_items(),
            'form_page_id' => '0',
            'status_page_id' => '0',
            'view_page_id' => '0',
            'edit_page_id' => '0',
            'top_page_id' => '0',
            'status_page_url' => '',
            'view_page_url' => '',
            'edit_page_url' => '',
            'top_page_url' => '',
            'frontend_pages_by_lang' => array(),
            'related_info_mode' => 'external',
            'related_target_post_type' => '',
            'related_post_status' => 'publish',
            'related_application_meta_key' => 'tcarm_application_number',
            'related_source_id_meta_key' => 'tcarm_source_application_id',
            'download_files' => array(),
            'download_link_expire_minutes' => '30',
            'frontend_custom_css' => '',
            'admin_custom_css' => '',
            'ai_provider' => 'openai',
            'ai_api_key' => '',
            'ai_model' => 'gpt-4o-mini',
            'openai_api_key' => '',
            'ai_translation_model' => 'gpt-5.4',
            'enabled_languages' => self::get_default_enabled_languages(),
            'email_subject_received' => '[Application] Application Received ({application_code})',
            'email_body_received' => "Dear {applicant_name},\n\nYour application has been received.\nApplication Number: {application_code}\n\nWe will contact you again after reviewing your application.",
            'email_subject_admin' => '[New Application] {event_title}',
            'email_body_admin' => "A new application has been received.\n\nApplication Number: {application_code}\nApplicant: {applicant_name}\nEvent Name: {event_title}\n\nPlease review it in the admin screen.",
            'email_subject_approved' => '[Application] Approval Notice ({application_code})',
            'email_body_approved' => "Dear {applicant_name},\n\nYour application has been approved.\nApplication Number: {application_code}\n\nPlease review any applicable guidelines before use.\n\nAvailable files can be downloaded from the application status page using your application number and email address.\nApplication Status: {status_url}",
            'email_subject_rejected' => '[Application] Rejection Notice ({application_code})',
            'email_body_rejected' => "Dear {applicant_name},\n\nYour application has been rejected for the following reason.\n\nReason: {reject_reason}\n\nPlease revise the application and resubmit if appropriate.",
            'email_subject_request' => '[Application] Additional Information Requested ({application_code})',
            'email_body_request' => "Dear {applicant_name},\n\nWe need additional information about your application.\n\nRequest: {request_note}\n\nPlease review the request and resubmit your application or contact the administrator.\n\nApplication Status: {status_url}",
            'email_subject_resubmitted' => '[Resubmission] Application ({application_code})',
            'email_body_resubmitted' => "An application resubmission has been received.\n\nApplication Number: {application_code}\nApplicant: {applicant_name}\nEvent Name: {event_title}\nResubmission Count: {resubmit_count}\n\nPlease review it in the admin screen.",
        );
    }

    public static function default_consent_items() {
        return array();
    }

    public static function default_fields() {
        return array();
    }

    public static function default_sections() {
        return array();
    }

    public function register_settings() {
        register_setting('tcarm_settings_group', self::OPTION_SETTINGS, array($this, 'sanitize_settings'));
        register_setting('tcarm_fields_group', self::OPTION_FIELDS, array($this, 'sanitize_fields'));
        register_setting('tcarm_fields_group', self::OPTION_SECTIONS, array($this, 'sanitize_sections'));
        register_setting('tcarm_fields_group', self::OPTION_SETTINGS, array($this, 'sanitize_settings'));
        register_setting('tcarm_translation_group', self::OPTION_SETTINGS, array($this, 'sanitize_settings'));
        register_setting('tcarm_translation_group', self::OPTION_TRANSLATIONS, array($this, 'sanitize_translation_strings'));
    }

    public function sanitize_settings($input) {
        $defaults = self::default_settings();
        $current = get_option(self::OPTION_SETTINGS, array());
        $base = wp_parse_args(is_array($current) ? $current : array(), $defaults);
        $partial = !empty($input['_partial']);
        $security_partial = $partial && !empty($input['_security_settings']);
        $out = array();
        foreach ($defaults as $key => $value) {
            if (in_array($key, array('consent_items', 'download_files', 'frontend_pages_by_lang', 'application_number_rule', 'enabled_languages'), true)) {
                continue;
            }
            $fallback = $partial ? (isset($base[$key]) ? $base[$key] : $value) : $value;
            if ($key === 'allowed_roles') {
                if (current_user_can('manage_options')) {
                    $out[$key] = isset($input[$key]) ? self::normalize_tcarm_allowed_roles($input[$key]) : self::normalize_tcarm_allowed_roles($fallback);
                } else {
                    $out[$key] = self::normalize_tcarm_allowed_roles($fallback);
                }
            } elseif (in_array($key, array('frontend_custom_css', 'admin_custom_css'), true)) {
                $out[$key] = isset($input[$key]) ? $this->sanitize_custom_css($input[$key]) : $fallback;
            } elseif ($key === 'openai_api_key' || $key === 'ai_api_key') {
                $incoming_key = isset($input[$key]) ? trim((string) $input[$key]) : '';
                $out[$key] = $incoming_key !== '' ? sanitize_text_field($incoming_key) : $fallback;
            } elseif ($key === 'ai_provider') {
                $provider = isset($input[$key]) ? sanitize_key($input[$key]) : $fallback;
                $out[$key] = in_array($provider, array('openai', 'gemini'), true) ? $provider : 'openai';
            } elseif ($key === 'ai_translation_model' || $key === 'ai_model') {
                $model = isset($input[$key]) ? sanitize_text_field($input[$key]) : $fallback;
                $out[$key] = $model !== '' ? $model : ($key === 'ai_model' ? 'gpt-4o-mini' : 'gpt-5.4');
            } elseif (strpos($key, 'email_body_') === 0) {
                $out[$key] = isset($input[$key]) ? sanitize_textarea_field($input[$key]) : $fallback;
            } elseif (strpos($key, 'email_subject_') === 0) {
                $out[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : $fallback;
            } elseif ($key === 'from_email') {
                $out[$key] = isset($input[$key]) ? sanitize_email($input[$key]) : $fallback;
            } elseif (in_array($key, array('recipient_email', 'cc_email', 'bcc_email'), true)) {
                $out[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : $fallback;
            } elseif (in_array($key, array('form_page_id', 'status_page_id', 'view_page_id', 'edit_page_id', 'top_page_id'), true)) {
                $out[$key] = isset($input[$key]) ? (string) absint($input[$key]) : $fallback;
            } elseif (in_array($key, array('status_page_url', 'view_page_url', 'edit_page_url', 'top_page_url', 'terms_url'), true)) {
                $out[$key] = isset($input[$key]) ? esc_url_raw($input[$key]) : $fallback;
            } else {
                $out[$key] = isset($input[$key]) ? sanitize_text_field($input[$key]) : $fallback;
            }
        }
        $mail_send_method = isset($input['mail_send_method']) ? sanitize_key($input['mail_send_method']) : (isset($base['mail_send_method']) ? $base['mail_send_method'] : 'wordpress');
        $out['mail_send_method'] = in_array($mail_send_method, array('wordpress', 'smtp'), true) ? $mail_send_method : 'wordpress';
        $out['smtp_host'] = isset($input['smtp_host']) ? sanitize_text_field($input['smtp_host']) : (isset($base['smtp_host']) ? $base['smtp_host'] : '');
        $out['smtp_port'] = isset($input['smtp_port']) ? (string) max(1, min(65535, absint($input['smtp_port']))) : (isset($base['smtp_port']) ? $base['smtp_port'] : '587');
        $smtp_encryption = isset($input['smtp_encryption']) ? sanitize_key($input['smtp_encryption']) : (isset($base['smtp_encryption']) ? $base['smtp_encryption'] : 'tls');
        $out['smtp_encryption'] = in_array($smtp_encryption, array('none', 'ssl', 'tls'), true) ? $smtp_encryption : 'tls';
        $out['smtp_auth'] = $partial && !array_key_exists('smtp_auth', $input) ? (isset($base['smtp_auth']) ? $base['smtp_auth'] : '1') : (!empty($input['smtp_auth']) ? '1' : '0');
        $out['smtp_username'] = isset($input['smtp_username']) ? sanitize_text_field($input['smtp_username']) : (isset($base['smtp_username']) ? $base['smtp_username'] : '');
        if (isset($input['smtp_password']) && (string) $input['smtp_password'] !== '') {
            $out['smtp_password'] = sanitize_text_field($input['smtp_password']);
        } else {
            $out['smtp_password'] = isset($base['smtp_password']) ? (string) $base['smtp_password'] : '';
        }
        foreach (array('turnstile_enabled','honeypot_enabled','rate_limit_enabled','turnstile_apply_form','turnstile_apply_status','turnstile_apply_edit','file_upload_enabled','delete_data_on_uninstall') as $bool_key) {
            if ($bool_key === 'turnstile_enabled' && $security_partial) {
                $out[$bool_key] = !empty($input[$bool_key]) ? '1' : '0';
                continue;
            }

            if (in_array($bool_key, array('turnstile_apply_form','turnstile_apply_status','turnstile_apply_edit'), true) && $security_partial) {
                if (empty($input['turnstile_enabled']) && !array_key_exists($bool_key, $input)) {
                    $out[$bool_key] = isset($base[$bool_key]) ? $base[$bool_key] : ($bool_key === 'turnstile_apply_form' ? '1' : '0');
                } else {
                    $out[$bool_key] = !empty($input[$bool_key]) ? '1' : '0';
                }
                continue;
            }

            $out[$bool_key] = $partial && !array_key_exists($bool_key, $input) ? (isset($base[$bool_key]) ? $base[$bool_key] : '0') : (!empty($input[$bool_key]) ? '1' : '0');
        }
        $theme = isset($input['turnstile_theme']) ? sanitize_key($input['turnstile_theme']) : (isset($base['turnstile_theme']) ? $base['turnstile_theme'] : 'auto');
        $out['turnstile_theme'] = in_array($theme, array('auto', 'light', 'dark'), true) ? $theme : 'auto';
        $size = isset($input['turnstile_size']) ? sanitize_key($input['turnstile_size']) : (isset($base['turnstile_size']) ? $base['turnstile_size'] : 'normal');
        $out['turnstile_size'] = in_array($size, array('normal', 'compact'), true) ? $size : 'normal';
        $ext = isset($input['file_allowed_extensions']) ? strtolower(sanitize_text_field($input['file_allowed_extensions'])) : (isset($base['file_allowed_extensions']) ? $base['file_allowed_extensions'] : $defaults['file_allowed_extensions']);
        $exts = array_filter(array_map('trim', explode(',', $ext)));
        $safe_exts = array();
        foreach ($exts as $e) {
            $e = preg_replace('/[^a-z0-9]/', '', $e);
            if ($e && !in_array($e, array('php','phtml','html','htm','js','svg'), true)) {
                $safe_exts[] = $e;
            }
        }
        $out['file_allowed_extensions'] = implode(',', array_unique($safe_exts ?: array('jpg','jpeg','png','pdf')));
        $out['file_max_size_mb'] = isset($input['file_max_size_mb']) ? (string) max(1, min(50, absint($input['file_max_size_mb']))) : (isset($base['file_max_size_mb']) ? $base['file_max_size_mb'] : '5');
        $out['file_max_uploads'] = isset($input['file_max_uploads']) ? (string) max(1, min(10, absint($input['file_max_uploads']))) : (isset($base['file_max_uploads']) ? $base['file_max_uploads'] : '3');
        $expire_minutes = isset($input['download_link_expire_minutes']) ? absint($input['download_link_expire_minutes']) : (isset($base['download_link_expire_minutes']) ? absint($base['download_link_expire_minutes']) : 30);
        $allowed_expire_minutes = array(0, 10, 30, 60, 1440);
        $out['download_link_expire_minutes'] = in_array($expire_minutes, $allowed_expire_minutes, true) ? (string) $expire_minutes : '30';
        $out['terms_text'] = isset($input['terms_text']) ? sanitize_text_field($input['terms_text']) : (isset($base['terms_text']) ? $base['terms_text'] : $defaults['terms_text']);
        $fallback_consents = isset($base['consent_items']) && is_array($base['consent_items']) ? $base['consent_items'] : self::default_consent_items();
        $out['consent_items'] = isset($input['consent_items']) ? $this->sanitize_consent_items($input['consent_items'], $fallback_consents) : $fallback_consents;
        $fallback_frontend_pages = isset($base['frontend_pages_by_lang']) && is_array($base['frontend_pages_by_lang']) ? $base['frontend_pages_by_lang'] : array();
        $out['frontend_pages_by_lang'] = isset($input['frontend_pages_by_lang']) ? $this->sanitize_frontend_pages_by_lang($input['frontend_pages_by_lang'], $fallback_frontend_pages) : $this->normalize_frontend_pages_by_lang($fallback_frontend_pages, $out);
        $fallback_downloads = isset($base['download_files']) && is_array($base['download_files']) ? $base['download_files'] : array();
        $out['download_files'] = isset($input['download_files']) ? $this->sanitize_download_files($input['download_files'], $fallback_downloads) : $fallback_downloads;
        $fallback_application_number_rule = isset($base['application_number_rule']) && is_array($base['application_number_rule']) ? $base['application_number_rule'] : self::default_application_number_rule();
        $out['application_number_rule'] = isset($input['application_number_rule']) ? $this->sanitize_application_number_rule($input['application_number_rule']) : $this->sanitize_application_number_rule($fallback_application_number_rule);
        $fallback_enabled_languages = isset($base['enabled_languages']) && is_array($base['enabled_languages']) ? $base['enabled_languages'] : self::get_default_enabled_languages();
        $out['enabled_languages'] = isset($input['enabled_languages']) ? self::sanitize_enabled_languages($input['enabled_languages']) : self::sanitize_enabled_languages($fallback_enabled_languages);
        self::apply_tcarm_role_capabilities(isset($out['allowed_roles']) ? $out['allowed_roles'] : array('administrator'));
        return $out;
    }

    public static function default_application_number_rule() {
        return array(
            array('type' => 'fixed', 'value' => 'APP'),
            array('type' => 'symbol', 'value' => '-'),
            array('type' => 'date', 'format' => 'Ymd'),
            array('type' => 'symbol', 'value' => '-'),
            array('type' => 'sequence', 'length' => 6),
        );
    }

    private function sanitize_application_number_rule($input) {
        if (!is_array($input)) {
            return self::default_application_number_rule();
        }

        $out = array();
        foreach ($input as $row) {
            if (!is_array($row) || !empty($row['_delete'])) {
                continue;
            }

            $type = isset($row['type']) ? sanitize_key($row['type']) : '';
            if ($type === 'fixed') {
                $value = isset($row['value']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $row['value']) : '';
                $value = substr($value, 0, 16);
                if ($value !== '') {
                    $out[] = array('type' => 'fixed', 'value' => $value);
                }
            } elseif ($type === 'symbol') {
                $value = isset($row['value']) ? (string) $row['value'] : '-';
                $out[] = array('type' => 'symbol', 'value' => $value === '_' ? '_' : '-');
            } elseif ($type === 'date') {
                $format = isset($row['format']) ? sanitize_key($row['format']) : 'Ymd';
                $out[] = array('type' => 'date', 'format' => in_array($format, array('Ymd', 'Ym', 'Y'), true) ? $format : 'Ymd');
            } elseif ($type === 'random_letters') {
                $length = isset($row['length']) ? absint($row['length']) : 2;
                $out[] = array('type' => 'random_letters', 'length' => max(1, min(8, $length)));
            } elseif ($type === 'random_numbers') {
                $length = isset($row['length']) ? absint($row['length']) : 2;
                $out[] = array('type' => 'random_numbers', 'length' => max(1, min(8, $length)));
            } elseif ($type === 'sequence') {
                $length = isset($row['length']) ? absint($row['length']) : 6;
                $out[] = array('type' => 'sequence', 'length' => max(1, min(12, $length)));
            }
        }

        return !empty($out) ? $out : self::default_application_number_rule();
    }

    private function sanitize_custom_css($css) {
        $css = (string) $css;
        $css = wp_strip_all_tags($css);
        $forbidden_patterns = array(
            '/<\s*\/?\s*script\b/i',
            '/expression\s*\(/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/@import\b/i',
            '/behavior\s*:/i',
            '/-moz-binding\s*:/i',
        );
        $css = preg_replace($forbidden_patterns, '', $css);
        return trim($css);
    }

    private function sanitize_consent_items($input, $fallback = array()) {
        $out = array();
        if (!is_array($input)) {
            return is_array($fallback) ? $fallback : array();
        }
        $i = 0;
        foreach ($input as $raw_key => $item) {
            if (!empty($item['_delete'])) {
                continue;
            }
            $label = isset($item['label']) ? sanitize_text_field($item['label']) : '';
            if ($label === '') {
                continue;
            }
            $key = sanitize_key($raw_key);
            if ($key === '' || strpos($key, 'new_') === 0) {
                $manual_id = isset($item['id']) ? sanitize_key($item['id']) : '';
                $base = $manual_id ? $manual_id : sanitize_key($label);
                $key = $base ? $base : 'consent_' . substr(md5($label . microtime()), 0, 8);
            }
            $base_key = $key;
            $suffix = 2;
            while (isset($out[$key])) {
                $key = $base_key . '_' . $suffix++;
            }
            $out[$key] = array(
                'label' => $label,
                'enabled' => !empty($item['enabled']) ? '1' : '0',
                'show_checkbox' => !empty($item['show_checkbox']) ? '1' : '0',
                'required' => (!empty($item['show_checkbox']) && !empty($item['required'])) ? '1' : '0',
                'sort_order' => isset($item['sort_order']) ? absint($item['sort_order']) : (($i + 1) * 10),
                'body' => isset($item['body']) ? sanitize_textarea_field($item['body']) : '',
                'checkbox_text' => isset($item['checkbox_text']) ? sanitize_text_field($item['checkbox_text']) : 'I agree to the content.',
                'link_url' => isset($item['link_url']) ? esc_url_raw($item['link_url']) : '',
                'link_text' => isset($item['link_text']) ? sanitize_text_field($item['link_text']) : '',
            );
            $i++;
        }
        uasort($out, function($a, $b) { return ((int) $a['sort_order']) <=> ((int) $b['sort_order']); });
        return $out;
    }

    private function normalize_frontend_pages_by_lang($value, $legacy_settings = array()) {
        $out = array();
        foreach (self::supported_languages() as $lang => $label) {
            $row = isset($value[$lang]) && is_array($value[$lang]) ? $value[$lang] : array();
            $out[$lang] = array(
                'form' => isset($row['form']) ? (string) absint($row['form']) : '0',
                'status' => isset($row['status']) ? (string) absint($row['status']) : '0',
                'view' => isset($row['view']) ? (string) absint($row['view']) : '0',
                'edit' => isset($row['edit']) ? (string) absint($row['edit']) : '0',
                'top' => isset($row['top']) ? (string) absint($row['top']) : '0',
            );
        }
        if (!empty($legacy_settings)) {
            $legacy_map = array(
                'form' => 'form_page_id',
                'status' => 'status_page_id',
                'view' => 'view_page_id',
                'edit' => 'edit_page_id',
                'top' => 'top_page_id',
            );
            foreach ($legacy_map as $type => $legacy_key) {
                if (empty($out['ja'][$type]) && !empty($legacy_settings[$legacy_key])) {
                    $out['ja'][$type] = (string) absint($legacy_settings[$legacy_key]);
                }
            }
        }
        return $out;
    }

    private function sanitize_frontend_pages_by_lang($input, $fallback = array()) {
        $fallback = $this->normalize_frontend_pages_by_lang($fallback, self::get_settings());
        $out = array();
        foreach (self::supported_languages() as $lang => $label) {
            $row = isset($input[$lang]) && is_array($input[$lang]) ? $input[$lang] : array();
            $fallback_row = isset($fallback[$lang]) ? $fallback[$lang] : array();
            foreach (array('form', 'status', 'view', 'edit', 'top') as $type) {
                $out[$lang][$type] = array_key_exists($type, $row) ? (string) absint($row[$type]) : (isset($fallback_row[$type]) ? (string) absint($fallback_row[$type]) : '0');
            }
        }
        return $out;
    }

    private function sanitize_download_files($input, $fallback = array()) {
        $out = array();
        if (!is_array($input)) {
            return is_array($fallback) ? $fallback : array();
        }
        $i = 0;
        foreach ($input as $raw_key => $item) {
            if (!is_array($item) || !empty($item['_delete'])) {
                continue;
            }
            $label = isset($item['label']) ? sanitize_text_field($item['label']) : '';
            $attachment_id = isset($item['attachment_id']) ? absint($item['attachment_id']) : 0;
            $file_url = isset($item['file_url']) ? esc_url_raw($item['file_url']) : '';
            if ($label === '' && $attachment_id) {
                $label = get_the_title($attachment_id);
            }
            if ($label === '' || (!$attachment_id && $file_url === '')) {
                continue;
            }
            $key = sanitize_key($raw_key);
            if ($key === '' || strpos($key, 'new_') === 0) {
                $key = 'file_' . substr(md5($label . '|' . $attachment_id . '|' . $file_url . '|' . microtime()), 0, 10);
            }
            $base_key = $key;
            $suffix = 2;
            while (isset($out[$key])) {
                $key = $base_key . '_' . $suffix++;
            }
            $out[$key] = array(
                'label' => $label,
                'description' => isset($item['description']) ? sanitize_textarea_field($item['description']) : '',
                'attachment_id' => $attachment_id,
                'file_url' => $file_url,
                'enabled' => !empty($item['enabled']) ? '1' : '0',
                'sort_order' => isset($item['sort_order']) ? absint($item['sort_order']) : (($i + 1) * 10),
            );
            $i++;
        }
        uasort($out, function($a, $b) { return ((int) $a['sort_order']) <=> ((int) $b['sort_order']); });
        return $out;
    }

    public function sanitize_sections($input) {
        $defaults = self::default_sections();
        $out = array();
        if (!is_array($input)) {
            return array();
        }
        $i = 0;
        foreach ($input as $raw_key => $section) {
            if (!empty($section['_delete'])) {
                continue;
            }
            $label = isset($section['label']) ? sanitize_text_field($section['label']) : '';
            if ($label === '') {
                continue;
            }
            $key = sanitize_key($raw_key);
            if ($key === '' || strpos($key, 'new_') === 0) {
                $manual_id = isset($section['id']) ? sanitize_key($section['id']) : '';
                $base = $manual_id ? $manual_id : sanitize_key($label);
                $key = $base ? $base : 'section_' . substr(md5($label . microtime()), 0, 8);
            }
            $base_key = $key;
            $suffix = 2;
            while (isset($out[$key])) {
                $key = $base_key . '_' . $suffix++;
            }
            $translations = array();
            if (!empty($section['translations']) && is_array($section['translations'])) {
                foreach (self::supported_languages() as $lang_code => $lang_label) {
                    if ($lang_code === 'ja') {
                        continue;
                    }
                    $src = isset($section['translations'][$lang_code]) && is_array($section['translations'][$lang_code]) ? $section['translations'][$lang_code] : array();
                    $translations[$lang_code] = array(
                        'label' => isset($src['label']) ? sanitize_text_field($src['label']) : '',
                    );
                }
            }
            $out[$key] = array(
                'label' => $label,
                'enabled' => !empty($section['enabled']) ? '1' : '0',
                'sort_order' => isset($section['sort_order']) ? absint($section['sort_order']) : (($i + 1) * 10),
                'translations' => $translations,
            );
            $i++;
        }
        return $out;
    }

    public function sanitize_fields($input) {
        $defaults = self::default_fields();
        $sections = self::get_sections();
        $current_fields = get_option(self::OPTION_FIELDS, array());
        $current_fields = is_array($current_fields) ? $current_fields : array();
        $out = array();
        $allowed_types = array('text', 'textarea', 'email', 'url', 'tel', 'date', 'checkbox', 'file', 'dropdown');
        if (!is_array($input) || empty($input)) {
            return array();
        }
        $i = 0;
        foreach ($input as $raw_key => $field) {
            if (!empty($field['_delete'])) {
                continue;
            }
            $key = sanitize_key($raw_key);
            $label = isset($field['label']) ? sanitize_text_field($field['label']) : '';
            if ($label === '') {
                continue;
            }
            if ($key === '' || strpos($key, 'new_') === 0) {
                $base = isset($field['key']) ? sanitize_key($field['key']) : '';
                if ($base === '') {
                    $base = sanitize_key($label);
                }
                $key = $base ? $base : 'field_' . substr(md5($label . microtime()), 0, 8);
            }
            $base_key = $key;
            $suffix = 2;
            while (isset($out[$key])) {
                $key = $base_key . '_' . $suffix++;
            }
            $type = isset($field['type']) ? sanitize_key($field['type']) : 'text';
            if (!in_array($type, $allowed_types, true)) {
                $type = 'text';
            }
            $section = isset($field['section']) ? sanitize_key($field['section']) : 'event';
            if (!isset($sections[$section])) {
                $section_keys = array_keys($sections);
                $section = !empty($section_keys) ? $section_keys[0] : 'event';
            }
            $choices = array();
            if ($type === 'dropdown' && !empty($field['choices']) && is_array($field['choices'])) {
                foreach ($field['choices'] as $choice) {
                    if (!is_array($choice)) {
                        continue;
                    }
                    $choice_label = isset($choice['label']) ? sanitize_text_field($choice['label']) : '';
                    $choice_value = isset($choice['value']) ? sanitize_title($choice['value']) : '';
                    if ($choice_label === '' && $choice_value === '') {
                        continue;
                    }
                    if ($choice_value === '') {
                        $choice_value = sanitize_title($choice_label);
                    }
                    if ($choice_label === '') {
                        $choice_label = $choice_value;
                    }
                    if ($choice_value === '') {
                        continue;
                    }
                    $choices[] = array(
                        'label' => $choice_label,
                        'value' => $choice_value,
                    );
                }
            }
            $translations = array();
            if (!empty($field['translations']) && is_array($field['translations'])) {
                foreach (self::supported_languages() as $lang_code => $lang_label) {
                    if ($lang_code === 'ja') {
                        continue;
                    }
                    $src = isset($field['translations'][$lang_code]) && is_array($field['translations'][$lang_code]) ? $field['translations'][$lang_code] : array();
                    $translations[$lang_code] = array(
                        'label' => isset($src['label']) ? sanitize_text_field($src['label']) : '',
                        'placeholder' => isset($src['placeholder']) ? sanitize_text_field($src['placeholder']) : '',
                        'description' => isset($src['description']) ? sanitize_textarea_field($src['description']) : '',
                    );
                }
            }
            $legacy_field = isset($current_fields[$key]) && is_array($current_fields[$key]) ? $current_fields[$key] : array();
            $out[$key] = array(
                'label' => $label,
                'type' => $type,
                'section' => $section,
                'enabled' => !empty($field['enabled']) ? '1' : '0',
                'required' => !empty($field['required']) ? '1' : '0',
                'public' => isset($field['public']) ? (!empty($field['public']) ? '1' : '0') : (isset($legacy_field['public']) ? (string) $legacy_field['public'] : '0'),
                'placeholder' => isset($field['placeholder']) ? sanitize_text_field($field['placeholder']) : '',
                'description' => isset($field['description']) ? sanitize_textarea_field($field['description']) : '',
                'translations' => $translations,
                'acf_key' => isset($field['acf_key']) ? sanitize_key($field['acf_key']) : (isset($legacy_field['acf_key']) ? sanitize_key($legacy_field['acf_key']) : ''),
                'sort_order' => isset($field['sort_order']) ? absint($field['sort_order']) : (($i + 1) * 10),
                'choices' => $choices,
                'taxonomy_enabled' => isset($field['taxonomy_enabled']) ? (($type === 'dropdown' && !empty($field['taxonomy_enabled'])) ? '1' : '0') : (isset($legacy_field['taxonomy_enabled']) ? (string) $legacy_field['taxonomy_enabled'] : '0'),
                'taxonomy_slug' => isset($field['taxonomy_slug']) ? (($type === 'dropdown') ? sanitize_key($field['taxonomy_slug']) : '') : (isset($legacy_field['taxonomy_slug']) ? sanitize_key($legacy_field['taxonomy_slug']) : ''),
            );
            $i++;
        }
        return !empty($out) ? $out : $defaults;
    }

    public static function get_settings() {
        return wp_parse_args(get_option(self::OPTION_SETTINGS, array()), self::default_settings());
    }

    public static function get_download_files($enabled_only = false) {
        $settings = self::get_settings();
        $files = isset($settings['download_files']) && is_array($settings['download_files']) ? $settings['download_files'] : array();
        foreach ($files as $key => &$file) {
            $file = wp_parse_args($file, array(
                'label' => '',
                'description' => '',
                'attachment_id' => 0,
                'file_url' => '',
                'enabled' => '1',
                'sort_order' => 999,
            ));
            $file['key'] = sanitize_key($key);
        }
        unset($file);
        if ($enabled_only) {
            $files = array_filter($files, function($file) {
                return !empty($file['enabled']) && $file['enabled'] === '1' && (!empty($file['attachment_id']) || !empty($file['file_url']));
            });
        }
        uasort($files, function($a, $b) { return ((int) $a['sort_order']) <=> ((int) $b['sort_order']); });
        return $files;
    }

    private function get_download_file($file_key) {
        $file_key = sanitize_key($file_key);
        $files = self::get_download_files(false);
        return isset($files[$file_key]) ? $files[$file_key] : null;
    }

    public static function get_consent_items() {
        $settings = self::get_settings();
        $raw_settings = get_option(self::OPTION_SETTINGS, array());
        if (is_array($raw_settings) && array_key_exists('consent_items', $raw_settings) && is_array($raw_settings['consent_items'])) {
            $items = $raw_settings['consent_items'];
        } else {
            $items = self::default_consent_items();
            if (!empty($items) && !empty($settings['terms_text']) && isset($items['usage_guidelines'])) {
                $items['usage_guidelines']['body'] = $settings['terms_text'];
                $items['usage_guidelines']['checkbox_text'] = $settings['terms_text'];
            }
        }
        foreach ($items as $key => &$item) {
            $item = wp_parse_args($item, array(
                'label' => $key,
                'enabled' => '1',
                'show_checkbox' => '1',
                'required' => '1',
                'sort_order' => 999,
                'body' => '',
                'checkbox_text' => 'I agree to the content.',
                'link_url' => '',
                'link_text' => '',
            ));
            if ($key === 'usage_guidelines' && empty($item['link_url']) && !empty($settings['terms_url'])) {
                $item['link_url'] = $settings['terms_url'];
            }
            if (empty($item['show_checkbox']) || $item['show_checkbox'] !== '1') {
                $item['show_checkbox'] = '0';
                $item['required'] = '0';
            } else {
                $item['show_checkbox'] = '1';
                $item['required'] = !empty($item['required']) && $item['required'] === '1' ? '1' : '0';
            }
        }
        unset($item);
        uasort($items, function($a, $b) { return ((int) $a['sort_order']) <=> ((int) $b['sort_order']); });
        return $items;
    }

    public static function get_sections() {
        $sections = get_option(self::OPTION_SECTIONS, array());
        if (!is_array($sections)) {
            return array();
        }
        $defaults = self::default_sections();
        foreach ($sections as $key => $section) {
            $base = array('label' => $key, 'enabled' => '1', 'sort_order' => 999, 'translations' => array());
            if (isset($defaults[$key])) {
                $base = wp_parse_args($defaults[$key], $base);
            }
            $sections[$key] = wp_parse_args(is_array($section) ? $section : array(), $base);
            if (!isset($sections[$key]['translations']) || !is_array($sections[$key]['translations'])) {
                $sections[$key]['translations'] = array();
            }
        }
        uasort($sections, function($a, $b) {
            $ao = isset($a['sort_order']) ? (int) $a['sort_order'] : 999;
            $bo = isset($b['sort_order']) ? (int) $b['sort_order'] : 999;
            return $ao <=> $bo;
        });
        return $sections;
    }

    public static function normalize_section_key($value) {
        $legacy = array(
            '申請者情報' => 'applicant',
            '使用内容' => 'usage',
            'イベント情報' => 'event',
            '同意事項' => 'agreement',
        );
        if (isset($legacy[$value])) {
            return $legacy[$value];
        }
        $key = sanitize_key($value);
        return $key ? $key : 'event';
    }

    public static function section_label($section_key) {
        $sections = self::get_sections();
        $key = self::normalize_section_key($section_key);
        return isset($sections[$key]['label']) ? $sections[$key]['label'] : $section_key;
    }

    public static function get_fields() {
        $fields = get_option(self::OPTION_FIELDS, array());
        if (!is_array($fields)) {
            return array();
        }
        $defaults = self::default_fields();
        foreach ($fields as $key => $field) {
            $base = array('label' => $key, 'type' => 'text', 'section' => 'event', 'enabled' => '1', 'required' => '0', 'public' => '0', 'acf_key' => '', 'placeholder' => '', 'description' => '', 'translations' => array(), 'sort_order' => 999, 'choices' => array(), 'taxonomy_enabled' => '0', 'taxonomy_slug' => '');
            if (isset($defaults[$key])) {
                $base = wp_parse_args($defaults[$key], $base);
            }
            $fields[$key] = wp_parse_args($field, $base);
            $fields[$key]['section'] = self::normalize_section_key(isset($fields[$key]['section']) ? $fields[$key]['section'] : 'event');
        }
        uasort($fields, function($a, $b) {
            $ao = isset($a['sort_order']) ? (int) $a['sort_order'] : 999;
            $bo = isset($b['sort_order']) ? (int) $b['sort_order'] : 999;
            return $ao <=> $bo;
        });
        return $fields;
    }

    private function render_lang_page_select_row($lang, $type, $label, $shortcode_hint, $settings) {
        $page_map = isset($settings['frontend_pages_by_lang']) && is_array($settings['frontend_pages_by_lang']) ? $settings['frontend_pages_by_lang'] : array();
        $page_map = $this->normalize_frontend_pages_by_lang($page_map, $settings);
        $selected = isset($page_map[$lang][$type]) ? absint($page_map[$lang][$type]) : 0;
        $current_url = $selected ? get_permalink($selected) : '';
        ob_start();
        ?>
        <div class="tcarm-settings-page-row tcarm-settings-split-row">
            <div class="tcarm-settings-label-col">
                <strong><?php echo esc_html($label); ?></strong>
                <span class="description"><?php echo esc_html__('Shortcode to place: ', 'shinseiflow-application-review'); ?><code><?php echo esc_html($shortcode_hint); ?></code></span>
            </div>
            <div class="tcarm-settings-control-col">
                <?php
                echo wp_kses(
                    wp_dropdown_pages(array(
                    'name' => self::OPTION_SETTINGS . '[frontend_pages_by_lang][' . $lang . '][' . $type . ']',
                    'selected' => $selected,
                    'show_option_none' => __('Select a page', 'shinseiflow-application-review'),
                    'option_none_value' => '0',
                    'echo' => false,
                    )),
                    $this->frontend_page_select_row_allowed_html()
                );
                ?>
                <?php if ($current_url): ?>
                    <p class="description tcarm-settings-current-url"><?php echo esc_html__('Current URL: ', 'shinseiflow-application-review'); ?><a href="<?php echo esc_url($this->add_lang_to_url($current_url, $lang)); ?>" target="_blank" rel="noopener"><?php echo esc_html($this->add_lang_to_url($current_url, $lang)); ?></a></p>
                <?php else: ?>
                    <p class="description tcarm-settings-current-url"><?php echo esc_html__('Current URL: Not set', 'shinseiflow-application-review'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function frontend_page_select_row_allowed_html() {
        return array(
            'div' => array(
                'class' => true,
            ),
            'strong' => array(),
            'span' => array(
                'class' => true,
            ),
            'code' => array(),
            'select' => array(
                'name' => true,
                'id' => true,
                'class' => true,
            ),
            'option' => array(
                'value' => true,
                'selected' => true,
                'class' => true,
            ),
            'p' => array(
                'class' => true,
            ),
            'a' => array(
                'href' => true,
                'target' => true,
                'rel' => true,
            ),
        );
    }

    private function render_language_frontend_page_settings($settings) {
        $rows = array(
            array('form', __('Application Form Page', 'shinseiflow-application-review'), '[tcarm_form lang="%s" show_steps="yes"]'),
            array('status', __('Application Status Page', 'shinseiflow-application-review'), '[tcarm_status lang="%s"]'),
            array('view', __('Submitted Content Page', 'shinseiflow-application-review'), '[tcarm_view lang="%s"]'),
            array('edit', __('Edit and Resubmit Page', 'shinseiflow-application-review'), '[tcarm_edit lang="%s"]'),
            array('top', __('Top Page', 'shinseiflow-application-review'), __('Back to top link', 'shinseiflow-application-review')),
        );
        $languages = self::get_enabled_languages(false);
        if (empty($languages)) {
            $supported = self::supported_languages();
            $languages = array('en' => $supported['en']);
        }
        ob_start();
        ?>
        <div class="tcarm-lang-page-settings-card">
            <div class="tcarm-display-tabs tcarm-lang-tabs" role="tablist" aria-label="<?php echo esc_attr__('Frontend page settings language tabs', 'shinseiflow-application-review'); ?>">
                <?php $i = 0; foreach ($languages as $lang => $label): ?>
                    <button type="button" class="<?php echo esc_attr('tcarm-display-tab tcarm-lang-tab' . ($i === 0 ? ' is-active' : '')); ?>" data-lang-panel="<?php echo esc_attr($lang); ?>" role="tab" aria-selected="<?php echo esc_attr($i === 0 ? 'true' : 'false'); ?>"><?php echo esc_html($label); ?></button>
                <?php $i++; endforeach; ?>
            </div>
            <?php $i = 0; foreach ($languages as $lang => $label): ?>
                <section class="<?php echo esc_attr('tcarm-display-panel tcarm-lang-panel' . ($i === 0 ? ' is-active' : '')); ?>" data-lang-panel="<?php echo esc_attr($lang); ?>" role="tabpanel">
                    <div class="tcarm-settings-row-list tcarm-page-settings-list">
                        <?php foreach ($rows as $row):
                            $hint = $row[2];
                            if (strpos($hint, '%s') !== false) {
                                $hint = sprintf($hint, $lang);
                            }
                            echo wp_kses($this->render_lang_page_select_row($lang, $row[0], $row[1], $hint, $settings), $this->frontend_page_select_row_allowed_html());
                        endforeach; ?>
                    </div>
                </section>
            <?php $i++; endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_page_select_row($label, $key, $shortcode_hint, $settings) {
        $selected = isset($settings[$key]) ? absint($settings[$key]) : 0;
        $current_url = $selected ? get_permalink($selected) : '';
        ob_start();
        ?>
        <div class="tcarm-settings-page-row tcarm-settings-split-row">
            <div class="tcarm-settings-label-col">
                <strong><?php echo esc_html($label); ?></strong>
                <span class="description"><?php echo esc_html__('Shortcode to place: ', 'shinseiflow-application-review'); ?><code><?php echo esc_html($shortcode_hint); ?></code></span>
            </div>
            <div class="tcarm-settings-control-col">
                <?php
                echo wp_kses(
                    wp_dropdown_pages(array(
                    'name' => self::OPTION_SETTINGS . '[' . $key . ']',
                    'selected' => $selected,
                    'show_option_none' => __('Select a page', 'shinseiflow-application-review'),
                    'option_none_value' => '0',
                    'echo' => false,
                    )),
                    $this->frontend_page_select_row_allowed_html()
                );
                ?>
                <?php if ($current_url): ?>
                    <p class="description tcarm-settings-current-url"><?php echo esc_html__('Current URL: ', 'shinseiflow-application-review'); ?><a href="<?php echo esc_url($current_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($current_url); ?></a></p>
                <?php else: ?>
                    <p class="description tcarm-settings-current-url"><?php echo esc_html__('Current URL: Not set', 'shinseiflow-application-review'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}
