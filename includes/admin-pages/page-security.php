<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Security_Trait {
    public function render_security_settings_page() {
        $settings = self::get_settings();
        $this->open_admin_wrap(__('Security Settings', 'shinseiflow-application-review'));
        ?>
            <form method="post" action="options.php" class="tcarm-basic-settings-form tcarm-security-settings-form tcarm-admin-security-page tcarm-admin-settings-page">
                <?php settings_fields('tcarm_settings_group'); ?>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_partial]" value="1">
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[_security_settings]" value="1">


                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Spam Protection Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Basic settings to help prevent automated form posts and repeated submissions in a short time.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <div class="tcarm-settings-row-list">
                            <div class="tcarm-settings-choice-row tcarm-spam-option">
                                <div class="tcarm-settings-choice-check">
                                    <input id="tcarm_honeypot_enabled" type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[honeypot_enabled]" value="1" <?php checked($settings['honeypot_enabled'], '1'); ?>>
                                </div>
                                <div class="tcarm-settings-choice-content">
                                    <label class="tcarm-settings-choice-title" for="tcarm_honeypot_enabled"><?php echo esc_html__('Enable simple bot protection using a hidden field and submission timing', 'shinseiflow-application-review'); ?></label>
                                    <p class="description"><?php echo esc_html__('Detect likely automated bot submissions using a hidden field that normal users cannot see and the elapsed time from form display to submission. Submissions are recorded in the blocked history when the hidden field contains a value or when the form is submitted immediately after display.', 'shinseiflow-application-review'); ?></p>
                                </div>
                            </div>
                            <div class="tcarm-settings-choice-row tcarm-spam-option">
                                <div class="tcarm-settings-choice-check">
                                    <input id="tcarm_rate_limit_enabled" type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[rate_limit_enabled]" value="1" <?php checked($settings['rate_limit_enabled'], '1'); ?>>
                                </div>
                                <div class="tcarm-settings-choice-content">
                                    <label class="tcarm-settings-choice-title" for="tcarm_rate_limit_enabled"><?php echo esc_html__('Enable short-term submission and lookup rate limits', 'shinseiflow-application-review'); ?></label>
                                    <p class="description"><?php echo esc_html__('Limit repeated submissions, resubmissions, and status lookups from the same IP address or email address within a short time. Limited attempts are saved to the blocked history with the reason, date and time, IP address, and minimal contact information.', 'shinseiflow-application-review'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-turnstile-settings-card">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Cloudflare Turnstile Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Add Turnstile verification to application, lookup, and edit forms when needed.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <div class="tcarm-settings-row-list">
                            <div class="tcarm-settings-choice-row tcarm-turnstile-master-row">
                                <div class="tcarm-settings-choice-content tcarm-turnstile-master-content">
                                    <label class="tcarm-settings-choice-title tcarm-turnstile-master-toggle tcarm-switch" for="tcarm_turnstile_enabled">
                                        <input id="tcarm_turnstile_enabled" class="tcarm-switch-input" type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_enabled]" value="1" <?php checked($settings['turnstile_enabled'], '1'); ?> aria-describedby="tcarm_turnstile_description">
                                        <span class="tcarm-switch-slider" aria-hidden="true"></span>
                                        <span class="tcarm-switch-label">
                                            <span class="tcarm-switch-label-text"><?php echo esc_html__('Enable Turnstile verification', 'shinseiflow-application-review'); ?></span>
                                            <span class="tcarm-switch-state" data-tcarm-switch-on="<?php echo esc_attr__('ON', 'shinseiflow-application-review'); ?>" data-tcarm-switch-off="<?php echo esc_attr__('OFF', 'shinseiflow-application-review'); ?>"><?php echo esc_html($settings['turnstile_enabled'] === '1' ? __('ON', 'shinseiflow-application-review') : __('OFF', 'shinseiflow-application-review')); ?></span>
                                        </span>
                                    </label>
                                    <p id="tcarm_turnstile_description" class="description"><?php echo esc_html__('Add Turnstile verification to application, lookup, and edit forms when needed.', 'shinseiflow-application-review'); ?></p>
                                </div>
                            </div>
                            <div class="tcarm-turnstile-dependent-settings<?php echo esc_attr($settings['turnstile_enabled'] === '1' ? '' : ' is-disabled'); ?>" data-tcarm-turnstile-dependent-settings aria-disabled="<?php echo esc_attr($settings['turnstile_enabled'] === '1' ? 'false' : 'true'); ?>">
                                <div class="tcarm-settings-choice-content tcarm-turnstile-dependent-content">
                                    <div class="tcarm-turnstile-scope-box">
                                        <strong class="tcarm-turnstile-scope-title"><?php echo esc_html__('Turnstile Scope', 'shinseiflow-application-review'); ?></strong>
                                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_apply_form]" value="1" <?php checked(isset($settings['turnstile_apply_form']) ? $settings['turnstile_apply_form'] : '1', '1'); ?>> <?php echo esc_html__('Application Form', 'shinseiflow-application-review'); ?></label>
                                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_apply_status]" value="1" <?php checked(isset($settings['turnstile_apply_status']) ? $settings['turnstile_apply_status'] : '0', '1'); ?>> <?php echo esc_html__('Application Status Lookup Form', 'shinseiflow-application-review'); ?></label>
                                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_apply_edit]" value="1" <?php checked(isset($settings['turnstile_apply_edit']) ? $settings['turnstile_apply_edit'] : '0', '1'); ?>> <?php echo esc_html__('Edit and Resubmit Form', 'shinseiflow-application-review'); ?></label>
                                    </div>
                                </div>
                                <label class="tcarm-settings-field">Site Key
                                    <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_site_key]" value="<?php echo esc_attr($settings['turnstile_site_key']); ?>">
                                </label>
                                <label class="tcarm-settings-field">Secret Key
                                    <input type="password" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_secret_key]" value="<?php echo esc_attr($settings['turnstile_secret_key']); ?>">
                                </label>
                                <label class="tcarm-settings-field">Theme
                                    <select name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_theme]"><option value="auto" <?php selected($settings['turnstile_theme'], 'auto'); ?>>auto</option><option value="light" <?php selected($settings['turnstile_theme'], 'light'); ?>>light</option><option value="dark" <?php selected($settings['turnstile_theme'], 'dark'); ?>>dark</option></select>
                                </label>
                                <label class="tcarm-settings-field">Size
                                    <select name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[turnstile_size]"><option value="normal" <?php selected($settings['turnstile_size'], 'normal'); ?>>normal</option><option value="compact" <?php selected($settings['turnstile_size'], 'compact'); ?>>compact</option></select>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-download-security-settings">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Download Link Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Set the expiration time for download links issued to approved applicants. After a link expires, a new link is issued when the applicant checks the application status page again.', 'shinseiflow-application-review'); ?></p></div>
                    <div class="tcarm-settings-card-body">
                        <div class="tcarm-settings-row-list">
                            <label class="tcarm-settings-field"><?php echo esc_html__('Download Link Expiration', 'shinseiflow-application-review'); ?>
                                <select name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[download_link_expire_minutes]">
                                    <?php $download_expire = isset($settings['download_link_expire_minutes']) ? (string) absint($settings['download_link_expire_minutes']) : '30'; ?>
                                    <option value="10" <?php selected($download_expire, '10'); ?>><?php echo esc_html__('10 minutes', 'shinseiflow-application-review'); ?></option>
                                    <option value="30" <?php selected($download_expire, '30'); ?>><?php echo esc_html__('30 minutes', 'shinseiflow-application-review'); ?></option>
                                    <option value="60" <?php selected($download_expire, '60'); ?>><?php echo esc_html__('1 hour', 'shinseiflow-application-review'); ?></option>
                                    <option value="1440" <?php selected($download_expire, '1440'); ?>><?php echo esc_html__('24 hours', 'shinseiflow-application-review'); ?></option>
                                    <option value="0" <?php selected($download_expire, '0'); ?>><?php echo esc_html__('No expiration', 'shinseiflow-application-review'); ?></option>
                                </select>
                                <span class="description"><?php echo esc_html__('30 minutes is recommended in most cases. If set to no expiration, copied download URLs may remain usable for a long time.', 'shinseiflow-application-review'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>

                <?php submit_button(__('Save Settings', 'shinseiflow-application-review')); ?>
            </form>
        </div>
        <?php
    }
}
