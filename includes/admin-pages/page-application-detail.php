<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Admin_Page_Applications_Trait {
    public function render_applications() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin display state; value is sanitized and allowlisted before use.
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        if (!in_array($action, array('', 'view'), true)) {
            $action = '';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin detail id; value is absint sanitized and does not modify data.
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        if ($action === 'view' && $id) {
            $this->render_application_detail($id);
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter; value is sanitized and allowlisted before use.
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        if ($status !== '' && !array_key_exists($status, self::statuses())) {
            $status = '';
        }
        $items = $this->get_applications($status);
        $deleted_items = $this->get_deleted_applications(50);
        $this->open_admin_wrap(__('Applications', 'shinseiflow-application-review'));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect notice state; value is sanitized and allowlisted before display.
        $notice = isset($_GET['tcarm_notice']) ? sanitize_key(wp_unslash($_GET['tcarm_notice'])) : '';
        if (!in_array($notice, array('', 'deleted', 'restored', 'permanently_deleted', 'permanent_delete_failed', 'permanent_delete_not_deleted', 'bulk_permanent_delete_none', 'bulk_permanent_delete_success', 'bulk_permanent_delete_partial', 'none'), true)) {
            $notice = '';
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect count for notice display; value is absint sanitized.
        $deleted_count = isset($_GET['deleted_count']) ? absint(wp_unslash($_GET['deleted_count'])) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect count for notice display; value is absint sanitized.
        $failed_count = isset($_GET['failed_count']) ? absint(wp_unslash($_GET['failed_count'])) : 0;
        if ($notice === 'deleted') {
            echo wp_kses_post('<div class="notice notice-success is-dismissible"><p>' . esc_html__('The selected applications were moved to deleted items.', 'shinseiflow-application-review') . '</p></div>');
        } elseif ($notice === 'restored') {
            echo wp_kses_post('<div class="notice notice-success is-dismissible"><p>' . esc_html__('The application was restored from deleted items.', 'shinseiflow-application-review') . '</p></div>');
        } elseif ($notice === 'permanently_deleted') {
            echo wp_kses_post('<div class="notice notice-success is-dismissible"><p>' . esc_html__('The application was permanently deleted.', 'shinseiflow-application-review') . '</p></div>');
        } elseif ($notice === 'permanent_delete_failed') {
            echo wp_kses_post('<div class="notice notice-error is-dismissible"><p>' . esc_html__('The application could not be permanently deleted.', 'shinseiflow-application-review') . '</p></div>');
        } elseif ($notice === 'permanent_delete_not_deleted') {
            echo wp_kses_post('<div class="notice notice-error is-dismissible"><p>' . esc_html__('Only deleted applications can be permanently deleted.', 'shinseiflow-application-review') . '</p></div>');
        } elseif ($notice === 'bulk_permanent_delete_none') {
            echo wp_kses_post('<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Please select applications to permanently delete.', 'shinseiflow-application-review') . '</p></div>');
        } elseif ($notice === 'bulk_permanent_delete_success') {
            /* translators: %d: number of deleted applications. */
            echo wp_kses_post('<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('%d applications were permanently deleted.', 'shinseiflow-application-review'), $deleted_count)) . '</p></div>');
        } elseif ($notice === 'bulk_permanent_delete_partial') {
            /* translators: 1: number of deleted applications, 2: number of applications that could not be deleted. */
            echo wp_kses_post('<div class="notice notice-warning is-dismissible"><p>' . esc_html(sprintf(__('%1$d applications were permanently deleted. %2$d could not be deleted.', 'shinseiflow-application-review'), $deleted_count, $failed_count)) . '</p></div>');
        } elseif ($notice === 'none') {
            echo wp_kses_post('<div class="notice notice-warning is-dismissible"><p>' . esc_html__('No target applications were selected.', 'shinseiflow-application-review') . '</p></div>');
        }
        ?>
            <div class="tcarm-panel tcarm-card-panel">
                <div class="tcarm-panel-header"><h2><?php echo esc_html__('Applications', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Review submitted applications and manage review workflows.', 'shinseiflow-application-review'); ?></p></div>
                <form id="tcarm-bulk-delete-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('tcarm_bulk_delete_applications', 'tcarm_bulk_delete_nonce'); ?>
                    <input type="hidden" name="action" value="tcarm_bulk_delete_applications">
                    <div class="tcarm-application-list-toolbar">
                        <ul class="subsubsub">
                            <li><a href="<?php echo esc_url(admin_url('admin.php?page=tcarm_applications')); ?>"><?php echo esc_html__('All', 'shinseiflow-application-review'); ?></a> | </li>
                            <?php foreach (self::review_statuses() as $key => $label): ?>
                                <li><a href="<?php echo esc_url(admin_url('admin.php?page=tcarm_applications&status=' . $key)); ?>"><?php echo esc_html($label); ?></a> | </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="tcarm-bulk-actions">
                            <button type="button" class="button tcarm-bulk-delete-button" id="tcarm-open-delete-modal"><?php echo esc_html__('Delete Application', 'shinseiflow-application-review'); ?></button>
                        </div>
                    </div>
                    <div class="tcarm-table-scroll"><table class="widefat striped tcarm-table tcarm-applications-table">
                    <thead><tr class="tcarm-status-detail-item"><th class="check-column"><input type="checkbox" id="tcarm-select-all-applications"></th><th class="tcarm-col-created"><?php echo esc_html__('Application Date', 'shinseiflow-application-review'); ?></th><th class="tcarm-col-code"><?php echo esc_html__('Application Number', 'shinseiflow-application-review'); ?></th><th class="tcarm-col-event"><?php echo esc_html__('Event Name', 'shinseiflow-application-review'); ?></th><th class="tcarm-col-applicant"><?php echo esc_html__('Applicant', 'shinseiflow-application-review'); ?></th><th class="tcarm-col-email"><?php echo esc_html__('Email', 'shinseiflow-application-review'); ?></th><th class="tcarm-col-status"><?php echo esc_html__('Status', 'shinseiflow-application-review'); ?></th><th class="tcarm-col-resubmit"><?php echo esc_html__('Edit', 'shinseiflow-application-review'); ?></th><th class="tcarm-col-action"><?php echo esc_html__('Actions', 'shinseiflow-application-review'); ?></th></tr></thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="9"><?php echo esc_html__('There is no application data yet.', 'shinseiflow-application-review'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <th class="check-column"><input type="checkbox" class="tcarm-application-checkbox" name="application_ids[]" value="<?php echo esc_attr(absint($item->id)); ?>"></th>
                            <td class="tcarm-col-created"><?php echo esc_html($item->created_at); ?></td>
                            <td class="tcarm-col-code"><code><?php echo esc_html($item->application_code); ?></code></td>
                            <td class="tcarm-col-event"><?php echo esc_html($item->event_title); ?></td>
                            <td class="tcarm-col-applicant"><?php echo esc_html($item->applicant_name); ?></td>
                            <td class="tcarm-col-email"><?php echo esc_html($item->contact_email); ?></td>
                            <td class="tcarm-col-status"><span class="tcarm-status tcarm-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html(self::status_label($item->status)); ?></span></td>
                            <td class="tcarm-col-resubmit">
                                <?php if (!empty($item->resubmit_count)): ?>
                                    <?php /* translators: %d: resubmission count. */ echo esc_html(sprintf(__('%d times', 'shinseiflow-application-review'), absint($item->resubmit_count))); ?>
                                <?php else: ?>
                                    <?php echo esc_html('—'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="tcarm-col-action"><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tcarm_applications&action=view&id=' . absint($item->id))); ?>"><?php echo esc_html__('Details', 'shinseiflow-application-review'); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                </form>
            </div>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Modal markup is generated internally with escaped text and fixed attributes.
            echo $this->render_delete_confirm_modal();
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Modal markup is generated internally with escaped text and fixed attributes.
            echo $this->render_restore_confirm_modal();
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Modal markup is generated internally with escaped text and fixed attributes.
            echo $this->render_permanent_delete_confirm_modal();
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Modal markup is generated internally with escaped text and fixed attributes.
            echo $this->render_bulk_permanent_delete_confirm_modal();
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Blocked log table markup escapes dynamic values before output.
            echo $this->render_blocked_logs_card();
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Deleted applications table markup escapes dynamic values before output.
            echo $this->render_deleted_applications_card($deleted_items);
            ?>
        </div>
        <?php
    }

    private function render_delete_confirm_modal() {
        wp_enqueue_script('tcarm-admin-application-list-actions', self::plugin_url() . 'assets/js/admin-application-list-actions.js', array(), self::VERSION, true);
        $application_list_i18n = array(
            'applicationDeleteNone' => __('Please select applications to delete.', 'shinseiflow-application-review'),
            'bulkPermanentDeleteNone' => __('Please select applications to permanently delete.', 'shinseiflow-application-review'),
        );
        wp_add_inline_script(
            'tcarm-admin-application-list-actions',
            'window.tcarmAdminI18n = Object.assign({}, window.tcarmAdminI18n || {}, ' . wp_json_encode($application_list_i18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');',
            'before'
        );
        ob_start();
        ?>
        <div class="tcarm-confirm-modal" id="tcarm-delete-confirm-modal" aria-hidden="true" style="display:none;">
            <div class="tcarm-confirm-modal__backdrop" data-tcarm-modal-close="delete"></div>
            <div class="tcarm-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tcarm-delete-confirm-title">
                <h2 id="tcarm-delete-confirm-title"><?php echo esc_html__('Delete this application?', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('The selected applications will be moved to deleted items.', 'shinseiflow-application-review'); ?><br><?php echo esc_html__('Deleted applications can be restored later. This action does not permanently delete application data.', 'shinseiflow-application-review'); ?></p>
                <div class="tcarm-confirm-modal__actions">
                    <button type="button" class="button" data-tcarm-modal-close="delete"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></button>
                    <button type="button" class="button button-primary" id="tcarm-confirm-delete-submit"><?php echo esc_html__('Delete', 'shinseiflow-application-review'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_restore_confirm_modal() {
        ob_start();
        ?>
        <div class="tcarm-confirm-modal" id="tcarm-restore-confirm-modal" aria-hidden="true" style="display:none;">
            <div class="tcarm-confirm-modal__backdrop" data-tcarm-modal-close="restore"></div>
            <div class="tcarm-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tcarm-restore-confirm-title">
                <h2 id="tcarm-restore-confirm-title"><?php echo esc_html__('Restore this application?', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('Restore this application from deleted items?', 'shinseiflow-application-review'); ?></p>
                <div class="tcarm-confirm-modal__actions">
                    <button type="button" class="button" data-tcarm-modal-close="restore"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></button>
                    <button type="button" class="button button-primary" id="tcarm-confirm-restore-submit"><?php echo esc_html__('Restore', 'shinseiflow-application-review'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_permanent_delete_confirm_modal() {
        ob_start();
        ?>
        <div class="tcarm-confirm-modal" id="tcarm-permanent-delete-confirm-modal" aria-hidden="true" style="display:none;">
            <div class="tcarm-confirm-modal__backdrop" data-tcarm-modal-close="permanent-delete"></div>
            <div class="tcarm-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tcarm-permanent-delete-confirm-title">
                <h2 id="tcarm-permanent-delete-confirm-title"><?php echo esc_html__('Permanently delete this application?', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('This application will be permanently deleted.', 'shinseiflow-application-review'); ?><br><?php echo esc_html__('This action cannot be undone.', 'shinseiflow-application-review'); ?><br><?php echo esc_html__('Do you want to continue?', 'shinseiflow-application-review'); ?></p>
                <div class="tcarm-confirm-modal__actions">
                    <button type="button" class="button" data-tcarm-modal-close="permanent-delete"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></button>
                    <button type="button" class="button button-primary" id="tcarm-confirm-permanent-delete-submit"><?php echo esc_html__('Permanently Delete', 'shinseiflow-application-review'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_bulk_permanent_delete_confirm_modal() {
        ob_start();
        ?>
        <div class="tcarm-confirm-modal" id="tcarm-bulk-permanent-delete-confirm-modal" aria-hidden="true" style="display:none;">
            <div class="tcarm-confirm-modal__backdrop" data-tcarm-modal-close="bulk-permanent-delete"></div>
            <div class="tcarm-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tcarm-bulk-permanent-delete-confirm-title">
                <h2 id="tcarm-bulk-permanent-delete-confirm-title"><?php echo esc_html__('Permanently delete the selected applications?', 'shinseiflow-application-review'); ?></h2>
                <p><?php echo esc_html__('The selected applications will be permanently deleted.', 'shinseiflow-application-review'); ?><br><?php echo esc_html__('This action cannot be undone.', 'shinseiflow-application-review'); ?><br><?php echo esc_html__('Do you want to continue?', 'shinseiflow-application-review'); ?></p>
                <div class="tcarm-confirm-modal__actions">
                    <button type="button" class="button" data-tcarm-modal-close="bulk-permanent-delete"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></button>
                    <button type="button" class="button button-primary" id="tcarm-confirm-bulk-permanent-delete-submit"><?php echo esc_html__('Permanently Delete', 'shinseiflow-application-review'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_deleted_applications_card($items) {
        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-deleted-applications-section">
            <div class="tcarm-panel-header">
                <div class="tcarm-panel-title-block">
                    <h2><?php echo esc_html__('Deleted Applications', 'shinseiflow-application-review'); ?></h2>
                    <p><?php echo esc_html__('Review and restore applications moved to deleted items. The data has not been permanently deleted.', 'shinseiflow-application-review'); ?></p>
                </div>
                <?php if (!empty($items)): ?>
                    <button type="button" class="button tcarm-bulk-permanent-delete-button" id="tcarm-open-bulk-permanent-delete-modal"><?php echo esc_html__('Permanently Delete', 'shinseiflow-application-review'); ?></button>
                <?php endif; ?>
            </div>
            <form id="tcarm-bulk-permanent-delete-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:none;">
                <?php wp_nonce_field('tcarm_bulk_permanently_delete_applications', 'tcarm_bulk_permanent_delete_nonce'); ?>
                <input type="hidden" name="action" value="tcarm_bulk_permanently_delete_applications">
            </form>
            <div class="tcarm-table-scroll"><table class="widefat striped tcarm-table tcarm-deleted-applications-table">
                <thead><tr class="tcarm-status-detail-item"><th class="check-column"><?php if (!empty($items)): ?><input type="checkbox" id="tcarm-select-all-deleted-applications"><?php endif; ?></th><th><?php echo esc_html__('Application Number', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Applicant', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Original Status', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Deleted At', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Deleted By', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Actions', 'shinseiflow-application-review'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7"><?php echo esc_html__('There are no deleted applications.', 'shinseiflow-application-review'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                    <?php
                    $restore_form_id = 'tcarm-restore-application-' . absint($item->id);
                    $permanent_delete_form_id = 'tcarm-permanent-delete-application-' . absint($item->id);
                    ?>
                    <tr>
                        <th class="check-column"><input type="checkbox" class="tcarm-deleted-application-checkbox" form="tcarm-bulk-permanent-delete-form" name="application_ids[]" value="<?php echo esc_attr(absint($item->id)); ?>"></th>
                        <td><code><?php echo esc_html($item->application_code); ?></code></td>
                        <td><?php echo esc_html($item->applicant_name); ?></td>
                        <td><span class="tcarm-status tcarm-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html(self::status_label($item->status)); ?></span></td>
                        <td><?php echo esc_html(!empty($item->deleted_at) ? $item->deleted_at : '—'); ?></td>
                        <td><?php echo esc_html($this->get_deleted_by_label($item)); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=tcarm_applications&action=view&id=' . absint($item->id))); ?>"><?php echo esc_html__('Details', 'shinseiflow-application-review'); ?></a>
                            <form id="<?php echo esc_attr($restore_form_id); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <?php wp_nonce_field('tcarm_restore_application_' . absint($item->id), 'tcarm_restore_nonce'); ?>
                                <input type="hidden" name="action" value="tcarm_restore_application">
                                <input type="hidden" name="id" value="<?php echo esc_attr(absint($item->id)); ?>">
                                <button type="button" class="button button-small tcarm-restore-application-button" data-form-id="<?php echo esc_attr($restore_form_id); ?>"><?php echo esc_html__('Restore', 'shinseiflow-application-review'); ?></button>
                            </form>
                            <form id="<?php echo esc_attr($permanent_delete_form_id); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                <?php wp_nonce_field('tcarm_permanently_delete_application_' . absint($item->id), 'tcarm_permanent_delete_nonce'); ?>
                                <input type="hidden" name="action" value="tcarm_permanently_delete_application">
                                <input type="hidden" name="id" value="<?php echo esc_attr(absint($item->id)); ?>">
                                <button type="button" class="button button-small tcarm-permanent-delete-application-button" data-form-id="<?php echo esc_attr($permanent_delete_form_id); ?>"><?php echo esc_html__('Permanently Delete', 'shinseiflow-application-review'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_blocked_logs_card() {
        $logs = $this->get_blocked_logs(20);
        $count7 = $this->get_blocked_count(7);
        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-blocked-panel">
            <div class="tcarm-panel-header"><h2><?php echo esc_html__('Blocked Submissions', 'shinseiflow-application-review'); ?></h2><p><?php /* translators: %d: number of blocked submissions in the past 7 days. */ echo esc_html(sprintf(__('Past 7 days: %d. Only minimal contact information is stored to help follow up on false positives.', 'shinseiflow-application-review'), $count7)); ?></p></div>
            <div class="tcarm-table-scroll"><table class="widefat striped tcarm-table tcarm-blocked-table">
                <thead><tr class="tcarm-status-detail-item"><th><?php echo esc_html__('Date and Time', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Type', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Reason', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Name', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Phone', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Email', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('IP', 'shinseiflow-application-review'); ?></th><th><?php echo esc_html__('Actions', 'shinseiflow-application-review'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8"><?php echo esc_html__('There is no blocked history yet.', 'shinseiflow-application-review'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log->created_at); ?></td>
                        <td><?php echo esc_html($log->event_type); ?></td>
                        <td><span class="tcarm-block-reason"><?php echo esc_html($log->reason_label); ?></span><br><code><?php echo esc_html($log->reason_key); ?></code></td>
                        <td><?php echo esc_html($log->applicant_name ?: '—'); ?></td>
                        <td><?php echo esc_html($log->contact_phone ?: '—'); ?></td>
                        <td><?php echo esc_html($log->contact_email ?: '—'); ?></td>
                        <td><?php echo esc_html($log->ip_address ?: '—'); ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tcarm_delete_blocked_log&id=' . absint($log->id)), 'tcarm_delete_blocked_log_' . absint($log->id))); ?>"><?php echo esc_html__('Delete', 'shinseiflow-application-review'); ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_application_detail($id) {
        $item = $this->get_application($id);
        if (!$item) {
            echo wp_kses_post('<div class="wrap"><h1>' . esc_html__('Application not found', 'shinseiflow-application-review') . '</h1></div>');
            return;
        }
        $fields = self::get_fields();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin detail display mode; value is sanitized and only toggles the edit form view.
        $is_edit_content = isset($_GET['edit_content']) && sanitize_text_field(wp_unslash($_GET['edit_content'])) === '1';
        /* translators: %s: application code. */
        $this->open_admin_wrap(sprintf(__('Application Details: %s', 'shinseiflow-application-review'), $item->application_code));
        $review_message = '';
        if ($item->status === 'rejected' && !empty($item->reject_reason)) {
            $review_message = $item->reject_reason;
        } elseif ($item->status === 'needs_more' && !empty($item->request_note)) {
            $review_message = $item->request_note;
        }
        ?>
            <p class="tcarm-back-link"><a href="<?php echo esc_url(admin_url('admin.php?page=tcarm_applications')); ?>"><?php echo esc_html__('← Back to Applications', 'shinseiflow-application-review'); ?></a></p>
            <?php if (!empty($item->deleted_at)): ?>
                <div class="notice notice-warning inline"><p><?php echo esc_html__('This application has been deleted.', 'shinseiflow-application-review'); ?></p></div>
            <?php endif; ?>
            <?php
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect notice state; value is sanitized and allowlisted before display.
            $resend_notice = isset($_GET['tcarm_resend']) ? sanitize_key(wp_unslash($_GET['tcarm_resend'])) : '';
            if (!in_array($resend_notice, array('', 'success', 'failed', 'invalid'), true)) {
                $resend_notice = '';
            }
            ?>
            <?php if ($resend_notice === 'success'): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('The notification email was resent.', 'shinseiflow-application-review'); ?></p></div>
            <?php elseif ($resend_notice === 'failed'): ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html__('Failed to resend the notification email. Please check the email settings.', 'shinseiflow-application-review'); ?></p></div>
            <?php elseif ($resend_notice === 'invalid'): ?>
                <div class="notice notice-warning is-dismissible"><p><?php echo esc_html__('The selected notification email cannot be resent in the current status.', 'shinseiflow-application-review'); ?></p></div>
            <?php endif; ?>
            <?php
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect notice state; value is sanitized and allowlisted before display.
            $content_edit_notice = isset($_GET['tcarm_content_edit']) ? sanitize_key(wp_unslash($_GET['tcarm_content_edit'])) : '';
            if (!in_array($content_edit_notice, array('', 'success', 'error'), true)) {
                $content_edit_notice = '';
            }
            ?>
            <?php if ($content_edit_notice === 'success'): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('The application content was updated.', 'shinseiflow-application-review'); ?></p></div>
            <?php elseif ($content_edit_notice === 'error'): ?>
                <?php $content_edit_errors = get_transient('tcarm_admin_edit_errors_' . absint($item->id) . '_' . get_current_user_id()); ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html__('The application content could not be saved.', 'shinseiflow-application-review'); ?></p><?php if (!empty($content_edit_errors) && is_array($content_edit_errors)): ?><ul><?php foreach ($content_edit_errors as $error): ?><li><?php echo esc_html($error); ?></li><?php endforeach; ?></ul><?php endif; ?></div>
            <?php endif; ?>
            <div class="tcarm-detail-layout tcarm-admin-application-detail">
                <div class="tcarm-detail-main tcarm-admin-detail-main">
                    <div class="tcarm-panel tcarm-card-panel tcarm-detail-content-panel">
                        <div class="tcarm-panel-header">
                            <div><h2><?php echo esc_html__('Application Content', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Displayed according to the sections and order configured in Form Settings.', 'shinseiflow-application-review'); ?></p></div>
                            <?php if (!$is_edit_content): ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg('edit_content', '1', admin_url('admin.php?page=tcarm_applications&action=view&id=' . absint($item->id)))); ?>"><?php echo esc_html__('Edit Application Content', 'shinseiflow-application-review'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="tcarm-detail-content-inner">
                            <?php if ($is_edit_content): ?>
                                <?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Edit form markup escapes field values, attributes, and textarea content before output.
                                echo $this->render_admin_application_edit_form($item);
                                ?>
                                <div class="tcarm-admin-edit-sticky-bar" role="region" aria-label="<?php echo esc_attr__('Application Content Edit Actions', 'shinseiflow-application-review'); ?>">
                                    <div class="tcarm-admin-edit-sticky-message"><?php echo esc_html__('You are editing the application content. Changes are not applied until you save.', 'shinseiflow-application-review'); ?></div>
                                    <div class="tcarm-admin-edit-sticky-actions">
                                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=tcarm_applications&action=view&id=' . absint($item->id))); ?>"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></a>
                                        <button type="submit" class="button button-primary" form="tcarm-admin-edit-content-form"><?php echo esc_html__('Save', 'shinseiflow-application-review'); ?></button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Application section markup escapes applicant-provided values before output.
                                echo $this->render_admin_application_sections($item);
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="tcarm-detail-side tcarm-admin-detail-side">
                    <div class="tcarm-panel tcarm-card-panel tcarm-detail-summary-panel">
                        <div class="tcarm-panel-header"><h2><?php echo esc_html__('Review Summary', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Check the current status and publication status.', 'shinseiflow-application-review'); ?></p></div>
                        <div class="tcarm-detail-side-inner">
                            <dl class="tcarm-summary-list">
                                <dt><?php echo esc_html__('Current Status', 'shinseiflow-application-review'); ?></dt>
                                <dd><span class="tcarm-status tcarm-status-<?php echo esc_attr($item->status); ?>"><?php echo esc_html(self::status_label($item->status)); ?></span></dd>
                                <dt><?php echo esc_html__('Application Date', 'shinseiflow-application-review'); ?></dt>
                                <dd><?php echo esc_html($item->created_at); ?></dd>
                                <dt><?php echo esc_html__('Updated Date', 'shinseiflow-application-review'); ?></dt>
                                <dd><?php echo esc_html($item->updated_at); ?></dd>
                                <dt><?php echo esc_html__('Edit Resubmission', 'shinseiflow-application-review'); ?></dt>
                                <dd>
                                    <?php if (!empty($item->resubmit_count)): ?>
                                        <?php /* translators: %d: resubmission count. */ echo esc_html(sprintf(__('%d times', 'shinseiflow-application-review'), absint($item->resubmit_count))); ?>
                                    <?php else: ?>
                                        <?php echo esc_html__('None', 'shinseiflow-application-review'); ?>
                                    <?php endif; ?>
                                </dd>
                                <dt><?php echo esc_html__('Last Edited At', 'shinseiflow-application-review'); ?></dt>
                                <dd>
                                    <?php if (!empty($item->last_resubmitted_at)): ?>
                                        <?php echo esc_html($item->last_resubmitted_at); ?>
                                    <?php else: ?>
                                        <?php echo esc_html('—'); ?>
                                    <?php endif; ?>
                                </dd>
                                <dt><?php echo esc_html__('Last Reviewed At', 'shinseiflow-application-review'); ?></dt>
                                <dd>
                                    <?php if (!empty($item->reviewed_at)): ?>
                                        <?php echo esc_html($item->reviewed_at); ?>
                                    <?php else: ?>
                                        <?php echo esc_html('—'); ?>
                                    <?php endif; ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    <div class="tcarm-panel tcarm-card-panel tcarm-detail-action-panel">
                        <div class="tcarm-panel-header"><h2><?php echo esc_html__('Review Actions', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Select approve or reject based on the review result.', 'shinseiflow-application-review'); ?></p></div>
                        <div class="tcarm-detail-side-inner">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <?php wp_nonce_field('tcarm_update_status_' . $item->id); ?>
                                <input type="hidden" name="action" value="tcarm_update_status">
                                <input type="hidden" name="id" value="<?php echo esc_attr(absint($item->id)); ?>">
                                <p><label><?php echo esc_html__('Message to Applicant', 'shinseiflow-application-review'); ?><br><textarea name="review_message" rows="6" class="large-text" placeholder="<?php echo esc_attr__('Enter the message to send to the applicant, such as the rejection reason.', 'shinseiflow-application-review'); ?>"><?php echo esc_textarea($review_message); ?></textarea></label></p>
                                <div class="tcarm-review-actions">
                                    <button class="button button-primary" name="new_status" value="approved"><?php echo esc_html__('Approve', 'shinseiflow-application-review'); ?></button>
                                    <button class="button" name="new_status" value="rejected"><?php echo esc_html__('Reject', 'shinseiflow-application-review'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Resend email card markup escapes URLs, nonces, and visible values before output.
                    echo $this->render_resend_email_card($item);
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- History timeline markup escapes entry values before output.
                    echo $this->render_application_history_timeline($item);
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}
