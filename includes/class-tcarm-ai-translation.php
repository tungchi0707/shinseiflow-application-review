<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_AI_Translation_Trait {
    private function render_ai_translation_settings_card($settings) {
        $provider = $this->get_ai_provider($settings);
        $model = $this->get_ai_model($settings);
        $has_key = $this->get_ai_api_key($settings) !== '';
        ob_start();
        ?>
        <div class="tcarm-panel tcarm-card-panel tcarm-settings-card tcarm-admin-card tcarm-ai-translation-settings-card">
            <div class="tcarm-panel-header"><h2><?php echo esc_html__('AI Translation Settings', 'shinseiflow-application-review'); ?></h2><p><?php echo esc_html__('Configure the AI provider, API key, and model used by translation helper tools.', 'shinseiflow-application-review'); ?></p></div>
            <div class="tcarm-settings-card-body">
                <div class="tcarm-settings-row-list">
                    <label class="tcarm-settings-field"><?php echo esc_html__('AI Provider', 'shinseiflow-application-review'); ?>
                        <select name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[ai_provider]">
                            <option value="openai" <?php selected($provider, 'openai'); ?>>OpenAI</option>
                            <option value="gemini" <?php selected($provider, 'gemini'); ?>>Gemini</option>
                        </select>
                    </label>
                    <label class="tcarm-settings-field"><?php echo esc_html__('API Key', 'shinseiflow-application-review'); ?>
                        <input type="password" class="regular-text" autocomplete="new-password" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[ai_api_key]" value="" placeholder="<?php echo $has_key ? esc_attr__('Configured (enter only to change)', 'shinseiflow-application-review') : ''; ?>">
                        <span class="description"><?php echo esc_html__('Enter the API key for the selected AI provider. Saved API keys are not displayed.', 'shinseiflow-application-review'); ?></span>
                    </label>
                    <label class="tcarm-settings-field"><?php echo esc_html__('Model', 'shinseiflow-application-review'); ?>
                        <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION_SETTINGS); ?>[ai_model]" value="<?php echo esc_attr($model); ?>" placeholder="<?php echo esc_attr($provider === 'gemini' ? 'gemini-1.5-flash' : 'gpt-4o-mini'); ?>">
                    </label>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_ai_provider($settings = null) {
        $settings = is_array($settings) ? $settings : self::get_settings();
        $provider = isset($settings['ai_provider']) ? sanitize_key($settings['ai_provider']) : 'openai';
        return in_array($provider, array('openai', 'gemini'), true) ? $provider : 'openai';
    }

    private function get_ai_api_key($settings = null) {
        $settings = is_array($settings) ? $settings : self::get_settings();
        $raw_settings = get_option(self::OPTION_SETTINGS, array());
        $raw_settings = is_array($raw_settings) ? $raw_settings : array();
        if (!empty($raw_settings['ai_api_key'])) {
            return trim((string) $raw_settings['ai_api_key']);
        }
        if (!empty($raw_settings['openai_api_key'])) {
            return trim((string) $raw_settings['openai_api_key']);
        }
        if (!empty($settings['ai_api_key'])) {
            return trim((string) $settings['ai_api_key']);
        }
        return !empty($settings['openai_api_key']) ? trim((string) $settings['openai_api_key']) : '';
    }

    private function get_ai_model($settings = null) {
        $settings = is_array($settings) ? $settings : self::get_settings();
        $raw_settings = get_option(self::OPTION_SETTINGS, array());
        $raw_settings = is_array($raw_settings) ? $raw_settings : array();
        if (!empty($raw_settings['ai_model'])) {
            return trim((string) $raw_settings['ai_model']);
        }
        if (!empty($raw_settings['ai_translation_model'])) {
            return trim((string) $raw_settings['ai_translation_model']);
        }
        if (!empty($settings['ai_model'])) {
            return trim((string) $settings['ai_model']);
        }
        if (!empty($settings['ai_translation_model'])) {
            return trim((string) $settings['ai_translation_model']);
        }
        return $this->get_ai_provider($settings) === 'gemini' ? 'gemini-1.5-flash' : 'gpt-4o-mini';
    }

    public function ajax_ai_translate_strings() {
        if (!$this->current_user_can_manage_tcarm()) {
            wp_send_json_error(array('message' => __('You do not have permission.', 'shinseiflow-application-review')), 403);
        }
        check_ajax_referer('tcarm_ai_translate_strings', 'nonce');

        $settings = self::get_settings();
        $provider = $this->get_ai_provider($settings);
        $api_key = $this->get_ai_api_key($settings);
        if ($api_key === '') {
            wp_send_json_error(array('message' => __('Please configure the API key for the selected AI provider.', 'shinseiflow-application-review')), 400);
        }

        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field(wp_unslash((string) $_POST['target_lang'])) : '';
        $allowed_targets = array_keys(self::get_enabled_languages(false));
        $allowed_targets = array_values(array_diff($allowed_targets, array('ja')));
        if (!in_array($target_lang, $allowed_targets, true)) {
            wp_send_json_error(array('message' => __('There are no target languages for translation.', 'shinseiflow-application-review')), 400);
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Source array is unslashed here and each key/value is sanitized and length-limited immediately below.
        $source = isset($_POST['source']) && is_array($_POST['source']) ? wp_unslash($_POST['source']) : array();
        $clean_source = array();
        $total_length = 0;
        foreach ($source as $key => $value) {
            if (count($clean_source) >= 100) {
                break;
            }
            $key = sanitize_text_field((string) $key);
            $key = substr($key, 0, 120);
            $value = sanitize_text_field((string) $value);
            $value = function_exists('mb_substr') ? mb_substr($value, 0, 2000) : substr($value, 0, 2000);
            $total_length += function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
            if ($total_length > 20000) {
                break;
            }
            if ($key !== '' && $value !== '') {
                $clean_source[$key] = $value;
            }
        }
        if (empty($clean_source)) {
            wp_send_json_error(array('message' => __('There are no empty fields to translate.', 'shinseiflow-application-review')), 400);
        }

        $model = $this->get_ai_model($settings);
        $translations = $provider === 'gemini'
            ? $this->translate_with_gemini($api_key, $model, $clean_source, $target_lang)
            : $this->translate_with_openai($api_key, $model, $clean_source, $target_lang);
        if (is_wp_error($translations)) {
            wp_send_json_error(array('message' => __('Failed to call the AI translation API. Please check your settings.', 'shinseiflow-application-review')), 500);
        }

        wp_send_json_success(array('translations' => $translations));
    }

    private function ai_target_language_labels($target_lang) {
        $all_targets = array(
            'en' => 'English',
            'zh-Hant' => 'Traditional Chinese written for general Chinese readers, using zh-Hant script',
            'zh-Hans' => 'Simplified Chinese written for general Chinese readers, using zh-Hans script',
            'ko' => 'Korean',
        );
        return isset($all_targets[$target_lang]) ? array($target_lang => $all_targets[$target_lang]) : array();
    }

    private function ai_translation_prompt_payload($source, $target_lang) {
        $targets = $this->ai_target_language_labels($target_lang);
        return array(
            'target_languages' => $targets,
            'source_language' => 'ja',
            'source_strings' => $source,
            'required_json_shape' => array_fill_keys(array_keys($targets), new stdClass()),
        );
    }

    private function translate_with_openai($api_key, $model, $source, $target_lang = '') {
        $targets = $this->ai_target_language_labels($target_lang);
        if (empty($targets)) {
            return new WP_Error('tcarm_ai_target_error', __('Invalid target language.', 'shinseiflow-application-review'));
        }
        $system = 'You translate Japanese website UI labels for an application management system. Return only valid JSON. Preserve keys exactly. Do not add explanations. Keep translations concise and natural for form labels, buttons, placeholders, and short messages.';
        $body = array(
            'model' => $model,
            'input' => array(
                array('role' => 'system', 'content' => $system),
                array('role' => 'user', 'content' => wp_json_encode($this->ai_translation_prompt_payload($source, $target_lang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            ),
            'temperature' => 0.2,
        );

        $response = wp_remote_post('https://api.openai.com/v1/responses', array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ));

        if (is_wp_error($response)) {
            return new WP_Error('tcarm_openai_request_error', __('AI translation request failed.', 'shinseiflow-application-review'));
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('tcarm_openai_error', __('AI translation API returned an error.', 'shinseiflow-application-review'));
        }

        $json = json_decode($raw, true);
        $text = '';
        if (is_array($json) && isset($json['output_text'])) {
            $text = (string) $json['output_text'];
        }
        if ($text === '' && is_array($json) && isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $item) {
                if (empty($item['content']) || !is_array($item['content'])) {
                    continue;
                }
                foreach ($item['content'] as $content) {
                    if (isset($content['text'])) {
                        $text .= (string) $content['text'];
                    }
                }
            }
        }
        return $this->parse_ai_translation_json($text, $source, array_keys($targets));
    }

    private function translate_with_gemini($api_key, $model, $source, $target_lang = '') {
        $targets = $this->ai_target_language_labels($target_lang);
        if (empty($targets)) {
            return new WP_Error('tcarm_ai_target_error', __('Invalid target language.', 'shinseiflow-application-review'));
        }
        $prompt = "Translate Japanese website UI labels for an application management system. Return only valid JSON. Preserve keys exactly. Do not add explanations.\n\n" . wp_json_encode($this->ai_translation_prompt_payload($source, $target_lang), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $body = array(
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => array(
                        array('text' => $prompt),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature' => 0.2,
            ),
        );
        $endpoint = add_query_arg(
            array('key' => $api_key),
            'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent'
        );
        $response = wp_remote_post($endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ));

        if (is_wp_error($response)) {
            return new WP_Error('tcarm_gemini_request_error', __('AI translation request failed.', 'shinseiflow-application-review'));
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('tcarm_gemini_error', __('AI translation API returned an error.', 'shinseiflow-application-review'));
        }
        $json = json_decode($raw, true);
        $text = '';
        if (!empty($json['candidates']) && is_array($json['candidates'])) {
            foreach ($json['candidates'] as $candidate) {
                if (empty($candidate['content']['parts']) || !is_array($candidate['content']['parts'])) {
                    continue;
                }
                foreach ($candidate['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $text .= (string) $part['text'];
                    }
                }
            }
        }
        return $this->parse_ai_translation_json($text, $source, array_keys($targets));
    }

    private function parse_ai_translation_json($text, $source, $langs) {
        $text = trim((string) $text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            return new WP_Error('tcarm_ai_parse_error', __('Could not read the AI translation result. Please try again.', 'shinseiflow-application-review'));
        }

        $out = array();
        foreach ($langs as $lang) {
            $out[$lang] = array();
            if (empty($decoded[$lang]) || !is_array($decoded[$lang])) {
                continue;
            }
            foreach ($source as $key => $value) {
                if (isset($decoded[$lang][$key])) {
                    $out[$lang][$key] = sanitize_text_field((string) $decoded[$lang][$key]);
                }
            }
        }
        return $out;
    }
}
