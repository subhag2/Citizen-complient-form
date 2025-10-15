<?php
if (!defined('ABSPATH')) exit;

class SR_DB {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . "service_requests";
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        // Include reply fields (reply_text, replied_at, replied_by) so admin replies
        // are persisted and can be shown on the tracking page. dbDelta will add
        // these columns to existing tables when the plugin is updated.
        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            description TEXT NOT NULL,
            location_general VARCHAR(255),
            location_intersection VARCHAR(255),
            address VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(100),
            zipcode VARCHAR(20),
            anonymous TINYINT(1) DEFAULT 0,
            name VARCHAR(150),
            street VARCHAR(255),
            city_contact VARCHAR(100),
            state_contact VARCHAR(100),
            zip_contact VARCHAR(20),
            phone VARCHAR(30),
            email_contact VARCHAR(150),
            pin_hash VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'Pending',
            reply_text TEXT,
            replied_at DATETIME NULL,
            replied_by VARCHAR(150) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function insert_request($data) {
        global $wpdb;
        $wpdb->insert(self::table_name(), $data);
        return $wpdb->insert_id;
    }

    public static function get_request($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table_name() . " WHERE id=%d", $id));
    }

    public static function update_status($id, $status) {
        global $wpdb;
        return $wpdb->update(self::table_name(), ['status' => $status], ['id' => $id]);
    }

    public static function save_reply($id, $reply_text, $replied_by = '') {
        global $wpdb;

        $data = [
            'reply_text' => $reply_text,
            'replied_at' => current_time('mysql'),
            'replied_by' => $replied_by
        ];

        return $wpdb->update(self::table_name(), $data, ['id' => $id]);
    }

    public static function get_request_by_id_and_pin($id, $pin) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . self::table_name() . " WHERE id=%d", $id));
        
        if ($request && password_verify($pin, $request->pin_hash)) {
            return $request;
        }
        
        return false;
    }

    public static function get_all_requests() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . self::table_name() . " ORDER BY created_at DESC");
    }
}
