<?php
/**
 * Health history card, rendered as a step-per-section wizard.
 *
 * Expects $atts, $schema and $include_waiver in scope.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

$steps = array_keys($schema);
if ($include_waiver) {
    $steps[] = 'waiver';
}
$total_steps = count($steps);
$step_index  = 0;
?>
<div class="waxing-patient-form-wrap">
    <form class="waxing-patient-form" data-action="waxing_save_patient" novalidate>

        <header class="waxing-form-header">
            <h2 class="waxing-form-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php if (!empty($atts['intro'])) : ?>
                <p class="waxing-form-intro"><?php echo esc_html($atts['intro']); ?></p>
            <?php endif; ?>
        </header>

        <div class="waxing-progress" role="group" aria-label="Form progress">
            <div class="waxing-progress-bar"><span class="waxing-progress-fill"></span></div>
            <p class="waxing-progress-text">Step <span class="waxing-step-current">1</span> of <?php echo (int) $total_steps; ?></p>
        </div>

        <?php foreach ($schema as $section_key => $section) : $step_index++; ?>
            <section class="waxing-step" data-step="<?php echo (int) $step_index; ?>"<?php echo $step_index === 1 ? '' : ' hidden'; ?>>
                <h3 class="waxing-step-title"><?php echo esc_html($section['title']); ?></h3>
                <?php if (!empty($section['description'])) : ?>
                    <p class="waxing-step-desc"><?php echo esc_html($section['description']); ?></p>
                <?php endif; ?>

                <div class="waxing-grid">
                    <?php
                    $current_group = null;
                    foreach ($section['fields'] as $key => $field) {
                        // Fields sharing a `group` get one heading above the run
                        // (e.g. the daily-fluids row) instead of repeating it.
                        $group = isset($field['group']) ? $field['group'] : null;
                        if ($group !== $current_group) {
                            if ($group !== null) {
                                echo '<h4 class="waxing-group-title">' . esc_html($group) . '</h4>';
                            }
                            $current_group = $group;
                        }
                        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/patient-field.php';
                    }
                    ?>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($include_waiver) : $step_index++; ?>
            <section class="waxing-step" data-step="<?php echo (int) $step_index; ?>" hidden>
                <h3 class="waxing-step-title">Waiver of Liability</h3>
                <div class="waxing-waiver-text" tabindex="0">
                    <?php echo wpautop(esc_html(Waxing_Patient_Forms::get_waiver_text())); ?>
                </div>

                <div class="waxing-field waxing-field--full">
                    <label class="waxing-check waxing-check--standalone waxing-check--accept">
                        <input type="checkbox" name="waiver_accept" value="1" required>
                        <span>I have read and agree to the waiver above, and I acknowledge that the answers I provided are true.</span>
                    </label>
                    <span class="waxing-field-error" aria-live="polite"></span>
                </div>

                <div class="waxing-field waxing-field--half">
                    <label class="waxing-label" for="waxing-f-waiver_signature">Type your full name as signature <span class="waxing-req">*</span></label>
                    <input type="text" id="waxing-f-waiver_signature" name="waiver_signature" class="waxing-signature" autocomplete="name" required>
                    <span class="waxing-field-error" aria-live="polite"></span>
                </div>

                <div class="waxing-field waxing-field--half">
                    <label class="waxing-label">Date</label>
                    <input type="text" value="<?php echo esc_attr(date_i18n(get_option('date_format'))); ?>" readonly>
                </div>
            </section>
        <?php endif; ?>

        <div class="waxing-form-message" role="alert" aria-live="assertive" hidden></div>

        <footer class="waxing-form-nav">
            <button type="button" class="waxing-btn waxing-btn--ghost waxing-prev" hidden>Back</button>
            <button type="button" class="waxing-btn waxing-next">Continue</button>
            <button type="submit" class="waxing-btn waxing-submit" hidden>
                <span class="waxing-btn-label">Submit</span>
                <span class="waxing-spinner" hidden></span>
            </button>
        </footer>

        <div class="waxing-form-success" hidden>
            <div class="waxing-success-icon">&#10003;</div>
            <p><?php echo esc_html($atts['success']); ?></p>
        </div>
    </form>
</div>
