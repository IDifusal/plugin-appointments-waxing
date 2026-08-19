<?php
/**
 * Renders a single intake field.
 *
 * Expects $key and $field in scope. Included once per field by the form views.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

$required = !empty($field['required']);
$width    = isset($field['width']) ? $field['width'] : 'full';
$field_id = 'waxing-f-' . $key;

// Conditional visibility is declared as "otherfield:value" and resolved in JS,
// so a patient only sees follow-up questions that apply to them.
$conditions = '';
if (!empty($field['show_if'])) {
    $conditions .= ' data-show-if="' . esc_attr($field['show_if']) . '"';
}
if (!empty($field['hide_if'])) {
    $conditions .= ' data-hide-if="' . esc_attr($field['hide_if']) . '"';
}

// Values carried over from a just-completed booking, so a client who already
// typed their name at checkout doesn't type it again here. Only ever plain
// scalars: the prefill map is built from sanitized order fields.
$prefill = '';
if (isset($prefill_values) && is_array($prefill_values) && isset($prefill_values[$key])) {
    $prefill = (string) $prefill_values[$key];
}

$classes = 'waxing-field waxing-field--' . esc_attr($width) . ' waxing-field--' . esc_attr($field['type']);
if (!empty($field['show_if'])) {
    $classes .= ' is-conditional';
}
?>
<div class="<?php echo $classes; ?>"<?php echo $conditions; ?> data-field="<?php echo esc_attr($key); ?>">

    <?php if ($field['type'] === 'yesno') : ?>

        <fieldset class="waxing-yesno">
            <legend class="waxing-label"><?php echo esc_html($field['label']); ?><?php if ($required) : ?> <span class="waxing-req">*</span><?php endif; ?></legend>
            <div class="waxing-yesno-options">
                <?php foreach (array('yes' => 'Yes', 'no' => 'No') as $val => $label) : ?>
                    <label class="waxing-pill">
                        <input type="radio" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($val); ?>"<?php echo $required ? ' required' : ''; ?>>
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

    <?php elseif ($field['type'] === 'radio') : ?>

        <fieldset class="waxing-yesno">
            <legend class="waxing-label"><?php echo esc_html($field['label']); ?><?php if ($required) : ?> <span class="waxing-req">*</span><?php endif; ?></legend>
            <div class="waxing-yesno-options">
                <?php foreach ($field['options'] as $val => $label) : ?>
                    <label class="waxing-pill">
                        <input type="radio" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($val); ?>">
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

    <?php elseif ($field['type'] === 'checkboxes') : ?>

        <fieldset class="waxing-checkgroup">
            <legend class="waxing-label"><?php echo esc_html($field['label']); ?></legend>
            <div class="waxing-checkgrid">
                <?php foreach ($field['options'] as $val => $label) : ?>
                    <label class="waxing-check">
                        <input type="checkbox" name="<?php echo esc_attr($key); ?>[]" value="<?php echo esc_attr($val); ?>">
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

    <?php elseif ($field['type'] === 'checkbox_single') : ?>

        <label class="waxing-check waxing-check--standalone">
            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1">
            <span><?php echo esc_html($field['label']); ?></span>
        </label>

    <?php elseif ($field['type'] === 'textarea') : ?>

        <label class="waxing-label" for="<?php echo esc_attr($field_id); ?>">
            <?php echo esc_html($field['label']); ?><?php if ($required) : ?> <span class="waxing-req">*</span><?php endif; ?>
        </label>
        <textarea
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($key); ?>"
            rows="3"
            <?php echo $required ? 'required' : ''; ?>><?php echo esc_textarea($prefill); ?></textarea>

    <?php else : ?>

        <label class="waxing-label" for="<?php echo esc_attr($field_id); ?>">
            <?php echo esc_html($field['label']); ?><?php if ($required) : ?> <span class="waxing-req">*</span><?php endif; ?>
        </label>
        <input
            type="<?php echo esc_attr($field['type']); ?>"
            id="<?php echo esc_attr($field_id); ?>"
            name="<?php echo esc_attr($key); ?>"
            <?php if (!empty($field['autocomplete'])) : ?>autocomplete="<?php echo esc_attr($field['autocomplete']); ?>"<?php endif; ?>
            <?php if ($field['type'] === 'tel') : ?>inputmode="tel"<?php endif; ?>
            <?php if ($prefill !== '') : ?>value="<?php echo esc_attr($prefill); ?>"<?php endif; ?>
            <?php echo $required ? 'required' : ''; ?>>

    <?php endif; ?>

    <span class="waxing-field-error" aria-live="polite"></span>
</div>
