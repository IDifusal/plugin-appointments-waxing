<?php
/**
 * Calendar Admin handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/class-waxing-calendar-admin-views.php';

class Waxing_Calendar_Admin {
    
    /**
     * Handle calendar admin route
     */
    public static function handle_route() {
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
                Waxing_Calendar_Admin_Views::show_login();
                exit;
            } else {
                Waxing_Calendar_Admin_Views::show_dashboard();
                exit;
            }
        }
    }
    
    /**
     * Parse calendar admin request
     */
    public static function parse_request($wp) {
        if (isset($wp->request) && $wp->request === 'calendar-admin') {
            // Mark this as a valid request to prevent 404
            $wp->matched_rule = 'calendar-admin';
            $wp->matched_query = 'calendar-admin=1';
            $wp->query_vars['calendar-admin'] = '1';
        }
    }
    
    /**
     * Handle calendar admin login
     */
    public static function handle_login() {
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
    
    /**
     * Handle blocking calendar time
     */
    public static function handle_block_time() {
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
        
        // Normalize time format to HH:MM:SS
        if (strlen($time) === 5) {
            $time = $time . ':00';
        }
        
        // Validate time format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            wp_send_json_error('Invalid time format');
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        // Check if the slot is already booked by a confirmed appointment
        $is_booked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s AND status IN ('booked', 'confirmed')",
            $date, $time
        ));
        
        if ($is_booked) {
            wp_send_json_error('Cannot block a time slot that is already booked');
        }
        
        // Get optional blocked_for (user name) parameter
        $blocked_for = isset($_POST['blocked_for']) ? sanitize_text_field($_POST['blocked_for']) : '';
        $blocked_for = trim($blocked_for);
        
        // First, delete any existing record with this date/time (to handle status changes)
        $wpdb->delete(
            $appointments_table,
            array('appointment_date' => $date, 'appointment_time' => $time),
            array('%s', '%s')
        );
        
        // Prepare data array
        $insert_data = array(
            'appointment_date' => $date,
            'appointment_time' => $time,
            'status' => 'blocked'
        );
        $insert_format = array('%s', '%s', '%s');
        
        // Add customer_name if provided
        if (!empty($blocked_for)) {
            $insert_data['customer_name'] = $blocked_for;
            $insert_format[] = '%s';
        }
        
        // Then insert the blocked slot
        $result = $wpdb->insert(
            $appointments_table,
            $insert_data,
            $insert_format
        );
        
        if ($result !== false) {
            $message = 'Time slot blocked successfully';
            if (!empty($blocked_for)) {
                $message .= ' for ' . esc_html($blocked_for);
            }
            wp_send_json_success($message);
        } else {
            $error = $wpdb->last_error;
            wp_send_json_error('Failed to block time slot - database error: ' . $error);
        }
    }
    
    /**
     * Handle unblocking calendar time
     */
    public static function handle_unblock_time() {
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
        
        // Normalize time format
        if (strlen($time) === 5) {
            $time = $time . ':00';
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        // Delete the blocked slot
        $result = $wpdb->delete(
            $appointments_table,
            array('appointment_date' => $date, 'appointment_time' => $time, 'status' => 'blocked'),
            array('%s', '%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success('Time slot unblocked successfully');
        } else {
            wp_send_json_error('Failed to unblock time slot - database error');
        }
    }
    
    /**
     * Handle blocking entire day
     */
    public static function handle_block_day() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        if (!isset($_POST['date'])) {
            wp_send_json_error('Missing date parameter');
        }
        
        $date = sanitize_text_field($_POST['date']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        // Get all time slots for this date
        $time_slots = Waxing_Services::get_time_slots_for_date($date);
        
        if (empty($time_slots)) {
            wp_send_json_error('No time slots available for this date (day may be closed)');
        }
        
        $blocked_count = 0;
        $skipped_appointments = 0;
        $skipped_already_blocked = 0;
        
        foreach ($time_slots as $time) {
            // Normalize time format to HH:MM:SS
            if (strlen($time) === 5) {
                $time = $time . ':00';
            }
            
            // Check if slot is already booked by an appointment
            $is_booked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s AND status IN ('booked', 'confirmed')",
                $date, $time
            ));
            
            if ($is_booked) {
                $skipped_appointments++;
                continue;
            }
            
            // Check if slot is already blocked (preserve existing blocked slots)
            $is_already_blocked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s AND status = 'blocked'",
                $date, $time
            ));
            
            if ($is_already_blocked) {
                $skipped_already_blocked++;
                continue;
            }
            
            // Insert the blocked slot (no customer_name for day blocking)
            $result = $wpdb->insert(
                $appointments_table,
                array(
                    'appointment_date' => $date,
                    'appointment_time' => $time,
                    'status' => 'blocked'
                ),
                array('%s', '%s', '%s')
            );
            
            if ($result !== false) {
                $blocked_count++;
            }
        }
        
        if ($blocked_count > 0) {
            $message = "Day blocked successfully. {$blocked_count} time slot(s) blocked.";
            if ($skipped_appointments > 0) {
                $message .= " {$skipped_appointments} slot(s) skipped (already booked by appointments).";
            }
            if ($skipped_already_blocked > 0) {
                $message .= " {$skipped_already_blocked} slot(s) skipped (already blocked).";
            }
            wp_send_json_success($message);
        } else {
            $message = 'No slots could be blocked.';
            if ($skipped_appointments > 0 || $skipped_already_blocked > 0) {
                $message .= ' All slots are already booked or blocked.';
            }
            wp_send_json_error($message);
        }
    }
    
    /**
     * Handle unblocking entire day
     */
    public static function handle_unblock_day() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        if (!isset($_POST['date'])) {
            wp_send_json_error('Missing date parameter');
        }
        
        $date = sanitize_text_field($_POST['date']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        // Delete all blocked slots for this date
        $result = $wpdb->delete(
            $appointments_table,
            array('appointment_date' => $date, 'status' => 'blocked'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success("Day unblocked successfully. {$result} time slot(s) unblocked.");
        } else {
            wp_send_json_error('Failed to unblock day - database error');
        }
    }
    
    /**
     * Get calendar events and fully blocked days data
     * Returns array with 'events' and 'fully_blocked_days' keys
     */
    public static function get_calendar_data() {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        $today = date('Y-m-d');
        $future_date = date('Y-m-d', strtotime('+60 days'));
        
        $appointments_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $appointments_table WHERE appointment_date >= %s AND appointment_date <= %s AND status != 'blocked' ORDER BY appointment_date, appointment_time",
            $today,
            $future_date
        ));
        
        $blocked_records = $wpdb->get_results(
            "SELECT * FROM $appointments_table WHERE status = 'blocked' ORDER BY appointment_date, appointment_time"
        );
        
        $appointments = array();
        $blocked_slots = array();
        
        foreach ($appointments_records as $record) {
            $appointments[] = $record;
        }
        
        foreach ($blocked_records as $record) {
            $blocked_slots[] = $record;
        }
        
        $calendar_events = array();
        
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
        
        foreach ($blocked_slots as $slot) {
            $blocked_title = 'Blocked';
            if (!empty($slot->customer_name)) {
                $blocked_title = 'Blocked for ' . $slot->customer_name;
            }
            
            $calendar_events[] = array(
                'id' => 'blocked_' . $slot->appointment_date . '_' . str_replace(':', '', $slot->appointment_time),
                'title' => $blocked_title,
                'start' => $slot->appointment_date . 'T' . $slot->appointment_time,
                'end' => date('Y-m-d\TH:i:s', strtotime($slot->appointment_date . ' ' . $slot->appointment_time . ' +1 hour')),
                'backgroundColor' => '#d63638',
                'borderColor' => '#a02622',
                'extendedProps' => array(
                    'type' => 'blocked',
                    'date' => $slot->appointment_date,
                    'time' => $slot->appointment_time,
                    'blocked_for' => $slot->customer_name ? $slot->customer_name : ''
                )
            );
        }
        
        $fully_blocked_days = array();
        $blocked_by_date = array();
        foreach ($blocked_slots as $slot) {
            if (!isset($blocked_by_date[$slot->appointment_date])) {
                $blocked_by_date[$slot->appointment_date] = 0;
            }
            $blocked_by_date[$slot->appointment_date]++;
        }
        
        foreach ($blocked_by_date as $date => $blocked_count) {
            $day_of_week = date('w', strtotime($date));
            if ($day_of_week == 0) continue;
            
            $expected_slots = count(Waxing_Services::get_time_slots_for_date($date));
            $booked_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND status IN ('booked', 'confirmed')",
                $date
            ));
            
            if ($blocked_count >= ($expected_slots - $booked_count)) {
                $fully_blocked_days[] = $date;
            }
        }
        
        return array(
            'events' => $calendar_events,
            'fully_blocked_days' => $fully_blocked_days
        );
    }
    
    /**
     * Handle AJAX request to get calendar events
     */
    public static function handle_get_calendar_events() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        $data = self::get_calendar_data();
        wp_send_json_success($data);
    }
}

