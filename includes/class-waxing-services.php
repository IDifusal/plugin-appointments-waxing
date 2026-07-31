<?php
/**
 * Services and business hours handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Services {

    /**
     * Get the list of bookable office locations.
     *
     * @return array Array of offices keyed by a stable slug used for storage.
     */
    public static function get_offices() {
        return array(
            'harrisburg' => array(
                'value'   => 'harrisburg',
                'name'    => 'Harrisburg',
                'address' => '7475 - 4250 Main St, Harrisburg, NC 28075',
            ),
            'indian_trail' => array(
                'value'   => 'indian_trail',
                'name'    => 'Indian Trail',
                'address' => '115 Unionville Indian Trail Rd, Indian Trail, NC 28079',
            ),
        );
    }

    /**
     * Get a single office by its slug.
     *
     * @param string $office_value Office slug.
     * @return array|null Office data or null if not found.
     */
    public static function get_office($office_value) {
        $offices = self::get_offices();
        return isset($offices[$office_value]) ? $offices[$office_value] : null;
    }

    /**
     * Get business hours for a specific day of week
     * 
     * @param int $day_of_week 0 = Sunday, 1 = Monday, ..., 6 = Saturday
     * @return array|null ['start_time' => 'HH:MM', 'end_time' => 'HH:MM'] or null if closed
     */
    public static function get_business_hours($day_of_week) {
        // Sunday = closed
        if ($day_of_week == 0) {
            return null;
        }
        
        // Monday - Wednesday: 10:00 - 17:00
        if ($day_of_week >= 1 && $day_of_week <= 3) {
            return array('start_time' => '10:00', 'end_time' => '17:00');
        }
        
        // Thursday: 10:00 - 19:00
        if ($day_of_week == 4) {
            return array('start_time' => '10:00', 'end_time' => '19:00');
        }
        
        // Friday: 10:00 - 17:00
        if ($day_of_week == 5) {
            return array('start_time' => '10:00', 'end_time' => '17:00');
        }
        
        // Saturday: 09:00 - 14:00
        if ($day_of_week == 6) {
            return array('start_time' => '09:00', 'end_time' => '14:00');
        }
        
        return null;
    }
    
    /**
     * Get available time slots for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @return array Array of time slots in HH:MM format
     */
    public static function get_time_slots_for_date($date) {
        $day_of_week = date('w', strtotime($date));
        $business_hours = self::get_business_hours($day_of_week);
        
        if (!$business_hours) {
            return array(); // Closed on this day
        }
        
        $start_time = strtotime($business_hours['start_time']);
        $end_time = strtotime($business_hours['end_time']);
        $slots = array();
        
        // Generate 30-minute slots
        $current = $start_time;
        while ($current < $end_time) {
            $slots[] = date('H:i', $current);
            $current = strtotime('+30 minutes', $current);
        }
        
        return $slots;
    }
    
    /**
     * Get all available WooCommerce products as services
     * 
     * @return array Array of service products
     */
    public static function get_waxing_services() {
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
}

