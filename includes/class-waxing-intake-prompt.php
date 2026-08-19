<?php
/**
 * Post-booking prompt inviting the client to complete their intake paperwork.
 *
 * Placed on the WooCommerce thank-you page rather than inside the booking
 * modal on purpose. The modal is the revenue path: adding a 63-question health
 * history in front of the payment button buys paperwork at the cost of
 * bookings. Once payment is done the client is committed, has their phone in
 * hand and is at a natural pause - and the order finally tells us who they
 * are, so the form arrives partly filled instead of blank.
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Intake_Prompt {

    /**
     * Where the intake form lives. Stored as an option so the shop owner can
     * move the page without editing code.
     */
    const OPTION_URL = 'waxing_intake_page_url';

    /**
     * The live intake page. Trailing slash is deliberate: WordPress 301s the
     * unslashed form, and while that redirect does preserve the query string,
     * pointing straight at the canonical URL saves every client a round trip.
     */
    const DEFAULT_URL = '/new-patient/';

    /**
     * Configured destination for the intake form, or '' if switched off.
     *
     * An explicitly blank option is treated as "disabled" so the prompt can be
     * turned off without deactivating anything else.
     */
    public static function get_intake_url() {
        $url = get_option(self::OPTION_URL, self::DEFAULT_URL);

        if (!is_string($url)) {
            return '';
        }

        return trim($url);
    }

    /**
     * Render the prompt under the thank-you page order details.
     *
     * @param int $order_id WooCommerce order ID.
     */
    public static function render($order_id) {
        $base = self::get_intake_url();
        if ($base === '') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Only appointment orders get this prompt - a plain product purchase
        // has no upcoming visit to prepare for.
        if (!self::order_has_appointment($order)) {
            return;
        }

        $email = sanitize_email($order->get_billing_email());

        // Returning clients already have a record on file, so asking them to
        // fill it in again is noise. Their esthetician can update it in person.
        if ($email !== '' && Waxing_Patient_Forms::find_patient_by_email($email)) {
            return;
        }

        $name  = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $phone = $order->get_billing_phone();

        $args = array();
        if ($name !== '') {
            $args['wx_name'] = $name;
        }
        if ($email !== '') {
            $args['wx_email'] = $email;
        }
        if ($phone !== '') {
            $args['wx_phone'] = $phone;
        }

        $link = empty($args) ? $base : add_query_arg(array_map('rawurlencode', $args), $base);

        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/views/intake-prompt.php';
    }

    /**
     * True when any line item carries booking metadata.
     */
    private static function order_has_appointment($order) {
        foreach ($order->get_items() as $item) {
            $data = $item->get_meta('_appointment_data');
            if ($data && is_array($data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The prompt reuses the intake form's palette so the hand-off from
     * checkout to paperwork looks like one flow.
     *
     * `woocommerce_thankyou` fires in the body, long after wp_enqueue_scripts
     * has flushed the <head>, so enqueueing here would silently do nothing and
     * the prompt would render unstyled. Hook the stylesheet on early instead,
     * while we're still in the header.
     */
    public static function maybe_enqueue_styles() {
        if (!function_exists('is_order_received_page') || !is_order_received_page()) {
            return;
        }

        if (self::get_intake_url() === '') {
            return;
        }

        if (wp_style_is('waxing-patient-forms', 'registered')) {
            wp_enqueue_style('waxing-patient-forms');
        }
    }
}
