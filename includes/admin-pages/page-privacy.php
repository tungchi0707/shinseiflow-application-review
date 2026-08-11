<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Privacy_Trait {
    public function render_privacy_settings_page() {
        $settings = self::get_settings();
        $this->open_admin_wrap(__('Personal Information and Data Storage', 'shinseiflow-application-review'));
        ?>
            <form method="post" action="options.php" class="tcarm-basic-settings-form tcarm-admin-privacy-settings-page tcarm-admin-settings-page">
                <?php settings_fields('tcarm_settings_group'); ?>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_partial]" value="1">

                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Privacy notice markup uses escaped text and textarea content before output.
                echo $this->render_privacy_data_retention_notice_card();
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Uninstall policy markup escapes option values and labels before output.
                echo $this->render_uninstall_data_policy_card($settings);
                ?>

                <?php submit_button(__('Save Settings', 'shinseiflow-application-review')); ?>
            </form>
        </div>
        <?php
    }

    private function render_privacy_data_retention_notice_card() {        $sample_text = "This site may collect and store information entered in application forms, contact details, uploaded files, review and notification history, and related records for receiving applications, reviewing content, sending notifications, operating the service, preventing spam, and troubleshooting.\n\nExternal services such as Cloudflare Turnstile may be used for spam prevention when enabled. AI translation tools may be used by administrators to assist with translating form fields and settings. Applicant-submitted content is not normally sent to AI translation automatically.\n\nPlease adjust this sample text to match the actual operation of your site.";
        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-privacy-data-notice-card">
            <div class="tcarm-panel-header">
                <h2><?php echo esc_html__('About Personal Information and Data Storage', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('Admin-facing notes about application data, attachments, notification history, and external service usage.', 'shinseiflow-application-review'); ?></p>
            </div>
            <div class="tcarm-settings-card-body">
                <p class="description"><?php echo esc_html__('This plugin stores information entered in application forms, uploaded files, review history, notification history, and related data for receiving applications, reviewing content, sending notifications, operations, spam prevention, and troubleshooting.', 'shinseiflow-application-review'); ?></p>

                <div class="tcarm-settings-row-list">
                    <div class="tcarm-settings-field">
                        <strong><?php echo esc_html__('Main Information Stored', 'shinseiflow-application-review'); ?></strong>
                        <ul class="tcarm-privacy-data-notice-list">
                            <li><?php echo esc_html__('Applicant information: Information entered or selected by the applicant based on Form Settings.', 'shinseiflow-application-review'); ?></li>
                            <li><?php echo esc_html__('Application content information: Content entered or selected by the applicant based on Form Settings.', 'shinseiflow-application-review'); ?></li>
                            <li><?php echo esc_html__('Attachments: Files uploaded during application submission or resubmission.', 'shinseiflow-application-review'); ?></li>
                            <li><?php echo esc_html__('Review information: Review status, approval or rejection results, review messages, rejection reasons, and related information.', 'shinseiflow-application-review'); ?></li>
                            <li><?php echo esc_html__('Notification and sending history: Submission receipts, auto replies, admin notifications, approval notifications, rejection notifications, resubmission notifications, submission and response history, and related records.', 'shinseiflow-application-review'); ?></li>
                            <li><?php echo esc_html__('Security-related information: Depending on settings and operations, submission times and information related to spam prevention or troubleshooting may be stored.', 'shinseiflow-application-review'); ?></li>
                        </ul>
                    </div>

                    <div class="tcarm-settings-field">
                        <strong><?php echo esc_html__('Purpose of Use', 'shinseiflow-application-review'); ?></strong>
                        <p class="description"><?php echo esc_html__('Saved information is used for receiving applications, reviewing content, review workflows, notifications, operations, spam prevention, and troubleshooting.', 'shinseiflow-application-review'); ?></p>
                    </div>

                    <div class="tcarm-settings-field">
                        <strong><?php echo esc_html__('About External Services', 'shinseiflow-application-review'); ?></strong>
                        <ul class="tcarm-privacy-data-notice-list">
                            <li><?php echo esc_html__('When Cloudflare Turnstile is enabled, an external service verification is performed as spam protection during form submission.', 'shinseiflow-application-review'); ?></li>
                            <li><?php echo esc_html__('The AI translation feature is an admin tool used to assist with translating form fields and related settings. It does not normally send applicant-submitted content to AI translation automatically.', 'shinseiflow-application-review'); ?></li>
                            <li><?php echo esc_html__('Analytics services such as Google Analytics may be used separately depending on site settings.', 'shinseiflow-application-review'); ?></li>
                        </ul>
                    </div>

                    <label class="tcarm-settings-field">
                        <strong><?php echo esc_html__('Sample Text for Privacy Policy', 'shinseiflow-application-review'); ?></strong>
                        <textarea class="large-text code" rows="10" readonly><?php echo esc_textarea($sample_text); ?></textarea>
                        <span class="description"><?php echo esc_html__('This text is an example. The actual privacy policy should be reviewed and adjusted by the site operator.', 'shinseiflow-application-review'); ?></span>
                    </label>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_uninstall_data_policy_card($settings) {
        $delete_data_on_uninstall = isset($settings['delete_data_on_uninstall']) ? (string) $settings['delete_data_on_uninstall'] : '0';

        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-uninstall-data-policy-card">
            <div class="tcarm-panel-header">
                <h2><?php echo esc_html__('Data Deletion on Uninstall', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('Configure whether data saved by this plugin should be deleted when the plugin is deleted.', 'shinseiflow-application-review'); ?></p>
            </div>
            <div class="tcarm-settings-card-body">
                <div class="tcarm-settings-row-list">
                    <label class="tcarm-settings-check">
                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[delete_data_on_uninstall]" value="1" <?php checked($delete_data_on_uninstall, '1'); ?>>
                        <?php echo esc_html__('Delete plugin data on uninstall', 'shinseiflow-application-review'); ?>
                    </label>
                    <p class="description"><?php echo esc_html__('When enabled, settings data and application-related data saved by this plugin are deleted when the plugin is deleted from the WordPress admin screen. Deactivating the plugin alone does not delete data. Files in the media library and notification email template bodies are not included in automatic deletion.', 'shinseiflow-application-review'); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
