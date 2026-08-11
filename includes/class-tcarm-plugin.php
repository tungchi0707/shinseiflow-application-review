<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Plugin_Core_Trait {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    public static function blocked_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::BLOCKED_TABLE;
    }

    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $installed_db_version = get_option('tcarm_db_version');
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::table_name();
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            application_code VARCHAR(32) NOT NULL,
            applicant_name VARCHAR(190) NOT NULL,
            contact_email VARCHAR(190) NOT NULL,
            contact_phone VARCHAR(80) DEFAULT '' NOT NULL,
            organization_name VARCHAR(190) DEFAULT '' NOT NULL,
            usage_purpose TEXT NULL,
            usage_period VARCHAR(190) DEFAULT '' NOT NULL,
            media VARCHAR(190) DEFAULT '' NOT NULL,
            event_title VARCHAR(255) DEFAULT '' NOT NULL,
            event_period VARCHAR(190) DEFAULT '' NOT NULL,
            event_location VARCHAR(255) DEFAULT '' NOT NULL,
            event_contact VARCHAR(255) DEFAULT '' NOT NULL,
            event_fee VARCHAR(190) DEFAULT '' NOT NULL,
            event_available_time VARCHAR(190) DEFAULT '' NOT NULL,
            related_link TEXT NULL,
            genre VARCHAR(190) DEFAULT '' NOT NULL,
            event_description LONGTEXT NULL,
            status VARCHAR(40) DEFAULT 'pending' NOT NULL,
            admin_note TEXT NULL,
            reject_reason TEXT NULL,
            request_note TEXT NULL,
            public_post_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            resubmit_count INT(10) UNSIGNED DEFAULT 0 NOT NULL,
            submitted_ip VARCHAR(80) DEFAULT '' NOT NULL,
            user_agent TEXT NULL,
            form_data_json LONGTEXT NULL,
            history_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            reviewed_at DATETIME NULL,
            last_resubmitted_at DATETIME NULL,
            last_status_changed_at DATETIME NULL,
            published_at DATETIME NULL,
            deleted_at DATETIME NULL,
            deleted_by BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY application_code (application_code),
            KEY status (status),
            KEY public_post_id (public_post_id),
            KEY created_at (created_at),
            KEY deleted_at (deleted_at)
        ) {$charset_collate};";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Plugin-owned custom application table creation is required on activation.
        dbDelta($sql);

        $blocked_table = self::blocked_table_name();
        $blocked_sql = "CREATE TABLE {$blocked_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(40) DEFAULT '' NOT NULL,
            reason_key VARCHAR(80) DEFAULT '' NOT NULL,
            reason_label VARCHAR(190) DEFAULT '' NOT NULL,
            applicant_name VARCHAR(190) DEFAULT '' NOT NULL,
            contact_phone VARCHAR(80) DEFAULT '' NOT NULL,
            contact_email VARCHAR(190) DEFAULT '' NOT NULL,
            ip_address VARCHAR(80) DEFAULT '' NOT NULL,
            user_agent TEXT NULL,
            source_url TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY reason_key (reason_key),
            KEY event_type (event_type)
        ) {$charset_collate};";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Plugin-owned custom blocked log table creation is required on activation.
        dbDelta($blocked_sql);

        if (!get_option(self::OPTION_SETTINGS)) {
            add_option(self::OPTION_SETTINGS, self::default_settings());
        }
        if (!get_option(self::OPTION_FIELDS)) {
            add_option(self::OPTION_FIELDS, self::default_fields());
        }
        if (!get_option(self::OPTION_SECTIONS)) {
            add_option(self::OPTION_SECTIONS, self::default_sections());
        }
        if (!get_option(self::OPTION_TRANSLATIONS)) {
            add_option(self::OPTION_TRANSLATIONS, array(
                'ja' => self::default_japanese_translation_strings(),
                'en' => self::default_translation_strings(),
                'zh-Hant' => self::default_traditional_chinese_translation_strings(),
                'zh-Hans' => self::default_simplified_chinese_translation_strings(),
                'ko' => self::default_korean_translation_strings(),
            ));
        }
        $translation_migration_complete = true;
        if ($installed_db_version !== false && (string) $installed_db_version !== '' && version_compare((string) $installed_db_version, '0.1.59', '<')) {
            $translation_migration_complete = self::migrate_japanese_translation_defaults();
        }
        if ($translation_migration_complete && $installed_db_version !== false && (string) $installed_db_version !== '' && version_compare((string) $installed_db_version, '0.1.60', '<')) {
            $translation_migration_complete = self::migrate_new_language_translation_defaults();
        }
        if ($translation_migration_complete && $installed_db_version !== false && (string) $installed_db_version !== '' && version_compare((string) $installed_db_version, self::DB_VERSION, '<')) {
            $translation_migration_complete = self::migrate_base_language_setting();
        }
        if ($translation_migration_complete) {
            update_option('tcarm_db_version', self::DB_VERSION);
        }
        self::apply_tcarm_role_capabilities();
        self::schedule_pending_upload_cleanup();
    }

    public static function deactivate() {
        self::unschedule_pending_upload_cleanup();
    }

    public function maybe_upgrade() {
        $installed = get_option('tcarm_db_version');
        if ($installed !== self::DB_VERSION) {
            self::activate();
        } elseif (!self::role_has_tcarm_capability('administrator')) {
            self::apply_tcarm_role_capabilities();
        }
    }

    private function get_request_ip() {
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    }

    private function validate_consent_items_from_post() {
        $errors = array();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Consent values are read only inside frontend submit flows that verify the form nonce before validation.
        $posted = isset($_POST['tcarm_consents']) && is_array($_POST['tcarm_consents']) ? array_map('sanitize_text_field', wp_unslash($_POST['tcarm_consents'])) : array();
        foreach (self::get_consent_items() as $key => $item) {
            $item = $this->apply_consent_translation($item);
            $show_checkbox = isset($item['show_checkbox']) ? $item['show_checkbox'] === '1' : true;
            if ($item['enabled'] !== '1' || !$show_checkbox || $item['required'] !== '1') {
                continue;
            }
            if (empty($posted[$key]) || (string) $posted[$key] !== '1') {
                $errors[] = sprintf(
                    /* translators: %s: consent item label. */
                    __( 'You must agree to %s.', 'shinseiflow-application-review' ),
                    $item['label']
                );
            }
        }
        return $errors;
    }

    private function render_consent_items() {
        $items = self::get_consent_items();
        $visible = array();
        foreach ($items as $key => $item) {
            if ($item['enabled'] === '1') {
                $visible[$key] = $this->apply_consent_translation($item);
            }
        }
        if (!$visible) {
            return '';
        }
        $html = '<section class="tcarm-front-section tcarm-front-section--agreements tcarm-consent-wrapper"><h2 class="tcarm-front-section-title tcarm-form-section-title">' . esc_html($this->t('common.consent_items', 'Consent Items')) . '</h2><div class="tcarm-front-agreements">';
        foreach ($visible as $key => $item) {
            $show_checkbox = isset($item['show_checkbox']) ? $item['show_checkbox'] === '1' : true;
            $required = $show_checkbox && $item['required'] === '1';
            $body = trim((string) $item['body']);
            $checkbox_text = !empty($item['checkbox_text']) ? $item['checkbox_text'] : __( 'I agree to the content.', 'shinseiflow-application-review' );
            $link_url = !empty($item['link_url']) ? esc_url($item['link_url']) : '';
            $link_text = !empty($item['link_text']) ? $item['link_text'] : sprintf(
                /* translators: %s: consent item label. */
                __( 'Open %s', 'shinseiflow-application-review' ),
                $item['label']
            );
            $html .= '<div class="tcarm-front-agreement tcarm-consent-item">';
            $html .= '<h3 class="tcarm-front-agreement-title">' . esc_html($item['label']) . ($required ? ' <span class="tcarm-required tcarm-front-required tcarm-required-mark">' . esc_html($this->t('common.required', 'Required')) . '</span>' : '') . '</h3>';
            if ($body !== '') {
                $html .= '<div class="tcarm-front-agreement-text tcarm-consent-scroll" tabindex="0">' . nl2br(esc_html($body)) . '</div>';
            }
            if ($link_url !== '') {
                $html .= '<p class="tcarm-front-agreement-link tcarm-consent-link"><a class="tcarm-front-agreement-link-anchor" href="' . $link_url . '" target="_blank" rel="noopener">' . esc_html($link_text) . '</a></p>';
            }
            if ($show_checkbox) {
                $html .= '<p class="tcarm-front-agreement-checkbox tcarm-terms tcarm-consent-check"><label><input class="tcarm-front-checkbox tcarm-choice-item" type="checkbox" name="tcarm_consents[' . esc_attr($key) . ']" value="1" data-tcarm-validate="checkbox"' . ($required ? ' required' : '') . '> ' . esc_html($checkbox_text) . '</label></p>';
            }
            $html .= '</div>';
        }
        $html .= '</div></section>';
        return $html;
    }

    private function turnstile_enabled_for($context = 'form') {
        $settings = self::get_settings();
        if (!in_array($context, array('form', 'status', 'edit'), true)) {
            return false;
        }
        if ($settings['turnstile_enabled'] !== '1' || empty($settings['turnstile_site_key']) || empty($settings['turnstile_secret_key'])) {
            return false;
        }
        $key = 'turnstile_apply_' . $context;
        return isset($settings[$key]) && $settings[$key] === '1';
    }

    private function render_turnstile_widget($context = 'form') {
        $settings = self::get_settings();
        if (!$this->turnstile_enabled_for($context)) {
            return '';
        }
        $theme = in_array($settings['turnstile_theme'], array('auto', 'light', 'dark'), true) ? $settings['turnstile_theme'] : 'auto';
        $size = in_array($settings['turnstile_size'], array('normal', 'compact'), true) ? $settings['turnstile_size'] : 'normal';
        return '<div class="tcarm-turnstile-wrap"><div class="cf-turnstile" data-sitekey="' . esc_attr($settings['turnstile_site_key']) . '" data-theme="' . esc_attr($theme) . '" data-size="' . esc_attr($size) . '"></div></div>';
    }

    private function verify_turnstile_response() {
        $settings = self::get_settings();
        if ($settings['turnstile_enabled'] !== '1' || empty($settings['turnstile_site_key']) || empty($settings['turnstile_secret_key'])) {
            return true;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Turnstile response is verified inside nonce-protected frontend form and lookup submit flows.
        $response = isset($_POST['cf-turnstile-response']) ? sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])) : '';
        if (empty($settings['turnstile_secret_key']) || empty($response)) {
            return false;
        }
        $verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'timeout' => 10,
            'body' => array(
                'secret' => $settings['turnstile_secret_key'],
                'response' => $response,
                'remoteip' => $this->get_request_ip(),
            ),
        ));
        if (is_wp_error($verify)) {
            return false;
        }
        $body = json_decode(wp_remote_retrieve_body($verify), true);
        return !empty($body['success']);
    }

    private function application_data_hash($data) {
        $fields = self::get_fields();
        $normalized = array();
        foreach ($fields as $key => $field) {
            $normalized[$key] = isset($data[$key]) ? (string) $data[$key] : '';
        }
        return md5(wp_json_encode($normalized));
    }

    private function check_rate_limit_if_enabled($scope, $identifier, $limit, $seconds) {
        $settings = self::get_settings();
        if (empty($settings['rate_limit_enabled']) || $settings['rate_limit_enabled'] !== '1') {
            return true;
        }
        return $this->check_rate_limit($scope, $identifier, $limit, $seconds);
    }

    private function is_too_fast_submission($minimum_seconds = 3) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Loaded-at timestamp is read inside nonce-protected frontend submit flows for bot protection timing.
        $loaded_at = isset($_POST['tcarm_form_loaded_at']) ? absint(wp_unslash($_POST['tcarm_form_loaded_at'])) : 0;
        if (!$loaded_at) {
            return false;
        }
        return (time() - $loaded_at) < $minimum_seconds;
    }

    private function extract_contact_from_post() {
        return array(
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Contact values are read for validation/logging inside nonce-protected frontend submit and lookup flows.
            'applicant_name' => isset($_POST['applicant_name']) ? sanitize_text_field(wp_unslash($_POST['applicant_name'])) : '',
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Contact values are read for validation/logging inside nonce-protected frontend submit and lookup flows.
            'contact_phone' => isset($_POST['contact_phone']) ? sanitize_text_field(wp_unslash($_POST['contact_phone'])) : '',
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Contact values are read for validation/logging inside nonce-protected frontend submit and lookup flows.
            'contact_email' => isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '',
        );
    }

    private function log_blocked_submission($event_type, $reason_key, $reason_label, $data = array()) {
        global $wpdb;
        $data = is_array($data) ? $data : array();
        $source_url = '';
        $http_referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if ($http_referer !== '') {
            $source_url = $http_referer;
        } elseif ($request_uri !== '') {
            $source_url = esc_url_raw(home_url('/' . ltrim($request_uri, '/')));
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom blocked log table insert; WordPress core APIs do not apply.
        $wpdb->insert(self::blocked_table_name(), array(
            'event_type' => sanitize_text_field($event_type),
            'reason_key' => sanitize_key($reason_key),
            'reason_label' => sanitize_text_field($reason_label),
            'applicant_name' => isset($data['applicant_name']) ? sanitize_text_field($data['applicant_name']) : '',
            'contact_phone' => isset($data['contact_phone']) ? sanitize_text_field($data['contact_phone']) : '',
            'contact_email' => isset($data['contact_email']) ? sanitize_email($data['contact_email']) : '',
            'ip_address' => $this->get_request_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'source_url' => $source_url,
            'created_at' => current_time('mysql'),
        ), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
        self::flush_application_cache();
    }

    private function check_rate_limit($scope, $identifier, $limit, $seconds) {
        $identifier = $identifier ? $identifier : 'unknown';
        $key = 'tcarm_rl_' . md5($scope . '|' . $identifier);
        $count = (int) get_transient($key);
        if ($count >= $limit) {
            return false;
        }
        set_transient($key, $count + 1, $seconds);
        return true;
    }
}
