<?php
/**
 * Public-facing patient intake forms.
 *
 * Provides two shortcodes:
 *   [waxing_patient_form]  - full skin care health history card
 *   [waxing_waiver_form]   - waiver of liability with typed signature
 *
 * Both submit over AJAX to keep the multi-step flow from losing answers on a
 * validation bounce.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Patient_Forms {

    const NONCE_ACTION = 'waxing_patient_form_nonce';

    /**
     * Assets are registered (not enqueued) on every page load and only
     * enqueued by the shortcodes, so pages without a form stay untouched.
     */
    public static function register_assets() {
        wp_register_style(
            'waxing-patient-forms',
            WAXING_APPOINTMENTS_PLUGIN_URL . 'assets/css/patient-forms.css',
            array(),
            WAXING_APPOINTMENTS_VERSION
        );

        wp_register_script(
            'waxing-patient-forms',
            WAXING_APPOINTMENTS_PLUGIN_URL . 'assets/js/patient-forms.js',
            array('jquery'),
            WAXING_APPOINTMENTS_VERSION,
            true
        );
    }

    /**
     * Enqueue and localise on demand.
     */
    private static function enqueue_assets() {
        wp_enqueue_style('waxing-patient-forms');
        wp_enqueue_script('waxing-patient-forms');

        wp_localize_script('waxing-patient-forms', 'waxing_patient_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce(self::NONCE_ACTION),
        ));
    }

    /**
     * Read the contact details handed over from a finished booking.
     *
     * The thank-you page links here with the client's name/email/phone so they
     * don't retype what they just entered at checkout. These are conveniences,
     * not credentials: everything is re-sanitized on submit, and nothing here
     * grants access to an existing record - the patient still has to submit the
     * form for anything to be written.
     *
     * @return array Map of field key => prefill value.
     */
    private static function get_prefill_from_request() {
        $map = array(
            'full_name'  => 'wx_name',
            'email'      => 'wx_email',
            'home_phone' => 'wx_phone',
        );

        $values = array();
        foreach ($map as $field => $param) {
            if (empty($_GET[$param])) {
                continue;
            }

            $raw = wp_unslash($_GET[$param]);
            $values[$field] = ($field === 'email')
                ? sanitize_email($raw)
                : sanitize_text_field($raw);
        }

        return array_filter($values);
    }

    /**
     * [waxing_patient_form] - health history card.
     */
    public static function patient_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title'   => 'Skin Care Health History',
            'intro'   => 'Please answer the following questions so we may have a better understanding of your general health and lifestyle. This will aid your esthetician in a more accurate analysis of your skin.',
            'success' => 'Thank you! Your information has been saved.',
            'waiver'  => 'yes',
        ), $atts, 'waxing_patient_form');

        self::enqueue_assets();

        $schema = Waxing_Patients::get_schema();
        $include_waiver = ($atts['waiver'] !== 'no');
        $prefill_values = self::get_prefill_from_request();

        ob_start();
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/patient-form.php';
        return ob_get_clean();
    }

    /**
     * [waxing_waiver_form] - standalone waiver.
     */
    public static function waiver_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title'   => 'Waiver of Liability and Hold Harmless Agreement',
            'success' => 'Thank you! Your signed waiver has been recorded.',
        ), $atts, 'waxing_waiver_form');

        self::enqueue_assets();

        $prefill_values = self::get_prefill_from_request();

        ob_start();
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/waiver-form.php';
        return ob_get_clean();
    }

    /**
     * [waxing_new_patient] - patient-facing intake page.
     *
     * Deliberately shows one path rather than asking the patient to choose
     * between the history card and the waiver: the long form already contains
     * the waiver, so a choice here mostly produces records that look signed
     * but carry no medical answers. The waiver-only route stays reachable as
     * a secondary link for people who genuinely need it.
     */
    public static function new_patient_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title'       => 'New Patient Registration',
            'intro'       => 'Please answer the following questions so we may have a better understanding of your general health and lifestyle. This will aid your esthetician in a more accurate analysis of your skin.',
            'success'     => 'Thank you! Your information has been saved.',
            'waiver_url'  => '',
            'waiver_text' => 'Already a client and only need to sign the waiver?',
            'waiver_link' => 'Sign the waiver instead',
        ), $atts, 'waxing_new_patient');

        self::enqueue_assets();

        $schema = Waxing_Patients::get_schema();
        $include_waiver = true;

        $prefill_values = self::get_prefill_from_request();

        ob_start();
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/patient-form.php';

        if (!empty($atts['waiver_url'])) {
            ?>
            <p class="waxing-secondary-link">
                <?php echo esc_html($atts['waiver_text']); ?>
                <a href="<?php echo esc_url($atts['waiver_url']); ?>"><?php echo esc_html($atts['waiver_link']); ?></a>
            </p>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * [waxing_kiosk] - front-desk chooser.
     *
     * Unlike the patient-facing page this one does offer both forms, because
     * the person choosing is staff who already know which document the client
     * in front of them still owes.
     */
    public static function kiosk_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title'          => 'Welcome',
            'intro'          => 'Please choose an option to get started.',
            'history_label'  => 'New Client',
            'history_desc'   => 'Health history and waiver — about 3 minutes.',
            'waiver_label'   => 'Waiver Only',
            'waiver_desc'    => 'Returning client who just needs to sign.',
            'success'        => 'All set! Thank you.',
            // Seconds to hold the confirmation before resetting for the next
            // walk-in. 0 keeps it on screen until staff tap to reset.
            'reset_after'    => '8',
        ), $atts, 'waxing_kiosk');

        self::enqueue_assets();

        $schema = Waxing_Patients::get_schema();

        ob_start();
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/kiosk.php';
        return ob_get_clean();
    }

    /**
     * The waiver text. Stored as an option so the business can amend the
     * wording without a code change; the text in force at signing time is
     * snapshotted onto the record so amendments never rewrite past consent.
     */
    public static function get_waiver_text() {
        $default = "I hereby release, waive, discharge and covenant not to sue South Beach Body Wax and Esthetics 1 LLC, or South Body Wax and Esthetics LLC, or South Beach Body Wax and Esthetics, their officers, agents, employees, independent contractors or estheticians from any and all liability, claims, demands, action whatsoever, arising out of or related to any loss, damage, or injury, including death, that may be sustained by me, or any of the property belonging to me, whether caused by the negligence of the releasees, or otherwise, while participating in hair removal and/or waxing, or while in, on or upon the premises where the activity is being conducted.\n\nI AM FULLY AWARE OF THE RISKS INVOLVED AND HAZARDS CONNECTED WITH HAIR REMOVAL AND/OR WAXING, and I hereby elect to voluntarily participate in said hair removal and/or waxing with full knowledge that said activity may be hazardous to me and my property.\n\nI voluntarily assume full responsibility for any risks of loss, property damage or personal injury, including death, that may be sustained by me, or any loss or damage to property owned by me, as a result of being engaged in such an activity, whether caused by the negligence of releasees or otherwise.";

        return get_option('waxing_waiver_text', $default);
    }

    /**
     * AJAX: save a health history submission.
     */
    public static function handle_patient_submission() {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $name  = isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if ($name === '') {
            wp_send_json_error(array('message' => 'Please enter your full name.', 'field' => 'full_name'));
        }
        if ($email === '' || !is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.', 'field' => 'email'));
        }

        // Re-submission by the same person updates their record instead of
        // creating a duplicate, so staff see one current card per patient.
        $post_id = self::find_patient_by_email($email);

        if ($post_id) {
            wp_update_post(array(
                'ID'         => $post_id,
                'post_title' => $name,
            ));
        } else {
            $post_id = wp_insert_post(array(
                'post_type'   => Waxing_Patients::POST_TYPE,
                'post_title'  => $name,
                'post_status' => 'publish',
            ), true);

            if (is_wp_error($post_id)) {
                wp_send_json_error(array('message' => 'We could not save your information. Please try again.'));
            }
        }

        foreach (Waxing_Patients::get_all_fields() as $key => $field) {
            if (!isset($_POST[$key])) {
                // Unchecked boxes and untouched conditional inputs are absent
                // from the POST body. Clear them so an updated record doesn't
                // keep a stale "yes" the patient just unticked.
                if (in_array($field['type'], array('checkboxes', 'checkbox_single'), true)) {
                    update_post_meta($post_id, Waxing_Patients::META_PREFIX . $key, $field['type'] === 'checkboxes' ? array() : '');
                }
                continue;
            }

            $raw = wp_unslash($_POST[$key]);
            $value = Waxing_Patients::sanitize_value($field, $raw);

            if ($value === null) {
                continue;
            }

            update_post_meta($post_id, Waxing_Patients::META_PREFIX . $key, $value);
        }

        update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'submitted_at', current_time('mysql'));

        // Optional waiver bundled into the same form.
        if (!empty($_POST['waiver_accept']) && !empty($_POST['waiver_signature'])) {
            self::store_waiver($post_id, sanitize_text_field(wp_unslash($_POST['waiver_signature'])));
        }

        wp_send_json_success(array('message' => 'Saved.'));
    }

    /**
     * AJAX: save a standalone waiver submission.
     */
    public static function handle_waiver_submission() {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $name      = isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '';
        $email     = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $phone     = isset($_POST['home_phone']) ? sanitize_text_field(wp_unslash($_POST['home_phone'])) : '';
        $signature = isset($_POST['waiver_signature']) ? sanitize_text_field(wp_unslash($_POST['waiver_signature'])) : '';

        if ($name === '') {
            wp_send_json_error(array('message' => 'Please enter your full name.', 'field' => 'full_name'));
        }
        if ($email === '' || !is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.', 'field' => 'email'));
        }
        if (empty($_POST['waiver_accept'])) {
            wp_send_json_error(array('message' => 'You must accept the waiver to continue.', 'field' => 'waiver_accept'));
        }
        if ($signature === '') {
            wp_send_json_error(array('message' => 'Please type your full name as your signature.', 'field' => 'waiver_signature'));
        }

        $post_id = self::find_patient_by_email($email);

        if (!$post_id) {
            $post_id = wp_insert_post(array(
                'post_type'   => Waxing_Patients::POST_TYPE,
                'post_title'  => $name,
                'post_status' => 'publish',
            ), true);

            if (is_wp_error($post_id)) {
                wp_send_json_error(array('message' => 'We could not save your waiver. Please try again.'));
            }

            update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'full_name', $name);
            update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'email', $email);
        }

        if ($phone !== '') {
            update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'home_phone', $phone);
        }

        self::store_waiver($post_id, $signature);

        wp_send_json_success(array('message' => 'Saved.'));
    }

    /**
     * Persist the signature plus the evidence that makes it defensible: when
     * it was given, from where, and the exact text that was agreed to.
     */
    private static function store_waiver($post_id, $signature) {
        update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'waiver_signature', $signature);
        update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'waiver_signed_at', current_time('mysql'));
        update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'waiver_text', self::get_waiver_text());

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = filter_var(wp_unslash($_SERVER['REMOTE_ADDR']), FILTER_VALIDATE_IP);
            if ($ip) {
                update_post_meta($post_id, Waxing_Patients::META_PREFIX . 'waiver_ip', $ip);
            }
        }
    }

    /**
     * Locate an existing patient record by email.
     */
    public static function find_patient_by_email($email) {
        $existing = get_posts(array(
            'post_type'      => Waxing_Patients::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => Waxing_Patients::META_PREFIX . 'email',
            'meta_value'     => $email,
        ));

        return empty($existing) ? 0 : (int) $existing[0];
    }
}
