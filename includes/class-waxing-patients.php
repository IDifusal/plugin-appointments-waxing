<?php
/**
 * Patient records: custom post type, field schema and admin UI.
 *
 * Patient data is stored as a `waxing_patient` CPT rather than in a custom
 * table so the records get the admin list table, search, revisions and
 * capability checks for free. Every answer lives in post meta prefixed with
 * `_waxing_` so nothing leaks into the theme via custom-field templates.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Patients {

    const POST_TYPE = 'waxing_patient';

    /**
     * Meta key prefix. Leading underscore keeps the fields out of the default
     * "Custom Fields" metabox, which would otherwise expose medical answers as
     * free-text editable rows.
     */
    const META_PREFIX = '_waxing_';

    /**
     * Register the post type.
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => 'Patients',
            'singular_name'      => 'Patient',
            'menu_name'          => 'Patients',
            'all_items'          => 'All Patients',
            'add_new'            => 'Add Patient',
            'add_new_item'       => 'Add New Patient',
            'edit_item'          => 'Edit Patient',
            'new_item'           => 'New Patient',
            'view_item'          => 'View Patient',
            'search_items'       => 'Search Patients',
            'not_found'          => 'No patients found',
            'not_found_in_trash' => 'No patients found in Trash',
        );

        register_post_type(self::POST_TYPE, array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            // Nested under the existing Appointments menu instead of claiming a
            // second top-level slot.
            'show_in_menu'    => 'waxing-appointments',
            'capability_type' => 'post',
            'capabilities'    => array(
                // Records are submitted by the public form, never hand-authored,
                // so the "Add New" affordance is removed to avoid half-filled
                // records with no consent trail.
                'create_posts' => 'do_not_allow',
            ),
            'map_meta_cap'    => true,
            'supports'        => array('title'),
            'has_archive'     => false,
            'rewrite'         => false,
            'query_var'       => false,
            'show_in_rest'    => false,
        ));
    }

    /**
     * Field schema shared by the renderer, the sanitiser and the admin view.
     *
     * Keeping one definition means adding a question is a single edit here and
     * the form, save routine and admin display all pick it up.
     *
     * Types: text, email, tel, date, number, textarea, yesno, checkboxes, radio.
     */
    public static function get_schema() {
        return array(
            'contact' => array(
                'title'  => 'Your Information',
                'fields' => array(
                    'full_name'   => array('label' => 'Full Name', 'type' => 'text', 'required' => true, 'autocomplete' => 'name', 'width' => 'full'),
                    'email'       => array('label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'width' => 'half'),
                    'home_phone'  => array('label' => 'Phone', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'width' => 'half'),
                    'bus_phone'   => array('label' => 'Alternate Phone', 'type' => 'tel', 'width' => 'half'),
                    'date_of_birth' => array('label' => 'Date of Birth', 'type' => 'date', 'width' => 'half'),
                    'address'     => array('label' => 'Address', 'type' => 'text', 'autocomplete' => 'street-address', 'width' => 'full'),
                    'city'        => array('label' => 'City', 'type' => 'text', 'autocomplete' => 'address-level2', 'width' => 'third'),
                    'state'       => array('label' => 'State', 'type' => 'text', 'autocomplete' => 'address-level1', 'width' => 'third'),
                    'zip'         => array('label' => 'ZIP', 'type' => 'text', 'autocomplete' => 'postal-code', 'width' => 'third'),
                    'occupation'  => array('label' => 'Occupation', 'type' => 'text', 'width' => 'half'),
                    'employment'  => array(
                        'label'   => 'Employment',
                        'type'    => 'radio',
                        'options' => array('full-time' => 'Full-time', 'part-time' => 'Part-time', 'other' => 'Other'),
                        'width'   => 'half',
                    ),
                ),
            ),
            'medical' => array(
                'title'       => 'Medical Background',
                'description' => 'This helps your esthetician analyze your skin accurately and avoid reactions.',
                'fields' => array(
                    'seen_dermatologist' => array('label' => 'Have you seen a dermatologist in the last 5 years?', 'type' => 'yesno'),
                    'doctor_name'        => array('label' => "Doctor's name", 'type' => 'text', 'show_if' => 'seen_dermatologist:yes'),
                    'under_care_now'     => array('label' => 'Are you under their care now?', 'type' => 'yesno', 'show_if' => 'seen_dermatologist:yes'),
                    'reason_treatment'   => array('label' => 'Reason for treatment', 'type' => 'text', 'show_if' => 'seen_dermatologist:yes'),
                    'deep_skin_peeling'  => array('label' => 'Have you had a deep skin peeling?', 'type' => 'yesno'),
                    'deep_skin_peeling_when' => array('label' => 'When?', 'type' => 'text', 'show_if' => 'deep_skin_peeling:yes'),
                    'cosmetic_surgery'   => array('label' => 'Have you had cosmetic surgery?', 'type' => 'yesno'),
                    'cosmetic_surgery_when' => array('label' => 'When?', 'type' => 'text', 'show_if' => 'cosmetic_surgery:yes'),
                    'cosmetic_surgery_kind' => array('label' => 'What kind?', 'type' => 'text', 'show_if' => 'cosmetic_surgery:yes'),
                    'pregnant'           => array('label' => 'Are you pregnant?', 'type' => 'yesno'),
                    'retin_a'            => array('label' => 'Are you using Retin-A or other topical drugs?', 'type' => 'yesno'),
                    'on_diet'            => array('label' => 'Are you on a diet?', 'type' => 'yesno'),
                    'on_diet_explain'    => array('label' => 'Please explain', 'type' => 'text', 'show_if' => 'on_diet:yes'),
                    'heart_condition'    => array('label' => 'Do you have a heart condition?', 'type' => 'yesno'),
                    'contact_lenses'     => array('label' => 'Do you wear contact lenses?', 'type' => 'yesno'),
                    'birth_control'      => array('label' => 'Do you take birth control pills?', 'type' => 'yesno'),
                    'taking_medications' => array('label' => 'Are you taking any medications?', 'type' => 'yesno'),
                    'exercise'           => array('label' => 'Do you exercise?', 'type' => 'yesno'),
                    'eczema'             => array('label' => 'Do you or have you had eczema?', 'type' => 'yesno'),
                    'seborrhea'          => array('label' => 'Do you or have you had seborrhea?', 'type' => 'yesno'),
                    'metal_implants'     => array('label' => 'Any metal implants except fillings (pacemaker, pins, copper IUD)?', 'type' => 'yesno'),
                    'health_conditions'  => array(
                        'label'   => 'Please check any health conditions you have had or are experiencing',
                        'type'    => 'checkboxes',
                        'options' => array(
                            'hypoglycemia'        => 'Hypoglycemia',
                            'heart_problems'      => 'Heart Problems',
                            'hysterectomy'        => 'Hysterectomy',
                            'sugar_diabetes'      => 'Sugar Diabetes',
                            'alcoholism'          => 'Alcoholism',
                            'hepatitis'           => 'Hepatitis',
                            'cancer'              => 'Cancer',
                            'blood_pressure'      => 'High/Low Blood Pressure',
                            'silicone_injections' => 'Silicone or Zyderm Injections',
                            'thyroid'             => 'Thyroid (Over/Under)',
                            'metabolic_disorders' => 'Metabolic Disorders',
                            'hormonal_problems'   => 'Hormonal Problems',
                            'sinus_problems'      => 'Sinus Problems',
                            'migraine_headaches'  => 'Migraine Headaches',
                        ),
                    ),
                    'allergies_none' => array('label' => 'I have no known allergies', 'type' => 'checkbox_single'),
                    'allergies'      => array('label' => 'List any allergies', 'type' => 'textarea', 'hide_if' => 'allergies_none:1'),
                    'medications'    => array('label' => 'List all medications you take regularly (hormones, vitamins, antibiotics, etc.)', 'type' => 'textarea'),
                ),
            ),
            'skincare' => array(
                'title'  => 'Skin & Lifestyle',
                'fields' => array(
                    'suffered_acne'     => array('label' => 'Have you ever suffered from acne?', 'type' => 'yesno'),
                    'acne_severity'     => array(
                        'label'   => 'How severe?',
                        'type'    => 'radio',
                        'options' => array('heavy' => 'Heavy', 'light' => 'Light', 'other' => 'Other'),
                        'show_if' => 'suffered_acne:yes',
                    ),
                    'allergic_reaction' => array('label' => 'Ever had an allergic reaction to skincare products or cosmetics?', 'type' => 'yesno'),
                    'allergic_products' => array('label' => 'Which products or ingredients?', 'type' => 'text', 'show_if' => 'allergic_reaction:yes'),
                    'had_facials'       => array('label' => 'Have you had skin care treatments/facials before?', 'type' => 'yesno'),
                    'had_facials_when'  => array('label' => 'When?', 'type' => 'text', 'show_if' => 'had_facials:yes'),
                    'uses_makeup'       => array('label' => 'Do you use make-up?', 'type' => 'yesno'),
                    'salty_foods'       => array('label' => 'Do you enjoy eating salty foods?', 'type' => 'yesno'),
                    'smoke'             => array('label' => 'Do you smoke?', 'type' => 'yesno'),
                    'smoke_amount'      => array('label' => 'How much?', 'type' => 'text', 'show_if' => 'smoke:yes'),
                    'cleanse_frequency' => array('label' => 'How often do you cleanse your skin?', 'type' => 'text'),
                    'facial_products'   => array('label' => 'Are you using any facial products?', 'type' => 'yesno'),
                    'facial_products_list' => array('label' => 'Which ones?', 'type' => 'text', 'show_if' => 'facial_products:yes'),
                    'skin_midday'       => array('label' => 'How does your skin feel in the middle of the day?', 'type' => 'text'),
                    'skin_end_of_day'   => array('label' => 'And at the end of the day?', 'type' => 'text'),
                    'sleep_hours'       => array('label' => 'How much sleep do you get per night?', 'type' => 'text'),
                    'under_stress'      => array('label' => 'Are you currently or periodically under a lot of stress?', 'type' => 'yesno'),
                    'fluid_water'       => array('label' => 'Water', 'type' => 'text', 'width' => 'third', 'group' => 'Daily fluids'),
                    'fluid_coffee'      => array('label' => 'Coffee', 'type' => 'text', 'width' => 'third', 'group' => 'Daily fluids'),
                    'fluid_juices'      => array('label' => 'Juices', 'type' => 'text', 'width' => 'third', 'group' => 'Daily fluids'),
                    'fluid_teas'        => array('label' => 'Teas', 'type' => 'text', 'width' => 'third', 'group' => 'Daily fluids'),
                    'fluid_colas'       => array('label' => 'Colas', 'type' => 'text', 'width' => 'third', 'group' => 'Daily fluids'),
                    'fluid_other'       => array('label' => 'Other', 'type' => 'text', 'width' => 'third', 'group' => 'Daily fluids'),
                    'skin_concern'      => array('label' => 'What is your specific concern with your skin condition?', 'type' => 'textarea'),
                    'expected_result'   => array('label' => 'What is the end result you are expecting?', 'type' => 'textarea'),
                    'comments'          => array('label' => 'Comments', 'type' => 'textarea'),
                    'referred_by'       => array('label' => 'Referred by', 'type' => 'text'),
                ),
            ),
        );
    }

    /**
     * Flattened key => field definition map across every section.
     */
    public static function get_all_fields() {
        $fields = array();
        foreach (self::get_schema() as $section) {
            foreach ($section['fields'] as $key => $field) {
                $fields[$key] = $field;
            }
        }
        return $fields;
    }

    /**
     * Sanitise one submitted value according to its field definition.
     *
     * Returns null when the value should not be stored at all (e.g. an option
     * that isn't in the field's own whitelist), so callers can skip the write
     * rather than persisting an empty string over a real answer.
     */
    public static function sanitize_value($field, $raw) {
        switch ($field['type']) {
            case 'email':
                return sanitize_email($raw);

            case 'textarea':
                return sanitize_textarea_field($raw);

            case 'date':
                // Normalise to Y-m-d and reject anything unparseable so the
                // admin column never renders a bogus date.
                $raw = sanitize_text_field($raw);
                if ($raw === '') {
                    return '';
                }
                $parts = date_parse($raw);
                if (!empty($parts['error_count']) || !checkdate((int) $parts['month'], (int) $parts['day'], (int) $parts['year'])) {
                    return '';
                }
                return sprintf('%04d-%02d-%02d', $parts['year'], $parts['month'], $parts['day']);

            case 'yesno':
                return in_array($raw, array('yes', 'no'), true) ? $raw : '';

            case 'radio':
                return isset($field['options'][$raw]) ? $raw : '';

            case 'checkbox_single':
                return $raw ? '1' : '';

            case 'checkboxes':
                // Values arrive as an array; keep only keys the schema declares
                // so a crafted POST can't inject arbitrary meta values.
                $selected = is_array($raw) ? $raw : array();
                $valid = array();
                foreach ($selected as $option) {
                    if (isset($field['options'][$option])) {
                        $valid[] = $option;
                    }
                }
                return $valid;

            default:
                return sanitize_text_field($raw);
        }
    }

    /**
     * Human-readable rendering of a stored value, for the admin record view.
     */
    public static function format_value($field, $value) {
        switch ($field['type']) {
            case 'yesno':
                if ($value === '') {
                    return '—';
                }
                return $value === 'yes' ? 'Yes' : 'No';

            case 'radio':
                return isset($field['options'][$value]) ? $field['options'][$value] : '—';

            case 'checkbox_single':
                return $value ? 'Yes' : 'No';

            case 'checkboxes':
                if (!is_array($value) || empty($value)) {
                    return '—';
                }
                $labels = array();
                foreach ($value as $option) {
                    if (isset($field['options'][$option])) {
                        $labels[] = $field['options'][$option];
                    }
                }
                return empty($labels) ? '—' : implode(', ', $labels);

            case 'date':
                return $value ? date_i18n(get_option('date_format'), strtotime($value)) : '—';

            default:
                return $value === '' ? '—' : $value;
        }
    }

    /**
     * Read every schema field for a patient into a key => value map.
     */
    public static function get_patient_data($post_id) {
        $data = array();
        foreach (self::get_all_fields() as $key => $field) {
            $value = get_post_meta($post_id, self::META_PREFIX . $key, true);
            if ($field['type'] === 'checkboxes' && !is_array($value)) {
                $value = array();
            }
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * Admin list table columns.
     */
    public static function set_columns($columns) {
        return array(
            'cb'       => isset($columns['cb']) ? $columns['cb'] : '',
            'title'    => 'Patient',
            'email'    => 'Email',
            'phone'    => 'Phone',
            'waiver'   => 'Waiver',
            'submitted' => 'Submitted',
        );
    }

    /**
     * Render a custom admin list column.
     */
    public static function render_column($column, $post_id) {
        switch ($column) {
            case 'email':
                $email = get_post_meta($post_id, self::META_PREFIX . 'email', true);
                echo $email ? '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>' : '—';
                break;

            case 'phone':
                $phone = get_post_meta($post_id, self::META_PREFIX . 'home_phone', true);
                echo $phone ? esc_html($phone) : '—';
                break;

            case 'waiver':
                $signed = get_post_meta($post_id, self::META_PREFIX . 'waiver_signed_at', true);
                if ($signed) {
                    echo '<span style="color:#155724;font-weight:600;">&#10003; Signed</span><br><small>'
                        . esc_html(date_i18n(get_option('date_format'), strtotime($signed))) . '</small>';
                } else {
                    echo '<span style="color:#dc3545;">Not signed</span>';
                }
                break;

            case 'submitted':
                echo esc_html(get_the_date(get_option('date_format'), $post_id));
                break;
        }
    }

    /**
     * Make the health-history record visible on the patient edit screen.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'waxing-patient-record',
            'Patient Record',
            array(__CLASS__, 'render_record_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'waxing-patient-waiver',
            'Liability Waiver',
            array(__CLASS__, 'render_waiver_meta_box'),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Read-only record view. Submissions are the source of truth — editing
     * answers after the fact would break the signed consent trail, so the
     * fields are displayed rather than re-offered as inputs.
     */
    public static function render_record_meta_box($post) {
        $schema = self::get_schema();
        $data = self::get_patient_data($post->ID);
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/views/patient-record-meta-box.php';
    }

    /**
     * Waiver signature panel.
     */
    public static function render_waiver_meta_box($post) {
        $signed_at  = get_post_meta($post->ID, self::META_PREFIX . 'waiver_signed_at', true);
        $signature  = get_post_meta($post->ID, self::META_PREFIX . 'waiver_signature', true);
        $ip         = get_post_meta($post->ID, self::META_PREFIX . 'waiver_ip', true);
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/views/patient-waiver-meta-box.php';
    }
}
