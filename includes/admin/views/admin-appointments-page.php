<?php
/**
 * Admin appointments page template
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Waxing Appointments</h1>
    
    <?php 
    // Debug: Show appointment count
    if (defined('WP_DEBUG') && WP_DEBUG) {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $appointments_table");
        $confirmed_count = $wpdb->get_var("SELECT COUNT(*) FROM $appointments_table WHERE status IN ('confirmed', 'booked')");
        echo '<p><strong>Debug:</strong> Total records: ' . $total_count . ' | Confirmed/Booked: ' . $confirmed_count . '</p>';
    }
    ?>
    
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
            <?php if (empty($appointments)): ?>
            <tr>
                <td colspan="10" style="text-align: center; padding: 20px;">
                    No appointments found. Appointments will appear here after payment is completed.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?php echo $appointment->id; ?></td>
                    <td><?php echo esc_html($appointment->customer_name); ?></td>
                    <td><?php echo esc_html($appointment->customer_email); ?></td>
                    <td><?php echo esc_html($appointment->customer_phone); ?></td>
                    <td><?php echo esc_html(str_replace('_', ' ', ucwords($appointment->service_id))); ?></td>
                    <td><?php 
                        // Format date without timezone conversion
                        $date_obj = DateTime::createFromFormat('Y-m-d', $appointment->appointment_date, new DateTimeZone('UTC'));
                        if ($date_obj) {
                            echo $date_obj->format('M j, Y');
                        } else {
                            echo date('M j, Y', strtotime($appointment->appointment_date));
                        }
                    ?></td>
                    <td><?php 
                        // Format time without timezone conversion
                        $time_str = $appointment->appointment_time;
                        if (strlen($time_str) === 5) {
                            $time_str = $time_str . ':00';
                        }
                        $time_parts = explode(':', $time_str);
                        $hours = intval($time_parts[0]);
                        $minutes = intval($time_parts[1]);
                        $am_pm = $hours >= 12 ? 'PM' : 'AM';
                        $display_hours = $hours > 12 ? $hours - 12 : ($hours == 0 ? 12 : $hours);
                        echo sprintf('%d:%02d %s', $display_hours, $minutes, $am_pm);
                    ?></td>
                    <td>$<?php echo number_format($appointment->total_price, 2); ?></td>
                    <td>$<?php echo number_format($appointment->deposit_paid, 2); ?></td>
                    <td><?php echo esc_html($appointment->status); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

