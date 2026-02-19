<?php
/**
 * Database handler for Waxing Appointments
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Database {
    
    /**
     * Create database tables on plugin activation
     */
    public static function create_tables() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        $charset_collate = $wpdb->get_charset_collate();
        
        // Single table for both appointments and blocked slots
        $appointments_sql = "CREATE TABLE $appointments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_name varchar(100),
            customer_email varchar(100),
            customer_phone varchar(20),
            service_id varchar(50),
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            total_price decimal(10,2),
            deposit_paid decimal(10,2),
            status varchar(20) NOT NULL DEFAULT 'pending',
            order_id mediumint(9),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date_time (appointment_date, appointment_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($appointments_sql);
        
        // Create payment links history table
        $payment_links_table = $wpdb->prefix . 'waxing_payment_links';
        $payment_links_sql = "CREATE TABLE $payment_links_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            description varchar(255),
            customer_name varchar(100),
            customer_email varchar(100),
            payment_link text NOT NULL,
            qr_code_url text,
            email_sent tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_at (created_at),
            KEY customer_email (customer_email)
        ) $charset_collate;";
        
        dbDelta($payment_links_sql);
    }
    
    /**
     * Get table name
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'waxing_appointments';
    }
    
    /**
     * Get payment links history table name
     */
    public static function get_payment_links_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'waxing_payment_links';
    }
}

