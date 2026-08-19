<?php
/**
 * Post-booking call to action pointing at the intake form.
 *
 * Expects $link and $order in scope.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="waxing-intake-prompt">
    <div class="waxing-intake-prompt-body">
        <h2 class="waxing-intake-prompt-title">One last step before your visit</h2>
        <p class="waxing-intake-prompt-text">
            Please complete your skin care health history so your esthetician can
            prepare for your appointment. It takes about three minutes, and we've
            already filled in your contact details.
        </p>
        <a class="waxing-intake-prompt-btn" href="<?php echo esc_url($link); ?>">
            Complete my health history
        </a>
        <p class="waxing-intake-prompt-note">
            You can also do this at the studio, but arriving with it done saves
            time in the chair.
        </p>
    </div>
</section>
