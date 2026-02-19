<?php
/**
 * Admin pages handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Admin {
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            'Waxing Appointments',
            'Appointments',
            'manage_options',
            'waxing-appointments',
            array(__CLASS__, 'admin_page'),
            'dashicons-calendar-alt',
            26
        );
        
        add_submenu_page(
            'waxing-appointments',
            'Calendar Admin Settings',
            'Calendar Settings',
            'manage_options',
            'waxing-calendar-settings',
            array(__CLASS__, 'calendar_settings_page')
        );
    }
    
    /**
     * Admin appointments page
     */
    public static function admin_page() {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        $appointments = $wpdb->get_results("SELECT * FROM $appointments_table ORDER BY appointment_date DESC, appointment_time DESC");
        
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/views/admin-appointments-page.php';
    }
    
    /**
     * Calendar settings page
     */
    public static function calendar_settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('waxing_calendar_settings');

            $username = sanitize_text_field($_POST['calendar_username']);
            $password = sanitize_text_field($_POST['calendar_password']);

            update_option('waxing_calendar_admin_username', $username);
            update_option('waxing_calendar_admin_password', $password);

            echo '<div class="notice notice-success"><p>Calendar admin credentials updated successfully!</p></div>';
        }

        if (isset($_POST['submit_stripe'])) {
            check_admin_referer('waxing_stripe_settings');

            $stripe_secret_key = sanitize_text_field($_POST['stripe_secret_key']);
            $stripe_mode = sanitize_text_field($_POST['stripe_mode']);

            update_option('waxing_stripe_secret_key', $stripe_secret_key);
            update_option('waxing_stripe_mode', $stripe_mode);

            echo '<div class="notice notice-success"><p>Stripe settings updated successfully!</p></div>';
        }

        if (isset($_POST['submit_twilio'])) {
            check_admin_referer('waxing_twilio_settings');

            $twilio_account_sid = sanitize_text_field($_POST['twilio_account_sid']);
            $twilio_auth_token = sanitize_text_field($_POST['twilio_auth_token']);
            $twilio_phone_number = sanitize_text_field($_POST['twilio_phone_number']);
            $twilio_admin_number = sanitize_text_field($_POST['twilio_admin_number']);
            $twilio_enabled = isset($_POST['twilio_enabled']) ? '1' : '0';

            update_option('waxing_twilio_account_sid', $twilio_account_sid);
            update_option('waxing_twilio_auth_token', $twilio_auth_token);
            update_option('waxing_twilio_phone_number', $twilio_phone_number);
            update_option('waxing_twilio_admin_number', $twilio_admin_number);
            update_option('waxing_twilio_enabled', $twilio_enabled);

            echo '<div class="notice notice-success"><p>Twilio settings updated successfully!</p></div>';
        }

        $current_username = get_option('waxing_calendar_admin_username', 'admin');
        $current_password = get_option('waxing_calendar_admin_password', 'waxing2024');
        $current_stripe_key = get_option('waxing_stripe_secret_key', '');
        $current_stripe_mode = get_option('waxing_stripe_mode', 'sandbox');
        $current_twilio_sid = get_option('waxing_twilio_account_sid', '');
        $current_twilio_token = get_option('waxing_twilio_auth_token', '');
        $current_twilio_phone = get_option('waxing_twilio_phone_number', '');
        $current_twilio_admin_number = get_option('waxing_twilio_admin_number', '');
        $current_twilio_enabled = get_option('waxing_twilio_enabled', '0');

        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/views/calendar-settings-page.php';
    }
}

