<?php
/**
 * Plugin Name: Waxing Appointments
 * Plugin URI: https://difusal.com
 * Description: Simple appointment booking system for waxing services with WooCommerce integration
 * Version: 1.0.13
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

define('WAXING_APPOINTMENTS_VERSION', '1.0.0');
define('WAXING_APPOINTMENTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAXING_APPOINTMENTS_PLUGIN_URL', plugin_dir_url(__FILE__));

class WaxingAppointments {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_appointment_modal'));
        add_action('wp_ajax_book_appointment', array($this, 'handle_appointment_booking'));
        add_action('wp_ajax_nopriv_book_appointment', array($this, 'handle_appointment_booking'));
        add_action('wp_ajax_check_availability', array($this, 'check_availability'));
        add_action('wp_ajax_nopriv_check_availability', array($this, 'check_availability'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('waxing_appointment_button', array($this, 'appointment_button_shortcode'));
        
        // Calendar admin route
        add_action('init', array($this, 'handle_calendar_admin_route'));
        add_action('wp_ajax_calendar_admin_login', array($this, 'handle_calendar_admin_login'));
        add_action('wp_ajax_nopriv_calendar_admin_login', array($this, 'handle_calendar_admin_login'));
        add_action('wp_ajax_block_calendar_time', array($this, 'handle_block_calendar_time'));
        add_action('wp_ajax_unblock_calendar_time', array($this, 'handle_unblock_calendar_time'));
        
        // WooCommerce hooks for dynamic pricing
        add_action('woocommerce_before_calculate_totals', array($this, 'set_cart_item_price'));
        add_filter('woocommerce_cart_item_name', array($this, 'modify_cart_item_name'), 10, 3);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_appointment_to_order'), 10, 4);
        
        // Check if we need to create the appointment product
        if (get_option('waxing_appointments_create_product') && class_exists('WooCommerce')) {
            $this->create_appointment_product();
            delete_option('waxing_appointments_create_product');
        }
    }
    
    public function activate() {
        $this->create_tables();
        // Create product after WooCommerce is available
        if (class_exists('WooCommerce')) {
            $this->create_appointment_product();
        } else {
            // Schedule product creation for later
            add_option('waxing_appointments_create_product', 1);
        }
    }
    
    public function deactivate() {
        // Clean up if needed
    }
    
    public function create_tables() {
        global $wpdb;
        
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $appointments_sql = "CREATE TABLE $appointments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            service_id varchar(50) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            total_price decimal(10,2) NOT NULL,
            deposit_paid decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            order_id mediumint(9),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $availability_sql = "CREATE TABLE $availability_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            time_slot time NOT NULL,
            is_available tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY date_time (date, time_slot)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($appointments_sql);
        dbDelta($availability_sql);
        
        $this->populate_default_availability();
    }
    
    private function populate_default_availability() {
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $time_slots = array('09:00:00', '10:00:00', '11:00:00', '12:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00');
        
        for ($i = 0; $i < 60; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            
            if (date('N', strtotime($date)) < 6) { // Monday to Friday
                foreach ($time_slots as $time) {
                    $wpdb->insert(
                        $availability_table,
                        array(
                            'date' => $date,
                            'time_slot' => $time,
                            'is_available' => 1
                        ),
                        array('%s', '%s', '%d')
                    );
                }
            }
        }
    }
    
    public function create_appointment_product() {
        if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) {
            return;
        }
        
        try {
            $existing_product = get_posts(array(
                'post_type' => 'product',
                'meta_key' => '_waxing_appointment_product',
                'meta_value' => 'yes',
                'posts_per_page' => 1
            ));
            
            if (empty($existing_product)) {
                $product = new WC_Product_Simple();
                $product->set_name('Appointment Deposit');
                $product->set_description('Deposit payment for waxing appointment booking');
                $product->set_short_description('40% deposit for your waxing appointment');
                $product->set_status('publish');
                $product->set_catalog_visibility('hidden');
                $product->set_virtual(true);
                $product->set_price(0);
                $product->set_regular_price(0);
                $product->save();
                
                update_post_meta($product->get_id(), '_waxing_appointment_product', 'yes');
            }
        } catch (Exception $e) {
            // Log error but don't break plugin activation
            error_log('Waxing Appointments: Could not create appointment product - ' . $e->getMessage());
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        // Enqueue Air Datepicker from CDN
        wp_enqueue_script('air-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/air-datepicker.min.js', array(), '3.5.3', true);
        wp_enqueue_style('air-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/air-datepicker.min.css', array(), '3.5.3');
        
        wp_enqueue_script('waxing-appointments', WAXING_APPOINTMENTS_PLUGIN_URL . 'assets/js/appointments.js', array('jquery', 'air-datepicker'), WAXING_APPOINTMENTS_VERSION, true);
        wp_enqueue_style('waxing-appointments', WAXING_APPOINTMENTS_PLUGIN_URL . 'assets/css/appointments.css', array('air-datepicker'), WAXING_APPOINTMENTS_VERSION);
        
        wp_localize_script('waxing-appointments', 'waxing_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waxing_appointments_nonce'),
            'container' => '.modal-content'
        ));
    }
    
    public function appointment_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Book Appointment',
            'style' => 'default', // default, inline, custom
            'class' => ''
        ), $atts);
        
        $button_class = 'btn-book-appointment';
        if (!empty($atts['class'])) {
            $button_class .= ' ' . sanitize_html_class($atts['class']);
        }
        
        $container_class = '';
        if ($atts['style'] === 'inline') {
            $container_class = 'inline-appointment-button';
        } elseif ($atts['style'] === 'custom') {
            $container_class = 'custom-appointment-button';
        } else {
            $container_class = 'default-appointment-button';
        }
        
        return '<div class="' . $container_class . '">
                    <button type="button" class="' . $button_class . '">' . esc_html($atts['text']) . '</button>
                </div>';
    }
    
    public function add_appointment_modal() {
        ?>
        <div id="waxing-appointment-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Book Your Waxing Appointment</h2>
                <form id="appointment-form">
                    <div class="form-group">
                        <label for="customer_name">Full Name *</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_email">Email *</label>
                        <input type="email" id="customer_email" name="customer_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_phone">Phone *</label>
                        <input type="tel" id="customer_phone" name="customer_phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="service">Service *</label>
                        <select id="service" name="service" required>
                            <option value="">Select a service...</option>
                            <option value="eyebrow_wax" data-price="25">Eyebrow Wax - $25</option>
                            <option value="upper_lip" data-price="15">Upper Lip - $15</option>
                            <option value="full_leg" data-price="80">Full Leg Wax - $80</option>
                            <option value="half_leg" data-price="45">Half Leg Wax - $45</option>
                            <option value="bikini" data-price="35">Bikini Wax - $35</option>
                            <option value="brazilian" data-price="65">Brazilian Wax - $65</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_date">Date *</label>
                        <input type="text" id="appointment_date" name="appointment_date" required placeholder="Select a date..." autocomplete="off">
                        <input type="hidden" id="appointment_date_value" name="appointment_date_value">
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_time">Time *</label>
                        <select id="appointment_time" name="appointment_time" required>
                            <option value="">Select date first...</option>
                        </select>
                    </div>
                    
                    <div id="price-summary" style="display:none;">
                        <div class="price-info">
                            <p><strong>Service Price:</strong> $<span id="total-price">0</span></p>
                            <p><strong>Deposit Required (40%):</strong> $<span id="deposit-price">0</span></p>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-appointment">Cancel</button>
                        <button type="submit" id="book-appointment">Book & Pay Deposit</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    public function check_availability() {
        check_ajax_referer('waxing_appointments_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $available_times = $wpdb->get_results($wpdb->prepare(
            "SELECT time_slot FROM $availability_table WHERE date = %s AND is_available = 1 ORDER BY time_slot",
            $date
        ));
        
        $times = array();
        foreach ($available_times as $time) {
            $times[] = array(
                'value' => $time->time_slot,
                'label' => date('g:i A', strtotime($time->time_slot))
            );
        }
        
        wp_send_json_success($times);
    }
    
    public function handle_appointment_booking() {
        check_ajax_referer('waxing_appointments_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['customer_name']);
        $email = sanitize_email($_POST['customer_email']);
        $phone = sanitize_text_field($_POST['customer_phone']);
        $service = sanitize_text_field($_POST['service']);
        $date = sanitize_text_field($_POST['appointment_date']);
        $time = sanitize_text_field($_POST['appointment_time']);
        
        if (!$this->is_time_available($date, $time)) {
            wp_send_json_error('This time slot is no longer available.');
        }
        
        $service_prices = array(
            'eyebrow_wax' => 25,
            'upper_lip' => 15,
            'full_leg' => 80,
            'half_leg' => 45,
            'bikini' => 35,
            'brazilian' => 65
        );
        
        $total_price = $service_prices[$service];
        $deposit = $total_price * 0.4;
        
        global $wpdb;
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        
        $appointment_id = $wpdb->insert(
            $appointments_table,
            array(
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'service_id' => $service,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'total_price' => $total_price,
                'deposit_paid' => $deposit,
                'status' => 'pending'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s')
        );
        
        if ($appointment_id) {
            $this->mark_time_unavailable($date, $time);
            $checkout_url = $this->create_checkout_session($appointment_id, $deposit, $name, $email);
            wp_send_json_success(array('checkout_url' => $checkout_url));
        } else {
            wp_send_json_error('Failed to book appointment.');
        }
    }
    
    private function is_time_available($date, $time) {
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT is_available FROM $availability_table WHERE date = %s AND time_slot = %s",
            $date, $time
        ));
        
        return $result == 1;
    }
    
    private function mark_time_unavailable($date, $time) {
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $wpdb->update(
            $availability_table,
            array('is_available' => 0),
            array('date' => $date, 'time_slot' => $time),
            array('%d'),
            array('%s', '%s')
        );
    }
    
    private function create_checkout_session($appointment_id, $deposit, $customer_name, $customer_email) {
        if (!class_exists('WooCommerce')) {
            return home_url();
        }
        
        $product_id = $this->get_appointment_product_id();
        if (!$product_id) {
            return home_url();
        }
        
        WC()->cart->empty_cart();
        WC()->cart->add_to_cart($product_id, 1, 0, array(), array(
            'appointment_id' => $appointment_id,
            'deposit_amount' => $deposit,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email
        ));
        
        return wc_get_checkout_url();
    }
    
    private function get_appointment_product_id() {
        $products = get_posts(array(
            'post_type' => 'product',
            'meta_key' => '_waxing_appointment_product',
            'meta_value' => 'yes',
            'posts_per_page' => 1
        ));
        
        return !empty($products) ? $products[0]->ID : false;
    }
    
    public function set_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['appointment_id']) && isset($cart_item['deposit_amount'])) {
                $cart_item['data']->set_price($cart_item['deposit_amount']);
            }
        }
    }
    
    public function modify_cart_item_name($product_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['appointment_id'])) {
            global $wpdb;
            $appointments_table = $wpdb->prefix . 'waxing_appointments';
            
            $appointment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $appointments_table WHERE id = %d",
                $cart_item['appointment_id']
            ));
            
            if ($appointment) {
                $service_name = str_replace('_', ' ', ucwords($appointment->service_id));
                $date = date('M j, Y', strtotime($appointment->appointment_date));
                $time = date('g:i A', strtotime($appointment->appointment_time));
                
                $product_name = "Appointment Deposit - {$service_name}<br><small>Date: {$date} at {$time}</small>";
            }
        }
        
        return $product_name;
    }
    
    public function save_appointment_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['appointment_id'])) {
            $item->add_meta_data('Appointment ID', $values['appointment_id']);
            $item->add_meta_data('Customer Name', $values['customer_name']);
            $item->add_meta_data('Customer Email', $values['customer_email']);
            
            // Update appointment with order ID
            global $wpdb;
            $appointments_table = $wpdb->prefix . 'waxing_appointments';
            
            $wpdb->update(
                $appointments_table,
                array('order_id' => $order->get_id(), 'status' => 'confirmed'),
                array('id' => $values['appointment_id']),
                array('%d', '%s'),
                array('%d')
            );
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Waxing Appointments',
            'Appointments',
            'manage_options',
            'waxing-appointments',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            26
        );
    }
    
    public function admin_page() {
        global $wpdb;
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        
        $appointments = $wpdb->get_results("SELECT * FROM $appointments_table ORDER BY appointment_date DESC, appointment_time DESC");
        
        ?>
        <div class="wrap">
            <h1>Waxing Appointments</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Total</th>
                        <th>Deposit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo $appointment->id; ?></td>
                        <td><?php echo esc_html($appointment->customer_name); ?></td>
                        <td><?php echo esc_html($appointment->customer_email); ?></td>
                        <td><?php echo esc_html($appointment->customer_phone); ?></td>
                        <td><?php echo esc_html(str_replace('_', ' ', ucwords($appointment->service_id))); ?></td>
                        <td><?php echo date('M j, Y', strtotime($appointment->appointment_date)); ?></td>
                        <td><?php echo date('g:i A', strtotime($appointment->appointment_time)); ?></td>
                        <td>$<?php echo number_format($appointment->total_price, 2); ?></td>
                        <td>$<?php echo number_format($appointment->deposit_paid, 2); ?></td>
                        <td><?php echo esc_html($appointment->status); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function handle_calendar_admin_route() {
        if (isset($_GET['calendar-admin']) || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/calendar-admin') !== false)) {
            if (!session_id()) {
                session_start();
            }
            
            if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
                $this->show_calendar_admin_login();
                exit;
            } else {
                $this->show_calendar_admin_dashboard();
                exit;
            }
        }
    }
    
    private function show_calendar_admin_login() {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Calendar Admin Login</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    background: #f1f1f1; 
                    margin: 0; 
                    padding: 50px 0;
                }
                .login-container { 
                    max-width: 400px; 
                    margin: 0 auto; 
                    background: white; 
                    padding: 30px; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .login-container h2 { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    color: #333;
                }
                .form-group { 
                    margin-bottom: 20px; 
                }
                .form-group label { 
                    block; 
                    margin-bottom: 5px; 
                    color: #555;
                }
                .form-group input { 
                    width: 100%; 
                    padding: 12px; 
                    border: 1px solid #ddd; 
                    border-radius: 4px; 
                    box-sizing: border-box;
                }
                .login-btn { 
                    width: 100%; 
                    padding: 12px; 
                    background: #0073aa; 
                    color: white; 
                    border: none; 
                    border-radius: 4px; 
                    cursor: pointer; 
                    font-size: 16px;
                }
                .login-btn:hover { 
                    background: #005a87; 
                }
                .error { 
                    color: #d63638; 
                    margin-bottom: 15px; 
                    text-align: center;
                }
            </style>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        </head>
        <body>
            <div class="login-container">
                <h2>Calendar Admin Login</h2>
                <div id="error-message" class="error" style="display:none;"></div>
                <form id="admin-login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                </form>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#admin-login-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'calendar_admin_login',
                            username: $('#username').val(),
                            password: $('#password').val(),
                            nonce: '<?php echo wp_create_nonce('calendar_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                $('#error-message').text(response.data).show();
                            }
                        }
                    });
                });
            });
            </script>
        </body>
        </html>
        <?php
    }
    
    public function handle_calendar_admin_login() {
        check_ajax_referer('calendar_admin_nonce', 'nonce');
        
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        
        // Simple hardcoded credentials (you can make this configurable later)
        if ($username === 'admin' && $password === 'waxing2024') {
            if (!session_id()) {
                session_start();
            }
            $_SESSION['calendar_admin_logged_in'] = true;
            wp_send_json_success('Login successful');
        } else {
            wp_send_json_error('Invalid credentials');
        }
    }
    
    private function show_calendar_admin_dashboard() {
        global $wpdb;
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        // Get upcoming appointments
        $appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $appointments_table WHERE appointment_date >= %s ORDER BY appointment_date, appointment_time",
            date('Y-m-d')
        ));
        
        // Get availability for next 30 days
        $availability = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $availability_table WHERE date >= %s AND date <= %s ORDER BY date, time_slot",
            date('Y-m-d'),
            date('Y-m-d', strtotime('+30 days'))
        ));
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Calendar Admin Dashboard</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    background: #f1f1f1;
                }
                .header { 
                    background: white; 
                    padding: 20px; 
                    margin-bottom: 20px; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center;
                }
                .logout-btn { 
                    background: #d63638; 
                    color: white; 
                    padding: 8px 16px; 
                    text-decoration: none; 
                    border-radius: 4px;
                }
                .dashboard-grid { 
                    display: grid; 
                    grid-template-columns: 1fr 1fr; 
                    gap: 20px;
                }
                .panel { 
                    background: white; 
                    padding: 20px; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .panel h3 { 
                    margin-top: 0; 
                    color: #333;
                }
                .appointment-item { 
                    padding: 10px; 
                    border-left: 4px solid #0073aa; 
                    margin-bottom: 10px; 
                    background: #f9f9f9;
                }
                .calendar-day { 
                    border: 1px solid #ddd; 
                    margin-bottom: 15px; 
                    background: #fff;
                }
                .calendar-day-header { 
                    background: #0073aa; 
                    color: white; 
                    padding: 10px; 
                    font-weight: bold;
                }
                .time-slot { 
                    padding: 8px 12px; 
                    border-bottom: 1px solid #eee; 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center;
                }
                .time-slot.blocked { 
                    background: #ffecec; 
                    color: #d63638;
                }
                .time-slot.booked { 
                    background: #e6f3ff; 
                    color: #0073aa;
                }
                .block-btn { 
                    background: #d63638; 
                    color: white; 
                    border: none; 
                    padding: 4px 8px; 
                    border-radius: 3px; 
                    cursor: pointer; 
                    font-size: 12px;
                }
                .unblock-btn { 
                    background: #00a32a; 
                    color: white; 
                    border: none; 
                    padding: 4px 8px; 
                    border-radius: 3px; 
                    cursor: pointer; 
                    font-size: 12px;
                }
                @media (max-width: 768px) {
                    .dashboard-grid { 
                        grid-template-columns: 1fr; 
                    }
                }
            </style>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        </head>
        <body>
            <div class="header">
                <h1>Calendar Admin Dashboard</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <div class="dashboard-grid">
                <div class="panel">
                    <h3>Upcoming Appointments</h3>
                    <?php if (empty($appointments)): ?>
                        <p>No upcoming appointments</p>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-item">
                                <strong><?php echo esc_html($appointment->customer_name); ?></strong><br>
                                <small><?php echo esc_html(str_replace('_', ' ', ucwords($appointment->service_id))); ?></small><br>
                                <?php echo date('M j, Y', strtotime($appointment->appointment_date)); ?> at 
                                <?php echo date('g:i A', strtotime($appointment->appointment_time)); ?><br>
                                <small>Status: <?php echo esc_html($appointment->status); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="panel">
                    <h3>Calendar Schedule Management</h3>
                    <p>Click to block/unblock time slots:</p>
                    
                    <?php
                    $current_date = '';
                    $booked_slots = array();
                    
                    // Create array of booked slots
                    foreach ($appointments as $appointment) {
                        $key = $appointment->appointment_date . '_' . $appointment->appointment_time;
                        $booked_slots[$key] = $appointment;
                    }
                    
                    foreach ($availability as $slot):
                        if ($current_date !== $slot->date):
                            if ($current_date !== '') echo '</div>';
                            $current_date = $slot->date;
                            ?>
                            <div class="calendar-day">
                                <div class="calendar-day-header">
                                    <?php echo date('l, M j, Y', strtotime($slot->date)); ?>
                                </div>
                        <?php endif; 
                        
                        $slot_key = $slot->date . '_' . $slot->time_slot;
                        $is_booked = isset($booked_slots[$slot_key]);
                        $class = $is_booked ? 'booked' : ($slot->is_available ? '' : 'blocked');
                        ?>
                        
                        <div class="time-slot <?php echo $class; ?>">
                            <span>
                                <?php echo date('g:i A', strtotime($slot->time_slot)); ?>
                                <?php if ($is_booked): ?>
                                    - Booked by <?php echo esc_html($booked_slots[$slot_key]->customer_name); ?>
                                <?php elseif (!$slot->is_available): ?>
                                    - Blocked
                                <?php endif; ?>
                            </span>
                            
                            <?php if (!$is_booked): ?>
                                <?php if ($slot->is_available): ?>
                                    <button class="block-btn" onclick="blockTimeSlot('<?php echo $slot->date; ?>', '<?php echo $slot->time_slot; ?>')">
                                        Block
                                    </button>
                                <?php else: ?>
                                    <button class="unblock-btn" onclick="unblockTimeSlot('<?php echo $slot->date; ?>', '<?php echo $slot->time_slot; ?>')">
                                        Unblock
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <script>
            function blockTimeSlot(date, time) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'block_calendar_time',
                        date: date,
                        time: time,
                        nonce: '<?php echo wp_create_nonce('calendar_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            }
            
            function unblockTimeSlot(date, time) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'unblock_calendar_time',
                        date: date,
                        time: time,
                        nonce: '<?php echo wp_create_nonce('calendar_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            }
            </script>
        </body>
        </html>
        <?php
        
        // Handle logout
        if (isset($_GET['logout'])) {
            session_start();
            session_destroy();
            wp_redirect(home_url('/calendar-admin'));
            exit;
        }
    }
    
    public function handle_block_calendar_time() {
        check_ajax_referer('calendar_admin_nonce', 'nonce');
        
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated');
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $result = $wpdb->update(
            $availability_table,
            array('is_available' => 0),
            array('date' => $date, 'time_slot' => $time),
            array('%d'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success('Time slot blocked');
        } else {
            wp_send_json_error('Failed to block time slot');
        }
    }
    
    public function handle_unblock_calendar_time() {
        check_ajax_referer('calendar_admin_nonce', 'nonce');
        
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated');
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $result = $wpdb->update(
            $availability_table,
            array('is_available' => 1),
            array('date' => $date, 'time_slot' => $time),
            array('%d'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success('Time slot unblocked');
        } else {
            wp_send_json_error('Failed to unblock time slot');
        }
    }
}

new WaxingAppointments();