<?php
/**
 * Stripe payment link generator
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Stripe {
    
    /**
     * Generate Stripe payment link
     */
    public static function generate_payment_link() {
        // Ensure session is started for calendar admin
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'waxing_appointments_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Get Stripe API key from settings
        $stripe_secret_key = get_option('waxing_stripe_secret_key', '');
        $stripe_mode = get_option('waxing_stripe_mode', 'sandbox');
        
        if (empty($stripe_secret_key)) {
            wp_send_json_error('Stripe API key not configured. Please configure it in Calendar Settings.');
        }
        
        // Get form data
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : 'Payment';
        $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
        $customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
        
        // Validate amount
        if ($amount < 0.50) {
            wp_send_json_error('Amount must be at least $0.50');
        }
        
        // Convert amount to cents for Stripe
        $amount_cents = intval($amount * 100);
        
        // Prepare Stripe API request
        // Note: Payment Links API does not accept 'customer' parameter
        // Instead, we'll append prefilled_email as URL parameter after creation
        $stripe_api_url = 'https://api.stripe.com/v1/payment_links';
        
        $post_data = array(
            'line_items[0][price_data][currency]' => 'usd',
            'line_items[0][price_data][product_data][name]' => $description,
            'line_items[0][price_data][unit_amount]' => $amount_cents,
            'line_items[0][quantity]' => 1,
        );
        
        // Make request to Stripe API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $stripe_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $stripe_secret_key,
            'Content-Type: application/x-www-form-urlencoded'
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Failed to create payment link';
            wp_send_json_error('Stripe API error: ' . $error_message);
        }
        
        $stripe_response = json_decode($response, true);
        
        if (!isset($stripe_response['url'])) {
            wp_send_json_error('Invalid response from Stripe API');
        }
        
        $payment_link = $stripe_response['url'];
        
        // Append prefilled_email parameter to URL if customer email is provided
        // According to Stripe docs: https://docs.stripe.com/payment-links/customize
        if (!empty($customer_email)) {
            $separator = (strpos($payment_link, '?') !== false) ? '&' : '?';
            $payment_link = $payment_link . $separator . 'prefilled_email=' . urlencode($customer_email);
        }
        
        // Generate QR code URL using a QR code service
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($payment_link);
        
        // Save to payment links history
        global $wpdb;
        $payment_links_table = Waxing_Database::get_payment_links_table_name();
        
        $email_sent = 0;
        
        // Send email if customer email is provided
        if (!empty($customer_email)) {
            $email_sent = self::send_payment_link_email($customer_email, $customer_name, $amount, $description, $payment_link);
        }
        
        // Save to database
        $wpdb->insert(
            $payment_links_table,
            array(
                'amount' => $amount,
                'description' => $description,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'payment_link' => $payment_link,
                'qr_code_url' => $qr_code_url,
                'email_sent' => $email_sent
            ),
            array('%f', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        wp_send_json_success(array(
            'payment_link' => $payment_link,
            'qr_code' => $qr_code_url,
            'email_sent' => $email_sent
        ));
    }
    
    /**
     * Send payment link email to customer
     */
    private static function send_payment_link_email($email, $name, $amount, $description, $payment_link) {
        $subject = 'Payment Link - ' . get_bloginfo('name');
        
        $message = "Hello";
        if (!empty($name)) {
            $message .= " " . $name;
        }
        $message .= ",\n\n";
        $message .= "A payment link has been created for you.\n\n";
        $message .= "Amount: $" . number_format($amount, 2) . "\n";
        if (!empty($description)) {
            $message .= "Description: " . $description . "\n";
        }
        $message .= "\n";
        $message .= "Please use the following link to complete your payment:\n";
        $message .= $payment_link . "\n\n";
        $message .= "Thank you!";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $result = wp_mail($email, $subject, $message, $headers);
        
        return $result ? 1 : 0;
    }
    
    /**
     * Get payment links history
     */
    public static function get_payment_links_history() {
        // Ensure session is started for calendar admin
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check standalone authentication
        if (!isset($_SESSION['calendar_admin_logged_in']) || !$_SESSION['calendar_admin_logged_in']) {
            wp_send_json_error('Not authenticated - please login first');
        }
        
        global $wpdb;
        $payment_links_table = Waxing_Database::get_payment_links_table_name();
        
        // Get last 50 payment links, ordered by most recent
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $limit = min($limit, 100); // Max 100
        
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $payment_links_table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
        wp_send_json_success($links);
    }
}

