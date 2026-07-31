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
        
        // Handle manual appointment creation trigger
        if (isset($_GET['create_appointment']) && isset($_GET['order_id']) && current_user_can('manage_options')) {
            check_admin_referer('create_appointment_' . intval($_GET['order_id']));
            $order_id = intval($_GET['order_id']);
            Waxing_WooCommerce::create_appointment_on_payment_complete($order_id);
            echo '<div class="notice notice-success"><p>Appointment creation triggered for order #' . $order_id . '. Check logs for details.</p></div>';
        }
        
        // Get only confirmed appointments (exclude blocked slots)
        $appointments = $wpdb->get_results(
            "SELECT * FROM $appointments_table 
            WHERE status IN ('confirmed', 'booked') 
            ORDER BY appointment_date DESC, appointment_time DESC"
        );
        
        // Handle case where query fails or returns null
        if (!is_array($appointments)) {
            $appointments = array();
        }
        
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

        if (isset($_POST['submit_add_calendar_user'])) {
            check_admin_referer('waxing_calendar_users');

            $new_username = sanitize_text_field($_POST['new_calendar_username']);
            $new_password = sanitize_text_field($_POST['new_calendar_password']);
            $calendar_users = get_option('waxing_calendar_admin_users', array());
            $primary_username = get_option('waxing_calendar_admin_username', 'admin');

            if (empty($new_username) || empty($new_password)) {
                echo '<div class="notice notice-error"><p>Username and password are required.</p></div>';
            } elseif ($new_username === $primary_username || isset($calendar_users[$new_username])) {
                echo '<div class="notice notice-error"><p>A calendar admin user with that username already exists.</p></div>';
            } else {
                $calendar_users[$new_username] = password_hash($new_password, PASSWORD_DEFAULT);
                update_option('waxing_calendar_admin_users', $calendar_users);
                echo '<div class="notice notice-success"><p>Calendar admin user <strong>' . esc_html($new_username) . '</strong> created successfully!</p></div>';
            }
        }

        if (isset($_POST['submit_delete_calendar_user'])) {
            check_admin_referer('waxing_calendar_users');

            $delete_username = sanitize_text_field($_POST['delete_calendar_username']);
            $calendar_users = get_option('waxing_calendar_admin_users', array());

            if (isset($calendar_users[$delete_username])) {
                unset($calendar_users[$delete_username]);
                update_option('waxing_calendar_admin_users', $calendar_users);
                echo '<div class="notice notice-success"><p>Calendar admin user <strong>' . esc_html($delete_username) . '</strong> deleted.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>User not found.</p></div>';
            }
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
        $calendar_users = get_option('waxing_calendar_admin_users', array());
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

