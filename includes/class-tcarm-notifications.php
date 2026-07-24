<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Notifications_Trait {
    private function resend_email_type_definitions() {
        return array(
            'received' => array(
                'label' => __('Applicant Auto Reply', 'shinseiflow-application-review'),
                'template' => 'received',
                'recipient_type' => 'applicant',
                'recipient_label' => __('To Applicant', 'shinseiflow-application-review'),
            ),
            'admin' => array(
                'label' => __('Admin Reception Notice', 'shinseiflow-application-review'),
                'template' => 'admin',
                'recipient_type' => 'admin',
                'recipient_label' => __('To Administrator', 'shinseiflow-application-review'),
            ),
            'approved' => array(
                'label' => __('Approval Notice', 'shinseiflow-application-review'),
                'template' => 'approved',
                'recipient_type' => 'applicant',
                'recipient_label' => __('To Applicant', 'shinseiflow-application-review'),
            ),
            'rejected' => array(
                'label' => __('Rejection Notice', 'shinseiflow-application-review'),
                'template' => 'rejected',
                'recipient_type' => 'applicant',
                'recipient_label' => __('To Applicant', 'shinseiflow-application-review'),
            ),
            'resubmitted' => array(
                'label' => __('Resubmission Notice (Admin)', 'shinseiflow-application-review'),
                'template' => 'resubmitted',
                'recipient_type' => 'admin',
                'recipient_label' => __('To Administrator', 'shinseiflow-application-review'),
            ),
        );
    }

    private function get_resend_email_options($item) {
        $defs = $this->resend_email_type_definitions();
        $allowed = array('received');
        if ($item && isset($item->status)) {
            if ($item->status === 'pending') {
                $allowed[] = 'admin';
                if (!empty($item->resubmit_count)) {
                    $allowed[] = 'resubmitted';
                }
            } elseif (in_array($item->status, array('approved', 'published'), true)) {
                $allowed[] = 'approved';
            } elseif ($item->status === 'rejected') {
                $allowed[] = 'rejected';
            }
        }
        $allowed = array_values(array_unique($allowed));
        $options = array();
        foreach ($allowed as $key) {
            if (isset($defs[$key])) {
                $options[$key] = $defs[$key];
            }
        }
        return $options;
    }

    private function render_resend_email_card($item) {
        $options = $this->get_resend_email_options($item);
        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-detail-resend-panel tcarm-admin-card">
            <div class="tcarm-panel-header"><h2><?php echo esc_html__('Resend Notification Email', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Resend notification emails according to the current status when the applicant or administrator did not receive them.', 'shinseiflow-application-review'); ?></p></div>
            <div class="tcarm-detail-side-inner">
                <?php if (empty($options)): ?>
                    <p class="description"><?php echo esc_html__('There are currently no notification emails available for resend.', 'shinseiflow-application-review'); ?></p>
                <?php else: ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="tcarm-resend-email-form">
                        <?php wp_nonce_field('tcarm_resend_email_' . $item->id); ?>
                        <input type="hidden" name="action" value="tcarm_resend_email">
                        <input type="hidden" name="id" value="<?php echo absint($item->id); ?>">
                        <p>
                            <label><?php echo esc_html__('Email to resend', 'shinseiflow-application-review'); ?><br>
                                <select name="email_type" id="tcarm-resend-email-type" class="regular-text">
                                    <?php foreach ($options as $key => $option): ?>
                                        <option value="<?php echo esc_attr($key); ?>" data-mail-label="<?php echo esc_attr($option['label']); ?>" data-recipient-label="<?php echo esc_attr($option['recipient_label']); ?>"><?php echo esc_html($option['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </p>
                        <p class="description" id="tcarm-resend-email-recipient"><?php echo esc_html__('Recipient:', 'shinseiflow-application-review'); ?> <?php echo esc_html(reset($options)['recipient_label']); ?></p>
                        <button type="button" class="button button-primary" id="tcarm-open-resend-email-modal"><?php echo esc_html__('Resend', 'shinseiflow-application-review'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($options)): ?>
            <?php echo wp_kses($this->render_resend_email_confirm_modal(), $this->resend_email_modal_allowed_html()); ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    private function render_resend_email_confirm_modal() {
        wp_enqueue_script('tcarm-admin-resend-email', self::plugin_url() . 'assets/js/admin-resend-email.js', array(), self::VERSION, true);
        $resend_email_i18n = array(
            'recipientPrefix' => __('Recipient: ', 'shinseiflow-application-review'),
            'dash' => __('—', 'shinseiflow-application-review'),
        );
        wp_add_inline_script(
            'tcarm-admin-resend-email',
            'window.tcarmAdminI18n = Object.assign({}, window.tcarmAdminI18n || {}, ' . wp_json_encode($resend_email_i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ');',
            'before'
        );
        ob_start();
        ?>
        <div class="tcarm-confirm-modal" id="tcarm-resend-email-confirm-modal" aria-hidden="true" style="display:none;">
            <div class="tcarm-confirm-modal__backdrop" data-tcarm-modal-close="resend-email"></div>
            <div class="tcarm-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tcarm-resend-email-confirm-title">
                <h2 id="tcarm-resend-email-confirm-title"><?php echo esc_html__('Resend notification email?', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('The selected notification email will be resent. Do you want to continue?', 'shinseiflow-application-review'); ?></p>
                <p><strong><?php echo esc_html__('Email type:', 'shinseiflow-application-review'); ?></strong><span id="tcarm-resend-email-confirm-type">—</span><br><strong><?php echo esc_html__('Recipient:', 'shinseiflow-application-review'); ?></strong> <span id="tcarm-resend-email-confirm-recipient">—</span></p>
                <div class="tcarm-confirm-modal__actions">
                    <button type="button" class="button" data-tcarm-modal-close="resend-email"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></button>
                    <button type="button" class="button button-primary" id="tcarm-confirm-resend-email-submit"><?php echo esc_html__('Resend', 'shinseiflow-application-review'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function resend_email_modal_allowed_html() {
        return array(
            'div' => array(
                'class' => true,
                'id' => true,
                'aria-hidden' => true,
                'style' => true,
                'data-tcarm-modal-close' => true,
                'role' => true,
                'aria-modal' => true,
                'aria-labelledby' => true,
            ),
            'h2' => array(
                'id' => true,
            ),
            'p' => array(),
            'strong' => array(),
            'span' => array(
                'id' => true,
            ),
            'br' => array(),
            'button' => array(
                'type' => true,
                'class' => true,
                'id' => true,
                'data-tcarm-modal-close' => true,
            ),
        );
    }

    public function handle_send_test_email() {
        if (!$this->current_user_can_manage_mail_settings()) {
            wp_die(esc_html__('Permission denied.', 'shinseiflow-application-review'));
        }
        check_admin_referer('tcarm_send_test_email');
        $test_email = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';
        $redirect_url = admin_url('admin.php?page=tcarm_mail_settings');
        if (!$test_email || !is_email($test_email)) {
            wp_safe_redirect(add_query_arg('tcarm_test_mail', 'invalid', $redirect_url));
            exit;
        }
        $settings = self::get_settings();
        $subject = '[' . wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES) . '] ' . __('Email Delivery Test', 'shinseiflow-application-review');
        $body = __('This is a test email for the application management system email delivery settings.', 'shinseiflow-application-review') . "\n" . __('If you received this email, the email delivery settings are working correctly.', 'shinseiflow-application-review');
        $headers = array();
        $from_email = isset($settings['from_email']) ? sanitize_email($settings['from_email']) : '';
        if ($from_email && is_email($from_email)) {
            $from_name = isset($settings['from_name']) ? $this->sanitize_mail_header_text($settings['from_name']) : '';
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }
        $sent = $this->send_tcarm_mail($test_email, $subject, $body, $headers);
        wp_safe_redirect(add_query_arg('tcarm_test_mail', $sent ? 'success' : 'failed', $redirect_url));
        exit;
    }

    private function current_user_can_manage_mail_settings() {
        return $this->current_user_can_manage_tcarm();
    }

    private function send_tcarm_mail($to, $subject, $body, $headers = array()) {
        $to = sanitize_email($to);
        if (!$to || !is_email($to)) {
            return false;
        }
        $subject = $this->sanitize_mail_header_text($subject);
        $this->tcarm_mail_in_progress = true;
        $sent = wp_mail($to, $subject, $body, $headers);
        $this->tcarm_mail_in_progress = false;
        return $sent;
    }

    public function configure_phpmailer_for_tcarm_mail($phpmailer) {
        if (!$this->tcarm_mail_in_progress) {
            return;
        }
        $settings = self::get_settings();
        if (empty($settings['mail_send_method']) || $settings['mail_send_method'] !== 'smtp') {
            return;
        }
        $host = isset($settings['smtp_host']) ? trim((string) $settings['smtp_host']) : '';
        if ($host === '') {
            return;
        }
        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = isset($settings['smtp_port']) ? max(1, min(65535, absint($settings['smtp_port']))) : 587;
        $phpmailer->SMTPAuth = !empty($settings['smtp_auth']) && $settings['smtp_auth'] === '1';
        if ($phpmailer->SMTPAuth) {
            $phpmailer->Username = isset($settings['smtp_username']) ? (string) $settings['smtp_username'] : '';
            $phpmailer->Password = isset($settings['smtp_password']) ? (string) $settings['smtp_password'] : '';
        }
        $encryption = isset($settings['smtp_encryption']) ? sanitize_key($settings['smtp_encryption']) : 'tls';
        if ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        } elseif ($encryption === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        } else {
            $phpmailer->SMTPSecure = '';
            $phpmailer->SMTPAutoTLS = false;
        }
    }

    private function sanitize_mail_header_text($text) {
        $text = sanitize_text_field((string) $text);
        return str_replace(array("\r", "\n"), '', $text);
    }

    private function sanitize_mail_address_list($value) {
        $parts = preg_split('/[,;]/', (string) $value);
        $safe = array();
        foreach ($parts as $part) {
            $email = sanitize_email(trim($part));
            if ($email && is_email($email)) {
                $safe[] = $email;
            }
        }
        return implode(',', array_unique($safe));
    }

    public function handle_resend_email() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('Permission denied.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized id is required to build the per-application resend nonce action checked immediately below.
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        check_admin_referer('tcarm_resend_email_' . $id);
        $item = $this->get_application($id);
        if (!$item) {
            wp_die(esc_html__('Application not found.', 'shinseiflow-application-review'));
        }
        $email_type = isset($_POST['email_type']) ? sanitize_key(wp_unslash($_POST['email_type'])) : '';
        $options = $this->get_resend_email_options($item);
        if (!$email_type || !isset($options[$email_type])) {
            wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&action=view&id=' . $id . '&tcarm_resend=invalid'));
            exit;
        }
        $option = $options[$email_type];
        $settings = self::get_settings();
        if ($option['recipient_type'] === 'admin') {
            $to = isset($settings['recipient_email']) ? sanitize_email($settings['recipient_email']) : '';
            $is_admin_mail = true;
        } else {
            $to = isset($item->contact_email) ? sanitize_email($item->contact_email) : '';
            $is_admin_mail = false;
        }
        if (!$to) {
            $this->append_application_history_entry($id, 'email_resend_failed', $option['label'] . ' ' . __('Failed to resend email. No recipient is configured.', 'shinseiflow-application-review'));
            wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&action=view&id=' . $id . '&tcarm_resend=failed'));
            exit;
        }
        $sent = $this->send_template_email($to, $option['template'], $item, $is_admin_mail);
        if ($sent) {
            $this->append_application_history_entry($id, 'email_resent', $option['label'] . ' ' . __('Email resent. Recipient:', 'shinseiflow-application-review') . ' ' . $option['recipient_label']);
            wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&action=view&id=' . $id . '&tcarm_resend=success'));
            exit;
        }
        $this->append_application_history_entry($id, 'email_resend_failed', $option['label'] . ' ' . __('Failed to resend email. Recipient:', 'shinseiflow-application-review') . ' ' . $option['recipient_label']);
        wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&action=view&id=' . $id . '&tcarm_resend=failed'));
        exit;
    }

    private function send_template_email($to, $template_key, $item, $admin = false) {
        $settings = self::get_settings();
        $subject_key = 'email_subject_' . $template_key;
        $body_key = 'email_body_' . $template_key;
        $subject = isset($settings[$subject_key]) ? $settings[$subject_key] : '';
        $body = isset($settings[$body_key]) ? $settings[$body_key] : '';
        $status_url = $this->get_frontend_page_url('status');
        $lookup_token = $item ? $this->create_lookup_token($item) : '';
        $view_url = ($item && $lookup_token) ? $this->build_frontend_url('view', $item->application_code, $lookup_token) : $this->get_frontend_page_url('view');
        $edit_url = ($item && $lookup_token) ? $this->build_frontend_url('edit', $item->application_code, $lookup_token) : $this->get_frontend_page_url('edit');
        $admin_url = ($item && !empty($item->id)) ? admin_url('admin.php?page=tcarm_applications&action=view&id=' . absint($item->id)) : admin_url('admin.php?page=tcarm_applications');
        $replacements = array(
            '{application_code}' => $item->application_code,
            '{application_no}' => $item->application_code,
            '{applicant_name}' => $this->application_value($item, 'applicant_name'),
            '{email}' => $this->application_value($item, 'contact_email'),
            '{contact_email}' => $this->application_value($item, 'contact_email'),
            '{contact_phone}' => $this->application_value($item, 'contact_phone'),
            '{organization_name}' => $this->application_value($item, 'organization_name'),
            '{usage_purpose}' => $this->application_value($item, 'usage_purpose'),
            '{usage_period}' => $this->application_value($item, 'usage_period'),
            '{media}' => $this->application_value($item, 'media'),
            '{event_title}' => $this->application_value($item, 'event_title'),
            '{event_period}' => $this->application_value($item, 'event_period'),
            '{event_location}' => $this->application_value($item, 'event_location'),
            '{status}' => self::status_label($item->status),
            '{status_url}' => $status_url,
            '{view_url}' => $view_url,
            '{edit_url}' => $edit_url,
            '{admin_url}' => $admin_url,
            '{site_name}' => get_bloginfo('name'),
            '{reject_reason}' => $item->reject_reason,
            '{request_note}' => $item->request_note,
            '{resubmit_count}' => isset($item->resubmit_count) ? (string) $item->resubmit_count : '0',
            '{submitted_at}' => $item->created_at,
            '{updated_at}' => $item->updated_at,
        );
        foreach (self::get_fields() as $field_key => $field) {
            $replacements['{' . $field_key . '}'] = $this->format_field_value($this->application_value($item, $field_key), $field);
        }
        $subject = strtr($subject, $replacements);
        $body = strtr($body, $replacements);
        $headers = array();
        $from_email = !empty($settings['from_email']) ? sanitize_email($settings['from_email']) : '';
        if ($from_email && is_email($from_email)) {
            $headers[] = 'From: ' . $this->sanitize_mail_header_text($settings['from_name']) . ' <' . $from_email . '>';
        }
        if ($admin) {
            if (!empty($settings['cc_email'])) {
                $cc_email = $this->sanitize_mail_address_list($settings['cc_email']);
                if ($cc_email !== '') {
                    $headers[] = 'Cc: ' . $cc_email;
                }
            }
            if (!empty($settings['bcc_email'])) {
                $bcc_email = $this->sanitize_mail_address_list($settings['bcc_email']);
                if ($bcc_email !== '') {
                    $headers[] = 'Bcc: ' . $bcc_email;
                }
            }
        }
        return $this->send_tcarm_mail($to, $subject, $body, $headers);
    }
}
