<?php
if (!defined('ABSPATH')) {
    exit;
}

trait TCARM_Application_Number_Trait {
    private function generate_application_code() {
        $settings = self::get_settings();
        $rule = isset($settings['application_number_rule']) && is_array($settings['application_number_rule']) ? $settings['application_number_rule'] : self::default_application_number_rule();
        $base_sequence = $this->next_application_sequence_base();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $this->build_application_code_from_rule($rule, $base_sequence + $attempt);
            if ($code !== '' && !$this->application_code_exists($code)) {
                return $code;
            }
        }

        $timestamp = current_time('timestamp');
        $fallback_prefix = 'APP-' . gmdate('Ymd', $timestamp) . '-' . gmdate('His', $timestamp) . '-';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $fallback_prefix . $this->random_digits(4);
            if (!$this->application_code_exists($code)) {
                return $code;
            }
        }

        return substr($fallback_prefix . $this->random_digits(8), 0, 32);
    }

    private function build_application_code_from_rule($rule, $sequence) {
        $parts = array();
        foreach ($rule as $row) {
            if (!is_array($row) || empty($row['type'])) {
                continue;
            }

            if ($row['type'] === 'fixed') {
                $value = isset($row['value']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $row['value']) : '';
                if ($value !== '') {
                    $parts[] = substr($value, 0, 16);
                }
            } elseif ($row['type'] === 'symbol') {
                $parts[] = isset($row['value']) && $row['value'] === '_' ? '_' : '-';
            } elseif ($row['type'] === 'date') {
                $format = isset($row['format']) && in_array($row['format'], array('Ymd', 'Ym', 'Y'), true) ? $row['format'] : 'Ymd';
                $parts[] = gmdate($format, current_time('timestamp'));
            } elseif ($row['type'] === 'random_letters') {
                $length = isset($row['length']) ? max(1, min(8, absint($row['length']))) : 2;
                $parts[] = $this->random_letters($length);
            } elseif ($row['type'] === 'random_numbers') {
                $length = isset($row['length']) ? max(1, min(8, absint($row['length']))) : 2;
                $parts[] = $this->random_digits($length);
            } elseif ($row['type'] === 'sequence') {
                $length = isset($row['length']) ? max(1, min(12, absint($row['length']))) : 6;
                $parts[] = str_pad((string) max(1, (int) $sequence), $length, '0', STR_PAD_LEFT);
            }
        }

        $code = implode('', $parts);
        return preg_match('/^[A-Za-z0-9_-]{1,32}$/', $code) ? $code : '';
    }

    private function next_application_sequence_base() {
        global $wpdb;
        $cache_key = self::application_cache_key(array('next_sequence_base'));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return (int) $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table sequence lookup with object cache.
        $max_id = (int) $wpdb->get_var($wpdb->prepare("SELECT MAX(id) FROM %i", self::table_name()));
        wp_cache_set($cache_key, $max_id + 1, self::application_cache_group(), self::application_cache_ttl());
        return $max_id + 1;
    }

    private function application_code_exists($code) {
        global $wpdb;
        $cache_key = self::application_cache_key(array('application_code_exists', $code));
        $found = false;
        $cached = wp_cache_get($cache_key, self::application_cache_group(), false, $found);
        if ($found) {
            return (bool) $cached;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin-owned custom application table code existence check with object cache.
        $exists = (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM %i WHERE application_code = %s LIMIT %d", self::table_name(), $code, 1));
        wp_cache_set($cache_key, $exists, self::application_cache_group(), self::application_cache_ttl());
        return $exists;
    }

    private function random_letters($length) {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $letters[wp_rand(0, strlen($letters) - 1)];
        }
        return $out;
    }

    private function random_digits($length) {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= (string) wp_rand(0, 9);
        }
        return $out;
    }
}
