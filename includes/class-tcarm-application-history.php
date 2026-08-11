<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Application_History_Trait {
    private function application_history_labels() {
        return array(
            'application_received' => 'Application received',
            'applicant_auto_reply_sent' => 'Applicant auto-reply email sent',
            'admin_notification_sent' => 'Admin notification email sent',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'needs_more' => 'Additional Information Requested',
            'resubmitted' => 'Resubmitted',
            'published_related_info' => 'Legacy extension action',
            'file_downloaded' => 'File downloaded',
            'moved_to_deleted' => 'Moved to deleted applications',
            'restored_from_deleted' => 'Restored from deleted applications',
        );
    }

    private function get_history_actor_label($actor = '') {
        $actor = trim((string) $actor);
        if ($actor !== '') {
            return $actor;
        }
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if ($user && $user->exists()) {
                return $user->display_name ? $user->display_name : $user->user_login;
            }
        }
        return '';
    }

    private function get_application_history($item) {
        $history = array();
        if ($item && isset($item->history_json) && !empty($item->history_json)) {
            $decoded = json_decode($item->history_json, true);
            if (is_array($decoded)) {
                $history = $decoded;
            }
        }
        $labels = $this->application_history_labels();
        $normalized = array();
        $has_received = false;
        foreach ($history as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $key = isset($entry['key']) ? sanitize_key($entry['key']) : '';
            $datetime = isset($entry['datetime']) ? sanitize_text_field($entry['datetime']) : '';
            $content = isset($entry['content']) ? sanitize_text_field($entry['content']) : '';
            $actor = isset($entry['actor']) ? sanitize_text_field($entry['actor']) : '';
            if ($content === '' && $key !== '' && isset($labels[$key])) {
                $content = $labels[$key];
            }
            if ($key === 'application_received') {
                $has_received = true;
            }
            if ($datetime === '' || $content === '') {
                continue;
            }
            $normalized[] = array(
                'key' => $key,
                'datetime' => $datetime,
                'content' => $content,
                'actor' => $actor,
            );
        }
        if (!$has_received && $item && !empty($item->created_at)) {
            array_unshift($normalized, array(
                'key' => 'application_received',
                'datetime' => $item->created_at,
                'content' => 'Application received',
                'actor' => 'Applicant',
            ));
        }
        usort($normalized, function($a, $b) {
            return strcmp($a['datetime'], $b['datetime']);
        });
        return $normalized;
    }

    private function append_application_history($application_id, $key, $actor = '') {
        $application_id = absint($application_id);
        if (!$application_id) {
            return;
        }
        $labels = $this->application_history_labels();
        if (!isset($labels[$key])) {
            return;
        }
        $item = $this->get_application($application_id);
        $history = array();
        if ($item && isset($item->history_json) && !empty($item->history_json)) {
            $decoded = json_decode($item->history_json, true);
            if (is_array($decoded)) {
                $history = $decoded;
            }
        }
        $history[] = array(
            'key' => $key,
            'datetime' => current_time('mysql'),
            'content' => $labels[$key],
            'actor' => $this->get_history_actor_label($actor),
        );
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table history update; WordPress core APIs do not apply.
        $wpdb->update(self::table_name(), array(
            'history_json' => wp_json_encode($history),
        ), array('id' => $application_id), array('%s'), array('%d'));
        self::flush_application_cache();
    }

    private function append_application_history_entry($application_id, $key, $content, $actor = '') {
        $application_id = absint($application_id);
        $content = sanitize_text_field((string) $content);
        if (!$application_id || $content === '') {
            return;
        }
        $item = $this->get_application($application_id);
        $history = array();
        if ($item && isset($item->history_json) && !empty($item->history_json)) {
            $decoded = json_decode($item->history_json, true);
            if (is_array($decoded)) {
                $history = $decoded;
            }
        }
        $history[] = array(
            'key' => sanitize_key($key),
            'datetime' => current_time('mysql'),
            'content' => $content,
            'actor' => $this->get_history_actor_label($actor),
        );
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table history update; WordPress core APIs do not apply.
        $wpdb->update(self::table_name(), array(
            'history_json' => wp_json_encode($history),
        ), array('id' => $application_id), array('%s'), array('%d'));
        self::flush_application_cache();
    }

    private function render_application_history_timeline($item) {
        $history = $this->get_application_history($item);
        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-history-card tcarm-admin-card">
            <div class="tcarm-panel-header"><h2 class="tcarm-admin-card-title"><?php echo esc_html__('Submission and Response History', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Review the history of application receipt, notifications, review actions, and public integrations.', 'shinseiflow-application-review'); ?></p></div>
            <div class="tcarm-detail-side-inner">
                <?php if (empty($history)): ?>
                    <p class="tcarm-history-empty"><?php echo esc_html__('There is no history yet.', 'shinseiflow-application-review'); ?></p>
                <?php else: ?>
                    <div class="tcarm-history-timeline">
                        <?php foreach ($history as $entry): ?>
                            <div class="tcarm-history-item">
                                <span class="tcarm-history-dot" aria-hidden="true"></span>
                                <div class="tcarm-history-date"><?php echo esc_html($entry['datetime']); ?></div>
                                <div class="tcarm-history-content">
                                    <?php echo esc_html($entry['content']); ?>
                                    <?php if (!empty($entry['actor'])): ?>
                                        <span class="tcarm-history-actor"><?php echo esc_html__('Actor: ', 'shinseiflow-application-review'); ?><?php echo esc_html($entry['actor']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
