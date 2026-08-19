<?php
/**
 * Plugin Name: Waxing Appointments
 * Plugin URI: https://difusal.com
 * Description: Simple appointment booking system for waxing services with WooCommerce integration
 * Version: 3.3.2
 * Author: Difusal
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WAXING_APPOINTMENTS_VERSION', '3.3.2');
define('WAXING_APPOINTMENTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAXING_APPOINTMENTS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required classes
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-database.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-services.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-appointments-handler.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-woocommerce.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-stripe.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-twilio.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-frontend.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-admin.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-calendar-admin.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-patients.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-patient-forms.php';
require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/class-waxing-intake-prompt.php';

/**
 * Main plugin class
 */
class WaxingAppointments {

    /**
     * Schema version. Bump only when the database layout changes.
     */
    const DB_VERSION = '2';

    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Ensure the schema is up to date (adds the office column / unique key on
        // installs that were activated before multi-location support existed).
        //
        // Gated on a dedicated schema version, not the plugin version: the
        // migration only needs to run once, and tying it to the plugin version
        // would re-run it on every release. The flag is only written when the
        // upgrade actually reports success, so a failed ALTER retries on the
        // next request instead of leaving queries pointed at a missing column.
        if (get_option('waxing_appointments_db_version') !== self::DB_VERSION) {
            if (Waxing_Database::maybe_upgrade_office_schema()) {
                update_option('waxing_appointments_db_version', self::DB_VERSION);
            }
        }

        // Patient records. Registered directly rather than hooked onto `init`:
        // this method already runs on `init`, so a nested add_action would be
        // added too late for the current request to see the post type.
        Waxing_Patients::register_post_type();

        // Frontend
        add_action('wp_enqueue_scripts', array('Waxing_Frontend', 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array('Waxing_Patient_Forms', 'register_assets'));
        add_action('wp_enqueue_scripts', array('Waxing_Intake_Prompt', 'maybe_enqueue_styles'), 20);
        add_action('wp_footer', array('Waxing_Frontend', 'add_appointment_modal'));
        add_shortcode('waxing_appointment_button', array('Waxing_Frontend', 'appointment_button_shortcode'));
        add_shortcode('waxing_patient_form', array('Waxing_Patient_Forms', 'patient_form_shortcode'));
        add_shortcode('waxing_waiver_form', array('Waxing_Patient_Forms', 'waiver_form_shortcode'));
        add_shortcode('waxing_new_patient', array('Waxing_Patient_Forms', 'new_patient_shortcode'));
        add_shortcode('waxing_kiosk', array('Waxing_Patient_Forms', 'kiosk_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_book_appointment', array('Waxing_Appointments_Handler', 'handle_appointment_booking'));
        add_action('wp_ajax_nopriv_book_appointment', array('Waxing_Appointments_Handler', 'handle_appointment_booking'));
        add_action('wp_ajax_check_availability', array('Waxing_Appointments_Handler', 'check_availability'));
        add_action('wp_ajax_nopriv_check_availability', array('Waxing_Appointments_Handler', 'check_availability'));
        add_action('wp_ajax_waxing_refresh_nonce', array('Waxing_Frontend', 'refresh_nonce'));
        add_action('wp_ajax_nopriv_waxing_refresh_nonce', array('Waxing_Frontend', 'refresh_nonce'));
        add_action('wp_ajax_waxing_save_patient', array('Waxing_Patient_Forms', 'handle_patient_submission'));
        add_action('wp_ajax_nopriv_waxing_save_patient', array('Waxing_Patient_Forms', 'handle_patient_submission'));
        add_action('wp_ajax_waxing_save_waiver', array('Waxing_Patient_Forms', 'handle_waiver_submission'));
        add_action('wp_ajax_nopriv_waxing_save_waiver', array('Waxing_Patient_Forms', 'handle_waiver_submission'));
        
        // Admin
        add_action('admin_menu', array('Waxing_Admin', 'add_admin_menu'));
        add_action('add_meta_boxes', array('Waxing_Patients', 'add_meta_boxes'));
        add_filter('manage_' . Waxing_Patients::POST_TYPE . '_posts_columns', array('Waxing_Patients', 'set_columns'));
        add_action('manage_' . Waxing_Patients::POST_TYPE . '_posts_custom_column', array('Waxing_Patients', 'render_column'), 10, 2);
        
        // Calendar admin route
        add_action('template_redirect', array('Waxing_Calendar_Admin', 'handle_route'));
        add_action('parse_request', array('Waxing_Calendar_Admin', 'parse_request'));
        add_action('wp_ajax_calendar_admin_login', array('Waxing_Calendar_Admin', 'handle_login'));
        add_action('wp_ajax_nopriv_calendar_admin_login', array('Waxing_Calendar_Admin', 'handle_login'));
        add_action('wp_ajax_block_calendar_time', array('Waxing_Calendar_Admin', 'handle_block_time'));
        add_action('wp_ajax_nopriv_block_calendar_time', array('Waxing_Calendar_Admin', 'handle_block_time'));
        add_action('wp_ajax_unblock_calendar_time', array('Waxing_Calendar_Admin', 'handle_unblock_time'));
        add_action('wp_ajax_nopriv_unblock_calendar_time', array('Waxing_Calendar_Admin', 'handle_unblock_time'));
        add_action('wp_ajax_block_calendar_day', array('Waxing_Calendar_Admin', 'handle_block_day'));
        add_action('wp_ajax_nopriv_block_calendar_day', array('Waxing_Calendar_Admin', 'handle_block_day'));
        add_action('wp_ajax_unblock_calendar_day', array('Waxing_Calendar_Admin', 'handle_unblock_day'));
        add_action('wp_ajax_nopriv_unblock_calendar_day', array('Waxing_Calendar_Admin', 'handle_unblock_day'));
        add_action('wp_ajax_get_calendar_events', array('Waxing_Calendar_Admin', 'handle_get_calendar_events'));
        add_action('wp_ajax_nopriv_get_calendar_events', array('Waxing_Calendar_Admin', 'handle_get_calendar_events'));
        add_action('wp_ajax_generate_stripe_payment_link', array('Waxing_Stripe', 'generate_payment_link'));
        add_action('wp_ajax_nopriv_generate_stripe_payment_link', array('Waxing_Stripe', 'generate_payment_link'));
        add_action('wp_ajax_get_payment_links_history', array('Waxing_Stripe', 'get_payment_links_history'));
        add_action('wp_ajax_nopriv_get_payment_links_history', array('Waxing_Stripe', 'get_payment_links_history'));
        add_action('wp_ajax_test_twilio_config', array('Waxing_Twilio', 'test_config'));
        add_action('wp_ajax_send_twilio_test_sms', array('Waxing_Twilio', 'send_test_sms'));
        
        // WooCommerce hooks
        add_action('woocommerce_before_calculate_totals', array('Waxing_WooCommerce', 'set_cart_item_price'));
        add_filter('woocommerce_cart_item_name', array('Waxing_WooCommerce', 'modify_cart_item_name'), 10, 3);
        add_filter('woocommerce_get_item_data', array('Waxing_WooCommerce', 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array('Waxing_WooCommerce', 'save_appointment_to_order'), 10, 4);
        add_action('woocommerce_payment_complete', array('Waxing_WooCommerce', 'create_appointment_on_payment_complete'));
        add_action('woocommerce_order_status_completed', array('Waxing_WooCommerce', 'create_appointment_on_payment_complete'));
        add_action('woocommerce_order_status_processing', array('Waxing_WooCommerce', 'create_appointment_on_payment_complete'));
        add_action('woocommerce_thankyou', array('Waxing_WooCommerce', 'create_appointment_on_payment_complete'));

        // Intake prompt renders after the appointment row above has run, so a
        // brand-new client is only asked for paperwork once their booking is
        // actually on the books.
        add_action('woocommerce_thankyou', array('Waxing_Intake_Prompt', 'render'), 20);
    }
    
    public function activate() {
        Waxing_Database::create_tables();
    }
    
    public function deactivate() {
        // Clean up if needed
    }
}

new WaxingAppointments();

