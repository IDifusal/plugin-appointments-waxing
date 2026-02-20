<?php
/**
 * Plugin Name: Waxing Appointments
 * Plugin URI: https://difusal.com
 * Description: Simple appointment booking system for waxing services with WooCommerce integration
 * Version: 2.5.3
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

define('WAXING_APPOINTMENTS_VERSION', '2.5.3');
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

/**
 * Main plugin class
 */
class WaxingAppointments {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Frontend
        add_action('wp_enqueue_scripts', array('Waxing_Frontend', 'enqueue_scripts'));
        add_action('wp_footer', array('Waxing_Frontend', 'add_appointment_modal'));
        add_shortcode('waxing_appointment_button', array('Waxing_Frontend', 'appointment_button_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_book_appointment', array('Waxing_Appointments_Handler', 'handle_appointment_booking'));
        add_action('wp_ajax_nopriv_book_appointment', array('Waxing_Appointments_Handler', 'handle_appointment_booking'));
        add_action('wp_ajax_check_availability', array('Waxing_Appointments_Handler', 'check_availability'));
        add_action('wp_ajax_nopriv_check_availability', array('Waxing_Appointments_Handler', 'check_availability'));
        
        // Admin
        add_action('admin_menu', array('Waxing_Admin', 'add_admin_menu'));
        
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
    }
    
    public function activate() {
        Waxing_Database::create_tables();
    }
    
    public function deactivate() {
        // Clean up if needed
    }
}

new WaxingAppointments();

