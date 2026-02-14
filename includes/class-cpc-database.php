<?php
/**
 * Database operations for the plugin
 *
 * @package    Cleaning_Price_Calculator
 * @subpackage Cleaning_Price_Calculator/includes
 */

class CPC_Database {
    
    /**
     * Create custom tables for the plugin
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Quotes Table
        $table_quotes = $wpdb->prefix . 'cpc_quotes';
        $sql_quotes = "CREATE TABLE $table_quotes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            customer_address text,
            message text,
            total_price decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(10) DEFAULT 'EUR',
            status varchar(20) DEFAULT 'pending',
            ip_address varchar(100),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_email (customer_email),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Quote Items Table - UPDATED WITH room_count
        $table_quote_items = $wpdb->prefix . 'cpc_quote_items';
        $sql_quote_items = "CREATE TABLE $table_quote_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            quote_id bigint(20) unsigned NOT NULL,
            room_name varchar(255) NOT NULL,
            room_count int(11) NOT NULL DEFAULT 1,
            area decimal(10,2) NOT NULL,
            price_per_sqm decimal(10,2) NOT NULL,
            subtotal decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY quote_id (quote_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_quotes);
        dbDelta($sql_quote_items);
        
        // Check if we need to migrate old data
        self::maybe_migrate_room_type_column();
        self::maybe_add_room_count_column();
    }
    
    /**
     * Migrate from room_type_name to room_name if needed
     */
    private static function maybe_migrate_room_type_column() {
        global $wpdb;
        $table = $wpdb->prefix . 'cpc_quote_items';
        
        // Check if room_type_name column exists
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'room_type_name'");
        
        if (!empty($columns)) {
            // Column exists, need to migrate
            $wpdb->query("ALTER TABLE $table CHANGE COLUMN room_type_name room_name varchar(255) NOT NULL");
            
            // Remove room_type_id if it exists
            $wpdb->query("ALTER TABLE $table DROP COLUMN IF EXISTS room_type_id");
        }
    }

    /**
     * Add room_count column if it doesn't exist
     */
    private static function maybe_add_room_count_column() {
        global $wpdb;
        $table = $wpdb->prefix . 'cpc_quote_items';
        
        // Check if room_count column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'room_count'");
        
        if (empty($column_exists)) {
            // Add room_count column with default value of 1
            $wpdb->query("ALTER TABLE $table ADD COLUMN room_count int(11) NOT NULL DEFAULT 1 AFTER room_name");
            error_log('CPC: Added room_count column to quote_items table');
        }
    }
    
    /**
     * Save quote
     */
    public static function save_quote($customer_data, $rooms_data) {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'cpc_quotes';
        $items_table = $wpdb->prefix . 'cpc_quote_items';
        
        // Calculate total
        $total = 0;
        foreach ($rooms_data as $room) {
            $total += floatval($room['subtotal']);
        }
        
        // Get currency from settings
        $currency = get_option('cpc_currency', 'EUR');
        
        // Insert quote
        $quote_data = array(
            'customer_name' => sanitize_text_field($customer_data['name']),
            'customer_email' => sanitize_email($customer_data['email']),
            'customer_phone' => sanitize_text_field($customer_data['phone']),
            'customer_address' => sanitize_textarea_field($customer_data['address'] ?? ''),
            'message' => sanitize_textarea_field($customer_data['message'] ?? ''),
            'total_price' => $total,
            'currency' => $currency,
            'ip_address' => self::get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        );
        
        error_log('CPC Database - Saving Quote: ' . print_r($quote_data, true));
        
        $result = $wpdb->insert($quotes_table, $quote_data);
        
        if ($result === false) {
            error_log('CPC Database - Failed to insert quote: ' . $wpdb->last_error);
            return false;
        }
        
        $quote_id = $wpdb->insert_id;
        error_log('CPC Database - Quote ID created: ' . $quote_id);
        
        // Insert quote items
        foreach ($rooms_data as $index => $room) {
            $item_data = array(
                'quote_id' => $quote_id,
                'room_name' => sanitize_text_field($room['room_name']),
                'room_count' => intval($room['room_count']),
                'area' => floatval($room['area']),
                'price_per_sqm' => floatval($room['price_per_sqm']),
                'subtotal' => floatval($room['subtotal']),
            );
            
            error_log('CPC Database - Saving Room Item ' . ($index + 1) . ': ' . print_r($item_data, true));
            
            $item_result = $wpdb->insert($items_table, $item_data);
            
            if ($item_result === false) {
                error_log('CPC Database - Failed to insert room item: ' . $wpdb->last_error);
            } else {
                error_log('CPC Database - Room item saved with ID: ' . $wpdb->insert_id);
            }
        }
        
        return $quote_id;
    }
    
    /**
     * Get quote with items
     */
    public static function get_quote($id) {
        global $wpdb;
        
        $quotes_table = $wpdb->prefix . 'cpc_quotes';
        $items_table = $wpdb->prefix . 'cpc_quote_items';
        
        $quote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $quotes_table WHERE id = %d",
            $id
        ));
        
        if (!$quote) {
            return null;
        }
        
        // Get items
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE quote_id = %d ORDER BY id ASC",
            $id
        ));
        
        // Ensure items is always an array
        $quote->items = is_array($items) ? $items : array();
        
        return $quote;
    }
    
    /**
     * Get all quotes with pagination
     */
    public static function get_quotes($limit = 20, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'cpc_quotes';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Get total quotes count
     */
    public static function get_quotes_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'cpc_quotes';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return sanitize_text_field($ip);
    }
}