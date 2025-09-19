<?php
/**
 * Plugin Name: Waxing Appointments
 * Plugin URI: https://difusal.com
 * Description: Simple appointment booking system for waxing services with WooCommerce integration
 * Version: 1.4.1
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

define('WAXING_APPOINTMENTS_VERSION', '1.3.0');
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
        add_action('template_redirect', array($this, 'handle_calendar_admin_route'));
        add_action('parse_request', array($this, 'parse_calendar_admin_request'));
        add_action('wp_ajax_calendar_admin_login', array($this, 'handle_calendar_admin_login'));
        add_action('wp_ajax_nopriv_calendar_admin_login', array($this, 'handle_calendar_admin_login'));
        add_action('wp_ajax_block_calendar_time', array($this, 'handle_block_calendar_time'));
        add_action('wp_ajax_nopriv_block_calendar_time', array($this, 'handle_block_calendar_time'));
        add_action('wp_ajax_unblock_calendar_time', array($this, 'handle_unblock_calendar_time'));
        add_action('wp_ajax_nopriv_unblock_calendar_time', array($this, 'handle_unblock_calendar_time'));
        add_action('wp_ajax_check_time_slot_status', array($this, 'handle_check_time_slot_status'));
        add_action('wp_ajax_nopriv_check_time_slot_status', array($this, 'handle_check_time_slot_status'));
        add_action('wp_ajax_debug_calendar_session', array($this, 'handle_debug_calendar_session'));
        add_action('wp_ajax_nopriv_debug_calendar_session', array($this, 'handle_debug_calendar_session'));
        add_action('wp_ajax_fix_missing_time_slots', array($this, 'handle_fix_missing_time_slots'));
        add_action('wp_ajax_nopriv_fix_missing_time_slots', array($this, 'handle_fix_missing_time_slots'));
        
        // WooCommerce hooks for dynamic pricing
        add_action('woocommerce_before_calculate_totals', array($this, 'set_cart_item_price'));
        add_filter('woocommerce_cart_item_name', array($this, 'modify_cart_item_name'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_appointment_to_order'), 10, 4);
        
        // No longer need to create appointment product as we use actual products
    }
    
    public function activate() {
        $this->create_tables();
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
        $this->fix_missing_time_slots();
    }
    
    private function populate_default_availability() {
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $time_slots = array('09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00');
        
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
    
    private function fix_missing_time_slots() {
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        // Check if 13:00:00 slots are missing and add them
        $missing_slots = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT date FROM $availability_table 
             WHERE date NOT IN (
                 SELECT date FROM $availability_table WHERE time_slot = %s
             ) 
             AND date >= %s 
             AND DAYOFWEEK(date) BETWEEN 2 AND 6
             ORDER BY date",
            '13:00:00',
            date('Y-m-d')
        ));
        
        if (!empty($missing_slots)) {
            foreach ($missing_slots as $slot) {
                $wpdb->insert(
                    $availability_table,
                    array(
                        'date' => $slot->date,
                        'time_slot' => '13:00:00',
                        'is_available' => 1
                    ),
                    array('%s', '%s', '%d')
                );
            }
        }
    }
    
    // Removed create_appointment_product() as we now use actual WooCommerce products
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        // Enqueue Air Datepicker and its locale from CDN
        wp_enqueue_script('air-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/air-datepicker.min.js', array(), '3.5.3', true);
        wp_enqueue_script('air-datepicker-locale', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/locale/en.js', array('air-datepicker'), '3.5.3', true);
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
    
    private function get_waxing_services() {
        $services = array();
        
        // Get all products
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        $products = wc_get_products($args);
        
        foreach ($products as $product) {
            // Solo incluir productos simples y que tengan precio
            if ($product->is_type('simple') && $product->get_price() > 0) {
                $services[] = array(
                    'id' => $product->get_id(),
                    'value' => sanitize_title($product->get_name()),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'sku' => $product->get_sku(),
                );
            }
        }
        
        return $services;
    }
    
    public function add_appointment_modal() {
        $services = $this->get_waxing_services();
        ?>
        <!-- Loading Modal -->
        <div id="waxing-loading-modal" class="modal loading-modal">
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p class="loading-text">Processing your request...</p>
            </div>
        </div>
        
        <!-- Main Appointment Modal -->
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
                            <?php foreach ($services as $service): ?>
                            <option value="<?php echo esc_attr($service['value']); ?>" data-price="<?php echo esc_attr($service['price']); ?>" data-product-id="<?php echo esc_attr($service['id']); ?>">
                                <?php echo esc_html($service['name']); ?> - $<?php echo number_format($service['price'], 2); ?>
                            </option>
                            <?php endforeach; ?>
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
                            <p><strong>Deposit Required (20%):</strong> $<span id="deposit-price">0</span></p>
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
        // For public-facing functionality, we'll verify nonce but not die on failure
        if (!wp_verify_nonce($_POST['nonce'], 'waxing_appointments_nonce')) {
            // Log the nonce failure but continue processing
            error_log('Waxing Appointments: Nonce verification failed for check_availability');
        }
        
        $date = sanitize_text_field($_POST['date']);
        // Normalize date to Y-m-d if a slash-formatted string is received
        if (strpos($date, '/') !== false) {
            $dt = DateTime::createFromFormat('m/d/Y', $date);
            if ($dt) {
                $date = $dt->format('Y-m-d');
            }
        }
        
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
        
        // Normalize date to Y-m-d if a slash-formatted string is received
        if (strpos($date, '/') !== false) {
            $dt = DateTime::createFromFormat('m/d/Y', $date);
            if ($dt) {
                $date = $dt->format('Y-m-d');
            }
        }
        $time = sanitize_text_field($_POST['appointment_time']);
        
        if (!$this->is_time_available($date, $time)) {
            wp_send_json_error('This time slot is no longer available.');
            return;
        }
        
        // Get product price from WooCommerce
        $product_id = null;
        $services = $this->get_waxing_services();
        foreach ($services as $srv) {
            if ($srv['value'] === $service) {
                $total_price = $srv['price'];
                $product_id = $srv['id'];
                break;
            }
        }
        
        if (!isset($total_price) || !$product_id) {
            wp_send_json_error('Invalid service selected.');
            return;
        }
        
        $deposit = $total_price * 0.2; // 20% deposit
        
        global $wpdb;
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        
        $result = $wpdb->insert(
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
        
        if ($result) {
            $appointment_id = $wpdb->insert_id;
            $this->mark_time_unavailable($date, $time);
            
            try {
                $checkout_url = $this->create_checkout_session($appointment_id, $deposit, $name, $email);
                if ($checkout_url === home_url()) {
                    throw new Exception('Failed to create checkout session');
                }
                wp_send_json_success(array('checkout_url' => $checkout_url));
            } catch (Exception $e) {
                // Revert the appointment and availability if checkout fails
                $wpdb->delete($appointments_table, array('id' => $appointment_id), array('%d'));
                $this->mark_time_available($date, $time);
                wp_send_json_error('Failed to create checkout session. Please try again.');
            }
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
    
    private function mark_time_available($date, $time) {
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        $wpdb->update(
            $availability_table,
            array('is_available' => 1),
            array('date' => $date, 'time_slot' => $time),
            array('%d'),
            array('%s', '%s')
        );
    }
    
    private function create_checkout_session($appointment_id, $deposit, $customer_name, $customer_email) {
        if (!class_exists('WooCommerce')) {
            throw new Exception('WooCommerce is not active');
        }
        
        // Get the selected service product ID from the appointment
        global $wpdb;
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $appointments_table WHERE id = %d",
            $appointment_id
        ));
        
        if (!$appointment) {
            throw new Exception('Appointment not found');
        }
        
        // Get the product ID from the service value
        $services = $this->get_waxing_services();
        $product_id = null;
        foreach ($services as $service) {
            if ($service['value'] === $appointment->service_id) {
                $product_id = $service['id'];
                break;
            }
        }
        
        if (!$product_id) {
            throw new Exception('Product not found');
        }
        
        // Get the product
        $product = wc_get_product($product_id);
        if (!$product) {
            throw new Exception('Invalid product');
        }
        
        // Initialize WooCommerce session if needed
        if (!WC()->session) {
            if (WC()->is_rest_api_request()) {
                WC()->initialize_session();
            } else {
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }
        }
        
        // Initialize cart if needed
        if (!WC()->cart) {
            WC()->initialize_cart();
        }
        
        // Empty cart
        WC()->cart->empty_cart();
        
        // Add the product with appointment details as cart item data
        $cart_item_data = array(
            'appointment_id' => $appointment_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'deposit_amount' => $deposit
        );
        
        // Add product to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
        
        if (!$cart_item_key) {
            throw new Exception('Failed to add product to cart');
        }
        
        // Force cart calculation
        WC()->cart->calculate_totals();
        
        // Get checkout URL
        $checkout_url = wc_get_checkout_url();
        if (!$checkout_url) {
            throw new Exception('Failed to get checkout URL');
        }
        
        return $checkout_url;
    }
    
    public function set_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['appointment_id'])) {
                // Get the regular price and calculate 40% deposit
                $regular_price = $cart_item['data']->get_regular_price();
                $deposit_amount = $regular_price * 0.2;
                $cart_item['data']->set_price($deposit_amount);
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
                $service_name = ucwords(str_replace('_', ' ', $appointment->service_id));
                $date = date('M j, Y', strtotime($appointment->appointment_date));
                $time = date('g:i A', strtotime($appointment->appointment_time));
                
                $product_name = "Appointment Deposit - {$service_name}<br><small>Date: {$date} at {$time}</small>";
            }
        }
        
        return $product_name;
    }
    
    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['appointment_id'])) {
            global $wpdb;
            $appointments_table = $wpdb->prefix . 'waxing_appointments';
            
            $appointment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $appointments_table WHERE id = %d",
                $cart_item['appointment_id']
            ));
            
            if ($appointment) {
                $service_name = ucwords(str_replace('_', ' ', $appointment->service_id));
                $date = date('M j, Y', strtotime($appointment->appointment_date));
                $time = date('g:i A', strtotime($appointment->appointment_time));
                
                $item_data[] = array(
                    'name'  => 'Service',
                    'value' => $service_name,
                    'display' => $service_name
                );
                $item_data[] = array(
                    'name'  => 'Appointment Date',
                    'value' => $date,
                    'display' => $date
                );
                $item_data[] = array(
                    'name'  => 'Appointment Time',
                    'value' => $time,
                    'display' => $time
                );
            }
        }
        
        return $item_data;
    }
    
    public function save_appointment_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['appointment_id'])) {
            $item->add_meta_data('Appointment ID', $values['appointment_id']);
            $item->add_meta_data('Customer Name', $values['customer_name']);
            $item->add_meta_data('Customer Email', $values['customer_email']);
            
            // Fetch appointment details to store date/time/service in the order item
            global $wpdb;
            $appointments_table = $wpdb->prefix . 'waxing_appointments';
            $appointment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $appointments_table WHERE id = %d",
                $values['appointment_id']
            ));
            
            if ($appointment) {
                $service_name = ucwords(str_replace('_', ' ', $appointment->service_id));
                $date = date('M j, Y', strtotime($appointment->appointment_date));
                $time = date('g:i A', strtotime($appointment->appointment_time));
                $item->add_meta_data('Service', $service_name);
                $item->add_meta_data('Appointment Date', $date);
                $item->add_meta_data('Appointment Time', $time);
            }
            
            // Update appointment with order ID
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
        
        add_submenu_page(
            'waxing-appointments',
            'Calendar Admin Settings',
            'Calendar Settings',
            'manage_options',
            'waxing-calendar-settings',
            array($this, 'calendar_settings_page')
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
    
    public function calendar_settings_page() {
        if (isset($_POST['submit'])) {
            $username = sanitize_text_field($_POST['calendar_username']);
            $password = sanitize_text_field($_POST['calendar_password']);
            
            update_option('waxing_calendar_admin_username', $username);
            update_option('waxing_calendar_admin_password', $password);
            
            echo '<div class="notice notice-success"><p>Calendar admin credentials updated successfully!</p></div>';
        }
        
        $current_username = get_option('waxing_calendar_admin_username', 'admin');
        $current_password = get_option('waxing_calendar_admin_password', 'waxing2024');
        ?>
        <div class="wrap">
            <h1>Calendar Admin Settings</h1>
            <p>Configure the login credentials for the standalone calendar admin panel at: <strong><?php echo home_url('/calendar-admin'); ?></strong></p>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">Username</th>
                        <td>
                            <input type="text" name="calendar_username" value="<?php echo esc_attr($current_username); ?>" class="regular-text" required />
                            <p class="description">Username for calendar admin login</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Password</th>
                        <td>
                            <input type="password" name="calendar_password" value="<?php echo esc_attr($current_password); ?>" class="regular-text" required />
                            <p class="description">Password for calendar admin login</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Update Credentials'); ?>
            </form>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h3>How to Access Calendar Admin</h3>
                <ol>
                    <li>Go to: <a href="<?php echo home_url('/calendar-admin'); ?>" target="_blank"><?php echo home_url('/calendar-admin'); ?></a></li>
                    <li>Use the credentials configured above</li>
                    <li>Manage appointment availability independently of WordPress</li>
                </ol>
                <p><strong>Note:</strong> This calendar admin system is completely independent of WordPress user accounts. Anyone with these credentials can access the calendar management interface.</p>
            </div>
        </div>
        <?php
    }
    
    public function handle_calendar_admin_route() {
        // Check if this is the calendar admin route
        $request_uri = $_SERVER['REQUEST_URI'];
        $parsed_url = parse_url($request_uri);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        
        // Remove trailing slash and check if path ends with calendar-admin
        $path = rtrim($path, '/');
        
        if (isset($_GET['calendar-admin']) || 
            $path === '/calendar-admin' || 
            substr($path, -15) === '/calendar-admin' ||
            (is_404() && strpos($request_uri, 'calendar-admin') !== false)) {
            
            if (!session_id()) {
                session_start();
            }
            
            // Handle logout
            if (isset($_GET['logout'])) {
                session_destroy();
                wp_redirect(home_url('/calendar-admin'));
                exit;
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
    
    public function parse_calendar_admin_request($wp) {
        if (isset($wp->request) && $wp->request === 'calendar-admin') {
            // Mark this as a valid request to prevent 404
            $wp->matched_rule = 'calendar-admin';
            $wp->matched_query = 'calendar-admin=1';
            $wp->query_vars['calendar-admin'] = '1';
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
                            password: $('#password').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                $('#error-message').text(response.data).show();
                            }
                        },
                        error: function() {
                            $('#error-message').text('Login error. Please try again.').show();
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
        // Bypass WordPress nonce for standalone login
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            wp_send_json_error('Missing credentials');
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        
        // Get credentials from WordPress options (but authentication is still independent)
        $admin_username = get_option('waxing_calendar_admin_username', 'admin');
        $admin_password = get_option('waxing_calendar_admin_password', 'waxing2024');
        
        if ($username === $admin_username && $password === $admin_password) {
            if (!session_id()) {
                session_start();
            }
            $_SESSION['calendar_admin_logged_in'] = true;
            $_SESSION['calendar_admin_user'] = $username;
            $_SESSION['calendar_admin_login_time'] = time();
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
        
        // Get availability for next 60 days
        $availability = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $availability_table WHERE date >= %s AND date <= %s ORDER BY date, time_slot",
            date('Y-m-d'),
            date('Y-m-d', strtotime('+60 days'))
        ));
        
        // Prepare data for FullCalendar
        $calendar_events = array();
        
        // Add appointments as events
        foreach ($appointments as $appointment) {
            $service_name = str_replace('_', ' ', ucwords($appointment->service_id));
            $calendar_events[] = array(
                'id' => 'appointment_' . $appointment->id,
                'title' => $appointment->customer_name . ' - ' . $service_name,
                'start' => $appointment->appointment_date . 'T' . $appointment->appointment_time,
                'end' => date('Y-m-d\TH:i:s', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time . ' +1 hour')),
                'backgroundColor' => '#0073aa',
                'borderColor' => '#005a87',
                'extendedProps' => array(
                    'type' => 'appointment',
                    'customer_name' => $appointment->customer_name,
                    'customer_email' => $appointment->customer_email,
                    'customer_phone' => $appointment->customer_phone,
                    'service' => $service_name,
                    'status' => $appointment->status,
                    'total_price' => $appointment->total_price,
                    'deposit_paid' => $appointment->deposit_paid
                )
            );
        }
        
        // Add blocked time slots as events
        $blocked_slots = array();
        foreach ($availability as $slot) {
            if (!$slot->is_available) {
                $slot_key = $slot->date . '_' . $slot->time_slot;
                // Check if this slot is not already booked by an appointment
                $is_booked = false;
                foreach ($appointments as $appointment) {
                    if ($appointment->appointment_date === $slot->date && $appointment->appointment_time === $slot->time_slot) {
                        $is_booked = true;
                        break;
                    }
                }
                
                if (!$is_booked) {
                    $calendar_events[] = array(
                        'id' => 'blocked_' . $slot->date . '_' . str_replace(':', '', $slot->time_slot),
                        'title' => 'Blocked',
                        'start' => $slot->date . 'T' . $slot->time_slot,
                        'end' => date('Y-m-d\TH:i:s', strtotime($slot->date . ' ' . $slot->time_slot . ' +1 hour')),
                        'backgroundColor' => '#d63638',
                        'borderColor' => '#a02622',
                        'extendedProps' => array(
                            'type' => 'blocked',
                            'date' => $slot->date,
                            'time' => $slot->time_slot
                        )
                    );
                }
            }
        }
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Calendar Admin Dashboard</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    background: #f6f7f9;
                    line-height: 1.5;
                }
                .header { 
                    background: white; 
                    padding: 25px; 
                    margin-bottom: 25px; 
                    border-radius: 12px; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center;
                    border: 1px solid #e1e5e9;
                }
                .header h1 {
                    margin: 0;
                    color: #1a202c;
                    font-weight: 600;
                }
                .logout-btn { 
                    background: #e53e3e; 
                    color: white; 
                    padding: 10px 20px; 
                    text-decoration: none; 
                    border-radius: 8px;
                    font-weight: 500;
                    transition: background 0.2s;
                }
                .logout-btn:hover {
                    background: #c53030;
                }
                .calendar-container { 
                    background: white; 
                    padding: 25px; 
                    border-radius: 12px; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    border: 1px solid #e1e5e9;
                    margin-bottom: 25px;
                }
                #calendar {
                    max-width: 100%;
                    margin: 0 auto;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin-bottom: 25px;
                }
                .stat-card {
                    background: white;
                    padding: 20px;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    border: 1px solid #e1e5e9;
                    text-align: center;
                }
                .stat-number {
                    font-size: 2.5rem;
                    font-weight: 700;
                    color: #0073aa;
                    margin: 0;
                }
                .stat-label {
                    color: #718096;
                    margin-top: 5px;
                    font-weight: 500;
                }
                .legend {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                }
                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .legend-color {
                    width: 16px;
                    height: 16px;
                    border-radius: 4px;
                }
                .legend-color.appointment {
                    background: #0073aa;
                }
                .legend-color.blocked {
                    background: #d63638;
                }
                .legend-color.available {
                    background: #00a32a;
                }
                .quick-actions {
                    background: white;
                    padding: 25px;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    border: 1px solid #e1e5e9;
                }
                .quick-actions h3 {
                    margin-top: 0;
                    color: #1a202c;
                }
                .time-slot-selector {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                    gap: 10px;
                    margin: 20px 0;
                }
                .time-slot-btn {
                    padding: 10px;
                    border: 2px solid #e2e8f0;
                    background: white;
                    border-radius: 8px;
                    cursor: pointer;
                    text-align: center;
                    transition: all 0.2s;
                    font-size: 14px;
                }
                .time-slot-btn:hover {
                    border-color: #0073aa;
                    background: #f7fafc;
                }
                .time-slot-btn.blocked {
                    background: #fed7d7;
                    border-color: #fc8181;
                    color: #c53030;
                }
                /* FullCalendar customizations */
                .fc-event-title {
                    font-weight: 500;
                }
                .fc-toolbar {
                    margin-bottom: 20px !important;
                }
                .fc-button {
                    background: #0073aa !important;
                    border-color: #0073aa !important;
                }
                .fc-button:hover {
                    background: #005a87 !important;
                    border-color: #005a87 !important;
                }
                .fc-today-button {
                    background: #00a32a !important;
                    border-color: #00a32a !important;
                }
                
                /* Selection styling */
                .fc-highlight {
                    background: rgba(0, 115, 170, 0.3) !important;
                    border: 2px dashed #0073aa !important;
                }
                .fc-select-mirror {
                    background: rgba(0, 115, 170, 0.2) !important;
                    border: 2px solid #0073aa !important;
                    color: #0073aa !important;
                    font-weight: bold;
                }
                
                /* Cursor changes for better UX */
                .fc-timegrid-slot {
                    cursor: crosshair;
                }
                .fc-daygrid-day {
                    cursor: pointer;
                }
                
                /* Selection instructions */
                .selection-instructions {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    text-align: center;
                    font-weight: 500;
                }
                .selection-instructions strong {
                    color: #ffd700;
                }
                @media (max-width: 768px) {
                    .stats-grid { 
                        grid-template-columns: 1fr; 
                    }
                    .legend {
                        justify-content: center;
                    }
                    .header {
                        flex-direction: column;
                        gap: 15px;
                        text-align: center;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📅 Calendar Admin Dashboard</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($appointments); ?></div>
                    <div class="stat-label">Upcoming Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php 
                        $blocked_count = 0;
                        foreach ($availability as $slot) {
                            if (!$slot->is_available) {
                                $is_booked = false;
                                foreach ($appointments as $appointment) {
                                    if ($appointment->appointment_date === $slot->date && $appointment->appointment_time === $slot->time_slot) {
                                        $is_booked = true;
                                        break;
                                    }
                                }
                                if (!$is_booked) $blocked_count++;
                            }
                        }
                        echo $blocked_count;
                    ?></div>
                    <div class="stat-label">Blocked Time Slots</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php 
                        $total_revenue = 0;
                        foreach ($appointments as $appointment) {
                            $total_revenue += $appointment->deposit_paid;
                        }
                        echo number_format($total_revenue, 2);
                    ?></div>
                    <div class="stat-label">Total Deposits</div>
                </div>
            </div>
            
            <!-- Selection Instructions -->
            <div class="selection-instructions">
                <strong>🎯 How to use the calendar:</strong> 
                <strong>DRAG</strong> to select multiple hours | 
                <strong>CLICK</strong> events for details | 
                Switch views with the buttons above
            </div>
            
            <!-- Calendar Container -->
            <div class="calendar-container">
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color appointment"></div>
                        <span>Appointments</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color blocked"></div>
                        <span>Blocked Times</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color available"></div>
                        <span>Available</span>
                    </div>
                </div>
                
                <div id="calendar"></div>
            </div>
            
            <!-- Quick Actions Panel -->
            <div class="quick-actions">
                <h3>🚀 Calendar Management Guide</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">
                        <h4 style="margin: 0 0 10px 0; color: #0073aa;">🖱️ Block Multiple Hours</h4>
                        <ol style="margin: 0; padding-left: 20px;">
                            <li>Switch to <strong>Week</strong> or <strong>Day</strong> view</li>
                            <li><strong>Click and drag</strong> across time slots</li>
                            <li>Confirm to block the selected range</li>
                        </ol>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #d63638;">
                        <h4 style="margin: 0 0 10px 0; color: #d63638;">🔓 Manage Individual Slots</h4>
                        <ul style="margin: 0; padding-left: 20px;">
                            <li><strong>Click</strong> red blocks to unblock</li>
                            <li><strong>Click</strong> empty slots to block</li>
                            <li><strong>Click</strong> blue appointments for details</li>
                        </ul>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <label for="quick-date" style="display: block; margin-bottom: 10px; font-weight: 500;">🗓️ Quick Block Specific Date:</label>
                    <input type="date" id="quick-date" style="padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; margin-right: 10px;">
                    <button onclick="showTimeSlotsForDate()" style="padding: 8px 15px; background: #0073aa; color: white; border: none; border-radius: 6px; cursor: pointer;">Show Available Times</button>
                </div>
                
                <div id="time-slots-container" style="display: none; margin-top: 20px;">
                    <div class="time-slot-selector" id="time-slots"></div>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek', // Start with week view for better time selection
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    height: 'auto',
                    events: <?php echo json_encode($calendar_events); ?>,
                    navLinks: true,
                    // Enable date/time selection
                    selectable: true,
                    selectMirror: true,
                    selectOverlap: false, // Don't allow selection over existing events
                    unselectAuto: true,
                    selectConstraint: {
                        daysOfWeek: [1, 2, 3, 4, 5], // Only weekdays
                        startTime: '09:00',
                        endTime: '18:00'
                    },
                    
                    // Selection callback - triggered when user drags to select time range
                    select: function(selectionInfo) {
                        var startDate = selectionInfo.start;
                        var endDate = selectionInfo.end;
                        
                        // Calculate how many time slots are selected
                        var duration = (endDate - startDate) / (1000 * 60 * 60); // duration in hours
                        var slotsCount = Math.ceil(duration);
                        
                        var action = confirm(
                            'You have selected ' + slotsCount + ' hour(s) from:\n' +
                            startDate.toLocaleString() + ' to ' + endDate.toLocaleString() + '\n\n' +
                            'What would you like to do?\n\n' +
                            'OK = Block these time slots\n' +
                            'Cancel = Clear selection'
                        );
                        
                        if (action) {
                            blockTimeRange(startDate, endDate);
                        }
                        
                        // Clear the selection
                        calendar.unselect();
                    },
                    
                    eventClick: function(info) {
                        var event = info.event;
                        var extendedProps = event.extendedProps;
                        
                        if (extendedProps.type === 'appointment') {
                            alert('📅 Appointment Details:\n\n' +
                                '👤 Customer: ' + extendedProps.customer_name + '\n' +
                                '📧 Email: ' + extendedProps.customer_email + '\n' +
                                '📞 Phone: ' + extendedProps.customer_phone + '\n' +
                                '💄 Service: ' + extendedProps.service + '\n' +
                                '📊 Status: ' + extendedProps.status + '\n' +
                                '💰 Total: $' + extendedProps.total_price + '\n' +
                                '💳 Deposit: $' + extendedProps.deposit_paid);
                        } else if (extendedProps.type === 'blocked') {
                            if (confirm('🚫 This time slot is currently BLOCKED.\n\nDo you want to UNBLOCK it?')) {
                                unblockTimeSlot(extendedProps.date, extendedProps.time);
                            }
                        }
                    },
                    
                    dateClick: function(info) {
                        if (info.view.type === 'dayGridMonth') {
                        calendar.changeView('timeGridWeek', info.dateStr);
                        }
                    },
                    
                    businessHours: {
                        daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
                        startTime: '09:00',
                        endTime: '18:00'
                    },
                    slotMinTime: '09:00:00',
                    slotMaxTime: '18:00:00',
                    slotDuration: '01:00:00', // 1 hour slots
                    snapDuration: '01:00:00' // Snap to 1 hour intervals
                });
                
                calendar.render();
            });
            
            function blockTimeRange(startDate, endDate) {
                var timeSlots = [];
                var current = new Date(startDate);
                
                // Generate all hour slots in the range
                while (current < endDate) {
                    var dateStr = current.toISOString().split('T')[0];
                    var timeStr = current.toTimeString().split(' ')[0]; // HH:MM:SS format
                    
                    timeSlots.push({
                        date: dateStr,
                        time: timeStr
                    });
                    
                    // Move to next hour
                    current.setHours(current.getHours() + 1);
                }
                
                if (timeSlots.length === 0) {
                    alert('❌ No valid time slots found in selection.');
                    return;
                }
                
                // Show progress
                var processed = 0;
                var errors = [];
                
                function processSlot(index) {
                    if (index >= timeSlots.length) {
                        // All done
                        if (errors.length > 0) {
                            alert('⚠️ Blocking completed with some errors:\n' + errors.join('\n'));
                        } else {
                            alert('✅ Successfully blocked ' + timeSlots.length + ' time slot(s)!');
                        }
                        location.reload();
                        return;
                    }
                    
                    var slot = timeSlots[index];
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'block_calendar_time',
                            date: slot.date,
                            time: slot.time
                        },
                        success: function(response) {
                            processed++;
                            if (!response.success) {
                                errors.push(slot.date + ' ' + slot.time + ': ' + response.data);
                            }
                            // Process next slot
                            processSlot(index + 1);
                        },
                        error: function() {
                            processed++;
                            errors.push(slot.date + ' ' + slot.time + ': Network error');
                            // Process next slot anyway
                            processSlot(index + 1);
                        }
                    });
                }
                
                // Start processing
                processSlot(0);
            }
            
            function checkAndToggleTimeSlot(date, time) {
                // Check if time slot exists and toggle its availability
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'check_time_slot_status',
                        date: date,
                        time: time
                    },
                    success: function(response) {
                        if (response.success) {
                            var isAvailable = response.data.is_available;
                            var isBooked = response.data.is_booked;
                            
                            if (isBooked) {
                                alert('❌ This time slot is already booked and cannot be modified.');
                                return;
                            }
                            
                            if (isAvailable) {
                                if (confirm('🚫 Block this time slot?')) {
                                    blockTimeSlot(date, time);
                                }
                            } else {
                                if (confirm('✅ Unblock this time slot?')) {
                                    unblockTimeSlot(date, time);
                                }
                            }
                        } else {
                            alert('❌ Time slot not found in system.');
                        }
                    }
                });
            }
            
            function blockTimeSlot(date, time) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'block_calendar_time',
                        date: date,
                        time: time
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
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
                        time: time
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                    }
                });
            }
            
            function showTimeSlotsForDate() {
                var date = document.getElementById('quick-date').value;
                if (!date) {
                    alert('Please select a date first.');
                    return;
                }
                
                var timeSlots = ['09:00:00', '10:00:00', '11:00:00', '12:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'];
                var container = document.getElementById('time-slots');
                var timeSlotsContainer = document.getElementById('time-slots-container');
                
                container.innerHTML = '';
                
                timeSlots.forEach(function(time) {
                    var button = document.createElement('div');
                    button.className = 'time-slot-btn';
                    button.textContent = time.substring(0, 5);
                    button.onclick = function() {
                        checkAndToggleTimeSlot(date, time);
                    };
                    container.appendChild(button);
                });
                
                timeSlotsContainer.style.display = 'block';
            }
            
            function debugSession() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'debug_calendar_session'
                    },
                    success: function(response) {
                        console.log('Debug Response:', response);
                        if (response.success) {
                            var info = response.data;
                            alert('📊 Session Debug Info:\n\n' +
                                'Session Status: ' + info.session_status + '\n' +
                                'Session ID: ' + info.session_id + '\n' +
                                'Logged In: ' + info.session_logged_in + '\n' +
                                'User: ' + info.session_user + '\n' +
                                'Login Time: ' + info.session_login_time + '\n' +
                                'Current Time: ' + info.current_time + '\n\n' +
                                'Check browser console for full details.');
                        } else {
                            alert('❌ Debug failed: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('❌ Network error during debug: ' + error);
                        console.log('Debug error:', xhr, status, error);
                    }
                });
            }
            
            function testBlockFunction() {
                var testDate = new Date();
                testDate.setDate(testDate.getDate() + 1); // Tomorrow
                var dateStr = testDate.toISOString().split('T')[0];
                var timeStr = '15:00:00'; // 3 PM
                
                console.log('Testing block function with:', dateStr, timeStr);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'block_calendar_time',
                        date: dateStr,
                        time: timeStr
                    },
                    success: function(response) {
                        console.log('Test Block Response:', response);
                        if (response.success) {
                            alert('✅ Test successful: ' + response.data);
                        } else {
                            alert('❌ Test failed: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('❌ Network error during test: ' + error + '\nStatus: ' + status);
                        console.log('Test error details:', xhr.responseText);
                    }
                });
            }
            
            function fixMissingTimeSlots() {
                if (!confirm('🕐 This will add missing 1PM (13:00) time slots to all dates.\n\nContinue?')) {
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'fix_missing_time_slots'
                    },
                    success: function(response) {
                        console.log('Fix Missing Slots Response:', response);
                        if (response.success) {
                            alert(response.data);
                            location.reload(); // Refresh to show new slots
                        } else {
                            alert('❌ Fix failed: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('❌ Network error during fix: ' + error);
                        console.log('Fix error:', xhr, status, error);
                    }
                });
            }
            </script>
        </body>
        </html>
        <?php
    }
    
    public function handle_block_calendar_time() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        if (!isset($_POST['date']) || !isset($_POST['time'])) {
            wp_send_json_error('Missing date or time parameters');
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        // Validate time format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            wp_send_json_error('Invalid time format');
        }
        
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        // Check if the slot exists first
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $availability_table WHERE date = %s AND time_slot = %s",
            $date, $time
        ));
        
        if (!$exists) {
            wp_send_json_error('Time slot does not exist in the system');
        }
        
        $result = $wpdb->update(
            $availability_table,
            array('is_available' => 0),
            array('date' => $date, 'time_slot' => $time),
            array('%d'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success('Time slot blocked successfully');
        } else {
            wp_send_json_error('Failed to block time slot - database error');
        }
    }
    
    public function handle_unblock_calendar_time() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        if (!isset($_POST['date']) || !isset($_POST['time'])) {
            wp_send_json_error('Missing date or time parameters');
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        // Validate time format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            wp_send_json_error('Invalid time format');
        }
        
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        // Check if the slot exists first
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $availability_table WHERE date = %s AND time_slot = %s",
            $date, $time
        ));
        
        if (!$exists) {
            wp_send_json_error('Time slot does not exist in the system');
        }
        
        $result = $wpdb->update(
            $availability_table,
            array('is_available' => 1),
            array('date' => $date, 'time_slot' => $time),
            array('%d'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success('Time slot unblocked successfully');
        } else {
            wp_send_json_error('Failed to unblock time slot - database error');
        }
    }
    
    public function handle_check_time_slot_status() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        if (!isset($_POST['date']) || !isset($_POST['time'])) {
            wp_send_json_error('Missing date or time parameters');
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        // Validate time format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            wp_send_json_error('Invalid time format');
        }
        
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        $appointments_table = $wpdb->prefix . 'waxing_appointments';
        
        // Check if time slot exists in availability table
        $availability = $wpdb->get_row($wpdb->prepare(
            "SELECT is_available FROM $availability_table WHERE date = %s AND time_slot = %s",
            $date, $time
        ));
        
        if (!$availability) {
            wp_send_json_error('Time slot not found in system');
        }
        
        // Check if time slot is booked
        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s",
            $date, $time
        ));
        
        wp_send_json_success(array(
            'is_available' => (bool)$availability->is_available,
            'is_booked' => (bool)$appointment
        ));
    }
    
    public function handle_debug_calendar_session() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $debug_info = array(
            'session_status' => session_status(),
            'session_id' => session_id(),
            'session_logged_in' => isset($_SESSION['calendar_admin_logged_in']) ? $_SESSION['calendar_admin_logged_in'] : 'not_set',
            'session_user' => isset($_SESSION['calendar_admin_user']) ? $_SESSION['calendar_admin_user'] : 'not_set',
            'session_login_time' => isset($_SESSION['calendar_admin_login_time']) ? $_SESSION['calendar_admin_login_time'] : 'not_set',
            'current_time' => time(),
            'post_data' => $_POST,
            'session_data' => $_SESSION
        );
        
        wp_send_json_success($debug_info);
    }
    
    public function handle_fix_missing_time_slots() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        global $wpdb;
        $availability_table = $wpdb->prefix . 'waxing_availability';
        
        // Find all dates that are missing the 13:00:00 slot
        $missing_slots = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT date FROM $availability_table 
             WHERE date NOT IN (
                 SELECT date FROM $availability_table WHERE time_slot = %s
             ) 
             AND date >= %s 
             AND DAYOFWEEK(date) BETWEEN 2 AND 6
             ORDER BY date",
            '13:00:00',
            date('Y-m-d')
        ));
        
        $added_count = 0;
        $errors = array();
        
        if (!empty($missing_slots)) {
            foreach ($missing_slots as $slot) {
                $result = $wpdb->insert(
                    $availability_table,
                    array(
                        'date' => $slot->date,
                        'time_slot' => '13:00:00',
                        'is_available' => 1
                    ),
                    array('%s', '%s', '%d')
                );
                
                if ($result) {
                    $added_count++;
                } else {
                    $errors[] = 'Failed to add slot for ' . $slot->date;
                }
            }
        }
        
        $message = "✅ Fixed missing 1PM time slots!\n\n";
        $message .= "Added: {$added_count} time slots\n";
        
        if (!empty($errors)) {
            $message .= "Errors: " . implode(', ', $errors);
        }
        
        if ($added_count === 0 && empty($errors)) {
            $message = "ℹ️ No missing 1PM slots found. All dates already have 13:00:00 time slots.";
        }
        
        wp_send_json_success($message);
    }
}

new WaxingAppointments();