<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_About_Trait {
    public function render_about_page() {
        $links = array(
            'documentation' => array(
                'url' => 'https://labs.tungchi.jp/shinseiflow/docs/',
                'icon' => 'dashicons-media-document',
                'title' => __('Documentation', 'shinseiflow-application-review'),
                'description' => __('Read setup guidance and usage documentation.', 'shinseiflow-application-review'),
            ),
            'website' => array(
                'url' => 'https://labs.tungchi.jp/shinseiflow/',
                'icon' => 'dashicons-admin-site-alt3',
                'title' => __('Project Website', 'shinseiflow-application-review'),
                'description' => __('Learn more about ShinseiFlow and its workflow.', 'shinseiflow-application-review'),
            ),
            'support' => array(
                'url' => 'https://labs.tungchi.jp/shinseiflow/support/',
                'icon' => 'dashicons-sos',
                'title' => __('Support', 'shinseiflow-application-review'),
                'description' => __('Find help with setup and everyday use.', 'shinseiflow-application-review'),
            ),
            'issues' => array(
                'url' => 'https://labs.tungchi.jp/shinseiflow/issues/',
                'icon' => 'dashicons-warning',
                'title' => __('Report an Issue', 'shinseiflow-application-review'),
                'description' => __('Share a bug report or improvement suggestion.', 'shinseiflow-application-review'),
            ),
            'development' => array(
                'url' => 'https://labs.tungchi.jp/support-development/',
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

            <section class="tcarm-about-section tcarm-about-section--plain" aria-labelledby="tcarm-about-resources-title">
                <div class="tcarm-about-section-heading">
                    <span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
                    <h2 id="tcarm-about-resources-title"><?php echo esc_html__('Resources', 'shinseiflow-application-review'); ?></h2>
                </div>
                <div class="tcarm-about-resource-grid">
                    <?php foreach (array('documentation', 'website', 'support', 'issues') as $key): ?>
                        <?php $resource = $links[$key]; ?>
                        <a class="tcarm-about-resource-card" href="<?php echo esc_url($resource['url']); ?>" target="_blank" rel="noopener noreferrer">
                            <span class="dashicons <?php echo esc_attr($resource['icon']); ?>" aria-hidden="true"></span>
                            <span class="tcarm-about-resource-content">
                                <strong><?php echo esc_html($resource['title']); ?></strong>
                                <span><?php echo esc_html($resource['description']); ?></span>
                            </span>
                            <span class="dashicons dashicons-external" aria-hidden="true"></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="tcarm-about-section tcarm-about-support" aria-labelledby="tcarm-about-support-title">
                <div class="tcarm-about-section-heading">
                    <span class="dashicons dashicons-heart" aria-hidden="true"></span>
                    <h2 id="tcarm-about-support-title"><?php echo esc_html__('Support Development', 'shinseiflow-application-review'); ?></h2>
                </div>
                <p><?php echo esc_html__('ShinseiFlow is developed and maintained as an independent open-source project.', 'shinseiflow-application-review'); ?></p>
                <p><?php echo esc_html__('If ShinseiFlow has been helpful to you, you can support its continued development.', 'shinseiflow-application-review'); ?></p>
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
