<?php
/**
 * Plugin Name: ShinseiFlow – Application Review & Approval Workflow
 * Description: Create application forms, review submissions, manage approval workflows, and notify applicants from WordPress.
 * Version: 0.4.3.34
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Casper Yeh
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: shinseiflow-application-review
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TCARM_PLUGIN_FILE', __FILE__);
define('TCARM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TCARM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-plugin.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-admin.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-assets.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-settings.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-translations.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-ai-translation.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-application-number.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-application-history.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-shortcodes.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-applications.php';
require_once TCARM_PLUGIN_DIR . 'includes/class-tcarm-notifications.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-dashboard.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-form-settings.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-notifications.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-translation.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-download-files.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-security.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-redirects.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-privacy.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-application-detail.php';
require_once TCARM_PLUGIN_DIR . 'includes/admin-pages/page-about.php';

final class TCARM_Plugin {
    const VERSION = '0.4.3.34';
    const DB_VERSION = '0.1.61';
    const CAPABILITY = 'manage_tcarm_applications';
    const OPTION_SETTINGS = 'tcarm_settings';
    const OPTION_FIELDS = 'tcarm_form_fields';
    const OPTION_SECTIONS = 'tcarm_form_sections';
    const OPTION_TRANSLATIONS = 'tcarm_translation_strings';
    const TABLE = 'tcarm_applications';
    const BLOCKED_TABLE = 'tcarm_blocked_submissions';
    const PENDING_UPLOAD_CLEANUP_HOOK = 'tcarm_cleanup_pending_uploads';

    private static $instance = null;
    private $current_tcarm_upload_subdir = '';
    private $tcarm_mail_in_progress = false;
    private $current_frontend_lang = '';
    private $current_frontend_show_steps = true;

    use TCARM_Plugin_Core_Trait;
    use TCARM_Admin_Trait;
    use TCARM_Assets_Trait;
    use TCARM_Settings_Trait;
    use TCARM_Translations_Trait;
    use TCARM_AI_Translation_Trait;
    use TCARM_Application_Number_Trait;
    use TCARM_Application_History_Trait;
    use TCARM_Shortcodes_Trait;
    use TCARM_Applications_Trait;
    use TCARM_Notifications_Trait;
    use TCARM_Admin_Page_Dashboard_Trait;
    use TCARM_Admin_Page_Form_Settings_Trait;
    use TCARM_Admin_Page_Notifications_Trait;
    use TCARM_Admin_Page_Translation_Trait;
    use TCARM_Admin_Page_Download_Files_Trait;
    use TCARM_Admin_Page_Security_Trait;
    use TCARM_Admin_Page_Redirects_Trait;
    use TCARM_Admin_Page_Privacy_Trait;
    use TCARM_Admin_Page_Applications_Trait;
    use TCARM_Admin_Page_About_Trait;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'handle_frontend_submit'));
        add_action('init', array(__CLASS__, 'schedule_pending_upload_cleanup'));
        add_action(self::PENDING_UPLOAD_CLEANUP_HOOK, array($this, 'cleanup_stale_pending_uploads'));
        add_action('template_redirect', array($this, 'handle_file_download'), 0);
        add_action('template_redirect', array($this, 'handle_frontend_lookup_redirect'), 1);
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'maybe_upgrade'));
        add_filter('option_page_capability_tcarm_settings_group', array($this, 'filter_settings_option_page_capability'));
        add_filter('option_page_capability_tcarm_fields_group', array($this, 'filter_settings_option_page_capability'));
        add_filter('option_page_capability_tcarm_translation_group', array($this, 'filter_settings_option_page_capability'));
        add_action('admin_post_tcarm_update_status', array($this, 'handle_admin_status'));
        add_action('admin_post_tcarm_admin_update_application_content', array($this, 'handle_admin_edit_application_content'));
        add_action('admin_post_tcarm_delete_blocked_log', array($this, 'handle_delete_blocked_log'));
        add_action('admin_post_tcarm_bulk_delete_applications', array($this, 'handle_bulk_delete_applications'));
        add_action('admin_post_tcarm_restore_application', array($this, 'handle_restore_application'));
        add_action('admin_post_tcarm_permanently_delete_application', array($this, 'handle_permanently_delete_application'));
        add_action('admin_post_tcarm_bulk_permanently_delete_applications', array($this, 'handle_bulk_permanently_delete_applications'));
        add_action('admin_post_tcarm_resend_email', array($this, 'handle_resend_email'));
        add_action('admin_post_tcarm_send_test_email', array($this, 'handle_send_test_email'));
        add_action('wp_ajax_tcarm_ai_translate_strings', array($this, 'ajax_ai_translate_strings'));
        add_action('phpmailer_init', array($this, 'configure_phpmailer_for_tcarm_mail'));
        add_shortcode('tcarm_form', array($this, 'shortcode_application_form'));
        add_shortcode('tcarm_status', array($this, 'shortcode_application_status'));
        add_shortcode('tcarm_view', array($this, 'shortcode_application_view'));
        add_shortcode('tcarm_edit', array($this, 'shortcode_application_edit'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
    }

    public static function plugin_dir() {
        return TCARM_PLUGIN_DIR;
    }

    public static function plugin_url() {
        return TCARM_PLUGIN_URL;
    }
}

register_activation_hook(__FILE__, array('TCARM_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('TCARM_Plugin', 'deactivate'));
add_action('plugins_loaded', array('TCARM_Plugin', 'instance'));
