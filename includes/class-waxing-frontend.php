<?php
/**
 * Frontend handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Frontend {
    
    /**
     * Enqueue scripts and styles
     */
    public static function enqueue_scripts() {
        wp_enqueue_script('jquery');

        // Enqueue Air Datepicker from CDN
        // Using version 3.5.3 with correct path - note: air-datepicker.js (not datepicker.min.js)
        wp_enqueue_script('air-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/air-datepicker.js', array('jquery'), '3.5.3', true);
        wp_enqueue_style('air-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.5.3/air-datepicker.css', array(), '3.5.3');

        // Enqueue our custom scripts after Air Datepicker
        wp_enqueue_script('waxing-appointments', WAXING_APPOINTMENTS_PLUGIN_URL . 'assets/js/appointments.js', array('jquery', 'air-datepicker'), WAXING_APPOINTMENTS_VERSION, true);
        wp_enqueue_style('waxing-appointments', WAXING_APPOINTMENTS_PLUGIN_URL . 'assets/css/appointments.css', array(), WAXING_APPOINTMENTS_VERSION);

        // Localize script for AJAX
        wp_localize_script('waxing-appointments', 'waxing_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('waxing_appointments_nonce')
        ));
    }
    
    /**
     * Appointment button shortcode
     */
    public static function appointment_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Book Appointment',
            'style' => 'default',
            'class' => ''
        ), $atts);
        
        $button_class = 'waxing-appointment-btn';
        if ($atts['style'] === 'inline') {
            $button_class .= ' inline-style';
        } elseif ($atts['style'] === 'custom') {
            $button_class .= ' custom-style';
        }
        
        if (!empty($atts['class'])) {
            $button_class .= ' ' . sanitize_html_class($atts['class']);
        }
        
        return '<button class="' . esc_attr($button_class) . '" id="waxing-open-modal">' . esc_html($atts['text']) . '</button>';
    }
    
    /**
     * Add appointment modal to footer
     */
    public static function add_appointment_modal() {
        $services = Waxing_Services::get_waxing_services();
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/appointment-modal.php';
    }
}

