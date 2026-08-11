<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Translations_Trait {
    private static function supported_languages() {
        return array(
            'ja' => '日本語',
            'en' => 'English',
            'zh-Hant' => '繁體中文',
            'zh-Hans' => '簡體中文',
            'ko' => '한국어',
        );
    }

    private static function get_default_enabled_languages() {
        return array(self::get_default_base_language());
    }

    private static function language_from_locale($locale) {
        $locale = trim((string) $locale);
        if ($locale === '') {
            return 'en';
        }
        $normalized = str_replace('-', '_', $locale);
        if (strpos($normalized, 'ja') === 0) {
            return 'ja';
        }
        if (strpos($normalized, 'en') === 0) {
            return 'en';
        }
        if (in_array($normalized, array('zh_TW', 'zh_HK', 'zh_Hant'), true)) {
            return 'zh-Hant';
        }
        if (in_array($normalized, array('zh_CN', 'zh_SG', 'zh_Hans'), true)) {
            return 'zh-Hans';
        }
        if (strpos($normalized, 'ko') === 0) {
            return 'ko';
        }
        return 'en';
    }

    private static function get_default_base_language() {
        $locale = function_exists('get_locale') ? (string) get_locale() : '';
        return self::language_from_locale($locale);
    }

    private static function sanitize_base_language($language) {
        $language = (string) $language;
        $supported = self::supported_languages();
        return isset($supported[$language]) ? $language : 'en';
    }

    private static function get_base_language($settings = null) {
        if (!is_array($settings)) {
            $settings = get_option(self::OPTION_SETTINGS, array());
        }
        if (isset($settings['base_language'])) {
            return self::sanitize_base_language($settings['base_language']);
        }
        return 'en';
    }

    private static function sanitize_enabled_languages($value) {
        $supported = self::supported_languages();
        $out = array();
        foreach ((array) $value as $lang) {
            $lang = (string) $lang;
            if (isset($supported[$lang])) {
                $out[] = $lang;
            }
        }
        $out = array_values(array_unique($out));
        return !empty($out) ? $out : array('en');
    }

    private static function get_enabled_languages($include_source = false) {
        $settings = get_option(self::OPTION_SETTINGS, array());
        $enabled = isset($settings['enabled_languages']) ? self::sanitize_enabled_languages($settings['enabled_languages']) : self::get_default_enabled_languages();
        $base_language = self::get_base_language($settings);
        if (!in_array($base_language, $enabled, true)) {
            $enabled[] = $base_language;
        }
        $supported = self::supported_languages();
        $out = array();
        foreach ($enabled as $lang) {
            if (isset($supported[$lang])) {
                $out[$lang] = $supported[$lang];
            }
        }
        return $out;
    }

    private function normalize_language_code($lang) {
        $lang = trim((string) $lang);
        if ($lang === '') {
            return '';
        }
        $aliases = array(
            'jp' => 'ja',
            'zh' => 'zh-Hant',
            'zh_TW' => 'zh-Hant',
            'zh-tw' => 'zh-Hant',
            'zh_HK' => 'zh-Hant',
            'zh-hk' => 'zh-Hant',
            'zh_CN' => 'zh-Hans',
            'zh-cn' => 'zh-Hans',
            'zh_SG' => 'zh-Hans',
            'zh-sg' => 'zh-Hans',
            'ko_KR' => 'ko',
            'ko-kr' => 'ko',
            'en_US' => 'en',
            'en-us' => 'en',
            'ja_JP' => 'ja',
            'ja-jp' => 'ja',
        );
        if (isset($aliases[$lang])) {
            $lang = $aliases[$lang];
        }
        if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $lang)) {
            $lang = str_replace('_', '-', $lang);
        }
        $supported = self::supported_languages();
        return isset($supported[$lang]) ? $lang : '';
    }

    private function get_request_language() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Language selector is read-only display state and does not modify data.
        $posted = isset($_POST['tcarm_lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_POST['tcarm_lang']))) : '';
        if ($posted !== '') {
            return $posted;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Language query is read-only display state and does not modify data.
        $query = isset($_GET['lang']) ? $this->normalize_language_code(sanitize_text_field(wp_unslash($_GET['lang']))) : '';
        if ($query !== '') {
            return $query;
        }
        if (function_exists('pll_current_language')) {
            $pll = $this->normalize_language_code((string) pll_current_language('slug'));
            if ($pll !== '') {
                return $pll;
            }
        }
        return 'ja';
    }

    private function set_current_language_from_shortcode($atts = array()) {
        $atts = shortcode_atts(array('lang' => ''), is_array($atts) ? $atts : array(), 'tcarm_application');
        $lang = $this->normalize_language_code(isset($atts['lang']) ? $atts['lang'] : '');
        if ($lang === '') {
            $lang = $this->get_request_language();
        }
        $this->current_frontend_lang = $lang ?: 'ja';
        return $this->current_frontend_lang;
    }

    private function normalize_shortcode_yes_no($value, $default = true) {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return (bool) $default;
        }
        if (in_array($value, array('yes', 'true', '1', 'on'), true)) {
            return true;
        }
        if (in_array($value, array('no', 'false', '0', 'off'), true)) {
            return false;
        }
        return (bool) $default;
    }

    private function set_current_form_options_from_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'lang' => '',
            'show_steps' => 'yes',
        ), is_array($atts) ? $atts : array(), 'tcarm_form');
        $this->set_current_language_from_shortcode($atts);
        $this->current_frontend_show_steps = $this->normalize_shortcode_yes_no(isset($atts['show_steps']) ? $atts['show_steps'] : 'yes', true);
    }

    private function should_show_frontend_steps() {
        return (bool) $this->current_frontend_show_steps;
    }

    private function current_language() {
        if ($this->current_frontend_lang !== '') {
            return $this->current_frontend_lang;
        }
        return $this->get_request_language();
    }

    private function translated_field_text($field, $prop, $lang = '') {
        $base = isset($field[$prop]) ? (string) $field[$prop] : '';
        $lang = $this->normalize_language_code($lang !== '' ? $lang : $this->current_language());
        if ($lang !== '' && $lang !== 'ja' && !empty($field['translations'][$lang]) && is_array($field['translations'][$lang])) {
            $translated = isset($field['translations'][$lang][$prop]) ? (string) $field['translations'][$lang][$prop] : '';
            if ($translated !== '') {
                return $translated;
            }
        }
        return $base;
    }

    private function apply_field_translation($field, $lang = '') {
        if (!is_array($field)) {
            return $field;
        }
        foreach (array('label', 'placeholder', 'description') as $prop) {
            $field[$prop] = $this->translated_field_text($field, $prop, $lang);
        }
        return $field;
    }

    private function translated_consent_text($consent, $prop, $lang = '') {
        $base = isset($consent[$prop]) ? (string) $consent[$prop] : '';
        $lang = $this->normalize_language_code($lang !== '' ? $lang : $this->current_language());
        if ($lang !== '' && $lang !== 'ja' && !empty($consent['translations'][$lang]) && is_array($consent['translations'][$lang])) {
            $translated = isset($consent['translations'][$lang][$prop]) ? (string) $consent['translations'][$lang][$prop] : '';
            if ($translated !== '') {
                return $translated;
            }
        }
        return $base;
    }

    private function apply_consent_translation($consent, $lang = '') {
        if (!is_array($consent)) {
            return $consent;
        }
        foreach (array('label', 'body', 'checkbox_text', 'link_text') as $prop) {
            $consent[$prop] = $this->translated_consent_text($consent, $prop, $lang);
        }
        return $consent;
    }

    private function translated_section_label($section_key, $lang = '') {
        $sections = self::get_sections();
        $key = self::normalize_section_key($section_key);
        $base = isset($sections[$key]['label']) ? (string) $sections[$key]['label'] : (string) $section_key;
        $lang = $this->normalize_language_code($lang !== '' ? $lang : $this->current_language());
        if ($lang !== '' && $lang !== 'ja' && !empty($sections[$key]['translations'][$lang]) && is_array($sections[$key]['translations'][$lang])) {
            $translated = isset($sections[$key]['translations'][$lang]['label']) ? (string) $sections[$key]['translations'][$lang]['label'] : '';
            if ($translated !== '') {
                return $translated;
            }
        }
        return $base;
    }

    private function should_add_lang_query($lang) {
        return $this->normalize_language_code($lang) !== '' && $lang !== 'ja';
    }

    private function add_lang_to_url($url, $lang = '') {
        $lang = $this->normalize_language_code($lang ?: $this->current_language());
        if (!$url || !$this->should_add_lang_query($lang)) {
            return $url;
        }
        return add_query_arg(array('lang' => $lang), $url);
    }

    private static function default_translation_strings() {
        return array(
            'common.next' => 'Next',
            'common.back' => 'Back',
            'common.submit' => 'Submit',
            'common.top' => 'Back to top',
            'common.check_status' => 'Check application status',
            'common.check_other_status' => 'Check another application status',
            'common.back_to_status' => 'Back to application status',
            'common.recheck_status' => 'Check application status again',
            'common.edit_and_resubmit' => 'Edit and resubmit',
            'common.view_submitted_content' => 'View submitted content',
            'common.review_input' => 'Review your input',
            'common.edit_content' => 'Edit',
            'common.resubmit' => 'Resubmit edited content',
            'common.move' => 'Move',
            'common.download' => 'Download',
            'common.application_number' => 'Application Number',
            'common.current_status' => 'Current Status',
            'common.rejection_reason' => 'Rejection Reason',
            'common.application_status' => 'Application Status',
            'common.application_status_check' => 'Application Status Check',
            'common.application_edit' => 'Edit Application Content',
            'common.application_view' => 'View Application Content',
            'common.completed' => 'Submission Complete',
            'common.submitted_content' => 'Submitted Content',
            'common.contact_email' => 'Contact Email',
            'common.sent_at' => 'Submitted At',
            'common.updated_at' => 'Updated At',
            'common.resubmit_count' => 'Resubmissions',
            'common.times' => 'times',
            'common.consent_items' => 'Consent Items',
            'common.consent_agreed' => 'Agreed',
            'common.required' => 'Required',
            'common.select_placeholder' => 'Please select',
            'form.upload_help_prefix' => 'Allowed uploads',
            'form.upload_help_max' => 'Maximum',
            'form.upload_help_until' => 'files',
            'form.title' => 'Application',
            'form.description' => 'Enter the required information, review your input, and submit the form.',
            'confirm.description' => 'Review your input and submit the form.',
            'complete.received_title' => 'Application received',
            'complete.received_description' => 'A confirmation email has been sent. We will contact you again after reviewing your application.',
            'complete.resubmitted_title' => 'Resubmission received',
            'complete.resubmitted_description' => 'Your changes have been saved and returned to pending review.',
            'status.pending' => 'Pending Review',
            'status.approved' => 'Approved',
            'status.rejected' => 'Rejected',
            'status.published' => 'Published',
            'status.needs_more' => 'Additional Information Requested',
            'status.check_result' => 'View result',
            'status.lookup_description' => 'Enter your application number and email address to check the current status.',
            'status.not_found' => 'No matching application was found.',
            'status.retry_later' => 'Please try again later.',
            'status.turnstile_failed' => 'Robot prevention verification failed. Please try again.',
            'status.view_empty_title' => 'View Submitted Content',
            'status.view_empty_description' => 'To view submitted content, access it from the application status page.',
            'status.edit_empty_title' => 'Edit and Resubmit',
            'status.edit_empty_description' => 'Please edit and resubmit from the application status page.',
            'status.token_expired' => 'The verification link has expired. Enter your application number and email address to check again.',
            'edit.description' => 'You can edit and resubmit a rejected application. After submission, the status returns to pending review.',
            'edit.cannot_edit' => 'This application cannot be edited at this time.',
            'edit.confirmation_note' => 'Additional Information',
            'redirect.description' => 'Redirecting you now. If you are not redirected automatically, use the button below.',
            'download.title' => 'Download Files',
            'download.description' => 'Approved applicants can download available files.',
            'steps.step1.label' => 'STEP 1',
            'steps.step1.title' => 'Input',
            'steps.step2.label' => 'STEP 2',
            'steps.step2.title' => 'Review',
            'steps.step3.label' => 'STEP 3',
            'steps.step3.title' => 'Submission Complete',
            'steps.step4.label' => 'STEP 4',
            'steps.step4.title' => 'Admin Review',
        );
    }

    private static function default_japanese_translation_strings() {
        return array(
            'common.next' => '次へ',
            'common.back' => '戻る',
            'common.submit' => '送信',
            'common.top' => 'トップへ戻る',
            'common.check_status' => '申請状況を確認',
            'common.check_other_status' => '別の申請状況を確認',
            'common.back_to_status' => '申請状況へ戻る',
            'common.recheck_status' => '申請状況を再確認',
            'common.edit_and_resubmit' => '修正して再申請',
            'common.view_submitted_content' => '申請内容を確認',
            'common.review_input' => '入力内容を確認',
            'common.edit_content' => '編集',
            'common.resubmit' => '修正内容を再申請',
            'common.move' => '移動',
            'common.download' => 'ダウンロード',
            'common.application_number' => '申請番号',
            'common.current_status' => '現在のステータス',
            'common.rejection_reason' => '不許可理由',
            'common.application_status' => '申請状況',
            'common.application_status_check' => '申請状況確認',
            'common.application_edit' => '申請内容を編集',
            'common.application_view' => '申請内容を確認',
            'common.completed' => '送信完了',
            'common.submitted_content' => '申請内容',
            'common.contact_email' => 'メールアドレス',
            'common.sent_at' => '申請日時',
            'common.updated_at' => '更新日時',
            'common.resubmit_count' => '再申請回数',
            'common.times' => '回',
            'common.consent_items' => '同意項目',
            'common.consent_agreed' => '同意済み',
            'common.required' => '必須',
            'common.select_placeholder' => '選択してください',
            'form.upload_help_prefix' => 'アップロード可能',
            'form.upload_help_max' => '最大',
            'form.upload_help_until' => 'ファイル',
            'form.title' => '申請',
            'form.description' => '必要事項を入力し、内容を確認して送信してください。',
            'confirm.description' => '入力内容を確認し、送信してください。',
            'complete.received_title' => '申請を受け付けました',
            'complete.received_description' => '確認メールを送信しました。申請内容の審査後、改めてご連絡します。',
            'complete.resubmitted_title' => '再申請を受け付けました',
            'complete.resubmitted_description' => '修正内容を保存し、審査待ちに戻しました。',
            'status.pending' => '審査待ち',
            'status.approved' => '許可済み',
            'status.rejected' => '不許可',
            'status.published' => '公開済み',
            'status.needs_more' => '追加確認依頼',
            'status.check_result' => '結果を確認',
            'status.lookup_description' => '申請番号とメールアドレスを入力して、現在の申請状況を確認してください。',
            'status.not_found' => '該当する申請が見つかりませんでした。',
            'status.retry_later' => 'しばらくしてからもう一度お試しください。',
            'status.turnstile_failed' => 'ロボット申請防止の確認に失敗しました。もう一度お試しください。',
            'status.view_empty_title' => '申請内容を確認',
            'status.view_empty_description' => '申請内容を確認するには、申請状況ページからアクセスしてください。',
            'status.edit_empty_title' => '修正再申請',
            'status.edit_empty_description' => '申請状況ページから修正して再申請してください。',
            'status.token_expired' => '確認リンクの有効期限が切れています。申請番号とメールアドレスを入力して、もう一度確認してください。',
            'edit.description' => '不許可となった申請を修正して再申請できます。送信後、ステータスは審査待ちに戻ります。',
            'edit.cannot_edit' => '現在、この申請は編集できません。',
            'edit.confirmation_note' => '追加情報',
            'redirect.description' => '移動しています。自動的に移動しない場合は、下のボタンを押してください。',
            'download.title' => 'ダウンロードファイル',
            'download.description' => '許可された申請者は、利用可能なファイルをダウンロードできます。',
            'steps.step1.label' => 'STEP 1',
            'steps.step1.title' => '入力',
            'steps.step2.label' => 'STEP 2',
            'steps.step2.title' => '内容確認',
            'steps.step3.label' => 'STEP 3',
            'steps.step3.title' => '送信完了',
            'steps.step4.label' => 'STEP 4',
            'steps.step4.title' => '管理者審査',
        );
    }

    private static function default_traditional_chinese_translation_strings() {
        return array(
            'common.next' => '下一步',
            'common.back' => '返回',
            'common.submit' => '送出',
            'common.top' => '返回頁首',
            'common.check_status' => '查詢申請狀態',
            'common.check_other_status' => '查詢其他申請狀態',
            'common.back_to_status' => '返回申請狀態',
            'common.recheck_status' => '再次查詢申請狀態',
            'common.edit_and_resubmit' => '編輯並重新送出',
            'common.view_submitted_content' => '查看已送出內容',
            'common.review_input' => '確認輸入內容',
            'common.edit_content' => '編輯',
            'common.resubmit' => '重新送出修改內容',
            'common.move' => '前往',
            'common.download' => '下載',
            'common.application_number' => '申請編號',
            'common.current_status' => '目前狀態',
            'common.rejection_reason' => '不許可原因',
            'common.application_status' => '申請狀態',
            'common.application_status_check' => '申請狀態查詢',
            'common.application_edit' => '編輯申請內容',
            'common.application_view' => '查看申請內容',
            'common.completed' => '送出完成',
            'common.submitted_content' => '已送出內容',
            'common.contact_email' => '聯絡電子郵件',
            'common.sent_at' => '送出時間',
            'common.updated_at' => '更新時間',
            'common.resubmit_count' => '重新申請次數',
            'common.times' => '次',
            'common.consent_items' => '同意項目',
            'common.consent_agreed' => '已同意',
            'common.required' => '必填',
            'common.select_placeholder' => '請選擇',
            'form.upload_help_prefix' => '允許上傳的檔案類型',
            'form.upload_help_max' => '單一檔案上限',
            'form.upload_help_until' => '個檔案以內',
            'form.title' => '申請',
            'form.description' => '請填寫必要資訊，確認內容後送出表單。',
            'confirm.description' => '請確認輸入內容後送出表單。',
            'complete.received_title' => '已收到申請',
            'complete.received_description' => '確認郵件已寄出。我們會在審核申請後再次與您聯絡。',
            'complete.resubmitted_title' => '已收到重新申請',
            'complete.resubmitted_description' => '修改內容已儲存，狀態已返回待審核。',
            'status.pending' => '待審核',
            'status.approved' => '已許可',
            'status.rejected' => '不許可',
            'status.published' => '已公開',
            'status.needs_more' => '需追加確認',
            'status.check_result' => '查看結果',
            'status.lookup_description' => '請輸入申請編號與電子郵件地址，以查詢目前的申請狀態。',
            'status.not_found' => '找不到符合條件的申請。',
            'status.retry_later' => '請稍後再試。',
            'status.turnstile_failed' => '機器人防護驗證失敗。請再試一次。',
            'status.view_empty_title' => '查看已送出內容',
            'status.view_empty_description' => '若要查看已送出內容，請從申請狀態頁面進入。',
            'status.edit_empty_title' => '編輯並重新送出',
            'status.edit_empty_description' => '請從申請狀態頁面編輯並重新送出。',
            'status.token_expired' => '驗證連結已過期。請輸入申請編號與電子郵件地址再次查詢。',
            'edit.description' => '您可以編輯不許可的申請並重新送出。送出後，狀態將返回待審核。',
            'edit.cannot_edit' => '目前無法編輯此申請。',
            'edit.confirmation_note' => '補充資訊',
            'redirect.description' => '正在前往下一頁。如未自動前往，請使用下方按鈕。',
            'download.title' => '下載檔案',
            'download.description' => '已獲許可的申請者可以下載可用的檔案。',
            'steps.step1.label' => 'STEP 1',
            'steps.step1.title' => '填寫',
            'steps.step2.label' => 'STEP 2',
            'steps.step2.title' => '確認內容',
            'steps.step3.label' => 'STEP 3',
            'steps.step3.title' => '送出完成',
            'steps.step4.label' => 'STEP 4',
            'steps.step4.title' => '管理員審核',
        );
    }

    private static function default_simplified_chinese_translation_strings() {
        return array(
            'common.next' => '下一步',
            'common.back' => '返回',
            'common.submit' => '提交',
            'common.top' => '返回顶部',
            'common.check_status' => '查询申请状态',
            'common.check_other_status' => '查询其他申请状态',
            'common.back_to_status' => '返回申请状态',
            'common.recheck_status' => '再次查询申请状态',
            'common.edit_and_resubmit' => '编辑并重新提交',
            'common.view_submitted_content' => '查看已提交内容',
            'common.review_input' => '确认输入内容',
            'common.edit_content' => '编辑',
            'common.resubmit' => '重新提交修改内容',
            'common.move' => '前往',
            'common.download' => '下载',
            'common.application_number' => '申请编号',
            'common.current_status' => '当前状态',
            'common.rejection_reason' => '未批准原因',
            'common.application_status' => '申请状态',
            'common.application_status_check' => '申请状态查询',
            'common.application_edit' => '编辑申请内容',
            'common.application_view' => '查看申请内容',
            'common.completed' => '提交完成',
            'common.submitted_content' => '已提交内容',
            'common.contact_email' => '联系邮箱',
            'common.sent_at' => '提交时间',
            'common.updated_at' => '更新时间',
            'common.resubmit_count' => '重新提交次数',
            'common.times' => '次',
            'common.consent_items' => '同意事项',
            'common.consent_agreed' => '已同意',
            'common.required' => '必填',
            'common.select_placeholder' => '请选择',
            'form.upload_help_prefix' => '允许上传的文件类型',
            'form.upload_help_max' => '单个文件上限',
            'form.upload_help_until' => '个文件以内',
            'form.title' => '申请',
            'form.description' => '请填写必要信息，确认内容后提交表单。',
            'confirm.description' => '请确认输入内容后提交表单。',
            'complete.received_title' => '已收到申请',
            'complete.received_description' => '确认邮件已发送。我们将在审核申请后再次与您联系。',
            'complete.resubmitted_title' => '已收到重新提交的申请',
            'complete.resubmitted_description' => '修改内容已保存，状态已返回待审核。',
            'status.pending' => '待审核',
            'status.approved' => '已批准',
            'status.rejected' => '未批准',
            'status.published' => '已发布',
            'status.needs_more' => '需要补充信息',
            'status.check_result' => '查看结果',
            'status.lookup_description' => '请输入申请编号和电子邮箱地址，以查询当前申请状态。',
            'status.not_found' => '未找到符合条件的申请。',
            'status.retry_later' => '请稍后重试。',
            'status.turnstile_failed' => '机器人防护验证失败。请重试。',
            'status.view_empty_title' => '查看已提交内容',
            'status.view_empty_description' => '如需查看已提交内容，请从申请状态页面进入。',
            'status.edit_empty_title' => '编辑并重新提交',
            'status.edit_empty_description' => '请从申请状态页面编辑并重新提交。',
            'status.token_expired' => '验证链接已过期。请输入申请编号和电子邮箱地址再次查询。',
            'edit.description' => '您可以编辑未批准的申请并重新提交。提交后，状态将返回待审核。',
            'edit.cannot_edit' => '当前无法编辑此申请。',
            'edit.confirmation_note' => '补充信息',
            'redirect.description' => '正在跳转。如未自动跳转，请使用下方按钮。',
            'download.title' => '下载文件',
            'download.description' => '已获批准的申请人可以下载可用文件。',
            'steps.step1.label' => 'STEP 1',
            'steps.step1.title' => '填写',
            'steps.step2.label' => 'STEP 2',
            'steps.step2.title' => '确认内容',
            'steps.step3.label' => 'STEP 3',
            'steps.step3.title' => '提交完成',
            'steps.step4.label' => 'STEP 4',
            'steps.step4.title' => '管理员审核',
        );
    }

    private static function default_korean_translation_strings() {
        return array(
            'common.next' => '다음',
            'common.back' => '돌아가기',
            'common.submit' => '제출',
            'common.top' => '맨 위로',
            'common.check_status' => '신청 상태 확인',
            'common.check_other_status' => '다른 신청 상태 확인',
            'common.back_to_status' => '신청 상태로 돌아가기',
            'common.recheck_status' => '신청 상태 다시 확인',
            'common.edit_and_resubmit' => '수정 후 다시 제출',
            'common.view_submitted_content' => '제출 내용 보기',
            'common.review_input' => '입력 내용 확인',
            'common.edit_content' => '수정',
            'common.resubmit' => '수정 내용 다시 제출',
            'common.move' => '이동',
            'common.download' => '다운로드',
            'common.application_number' => '신청 번호',
            'common.current_status' => '현재 상태',
            'common.rejection_reason' => '반려 사유',
            'common.application_status' => '신청 상태',
            'common.application_status_check' => '신청 상태 조회',
            'common.application_edit' => '신청 내용 수정',
            'common.application_view' => '신청 내용 보기',
            'common.completed' => '제출 완료',
            'common.submitted_content' => '제출 내용',
            'common.contact_email' => '연락처 이메일',
            'common.sent_at' => '제출 일시',
            'common.updated_at' => '업데이트 일시',
            'common.resubmit_count' => '재제출 횟수',
            'common.times' => '회',
            'common.consent_items' => '동의 항목',
            'common.consent_agreed' => '동의함',
            'common.required' => '필수',
            'common.select_placeholder' => '선택해 주세요',
            'form.upload_help_prefix' => '업로드 가능 파일 형식',
            'form.upload_help_max' => '파일당 최대',
            'form.upload_help_until' => '개 파일까지',
            'form.title' => '신청',
            'form.description' => '필수 정보를 입력하고 내용을 확인한 후 양식을 제출해 주세요.',
            'confirm.description' => '입력 내용을 확인한 후 양식을 제출해 주세요.',
            'complete.received_title' => '신청이 접수되었습니다',
            'complete.received_description' => '확인 이메일을 보냈습니다. 신청 내용을 심사한 후 다시 연락드리겠습니다.',
            'complete.resubmitted_title' => '재제출이 접수되었습니다',
            'complete.resubmitted_description' => '수정 내용이 저장되었으며 심사 대기 상태로 돌아갔습니다.',
            'status.pending' => '심사 대기',
            'status.approved' => '승인됨',
            'status.rejected' => '반려됨',
            'status.published' => '게시됨',
            'status.needs_more' => '추가 정보 요청',
            'status.check_result' => '결과 보기',
            'status.lookup_description' => '신청 번호와 이메일 주소를 입력하여 현재 신청 상태를 확인해 주세요.',
            'status.not_found' => '일치하는 신청을 찾을 수 없습니다.',
            'status.retry_later' => '잠시 후 다시 시도해 주세요.',
            'status.turnstile_failed' => '로봇 방지 인증에 실패했습니다. 다시 시도해 주세요.',
            'status.view_empty_title' => '제출 내용 보기',
            'status.view_empty_description' => '제출 내용을 보려면 신청 상태 페이지에서 접속해 주세요.',
            'status.edit_empty_title' => '수정 후 다시 제출',
            'status.edit_empty_description' => '신청 상태 페이지에서 내용을 수정한 후 다시 제출해 주세요.',
            'status.token_expired' => '확인 링크가 만료되었습니다. 신청 번호와 이메일 주소를 입력하여 다시 확인해 주세요.',
            'edit.description' => '반려된 신청을 수정하여 다시 제출할 수 있습니다. 제출 후 상태는 심사 대기로 돌아갑니다.',
            'edit.cannot_edit' => '현재 이 신청은 수정할 수 없습니다.',
            'edit.confirmation_note' => '추가 정보',
            'redirect.description' => '이동 중입니다. 자동으로 이동하지 않으면 아래 버튼을 눌러 주세요.',
            'download.title' => '파일 다운로드',
            'download.description' => '승인된 신청자는 제공되는 파일을 다운로드할 수 있습니다.',
            'steps.step1.label' => 'STEP 1',
            'steps.step1.title' => '입력',
            'steps.step2.label' => 'STEP 2',
            'steps.step2.title' => '내용 확인',
            'steps.step3.label' => 'STEP 3',
            'steps.step3.title' => '제출 완료',
            'steps.step4.label' => 'STEP 4',
            'steps.step4.title' => '관리자 심사',
        );
    }

    private static function default_translation_strings_for_language($language) {
        switch ((string) $language) {
            case 'en':
                return self::default_translation_strings();
            case 'ja':
                return self::default_japanese_translation_strings();
            case 'zh-Hant':
                return self::default_traditional_chinese_translation_strings();
            case 'zh-Hans':
                return self::default_simplified_chinese_translation_strings();
            case 'ko':
                return self::default_korean_translation_strings();
            default:
                return array();
        }
    }

    private static function migrate_japanese_translation_defaults() {
        $stored = get_option(self::OPTION_TRANSLATIONS, array());
        if (!is_array($stored)) {
            return false;
        }

        $japanese = isset($stored['ja']) && is_array($stored['ja']) ? $stored['ja'] : array();
        $english_defaults = self::default_translation_strings();
        $japanese_defaults = self::default_japanese_translation_strings();
        $changed = false;

        foreach ($japanese_defaults as $key => $japanese_default) {
            if (!array_key_exists($key, $japanese)) {
                $japanese[$key] = $japanese_default;
                $changed = true;
                continue;
            }

            if (is_string($japanese[$key]) && $japanese[$key] === $english_defaults[$key]) {
                $japanese[$key] = $japanese_default;
                $changed = true;
            }
        }

        if (!$changed) {
            return true;
        }

        $stored['ja'] = $japanese;
        if (update_option(self::OPTION_TRANSLATIONS, $stored)) {
            return true;
        }

        return get_option(self::OPTION_TRANSLATIONS, array()) === $stored;
    }

    private static function migrate_new_language_translation_defaults() {
        $stored = get_option(self::OPTION_TRANSLATIONS, array());
        if (!is_array($stored)) {
            return false;
        }

        $changed = false;
        foreach (array('zh-Hant', 'zh-Hans', 'ko') as $language) {
            $row = isset($stored[$language]) && is_array($stored[$language]) ? $stored[$language] : array();
            $defaults = self::default_translation_strings_for_language($language);

            foreach ($defaults as $key => $default) {
                if (!array_key_exists($key, $row) || $row[$key] === '') {
                    $row[$key] = $default;
                    $changed = true;
                }
            }

            if (!isset($stored[$language]) || !is_array($stored[$language])) {
                $stored[$language] = $row;
            } elseif ($stored[$language] !== $row) {
                $stored[$language] = $row;
            }
        }

        if (!$changed) {
            return true;
        }

        if (update_option(self::OPTION_TRANSLATIONS, $stored)) {
            return true;
        }

        return get_option(self::OPTION_TRANSLATIONS, array()) === $stored;
    }

    private static function migrate_base_language_setting() {
        $settings = get_option(self::OPTION_SETTINGS, array());
        if (!is_array($settings)) {
            return false;
        }

        $supported = self::supported_languages();
        $enabled = array();
        if (isset($settings['enabled_languages'])) {
            foreach ((array) $settings['enabled_languages'] as $language) {
                $language = (string) $language;
                if (isset($supported[$language]) && !in_array($language, $enabled, true)) {
                    $enabled[] = $language;
                }
            }
        }

        $stored_base = isset($settings['base_language']) ? (string) $settings['base_language'] : '';
        if (isset($supported[$stored_base])) {
            $base_language = $stored_base;
        } elseif (count($enabled) === 1) {
            $base_language = $enabled[0];
        } elseif (count($enabled) > 1 && in_array('ja', $enabled, true)) {
            $base_language = 'ja';
        } elseif (count($enabled) > 1) {
            $locale_language = self::get_default_base_language();
            $base_language = in_array($locale_language, $enabled, true) ? $locale_language : $enabled[0];
        } else {
            $base_language = 'en';
        }

        if (!in_array($base_language, $enabled, true)) {
            $enabled[] = $base_language;
        }

        $updated = $settings;
        $updated['base_language'] = $base_language;
        $updated['enabled_languages'] = $enabled;
        if ($updated === $settings) {
            return true;
        }

        if (update_option(self::OPTION_SETTINGS, $updated)) {
            return true;
        }

        return get_option(self::OPTION_SETTINGS, array()) === $updated;
    }

    private function normalize_translation_strings($value) {
        $english_defaults = self::default_translation_strings();
        $out = array();
        foreach (self::supported_languages() as $lang => $label) {
            $row = isset($value[$lang]) && is_array($value[$lang]) ? $value[$lang] : array();
            $language_defaults = self::default_translation_strings_for_language($lang);
            $out[$lang] = $row;
            foreach ($english_defaults as $key => $english_default) {
                if (array_key_exists($key, $row)) {
                    $out[$lang][$key] = (string) $row[$key];
                } else {
                    $out[$lang][$key] = isset($language_defaults[$key]) ? $language_defaults[$key] : '';
                }
            }
        }
        return $out;
    }

    public function sanitize_translation_strings($input) {
        $out = array();
        $defaults = self::default_translation_strings();
        $current = get_option(self::OPTION_TRANSLATIONS, array());
        $current = $this->normalize_translation_strings(is_array($current) ? $current : array());
        foreach (self::supported_languages() as $lang => $label) {
            if (!isset($input[$lang])) {
                $out[$lang] = isset($current[$lang]) ? $current[$lang] : array();
                continue;
            }
            $row = isset($input[$lang]) && is_array($input[$lang]) ? $input[$lang] : array();
            $out[$lang] = isset($current[$lang]) && is_array($current[$lang]) ? $current[$lang] : array();
            foreach ($defaults as $key => $default) {
                $out[$lang][$key] = array_key_exists($key, $row) ? sanitize_text_field($row[$key]) : '';
            }
        }
        return $out;
    }

    private function get_translation_strings() {
        $stored = get_option(self::OPTION_TRANSLATIONS, array());
        return $this->normalize_translation_strings(is_array($stored) ? $stored : array());
    }

    private function t($key, $default = '', $lang = '') {
        $key = (string) $key;
        $lang = $this->normalize_language_code($lang ?: $this->current_language());
        $strings = $this->get_translation_strings();
        if ($lang !== '' && isset($strings[$lang][$key]) && trim((string) $strings[$lang][$key]) !== '') {
            return (string) $strings[$lang][$key];
        }
        if ($lang === 'ja' && isset($strings['ja'][$key]) && trim((string) $strings['ja'][$key]) !== '') {
            return (string) $strings['ja'][$key];
        }
        $defaults = self::default_translation_strings();
        if ($default === '' && isset($defaults[$key])) {
            return $defaults[$key];
        }
        return (string) $default;
    }

    private function frontend_status_label($status) {
        $map = array(
            'pending' => 'status.pending',
            'approved' => 'status.approved',
            'rejected' => 'status.rejected',
            'published' => 'status.published',
            'needs_more' => 'status.needs_more',
        );
        return isset($map[$status]) ? $this->t($map[$status]) : self::status_label($status);
    }
}
