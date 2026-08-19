<?php
/**
 * Standalone waiver of liability form.
 *
 * Expects $atts in scope.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

// Contact details carried over from a finished booking, if any. Missing keys
// simply render an empty field, so the standalone waiver page is unaffected.
$wx_pre = isset($prefill_values) && is_array($prefill_values) ? $prefill_values : array();
$wx_val = function ($key) use ($wx_pre) {
    return isset($wx_pre[$key]) ? (string) $wx_pre[$key] : '';
};
?>
<div class="waxing-patient-form-wrap">
    <form class="waxing-patient-form waxing-patient-form--single" data-action="waxing_save_waiver" novalidate>

        <header class="waxing-form-header">
            <h2 class="waxing-form-title"><?php echo esc_html($atts['title']); ?></h2>
        </header>

        <section class="waxing-step" data-step="1">
            <div class="waxing-waiver-text" tabindex="0">
                <?php echo wpautop(esc_html(Waxing_Patient_Forms::get_waiver_text())); ?>
            </div>

            <div class="waxing-grid">
                <div class="waxing-field waxing-field--full">
                    <label class="waxing-label" for="waxing-w-name">Full Name <span class="waxing-req">*</span></label>
                    <input type="text" id="waxing-w-name" name="full_name" value="<?php echo esc_attr($wx_val('full_name')); ?>" autocomplete="name" required>
                    <span class="waxing-field-error" aria-live="polite"></span>
                </div>

                <div class="waxing-field waxing-field--half">
                    <label class="waxing-label" for="waxing-w-email">Email <span class="waxing-req">*</span></label>
                    <input type="email" id="waxing-w-email" name="email" value="<?php echo esc_attr($wx_val('email')); ?>" autocomplete="email" required>
                    <span class="waxing-field-error" aria-live="polite"></span>
                </div>

                <div class="waxing-field waxing-field--half">
                    <label class="waxing-label" for="waxing-w-phone">Phone</label>
                    <input type="tel" id="waxing-w-phone" name="home_phone" value="<?php echo esc_attr($wx_val('home_phone')); ?>" inputmode="tel" autocomplete="tel">
                    <span class="waxing-field-error" aria-live="polite"></span>
                </div>

                <div class="waxing-field waxing-field--full">
                    <label class="waxing-check waxing-check--standalone waxing-check--accept">
                        <input type="checkbox" name="waiver_accept" value="1" required>
                        <span>I have read and agree to the waiver above.</span>
                    </label>
                    <span class="waxing-field-error" aria-live="polite"></span>
                </div>

                <div class="waxing-field waxing-field--half">
                    <label class="waxing-label" for="waxing-w-signature">Type your full name as signature <span class="waxing-req">*</span></label>
                    <input type="text" id="waxing-w-signature" name="waiver_signature" class="waxing-signature" autocomplete="name" required>
                    <span class="waxing-field-error" aria-live="polite"></span>
                </div>

                <div class="waxing-field waxing-field--half">
                    <label class="waxing-label">Date</label>
                    <input type="text" value="<?php echo esc_attr(date_i18n(get_option('date_format'))); ?>" readonly>
                </div>
            </div>
        </section>

        <div class="waxing-form-message" role="alert" aria-live="assertive" hidden></div>

        <footer class="waxing-form-nav">
            <button type="submit" class="waxing-btn waxing-submit">
                <span class="waxing-btn-label">Sign &amp; Submit</span>
                <span class="waxing-spinner" hidden></span>
            </button>
        </footer>

        <div class="waxing-form-success" hidden>
            <div class="waxing-success-icon">&#10003;</div>
            <p><?php echo esc_html($atts['success']); ?></p>
        </div>
    </form>
</div>
