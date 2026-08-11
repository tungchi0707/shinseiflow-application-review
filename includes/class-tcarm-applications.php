<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Applications_Trait {
    private static function application_cache_group() {
        return 'tcarm_applications';
    }

    private static function application_cache_ttl() {
        return MINUTE_IN_SECONDS;
    }

    private static function application_cache_key($parts) {
        return 'tcarm_' . md5(wp_json_encode($parts));
    }

    private static function flush_application_cache() {
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(self::application_cache_group());
        }
    }

    private function get_application_extra_data($item) {
        if (!$item || empty($item->form_data_json)) {
            return array();
        }
        $decoded = json_decode($item->form_data_json, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function application_value($item, $key) {
        $extra = is_object($item) ? $this->get_application_extra_data($item) : array();
        if (isset($extra[$key]) && is_array($extra[$key])) {
            return $extra[$key];
        }
        if (is_object($item) && isset($item->{$key})) {
            return $item->{$key};
        }
        if (is_array($item) && isset($item[$key])) {
            return $item[$key];
        }
        return isset($extra[$key]) ? $extra[$key] : '';
    }

    private function dropdown_choices($field) {
        $choices = isset($field['choices']) && is_array($field['choices']) ? $field['choices'] : array();
        $out = array();
        foreach ($choices as $choice) {
            if (!is_array($choice)) {
                continue;
            }
            $label = isset($choice['label']) ? (string) $choice['label'] : '';
            $value = isset($choice['value']) ? (string) $choice['value'] : '';
            if ($label === '' && $value === '') {
                continue;
            }
            if ($value === '') {
                $value = sanitize_key($label);
            }
            if ($label === '') {
                $label = $value;
            }
            $out[] = array('label' => $label, 'value' => $value);
        }
        return $out;
    }

    private function dropdown_choice_label($value, $field) {
        foreach ($this->dropdown_choices($field) as $choice) {
            if ((string) $choice['value'] === (string) $value) {
                return $choice['label'];
            }
        }
        return (string) $value;
    }

    private function dropdown_choice_values($field) {
        return array_map(function($choice) {
            return (string) $choice['value'];
        }, $this->dropdown_choices($field));
    }

    private function format_field_value($value, $field) {
        if (isset($field['type']) && $field['type'] === 'checkbox_group') {
            if (!is_array($value) || empty($value)) {
                return '';
            }
            $labels = array();
            foreach ($value as $selected_value) {
                if (is_string($selected_value)) {
                    $labels[] = $this->dropdown_choice_label($selected_value, $field);
                }
            }
            return implode(', ', $labels);
        }
        if (isset($field['type']) && in_array($field['type'], array('dropdown', 'radio'), true)) {
            return $this->dropdown_choice_label($value, $field);
        }
        if (isset($field['type']) && $field['type'] === 'file') {
            $attachments = $this->decode_file_attachments($value);
            if (empty($attachments)) {
                return '';
            }
            return implode("
", array_map(function($file) {
                return isset($file['name']) ? $file['name'] : '';
            }, $attachments));
        }
        return (string) $value;
    }

    private function decode_file_attachments($value) {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return array();
        }
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return array();
        }
        $files = array();
        foreach ($decoded as $file) {
            if (!is_array($file) || empty($file['url']) || empty($file['name'])) {
                continue;
            }
            $files[] = array(
                'name' => sanitize_file_name($file['name']),
                'original_name' => isset($file['original_name']) ? sanitize_file_name($file['original_name']) : sanitize_file_name($file['name']),
                'stored_name' => isset($file['stored_name']) ? sanitize_file_name($file['stored_name']) : '',
                'url' => esc_url_raw($file['url']),
                'file' => isset($file['file']) ? sanitize_text_field($file['file']) : '',
                'type' => isset($file['type']) ? sanitize_text_field($file['type']) : '',
                'size' => isset($file['size']) ? absint($file['size']) : 0,
            );
        }
        return $files;
    }

    private function sanitize_file_value($value) {
        $attachments = $this->decode_file_attachments(is_array($value) ? wp_json_encode($value) : (string) $value);
        return !empty($attachments) ? wp_json_encode($attachments) : '';
    }

    private function render_file_value_html($value) {
        $attachments = $this->decode_file_attachments($value);
        if (empty($attachments)) {
            return '';
        }
        $html = '<div class="tcarm-attachment-list">';
        foreach ($attachments as $file) {
            $name = isset($file['name']) ? $file['name'] : '';
            $url = isset($file['url']) ? $file['url'] : '';
            $type = isset($file['type']) ? $file['type'] : '';
            $html .= '<div class="tcarm-attachment-item">';
            $is_lightbox_image = in_array($type, array('image/jpeg', 'image/png', 'image/gif', 'image/webp'), true);
            if ($is_lightbox_image) {
                $html .= '<a href="' . esc_url($url) . '" class="tcarm-attachment-lightbox-link" data-tcarm-image-lightbox="1" data-tcarm-lightbox-caption="' . esc_attr($name) . '"><img src="' . esc_url($url) . '" alt="" class="tcarm-attachment-thumb"></a>';
            } elseif (strpos($type, 'image/') === 0) {
                $html .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener"><img src="' . esc_url($url) . '" alt="" class="tcarm-attachment-thumb"></a>';
            }
            $html .= '<a href="' . esc_url($url) . '"' . ($is_lightbox_image ? ' data-tcarm-image-lightbox="1" data-tcarm-lightbox-caption="' . esc_attr($name) . '"' : ' target="_blank" rel="noopener"') . '>' . esc_html($name) . '</a>';
            if (!empty($file['size'])) {
                $html .= '<span class="tcarm-attachment-size">' . esc_html(size_format((int) $file['size'])) . '</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return wp_kses($html, $this->attachment_html_allowed_tags());
    }

    private function attachment_html_allowed_tags() {
        return array(
            'div' => array(
                'class' => true,
            ),
            'a' => array(
                'href' => true,
                'class' => true,
                'target' => true,
                'rel' => true,
                'data-tcarm-image-lightbox' => true,
                'data-tcarm-lightbox-caption' => true,
            ),
            'img' => array(
                'src' => true,
                'alt' => true,
                'class' => true,
            ),
            'span' => array(
                'class' => true,
            ),
        );
    }

    private function application_field_value_allowed_tags() {
        $tags = $this->attachment_html_allowed_tags();
        $tags['br'] = array();
        return $tags;
    }

    private function admin_application_edit_field_allowed_tags() {
        $tags = $this->application_field_value_allowed_tags();
        $tags['label'] = array(
            'class' => true,
            'for' => true,
        );
        $tags['input'] = array(
            'id' => true,
            'type' => true,
            'name' => true,
            'value' => true,
            'class' => true,
            'placeholder' => true,
            'required' => true,
            'checked' => true,
            'multiple' => true,
        );
        $tags['textarea'] = array(
            'id' => true,
            'name' => true,
            'rows' => true,
            'placeholder' => true,
            'required' => true,
        );
        $tags['select'] = array(
            'id' => true,
            'name' => true,
            'required' => true,
        );
        $tags['option'] = array(
            'value' => true,
            'selected' => true,
        );
        $tags['p'] = array(
            'class' => true,
        );
        return $tags;
    }

    private function render_admin_application_sections($item) {
        $fields = self::get_fields();
        $sections = self::get_sections();
        $grouped = array();

        foreach ($sections as $section_key => $section) {
            $normalized = self::normalize_section_key($section_key);
            if (!isset($grouped[$normalized])) {
                $grouped[$normalized] = array(
                    'label' => isset($section['label']) ? $section['label'] : $normalized,
                    'enabled' => isset($section['enabled']) ? $section['enabled'] : '1',
                    'fields' => array(),
                );
            }
        }

        foreach ($fields as $key => $field) {
            $current_value = $this->application_value($item, $key);
            $field_enabled = !isset($field['enabled']) || $field['enabled'] === '1';
            if (!$field_enabled && $current_value === '') {
                continue;
            }
            $section = !empty($field['section']) ? self::normalize_section_key($field['section']) : 'event';
            if (!isset($grouped[$section])) {
                $grouped[$section] = array(
                    'label' => self::section_label($section),
                    'enabled' => '1',
                    'fields' => array(),
                );
            }
            $grouped[$section]['fields'][$key] = $this->apply_field_translation($field);
        }

        ob_start();
        echo '<div class="tcarm-admin-application-sections">';
        foreach ($grouped as $section_key => $section) {
            if (empty($section['fields'])) {
                continue;
            }
            if (isset($section['enabled']) && $section['enabled'] !== '1') {
                $has_value = false;
                foreach ($section['fields'] as $field_key => $field) {
                    if ($this->application_value($item, $field_key) !== '') {
                        $has_value = true;
                        break;
                    }
                }
                if (!$has_value) {
                    continue;
                }
            }
            echo '<section class="tcarm-admin-application-section tcarm-admin-application-section-' . esc_attr($section_key) . '">';
            echo '<div class="tcarm-admin-application-section-header"><h3 class="tcarm-admin-application-section-title">' . esc_html($section['label']) . '</h3></div>';
            echo '<div class="tcarm-admin-application-section-body"><dl class="tcarm-admin-field-list">';
            foreach ($section['fields'] as $field_key => $field) {
                $value = $this->application_value($item, $field_key);
                $is_empty = ($value === '' || $value === null || (is_array($value) && empty($value)));
                if (isset($field['type']) && $field['type'] === 'file') {
                    $is_empty = empty($this->decode_file_attachments($value));
                    $value_html = $is_empty ? '—' : $this->render_file_value_html($value);
                } else {
                    $value_html = $is_empty ? '—' : nl2br(esc_html($this->format_field_value($value, $field)));
                }
                echo '<div class="tcarm-admin-field-row tcarm-admin-field-row-' . esc_attr($field_key) . '">';
                echo '<dt class="tcarm-admin-field-label">' . esc_html(isset($field['label']) ? $field['label'] : $field_key) . '</dt>';
                echo '<dd class="' . esc_attr('tcarm-admin-field-value' . ($is_empty ? ' is-empty' : '')) . '">' . wp_kses($value_html, $this->application_field_value_allowed_tags()) . '</dd>';
                echo '</div>';
            }
            echo '</dl></div>';
            echo '</section>';
        }

        $consents = self::get_consent_items();
        $enabled_consents = array();
        foreach ($consents as $consent_key => $consent) {
            $show_checkbox = isset($consent['show_checkbox']) ? $consent['show_checkbox'] === '1' : true;
            if (isset($consent['enabled']) && $consent['enabled'] === '1' && $show_checkbox) {
                $enabled_consents[$consent_key] = $consent;
            }
        }
        if (!empty($enabled_consents)) {
            echo '<section class="tcarm-admin-application-section tcarm-admin-application-section-consents">';
            echo '<div class="tcarm-admin-application-section-header"><h3 class="tcarm-admin-application-section-title">' . esc_html__('Consent Items', 'shinseiflow-application-review') . '</h3><p class="tcarm-admin-detail-note">' . esc_html__('Consent items confirmed when the application was submitted.', 'shinseiflow-application-review') . '</p></div>';
            echo '<div class="tcarm-admin-application-section-body"><dl class="tcarm-admin-field-list">';
            foreach ($enabled_consents as $consent_key => $consent) {
                $required = isset($consent['required']) && $consent['required'] === '1';
                echo '<div class="tcarm-admin-field-row tcarm-admin-consent-row tcarm-admin-consent-row-' . esc_attr($consent_key) . '">';
                echo '<dt class="tcarm-admin-field-label">' . esc_html(isset($consent['label']) ? $consent['label'] : $consent_key) . '</dt>';
                echo '<dd class="tcarm-admin-field-value"><span class="' . esc_attr('tcarm-admin-consent-status' . ($required ? '' : ' tcarm-admin-consent-status--optional')) . '">' . esc_html__('Agreed', 'shinseiflow-application-review') . ($required ? '' : esc_html__(' (Optional)', 'shinseiflow-application-review')) . '</span></dd>';
                echo '</div>';
            }
            echo '</dl></div>';
            echo '</section>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function render_admin_application_edit_form($item) {
        $fields = self::get_fields();
        $sections = self::get_sections();
        $detail_url = admin_url('admin.php?page=tcarm_applications&action=view&id=' . absint($item->id));
        ob_start();
        ?>
        <form id="tcarm-admin-edit-content-form" class="tcarm-admin-application-edit-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('tcarm_admin_edit_application_content_' . absint($item->id)); ?>
            <input type="hidden" name="action" value="tcarm_admin_update_application_content">
            <input type="hidden" name="id" value="<?php echo absint($item->id); ?>">
            <div class="tcarm-admin-application-sections tcarm-admin-application-edit-sections">
                <?php foreach ($sections as $section_key => $section): ?>
                    <?php
                    $section_fields = array();
                    foreach ($fields as $field_key => $field) {
                        if ((isset($field['section']) ? $field['section'] : '') === $section_key) {
                            $section_fields[$field_key] = $field;
                        }
                    }
                    if (empty($section_fields)) {
                        continue;
                    }
                    ?>
                    <section class="tcarm-admin-application-section tcarm-admin-application-section-<?php echo esc_attr($section_key); ?>">
                        <div class="tcarm-admin-application-section-header"><h3 class="tcarm-admin-application-section-title"><?php echo esc_html($section['label']); ?></h3></div>
                        <div class="tcarm-admin-application-section-body">
                            <div class="tcarm-admin-edit-field-list">
                                <?php foreach ($section_fields as $field_key => $field): ?>
                                    <?php echo wp_kses($this->render_admin_application_edit_field($item, $field_key, $field), $this->admin_application_edit_field_allowed_tags()); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <div class="tcarm-admin-edit-actions">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Save', 'shinseiflow-application-review'); ?></button>
                <a class="button" href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html__('Cancel', 'shinseiflow-application-review'); ?></a>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_admin_application_edit_field($item, $key, $field) {
        $type = isset($field['type']) ? $field['type'] : 'text';
        $value = $this->application_value($item, $key);
        $label = isset($field['label']) ? $field['label'] : $key;
        $required = isset($field['required']) && $field['required'] === '1';
        $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
        ob_start();
        ?>
        <div class="tcarm-admin-edit-field tcarm-admin-edit-field-<?php echo esc_attr($key); ?> tcarm-admin-edit-field--<?php echo esc_attr($type); ?>">
            <label class="tcarm-admin-edit-label" for="tcarm-admin-edit-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?><?php if ($required): ?> <span class="tcarm-required">*</span><?php endif; ?></label>
            <div class="tcarm-admin-edit-control">
                <?php if ($type === 'textarea'): ?>
                    <textarea id="tcarm-admin-edit-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" rows="5" placeholder="<?php echo esc_attr($placeholder); ?>"<?php if ($required): ?> required="<?php echo esc_attr('required'); ?>"<?php endif; ?>><?php echo esc_textarea($value); ?></textarea>
                <?php elseif ($type === 'checkbox_group'): ?>
                    <?php $checkbox_group_values = is_array($value) ? $value : array(); ?>
                    <?php foreach ($this->dropdown_choices($field) as $choice_index => $choice): $choice_id = 'tcarm-admin-edit-' . $key . '-' . $choice_index; ?>
                        <label class="tcarm-admin-edit-checkbox" for="<?php echo esc_attr($choice_id); ?>"><input id="<?php echo esc_attr($choice_id); ?>" type="checkbox" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($choice['value']); ?>" <?php checked(in_array((string) $choice['value'], $checkbox_group_values, true)); ?>> <?php echo esc_html($choice['label']); ?></label>
                    <?php endforeach; ?>
                <?php elseif ($type === 'radio'): ?>
                    <?php foreach ($this->dropdown_choices($field) as $choice_index => $choice): $choice_id = 'tcarm-admin-edit-' . $key . ($choice_index === 0 ? '' : '-' . $choice_index); ?>
                        <label class="tcarm-admin-edit-radio" for="<?php echo esc_attr($choice_id); ?>"><input id="<?php echo esc_attr($choice_id); ?>" type="radio" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($choice['value']); ?>" <?php checked((string) $value, (string) $choice['value']); ?><?php if ($required && $choice_index === 0): ?> required="<?php echo esc_attr('required'); ?>"<?php endif; ?>> <?php echo esc_html($choice['label']); ?></label>
                    <?php endforeach; ?>
                <?php elseif ($type === 'dropdown'): ?>
                    <select id="tcarm-admin-edit-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>"<?php if ($required): ?> required="<?php echo esc_attr('required'); ?>"<?php endif; ?>>
                        <option value=""><?php echo esc_html__('Select', 'shinseiflow-application-review'); ?></option>
                        <?php foreach ($this->dropdown_choices($field) as $choice): ?>
                            <option value="<?php echo esc_attr($choice['value']); ?>" <?php selected((string) $value, (string) $choice['value']); ?>><?php echo esc_html($choice['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'file'): ?>
                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                    <?php if (!empty($value)): ?><div class="tcarm-admin-edit-current-file"><?php echo wp_kses($this->render_file_value_html($value), $this->attachment_html_allowed_tags()); ?></div><?php endif; ?>
                    <input id="tcarm-admin-edit-<?php echo esc_attr($key); ?>" type="file" name="<?php echo esc_attr($key); ?>[]" multiple>
                    <p class="description"><?php echo esc_html__('The current file will be replaced only if you select a new file. If no file is selected, the current file is kept.', 'shinseiflow-application-review'); ?></p>
                <?php else: ?>
                    <?php $input_type = in_array($type, array('email', 'url', 'tel', 'date'), true) ? $type : 'text'; ?>
                    <input id="tcarm-admin-edit-<?php echo esc_attr($key); ?>" type="<?php echo esc_attr($input_type); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>"<?php if ($required): ?> required="<?php echo esc_attr('required'); ?>"<?php endif; ?>>
                <?php endif; ?>
                <?php if (!empty($field['description'])): ?><p class="description"><?php echo nl2br(esc_html($field['description'])); ?></p><?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function upload_settings() {
        $settings = self::get_settings();
        $exts = array_filter(array_map('trim', explode(',', isset($settings['file_allowed_extensions']) ? $settings['file_allowed_extensions'] : 'jpg,jpeg,png,pdf')));
        $safe_exts = array();
        foreach ($exts as $ext) {
            $ext = strtolower(preg_replace('/[^a-z0-9]/', '', (string) $ext));
            if ($ext !== '' && !$this->is_forbidden_upload_extension($ext)) {
                $safe_exts[] = $ext;
            }
        }
        return array(
            'enabled' => isset($settings['file_upload_enabled']) ? $settings['file_upload_enabled'] : '1',
            'extensions' => array_values(array_unique($safe_exts ?: array('jpg','jpeg','png','pdf'))),
            'max_size' => max(1, absint(isset($settings['file_max_size_mb']) ? $settings['file_max_size_mb'] : 5)) * 1024 * 1024,
            'max_uploads' => max(1, absint(isset($settings['file_max_uploads']) ? $settings['file_max_uploads'] : 3)),
        );
    }

    private function is_forbidden_upload_extension($ext) {
        return in_array(strtolower((string) $ext), array('php','phtml','php3','php4','php5','php7','phar','cgi','pl','py','sh','asp','aspx','html','htm','js','svg'), true);
    }

    private function upload_allowed_mimes($extensions) {
        $map = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        );
        $mimes = array();
        foreach ($extensions as $ext) {
            $ext = strtolower(trim($ext));
            if (isset($map[$ext])) {
                $mimes[$ext] = $map[$ext];
            }
        }
        return $mimes;
    }

    private function get_wp_filesystem() {
        global $wp_filesystem;

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (!$wp_filesystem) {
            WP_Filesystem();
        }

        return $wp_filesystem;
    }

    private function filesystem_is_writable($path) {
        $filesystem = $this->get_wp_filesystem();
        return $filesystem && $filesystem->is_writable($path);
    }

    private function filesystem_move($source, $destination) {
        $filesystem = $this->get_wp_filesystem();
        return $filesystem && $filesystem->move($source, $destination, true);
    }

    private function write_upload_security_files($dir) {
        if (!$dir || !is_dir($dir) || !$this->filesystem_is_writable($dir)) {
            return;
        }
        $index = trailingslashit($dir) . 'index.html';
        if (!file_exists($index)) {
            @file_put_contents($index, "");
        }
        $htaccess = trailingslashit($dir) . '.htaccess';
        if (!file_exists($htaccess)) {
            $rules = "Options -Indexes\n";
            $rules .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phar|cgi|pl|py|sh|asp|aspx)$\">\n";
            $rules .= "  Require all denied\n";
            $rules .= "</FilesMatch>\n";
            @file_put_contents($htaccess, $rules);
        }
    }

    private function ensure_tcarm_upload_directory($relative_subdir = '') {
        $uploads = wp_upload_dir();
        $base = trailingslashit($uploads['basedir']) . 'tcarm-applications';
        $base_url = trailingslashit($uploads['baseurl']) . 'tcarm-applications';
        $relative_subdir = trim((string) $relative_subdir, '/');
        $target = $relative_subdir !== '' ? trailingslashit($base) . $relative_subdir : $base;
        if (!wp_mkdir_p($target)) {
            return false;
        }
        $this->write_upload_security_files($base);
        $parts = $relative_subdir !== '' ? explode('/', $relative_subdir) : array();
        $current = $base;
        foreach ($parts as $part) {
            $current = trailingslashit($current) . sanitize_file_name($part);
            $this->write_upload_security_files($current);
        }
        return array(
            'path' => $target,
            'url' => $relative_subdir !== '' ? trailingslashit($base_url) . $relative_subdir : $base_url,
            'subdir' => $relative_subdir,
        );
    }

    public function filter_tcarm_upload_dir($uploads) {
        $relative = trim((string) $this->current_tcarm_upload_subdir, '/');
        if ($relative === '') {
            return $uploads;
        }
        $base_path = trailingslashit($uploads['basedir']) . 'tcarm-applications';
        $base_url = trailingslashit($uploads['baseurl']) . 'tcarm-applications';
        $uploads['subdir'] = '/tcarm-applications/' . $relative;
        $uploads['path'] = trailingslashit($base_path) . $relative;
        $uploads['url'] = trailingslashit($base_url) . $relative;
        return $uploads;
    }

    private function upload_relative_subdir($application_id = 0) {
        $year = gmdate('Y');
        $month = gmdate('m');
        $leaf = $application_id ? ('application-' . absint($application_id)) : 'pending';
        return $year . '/' . $month . '/' . $leaf;
    }

    private function secure_upload_filename($application_id, $ext) {
        $prefix = $application_id ? ('tcarm_' . absint($application_id)) : 'tcarm_pending';
        return $prefix . '_' . strtolower(wp_generate_password(12, false, false)) . '.' . strtolower($ext);
    }

    private function file_passes_mime_check($file, $original_name, $allowed_mimes) {
        if (empty($allowed_mimes) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        $checked = wp_check_filetype_and_ext($file['tmp_name'], $original_name, $allowed_mimes);
        if (empty($checked['ext']) || empty($checked['type'])) {
            return false;
        }
        return in_array($checked['type'], array_values($allowed_mimes), true);
    }

    private function migrate_application_attachments($application_id, $data) {
        $application_id = absint($application_id);
        if (!$application_id) {
            return $data;
        }
        $uploads = wp_upload_dir();
        $target_info = $this->ensure_tcarm_upload_directory($this->upload_relative_subdir($application_id));
        if (!$target_info) {
            return $data;
        }
        $fields = self::get_fields();
        foreach ($fields as $key => $field) {
            if ((isset($field['type']) ? $field['type'] : '') !== 'file' || empty($data[$key])) {
                continue;
            }
            $attachments = $this->decode_file_attachments($data[$key]);
            if (empty($attachments)) {
                continue;
            }
            $changed = false;
            foreach ($attachments as &$attachment) {
                $current_file = isset($attachment['file']) ? $attachment['file'] : '';
                if (!$current_file || !file_exists($current_file)) {
                    continue;
                }
                if (strpos(str_replace('\\', '/', $current_file), '/tcarm-applications/') === false) {
                    continue;
                }
                if (strpos(str_replace('\\', '/', $current_file), '/application-' . $application_id . '/') !== false) {
                    continue;
                }
                $ext = strtolower(pathinfo($current_file, PATHINFO_EXTENSION));
                if (!$ext) {
                    continue;
                }
                $new_name = $this->secure_upload_filename($application_id, $ext);
                $new_file = trailingslashit($target_info['path']) . $new_name;
                if ($this->filesystem_move($current_file, $new_file)) {
                    $attachment['file'] = $new_file;
                    $attachment['url'] = trailingslashit($target_info['url']) . $new_name;
                    $changed = true;
                }
            }
            unset($attachment);
            if ($changed) {
                $data[$key] = wp_json_encode($attachments);
            }
        }
        return $data;
    }

    private function normalize_files_array($key) {
        $file_key = (string) $key;
        if ($file_key === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $file_key)) {
            return array();
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WordPress file upload array is validated by the upload handler; file names, MIME, size, and upload errors are checked before storage.
        if (empty($_FILES[$file_key]) || !is_array($_FILES[$file_key])) {
            return array();
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WordPress file upload array is not plain text input; $file_key is allowlisted, surrounding admin/frontend handlers verify capability and nonce, file name/MIME/size/error are validated before storage, and upload behavior/data format stay unchanged.
        $files = $_FILES[$file_key];
        if (!isset($files['name'], $files['type'], $files['tmp_name'], $files['error'], $files['size'])) {
            return array();
        }
        $normalized = array();
        if (is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                $normalized[] = array(
                    'name' => sanitize_file_name(wp_unslash($name)),
                    'type' => isset($files['type'][$i]) ? sanitize_text_field(wp_unslash($files['type'][$i])) : '',
                    'tmp_name' => isset($files['tmp_name'][$i]) ? (string) $files['tmp_name'][$i] : '',
                    'error' => isset($files['error'][$i]) ? $files['error'][$i] : UPLOAD_ERR_NO_FILE,
                    'size' => isset($files['size'][$i]) ? $files['size'][$i] : 0,
                );
            }
        } else {
            $files['name'] = sanitize_file_name(wp_unslash($files['name']));
            $files['type'] = sanitize_text_field(wp_unslash($files['type']));
            $normalized[] = $files;
        }
        return $normalized;
    }

    private function request_has_new_file_uploads() {
        foreach (self::get_fields() as $key => $field) {
            if ((isset($field['enabled']) && $field['enabled'] !== '1') || (isset($field['type']) ? $field['type'] : '') !== 'file') {
                continue;
            }
            foreach ($this->normalize_files_array($key) as $file) {
                if (isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_NO_FILE) {
                    return true;
                }
            }
        }
        return false;
    }

    private function cleanup_request_uploads($uploads) {
        $upload_dir = wp_upload_dir();
        $storage_root = realpath(trailingslashit($upload_dir['basedir']) . 'tcarm-applications');
        if (!$storage_root) {
            return;
        }
        $storage_root = rtrim(str_replace('\\', '/', $storage_root), '/');
        foreach ($uploads as $upload) {
            $path = isset($upload['file']) ? (string) $upload['file'] : '';
            if ($path === '' || is_link($path) || !is_file($path)) {
                continue;
            }
            $real_path = realpath($path);
            if (!$real_path || is_link($path) || !is_file($real_path)) {
                continue;
            }
            $real_path = str_replace('\\', '/', $real_path);
            if (strpos($real_path, $storage_root . '/') !== 0) {
                continue;
            }
            wp_delete_file($real_path);
        }
    }

    private function clear_request_upload_values(&$data, $uploads) {
        foreach ($uploads as $upload) {
            $field_key = isset($upload['field_key']) ? (string) $upload['field_key'] : '';
            if ($field_key !== '' && array_key_exists($field_key, $data)) {
                $data[$field_key] = '';
            }
        }
    }

    private function process_file_uploads(&$data, $application_id = 0, &$request_uploads = array()) {
        $fields = self::get_fields();
        $settings = $this->upload_settings();
        $errors = array();
        foreach ($fields as $key => $field) {
            if ((isset($field['enabled']) && $field['enabled'] !== '1') || (isset($field['type']) ? $field['type'] : '') !== 'file') {
                continue;
            }
            $incoming = array_filter($this->normalize_files_array($key), function($file) {
                return isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
            });
            if (empty($incoming)) {
                continue;
            }
            if ($settings['enabled'] !== '1') {
                $errors[] = $field['label'] . '：' . __('File attachments are disabled.', 'shinseiflow-application-review');
                continue;
            }
            if (count($incoming) > $settings['max_uploads']) {
                /* translators: %d: maximum number of files. */
                $errors[] = $field['label'] . '：' . sprintf(__('You can upload up to %d files.', 'shinseiflow-application-review'), $settings['max_uploads']);
                continue;
            }
            $attachments = array();
            $allowed_mimes = $this->upload_allowed_mimes($settings['extensions']);
            $relative_subdir = $this->upload_relative_subdir($application_id);
            if (!$this->ensure_tcarm_upload_directory($relative_subdir)) {
                $errors[] = $field['label'] . '：' . __('Could not create the attachment storage directory.', 'shinseiflow-application-review');
                continue;
            }
            foreach ($incoming as $file) {
                if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = $field['label'] . '：' . __('Could not upload the file.', 'shinseiflow-application-review');
                    continue;
                }
                $original_name = isset($file['name']) ? sanitize_file_name($file['name']) : '';
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                if ($this->is_forbidden_upload_extension($ext)) {
                    $errors[] = $field['label'] . '：' . __('This file type cannot be uploaded.', 'shinseiflow-application-review');
                    continue;
                }
                if (!$ext || !in_array($ext, $settings['extensions'], true)) {
                    /* translators: %s: comma-separated allowed file extensions. */
                    $errors[] = $field['label'] . '：' . sprintf(__('Allowed file types are: %s.', 'shinseiflow-application-review'), implode(', ', $settings['extensions']));
                    continue;
                }
                if (!empty($file['size']) && (int) $file['size'] > $settings['max_size']) {
                    /* translators: %s: maximum file size. */
                    $errors[] = $field['label'] . '：' . sprintf(__('File size must be %s or less.', 'shinseiflow-application-review'), size_format($settings['max_size']));
                    continue;
                }
                if (!$this->file_passes_mime_check($file, $original_name, $allowed_mimes)) {
                    $errors[] = $field['label'] . '：' . __('Could not verify the file type. Please upload an allowed image or PDF file.', 'shinseiflow-application-review');
                    continue;
                }
                require_once ABSPATH . 'wp-admin/includes/file.php';
                $file['name'] = $this->secure_upload_filename($application_id, $ext);
                $this->current_tcarm_upload_subdir = $relative_subdir;
                add_filter('upload_dir', array($this, 'filter_tcarm_upload_dir'));
                $moved = wp_handle_upload($file, array(
                    'test_form' => false,
                    'mimes' => $allowed_mimes,
                ));
                remove_filter('upload_dir', array($this, 'filter_tcarm_upload_dir'));
                $this->current_tcarm_upload_subdir = '';
                if (isset($moved['error'])) {
                    $errors[] = $field['label'] . '：' . __('Could not upload the file.', 'shinseiflow-application-review');
                    continue;
                }
                $request_uploads[] = array(
                    'field_key' => $key,
                    'file' => isset($moved['file']) ? (string) $moved['file'] : '',
                );
                $attachments[] = array(
                    'name' => $original_name,
                    'original_name' => $original_name,
                    'stored_name' => basename(isset($moved['file']) ? $moved['file'] : ''),
                    'url' => isset($moved['url']) ? esc_url_raw($moved['url']) : '',
                    'file' => isset($moved['file']) ? sanitize_text_field($moved['file']) : '',
                    'type' => isset($moved['type']) ? sanitize_text_field($moved['type']) : '',
                    'size' => isset($file['size']) ? absint($file['size']) : 0,
                );
            }
            if (!empty($attachments)) {
                $data[$key] = wp_json_encode($attachments);
            }
        }
        return $errors;
    }

    public static function schedule_pending_upload_cleanup() {
        if (!wp_next_scheduled(self::PENDING_UPLOAD_CLEANUP_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::PENDING_UPLOAD_CLEANUP_HOOK);
        }
    }

    public static function unschedule_pending_upload_cleanup() {
        wp_clear_scheduled_hook(self::PENDING_UPLOAD_CLEANUP_HOOK);
    }

    public function cleanup_stale_pending_uploads() {
        $upload_dir = wp_upload_dir();
        $storage_root_path = trailingslashit($upload_dir['basedir']) . 'tcarm-applications';
        if (is_link($storage_root_path)) {
            return;
        }
        $storage_root = realpath($storage_root_path);
        if (!$storage_root || is_link($storage_root)) {
            return;
        }
        $storage_root = rtrim(str_replace('\\', '/', $storage_root), '/');
        $pending_dirs = glob($storage_root . '/*/*/pending', GLOB_ONLYDIR);
        if (!is_array($pending_dirs)) {
            return;
        }
        $cutoff = time() - DAY_IN_SECONDS;
        foreach ($pending_dirs as $pending_dir) {
            if (is_link($pending_dir)) {
                continue;
            }
            $real_pending_dir = realpath($pending_dir);
            if (!$real_pending_dir || is_link($pending_dir) || !is_dir($real_pending_dir)) {
                continue;
            }
            $real_pending_dir = rtrim(str_replace('\\', '/', $real_pending_dir), '/');
            $relative = ltrim(substr($real_pending_dir, strlen($storage_root)), '/');
            if (strpos($real_pending_dir, $storage_root . '/') !== 0 || !preg_match('#^[0-9]{4}/[0-9]{2}/pending$#', $relative)) {
                continue;
            }
            try {
                $files = new FilesystemIterator($real_pending_dir, FilesystemIterator::SKIP_DOTS);
            } catch (UnexpectedValueException $e) {
                continue;
            }
            foreach ($files as $file_info) {
                try {
                    $name = $file_info->getFilename();
                    if ($name === '.htaccess' || $name === 'index.html' || $file_info->isLink() || !$file_info->isFile()) {
                        continue;
                    }
                    $path = $file_info->getPathname();
                    if (is_link($path) || !is_file($path)) {
                        continue;
                    }
                    $modified = @filemtime($path);
                    if ($modified === false || $modified >= $cutoff) {
                        continue;
                    }
                    $real_path = realpath($path);
                    if (!$real_path || is_link($path) || !is_file($real_path)) {
                        continue;
                    }
                    $real_path = str_replace('\\', '/', $real_path);
                    if (dirname($real_path) !== $real_pending_dir || strpos($real_path, $real_pending_dir . '/') !== 0) {
                        continue;
                    }
                    wp_delete_file($real_path);
                } catch (RuntimeException $e) {
                    continue;
                }
            }
        }
    }

    public static function statuses() {
        return array(
            'pending' => __('Pending Review', 'shinseiflow-application-review'),
            'approved' => __('Approved', 'shinseiflow-application-review'),
            'rejected' => __('Rejected', 'shinseiflow-application-review'),
            'needs_more' => __('Additional Information Requested', 'shinseiflow-application-review'),
            'published' => __('Published', 'shinseiflow-application-review'),
        );
    }

    public static function review_statuses() {
        $statuses = self::statuses();
        unset($statuses['published']);
        unset($statuses['needs_more']);
        return $statuses;
    }

    public static function status_label($status) {
        $statuses = self::statuses();
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    public function handle_delete_blocked_log() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('You do not have permission.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Sanitized id is required to build the per-row nonce action checked immediately below.
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
        if (!$id) {
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        check_admin_referer('tcarm_delete_blocked_log_' . $id);
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom blocked log table delete; WordPress core APIs do not apply.
        $wpdb->delete(self::blocked_table_name(), array('id' => $id), array('%d'));
        self::flush_application_cache();
        wp_safe_redirect(admin_url('admin.php?page=tcarm_applications'));
        exit;
    }

    public function handle_bulk_delete_applications() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('You do not have permission.', 'shinseiflow-application-review'));
        }
        check_admin_referer('tcarm_bulk_delete_applications', 'tcarm_bulk_delete_nonce');
        $ids = isset($_POST['application_ids']) && is_array($_POST['application_ids']) ? array_map('absint', wp_unslash($_POST['application_ids'])) : array();
        $ids = array_values(array_filter(array_unique($ids)));
        if (empty($ids)) {
            wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&tcarm_notice=none'));
            exit;
        }
        global $wpdb;
        $table = self::table_name();
        $deleted_at = current_time('mysql');
        $deleted_by = get_current_user_id();
        foreach ($ids as $id) {
            $item = $this->get_application($id);
            if (!$item || !empty($item->deleted_at)) {
                continue;
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table soft delete; WordPress core APIs do not apply.
            $wpdb->update($table, array(
                'deleted_at' => $deleted_at,
                'deleted_by' => $deleted_by,
                'updated_at' => current_time('mysql'),
            ), array('id' => $id), array('%s', '%d', '%s'), array('%d'));
            $this->append_application_history($id, 'moved_to_deleted');
        }
        self::flush_application_cache();
        wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&tcarm_notice=deleted'));
        exit;
    }

    public function handle_restore_application() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('You do not have permission.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized id is required to build the per-application nonce action checked immediately below.
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        if (!$id) {
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        check_admin_referer('tcarm_restore_application_' . $id, 'tcarm_restore_nonce');
        $item = $this->get_application($id);
        if (!$item) {
            wp_die(esc_html__('Application not found.', 'shinseiflow-application-review'));
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table restore; WordPress core APIs do not apply.
        $wpdb->query($wpdb->prepare(
            "UPDATE %i SET deleted_at = NULL, deleted_by = 0, updated_at = %s WHERE id = %d",
            self::table_name(),
            current_time('mysql'),
            $id
        ));
        self::flush_application_cache();
        $this->append_application_history($id, 'restored_from_deleted');
        wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&tcarm_notice=restored'));
        exit;
    }

    public function handle_permanently_delete_application() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized id is required to build the per-application nonce action checked immediately below.
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        if (!$id) {
            wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&tcarm_notice=permanent_delete_failed'));
            exit;
        }
        check_admin_referer('tcarm_permanently_delete_application_' . $id, 'tcarm_permanent_delete_nonce');
        $item = $this->get_application($id);
        if (!$item || empty($item->deleted_at)) {
            wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&tcarm_notice=permanent_delete_not_deleted'));
            exit;
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table permanent delete; WordPress core APIs do not apply.
        $deleted = $wpdb->delete(self::table_name(), array('id' => $id), array('%d'));
        self::flush_application_cache();
        $notice = $deleted ? 'permanently_deleted' : 'permanent_delete_failed';
        wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&tcarm_notice=' . $notice));
        exit;
    }

    public function handle_bulk_permanently_delete_applications() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'shinseiflow-application-review'));
        }
        check_admin_referer('tcarm_bulk_permanently_delete_applications', 'tcarm_bulk_permanent_delete_nonce');
        $ids = isset($_POST['application_ids']) && is_array($_POST['application_ids']) ? array_map('absint', wp_unslash($_POST['application_ids'])) : array();
        $ids = array_values(array_filter(array_unique($ids)));
        if (empty($ids)) {
            wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&tcarm_notice=bulk_permanent_delete_none'));
            exit;
        }

        global $wpdb;
        $success = 0;
        $failed = 0;
        foreach ($ids as $id) {
            $item = $this->get_application($id);
            if (!$item || empty($item->deleted_at)) {
                $failed++;
                continue;
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table bulk permanent delete; WordPress core APIs do not apply.
            $deleted = $wpdb->delete(self::table_name(), array('id' => $id), array('%d'));
            if ($deleted) {
                $success++;
            } else {
                $failed++;
            }
        }
        self::flush_application_cache();

        $notice = 'permanent_delete_failed';
        if ($success > 0 && $failed > 0) {
            $notice = 'bulk_permanent_delete_partial';
        } elseif ($success > 0) {
            $notice = 'bulk_permanent_delete_success';
        }
        wp_safe_redirect(add_query_arg(array(
            'page' => 'tcarm_applications',
            'tcarm_notice' => $notice,
            'deleted_count' => $success,
            'failed_count' => $failed,
        ), admin_url('admin.php')));
        exit;
    }

    private function admin_edit_protected_columns() {
        return array(
            'application_code',
            'status',
            'admin_note',
            'reject_reason',
            'request_note',
            'public_post_id',
            'resubmit_count',
            'submitted_ip',
            'user_agent',
            'history_json',
            'created_at',
            'reviewed_at',
            'last_resubmitted_at',
            'last_status_changed_at',
            'published_at',
        );
    }

    private function build_admin_application_content_update($data) {
        $allowed_columns = array_flip(self::application_db_columns());
        $protected = array_flip($this->admin_edit_protected_columns());
        $update = array(
            'form_data_json' => wp_json_encode($data),
            'updated_at' => current_time('mysql'),
        );
        foreach ($data as $key => $value) {
            if (!isset($allowed_columns[$key]) || isset($protected[$key]) || is_array($value)) {
                continue;
            }
            $update[$key] = $value;
        }
        return $update;
    }

    public function handle_admin_edit_application_content() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('You do not have permission.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized id is required to build the per-application nonce action checked immediately below.
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        if (!$id) {
            wp_die(esc_html__('Invalid request.', 'shinseiflow-application-review'));
        }
        check_admin_referer('tcarm_admin_edit_application_content_' . $id);
        $item = $this->get_application($id);
        if (!$item) {
            wp_die(esc_html__('Application not found.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Admin edit payload is sanitized by sanitize_application_data() immediately after nonce verification.
        $data = $this->sanitize_application_data($_POST);
        $file_errors = $this->process_file_uploads($data, $id);
        $errors = array_merge($this->validate_application_data($data), $file_errors);
        $detail_url = admin_url('admin.php?page=tcarm_applications&action=view&id=' . $id);
        if (!empty($errors)) {
            set_transient('tcarm_admin_edit_errors_' . $id . '_' . get_current_user_id(), $errors, 60);
            wp_safe_redirect(add_query_arg(array(
                'edit_content' => '1',
                'tcarm_content_edit' => 'error',
            ), $detail_url));
            exit;
        }

        global $wpdb;
        $update = $this->build_admin_application_content_update($data);
        $filtered_update = self::filter_db_data($update);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table content update; WordPress core APIs do not apply.
        $updated = $wpdb->update(self::table_name(), $filtered_update, array('id' => $id), $this->application_db_formats_for($filtered_update), array('%d'));
        self::flush_application_cache();
        if ($updated === false) {
            set_transient('tcarm_admin_edit_errors_' . $id . '_' . get_current_user_id(), array(__('Could not update the application content.', 'shinseiflow-application-review')), 60);
            wp_safe_redirect(add_query_arg(array(
                'edit_content' => '1',
                'tcarm_content_edit' => 'error',
            ), $detail_url));
            exit;
        }
        $this->append_application_history_entry($id, 'admin_edited_application_content', 'An administrator edited the application content.');
        wp_safe_redirect(add_query_arg('tcarm_content_edit', 'success', $detail_url));
        exit;
    }

    public function handle_admin_status() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_die(esc_html__('Permission denied.', 'shinseiflow-application-review'));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Sanitized id is required to build the per-application nonce action checked immediately below.
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        check_admin_referer('tcarm_update_status_' . $id);
        $new_status = isset($_POST['new_status']) ? sanitize_key(wp_unslash($_POST['new_status'])) : '';
        if (!array_key_exists($new_status, self::review_statuses())) {
            wp_die(esc_html__('Invalid status.', 'shinseiflow-application-review'));
        }
        global $wpdb;
        $old_item = $this->get_application($id);
        $old_status = $old_item && isset($old_item->status) ? (string) $old_item->status : '';
        $review_message = isset($_POST['review_message']) ? sanitize_textarea_field(wp_unslash($_POST['review_message'])) : '';
        $data = array(
            'status' => $new_status,
            'updated_at' => current_time('mysql'),
            'reviewed_at' => current_time('mysql'),
            'last_status_changed_at' => current_time('mysql'),
        );
        if ($new_status === 'rejected') {
            $data['reject_reason'] = $review_message;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required plugin-owned custom application table status update; WordPress core APIs do not apply.
        $wpdb->update(self::table_name(), $data, array('id' => $id), $this->application_db_formats_for($data), array('%d'));
        self::flush_application_cache();
        $item = $this->get_application($id);
        if ($item) {
            do_action('tcarm_application_status_changed', $id, $old_status, $new_status, $item);
            if ($new_status === 'approved') {
                do_action('tcarm_application_approved', $id, $item);
            }
        }
        if ($item && in_array($new_status, array('approved', 'rejected'), true)) {
            $this->append_application_history($id, $new_status);
            $template_key = $new_status === 'approved' ? 'approved' : 'rejected';
            $this->send_template_email($item->contact_email, $template_key, $item);
        }
        wp_safe_redirect(admin_url('admin.php?page=tcarm_applications&action=view&id=' . $id . '&updated=1'));
        exit;
    }

    private function get_counts() {
        global $wpdb;
        $table = self::table_name();
        $cache_key = self::application_cache_key(array('counts'));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return $cached;
        }
        $counts = array('total' => 0, 'pending' => 0, 'approved' => 0);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table count with object cache.
        $counts['total'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", $table));
        foreach (array('pending', 'approved') as $status) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table status count with object cache.
            $counts[$status] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE status = %s", $table, $status));
        }
        wp_cache_set($cache_key, $counts, self::application_cache_group(), self::application_cache_ttl());
        return $counts;
    }

    private function get_applications($status = '') {
        global $wpdb;
        $table = self::table_name();
        $cache_key = self::application_cache_key(array('applications', $status));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return $cached;
        }
        if ($status && array_key_exists($status, self::statuses())) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table list with object cache.
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE status = %s AND deleted_at IS NULL ORDER BY created_at DESC LIMIT %d", $table, $status, 200));
            wp_cache_set($cache_key, $items, self::application_cache_group(), self::application_cache_ttl());
            return $items;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table list with object cache.
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT %d", $table, 200));
        wp_cache_set($cache_key, $items, self::application_cache_group(), self::application_cache_ttl());
        return $items;
    }

    private function get_deleted_applications($limit = 50) {
        global $wpdb;
        $table = self::table_name();
        $limit = max(1, min(200, absint($limit)));
        $cache_key = self::application_cache_key(array('deleted_applications', $limit));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table deleted list with object cache.
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT %d", $table, $limit));
        wp_cache_set($cache_key, $items, self::application_cache_group(), self::application_cache_ttl());
        return $items;
    }

    private function get_deleted_by_label($item) {
        $deleted_by = isset($item->deleted_by) ? absint($item->deleted_by) : 0;
        if (!$deleted_by) {
            return '—';
        }
        $user = get_userdata($deleted_by);
        if ($user) {
            return $user->display_name ? $user->display_name : $user->user_login;
        }
        return 'User ID: ' . $deleted_by;
    }

    private function get_blocked_logs($limit = 20) {
        global $wpdb;
        $table = self::blocked_table_name();
        $limit = max(1, min(100, absint($limit)));
        $cache_key = self::application_cache_key(array('blocked_logs', $limit));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom blocked log table list with object cache.
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY created_at DESC LIMIT %d", $table, $limit));
        wp_cache_set($cache_key, $items, self::application_cache_group(), self::application_cache_ttl());
        return $items;
    }

    private function get_blocked_count($days = 7) {
        global $wpdb;
        $table = self::blocked_table_name();
        $days = max(1, absint($days));
        $cache_key = self::application_cache_key(array('blocked_count', $days, gmdate('YmdHi')));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return (int) $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom blocked log table count with short-lived object cache.
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i WHERE created_at >= DATE_SUB(%s, INTERVAL %d DAY)", $table, current_time('mysql'), $days));
        wp_cache_set($cache_key, $count, self::application_cache_group(), self::application_cache_ttl());
        return $count;
    }

    private function get_application($id) {
        global $wpdb;
        $id = absint($id);
        $cache_key = self::application_cache_key(array('application', $id));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table row lookup with object cache.
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", self::table_name(), $id));
        wp_cache_set($cache_key, $item, self::application_cache_group(), self::application_cache_ttl());
        return $item;
    }

    private function get_application_by_code_email($code, $email) {
        global $wpdb;
        $cache_key = self::application_cache_key(array('application_code_email', $code, md5(strtolower((string) $email))));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table applicant lookup with object cache.
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE application_code = %s AND contact_email = %s", self::table_name(), $code, $email));
        wp_cache_set($cache_key, $item, self::application_cache_group(), self::application_cache_ttl());
        return $item;
    }

    private static function application_db_columns() {
        return array(
            'application_code', 'applicant_name', 'contact_email', 'contact_phone', 'organization_name',
            'usage_purpose', 'usage_period', 'media', 'event_title', 'event_period', 'event_location',
            'event_contact', 'event_fee', 'event_available_time', 'related_link', 'genre', 'event_description',
            'status', 'admin_note', 'reject_reason', 'request_note', 'public_post_id', 'resubmit_count',
            'submitted_ip', 'user_agent', 'form_data_json', 'history_json', 'created_at', 'updated_at', 'reviewed_at',
            'last_resubmitted_at', 'last_status_changed_at', 'published_at'
        );
    }

    private static function filter_db_data($data) {
        $allowed = array_flip(self::application_db_columns());
        return array_filter(array_intersect_key($data, $allowed), function($value) {
            return !is_array($value);
        });
    }

    private function application_db_formats_for($data) {
        $int_fields = array('public_post_id', 'resubmit_count', 'deleted_by');
        $formats = array();
        foreach ($data as $key => $value) {
            $formats[] = in_array($key, $int_fields, true) ? '%d' : '%s';
        }
        return $formats;
    }
}
