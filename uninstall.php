<?php
/**
 * Plugin uninstall cleanup.
 *
 * Data is removed only when the administrator explicitly enabled the
 * uninstall cleanup setting before deleting the plugin.
 *
 * @package Application_Review_Manager
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$tcarm_settings = get_option('tcarm_settings', array());
if (!is_array($tcarm_settings) || empty($tcarm_settings['delete_data_on_uninstall']) || (string) $tcarm_settings['delete_data_on_uninstall'] !== '1') {
    return;
}

$tcarm_preserved_settings = array();
foreach ($tcarm_settings as $tcarm_setting_key => $tcarm_setting_value) {
    if (strpos($tcarm_setting_key, 'email_subject_') === 0 || strpos($tcarm_setting_key, 'email_body_') === 0) {
        $tcarm_preserved_settings[$tcarm_setting_key] = $tcarm_setting_value;
    }
}

if (!empty($tcarm_preserved_settings)) {
    update_option('tcarm_settings', $tcarm_preserved_settings, false);
} else {
    delete_option('tcarm_settings');
}

foreach (array(
    'tcarm_form_fields',
    'tcarm_form_sections',
    'tcarm_translation_strings',
    'tcarm_category_color_rules',
    'tcarm_db_version',
) as $tcarm_option_name) {
    delete_option($tcarm_option_name);
}

foreach (array(
    'tcarm_lookup_token_',
    'tcarm_last_errors_',
    'tcarm_edit_errors_',
    'tcarm_admin_edit_errors_',
    'tcarm_turnstile_verified_',
    'tcarm_rl_',
) as $tcarm_transient_prefix) {
    $tcarm_transient_like = $wpdb->esc_like('_transient_' . $tcarm_transient_prefix) . '%';
    $tcarm_timeout_like   = $wpdb->esc_like('_transient_timeout_' . $tcarm_transient_prefix) . '%';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Optional uninstall cleanup removes plugin-owned transient records only when data deletion is enabled.
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $tcarm_transient_like,
            $tcarm_timeout_like
        )
    );
}

foreach (array(
    $wpdb->prefix . 'tcarm_applications',
    $wpdb->prefix . 'tcarm_blocked_submissions',
) as $tcarm_table_name) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Optional uninstall cleanup removes plugin-owned custom tables only when data deletion is enabled.
    $wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($tcarm_table_name) . '`');
}
