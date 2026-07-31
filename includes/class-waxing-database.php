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
            office varchar(50) NOT NULL DEFAULT 'harrisburg',
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            total_price decimal(10,2),
            deposit_paid decimal(10,2),
            status varchar(20) NOT NULL DEFAULT 'pending',
            order_id mediumint(9),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY office_date_time (office, appointment_date, appointment_time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($appointments_sql);

        // Ensure the office column and updated unique key exist on already-installed sites.
        self::maybe_upgrade_office_schema();
        
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
     * Upgrade existing installs to the office-aware schema.
     *
     * Adds the `office` column if missing and replaces the old
     * (appointment_date, appointment_time) unique key with an
     * office-scoped one so each location keeps an independent schedule.
     */
    public static function maybe_upgrade_office_schema() {
        global $wpdb;

        $table = $wpdb->prefix . 'waxing_appointments';

        // Add the office column if it doesn't exist yet.
        $has_office = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'office'",
            DB_NAME,
            $table
        ));

        if (!$has_office) {
            if ($wpdb->query("ALTER TABLE $table ADD COLUMN office varchar(50) NOT NULL DEFAULT 'harrisburg' AFTER service_id") === false) {
                error_log('WaxingAppointments: failed to add office column - ' . $wpdb->last_error);
                return false;
            }
        }

        // Swap the unique key so uniqueness is per office.
        $has_old_key = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'date_time'",
            DB_NAME,
            $table
        ));
        if ($has_old_key) {
            $wpdb->query("ALTER TABLE $table DROP INDEX date_time");
        }

        $has_new_key = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'office_date_time'",
            DB_NAME,
            $table
        ));
        if (!$has_new_key) {
            // A pre-existing duplicate (date, time) pair across offices makes this
            // fail. Log it rather than aborting: the column migration above is what
            // bookings depend on, and the unique key is a belt-and-braces guard on
            // top of the availability check.
            if ($wpdb->query("ALTER TABLE $table ADD UNIQUE KEY office_date_time (office, appointment_date, appointment_time)") === false) {
                error_log('WaxingAppointments: failed to add office_date_time index - ' . $wpdb->last_error);
            }
        }

        return true;
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

