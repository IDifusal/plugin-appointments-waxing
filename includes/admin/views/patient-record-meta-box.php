<?php
/**
 * Read-only patient record display on the admin edit screen.
 *
 * Expects $schema and $data in scope.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
    .waxing-record-section { margin: 0 0 26px; }
    .waxing-record-section h3 { margin: 0 0 10px; padding-bottom: 8px; border-bottom: 2px solid #2383f0; font-size: 14px; text-transform: uppercase; letter-spacing: .04em; }
    .waxing-record-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 4px 24px; }
    .waxing-record-row { display: flex; gap: 10px; padding: 7px 0; border-bottom: 1px solid #f0f0f1; font-size: 13px; }
    .waxing-record-row.is-wide { grid-column: 1 / -1; flex-direction: column; gap: 3px; }
    .waxing-record-label { flex: 1; color: #646970; }
    .waxing-record-value { flex: 0 0 auto; max-width: 55%; font-weight: 600; text-align: right; word-break: break-word; }
    .waxing-record-row.is-wide .waxing-record-value { max-width: 100%; text-align: left; font-weight: 500; white-space: pre-wrap; }
    .waxing-record-value.is-empty { color: #a7aaad; font-weight: 400; }
    .waxing-record-value.is-flag { color: #dc3545; }
</style>

<?php foreach ($schema as $section) : ?>
    <div class="waxing-record-section">
        <h3><?php echo esc_html($section['title']); ?></h3>
        <div class="waxing-record-grid">
            <?php foreach ($section['fields'] as $key => $field) :
                $value = isset($data[$key]) ? $data[$key] : '';
                $display = Waxing_Patients::format_value($field, $value);

                // Long-form answers get their own full-width row so the text
                // isn't crammed into a right-aligned column.
                $is_wide = in_array($field['type'], array('textarea', 'checkboxes'), true);

                $value_class = 'waxing-record-value';
                if ($display === '—') {
                    $value_class .= ' is-empty';
                } elseif ($field['type'] === 'yesno' && $value === 'yes') {
                    // "Yes" on a medical question is the answer staff need to
                    // catch at a glance before a treatment.
                    $value_class .= ' is-flag';
                }
                ?>
                <div class="waxing-record-row<?php echo $is_wide ? ' is-wide' : ''; ?>">
                    <span class="waxing-record-label"><?php echo esc_html($field['label']); ?></span>
                    <span class="<?php echo esc_attr($value_class); ?>"><?php echo esc_html($display); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<p class="description">
    This record was submitted by the patient. Answers are shown read-only to preserve the consent trail.
</p>
