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

