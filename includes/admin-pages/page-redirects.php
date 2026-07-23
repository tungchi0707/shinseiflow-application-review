<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Redirects_Trait {
    public function render_settings() {
        $settings = self::get_settings();
        $this->open_admin_wrap(__('Basic Settings', 'shinseiflow-application-review'));
        ?>
            <form method="post" action="options.php" class="tcarm-basic-settings-form tcarm-admin-basic-settings-page tcarm-admin-settings-page">
                <?php settings_fields('tcarm_settings_group'); ?>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_partial]" value="1">

                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-page-settings">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Frontend Page Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Configure application form, status lookup, content review, and edit pages for each language.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <p class="description"><?php echo esc_html__('Selecting fixed pages is recommended. Languages without settings fall back to the Japanese setting or existing settings.', 'shinseiflow-application-review'); ?></p>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Frontend page settings markup escapes page URLs, labels, and field attributes before output.
                        echo $this->render_language_frontend_page_settings($settings);
                        ?>
                    </div>
                </div>

                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Application number settings markup escapes preview, rule values, and attributes before output.
                echo $this->render_application_number_settings_card($settings);
                ?>

                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Attachment Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Common rules for attachments that can be uploaded through the application form.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <div class="tcarm-settings-row-list">
                            <label class="tcarm-settings-check"><input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[file_upload_enabled]" value="1" <?php checked(isset($settings['file_upload_enabled']) ? $settings['file_upload_enabled'] : '1', '1'); ?>> <?php echo esc_html__('Enable file upload fields', 'shinseiflow-application-review'); ?></label>
                            <label class="tcarm-settings-field"><?php echo esc_html__('Allowed Extensions', 'shinseiflow-application-review'); ?>
                                <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[file_allowed_extensions]" value="<?php echo esc_attr(isset($settings['file_allowed_extensions']) ? $settings['file_allowed_extensions'] : 'jpg,jpeg,png,pdf'); ?>">
                                <span class="description"><?php echo esc_html__('Specify values separated by commas. Example:', 'shinseiflow-application-review'); ?><code>jpg,jpeg,png,pdf</code><?php echo esc_html__('. For safety, svg, html, php, and similar file types cannot be used.', 'shinseiflow-application-review'); ?></span>
                            </label>
                            <label class="tcarm-settings-field tcarm-settings-inline-number"><?php echo esc_html__('Maximum File Size', 'shinseiflow-application-review'); ?>
                                <span><input type="number" min="1" max="50" class="small-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[file_max_size_mb]" value="<?php echo esc_attr(isset($settings['file_max_size_mb']) ? $settings['file_max_size_mb'] : '5'); ?>"> MB</span>
                            </label>
                            <label class="tcarm-settings-field tcarm-settings-inline-number"><?php echo esc_html__('Maximum Upload Count', 'shinseiflow-application-review'); ?>
                                <span><input type="number" min="1" max="10" class="small-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[file_max_uploads]" value="<?php echo esc_attr(isset($settings['file_max_uploads']) ? $settings['file_max_uploads'] : '3'); ?>"> <?php echo esc_html__('items', 'shinseiflow-application-review'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>

                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Permission settings card escapes role labels, keys, and attributes before output.
                echo $this->render_permission_settings_card($settings);
                ?>

                <?php submit_button(__('Save Settings', 'shinseiflow-application-review')); ?>
            </form>
        </div>
        <?php
    }

    private function render_application_number_settings_card($settings) {
        $rule = isset($settings['application_number_rule']) && is_array($settings['application_number_rule']) ? $settings['application_number_rule'] : self::default_application_number_rule();
        $preview = $this->build_application_code_from_rule($rule, 1);
        if ($preview === '') {
            $preview = $this->build_application_code_from_rule(self::default_application_number_rule(), 1);
        }

        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-application-number-settings-card">
            <div class="tcarm-panel-header">
                <h2><?php echo esc_html__('Application Number Settings', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('Configure the format of application numbers issued automatically for new submissions.', 'shinseiflow-application-review'); ?></p>
            </div>
            <div class="tcarm-settings-card-body">
                <p class="description"><?php echo esc_html__('This setting applies only to newly submitted applications. Already issued application numbers will not change. Up to 32 characters can be used.', 'shinseiflow-application-review'); ?></p>
                <div class="tcarm-number-rule-preview">
                    <span class="tcarm-number-rule-preview-label"><?php echo esc_html__('Preview', 'shinseiflow-application-review'); ?>:</span>
                    <code data-tcarm-application-number-preview><?php echo esc_html($preview); ?></code>
                </div>
                <div class="tcarm-number-rule-compact">
                    <div class="tcarm-number-rule-builder tcarm-application-number-rule-list" data-tcarm-application-number-rule-list>
                        <?php foreach ($rule as $index => $row): ?>
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rule row markup escapes rule values and attributes before output.
                            echo $this->render_application_number_rule_row($index, $row);
                            ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="tcarm-number-rule-add-card">
                <button type="button" class="button button-secondary" data-tcarm-add-application-number-rule><?php echo esc_html__('Add Row', 'shinseiflow-application-review'); ?></button>
                    </div>
                </div>
                <script type="text/template" id="tcarm-application-number-rule-template">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template row markup is generated by escaped rule renderer and stored in a text/template script.
                    echo $this->render_application_number_rule_row('__KEY__', array('type' => 'fixed', 'value' => 'APP'));
                    ?>
                </script>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_application_number_rule_row($index, $row) {
        $row = is_array($row) ? $row : array();
        $type = isset($row['type']) ? sanitize_key($row['type']) : 'fixed';
        $value = isset($row['value']) ? (string) $row['value'] : ($type === 'symbol' ? '-' : '');
        $format = isset($row['format']) ? (string) $row['format'] : 'Ymd';
        $length = isset($row['length']) ? absint($row['length']) : ($type === 'sequence' ? 6 : 2);
        $base_name = self::OPTION_SETTINGS . '[application_number_rule][' . $index . ']';

        ob_start();
        ?>
        <div class="tcarm-number-rule-card tcarm-application-number-rule-row" data-tcarm-application-number-rule-row>
            <input type="hidden" name="<?php echo esc_attr($base_name); ?>[_delete]" value="0" data-tcarm-rule-delete>
            <div class="tcarm-number-rule-card-title">
                <select name="<?php echo esc_attr($base_name); ?>[type]" data-tcarm-rule-type>
                    <option value="fixed" <?php selected($type, 'fixed'); ?>><?php echo esc_html__('Fixed Text', 'shinseiflow-application-review'); ?></option>
                    <option value="symbol" <?php selected($type, 'symbol'); ?>><?php echo esc_html__('Static Text', 'shinseiflow-application-review'); ?></option>
                    <option value="date" <?php selected($type, 'date'); ?>><?php echo esc_html__('Date', 'shinseiflow-application-review'); ?></option>
                    <option value="random_letters" <?php selected($type, 'random_letters'); ?>><?php echo esc_html__('Random Letters', 'shinseiflow-application-review'); ?></option>
                    <option value="random_numbers" <?php selected($type, 'random_numbers'); ?>><?php echo esc_html__('Random Digits', 'shinseiflow-application-review'); ?></option>
                    <option value="sequence" <?php selected($type, 'sequence'); ?>><?php echo esc_html__('Sequence', 'shinseiflow-application-review'); ?></option>
                </select>
            </div>
            <div class="tcarm-number-rule-card-control">
                <input type="text" class="regular-text" name="<?php echo esc_attr($base_name); ?>[value]" value="<?php echo esc_attr($value); ?>" placeholder="APP" data-tcarm-rule-value>
                <select name="<?php echo esc_attr($base_name); ?>[format]" data-tcarm-rule-format>
                    <option value="Ymd" <?php selected($format, 'Ymd'); ?>>Ymd</option>
                    <option value="Ym" <?php selected($format, 'Ym'); ?>>Ym</option>
                    <option value="Y" <?php selected($format, 'Y'); ?>>Y</option>
                </select>
                <input type="number" min="1" max="12" class="small-text" name="<?php echo esc_attr($base_name); ?>[length]" value="<?php echo esc_attr($length); ?>" data-tcarm-rule-length>
            </div>
            <div class="tcarm-number-rule-card-actions">
                <button type="button" class="button button-secondary" data-tcarm-remove-application-number-rule><?php echo esc_html__('Delete', 'shinseiflow-application-review'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_permission_settings_card($settings) {
        if (!current_user_can('manage_options')) {
            ob_start();
            ?>
            <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-permission-settings-card">
                <div class="tcarm-panel-header">
                    <h2><?php echo esc_html__('Permission Settings', 'shinseiflow-application-review'); ?></h2>
                    <p><?php echo esc_html__('Only WordPress administrators can change permission settings.', 'shinseiflow-application-review'); ?></p>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        $roles = function_exists('get_editable_roles') ? get_editable_roles() : array();
        if (empty($roles) || !is_array($roles)) {
            $roles = array(
                'administrator' => array('name' => 'Administrator'),
            );
        }
        $allowed_roles = self::normalize_tcarm_allowed_roles(isset($settings['allowed_roles']) ? $settings['allowed_roles'] : array('administrator'));

        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-permission-settings-card">
            <div class="tcarm-panel-header">
                <h2><?php echo esc_html__('Permission Settings', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('Configure role groups that can use this plugin.', 'shinseiflow-application-review'); ?></p>
            </div>
            <div class="tcarm-settings-card-body">
                <p class="description"><?php echo esc_html__('Grant access to this plugin\'s admin screens to the role groups selected here. Only WordPress administrators can change permission settings.', 'shinseiflow-application-review'); ?></p>
                <div class="tcarm-settings-row-list">
                    <div class="tcarm-settings-field">
                        <strong><?php echo esc_html__('Role groups that can use this plugin', 'shinseiflow-application-review'); ?></strong>
                        <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[allowed_roles][]" value="administrator">
                        <div class="tcarm-settings-check-group">
                            <?php foreach ($roles as $role_key => $role): ?>
                                <?php
                                $role_key = sanitize_key($role_key);
                                $role_name = isset($role['name']) ? translate_user_role($role['name']) : $role_key;
                                $is_administrator = $role_key === 'administrator';
                                $is_checked = $is_administrator || in_array($role_key, $allowed_roles, true);
                                ?>
                                <label class="tcarm-settings-check">
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[allowed_roles][]" value="<?php echo esc_attr($role_key); ?>" <?php checked($is_checked); ?> <?php disabled($is_administrator); ?>>
                                    <?php echo esc_html($role_name); ?>
                                    <?php if ($is_administrator): ?>
                                        <span class="description"><?php echo esc_html__('Administrators can always use this plugin.', 'shinseiflow-application-review'); ?></span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="description"><?php echo esc_html__('The plugin-specific capability is removed from unselected role groups. Administrators are always enabled for safety.', 'shinseiflow-application-review'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

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
