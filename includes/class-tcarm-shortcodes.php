<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Shortcodes_Trait {
    public function shortcode_application_form($atts = array()) {
        $this->set_current_form_options_from_shortcode($atts);
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Step only selects the frontend form state; confirm submissions verify the frontend nonce immediately below.
        $step = isset($_POST['tcarm_step']) ? sanitize_key(wp_unslash($_POST['tcarm_step'])) : 'form';
        $errors = array();
        $data = array();
        if ($step === 'confirm') {
            check_admin_referer('tcarm_frontend_form', 'tcarm_nonce');
            $settings = self::get_settings();
            if ($settings['honeypot_enabled'] === '1' && $this->posted_honeypot_has_value()) {
                $this->log_blocked_submission('Application submission', 'honeypot', 'Hidden field was filled in', $this->extract_contact_from_post());
                return $this->render_frontend_form(array(), array(__('Could not verify your input. Please wait a moment and try again.', 'shinseiflow-application-review')));
            }
            if ($settings['honeypot_enabled'] === '1' && $this->is_too_fast_submission()) {
                $this->log_blocked_submission('Application submission', 'too_fast', 'Submission time was too short', $this->extract_contact_from_post());
                return $this->render_frontend_form(array(), array(__('The submission was too fast. Please check your input and try again.', 'shinseiflow-application-review')));
            }
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Full form payload is sanitized by sanitize_application_data() immediately after nonce verification.
            $data = $this->sanitize_application_data($_POST);
            $file_errors = $this->process_file_uploads($data);
            $errors = array_merge($this->validate_application_data($data), $file_errors, $this->validate_consent_items_from_post());
            if ($this->turnstile_enabled_for('form') && !$this->verify_turnstile_response()) {
                $errors[] = __('Robot prevention verification failed. Please try again.', 'shinseiflow-application-review');
            }
            if ($errors) {
                return $this->render_frontend_form($data, $errors);
            }
            return $this->render_confirm($data);
        }
        return $this->render_frontend_form(array(), array());
    }

    public function handle_frontend_submit() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Submit marker only routes to the edit handler; the handler verifies its own nonce.
        if (isset($_POST['tcarm_edit_final_submit'])) {
            $this->handle_frontend_edit_submit();
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Submit marker only detects whether this request belongs to the frontend form; nonce is verified immediately below.
        if (!isset($_POST['tcarm_final_submit'])) {
            return;
        }
        if (!isset($_POST['tcarm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tcarm_nonce'])), 'tcarm_frontend_form')) {
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        $settings = self::get_settings();
        if ($settings['honeypot_enabled'] === '1' && $this->posted_honeypot_has_value()) {
            $this->log_blocked_submission('Application submission', 'honeypot', 'Hidden field was filled in', $this->extract_contact_from_post());
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        if ($settings['honeypot_enabled'] === '1' && $this->is_too_fast_submission()) {
            $this->log_blocked_submission('Application submission', 'too_fast', 'Submission time was too short', $this->extract_contact_from_post());
            wp_die(esc_html__('The submission was too fast. Please check your input and try again.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Full form payload is sanitized by sanitize_application_data() immediately after nonce verification.
        $data = $this->sanitize_application_data($_POST);
        $errors = $this->validate_application_data($data);
        if ($this->turnstile_enabled_for('form')) {
            $verified = isset($_POST['tcarm_turnstile_verified']) ? sanitize_text_field(wp_unslash($_POST['tcarm_turnstile_verified'])) : '';
            if (!$verified || !wp_verify_nonce($verified, 'tcarm_turnstile_verified_' . $this->application_data_hash($data))) {
                $errors[] = __('Robot prevention verification failed. Please try again.', 'shinseiflow-application-review');
            }
        }
        if ($errors) {
            set_transient('tcarm_last_errors_' . get_current_user_id(), $errors, 60);
            return;
        }
        $ip = $this->get_request_ip();
        if (!$this->check_rate_limit_if_enabled('submit_ip', $ip, 5, 10 * MINUTE_IN_SECONDS) || !$this->check_rate_limit_if_enabled('submit_email', strtolower($data['contact_email']), 3, 10 * MINUTE_IN_SECONDS)) {
            $this->log_blocked_submission('Application submission', 'rate_limit', 'Repeated submissions in a short time', $data);
            wp_die(esc_html__('Too many submissions were made. Please wait a while and try again.', 'shinseiflow-application-review'));
        }
        global $wpdb;
        $now = current_time('mysql');
        $code = $this->generate_application_code();
        $insert = array_merge($data, array(
            'form_data_json' => wp_json_encode($data),
            'application_code' => $code,
            'status' => 'pending',
            'submitted_ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $filtered_insert = self::filter_db_data($insert);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table insert; WordPress core APIs do not apply.
        $wpdb->insert(self::table_name(), $filtered_insert, $this->application_db_formats_for($filtered_insert));
        self::flush_application_cache();
        $id = (int) $wpdb->insert_id;
        if ($id) {
            $data = $this->migrate_application_attachments($id, $data);
            $filtered_update = self::filter_db_data(array_merge($data, array(
                'form_data_json' => wp_json_encode($data),
                'updated_at' => $now,
            )));
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table post-insert attachment update; WordPress core APIs do not apply.
            $wpdb->update(self::table_name(), $filtered_update, array('id' => $id), $this->application_db_formats_for($filtered_update), array('%d'));
            self::flush_application_cache();
        }
        $item = $this->get_application($id);
        if ($item) {
            $this->append_application_history($id, 'application_received', 'Applicant');
            $this->send_template_email($item->contact_email, 'received', $item);
            $this->append_application_history($id, 'applicant_auto_reply_sent', 'System');
            $settings = self::get_settings();
            $this->send_template_email($settings['recipient_email'], 'admin', $item, true);
            $this->append_application_history($id, 'admin_notification_sent', 'System');
        }
        $success_args = array('tcarm_submitted' => '1', 'tcarm_code' => rawurlencode($code));
        $submit_lang = isset($_POST['tcarm_lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_POST['tcarm_lang']))) : '';
        if ($this->should_add_lang_query($submit_lang)) {
            $success_args['lang'] = $submit_lang;
        }
        if ($item) {
            $success_args['tcarm_token'] = rawurlencode($this->create_lookup_token($item));
        }
        $success_url = add_query_arg($success_args, remove_query_arg(array('tcarm_submitted', 'tcarm_code', 'tcarm_token')));
        wp_safe_redirect($success_url);
        exit;
    }

    private function handle_frontend_edit_submit() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public edit submit reads sanitized code first to identify the application-specific nonce action verified immediately below.
        $code = isset($_POST['application_code']) ? sanitize_text_field(wp_unslash($_POST['application_code'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public edit submit reads sanitized token first to resolve the application before verifying the application-specific nonce.
        $token = isset($_POST['tcarm_token']) ? sanitize_text_field(wp_unslash($_POST['tcarm_token'])) : '';
        $item = $this->get_application_by_code($code);
        if (!$item || !$this->verify_access_token($item, $token)) {
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        if ($item->status !== 'rejected') {
            wp_die(esc_html__('This application cannot be edited.', 'shinseiflow-application-review'));
        }
        if (!isset($_POST['tcarm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tcarm_nonce'])), 'tcarm_frontend_edit_' . $item->application_code)) {
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        $settings = self::get_settings();
        if ($settings['honeypot_enabled'] === '1' && $this->posted_honeypot_has_value()) {
            $this->log_blocked_submission('Resubmission', 'honeypot', 'Hidden field was filled in', $this->extract_contact_from_post());
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        if ($settings['honeypot_enabled'] === '1' && $this->is_too_fast_submission()) {
            $this->log_blocked_submission('Resubmission', 'too_fast', 'Submission time was too short', $this->extract_contact_from_post());
            wp_die(esc_html__('The submission was too fast. Please check your input and try again.', 'shinseiflow-application-review'));
        }
        if ($this->turnstile_enabled_for('edit') && !$this->verify_turnstile_response()) {
            wp_die(esc_html__('Robot prevention verification failed.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Edit payload is sanitized by sanitize_application_data() immediately after application-specific nonce verification.
        $data = $this->sanitize_application_data($_POST);
        $file_errors = $this->process_file_uploads($data, (int) $item->id);
        $errors = array_merge($this->validate_application_data($data), $file_errors);
        if ($errors) {
            set_transient('tcarm_edit_errors_' . $item->application_code, $errors, 60);
            $edit_error_args = array('tcarm_edit_error' => '1', 'tcarm_token' => rawurlencode($token));
            $edit_lang = isset($_POST['tcarm_lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_POST['tcarm_lang']))) : '';
            if ($this->should_add_lang_query($edit_lang)) {
                $edit_error_args['lang'] = $edit_lang;
            }
            wp_safe_redirect(add_query_arg($edit_error_args, remove_query_arg(array('tcarm_edit_updated', 'tcarm_submitted', 'tcarm_code', 'code', 'token'))));
            exit;
        }
        if (!$this->check_rate_limit_if_enabled('edit_ip', $this->get_request_ip(), 5, 30 * MINUTE_IN_SECONDS)) {
            $this->log_blocked_submission('Resubmission', 'rate_limit', 'Repeated submissions in a short time', $data);
            wp_die(esc_html__('Too many submissions were made. Please wait a while and try again.', 'shinseiflow-application-review'));
        }
        global $wpdb;
        $update = array_merge($data, array(
            'form_data_json' => wp_json_encode($data),
            'status' => 'pending',
            'reviewed_at' => null,
            'last_resubmitted_at' => current_time('mysql'),
            'last_status_changed_at' => current_time('mysql'),
            'resubmit_count' => (int) $item->resubmit_count + 1,
            'updated_at' => current_time('mysql'),
        ));
        $filtered_update = self::filter_db_data($update);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table resubmission update; WordPress core APIs do not apply.
        $wpdb->update(self::table_name(), $filtered_update, array('id' => (int) $item->id), $this->application_db_formats_for($filtered_update), array('%d'));
        self::flush_application_cache();
        $updated = $this->get_application((int) $item->id);
        if ($updated) {
            $this->append_application_history((int) $item->id, 'resubmitted', 'Applicant');
            $settings = self::get_settings();
            $this->send_template_email($settings['recipient_email'], 'resubmitted', $updated, true);
        }
        $edit_success_args = array('tcarm_edit_updated' => '1', 'tcarm_token' => rawurlencode($token));
        $edit_lang = isset($_POST['tcarm_lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_POST['tcarm_lang']))) : '';
        if ($this->should_add_lang_query($edit_lang)) {
            $edit_success_args['lang'] = $edit_lang;
        }
        wp_safe_redirect(add_query_arg($edit_success_args, remove_query_arg(array('tcarm_edit_error', 'code', 'token'))));
        exit;
    }

    private function posted_honeypot_has_value() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Honeypot value is read inside nonce-protected frontend submit and lookup flows.
        $honeypot_value = isset($_POST['tcarm_website']) ? sanitize_text_field(wp_unslash($_POST['tcarm_website'])) : '';
        return trim($honeypot_value) !== '';
    }

    private function sanitize_application_data($source) {
        $fields = self::get_fields();
        $data = array();
        foreach ($fields as $key => $field) {
            $raw = isset($source[$key]) ? wp_unslash($source[$key]) : '';
            $type = isset($field['type']) ? $field['type'] : 'text';
            if ($type === 'checkbox') {
                $data[$key] = !empty($source[$key]) ? '1' : '0';
            } elseif ($type === 'dropdown') {
                $data[$key] = sanitize_title($raw);
            } elseif ($type === 'file') {
                $data[$key] = isset($source[$key]) ? $this->sanitize_file_value(wp_unslash($source[$key])) : '';
            } elseif ($type === 'email') {
                $data[$key] = sanitize_email($raw);
            } elseif ($type === 'url') {
                $data[$key] = esc_url_raw($raw);
            } elseif ($type === 'tel') {
                $data[$key] = sanitize_text_field($raw);
            } elseif ($type === 'date') {
                $data[$key] = sanitize_text_field($raw);
            } elseif ($type === 'textarea') {
                $data[$key] = sanitize_textarea_field($raw);
            } else {
                $data[$key] = sanitize_text_field($raw);
            }
        }
        return $data;
    }

    private function validate_application_data($data) {
        $errors = array();
        $fields = self::get_fields();
        foreach ($fields as $key => $field) {
            $field = $this->apply_field_translation($field);
            if (isset($field['enabled']) && $field['enabled'] !== '1') {
                continue;
            }
            if ($field['required'] === '1') {
                $is_empty = empty($data[$key]);
                if (isset($field['type']) && $field['type'] === 'checkbox') {
                    $is_empty = ((string) $data[$key] !== '1');
                } elseif (isset($field['type']) && $field['type'] === 'file') {
                    $is_empty = empty($this->decode_file_attachments(isset($data[$key]) ? $data[$key] : ''));
                }
                if ($is_empty) {
                    /* translators: %s: field label. */
                    $errors[] = sprintf(__('%s is required.', 'shinseiflow-application-review'), $field['label']);
                }
            }
            if (!empty($data[$key]) && isset($field['type']) && $field['type'] === 'dropdown' && !in_array((string) $data[$key], $this->dropdown_choice_values($field), true)) {
                /* translators: %s: field label. */
                $errors[] = sprintf(__('The selected value for %s is invalid.', 'shinseiflow-application-review'), $field['label']);
            }
            if (!empty($data[$key]) && isset($field['type']) && $field['type'] === 'email' && !is_email($data[$key])) {
                /* translators: %s: field label. */
                $errors[] = sprintf(__('%s has an invalid format.', 'shinseiflow-application-review'), $field['label']);
            }
            if (!empty($data[$key]) && isset($field['type']) && $field['type'] === 'url' && !filter_var($data[$key], FILTER_VALIDATE_URL)) {
                /* translators: %s: field label. */
                $errors[] = sprintf(__('%s has an invalid format.', 'shinseiflow-application-review'), $field['label']);
            }
            if (!empty($data[$key]) && isset($field['type']) && $field['type'] === 'tel' && !preg_match('/^[0-9+()\-\s]+$/', $data[$key])) {
                /* translators: %s: field label. */
                $errors[] = sprintf(__('Please enter %s using numbers, hyphens, plus signs, parentheses, and spaces.', 'shinseiflow-application-review'), $field['label']);
            }
            if (!empty($data[$key]) && isset($field['type']) && $field['type'] === 'date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$key])) {
                /* translators: %s: field label. */
                $errors[] = sprintf(__('Please enter %s in YYYY-MM-DD format.', 'shinseiflow-application-review'), $field['label']);
            }
        }
        return $errors;
    }

    private function render_frontend_steps($current = 'input') {
        if (!$this->should_show_frontend_steps()) {
            return '';
        }
        $steps = array(
            'input' => array('label_key' => 'steps.step1.label', 'title_key' => 'steps.step1.title', 'label' => 'STEP 1', 'title' => 'Input', 'icon' => 'edit_document'),
            'confirm' => array('label_key' => 'steps.step2.label', 'title_key' => 'steps.step2.title', 'label' => 'STEP 2', 'title' => 'Review', 'icon' => 'grading'),
            'complete' => array('label_key' => 'steps.step3.label', 'title_key' => 'steps.step3.title', 'label' => 'STEP 3', 'title' => 'Submission Complete', 'icon' => 'forward_to_inbox'),
            'review' => array('label_key' => 'steps.step4.label', 'title_key' => 'steps.step4.title', 'label' => 'STEP 4', 'title' => 'Administrator Review', 'icon' => 'check_circle'),
        );
        $html = '<div class="tcarm-front-steps" aria-label="' . esc_attr($this->t('steps.aria_label', 'Application steps')) . '">';
        $index = 1;
        foreach ($steps as $key => $step) {
            $classes = array('tcarm-front-step', 'tcarm-front-step-' . sanitize_html_class($key));
            if ($key === $current) {
                $classes[] = 'is-active';
            }
            $html .= '<div class="' . esc_attr(implode(' ', $classes)) . '" data-step="' . esc_attr($key) . '">';
            $html .= '<span class="tcarm-front-step-icon" aria-hidden="true">' . $this->render_frontend_step_icon($step['icon']) . '</span>';
            $html .= '<span class="tcarm-front-step-body">';
            $html .= '<span class="tcarm-front-step-number">' . esc_html($this->t($step['label_key'], $step['label'])) . '</span>';
            $html .= '<span class="tcarm-front-step-label">' . esc_html($this->t($step['title_key'], $step['title'])) . '</span>';
            $html .= '</span>';
            $html .= '</div>';
            $index++;
        }
        $html .= '</div>';
        return wp_kses($html, $this->frontend_shortcode_allowed_tags());
    }

    private function render_frontend_step_icon($type) {
        $icons = array(
            'edit_document' => 'edit_document',
            'grading' => 'grading',
            'forward_to_inbox' => 'forward_to_inbox',
            'check_circle' => 'check_circle',
        );
        $icon = isset($icons[$type]) ? $icons[$type] : 'edit_document';
        return wp_kses(
            '<span class="tcarm-front-step-symbol material-symbols-outlined" aria-hidden="true">' . esc_html($icon) . '</span>',
            array(
                'span' => array(
                    'class' => true,
                    'aria-hidden' => true,
                ),
            )
        );
    }

    private function frontend_shortcode_allowed_tags() {
        $tags = $this->attachment_html_allowed_tags();

        $tags['div'] = array(
            'class' => true,
            'aria-hidden' => true,
            'aria-label' => true,
            'data-step' => true,
            'data-sitekey' => true,
            'data-theme' => true,
            'data-size' => true,
        );
        $tags['section'] = array(
            'class' => true,
        );
        $tags['h1'] = array(
            'class' => true,
        );
        $tags['h2'] = array(
            'class' => true,
        );
        $tags['h3'] = array(
            'class' => true,
        );
        $tags['p'] = array(
            'class' => true,
        );
        $tags['ul'] = array(
            'class' => true,
        );
        $tags['li'] = array(
            'class' => true,
        );
        $tags['span'] = array(
            'class' => true,
            'aria-hidden' => true,
        );
        $tags['strong'] = array(
            'class' => true,
        );
        $tags['br'] = array();
        $tags['form'] = array(
            'class' => true,
            'method' => true,
            'enctype' => true,
            'action' => true,
        );
        $tags['label'] = array(
            'class' => true,
            'for' => true,
        );
        $tags['input'] = array(
            'class' => true,
            'type' => true,
            'name' => true,
            'value' => true,
            'placeholder' => true,
            'tabindex' => true,
            'autocomplete' => true,
            'checked' => true,
            'required' => true,
            'multiple' => true,
            'accept' => true,
            'data-tcarm-validate' => true,
        );
        $tags['textarea'] = array(
            'class' => true,
            'name' => true,
            'rows' => true,
            'placeholder' => true,
            'required' => true,
            'data-tcarm-validate' => true,
        );
        $tags['select'] = array(
            'class' => true,
            'name' => true,
            'required' => true,
            'data-tcarm-validate' => true,
        );
        $tags['option'] = array(
            'value' => true,
            'selected' => true,
        );
        $tags['button'] = array(
            'class' => true,
            'type' => true,
        );
        $tags['table'] = array(
            'class' => true,
        );
        $tags['tbody'] = array();
        $tags['tr'] = array(
            'class' => true,
        );
        $tags['th'] = array(
            'class' => true,
        );
        $tags['td'] = array(
            'class' => true,
        );
        $tags['noscript'] = array();

        return $tags;
    }

    private function group_application_fields_by_section($item) {
        $fields = self::get_fields();
        $sections = self::get_sections();
        $grouped = array();

        foreach ($sections as $section_key => $section) {
            $normalized = self::normalize_section_key($section_key);
            $grouped[$normalized] = array(
                'label' => $this->translated_section_label($normalized),
                'description' => isset($section['description']) ? $section['description'] : '',
                'enabled' => isset($section['enabled']) ? $section['enabled'] : '1',
                'fields' => array(),
            );
        }

        foreach ($fields as $key => $field) {
            $current_value = $this->application_value($item, $key);
            if (isset($field['enabled']) && $field['enabled'] !== '1' && $current_value === '') {
                continue;
            }
            $section = !empty($field['section']) ? self::normalize_section_key($field['section']) : 'event';
            if (!isset($grouped[$section])) {
                $grouped[$section] = array(
                    'label' => $this->translated_section_label($section),
                    'description' => '',
                    'enabled' => '1',
                    'fields' => array(),
                );
            }
            if (isset($grouped[$section]['enabled']) && $grouped[$section]['enabled'] !== '1' && $current_value === '') {
                continue;
            }
            $grouped[$section]['fields'][$key] = $field;
        }

        return $grouped;
    }

    private function render_application_confirm_cards($item) {
        $grouped = $this->group_application_fields_by_section($item);
        ob_start();
        foreach ($grouped as $section => $section_data):
            if (empty($section_data['fields'])) {
                continue;
            }
            ?>
            <section class="tcarm-front-section tcarm-front-section--confirm tcarm-confirm-section tcarm-confirm-section-<?php echo esc_attr($section); ?>">
                <h2 class="tcarm-front-section-title tcarm-form-section-title"><?php echo esc_html($section_data['label']); ?></h2>
                <?php if (!empty($section_data['description'])): ?><p class="tcarm-front-section-description tcarm-form-section-description"><?php echo nl2br(esc_html($section_data['description'])); ?></p><?php endif; ?>
                <div class="tcarm-front-summary tcarm-confirm-grid">
                    <?php foreach ($section_data['fields'] as $key => $field): $value = $this->application_value($item, $key); ?>
                        <?php
                        $is_file_field = isset($field['type']) && $field['type'] === 'file';
                        $value_html = $is_file_field ? $this->render_file_value_html($value) : nl2br(esc_html($this->format_field_value($value, $field)));
                        ?>
                        <div class="tcarm-front-summary-row tcarm-confirm-row">
                            <div class="tcarm-front-summary-label tcarm-confirm-label"><?php echo esc_html($field['label']); ?></div>
                            <div class="tcarm-front-summary-value tcarm-confirm-value"><?php echo wp_kses($value_html, $this->application_field_value_allowed_tags()); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach;
        $enabled_consents = array();
        foreach (self::get_consent_items() as $consent_key => $consent) {
            $show_checkbox = isset($consent['show_checkbox']) ? $consent['show_checkbox'] === '1' : true;
            if ($consent['enabled'] === '1' && $show_checkbox) {
                $enabled_consents[$consent_key] = $consent;
            }
        }
        if ($enabled_consents): ?>
            <section class="tcarm-front-section tcarm-front-section--confirm tcarm-front-section--agreements-confirm tcarm-confirm-section tcarm-confirm-section-consents">
                <h2 class="tcarm-front-section-title tcarm-form-section-title"><?php echo esc_html($this->t('common.consent_items', 'Consent Items')); ?></h2>
                <div class="tcarm-front-summary tcarm-confirm-grid">
                    <?php foreach ($enabled_consents as $consent): ?>
                        <div class="tcarm-front-summary-row tcarm-confirm-row">
                            <div class="tcarm-front-summary-label tcarm-confirm-label"><?php echo esc_html($consent['label']); ?></div>
                            <div class="tcarm-front-summary-value tcarm-confirm-value"><?php echo esc_html($this->t('common.consent_agreed', 'Agreed')); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif;
        return ob_get_clean();
    }

    private function render_frontend_form($data = array(), $errors = array()) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public completion state is read-only and values are sanitized before display.
        if (isset($_GET['tcarm_submitted']) && sanitize_text_field(wp_unslash($_GET['tcarm_submitted'])) === '1') {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public completion page displays sanitized redirected values after successful submission.
            $code = isset($_GET['tcarm_code']) ? sanitize_text_field(wp_unslash($_GET['tcarm_code'])) : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public completion page preserves a signed lookup token for applicant navigation.
            $token = isset($_GET['tcarm_token']) ? sanitize_text_field(wp_unslash($_GET['tcarm_token'])) : '';
            $buttons = '';
            if ($token) {
                $buttons .= '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url(add_query_arg(array('tcarm_token' => rawurlencode($token)), $this->get_frontend_page_url('status'))) . '">' . esc_html($this->t('common.check_status', 'Check application status')) . '</a>';
            } else {
                $buttons .= '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->get_frontend_page_url('status')) . '">' . esc_html($this->t('common.check_status', 'Check application status')) . '</a>';
            }
            $buttons .= '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->get_frontend_page_url('top')) . '">' . esc_html($this->t('common.top', 'Back to top')) . '</a>';
            return '<div class="tcarm-front tcarm-frontend tcarm-front--result tcarm-frontend-result tcarm-complete-message"><div class="tcarm-form tcarm-front-form tcarm-complete-form">' . wp_kses($this->render_frontend_steps('complete'), $this->frontend_shortcode_allowed_tags()) . '<section class="tcarm-front-section tcarm-form-section tcarm-complete-section"><h2 class="tcarm-front-section-title tcarm-form-section-title">' . esc_html($this->t('complete.received_title', 'Application received')) . '</h2><p class="tcarm-application-number-box"><span class="tcarm-application-number-label">' . esc_html($this->t('common.application_number', 'Application Number')) . '：</span><strong class="tcarm-application-number">' . esc_html($code) . '</strong></p><p>' . esc_html($this->t('complete.received_description', 'A confirmation email has been sent. An administrator will review the content and contact you again.')) . '</p><div class="tcarm-actions tcarm-front-actions tcarm-result-actions tcarm-complete-actions">' . wp_kses($buttons, $this->frontend_shortcode_allowed_tags()) . '</div></section></div></div>';
        }
        $fields = self::get_fields();
        $settings = self::get_settings();
        if (empty($fields)) {
            return '<div class="tcarm-front tcarm-frontend tcarm-front--form tcarm-front-application-form tcarm-application-form-page tcarm-application-form"><div class="tcarm-form tcarm-front-form"><section class="tcarm-front-section tcarm-form-section"><p class="tcarm-front-notice tcarm-front-notice--info">' . esc_html__('No form fields have been configured.', 'shinseiflow-application-review') . '</p></section></div></div>';
        }
        ob_start();
        ?>
        <div class="tcarm-front tcarm-frontend tcarm-front--form tcarm-front-application-form tcarm-application-form-page tcarm-application-form"><form class="tcarm-form" method="post" enctype="multipart/form-data">
            <div class="tcarm-front-header tcarm-front-heading">
                <h1 class="tcarm-front-title"><?php echo esc_html($this->t('form.title', 'Application')); ?></h1>
                <p class="tcarm-front-description"><?php echo esc_html($this->t('form.description', 'Enter the required information, review it, and submit the application.')); ?></p>
            </div>
            <?php echo wp_kses($this->render_frontend_steps('input'), $this->frontend_shortcode_allowed_tags()); ?>
            <input type="hidden" name="tcarm_step" value="confirm">
            <input type="hidden" name="tcarm_lang" value="<?php echo esc_attr($this->current_language()); ?>">
            <?php wp_nonce_field('tcarm_frontend_form', 'tcarm_nonce'); ?>
            <input type="hidden" name="tcarm_form_loaded_at" value="<?php echo esc_attr(time()); ?>">
            <?php if ($settings['honeypot_enabled'] === '1'): ?><div class="tcarm-hp" aria-hidden="true"><label>Website <input type="text" name="tcarm_website" value="" tabindex="-1" autocomplete="off"></label></div><?php endif; ?>
            <?php if ($errors): ?>
                <div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error"><ul><?php foreach ($errors as $error): ?><li><?php echo esc_html($error); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php echo wp_kses($this->render_form_sections($fields, $data), $this->frontend_shortcode_allowed_tags()); ?>
            <?php echo wp_kses($this->render_consent_items(), $this->frontend_shortcode_allowed_tags()); ?>
            <?php echo wp_kses($this->render_turnstile_widget('form'), $this->frontend_shortcode_allowed_tags()); ?>
            <div class="tcarm-actions tcarm-front-actions tcarm-form-actions"><button type="submit" class="tcarm-button tcarm-front-button tcarm-front-button--primary tcarm-submit-button"><?php echo esc_html($this->t('common.review_input', 'Review your input')); ?></button> <a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="<?php echo esc_url($this->get_frontend_page_url('top')); ?>"><?php echo esc_html($this->t('common.back', 'Back')); ?></a></div>
        </form></div>
        <?php
        return ob_get_clean();
    }

    private function render_form_sections($fields, $data) {
        $sections = self::get_sections();
        $grouped = array();
        foreach ($fields as $key => $field) {
            if (isset($field['enabled']) && $field['enabled'] !== '1') {
                continue;
            }
            $section = !empty($field['section']) ? self::normalize_section_key($field['section']) : 'event';
            if (isset($sections[$section]) && $sections[$section]['enabled'] !== '1') {
                continue;
            }
            if (!isset($grouped[$section])) {
                $grouped[$section] = array();
            }
            $grouped[$section][$key] = $field;
        }
        ob_start();
        foreach ($grouped as $section => $section_fields): ?>
            <section class="tcarm-front-section tcarm-form-section tcarm-front-section--<?php echo esc_attr($section); ?> tcarm-form-section-<?php echo esc_attr($section); ?>">
                <h2 class="tcarm-front-section-title tcarm-form-section-title"><?php echo esc_html($this->translated_section_label($section)); ?></h2>
                <div class="tcarm-front-section-body tcarm-section-fields">
                    <?php foreach ($section_fields as $key => $field): ?>
                        <?php echo wp_kses($this->field_input($key, $fields, $data), $this->frontend_shortcode_allowed_tags()); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach;
        return ob_get_clean();
    }

    private function field_input($key, $fields, $data, $type = null) {
        $field = isset($fields[$key]) ? $fields[$key] : array('label' => $key, 'required' => '0', 'type' => 'text', 'enabled' => '1');
        $field = $this->apply_field_translation($field);
        if (isset($field['enabled']) && $field['enabled'] !== '1') {
            return '';
        }
        $value = isset($data[$key]) ? $data[$key] : '';
        $type = $type ? $type : (isset($field['type']) ? $field['type'] : 'text');
        $required = $field['required'] === '1';
        $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
        $validation_type = in_array($type, array('email', 'url', 'tel', 'date'), true) ? $type : ($type === 'checkbox' ? 'checkbox' : ($type === 'dropdown' ? 'select' : 'text'));
        ob_start();
        ?>
        <div class="<?php echo esc_attr('tcarm-front-field tcarm-field tcarm-form-field' . ($required ? ' tcarm-form-field-required' : '') . ' tcarm-front-field--' . $type . ' tcarm-field-' . $key); ?>">
            <label class="tcarm-front-label tcarm-form-label"><?php echo esc_html($field['label']); ?><?php if ($required): ?> <span class="tcarm-required tcarm-front-required tcarm-required-mark">*</span><?php endif; ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea class="tcarm-front-textarea tcarm-form-control" name="<?php echo esc_attr($key); ?>" rows="5" placeholder="<?php echo esc_attr($placeholder); ?>" data-tcarm-validate="text"<?php if ($required): ?> required<?php endif; ?>><?php echo esc_textarea($value); ?></textarea>
            <?php elseif ($type === 'checkbox'): ?>
                <span class="tcarm-checkbox-field tcarm-choice-group"><input class="tcarm-front-checkbox tcarm-choice-item" type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" data-tcarm-validate="checkbox" <?php checked((string) $value, '1'); ?><?php if ($required): ?> required<?php endif; ?>> <?php echo esc_html($field['label']); ?></span>
            <?php elseif ($type === 'dropdown'): ?>
                <select class="tcarm-front-select tcarm-form-control tcarm-choice-group" name="<?php echo esc_attr($key); ?>" data-tcarm-validate="select"<?php if ($required): ?> required<?php endif; ?>>
                    <option value=""><?php echo esc_html($this->t('common.select_placeholder', 'Select')); ?></option>
                    <?php foreach ($this->dropdown_choices($field) as $choice): ?>
                        <option value="<?php echo esc_attr($choice['value']); ?>" <?php selected((string) $value, (string) $choice['value']); ?>><?php echo esc_html($choice['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($type === 'file'): ?>
                <?php $upload_settings = $this->upload_settings(); $accept = '.' . implode(',.', $upload_settings['extensions']); ?>
                <?php if (!empty($value)): ?><input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>"><?php echo wp_kses($this->render_file_value_html($value), $this->attachment_html_allowed_tags()); ?><?php endif; ?>
                <input class="tcarm-front-input tcarm-front-input--file tcarm-form-control" type="file" name="<?php echo esc_attr($key); ?>[]" accept="<?php echo esc_attr($accept); ?>" multiple data-tcarm-validate="file"<?php if ($required && empty($value)): ?> required<?php endif; ?>>
                <p class="tcarm-file-help tcarm-field-help"><?php echo esc_html($this->t('form.upload_help_prefix', 'Allowed uploads')); ?>：<?php echo esc_html(implode(', ', $upload_settings['extensions'])); ?> ／ <?php echo esc_html($this->t('form.upload_help_max', 'Maximum')); ?> <?php echo esc_html(size_format($upload_settings['max_size'])); ?> ／ <?php echo esc_html($upload_settings['max_uploads']); ?> <?php echo esc_html($this->t('form.upload_help_until', 'files')); ?></p>
            <?php elseif ($type === 'date'): ?>
                <input class="tcarm-front-input tcarm-form-control" type="date" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" data-tcarm-validate="date"<?php if ($required): ?> required<?php endif; ?>>
            <?php else: ?>
                <input class="tcarm-front-input tcarm-form-control" type="<?php echo esc_attr(in_array($type, array('email', 'url', 'tel'), true) ? ($type === 'tel' ? 'tel' : $type) : 'text'); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" data-tcarm-validate="<?php echo esc_attr($validation_type); ?>"<?php if ($required): ?> required<?php endif; ?>>
            <?php endif; ?>
            <?php if (!empty($field['description'])): ?><p class="tcarm-field-help"><?php echo nl2br(esc_html($field['description'])); ?></p><?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_confirm($data) {
        $fields = self::get_fields();
        $settings = self::get_settings();
        ob_start();
        ?>
        <div class="tcarm-front tcarm-frontend tcarm-front--confirm tcarm-front-application-confirm tcarm-application-confirm-page"><form class="tcarm-form tcarm-confirm-form" method="post">
            <div class="tcarm-front-header tcarm-front-heading">
                <h1 class="tcarm-front-title"><?php echo esc_html($this->t('common.application_view', 'View Application Content')); ?></h1>
                <p class="tcarm-front-description"><?php echo esc_html($this->t('confirm.description', 'Please review your information and submit it if everything is correct.')); ?></p>
            </div>
            <?php echo wp_kses($this->render_frontend_steps('confirm'), $this->frontend_shortcode_allowed_tags()); ?>
            <?php echo wp_kses($this->render_application_confirm_cards((object) $data), $this->frontend_shortcode_allowed_tags()); ?>
            <?php foreach ($fields as $key => $field): ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(isset($data[$key]) ? $data[$key] : ''); ?>">
            <?php endforeach; ?>
            <?php foreach (self::get_consent_items() as $consent_key => $consent): $show_checkbox = isset($consent['show_checkbox']) ? $consent['show_checkbox'] === '1' : true; if ($consent['enabled'] !== '1' || !$show_checkbox) { continue; } ?>
                <input type="hidden" name="tcarm_consents[<?php echo esc_attr($consent_key); ?>]" value="1">
            <?php endforeach; ?>
            <?php wp_nonce_field('tcarm_frontend_form', 'tcarm_nonce'); ?>
            <input type="hidden" name="tcarm_form_loaded_at" value="<?php echo esc_attr(time() - 10); ?>">
            <?php if ($this->turnstile_enabled_for('form')): ?><input type="hidden" name="tcarm_turnstile_verified" value="<?php echo esc_attr(wp_create_nonce('tcarm_turnstile_verified_' . $this->application_data_hash($data))); ?>"><?php endif; ?>
            <input type="hidden" name="tcarm_lang" value="<?php echo esc_attr($this->current_language()); ?>">
            <input type="hidden" name="tcarm_final_submit" value="1">
            <div class="tcarm-actions tcarm-front-actions tcarm-form-actions"><button type="button" class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" onclick="history.back();"><?php echo esc_html($this->t('common.edit_content', 'Edit')); ?></button> <button type="submit" class="tcarm-button tcarm-front-button tcarm-front-button--primary"><?php echo esc_html($this->t('common.submit', 'Submit')); ?></button></div>
        </form></div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_application_status($atts = array()) {
        $this->set_current_language_from_shortcode($atts);
        return $this->render_lookup_interface('status');
    }

    public function shortcode_application_view($atts = array()) {
        $this->set_current_language_from_shortcode($atts);
        return $this->render_lookup_interface('view');
    }

    public function shortcode_application_edit($atts = array()) {
        $this->set_current_language_from_shortcode($atts);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public edit completion state is read-only and resolved through the applicant lookup token flow.
        if (isset($_GET['tcarm_edit_updated']) && sanitize_text_field(wp_unslash($_GET['tcarm_edit_updated'])) === '1') {
            $item = $this->resolve_frontend_application_from_request();
            $code = $item ? $item->application_code : '';
            $token = $item ? $this->current_lookup_token_for_item($item) : '';
            if (!$item) {
                $buttons = '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->get_frontend_page_url('status')) . '">' . esc_html($this->t('common.recheck_status', 'Check application status again')) . '</a>';
                $buttons .= '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->get_frontend_page_url('top')) . '">' . esc_html($this->t('common.top', 'Back to top')) . '</a>';
                return '<div class="tcarm-front tcarm-frontend tcarm-front--edit tcarm-front--result">' . wp_kses($this->token_expired_notice(), $this->frontend_shortcode_allowed_tags()) . '<div class="tcarm-actions tcarm-front-actions tcarm-result-actions tcarm-complete-actions">' . wp_kses($buttons, $this->frontend_shortcode_allowed_tags()) . '</div></div>';
            }
            $buttons = '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->build_frontend_url('status', $code, $token)) . '">' . esc_html($this->t('common.back_to_status', 'Back to application status')) . '</a>';
            if ($this->get_frontend_page_url('view', false)) {
                $buttons = '<a class="tcarm-button tcarm-front-button tcarm-front-button--primary" href="' . esc_url($this->build_frontend_url('view', $code, $token)) . '">' . esc_html($this->t('common.view_submitted_content', 'View submitted content')) . '</a>' . $buttons;
            }
            $buttons .= '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->get_frontend_page_url('top')) . '">' . esc_html($this->t('common.top', 'Back to top')) . '</a>';
            return '<div class="tcarm-front tcarm-frontend tcarm-front--edit tcarm-front--result tcarm-complete-message tcarm-edit-complete-message"><div class="tcarm-form tcarm-front-form tcarm-complete-form tcarm-edit-complete-form">' . wp_kses($this->render_frontend_steps('complete'), $this->frontend_shortcode_allowed_tags()) . '<section class="tcarm-front-section tcarm-form-section tcarm-complete-section tcarm-edit-complete-section"><h2 class="tcarm-front-section-title tcarm-form-section-title">' . esc_html($this->t('complete.resubmitted_title', 'Resubmission received')) . '</h2><p class="tcarm-application-number-box"><span class="tcarm-application-number-label">' . esc_html($this->t('common.application_number', 'Application Number')) . '：</span><strong class="tcarm-application-number">' . esc_html($code) . '</strong></p><p>' . esc_html($this->t('complete.resubmitted_description', 'The content has been updated and returned to pending review.')) . '</p><div class="tcarm-actions tcarm-front-actions tcarm-result-actions tcarm-complete-actions">' . wp_kses($buttons, $this->frontend_shortcode_allowed_tags()) . '</div></section></div></div>';
        }
        $item = $this->resolve_frontend_application_from_request();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Status lookup marker only renders a nonce-protected public lookup result block.
        $status_lookup_requested = isset($_POST['tcarm_status_lookup']) ? sanitize_text_field(wp_unslash($_POST['tcarm_status_lookup'])) : '';
        if (!$item && $status_lookup_requested !== '') {
            check_admin_referer('tcarm_status_lookup', 'tcarm_status_nonce');
            if (!$this->posted_honeypot_has_value()) {
                $code = isset($_POST['application_code']) ? sanitize_text_field(wp_unslash($_POST['application_code'])) : '';
                $email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
                $item = $this->get_application_by_code_email($code, $email);
                if ($item) {
                    return $this->render_frontend_redirect($this->build_frontend_url('edit', $item->application_code, $this->create_lookup_token($item)));
                }
            }
        }
        if (!$item) {
            return $this->render_lookup_interface('edit');
        }
        return $this->render_edit_form($item);
    }

    private function render_edit_form($item) {
        $token = $this->current_lookup_token_for_item($item);
        if ($item->status !== 'rejected') {
            return '<div class="tcarm-front tcarm-frontend tcarm-front--edit"><div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error">' . esc_html($this->t('edit.cannot_edit', 'This application cannot be edited at this time.')) . '</div>' . wp_kses($this->render_application_summary($item, true), $this->frontend_shortcode_allowed_tags()) . '</div>';
        }
        $errors = get_transient('tcarm_edit_errors_' . $item->application_code);
        delete_transient('tcarm_edit_errors_' . $item->application_code);
        $data = array_merge((array) $item, $this->get_application_extra_data($item));
        $fields = self::get_fields();
        $settings = self::get_settings();
        ob_start();
        ?>
        <div class="tcarm-front tcarm-frontend tcarm-front--edit tcarm-front-application-edit tcarm-application-edit-page tcarm-application-edit"><form class="tcarm-form tcarm-edit-form" method="post" enctype="multipart/form-data">
            <div class="tcarm-front-header tcarm-front-heading">
                <h1 class="tcarm-front-title"><?php echo esc_html($this->t('common.application_edit', 'Edit Application Content')); ?></h1>
                <p class="tcarm-front-description"><?php echo esc_html($this->t('edit.description', 'You can edit and resubmit a rejected application. After submission, the status returns to pending review.')); ?></p>
            </div>
            <?php echo wp_kses($this->render_frontend_steps('input'), $this->frontend_shortcode_allowed_tags()); ?>
            <?php if (!empty($item->reject_reason)): ?><div class="tcarm-reject-reason-card tcarm-status-box__reason tcarm-edit-reason-card"><strong><?php echo esc_html($this->t('common.rejection_reason', 'Rejection Reason')); ?>：</strong><div><?php echo nl2br(esc_html($item->reject_reason)); ?></div></div><?php endif; ?>
            <?php if (!empty($item->request_note)): ?><div class="tcarm-message tcarm-front-notice tcarm-front-notice--warning tcarm-warning tcarm-alert tcarm-alert-warning"><strong><?php echo esc_html($this->t('edit.confirmation_note', 'Confirmation Notes')); ?>：</strong><br><?php echo nl2br(esc_html($item->request_note)); ?></div><?php endif; ?>
            <?php if ($errors): ?>
                <div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error"><ul><?php foreach ((array) $errors as $error): ?><li><?php echo esc_html($error); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <?php echo wp_kses($this->render_form_sections($fields, $data), $this->frontend_shortcode_allowed_tags()); ?>
            <?php echo wp_kses($this->render_consent_items(), $this->frontend_shortcode_allowed_tags()); ?>
            <?php if ($settings['honeypot_enabled'] === '1'): ?><div class="tcarm-hp" aria-hidden="true"><label>Website <input type="text" name="tcarm_website" value="" tabindex="-1" autocomplete="off"></label></div><?php endif; ?>
            <?php echo wp_kses($this->render_turnstile_widget('edit'), $this->frontend_shortcode_allowed_tags()); ?>
            <?php wp_nonce_field('tcarm_frontend_edit_' . $item->application_code, 'tcarm_nonce'); ?>
            <input type="hidden" name="tcarm_form_loaded_at" value="<?php echo esc_attr(time()); ?>">
            <input type="hidden" name="tcarm_edit_final_submit" value="1">
            <input type="hidden" name="tcarm_lang" value="<?php echo esc_attr($this->current_language()); ?>">
            <input type="hidden" name="application_code" value="<?php echo esc_attr($item->application_code); ?>">
            <input type="hidden" name="tcarm_token" value="<?php echo esc_attr($token); ?>">
            <div class="tcarm-actions tcarm-front-actions tcarm-form-actions"><button type="submit" class="tcarm-button tcarm-front-button tcarm-front-button--primary tcarm-submit-button"><?php echo esc_html($this->t('common.resubmit', 'Resubmit edited content')); ?></button><a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="<?php echo esc_url($this->build_frontend_url('status', $item->application_code, $token)); ?>"><?php echo esc_html($this->t('common.back_to_status', 'Back to application status')); ?></a></div>
        </form></div>
        <?php
        return ob_get_clean();
    }

    public function handle_frontend_lookup_redirect() {
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Lookup marker only detects the public lookup form; nonce is verified immediately below.
        $status_lookup_requested = isset($_POST['tcarm_status_lookup']) ? sanitize_text_field(wp_unslash($_POST['tcarm_status_lookup'])) : '';
        if (is_admin() || $request_method !== 'POST' || $status_lookup_requested === '') {
            return;
        }
        if (!isset($_POST['tcarm_status_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tcarm_status_nonce'])), 'tcarm_status_lookup')) {
            return;
        }
        $mode = isset($_POST['tcarm_lookup_mode']) ? sanitize_key(wp_unslash($_POST['tcarm_lookup_mode'])) : 'status';
        if (!in_array($mode, array('status', 'view', 'edit'), true)) {
            $mode = 'status';
        }
        $settings = self::get_settings();
        if ($settings['honeypot_enabled'] === '1' && $this->posted_honeypot_has_value()) {
            return;
        }
        if ($settings['honeypot_enabled'] === '1' && $this->is_too_fast_submission()) {
            return;
        }
        if ($this->turnstile_enabled_for('status') && !$this->verify_turnstile_response()) {
            return;
        }
        if (!$this->check_rate_limit_if_enabled('lookup_ip', $this->get_request_ip(), 20, 10 * MINUTE_IN_SECONDS)) {
            return;
        }
        $code = isset($_POST['application_code']) ? sanitize_text_field(wp_unslash($_POST['application_code'])) : '';
        $email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
        if ($code === '' || $email === '') {
            return;
        }
        $item = $this->get_application_by_code_email($code, $email);
        if (!$item) {
            return;
        }
        $lookup_lang = isset($_POST['tcarm_lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_POST['tcarm_lang']))) : '';
        if ($lookup_lang !== '') {
            $this->current_frontend_lang = $lookup_lang;
        }
        $url = add_query_arg(array('tcarm_token' => rawurlencode($this->create_lookup_token($item))), $this->get_frontend_page_url($mode));
        wp_safe_redirect($url);
        exit;
    }

    private function render_frontend_redirect($url) {
        $url = esc_url_raw($url);
        $escaped_url = esc_url($url);
        wp_enqueue_script('tcarm-frontend-redirect', self::plugin_url() . 'assets/js/frontend-redirect.js', array(), self::VERSION, true);
        wp_localize_script(
            'tcarm-frontend-redirect',
            'tcarmFrontendRedirect',
            array(
                'url' => $url,
            )
        );
        return '<div class="tcarm-front tcarm-frontend tcarm-front--redirect"><div class="tcarm-message tcarm-front-notice tcarm-front-notice--info tcarm-info tcarm-alert tcarm-alert-info"><p>' . esc_html($this->t('redirect.description', 'Redirecting. If you are not redirected automatically, press the button below.')) . '</p><div class="tcarm-actions tcarm-front-actions"><a class="tcarm-front-button tcarm-front-button--primary" href="' . $escaped_url . '">' . esc_html($this->t('common.move', 'Go')) . '</a></div></div><noscript><p><a href="' . $escaped_url . '">' . esc_html($this->t('common.move', 'Go')) . '</a></p></noscript></div>';
    }

    private function render_lookup_interface($mode = 'status') {
        $message = '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public applicant lookup links use signed tokens or code/token validation instead of WordPress nonces.
        $has_lookup_token = (isset($_GET['tcarm_token']) && sanitize_text_field(wp_unslash($_GET['tcarm_token'])) !== '') || ((isset($_GET['code']) && sanitize_text_field(wp_unslash($_GET['code'])) !== '') && (isset($_GET['token']) && sanitize_text_field(wp_unslash($_GET['token'])) !== ''));
        $item = $this->resolve_frontend_application_from_request();
        if ($has_lookup_token && !$item) {
            $message = $this->token_expired_notice();
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Lookup marker only detects the public lookup form; nonce is verified immediately below.
        $status_lookup_requested = isset($_POST['tcarm_status_lookup']) ? sanitize_text_field(wp_unslash($_POST['tcarm_status_lookup'])) : '';
        if ($status_lookup_requested !== '') {
            check_admin_referer('tcarm_status_lookup', 'tcarm_status_nonce');
            if (!$this->check_rate_limit_if_enabled('lookup_ip', $this->get_request_ip(), 20, 10 * MINUTE_IN_SECONDS)) {
                $this->log_blocked_submission('Application status lookup', 'rate_limit', 'Repeated searches in a short time', $this->extract_contact_from_post());
                $message = '<div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error">' . esc_html($this->t('status.retry_later', 'Please wait a while and try again.')) . '</div>';
            } elseif ($this->turnstile_enabled_for('status') && !$this->verify_turnstile_response()) {
                $this->log_blocked_submission('Application status lookup', 'turnstile_failed', 'Turnstile verification failed', $this->extract_contact_from_post());
                $message = '<div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error">' . esc_html($this->t('status.turnstile_failed', 'Robot prevention verification failed. Please try again.')) . '</div>';
            } elseif (self::get_settings()['honeypot_enabled'] === '1' && $this->posted_honeypot_has_value()) {
                $this->log_blocked_submission('Application status lookup', 'honeypot', 'Hidden field was filled in', $this->extract_contact_from_post());
                $message = '<div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error">' . esc_html($this->t('status.not_found', 'No matching application was found.')) . '</div>';
            } elseif (self::get_settings()['honeypot_enabled'] === '1' && $this->is_too_fast_submission()) {
                $this->log_blocked_submission('Application status lookup', 'too_fast', 'Submission time was too short', $this->extract_contact_from_post());
                $message = '<div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error">' . esc_html($this->t('status.retry_later', 'Please wait a while and try again.')) . '</div>';
            } else {
                $code = isset($_POST['application_code']) ? sanitize_text_field(wp_unslash($_POST['application_code'])) : '';
                $email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
                $item = $this->get_application_by_code_email($code, $email);
                if ($item) {
                    $lookup_token = $this->create_lookup_token($item);
                    $lookup_lang = isset($_POST['tcarm_lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_POST['tcarm_lang']))) : '';
                    if ($lookup_lang !== '') { $this->current_frontend_lang = $lookup_lang; }
                    return $this->render_frontend_redirect(add_query_arg(array('tcarm_token' => rawurlencode($lookup_token)), $this->get_frontend_page_url($mode)));
                }
                $this->log_blocked_submission('Application status lookup', 'not_found', 'No matching application', $this->extract_contact_from_post());
                $message = '<div class="tcarm-message tcarm-front-notice tcarm-front-notice--error tcarm-error tcarm-alert tcarm-alert-error">' . esc_html($this->t('status.not_found', 'No matching application was found.')) . '</div>';
            }
        }
        ob_start();
        ?>
        <div class="<?php echo esc_attr('tcarm-front tcarm-frontend tcarm-front--' . $mode . ' ' . ($mode === 'edit' ? 'tcarm-front-application-edit tcarm-application-edit-page' : 'tcarm-front-application-status tcarm-application-status') . ' tcarm-status-lookup'); ?>">
        <?php echo wp_kses_post($message); ?>
        <?php if ($item): ?>
            <?php echo wp_kses($this->render_application_summary($item, $mode === 'view'), $this->frontend_shortcode_allowed_tags()); ?>
            <?php if ($mode === 'status') { echo wp_kses($this->render_frontend_download_files($item), $this->frontend_shortcode_allowed_tags()); } ?>
            <?php echo wp_kses($this->render_frontend_action_buttons($item, $mode), $this->frontend_shortcode_allowed_tags()); ?>
        <?php elseif (in_array($mode, array('view', 'edit'), true)): ?>
            <div class="tcarm-message tcarm-front-notice tcarm-front-notice--info tcarm-info tcarm-alert tcarm-alert-info">
                <h2><?php echo esc_html($mode === 'view' ? $this->t('status.view_empty_title', 'Submitted Content Review') : $this->t('status.edit_empty_title', 'Edit and Resubmit')); ?></h2>
                <p><?php echo esc_html($mode === 'view' ? $this->t('status.view_empty_description', 'To view submitted content, access it from the application status page.') : $this->t('status.edit_empty_description', 'Please edit and resubmit from the application status page.')); ?></p>
                <div class="tcarm-actions tcarm-front-actions">
                    <a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="<?php echo esc_url($this->get_frontend_page_url('status')); ?>"><?php echo esc_html($this->t('common.check_status', 'Check application status')); ?></a>
                    <a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="<?php echo esc_url($this->get_frontend_page_url('top')); ?>"><?php echo esc_html($this->t('common.top', 'Back to top')); ?></a>
                </div>
            </div>
        <?php else: ?>
            <form class="tcarm-form tcarm-front-form tcarm-front-card tcarm-status-search-form" method="post">
                <div class="tcarm-front-header tcarm-front-heading">
                    <h1 class="tcarm-front-title"><?php echo esc_html($this->t('common.application_status_check', 'Check Application Status')); ?></h1>
                    <p class="tcarm-front-description"><?php echo esc_html($this->t('status.lookup_description', 'Enter your application number and email address to check the current review status.')); ?></p>
                </div>
                <?php wp_nonce_field('tcarm_status_lookup', 'tcarm_status_nonce'); ?>
                <input type="hidden" name="tcarm_status_lookup" value="1">
                <input type="hidden" name="tcarm_lookup_mode" value="<?php echo esc_attr($mode); ?>">
                <input type="hidden" name="tcarm_lang" value="<?php echo esc_attr($this->current_language()); ?>">
                <input type="hidden" name="tcarm_form_loaded_at" value="<?php echo esc_attr(time()); ?>">
                <?php if (self::get_settings()['honeypot_enabled'] === '1'): ?><div class="tcarm-hp" aria-hidden="true"><label>Website <input type="text" name="tcarm_website" value="" tabindex="-1" autocomplete="off"></label></div><?php endif; ?>
                <div class="tcarm-status-search-fields">
                    <div class="tcarm-front-field tcarm-form-field tcarm-front-field--text"><label class="tcarm-front-label tcarm-form-label"><?php echo esc_html($this->t('common.application_number', 'Application Number')); ?></label><input class="tcarm-front-input tcarm-form-control" type="text" name="application_code" required></div>
                    <div class="tcarm-front-field tcarm-form-field tcarm-front-field--email"><label class="tcarm-front-label tcarm-form-label"><?php echo esc_html($this->t('common.contact_email', 'Contact Email Address')); ?></label><input class="tcarm-front-input tcarm-form-control" type="email" name="contact_email" required></div>
                </div>
                <?php echo wp_kses($this->render_turnstile_widget('status'), $this->frontend_shortcode_allowed_tags()); ?>
                <div class="tcarm-actions tcarm-front-actions tcarm-form-actions"><button type="submit" class="tcarm-button tcarm-front-button tcarm-front-button--primary tcarm-submit-button"><?php echo esc_html($this->t('status.check_result', 'Check result')); ?></button></div>
            </form>
        <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_frontend_download_files($item) {
        if (!$item || $item->status !== 'approved') {
            return '';
        }
        $files = self::get_download_files(true);
        if (empty($files)) {
            return '';
        }
        ob_start();
        ?>
        <div class="tcarm-front-section tcarm-download-files">
            <h2 class="tcarm-front-section-title tcarm-download-files-title"><?php echo esc_html($this->t('download.title', 'Download Files')); ?></h2>
            <p class="tcarm-download-files-description"><?php echo esc_html($this->t('download.description', 'Approved applicants can download available files.')); ?></p>
            <div class="tcarm-download-file-list">
                <?php foreach ($files as $file_key => $file): ?>
                    <div class="tcarm-download-file-item">
                        <h3 class="tcarm-download-file-title"><?php echo esc_html($file['label']); ?></h3>
                        <?php if (!empty($file['description'])): ?>
                            <p class="tcarm-download-file-description"><?php echo nl2br(esc_html($file['description'])); ?></p>
                        <?php endif; ?>
                        <a class="tcarm-button tcarm-front-button tcarm-front-button--primary tcarm-download-button" href="<?php echo esc_url($this->build_download_url($item, $file_key)); ?>"><?php echo esc_html($this->t('common.download', 'Download')); ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function build_download_url($item, $file_key) {
        $expires = $this->create_download_expires_timestamp();
        return add_query_arg(array(
            'tcarm_download' => '1',
            'app' => absint($item->id),
            'file' => sanitize_key($file_key),
            'expires' => $expires,
            'token' => $this->create_download_token($item, $file_key, $expires),
        ), home_url('/'));
    }

    private function get_download_link_expire_minutes() {
        $settings = self::get_settings();
        $minutes = isset($settings['download_link_expire_minutes']) ? absint($settings['download_link_expire_minutes']) : 30;
        $allowed = array(0, 10, 30, 60, 1440);
        return in_array($minutes, $allowed, true) ? $minutes : 30;
    }

    private function create_download_expires_timestamp() {
        $minutes = $this->get_download_link_expire_minutes();
        if ($minutes === 0) {
            return 0;
        }
        return time() + ($minutes * MINUTE_IN_SECONDS);
    }

    private function create_download_token($item, $file_key, $expires) {
        $file_key = sanitize_key($file_key);
        $expires = absint($expires);
        return hash_hmac('sha256', (int) $item->id . '|' . $item->application_code . '|' . strtolower($item->contact_email) . '|' . $file_key . '|' . $expires, wp_salt('auth'));
    }

    private function verify_download_token($item, $file_key, $expires, $token) {
        $expected = $this->create_download_token($item, $file_key, $expires);
        return is_string($token) && $token !== '' && hash_equals($expected, $token);
    }

    private function path_is_inside_uploads_dir($path) {
        $uploads = wp_upload_dir();
        if (empty($uploads['basedir'])) {
            return false;
        }
        $real_path = realpath($path);
        $real_base = realpath($uploads['basedir']);
        if (!$real_path || !$real_base) {
            return false;
        }
        $real_path = str_replace('\\', '/', $real_path);
        $real_base = rtrim(str_replace('\\', '/', $real_base), '/');
        return $real_path === $real_base || strpos($real_path, $real_base . '/') === 0;
    }

    public function handle_file_download() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Secure download links use signed tokens, expiry, approval status, and file path validation instead of WordPress nonces.
        $download_requested = isset($_GET['tcarm_download']) ? sanitize_key(wp_unslash($_GET['tcarm_download'])) : '';
        if ($download_requested === '') {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Secure download endpoint validates signed token, expiry, approval status, and file path.
        $application_id = isset($_GET['app']) ? absint(wp_unslash($_GET['app'])) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Secure download endpoint validates signed token, expiry, approval status, and file path.
        $file_key = isset($_GET['file']) ? sanitize_key(wp_unslash($_GET['file'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Secure download endpoint validates signed token, expiry, approval status, and file path.
        $expires = isset($_GET['expires']) ? absint(wp_unslash($_GET['expires'])) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Secure download endpoint validates signed token, expiry, approval status, and file path.
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        $item = $application_id ? $this->get_application($application_id) : null;
        if (!$item || $item->status !== 'approved' || $file_key === '' || !$this->verify_download_token($item, $file_key, $expires, $token)) {
            status_header(403);
            wp_die(esc_html__('This file cannot be downloaded.', 'shinseiflow-application-review'));
        }
        if ($expires > 0 && time() > $expires) {
            status_header(403);
            wp_die(esc_html__('The download link has expired. Please check again from the application status page.', 'shinseiflow-application-review'));
        }
        $file = $this->get_download_file($file_key);
        if (!$file || empty($file['enabled']) || $file['enabled'] !== '1') {
            status_header(404);
            wp_die(esc_html__('File not found.', 'shinseiflow-application-review'));
        }
        $attachment_id = !empty($file['attachment_id']) ? absint($file['attachment_id']) : 0;
        $path = $attachment_id ? get_attached_file($attachment_id) : '';
        if (!$path || !file_exists($path) || !is_readable($path)) {
            $url = !empty($file['file_url']) ? esc_url_raw($file['file_url']) : '';
            $upload_dir = wp_upload_dir();
            if ($url && !empty($upload_dir['baseurl']) && strpos($url, $upload_dir['baseurl']) === 0) {
                $relative = ltrim(substr($url, strlen($upload_dir['baseurl'])), '/');
                $candidate = trailingslashit($upload_dir['basedir']) . $relative;
                if (file_exists($candidate) && is_readable($candidate) && $this->path_is_inside_uploads_dir($candidate)) {
                    $path = $candidate;
                }
            }
        }
        if (!$path || !file_exists($path) || !is_readable($path) || !$this->path_is_inside_uploads_dir($path)) {
            status_header(404);
            wp_die(esc_html__('File not found.', 'shinseiflow-application-review'));
        }
        $filesize = filesize($path);
        if ($filesize === false) {
            status_header(404);
            wp_die(esc_html__('File not found.', 'shinseiflow-application-review'));
        }
        $this->append_application_history_entry($item->id, 'file_downloaded', 'File download: ' . sanitize_text_field($file['label']), 'Applicant');
        $filename = sanitize_file_name(wp_basename($path));
        if ($filename === '') {
            $filename = 'download';
        }
        $mime = wp_check_filetype($filename);
        nocache_headers();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . (!empty($mime['type']) ? $mime['type'] : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . (int) $filesize);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Secure download endpoint streams a validated local file after token, status, path, existence, and readability checks.
        readfile($path);
        exit;
    }

    private function render_frontend_action_buttons($item, $context = 'status') {
        $token = $this->current_lookup_token_for_item($item);
        $buttons = array();
        if ($context !== 'view' && $item->status !== 'rejected' && $this->get_frontend_page_url('view', false)) {
            $buttons[] = '<a class="tcarm-button tcarm-front-button tcarm-front-button--primary" href="' . esc_url($this->build_frontend_url('view', $item->application_code, $token)) . '">' . esc_html($this->t('common.view_submitted_content', 'View submitted content')) . '</a>';
        }
        if ($item->status === 'rejected' && $context !== 'edit' && $this->get_frontend_page_url('edit', false)) {
            $buttons[] = '<a class="tcarm-button tcarm-front-button tcarm-front-button--primary tcarm-front-button--resubmit" href="' . esc_url($this->build_frontend_url('edit', $item->application_code, $token)) . '">' . esc_html($this->t('common.edit_and_resubmit', 'Edit and resubmit')) . '</a>';
        }
        if ($context !== 'status') {
            $buttons[] = '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->build_frontend_url('status', $item->application_code, $token)) . '">' . esc_html($this->t('common.back_to_status', 'Back to application status')) . '</a>';
        }
        if ($context === 'status') {
            $buttons[] = '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->get_frontend_page_url('status')) . '">' . esc_html($this->t('common.check_other_status', 'Check another application status')) . '</a>';
        }
        $buttons[] = '<a class="tcarm-button-secondary tcarm-front-button tcarm-front-button--secondary" href="' . esc_url($this->get_frontend_page_url('top')) . '">' . esc_html($this->t('common.top', 'Back to top')) . '</a>';
        return '<div class="tcarm-actions tcarm-front-actions tcarm-status-actions">' . wp_kses(implode('', $buttons), $this->frontend_shortcode_allowed_tags()) . '</div>';
    }

    private function frontend_status_badge_class($status) {
        $status = sanitize_key((string) $status);
        if ($status === 'approved' || $status === 'published') {
            return 'tcarm-status-badge-approved tcarm-front-status-approved';
        }
        if ($status === 'rejected') {
            return 'tcarm-status-badge-rejected tcarm-front-status-rejected';
        }
        if ($status === 'needs_more') {
            return 'tcarm-status-badge-need-confirmation tcarm-front-status-needs-confirmation';
        }
        return 'tcarm-status-badge-pending tcarm-front-status-pending';
    }

    private function render_application_summary($item, $show_full = true) {
        $token = $this->create_access_token($item);
        ob_start();
        ?>
        <div class="tcarm-front-section tcarm-front-section--application-summary tcarm-application-card tcarm-status-result">
            <h2 class="tcarm-front-section-title tcarm-form-section-title tcarm-status-result-title"><?php echo esc_html($this->t('common.application_status', 'Application Status')); ?></h2>
            <div class="<?php echo esc_attr('tcarm-front-status-box tcarm-status-box' . (($item->status === 'rejected' && !empty($item->reject_reason)) ? ' tcarm-status-box--rejected-with-reason' : '')); ?>">
                <div class="tcarm-status-box__row"><span><?php echo esc_html($this->t('common.current_status', 'Current Status')); ?></span><strong class="tcarm-status-badge tcarm-front-status-badge <?php echo esc_attr($this->frontend_status_badge_class($item->status)); ?>"><?php echo esc_html($this->frontend_status_label($item->status)); ?></strong></div>
                <?php if (!empty($item->reject_reason) && $item->status === 'rejected'): ?>
                    <div class="tcarm-reject-reason-card tcarm-status-box__reason"><strong><?php echo esc_html($this->t('common.rejection_reason', 'Rejection Reason')); ?>：</strong><div><?php echo nl2br(esc_html($item->reject_reason)); ?></div></div>
                <?php endif; ?>
            </div>
            <p class="tcarm-application-number-box"><span class="tcarm-application-number-label"><?php echo esc_html($this->t('common.application_number', 'Application Number')); ?>：</span><strong class="tcarm-application-number"><?php echo esc_html($item->application_code); ?></strong></p>
            <?php if (!empty($item->request_note) && $item->status === 'needs_more'): ?>
                <div class="tcarm-message tcarm-front-notice tcarm-front-notice--warning tcarm-warning tcarm-alert tcarm-alert-warning"><strong><?php echo esc_html($this->t('edit.confirmation_note', 'Confirmation Notes')); ?>：</strong><br><?php echo nl2br(esc_html($item->request_note)); ?></div>
            <?php endif; ?>
            <?php if ($show_full): ?>
                <h3 class="tcarm-front-subtitle"><?php echo esc_html($this->t('common.submitted_content', 'Submitted Content')); ?></h3>
                <?php echo wp_kses($this->render_application_meta_table($item), $this->frontend_shortcode_allowed_tags()); ?>
                <?php echo wp_kses($this->render_application_confirm_cards($item), $this->frontend_shortcode_allowed_tags()); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_application_meta_table($item) {
        if (!isset($item->application_code)) {
            return '';
        }
        ob_start();
        ?>
        <table class="tcarm-front-table tcarm-confirm-table tcarm-application-table tcarm-status-detail-list tcarm-application-meta-table"><tbody>
            <tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.application_number', 'Application Number')); ?></th><td><?php echo esc_html($item->application_code); ?></td></tr>
            <tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.sent_at', 'Submitted At')); ?></th><td><?php echo esc_html(isset($item->created_at) ? $item->created_at : ''); ?></td></tr>
            <?php if (!empty($item->updated_at)): ?><tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.updated_at', 'Updated At')); ?></th><td><?php echo esc_html($item->updated_at); ?></td></tr><?php endif; ?>
            <?php if (!empty($item->resubmit_count)): ?><tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.resubmit_count', 'Edit Resubmission')); ?></th><td><?php echo esc_html($item->resubmit_count . $this->t('common.times', ' time(s)')); ?><?php if (!empty($item->last_resubmitted_at)): ?>（<?php echo esc_html($item->last_resubmitted_at); ?>）<?php endif; ?></td></tr><?php endif; ?>
        </tbody></table>
        <?php
        return ob_get_clean();
    }

    private function render_application_table($item, $include_meta = true) {
        $fields = self::get_fields();
        ob_start();
        ?>
        <table class="tcarm-front-table tcarm-confirm-table tcarm-application-table tcarm-status-detail-list"><tbody>
            <?php if ($include_meta && isset($item->application_code)): ?>
                <tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.application_number', 'Application Number')); ?></th><td><?php echo esc_html($item->application_code); ?></td></tr>
                <tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.sent_at', 'Submitted At')); ?></th><td><?php echo esc_html(isset($item->created_at) ? $item->created_at : ''); ?></td></tr>
                <?php if (!empty($item->updated_at)): ?><tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.updated_at', 'Updated At')); ?></th><td><?php echo esc_html($item->updated_at); ?></td></tr><?php endif; ?>
                <?php if (!empty($item->resubmit_count)): ?><tr class="tcarm-status-detail-item"><th><?php echo esc_html($this->t('common.resubmit_count', 'Edit Resubmission')); ?></th><td><?php echo esc_html($item->resubmit_count . $this->t('common.times', ' time(s)')); ?><?php if (!empty($item->last_resubmitted_at)): ?>（<?php echo esc_html($item->last_resubmitted_at); ?>）<?php endif; ?></td></tr><?php endif; ?>
            <?php endif; ?>
            <?php foreach ($fields as $key => $field): ?>
                <?php $current_value = $this->application_value($item, $key); if (isset($field['enabled']) && $field['enabled'] !== '1' && $current_value === '') { continue; } ?>
                <?php $field = $this->apply_field_translation($field); $value = $this->application_value($item, $key); ?>
                <?php
                $is_file_field = isset($field['type']) && $field['type'] === 'file';
                $value_html = $is_file_field ? $this->render_file_value_html($value) : nl2br(esc_html($this->format_field_value($value, $field)));
                ?>
                <tr class="tcarm-status-detail-item"><th><?php echo esc_html($field['label']); ?></th><td><?php echo wp_kses($value_html, $this->application_field_value_allowed_tags()); ?></td></tr>
            <?php endforeach; ?>
        </tbody></table>
        <?php
        return ob_get_clean();
    }

    private function get_current_frontend_url() {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : wp_parse_url(home_url(), PHP_URL_HOST);
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        return remove_query_arg(array('code', 'token', 'tcarm_submitted', 'tcarm_code', 'tcarm_token', 'tcarm_edit_updated', 'tcarm_edit_error'), $scheme . $host . $uri);
    }

    private function get_frontend_page_url($type, $fallback = true, $lang = '') {
        $settings = self::get_settings();
        $lang = $this->normalize_language_code($lang ?: $this->current_language());
        $page_map = isset($settings['frontend_pages_by_lang']) && is_array($settings['frontend_pages_by_lang']) ? $settings['frontend_pages_by_lang'] : array();
        $page_map = $this->normalize_frontend_pages_by_lang($page_map, $settings);
        foreach (array($lang, 'ja') as $candidate_lang) {
            if (!$candidate_lang || empty($page_map[$candidate_lang][$type])) {
                continue;
            }
            $url = get_permalink(absint($page_map[$candidate_lang][$type]));
            if ($url) {
                return $this->add_lang_to_url($url, $lang);
            }
        }
        $page_key = $type . '_page_id';
        if (!empty($settings[$page_key])) {
            $url = get_permalink(absint($settings[$page_key]));
            if ($url) {
                return $this->add_lang_to_url($url, $lang);
            }
        }
        if ($type === 'top') {
            if (!empty($settings['top_page_url'])) {
                return $this->add_lang_to_url(esc_url_raw($settings['top_page_url']), $lang);
            }
            return $fallback ? $this->add_lang_to_url(home_url('/'), $lang) : '';
        }
        $url_key = $type . '_page_url';
        if (!empty($settings[$url_key])) {
            return $this->add_lang_to_url(esc_url_raw($settings[$url_key]), $lang);
        }
        if (!$fallback) {
            return '';
        }
        if ($type === 'status') {
            return $this->add_lang_to_url(home_url('/'), $lang);
        }
        return $this->add_lang_to_url(home_url('/'), $lang);
    }

    private function build_frontend_url($type, $code, $token) {
        $token = $token ? $token : '';
        return add_query_arg(array(
            'tcarm_token' => rawurlencode($token),
        ), $this->get_frontend_page_url($type));
    }

    private function create_access_token($item) {
        return hash_hmac('sha256', $item->application_code . '|' . strtolower($item->contact_email), wp_salt('auth'));
    }

    private function create_lookup_token($item) {
        $token = 'mla_' . wp_generate_password(32, false, false);
        $payload = array(
            'application_id' => (int) $item->id,
            'email_hash' => hash_hmac('sha256', strtolower($item->contact_email), wp_salt('auth')),
            'created_at' => time(),
        );
        set_transient('tcarm_lookup_token_' . $token, $payload, HOUR_IN_SECONDS);
        return $token;
    }

    private function get_lookup_token_payload($token) {
        $token = sanitize_text_field((string) $token);
        if ($token === '' || strpos($token, 'mla_') !== 0) {
            return false;
        }
        $payload = get_transient('tcarm_lookup_token_' . $token);
        return is_array($payload) ? $payload : false;
    }

    private function verify_access_token($item, $token) {
        $token = (string) $token;
        if ($token === '') {
            return false;
        }
        $payload = $this->get_lookup_token_payload($token);
        if ($payload && !empty($payload['application_id']) && (int) $payload['application_id'] === (int) $item->id) {
            return true;
        }
        // Backward compatibility for older links generated before v0.1.34.
        return hash_equals($this->create_access_token($item), $token);
    }

    private function resolve_frontend_application_from_request() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public applicant links are authenticated by signed lookup token or legacy code/token validation.
        $lookup_token = isset($_GET['tcarm_token']) ? sanitize_text_field(wp_unslash($_GET['tcarm_token'])) : '';
        if ($lookup_token) {
            $payload = $this->get_lookup_token_payload($lookup_token);
            if (!$payload || empty($payload['application_id'])) {
                return null;
            }
            $item = $this->get_application((int) $payload['application_id']);
            if (!$item) {
                return null;
            }
            $expected = hash_hmac('sha256', strtolower($item->contact_email), wp_salt('auth'));
            if (empty($payload['email_hash']) || !hash_equals($expected, (string) $payload['email_hash'])) {
                return null;
            }
            return $item;
        }

        // Legacy support: /page/?code=...&token=...
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Legacy applicant links are authenticated by application code and access token.
        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Legacy applicant links are authenticated by application code and access token.
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        if (!$code || !$token) {
            return null;
        }
        $item = $this->get_application_by_code($code);
        if (!$item || !$this->verify_access_token($item, $token)) {
            return null;
        }
        return $item;
    }

    private function current_lookup_token_for_item($item) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public applicant navigation preserves a signed lookup token and does not require a WordPress nonce.
        $token = isset($_GET['tcarm_token']) ? sanitize_text_field(wp_unslash($_GET['tcarm_token'])) : '';
        if ($token && $this->verify_access_token($item, $token)) {
            return $token;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Posted lookup token is used only to preserve applicant navigation after nonce-protected frontend form posts.
        $posted = isset($_POST['tcarm_token']) ? sanitize_text_field(wp_unslash($_POST['tcarm_token'])) : '';
        if ($posted && $this->verify_access_token($item, $posted)) {
            return $posted;
        }
        return $this->create_lookup_token($item);
    }

    private function token_expired_notice() {
        return '<div class="tcarm-message tcarm-front-notice tcarm-front-notice--warning tcarm-warning tcarm-alert tcarm-alert-warning"><p>' . esc_html($this->t('status.token_expired', 'The confirmation link has expired. Please enter your application number and email address to check again.')) . '</p></div>';
    }

    private function get_application_by_code($code) {
        global $wpdb;
        $cache_key = self::application_cache_key(array('application_code', $code));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table code lookup with object cache.
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE application_code = %s", self::table_name(), $code));
        wp_cache_set($cache_key, $item, self::application_cache_group(), self::application_cache_ttl());
        return $item;
    }

    private function generate_application_code() {
        $settings = self::get_settings();
        $rule = isset($settings['application_number_rule']) && is_array($settings['application_number_rule']) ? $settings['application_number_rule'] : self::default_application_number_rule();
        $base_sequence = $this->next_application_sequence_base();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $this->build_application_code_from_rule($rule, $base_sequence + $attempt);
            if ($code !== '' && !$this->application_code_exists($code)) {
                return $code;
            }
        }

        $timestamp = current_time('timestamp');
        $fallback_prefix = 'APP-' . gmdate('Ymd', $timestamp) . '-' . gmdate('His', $timestamp) . '-';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $fallback_prefix . $this->random_digits(4);
            if (!$this->application_code_exists($code)) {
                return $code;
            }
        }

        return substr($fallback_prefix . $this->random_digits(8), 0, 32);
    }

    private function build_application_code_from_rule($rule, $sequence) {
        $parts = array();
        foreach ($rule as $row) {
            if (!is_array($row) || empty($row['type'])) {
                continue;
            }

            if ($row['type'] === 'fixed') {
                $value = isset($row['value']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $row['value']) : '';
                if ($value !== '') {
                    $parts[] = substr($value, 0, 16);
                }
            } elseif ($row['type'] === 'symbol') {
                $parts[] = isset($row['value']) && $row['value'] === '_' ? '_' : '-';
            } elseif ($row['type'] === 'date') {
                $format = isset($row['format']) && in_array($row['format'], array('Ymd', 'Ym', 'Y'), true) ? $row['format'] : 'Ymd';
                $parts[] = gmdate($format, current_time('timestamp'));
            } elseif ($row['type'] === 'random_letters') {
                $length = isset($row['length']) ? max(1, min(8, absint($row['length']))) : 2;
                $parts[] = $this->random_letters($length);
            } elseif ($row['type'] === 'random_numbers') {
                $length = isset($row['length']) ? max(1, min(8, absint($row['length']))) : 2;
                $parts[] = $this->random_digits($length);
            } elseif ($row['type'] === 'sequence') {
                $length = isset($row['length']) ? max(1, min(12, absint($row['length']))) : 6;
                $parts[] = str_pad((string) max(1, (int) $sequence), $length, '0', STR_PAD_LEFT);
            }
        }

        $code = implode('', $parts);
        return preg_match('/^[A-Za-z0-9_-]{1,32}$/', $code) ? $code : '';
    }

    private function next_application_sequence_base() {
        global $wpdb;
        $cache_key = self::application_cache_key(array('next_sequence_base'));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return (int) $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table sequence lookup with object cache.
        $max_id = (int) $wpdb->get_var($wpdb->prepare("SELECT MAX(id) FROM %i", self::table_name()));
        wp_cache_set($cache_key, $max_id + 1, self::application_cache_group(), self::application_cache_ttl());
        return $max_id + 1;
    }

    private function application_code_exists($code) {
        global $wpdb;
        $cache_key = self::application_cache_key(array('application_code_exists', $code));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return (bool) $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table code existence check with object cache.
        $exists = (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE application_code = %s LIMIT %d", self::table_name(), $code, 1));
        wp_cache_set($cache_key, $exists, self::application_cache_group(), self::application_cache_ttl());
        return $exists;
    }

    private function random_letters($length) {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $letters[wp_rand(0, strlen($letters) - 1)];
        }
        return $out;
    }

    private function random_digits($length) {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= (string) wp_rand(0, 9);
        }
        return $out;
    }
}
