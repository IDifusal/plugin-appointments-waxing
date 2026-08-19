<?php
/**
 * Waiver signature panel on the admin edit screen.
 *
 * Expects $signed_at, $signature and $ip in scope.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if ($signed_at) : ?>
    <p style="margin-top:0;">
        <strong style="color:#155724;">&#10003; Signed</strong>
    </p>
    <p style="font-family:'Segoe Script','Bradley Hand',cursive;font-size:20px;padding:12px;background:#fafbfc;border:1px solid #dcdcde;border-radius:6px;margin:0 0 12px;">
        <?php echo esc_html($signature); ?>
    </p>
    <p style="margin:0;font-size:12px;color:#646970;">
        <strong>Date:</strong>
        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($signed_at))); ?>
    </p>
    <?php if ($ip) : ?>
        <p style="margin:4px 0 0;font-size:12px;color:#646970;">
            <strong>IP:</strong> <?php echo esc_html($ip); ?>
        </p>
    <?php endif; ?>
<?php else : ?>
    <p style="margin:0;color:#dc3545;">
        <strong>Not signed.</strong>
    </p>
    <p class="description" style="margin-top:6px;">
        This patient has not submitted the liability waiver.
    </p>
<?php endif; ?>
