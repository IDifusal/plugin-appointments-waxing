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
        
        // First, delete any existing record with this date/time (to handle status changes)
        $wpdb->delete(
            $appointments_table,
            array('appointment_date' => $date, 'appointment_time' => $time),
            array('%s', '%s')
        );
        
        // Then insert the blocked slot
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
            wp_send_json_success('Time slot blocked successfully');
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
        $skipped_count = 0;
        
        foreach ($time_slots as $time) {
            // Normalize time format to HH:MM:SS
            if (strlen($time) === 5) {
                $time = $time . ':00';
            }
            
            // Check if slot is already booked
            $is_booked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s AND status IN ('booked', 'confirmed')",
                $date, $time
            ));
            
            if ($is_booked) {
                $skipped_count++;
                continue;
            }
            
            // First, delete any existing record with this date/time (to handle status changes)
            $wpdb->delete(
                $appointments_table,
                array('appointment_date' => $date, 'appointment_time' => $time),
                array('%s', '%s')
            );
            
            // Then insert the blocked slot
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
            if ($skipped_count > 0) {
                $message .= " {$skipped_count} slot(s) skipped (already booked).";
            }
            wp_send_json_success($message);
        } else {
            wp_send_json_error('No slots could be blocked. All slots may already be booked.');
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
}

