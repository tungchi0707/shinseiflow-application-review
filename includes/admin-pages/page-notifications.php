<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Notifications_Trait {
    public function render_mail_settings_page() {
        $settings = self::get_settings();
        $templates = array(
            'received' => __('Applicant Auto Reply', 'shinseiflow-application-review'),
            'admin' => __('Admin Submission Notification', 'shinseiflow-application-review'),
            'approved' => __('Approval Notification', 'shinseiflow-application-review'),
            'rejected' => __('Rejection Notification', 'shinseiflow-application-review'),
            'resubmitted' => __('Resubmission Received Notification (Admin)', 'shinseiflow-application-review'),
        );
        $template_vars = array(
            '{application_code}' => __('Application Number', 'shinseiflow-application-review'),
            '{application_no}' => __('Application Number (Compatibility)', 'shinseiflow-application-review'),
            '{applicant_name}' => __('Applicant Name', 'shinseiflow-application-review'),
            '{email}' => __('Email Address', 'shinseiflow-application-review'),
            '{event_title}' => __('Event Name', 'shinseiflow-application-review'),
            '{status}' => __('Current Status', 'shinseiflow-application-review'),
            '{status_url}' => __('Application Status URL', 'shinseiflow-application-review'),
            '{view_url}' => __('Application Content Review URL', 'shinseiflow-application-review'),
            '{edit_url}' => __('Edit Resubmission URL', 'shinseiflow-application-review'),
            '{admin_url}' => __('Admin Screen URL', 'shinseiflow-application-review'),
            '{reject_reason}' => __('Rejection Reason', 'shinseiflow-application-review'),
            '{resubmit_count}' => __('Resubmission Count', 'shinseiflow-application-review'),
            '{site_name}' => __('Site Name', 'shinseiflow-application-review'),
        );
        $this->open_admin_wrap(__('Email Notifications', 'shinseiflow-application-review'));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect notice state; value is sanitized and allowlisted before display.
        $test_result = isset($_GET['tcarm_test_mail']) ? sanitize_key(wp_unslash($_GET['tcarm_test_mail'])) : '';
        if (!in_array($test_result, array('', 'success', 'failed', 'invalid'), true)) {
            $test_result = '';
        }
        if ($test_result === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('The test email was sent.', 'shinseiflow-application-review') . '</p></div>';
        } elseif ($test_result === 'failed') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to send the test email. Please check the sending settings.', 'shinseiflow-application-review') . '</p></div>';
        } elseif ($test_result === 'invalid') {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Please enter a valid email address.', 'shinseiflow-application-review') . '</p></div>';
        }
        ?>
            <form id="tcarm-test-mail-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="tcarm-test-mail-form">
                <?php wp_nonce_field('tcarm_send_test_email'); ?>
                <input type="hidden" name="action" value="tcarm_send_test_email">
            </form>

            <form method="post" action="options.php" class="tcarm-basic-settings-form tcarm-mail-settings-form tcarm-admin-mail-settings-page tcarm-admin-settings-page">
                <?php settings_fields('tcarm_settings_group'); ?>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_partial]" value="1">

                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Email Notifications', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Configure sender and recipient settings for applicant and admin notification emails.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <div class="tcarm-settings-row-list">
                            <label class="tcarm-settings-field"><?php echo esc_html__('Admin Recipients', 'shinseiflow-application-review'); ?>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[recipient_email]" value="<?php echo esc_attr($settings['recipient_email']); ?>">
                            </label>
                            <label class="tcarm-settings-field">CC
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[cc_email]" value="<?php echo esc_attr($settings['cc_email']); ?>">
                                <span class="description"><?php echo esc_html__('To specify multiple recipients, separate them with commas. Example: info@example.com, staff@example.com', 'shinseiflow-application-review'); ?></span>
                            </label>
                            <label class="tcarm-settings-field">BCC
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[bcc_email]" value="<?php echo esc_attr($settings['bcc_email']); ?>">
                                <span class="description"><?php echo esc_html__('To specify multiple recipients, separate them with commas. Example: info@example.com, staff@example.com', 'shinseiflow-application-review'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-mail-send-settings-card">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Sending Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Configure sender and sending method for notification emails. If SMTP is not used, emails are sent using WordPress default mail.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <div class="tcarm-settings-row-list">
                            <label class="tcarm-settings-field"><?php echo esc_html__('Sending Method', 'shinseiflow-application-review'); ?>
                                <select name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[mail_send_method]">
                                    <option value="wordpress" <?php selected($settings['mail_send_method'], 'wordpress'); ?>><?php echo esc_html__('WordPress Default Mail', 'shinseiflow-application-review'); ?></option>
                                    <option value="smtp" <?php selected($settings['mail_send_method'], 'smtp'); ?>><?php echo esc_html__('SMTP', 'shinseiflow-application-review'); ?></option>
                                </select>
                            </label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('From Name', 'shinseiflow-application-review'); ?>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[from_name]" value="<?php echo esc_attr($settings['from_name']); ?>">
                            </label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('From Email Address', 'shinseiflow-application-review'); ?>
                                <input type="email" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[from_email]" value="<?php echo esc_attr($settings['from_email']); ?>">
                                <span class="description"><?php echo esc_html__('When using SMTP, set an email address that is allowed to send from the SMTP account.', 'shinseiflow-application-review'); ?></span>
                            </label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('SMTP Host', 'shinseiflow-application-review'); ?>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[smtp_host]" value="<?php echo esc_attr($settings['smtp_host']); ?>">
                            </label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('SMTP Port', 'shinseiflow-application-review'); ?>
                                <input type="number" min="1" max="65535" class="small-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[smtp_port]" value="<?php echo esc_attr($settings['smtp_port']); ?>">
                                <span class="description"><?php echo esc_html__('Usually use TLS (587) or SSL (465).', 'shinseiflow-application-review'); ?></span>
                            </label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('Encryption', 'shinseiflow-application-review'); ?>
                                <select name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[smtp_encryption]">
                                    <option value="none" <?php selected($settings['smtp_encryption'], 'none'); ?>><?php echo esc_html__('None', 'shinseiflow-application-review'); ?></option>
                                    <option value="ssl" <?php selected($settings['smtp_encryption'], 'ssl'); ?>>SSL</option>
                                    <option value="tls" <?php selected($settings['smtp_encryption'], 'tls'); ?>>TLS</option>
                                </select>
                            </label>
                            <label class="tcarm-settings-field tcarm-settings-field--checkbox">
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[smtp_auth]" value="1" <?php checked($settings['smtp_auth'], '1'); ?>> <?php echo esc_html__('Use SMTP authentication', 'shinseiflow-application-review'); ?>
                            </label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('SMTP Username', 'shinseiflow-application-review'); ?>
                                <input type="text" class="regular-text" autocomplete="off" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[smtp_username]" value="<?php echo esc_attr($settings['smtp_username']); ?>">
                            </label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('SMTP Password', 'shinseiflow-application-review'); ?>
                                <input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[smtp_password]" value="" placeholder="<?php echo !empty($settings['smtp_password']) ? esc_attr__('Saved (enter only to change)', 'shinseiflow-application-review') : ''; ?>">
                                <span class="description"><?php echo esc_html__('The saved password is not displayed. Enter a value only when changing it.', 'shinseiflow-application-review'); ?></span>
                            </label>
                        </div>
                        <div class="tcarm-settings-subsection tcarm-test-mail-section">
                            <h3><?php echo esc_html__('Test Send', 'shinseiflow-application-review'); ?></h3>
                            <p class="description"><?php echo esc_html__('Send a test email using the current sending settings. The test email does not include application data or personal information.', 'shinseiflow-application-review'); ?></p>
                            <label class="tcarm-settings-field"><?php echo esc_html__('Test Recipient Email Address', 'shinseiflow-application-review'); ?>
                                <input type="email" class="regular-text" name="test_email" value="" required form="tcarm-test-mail-form">
                            </label>
                            <button type="submit" class="button button-secondary" form="tcarm-test-mail-form"><?php echo esc_html__('Send Test Email', 'shinseiflow-application-review'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-mail-template-card">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Email Templates', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Edit notification messages such as submission receipt and review results by switching tabs.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-mail-template-body">
                        <div class="tcarm-template-tabs" role="tablist" aria-label="<?php echo esc_attr__('Email Template', 'shinseiflow-application-review'); ?>">
                            <?php $first_template = true; foreach ($templates as $key => $label): ?>
                                <button type="button" class="<?php echo esc_attr('tcarm-template-tab' . ($first_template ? ' is-active' : '')); ?>" data-template="<?php echo esc_attr($key); ?>" role="tab" aria-selected="<?php echo esc_attr($first_template ? 'true' : 'false'); ?>"><?php echo esc_html($label); ?></button>
                            <?php $first_template = false; endforeach; ?>
                        </div>
                        <div class="tcarm-mail-template-layout">
                            <div class="tcarm-template-editor-area">
                                <?php $first_template = true; foreach ($templates as $key => $label): ?>
                                    <section class="<?php echo esc_attr('tcarm-template-panel' . ($first_template ? ' is-active' : '')); ?>" data-template-panel="<?php echo esc_attr($key); ?>" role="tabpanel">
                                        <h3><?php echo esc_html($label); ?></h3>
                                        <label class="tcarm-settings-field"><?php echo esc_html__('Subject', 'shinseiflow-application-review'); ?>
                                            <input type="text" class="large-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[email_subject_<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings['email_subject_' . $key]); ?>">
                                        </label>
                                        <label class="tcarm-settings-field"><?php echo esc_html__('Body', 'shinseiflow-application-review'); ?>
                                            <textarea class="large-text" rows="13" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[email_body_<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($settings['email_body_' . $key]); ?></textarea>
                                        </label>
                                    </section>
                                <?php $first_template = false; endforeach; ?>
                            </div>
                            <aside class="tcarm-template-vars" aria-label="<?php echo esc_attr__('Available Variables', 'shinseiflow-application-review'); ?>">
                                <h3><?php echo esc_html__('Available Variables', 'shinseiflow-application-review'); ?></h3>
                                <p><?php echo esc_html__('Copy these variables into the subject or body.', 'shinseiflow-application-review'); ?></p>
                                <div class="tcarm-template-var-list">
                                    <?php foreach ($template_vars as $var => $desc): ?>
                                        <button type="button" class="tcarm-copy-var" data-var="<?php echo esc_attr($var); ?>"><code><?php echo esc_html($var); ?></code><span><?php echo esc_html($desc); ?></span></button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description tcarm-copy-var-note"><?php echo esc_html__('Click a button to copy the variable.', 'shinseiflow-application-review'); ?></p>
                            </aside>
                        </div>
                    </div>
                </div>

                <?php submit_button(__('Save Settings', 'shinseiflow-application-review')); ?>
            </form>
        </div>
        <?php
    }
}
