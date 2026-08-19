<?php
/**
 * Front-desk kiosk: chooser screen plus both forms.
 *
 * Both forms are rendered up front and toggled client-side rather than
 * fetched on demand, so a flaky in-store connection can't leave a client
 * staring at a spinner after they've already tapped.
 *
 * Expects $atts and $schema in scope.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="waxing-kiosk" data-reset-after="<?php echo esc_attr((int) $atts['reset_after']); ?>">

    <section class="waxing-kiosk-choose">
        <h2 class="waxing-kiosk-title"><?php echo esc_html($atts['title']); ?></h2>
        <?php if (!empty($atts['intro'])) : ?>
            <p class="waxing-kiosk-intro"><?php echo esc_html($atts['intro']); ?></p>
        <?php endif; ?>

        <div class="waxing-kiosk-options">
            <button type="button" class="waxing-kiosk-card" data-open="history">
                <span class="waxing-kiosk-card-label"><?php echo esc_html($atts['history_label']); ?></span>
                <span class="waxing-kiosk-card-desc"><?php echo esc_html($atts['history_desc']); ?></span>
            </button>

            <button type="button" class="waxing-kiosk-card waxing-kiosk-card--alt" data-open="waiver">
                <span class="waxing-kiosk-card-label"><?php echo esc_html($atts['waiver_label']); ?></span>
                <span class="waxing-kiosk-card-desc"><?php echo esc_html($atts['waiver_desc']); ?></span>
            </button>
        </div>
    </section>

    <section class="waxing-kiosk-panel" data-panel="history" hidden>
        <button type="button" class="waxing-kiosk-back">&larr; Start over</button>
        <?php
        $include_waiver = true;
        $form_atts = array(
            'title'   => $atts['history_label'],
            'intro'   => '',
            'success' => $atts['success'],
        );
        // The shared form view reads $atts, so swap in the kiosk's copy for
        // the duration of the include and restore it afterwards.
        $kiosk_atts = $atts;
        $atts = $form_atts;
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/patient-form.php';
        $atts = $kiosk_atts;
        ?>
    </section>

    <section class="waxing-kiosk-panel" data-panel="waiver" hidden>
        <button type="button" class="waxing-kiosk-back">&larr; Start over</button>
        <?php
        $waiver_atts = array(
            'title'   => 'Waiver of Liability and Hold Harmless Agreement',
            'success' => $atts['success'],
        );
        $kiosk_atts = $atts;
        $atts = $waiver_atts;
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/waiver-form.php';
        $atts = $kiosk_atts;
        ?>
    </section>
</div>
