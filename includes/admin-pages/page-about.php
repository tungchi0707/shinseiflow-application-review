<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_About_Trait {
    public function render_about_page() {
        $links = array(
            'resources' => array(
                'url' => 'https://labs.tungchi.jp/shinseiflow/',
            ),
            'development' => array(
                'url' => 'https://labs.tungchi.jp/support-the-project/',
            ),
        );

        $this->open_admin_wrap(__('About ShinseiFlow', 'shinseiflow-application-review'));
        ?>
        <div class="tcarm-about-page">
            <section class="tcarm-about-hero" aria-labelledby="tcarm-about-wordmark">
                <p id="tcarm-about-wordmark" class="tcarm-about-wordmark"><?php echo esc_html__('ShinseiFlow', 'shinseiflow-application-review'); ?></p>
                <h2><?php echo esc_html__('Application Review & Approval Workflow for WordPress', 'shinseiflow-application-review'); ?></h2>
                <p class="tcarm-about-version"><?php echo esc_html__('Version', 'shinseiflow-application-review'); ?> <?php echo esc_html(self::VERSION); ?></p>
                <p class="tcarm-about-description"><?php echo esc_html__('Build application forms, review submissions, manage approval workflows, send notifications, and provide approved downloads in WordPress.', 'shinseiflow-application-review'); ?></p>
            </section>

            <section class="tcarm-about-section tcarm-about-action-card tcarm-about-card-shell" aria-labelledby="tcarm-about-resources-title">
                <div class="tcarm-about-section-heading">
                    <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
                    <h2 id="tcarm-about-resources-title"><?php echo esc_html__('Documentation / Support', 'shinseiflow-application-review'); ?></h2>
                </div>
                <p><?php echo esc_html__('View setup guides and usage documentation, and get support or report an issue.', 'shinseiflow-application-review'); ?></p>
                <a class="button button-primary" href="<?php echo esc_url($links['resources']['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('View Documentation and Support', 'shinseiflow-application-review'); ?></a>
            </section>

            <section class="tcarm-about-section tcarm-about-action-card tcarm-about-card-shell" aria-labelledby="tcarm-about-support-title">
                <div class="tcarm-about-section-heading">
                    <span class="dashicons dashicons-heart" aria-hidden="true"></span>
                    <h2 id="tcarm-about-support-title"><?php echo esc_html__('Support Development', 'shinseiflow-application-review'); ?></h2>
                </div>
                <p><?php echo esc_html__('ShinseiFlow is an independently developed and maintained project. If ShinseiFlow has been helpful to you, please consider supporting its continued development.', 'shinseiflow-application-review'); ?></p>
                <a class="button button-primary" href="<?php echo esc_url($links['development']['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Support Development', 'shinseiflow-application-review'); ?></a>
            </section>

            <div class="tcarm-about-details-grid">
                <section class="tcarm-about-section" aria-labelledby="tcarm-about-credits-title">
                    <div class="tcarm-about-section-heading">
                        <span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
                        <h2 id="tcarm-about-credits-title"><?php echo esc_html__('Credits', 'shinseiflow-application-review'); ?></h2>
                    </div>
                    <dl class="tcarm-about-details">
                        <div><dt><?php echo esc_html__('Developer', 'shinseiflow-application-review'); ?></dt><dd><?php echo esc_html__('Casper Yeh', 'shinseiflow-application-review'); ?></dd></div>
                        <div><dt><?php echo esc_html__('Plugin', 'shinseiflow-application-review'); ?></dt><dd><?php echo esc_html__('ShinseiFlow – Application Review & Approval Workflow', 'shinseiflow-application-review'); ?></dd></div>
                        <div><dt><?php echo esc_html__('Version', 'shinseiflow-application-review'); ?></dt><dd><?php echo esc_html(self::VERSION); ?></dd></div>
                        <div><dt><?php echo esc_html__('License', 'shinseiflow-application-review'); ?></dt><dd><?php echo esc_html__('GPL-2.0-or-later', 'shinseiflow-application-review'); ?></dd></div>
                    </dl>
                </section>

                <section class="tcarm-about-section" aria-labelledby="tcarm-about-acknowledgements-title">
                    <div class="tcarm-about-section-heading">
                        <span class="dashicons dashicons-groups" aria-hidden="true"></span>
                        <h2 id="tcarm-about-acknowledgements-title"><?php echo esc_html__('Acknowledgements', 'shinseiflow-application-review'); ?></h2>
                    </div>
                    <p><?php echo esc_html__('ShinseiFlow is built on WordPress and made possible by the open-source community.', 'shinseiflow-application-review'); ?></p>
                </section>
            </div>
        </div>
        <?php
        $this->close_admin_wrap();
    }
}
