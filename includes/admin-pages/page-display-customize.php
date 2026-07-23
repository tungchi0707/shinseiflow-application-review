<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Display_Customize_Trait {
    private function display_customize_class_groups() {
        return array(
            __('Frontend Common', 'shinseiflow-application-review') => array(
                __('Frontend Shortcode Wrapper', 'shinseiflow-application-review') => '.tcarm-front',
                __('Forms and Search Forms', 'shinseiflow-application-review') => '.tcarm-front-form, .tcarm-form',
                __('Cards and Sections', 'shinseiflow-application-review') => '.tcarm-front-card, .tcarm-front-section',
                __('Card Heading', 'shinseiflow-application-review') => '.tcarm-front-section-title',
                __('Card Description', 'shinseiflow-application-review') => '.tcarm-front-section-description',
            ),
            __('Page-specific Wrapper', 'shinseiflow-application-review') => array(
                __('Application Form', 'shinseiflow-application-review') => '.tcarm-front-application-form',
                __('Application Content Review', 'shinseiflow-application-review') => '.tcarm-front-application-confirm',
                __('Submission Complete Page', 'shinseiflow-application-review') => '.tcarm-complete-message, .tcarm-frontend-result',
                __('Submission Complete Page Form Frame', 'shinseiflow-application-review') => '.tcarm-complete-form',
                __('Submission Complete Section', 'shinseiflow-application-review') => '.tcarm-complete-section',
                __('Application Status', 'shinseiflow-application-review') => '.tcarm-front-application-status',
                __('Edit and Resubmit', 'shinseiflow-application-review') => '.tcarm-front-application-edit, .tcarm-application-edit-page, .tcarm-edit-form',
                __('Edit Resubmission Complete Page', 'shinseiflow-application-review') => '.tcarm-edit-complete-message, .tcarm-edit-complete-form, .tcarm-edit-complete-section',
            ),
            __('Application Flow', 'shinseiflow-application-review') => array(
                __('Entire Step Area', 'shinseiflow-application-review') => '.tcarm-front-steps',
                __('Step Card Common', 'shinseiflow-application-review') => '.tcarm-front-step',
                __('Input Step', 'shinseiflow-application-review') => '.tcarm-front-step-input',
                __('Review Step', 'shinseiflow-application-review') => '.tcarm-front-step-confirm',
                __('Submission Complete Step', 'shinseiflow-application-review') => '.tcarm-front-step-complete',
                __('Admin Review Step', 'shinseiflow-application-review') => '.tcarm-front-step-review',
                __('Current Step', 'shinseiflow-application-review') => '.tcarm-front-step.is-active',
                __('Step Icon', 'shinseiflow-application-review') => '.tcarm-front-step-icon',
                __('Step Icon Text', 'shinseiflow-application-review') => '.tcarm-front-step-symbol',
                __('Step Number', 'shinseiflow-application-review') => '.tcarm-front-step-number',
                __('Step Label', 'shinseiflow-application-review') => '.tcarm-front-step-label',
            ),
            __('Form Field', 'shinseiflow-application-review') => array(
                __('Input Item', 'shinseiflow-application-review') => '.tcarm-front-field, .tcarm-form-field',
                __('Label', 'shinseiflow-application-review') => '.tcarm-front-label, .tcarm-form-label',
                __('Required Badge', 'shinseiflow-application-review') => '.tcarm-required, .tcarm-required-mark',
                __('Input Field', 'shinseiflow-application-review') => '.tcarm-front-input, .tcarm-form-control',
                __('Textarea', 'shinseiflow-application-review') => '.tcarm-front-textarea',
                __('Select', 'shinseiflow-application-review') => '.tcarm-front-select',
                __('Checkbox', 'shinseiflow-application-review') => '.tcarm-front-checkbox',
                __('Supplemental Description', 'shinseiflow-application-review') => '.tcarm-field-help, .tcarm-file-help',
                __('Input Error', 'shinseiflow-application-review') => '.tcarm-field-error',
            ),
            __('Button', 'shinseiflow-application-review') => array(
                __('Button Area', 'shinseiflow-application-review') => '.tcarm-front-actions',
                __('Button Common', 'shinseiflow-application-review') => '.tcarm-front-button',
                __('Primary Button', 'shinseiflow-application-review') => '.tcarm-front-button--primary',
                __('Secondary Button', 'shinseiflow-application-review') => '.tcarm-front-button--secondary',
                __('Download Button', 'shinseiflow-application-review') => '.tcarm-download-button',
            ),
            __('Notices and Status', 'shinseiflow-application-review') => array(
                __('Notice Box Common', 'shinseiflow-application-review') => '.tcarm-front-notice',
                __('Information', 'shinseiflow-application-review') => '.tcarm-front-notice--info',
                __('Success', 'shinseiflow-application-review') => '.tcarm-front-notice--success',
                __('Notice', 'shinseiflow-application-review') => '.tcarm-front-notice--warning',
                __('Error', 'shinseiflow-application-review') => '.tcarm-front-notice--error',
                __('Status Badge Common', 'shinseiflow-application-review') => '.tcarm-front-status-badge, .tcarm-status-badge',
                __('Application Number Label', 'shinseiflow-application-review') => '.tcarm-application-number-label',
                __('Application Number', 'shinseiflow-application-review') => '.tcarm-application-number',
                __('Pending Review', 'shinseiflow-application-review') => '.tcarm-front-status-pending',
                __('Approved', 'shinseiflow-application-review') => '.tcarm-front-status-approved',
                __('Rejected', 'shinseiflow-application-review') => '.tcarm-front-status-rejected',
                __('Rejection Reason', 'shinseiflow-application-review') => '.tcarm-reject-reason-card, .tcarm-status-box__reason',
                __('Additional Confirmation Request', 'shinseiflow-application-review') => '.tcarm-front-status-needs-confirmation',
            ),
            __('Review and Application Content Display', 'shinseiflow-application-review') => array(
                __('Review List', 'shinseiflow-application-review') => '.tcarm-front-summary',
                __('Review Row', 'shinseiflow-application-review') => '.tcarm-front-summary-row',
                __('Review Label', 'shinseiflow-application-review') => '.tcarm-front-summary-label',
                __('Review Value', 'shinseiflow-application-review') => '.tcarm-front-summary-value',
                __('Details Table', 'shinseiflow-application-review') => '.tcarm-front-table, .tcarm-application-table',
            ),
            __('Files and Downloads', 'shinseiflow-application-review') => array(
                __('Attachment List', 'shinseiflow-application-review') => '.tcarm-attachment-list',
                __('Attachment Item', 'shinseiflow-application-review') => '.tcarm-attachment-item',
                __('Attachment Image Preview', 'shinseiflow-application-review') => '.tcarm-attachment-thumb',
                __('Download Section', 'shinseiflow-application-review') => '.tcarm-download-files',
                __('Download List', 'shinseiflow-application-review') => '.tcarm-download-file-list',
                __('Download Item', 'shinseiflow-application-review') => '.tcarm-download-file-item',
                __('Download Title', 'shinseiflow-application-review') => '.tcarm-download-file-title',
                __('Download Description', 'shinseiflow-application-review') => '.tcarm-download-file-description',
            ),
            __('Admin Screen', 'shinseiflow-application-review') => array(
                __('Admin Screen Wrapper', 'shinseiflow-application-review') => '.tcarm-admin-page',
                __('Settings Card', 'shinseiflow-application-review') => '.tcarm-admin-card',
                __('Settings Input Row', 'shinseiflow-application-review') => '.tcarm-admin-field-row',
                __('Application Details Page', 'shinseiflow-application-review') => '.tcarm-admin-application-detail',
                __('Review Actions', 'shinseiflow-application-review') => '.tcarm-review-actions',
                __('Submission and Response History', 'shinseiflow-application-review') => '.tcarm-history-timeline',
            ),
        );
    }

    private function render_css_class_table() {
        $groups = $this->display_customize_class_groups();
        ob_start();
        foreach ($groups as $group_label => $classes): ?>
            <h3><?php echo esc_html($group_label); ?></h3>
            <table class="widefat striped tcarm-css-class-table">
                <thead><tr><th><?php echo esc_html__('Purpose', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('CSS Class', 'shinseiflow-application-review'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($classes as $label => $class_name): ?>
                        <tr><td><?php echo esc_html($label); ?></td><td><code><?php echo esc_html($class_name); ?></code></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
        <h3><?php echo esc_html__('CSS Example', 'shinseiflow-application-review'); ?></h3>
        <pre class="tcarm-css-sample"><code>.tcarm-front-card {
  border-radius: 16px;
}

.tcarm-front-button--primary {
  background: #0f4c63;
}

.tcarm-front-status-approved {
  background: #eef8f1;
}</code></pre>
        <?php
        return ob_get_clean();
    }

    private function render_display_customize_settings_card($settings) {
        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-display-customize-card">
            <div class="tcarm-panel-header"><h2 class="tcarm-admin-card-title"><?php echo esc_html__('Display Customization', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Configure CSS applied to frontend pages and this plugin\'s admin screens.', 'shinseiflow-application-review'); ?></p></div>
            <div class="tcarm-settings-card-body">
                <div class="tcarm-display-customize-layout">
                    <div class="tcarm-display-editor-col">
                        <div class="tcarm-display-tabs" role="tablist" aria-label="<?php echo esc_attr__('Display Customization', 'shinseiflow-application-review'); ?>">
                            <button type="button" class="tcarm-display-tab is-active" data-display-panel="frontend" role="tab" aria-selected="true"><?php echo esc_html__('Frontend', 'shinseiflow-application-review'); ?></button>
                            <button type="button" class="tcarm-display-tab" data-display-panel="admin" role="tab" aria-selected="false"><?php echo esc_html__('Admin Screen', 'shinseiflow-application-review'); ?></button>
                        </div>
                        <section class="tcarm-display-panel is-active" data-display-panel="frontend" role="tabpanel">
                            <label class="tcarm-settings-field"><?php echo esc_html__('Frontend Custom CSS', 'shinseiflow-application-review'); ?>
                                <textarea class="large-text code tcarm-custom-css-textarea" rows="14" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[frontend_custom_css]" placeholder=".tcarm-application-form {&#10;  max-width: 960px;&#10;}"><?php echo esc_textarea(isset($settings['frontend_custom_css']) ? $settings['frontend_custom_css'] : ''); ?></textarea>
                                <span class="description"><?php echo esc_html__('Enter CSS applied to frontend pages such as the application form, status page, and download file display.', 'shinseiflow-application-review'); ?></span>
                            </label>
                        </section>
                        <section class="tcarm-display-panel" data-display-panel="admin" role="tabpanel">
                            <label class="tcarm-settings-field"><?php echo esc_html__('Admin Custom CSS', 'shinseiflow-application-review'); ?>
                                <textarea class="large-text code tcarm-custom-css-textarea" rows="14" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[admin_custom_css]" placeholder=".tcarm-history-timeline {&#10;  font-size: 13px;&#10;}"><?php echo esc_textarea(isset($settings['admin_custom_css']) ? $settings['admin_custom_css'] : ''); ?></textarea>
                                <span class="description"><?php echo esc_html__('Enter CSS applied only to this plugin\'s admin screens. It is not applied to the entire WordPress admin area.', 'shinseiflow-application-review'); ?></span>
                            </label>
                        </section>
                    </div>
                    <aside class="tcarm-display-classes-col" aria-label="<?php echo esc_attr__('CSS Class List', 'shinseiflow-application-review'); ?>">
                        <div class="tcarm-display-classes-card">
                            <h3><?php echo esc_html__('CSS Class List', 'shinseiflow-application-review'); ?></h3>
                            <p class="description"><?php echo esc_html__('Main CSS classes available for custom CSS. You can reference them while editing CSS on the left.', 'shinseiflow-application-review'); ?></p>
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS class table markup escapes group labels and class names before output.
                            echo $this->render_css_class_table();
                            ?>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_display_customize_settings_page() {
        $settings = self::get_settings();
        $this->open_admin_wrap(__('Display Customization', 'shinseiflow-application-review'));
        ?>
            <form method="post" action="options.php" class="tcarm-basic-settings-form tcarm-display-customize-settings-form tcarm-admin-display-customize-page tcarm-admin-settings-page">
                <?php settings_fields('tcarm_settings_group'); ?>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_partial]" value="1">

                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Settings card markup escapes custom CSS textarea content and fixed UI labels before output.
                echo $this->render_display_customize_settings_card($settings);
                ?>

                <?php submit_button(__('Save Settings', 'shinseiflow-application-review')); ?>
            </form>
        </div>
        <?php
    }
}
