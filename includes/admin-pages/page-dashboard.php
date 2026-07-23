<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Dashboard_Trait {
    public function render_dashboard() {
        $counts = $this->get_counts();
        $this->open_admin_wrap(__('Home', 'shinseiflow-application-review'));
        ?>
            <div class="tcarm-hero tcarm-hero--simple">
                <div>
                    <p class="tcarm-eyebrow"><?php echo esc_html__('ShinseiFlow', 'shinseiflow-application-review'); ?></p>
                    <h2><?php echo esc_html__('Application Review & Approval Workflow', 'shinseiflow-application-review'); ?></h2>
                    <p><?php echo esc_html__('A WordPress plugin for managing application submissions, review workflows, notifications, and applicant communication.', 'shinseiflow-application-review'); ?></p>
                    <div class="tcarm-hero-actions">
                        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=tcarm_applications')); ?>"><?php echo esc_html__('View Applications', 'shinseiflow-application-review'); ?></a>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tcarm_form_settings')); ?>"><?php echo esc_html__('Go to Form Settings', 'shinseiflow-application-review'); ?></a>
                    </div>
                </div>
            </div>
            <div class="tcarm-cards">
                <div class="tcarm-card"><strong><?php echo esc_html($counts['total']); ?></strong><span><?php echo esc_html__('Applications', 'shinseiflow-application-review'); ?></span></div>
                <div class="tcarm-card"><strong><?php echo esc_html($counts['pending']); ?></strong><span><?php echo esc_html__('Pending Review', 'shinseiflow-application-review'); ?></span></div>
                <div class="tcarm-card"><strong><?php echo esc_html($counts['approved']); ?></strong><span><?php echo esc_html__('Approved', 'shinseiflow-application-review'); ?></span></div>
            </div>
            <div class="tcarm-dashboard-main">
                <div class="tcarm-panel tcarm-card-panel">
                    <div class="tcarm-panel-header"><h2><?php echo esc_html__('Frontend Page Setup Status', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Review shortcode pages and destination URLs.', 'shinseiflow-application-review'); ?></p></div>
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Status table markup escapes labels, shortcodes, and URLs before output.
                    echo $this->render_frontend_page_status_table();
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_frontend_page_status_table() {
        $rows = array(
            array(__('Application Form Page', 'shinseiflow-application-review'), 'form', '[tcarm_form lang="ja" show_steps="yes"]'),
            array(__('Application Status Page', 'shinseiflow-application-review'), 'status', '[tcarm_status]'),
            array(__('Application Review Page', 'shinseiflow-application-review'), 'view', '[tcarm_view]'),
            array(__('Edit and Resubmit Page', 'shinseiflow-application-review'), 'edit', '[tcarm_edit]'),
            array(__('Top Page', 'shinseiflow-application-review'), 'top', __('Back to Top link', 'shinseiflow-application-review')),
        );
        ob_start();
        ?>
        <table class="widefat striped tcarm-page-status-table">
            <thead><tr class="tcarm-status-detail-item"><th><?php echo esc_html__('Item', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Shortcode / Purpose', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Current URL', 'shinseiflow-application-review'); ?></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): $url = $this->get_frontend_page_url($row[1], false); ?>
                <tr>
                    <td><?php echo esc_html($row[0]); ?></td>
                    <td><code><?php echo esc_html($row[2]); ?></code></td>
                    <td>
                        <?php if ($url): ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php echo esc_html($url); ?></a>
                        <?php else: ?>
                            <span class="tcarm-muted"><?php echo esc_html__('Not set', 'shinseiflow-application-review'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    private function admin_icon_svg($type) {
        if ($type === 'edit') {
            $svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 19h1.4l9.9-9.9-1.4-1.4L5 17.6V19Zm-2 2v-4.25L16.3 3.45c.2-.2.42-.34.66-.44.24-.1.49-.15.74-.15.27 0 .53.05.78.16.25.11.47.26.66.45l1.41 1.42c.2.19.35.41.45.66.1.25.15.5.15.76s-.05.51-.15.75c-.1.24-.25.46-.45.66L7.25 21H3Z"/></svg>';
            return wp_kses($svg, $this->admin_icon_svg_allowed_tags());
        }
        $svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 21c-.55 0-1.02-.2-1.41-.59C5.2 20.02 5 19.55 5 19V6H4V4h5V3h6v1h5v2h-1v13c0 .55-.2 1.02-.59 1.41-.39.39-.86.59-1.41.59H7ZM17 6H7v13h10V6ZM9 17h2V8H9v9Zm4 0h2V8h-2v9Z"/></svg>';
        return wp_kses($svg, $this->admin_icon_svg_allowed_tags());
    }

    private function admin_icon_svg_allowed_tags() {
        return array(
            'svg' => array(
                'viewBox' => true,
                'viewbox' => true,
                'aria-hidden' => true,
                'focusable' => true,
            ),
            'path' => array(
                'd' => true,
            ),
        );
    }
}
