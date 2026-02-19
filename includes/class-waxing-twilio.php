<?php
/**
 * Twilio SMS handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Twilio {

    /**
     * Send SMS via Twilio API
     *
     * @param string $to Phone number to send to (E.164 format)
     * @param string $message Message content
     * @return array Response with success status and message
     */
    public static function send_sms($to, $message) {
        // Get Twilio credentials from WordPress options
        $account_sid = get_option('waxing_twilio_account_sid', '');
        $auth_token = get_option('waxing_twilio_auth_token', '');
        $from_number = get_option('waxing_twilio_phone_number', '');

        // Validate credentials
        if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
            return array(
                'success' => false,
                'message' => 'Twilio credentials not configured'
            );
        }

        // Normalize phone number to E.164 format
        $to = self::normalize_phone_number($to);
        if (!$to) {
            return array(
                'success' => false,
                'message' => 'Invalid phone number format'
            );
        }

        // Prepare Twilio API request
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";

        $post_data = array(
            'From' => $from_number,
            'To' => $to,
            'Body' => $message
        );

        // Make request using WordPress HTTP API
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $post_data
        ));

        // Check for errors
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'HTTP request failed: ' . $response->get_error_message()
            );
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Check response code
        if ($http_code >= 200 && $http_code < 300) {
            return array(
                'success' => true,
                'message' => 'SMS sent successfully',
                'sid' => isset($data['sid']) ? $data['sid'] : null,
                'data' => $data
            );
        } else {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown error';
            return array(
                'success' => false,
                'message' => 'Twilio API error: ' . $error_message,
                'code' => isset($data['code']) ? $data['code'] : null
            );
        }
    }

    /**
     * Normalize phone number to E.164 format
     *
     * @param string $phone Phone number
     * @return string|false Normalized phone number or false if invalid
     */
    public static function normalize_phone_number($phone) {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);

        // If 10 digits, assume US number and add +1
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        // If 11 digits starting with 1, assume US number
        if (strlen($digits) === 11 && substr($digits, 0, 1) === '1') {
            return '+' . $digits;
        }

        // If already has country code format
        if (strlen($digits) > 10) {
            return '+' . $digits;
        }

        return false;
    }

    /**
     * Test Twilio configuration via AJAX
     */
    public static function test_config() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('WaxingAppointments Twilio: Test config failed - insufficient permissions');
            wp_send_json_error('Insufficient permissions');
        }

        check_ajax_referer('waxing_twilio_test', 'nonce');

        // Get Twilio credentials
        $account_sid = get_option('waxing_twilio_account_sid', '');
        $auth_token = get_option('waxing_twilio_auth_token', '');
        $from_number = get_option('waxing_twilio_phone_number', '');

        if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
            error_log('WaxingAppointments Twilio: Test config failed - credentials not configured');
            wp_send_json_error('Twilio credentials not fully configured. Please fill all fields.');
        }

        error_log('WaxingAppointments Twilio: Testing configuration for account ' . $account_sid);

        // Test by fetching account info from Twilio API
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}.json";

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token)
            )
        ));

        if (is_wp_error($response)) {
            error_log('WaxingAppointments Twilio: Test config failed - ' . $response->get_error_message());
            wp_send_json_error('HTTP request failed: ' . $response->get_error_message());
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($http_code === 200) {
            $account_name = isset($data['friendly_name']) ? $data['friendly_name'] : 'N/A';
            $status = isset($data['status']) ? $data['status'] : 'unknown';

            error_log('WaxingAppointments Twilio: Configuration valid - Account: ' . $account_name . ', Status: ' . $status);

            wp_send_json_success(array(
                'message' => 'Twilio configuration is valid',
                'account_name' => $account_name,
                'phone_number' => $from_number,
                'status' => ucfirst($status)
            ));
        } else {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown error';
            error_log('WaxingAppointments Twilio: Test config failed - ' . $error_message);
            wp_send_json_error('Invalid credentials: ' . $error_message);
        }
    }

    /**
     * Send test SMS via AJAX
     */
    public static function send_test_sms() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('WaxingAppointments Twilio: Test SMS failed - insufficient permissions');
            wp_send_json_error('Insufficient permissions');
        }

        check_ajax_referer('waxing_twilio_test', 'nonce');

        $test_number = isset($_POST['test_number']) ? sanitize_text_field($_POST['test_number']) : '';

        if (empty($test_number)) {
            error_log('WaxingAppointments Twilio: Test SMS failed - no phone number provided');
            wp_send_json_error('Please provide a phone number');
        }

        error_log('WaxingAppointments Twilio: Attempting to send test SMS to ' . $test_number);

        $message = "Test message from Waxing Appointments plugin. Your Twilio integration is working correctly!";

        $result = self::send_sms($test_number, $message);

        if ($result['success']) {
            error_log('WaxingAppointments Twilio: Test SMS sent successfully. SID: ' . $result['sid']);
            wp_send_json_success(array(
                'message' => 'Test SMS sent successfully! Check your phone at ' . $test_number,
                'sid' => $result['sid']
            ));
        } else {
            error_log('WaxingAppointments Twilio: Test SMS failed - ' . $result['message']);
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Send appointment notification to admin
     *
     * @param array $appointment_data Appointment data
     * @return array Result
     */
    public static function send_appointment_notification_to_admin($appointment_data) {
        // Get admin phone number
        $admin_phone = get_option('waxing_twilio_admin_number', '');

        if (empty($admin_phone)) {
            return array('success' => false, 'message' => 'Admin phone number not configured');
        }

        // Extract appointment details
        $customer_name = isset($appointment_data['customer_name']) ? $appointment_data['customer_name'] : 'Customer';
        $customer_phone = isset($appointment_data['customer_phone']) ? $appointment_data['customer_phone'] : 'N/A';
        $customer_email = isset($appointment_data['customer_email']) ? $appointment_data['customer_email'] : 'N/A';
        $service = isset($appointment_data['service_id']) ? ucwords(str_replace('_', ' ', $appointment_data['service_id'])) : 'Service';
        $date = isset($appointment_data['appointment_date']) ? date('M j, Y', strtotime($appointment_data['appointment_date'])) : '';
        $time = isset($appointment_data['appointment_time']) ? date('g:i A', strtotime($appointment_data['appointment_time'])) : '';

        // Create admin notification message (short format for single SMS)
        $message = "New Appt: {$customer_name} - {$service} - {$date} {$time} - {$customer_phone}";

        return self::send_sms($admin_phone, $message);
    }

    /**
     * Send appointment reminder SMS
     *
     * @param array $appointment_data Appointment data
     * @return array Result
     */
    public static function send_appointment_reminder($appointment_data) {
        $phone = isset($appointment_data['customer_phone']) ? $appointment_data['customer_phone'] : '';
        $name = isset($appointment_data['customer_name']) ? $appointment_data['customer_name'] : 'Customer';
        $date = isset($appointment_data['appointment_date']) ? date('M j, Y', strtotime($appointment_data['appointment_date'])) : '';
        $time = isset($appointment_data['appointment_time']) ? date('g:i A', strtotime($appointment_data['appointment_time'])) : '';

        if (empty($phone)) {
            return array('success' => false, 'message' => 'No phone number provided');
        }

        $business_name = get_option('waxing_business_name', 'South Beach Waxing');

        $message = "Reminder: You have an appointment tomorrow at {$time} with {$business_name}. We look forward to seeing you!";

        return self::send_sms($phone, $message);
    }
}
