<?php
/**
 * Appointments booking handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Appointments_Handler {
    
    /**
     * Check availability for a specific date
     */
    public static function check_availability() {
        // For public-facing functionality, we'll verify nonce but not die on failure
        if (!wp_verify_nonce($_POST['nonce'], 'waxing_appointments_nonce')) {
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

        $office = self::sanitize_office(isset($_POST['office']) ? $_POST['office'] : '');

        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();

        // Get all booked, confirmed, or blocked slots for this date and office
        $booked_slots = $wpdb->get_results($wpdb->prepare(
            "SELECT appointment_time FROM $appointments_table WHERE appointment_date = %s AND office = %s AND status IN ('booked', 'confirmed', 'blocked')",
            $date,
            $office
        ));
        
        $booked_times = array();
        foreach ($booked_slots as $slot) {
            // Normalize time format for comparison (remove seconds if present)
            $normalized_time = substr($slot->appointment_time, 0, 5); // Get HH:MM part
            $booked_times[] = $normalized_time;
        }
        
        // Get time slots based on day of week
        $all_times = Waxing_Services::get_time_slots_for_date($date);
        
        $times = array();
        foreach ($all_times as $time) {
            if (!in_array($time, $booked_times)) {
                $times[] = array(
                    'value' => $time,
                    'label' => date('g:i A', strtotime($time))
                );
            }
        }
        
        wp_send_json_success($times);
    }
    
    /**
     * Handle appointment booking request
     */
    public static function handle_appointment_booking() {
        check_ajax_referer('waxing_appointments_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['customer_name']);
        $email = sanitize_email($_POST['customer_email']);
        $phone = sanitize_text_field($_POST['customer_phone']);
        $service = sanitize_text_field($_POST['service']);
        $office = self::sanitize_office(isset($_POST['office']) ? $_POST['office'] : '');
        $date = sanitize_text_field($_POST['appointment_date']);
        $payment_type = isset($_POST['payment_type']) ? sanitize_text_field($_POST['payment_type']) : 'deposit';

        if (!Waxing_Services::get_office($office)) {
            wp_send_json_error('Please select a valid location.');
            return;
        }

        // Normalize date to Y-m-d if a slash-formatted string is received
        if (strpos($date, '/') !== false) {
            $dt = DateTime::createFromFormat('m/d/Y', $date);
            if ($dt) {
                $date = $dt->format('Y-m-d');
            }
        }
        $time = sanitize_text_field($_POST['appointment_time']);

        if (!self::is_time_available($date, $time, $office)) {
            wp_send_json_error('This time slot is no longer available.');
            return;
        }
        
        // Get product price from WooCommerce
        $product_id = null;
        $services = Waxing_Services::get_waxing_services();
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

        // Calculate payment amount based on payment type
        if ($payment_type === 'full') {
            $payment_amount = $total_price; // 100% payment
        } else {
            $payment_amount = $total_price * 0.2; // 20% deposit (default)
        }
        
        // Don't save to database yet - only save when payment is completed
        // Just validate availability and create checkout session with temporary data
        
        try {
            $checkout_url = Waxing_WooCommerce::create_checkout_session($product_id, $payment_amount, $name, $email, $phone, $service, $date, $time, $total_price, $payment_type, $office);
            if ($checkout_url === home_url()) {
                throw new Exception('Failed to create checkout session');
            }
            wp_send_json_success(array('checkout_url' => $checkout_url));
        } catch (Exception $e) {
            error_log('WaxingAppointments: create_checkout_session failed - ' . $e->getMessage());
            wp_send_json_error('Failed to create checkout session. Please try again.');
        }
    }
    
    /**
     * Sanitize an office slug, falling back to the first configured office.
     */
    public static function sanitize_office($office) {
        $office = sanitize_text_field($office);
        if (Waxing_Services::get_office($office)) {
            return $office;
        }

        // Default to the first configured office for backward compatibility.
        $offices = Waxing_Services::get_offices();
        return key($offices);
    }

    /**
     * Check if a time slot is available for a given office
     */
    public static function is_time_available($date, $time, $office = null) {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();

        $office = self::sanitize_office($office);

        // Normalize time format to HH:MM:SS for database comparison
        if (strlen($time) === 5) {
            $time = $time . ':00';
        }

        // Check if time is within business hours
        if (!self::is_business_hours($date, $time)) {
            return false;
        }

        // Time is available if no record exists with booked, confirmed, or blocked status
        // for this office. Compare both HH:MM:SS and HH:MM formats in the database.
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND office = %s AND (appointment_time = %s OR appointment_time = %s) AND status IN ('booked', 'confirmed', 'blocked')",
            $date, $office, $time, substr($time, 0, 5)
        ));

        return $result == 0;
    }
    
    /**
     * Check if a given date/time falls within business hours
     */
    public static function is_business_hours($date, $time) {
        $day_of_week = date('w', strtotime($date));
        $business_hours = Waxing_Services::get_business_hours($day_of_week);
        if ($business_hours === null) {
            return false; // closed that day
        }

        // Normalize time format for comparison (ensure HH:MM format)
        $normalized_time = substr($time, 0, 5); // Get HH:MM part
        
        // Normalize times to comparable strings
        $start = strtotime($business_hours['start_time']);
        $end = strtotime($business_hours['end_time']);
        $t = strtotime($normalized_time);

        return ($t >= $start && $t < $end);
    }
    
    /**
     * Mark time slot as unavailable
     */
    public static function mark_time_unavailable($date, $time, $office = null) {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();

        $office = self::sanitize_office($office);

        // Update status to booked/confirmed if exists with pending status
        $wpdb->update(
            $appointments_table,
            array('status' => 'confirmed'),
            array('appointment_date' => $date, 'appointment_time' => $time, 'office' => $office, 'status' => 'pending'),
            array('%s'),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Mark time slot as available
     */
    public static function mark_time_available($date, $time, $office = null) {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();

        $office = self::sanitize_office($office);

        // Delete the blocked slot
        $wpdb->delete(
            $appointments_table,
            array('appointment_date' => $date, 'appointment_time' => $time, 'office' => $office, 'status' => 'blocked'),
            array('%s', '%s', '%s', '%s')
        );
    }
}

