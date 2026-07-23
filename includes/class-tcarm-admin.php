<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Trait {
    private function current_user_can_manage_tcarm() {
        return current_user_can(self::CAPABILITY) || current_user_can('manage_options');
    }

    public function filter_settings_option_page_capability($capability) {
        return self::CAPABILITY;
    }

    public static function role_has_tcarm_capability($role_key) {
        if (!function_exists('get_role')) {
            return false;
        }
        $role = get_role(sanitize_key($role_key));
        return $role && !empty($role->capabilities[self::CAPABILITY]);
    }

    public static function normalize_tcarm_allowed_roles($roles) {
        $available_roles = self::tcarm_available_role_keys();
        $out = array('administrator');
        foreach ((array) $roles as $role_key) {
            $role_key = sanitize_key($role_key);
            if ($role_key && isset($available_roles[$role_key])) {
                $out[] = $role_key;
            }
        }
        return array_values(array_unique($out));
    }

    public static function apply_tcarm_role_capabilities($allowed_roles = null) {
        if (!function_exists('wp_roles')) {
            return;
        }

        if ($allowed_roles === null) {
            $settings = get_option(self::OPTION_SETTINGS, array());
            $allowed_roles = isset($settings['allowed_roles']) ? $settings['allowed_roles'] : array('administrator');
        }

        $allowed_roles = array_fill_keys(self::normalize_tcarm_allowed_roles($allowed_roles), true);
        $allowed_roles['administrator'] = true;
        $wp_roles = wp_roles();
        if (empty($wp_roles->roles) || !is_array($wp_roles->roles)) {
            return;
        }

        foreach (array_keys($wp_roles->roles) as $role_key) {
            $role = $wp_roles->get_role($role_key);
            if (!$role) {
                continue;
            }
            if (isset($allowed_roles[$role_key])) {
                $role->add_cap(self::CAPABILITY);
            } elseif ($role_key !== 'administrator') {
                $role->remove_cap(self::CAPABILITY);
            }
        }
    }

    private static function tcarm_available_role_keys() {
        if (!function_exists('wp_roles')) {
            return array('administrator' => true);
        }
        $wp_roles = wp_roles();
        $roles = !empty($wp_roles->roles) && is_array($wp_roles->roles) ? $wp_roles->roles : array();
        if (!$roles) {
            return array('administrator' => true);
        }
        return array_fill_keys(array_keys($roles), true);
    }

    public function register_admin_menu() {
        add_menu_page(__('Application Management', 'shinseiflow-application-review'), __('Application Management', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_dashboard', array($this, 'render_dashboard'), 'dashicons-forms', 26);
        add_submenu_page('tcarm_dashboard', __('Home', 'shinseiflow-application-review'), __('Home', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_dashboard', array($this, 'render_dashboard'));
        add_submenu_page('tcarm_dashboard', __('Applications', 'shinseiflow-application-review'), __('Applications', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_applications', array($this, 'render_applications'));
        add_submenu_page('tcarm_dashboard', __('Form Settings', 'shinseiflow-application-review'), __('Form Settings', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_form_settings', array($this, 'render_form_settings'));
        add_submenu_page('tcarm_dashboard', __('Download File Settings', 'shinseiflow-application-review'), __('Download File Settings', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_download_files_settings', array($this, 'render_download_files_settings_page'));
        add_submenu_page('tcarm_dashboard', __('Notification Email Settings', 'shinseiflow-application-review'), __('Notification Email Settings', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_mail_settings', array($this, 'render_mail_settings_page'));
        add_submenu_page('tcarm_dashboard', __('Security Settings', 'shinseiflow-application-review'), __('Security Settings', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_security_settings', array($this, 'render_security_settings_page'));
        add_submenu_page('tcarm_dashboard', __('General Settings', 'shinseiflow-application-review'), __('General Settings', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_settings', array($this, 'render_settings'));
        add_submenu_page('tcarm_dashboard', __('Display Customization', 'shinseiflow-application-review'), __('Display Customization', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_display_customize', array($this, 'render_display_customize_settings_page'));
        add_submenu_page('tcarm_dashboard', __('Multilingual Settings', 'shinseiflow-application-review'), __('Multilingual Settings', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_translation_settings', array($this, 'render_translation_settings_page'));
        add_submenu_page('tcarm_dashboard', __('Privacy and Data Retention', 'shinseiflow-application-review'), __('Privacy and Data Retention', 'shinseiflow-application-review'), self::CAPABILITY, 'tcarm_privacy_settings', array($this, 'render_privacy_settings_page'));
        add_submenu_page('tcarm_dashboard', __('About', 'shinseiflow-application-review'), __('About', 'shinseiflow-application-review'), self::CAPABILITY, 'shinseiflow-about', array($this, 'render_about_page'));
    }

    private function admin_page_title($title, $description = '') {
        ob_start();
        ?>
        <div class="tcarm-page-header">
            <div>
                <h2><?php echo esc_html($title); ?></h2>
                <?php if ($description): ?><p><?php echo esc_html($description); ?></p><?php endif; ?>
            </div>
            <span class="tcarm-version-pill">v<?php echo esc_html(self::VERSION); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    private function admin_tabs() {
        $tabs = array(
            'tcarm_dashboard' => __('Home', 'shinseiflow-application-review'),
            'tcarm_applications' => __('Applications', 'shinseiflow-application-review'),
            'tcarm_form_settings' => __('Form Settings', 'shinseiflow-application-review'),
            'tcarm_download_files_settings' => __('Download File Settings', 'shinseiflow-application-review'),
            'tcarm_mail_settings' => __('Notification Email Settings', 'shinseiflow-application-review'),
            'tcarm_security_settings' => __('Security Settings', 'shinseiflow-application-review'),
            'tcarm_settings' => __('General Settings', 'shinseiflow-application-review'),
            'tcarm_display_customize' => __('Display Customization', 'shinseiflow-application-review'),
            'tcarm_translation_settings' => __('Multilingual Settings', 'shinseiflow-application-review'),
            'tcarm_privacy_settings' => __('Privacy and Data Retention', 'shinseiflow-application-review'),
            'shinseiflow-about' => __('About', 'shinseiflow-application-review'),
        );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin tab state; value is sanitized and allowlisted against registered tabs.
        $current = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'tcarm_dashboard';
        if (!isset($tabs[$current])) {
            $current = 'tcarm_dashboard';
        }
        ob_start();
        ?>
        <nav class="nav-tab-wrapper tcarm-nav-tabs" aria-label="<?php echo esc_attr__('ShinseiFlow Admin Menu', 'shinseiflow-application-review'); ?>">
            <?php foreach ($tabs as $slug => $label): ?>
                <?php $tab_class = 'nav-tab' . ($current === $slug ? ' nav-tab-active' : ''); ?>
                <a class="<?php echo esc_attr($tab_class); ?>" href="<?php echo esc_url(admin_url('admin.php?page=' . $slug)); ?>"<?php if ($current === $slug): ?> aria-current="<?php echo esc_attr('page'); ?>"<?php endif; ?>><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php
        return ob_get_clean();
    }

    private function open_admin_wrap($title = '') {
        echo '<div class="wrap tcarm-wrap tcarm-admin-page">';
        echo '<h1 class="tcarm-admin-title">' . esc_html__('ShinseiFlow', 'shinseiflow-application-review') . ' v' . esc_html(self::VERSION) . '</h1>';
        echo wp_kses($this->admin_tabs(), $this->admin_tabs_allowed_html());
        if ($title) {
            echo wp_kses($this->admin_page_title($title), $this->admin_page_title_allowed_html());
        }
    }

    private function admin_tabs_allowed_html() {
        return array(
            'nav' => array(
                'class' => true,
                'aria-label' => true,
            ),
            'a' => array(
                'class' => true,
                'href' => true,
                'aria-current' => true,
            ),
        );
    }

    private function admin_page_title_allowed_html() {
        return array(
            'div' => array(
                'class' => true,
            ),
            'h2' => array(),
            'p' => array(),
            'span' => array(
                'class' => true,
            ),
        );
    }

    private function close_admin_wrap() {
        echo '</div>';
    }
}
