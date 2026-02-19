<?php
/**
 * WooCommerce integration handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_WooCommerce {
    
    /**
     * Create checkout session for appointment booking
     */
    public static function create_checkout_session($product_id, $payment_amount, $customer_name, $customer_email, $customer_phone, $service_id, $appointment_date, $appointment_time, $total_price, $payment_type = 'deposit') {
        if (!class_exists('WooCommerce')) {
            throw new Exception('WooCommerce is not active');
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
        
        // Add the product with appointment details as cart item data (temporary, not saved to DB yet)
        $cart_item_data = array(
            'appointment_customer_name' => $customer_name,
            'appointment_customer_email' => $customer_email,
            'appointment_customer_phone' => $customer_phone,
            'appointment_service_id' => $service_id,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'appointment_total_price' => $total_price,
            'appointment_payment_amount' => $payment_amount,
            'appointment_payment_type' => $payment_type
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
    
    /**
     * Set cart item price based on payment type
     */
    public static function set_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['appointment_service_id'])) {
                // Use the payment amount directly from cart item data
                if (isset($cart_item['appointment_payment_amount'])) {
                    $cart_item['data']->set_price($cart_item['appointment_payment_amount']);
                } else {
                    // Fallback to 20% deposit if not set
                    $regular_price = $cart_item['data']->get_regular_price();
                    $deposit_amount = $regular_price * 0.2;
                    $cart_item['data']->set_price($deposit_amount);
                }
            }
        }
    }
    
    /**
     * Modify cart item name to show appointment details
     */
    public static function modify_cart_item_name($product_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['appointment_service_id'])) {
            $service_name = ucwords(str_replace('_', ' ', $cart_item['appointment_service_id']));
            $date = date('M j, Y', strtotime($cart_item['appointment_date']));
            $time = date('g:i A', strtotime($cart_item['appointment_time']));

            // Determine payment type label
            $payment_type = isset($cart_item['appointment_payment_type']) ? $cart_item['appointment_payment_type'] : 'deposit';
            $payment_label = ($payment_type === 'full') ? 'Full Payment' : 'Deposit';

            $product_name = "Appointment {$payment_label} - {$service_name}<br><small>Date: {$date} at {$time}</small>";
        }

        return $product_name;
    }
    
    /**
     * Display appointment data in cart
     */
    public static function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['appointment_service_id'])) {
            $service_name = ucwords(str_replace('_', ' ', $cart_item['appointment_service_id']));
            $date = date('M j, Y', strtotime($cart_item['appointment_date']));
            $time = date('g:i A', strtotime($cart_item['appointment_time']));
            
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
        
        return $item_data;
    }
    
    /**
     * Save appointment data to order item meta
     */
    public static function save_appointment_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['appointment_service_id'])) {
            // Save appointment data to order item meta (appointment not created in DB yet)
            $item->add_meta_data('Customer Name', $values['appointment_customer_name']);
            $item->add_meta_data('Customer Email', $values['appointment_customer_email']);
            $item->add_meta_data('Customer Phone', $values['appointment_customer_phone']);

            $service_name = ucwords(str_replace('_', ' ', $values['appointment_service_id']));
            $date = date('M j, Y', strtotime($values['appointment_date']));
            $time = date('g:i A', strtotime($values['appointment_time']));

            // Get payment type
            $payment_type = isset($values['appointment_payment_type']) ? $values['appointment_payment_type'] : 'deposit';
            $payment_amount = isset($values['appointment_payment_amount']) ? $values['appointment_payment_amount'] : ($values['appointment_total_price'] * 0.2);

            $item->add_meta_data('Service', $service_name);
            $item->add_meta_data('Appointment Date', $date);
            $item->add_meta_data('Appointment Time', $time);
            $item->add_meta_data('Total Price', $values['appointment_total_price']);

            // Add payment type specific metadata
            if ($payment_type === 'full') {
                $item->add_meta_data('Payment Type', 'Full Payment');
                $item->add_meta_data('Amount Paid', $payment_amount);
            } else {
                $item->add_meta_data('Payment Type', 'Deposit');
                $item->add_meta_data('Deposit Amount', $payment_amount);
            }

            // Store raw appointment data for later use when payment completes
            $item->add_meta_data('_appointment_data', array(
                'customer_name' => $values['appointment_customer_name'],
                'customer_email' => $values['appointment_customer_email'],
                'customer_phone' => $values['appointment_customer_phone'],
                'service_id' => $values['appointment_service_id'],
                'appointment_date' => $values['appointment_date'],
                'appointment_time' => $values['appointment_time'],
                'total_price' => $values['appointment_total_price'],
                'deposit_paid' => $payment_amount,
                'payment_type' => $payment_type
            ));
        }
    }
    
    /**
     * Create appointment in database when payment is completed
     */
    public static function create_appointment_on_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if order has appointment items
        foreach ($order->get_items() as $item_id => $item) {
            $appointment_data = $item->get_meta('_appointment_data');
            
            if ($appointment_data && is_array($appointment_data)) {
                global $wpdb;
                $appointments_table = Waxing_Database::get_table_name();
                
                // Normalize time format to HH:MM:SS before checking duplicates, availability and saving
                $appointment_time = $appointment_data['appointment_time'];
                if (strlen($appointment_time) === 5) {
                    $appointment_time = $appointment_time . ':00';
                }
                
                // Check if appointment already exists (prevent duplicates)
                // Compare using both formats to handle any inconsistencies
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $appointments_table WHERE appointment_date = %s AND (appointment_time = %s OR appointment_time = %s) AND customer_email = %s AND order_id = %d",
                    $appointment_data['appointment_date'],
                    $appointment_time,
                    substr($appointment_time, 0, 5),
                    $appointment_data['customer_email'],
                    $order_id
                ));
                
                if ($existing) {
                    // Appointment already exists, skip
                    continue;
                }
                
                // Check if time slot is still available
                if (!Waxing_Appointments_Handler::is_time_available($appointment_data['appointment_date'], $appointment_time)) {
                    error_log('WaxingAppointments: Time slot no longer available for order ' . $order_id);
                    // Send notification to admin or customer
                    continue;
                }
                
                // Insert appointment into database
                $result = $wpdb->insert(
                    $appointments_table,
                    array(
                        'customer_name' => $appointment_data['customer_name'],
                        'customer_email' => $appointment_data['customer_email'],
                        'customer_phone' => $appointment_data['customer_phone'],
                        'service_id' => $appointment_data['service_id'],
                        'appointment_date' => $appointment_data['appointment_date'],
                        'appointment_time' => $appointment_time,
                        'total_price' => $appointment_data['total_price'],
                        'deposit_paid' => $appointment_data['deposit_paid'],
                        'status' => 'confirmed',
                        'order_id' => $order_id
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d')
                );
                
                if ($result) {
                    $appointment_id = $wpdb->insert_id;
                    // Mark time as unavailable
                    Waxing_Appointments_Handler::mark_time_unavailable($appointment_data['appointment_date'], $appointment_time);

                    // Update order item meta with appointment ID
                    wc_update_order_item_meta($item_id, 'Appointment ID', $appointment_id);

                    // Send SMS notification to admin if Twilio is enabled
                    $twilio_enabled = get_option('waxing_twilio_enabled', '0');
                    if ($twilio_enabled === '1') {
                        $sms_result = Waxing_Twilio::send_appointment_notification_to_admin($appointment_data);
                        if ($sms_result['success']) {
                            error_log('WaxingAppointments: SMS notification sent to admin for appointment ' . $appointment_id);
                        } else {
                            error_log('WaxingAppointments: Failed to send SMS notification to admin: ' . $sms_result['message']);
                        }
                    }
                } else {
                    error_log('WaxingAppointments: Failed to create appointment for order ' . $order_id);
                }
            }
        }
    }
}

