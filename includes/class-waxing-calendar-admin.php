<?php
/**
 * Calendar Admin handler
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/class-waxing-calendar-admin-views.php';

class Waxing_Calendar_Admin {

    /**
     * Name of the auth cookie issued on successful calendar admin login.
     */
    const AUTH_COOKIE = 'waxing_calendar_auth';

    /**
     * How long a calendar admin stays logged in (12 hours).
     */
    const AUTH_LIFETIME = 43200;

    /**
     * Issue an auth token for a calendar admin user.
     *
     * The token is stored in a transient and handed to the browser in a cookie
     * scoped to the whole site. PHP sessions are not reliable here: page caches
     * strip PHPSESSID, session.gc_maxlifetime expires the session while the
     * dashboard is still on screen, and multi-worker hosting can lose the
     * session file between the page render and the admin-ajax.php request.
     */
    private static function issue_auth_token($username) {
        $token = wp_generate_password(48, false, false);

        set_transient('waxing_cal_auth_' . $token, array(
            'user' => $username,
            'login_time' => time(),
        ), self::AUTH_LIFETIME);

        setcookie(
            self::AUTH_COOKIE,
            $token,
            array(
                'expires' => time() + self::AUTH_LIFETIME,
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );
        $_COOKIE[self::AUTH_COOKIE] = $token;

        return $token;
    }

    /**
     * Validate the current request's calendar admin credentials.
     *
     * Accepts the cookie token first, then falls back to a legacy PHP session
     * so admins who are already logged in are not kicked out by this upgrade.
     */
    public static function is_authenticated() {
        if (!empty($_COOKIE[self::AUTH_COOKIE])) {
            $token = sanitize_text_field(wp_unslash($_COOKIE[self::AUTH_COOKIE]));
            $data = get_transient('waxing_cal_auth_' . $token);

            if (!empty($data) && !empty($data['user'])) {
                return true;
            }
        }

        // Legacy session fallback (pre-token logins).
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        return !empty($_SESSION['calendar_admin_logged_in']);
    }

    /**
     * Clear the current calendar admin credentials.
     */
    public static function clear_auth() {
        if (!empty($_COOKIE[self::AUTH_COOKIE])) {
            $token = sanitize_text_field(wp_unslash($_COOKIE[self::AUTH_COOKIE]));
            delete_transient('waxing_cal_auth_' . $token);

            setcookie(self::AUTH_COOKIE, '', array(
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ));
            unset($_COOKIE[self::AUTH_COOKIE]);
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            session_destroy();
        }
    }

    /**
     * Reject an unauthenticated AJAX request.
     *
     * Sends a machine-readable code so the dashboard can redirect to the login
     * screen instead of showing a toast over a calendar that still looks fine.
     */
    public static function require_auth() {
        if (self::is_authenticated()) {
            return;
        }

        wp_send_json_error(array(
            'code' => 'not_authenticated',
            'message' => 'Not authenticated - please login first',
        ));
    }

    /**
     * Handle calendar admin route
     */
    public static function handle_route() {
        // Check if this is the calendar admin route
        $request_uri = $_SERVER['REQUEST_URI'];
        $parsed_url = parse_url($request_uri);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        
        // Remove trailing slash and check if path ends with calendar-admin
        $path = rtrim($path, '/');
        
        if (isset($_GET['calendar-admin']) || 
            $path === '/calendar-admin' || 
            substr($path, -15) === '/calendar-admin' ||
            (is_404() && strpos($request_uri, 'calendar-admin') !== false)) {
            
            // Never let a page cache / CDN serve this route: a cached copy would
            // drop the auth cookie and show the dashboard to a logged-out visitor.
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            nocache_headers();

            // Handle logout
            if (isset($_GET['logout'])) {
                self::clear_auth();
                wp_redirect(home_url('/calendar-admin'));
                exit;
            }

            if (!self::is_authenticated()) {
                Waxing_Calendar_Admin_Views::show_login();
                exit;
            } else {
                Waxing_Calendar_Admin_Views::show_dashboard();
                exit;
            }
        }
    }
    
    /**
     * Parse calendar admin request
     */
    public static function parse_request($wp) {
        if (isset($wp->request) && $wp->request === 'calendar-admin') {
            // Mark this as a valid request to prevent 404
            $wp->matched_rule = 'calendar-admin';
            $wp->matched_query = 'calendar-admin=1';
            $wp->query_vars['calendar-admin'] = '1';
        }
    }
    
    /**
     * Handle calendar admin login
     */
    public static function handle_login() {
        // Bypass WordPress nonce for standalone login
        if (!isset($_POST['username']) || !isset($_POST['password'])) {
            wp_send_json_error('Missing credentials');
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        
        // Get credentials from WordPress options (but authentication is still independent)
        $admin_username = get_option('waxing_calendar_admin_username', 'admin');
        $admin_password = get_option('waxing_calendar_admin_password', 'waxing2024');

        $authenticated = ($username === $admin_username && $password === $admin_password);

        // Check additional calendar admin users (created in plugin settings, passwords stored hashed)
        if (!$authenticated) {
            $calendar_users = get_option('waxing_calendar_admin_users', array());
            if (isset($calendar_users[$username]) && password_verify($password, $calendar_users[$username])) {
                $authenticated = true;
            }
        }

        if ($authenticated) {
            self::issue_auth_token($username);
            wp_send_json_success('Login successful');
        } else {
            wp_send_json_error('Invalid credentials');
        }
    }
    
    /**
     * Handle blocking calendar time
     */
    public static function handle_block_time() {
        Waxing_Calendar_Admin::require_auth();
        
        if (!isset($_POST['date']) || !isset($_POST['time'])) {
            wp_send_json_error('Missing date or time parameters');
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        // Normalize time format to HH:MM:SS
        if (strlen($time) === 5) {
            $time = $time . ':00';
        }
        
        // Validate time format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            wp_send_json_error('Invalid time format');
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();

        // A slot can optionally be blocked for a single office; default blocks every office.
        $office = isset($_POST['office']) ? sanitize_text_field($_POST['office']) : '';
        $offices = Waxing_Services::get_offices();
        $target_offices = ($office !== '' && isset($offices[$office])) ? array($office) : array_keys($offices);

        // Check if the slot is already booked by a confirmed appointment (in any target office)
        $is_booked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s AND office IN (" . implode(',', array_fill(0, count($target_offices), '%s')) . ") AND status IN ('booked', 'confirmed')",
            array_merge(array($date, $time), $target_offices)
        ));

        if ($is_booked) {
            wp_send_json_error('Cannot block a time slot that is already booked');
        }

        // Get optional blocked_for (user name) parameter
        $blocked_for = isset($_POST['blocked_for']) ? sanitize_text_field($_POST['blocked_for']) : '';
        $blocked_for = trim($blocked_for);

        $blocked_count = 0;
        foreach ($target_offices as $target_office) {
            // Remove any existing record for this office/date/time (to handle status changes)
            $wpdb->delete(
                $appointments_table,
                array('appointment_date' => $date, 'appointment_time' => $time, 'office' => $target_office),
                array('%s', '%s', '%s')
            );

            $insert_data = array(
                'appointment_date' => $date,
                'appointment_time' => $time,
                'office' => $target_office,
                'status' => 'blocked'
            );
            $insert_format = array('%s', '%s', '%s', '%s');

            if (!empty($blocked_for)) {
                $insert_data['customer_name'] = $blocked_for;
                $insert_format[] = '%s';
            }

            $result = $wpdb->insert($appointments_table, $insert_data, $insert_format);
            if ($result !== false) {
                $blocked_count++;
            }
        }

        if ($blocked_count > 0) {
            $message = 'Time slot blocked successfully';
            if (!empty($blocked_for)) {
                $message .= ' for ' . esc_html($blocked_for);
            }
            wp_send_json_success($message);
        } else {
            $error = $wpdb->last_error;
            wp_send_json_error('Failed to block time slot - database error: ' . $error);
        }
    }
    
    /**
     * Handle unblocking calendar time
     */
    public static function handle_unblock_time() {
        Waxing_Calendar_Admin::require_auth();
        
        if (!isset($_POST['date']) || !isset($_POST['time'])) {
            wp_send_json_error('Missing date or time parameters');
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        
        // Normalize time format
        if (strlen($time) === 5) {
            $time = $time . ':00';
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();

        // Unblock a single office if provided, otherwise clear the block for every office.
        $office = isset($_POST['office']) ? sanitize_text_field($_POST['office']) : '';
        $offices = Waxing_Services::get_offices();

        if ($office !== '' && isset($offices[$office])) {
            $result = $wpdb->delete(
                $appointments_table,
                array('appointment_date' => $date, 'appointment_time' => $time, 'office' => $office, 'status' => 'blocked'),
                array('%s', '%s', '%s', '%s')
            );
        } else {
            $result = $wpdb->delete(
                $appointments_table,
                array('appointment_date' => $date, 'appointment_time' => $time, 'status' => 'blocked'),
                array('%s', '%s', '%s')
            );
        }

        if ($result !== false) {
            wp_send_json_success('Time slot unblocked successfully');
        } else {
            wp_send_json_error('Failed to unblock time slot - database error');
        }
    }
    
    /**
     * Handle blocking entire day
     */
    public static function handle_block_day() {
        Waxing_Calendar_Admin::require_auth();
        
        if (!isset($_POST['date'])) {
            wp_send_json_error('Missing date parameter');
        }
        
        $date = sanitize_text_field($_POST['date']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        // Get all time slots for this date
        $time_slots = Waxing_Services::get_time_slots_for_date($date);
        
        if (empty($time_slots)) {
            wp_send_json_error('No time slots available for this date (day may be closed)');
        }
        
        // Blocking a whole day closes every office for that date.
        $office = isset($_POST['office']) ? sanitize_text_field($_POST['office']) : '';
        $offices = Waxing_Services::get_offices();
        $target_offices = ($office !== '' && isset($offices[$office])) ? array($office) : array_keys($offices);

        $blocked_count = 0;
        $skipped_appointments = 0;
        $skipped_already_blocked = 0;

        foreach ($time_slots as $time) {
            // Normalize time format to HH:MM:SS
            if (strlen($time) === 5) {
                $time = $time . ':00';
            }

            foreach ($target_offices as $target_office) {
                // Check if slot is already booked by an appointment (for this office)
                $is_booked = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s AND office = %s AND status IN ('booked', 'confirmed')",
                    $date, $time, $target_office
                ));

                if ($is_booked) {
                    $skipped_appointments++;
                    continue;
                }

                // Check if slot is already blocked (preserve existing blocked slots)
                $is_already_blocked = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND appointment_time = %s AND office = %s AND status = 'blocked'",
                    $date, $time, $target_office
                ));

                if ($is_already_blocked) {
                    $skipped_already_blocked++;
                    continue;
                }

                // Insert the blocked slot (no customer_name for day blocking)
                $result = $wpdb->insert(
                    $appointments_table,
                    array(
                        'appointment_date' => $date,
                        'appointment_time' => $time,
                        'office' => $target_office,
                        'status' => 'blocked'
                    ),
                    array('%s', '%s', '%s', '%s')
                );

                if ($result !== false) {
                    $blocked_count++;
                }
            }
        }
        
        if ($blocked_count > 0) {
            $message = "Day blocked successfully. {$blocked_count} time slot(s) blocked.";
            if ($skipped_appointments > 0) {
                $message .= " {$skipped_appointments} slot(s) skipped (already booked by appointments).";
            }
            if ($skipped_already_blocked > 0) {
                $message .= " {$skipped_already_blocked} slot(s) skipped (already blocked).";
            }
            wp_send_json_success($message);
        } else {
            $message = 'No slots could be blocked.';
            if ($skipped_appointments > 0 || $skipped_already_blocked > 0) {
                $message .= ' All slots are already booked or blocked.';
            }
            wp_send_json_error($message);
        }
    }
    
    /**
     * Handle unblocking entire day
     */
    public static function handle_unblock_day() {
        Waxing_Calendar_Admin::require_auth();
        
        if (!isset($_POST['date'])) {
            wp_send_json_error('Missing date parameter');
        }
        
        $date = sanitize_text_field($_POST['date']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error('Invalid date format');
        }
        
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        // Delete all blocked slots for this date
        $result = $wpdb->delete(
            $appointments_table,
            array('appointment_date' => $date, 'status' => 'blocked'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success("Day unblocked successfully. {$result} time slot(s) unblocked.");
        } else {
            wp_send_json_error('Failed to unblock day - database error');
        }
    }
    
    /**
     * Fixed color palette per office for the single calendar view.
     */
    public static function get_office_colors() {
        return array(
            'harrisburg'   => array('bg' => '#0073aa', 'border' => '#005a87'),
            'indian_trail' => array('bg' => '#7b3fbf', 'border' => '#5c2e8f'),
            '_default'     => array('bg' => '#0073aa', 'border' => '#005a87'),
        );
    }

    /**
     * Short label used to prefix event titles so offices are distinguishable at a glance.
     */
    public static function get_office_short_label($office_value) {
        $labels = array(
            'harrisburg'   => 'HBG',
            'indian_trail' => 'IT',
        );
        if (isset($labels[$office_value])) {
            return $labels[$office_value];
        }
        return strtoupper(substr((string) $office_value, 0, 3));
    }

    /**
     * Get calendar events and fully blocked days data
     * Returns array with 'events' and 'fully_blocked_days' keys
     */
    public static function get_calendar_data() {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        $today = date('Y-m-d');
        $future_date = date('Y-m-d', strtotime('+60 days'));
        
        $appointments_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $appointments_table WHERE appointment_date >= %s AND appointment_date <= %s AND status != 'blocked' ORDER BY appointment_date, appointment_time",
            $today,
            $future_date
        ));
        
        $blocked_records = $wpdb->get_results(
            "SELECT * FROM $appointments_table WHERE status = 'blocked' ORDER BY appointment_date, appointment_time"
        );
        
        $appointments = array();
        $blocked_slots = array();
        
        foreach ($appointments_records as $record) {
            $appointments[] = $record;
        }
        
        foreach ($blocked_records as $record) {
            $blocked_slots[] = $record;
        }
        
        $calendar_events = array();
        
        // Distinct colors per office so a single calendar visually separates locations.
        $office_colors = self::get_office_colors();

        foreach ($appointments as $appointment) {
            $service_name = str_replace('_', ' ', ucwords($appointment->service_id));
            $office = Waxing_Services::get_office($appointment->office);
            $office_name = $office ? $office['name'] : ucwords(str_replace('_', ' ', (string) $appointment->office));
            $office_short = self::get_office_short_label($appointment->office);
            $colors = isset($office_colors[$appointment->office]) ? $office_colors[$appointment->office] : $office_colors['_default'];

            $calendar_events[] = array(
                'id' => 'appointment_' . $appointment->id,
                'title' => '[' . $office_short . '] ' . $appointment->customer_name . ' - ' . $service_name,
                'start' => $appointment->appointment_date . 'T' . $appointment->appointment_time,
                'end' => date('Y-m-d\TH:i:s', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time . ' +30 minutes')),
                'backgroundColor' => $colors['bg'],
                'borderColor' => $colors['border'],
                'extendedProps' => array(
                    'type' => 'appointment',
                    'customer_name' => $appointment->customer_name,
                    'customer_email' => $appointment->customer_email,
                    'customer_phone' => $appointment->customer_phone,
                    'service' => $service_name,
                    'office' => $appointment->office,
                    'office_name' => $office_name,
                    'status' => $appointment->status,
                    'total_price' => $appointment->total_price,
                    'deposit_paid' => $appointment->deposit_paid
                )
            );
        }
        
        // A block can exist per office; collapse the same date+time into one calendar
        // event and track which offices it covers so the single calendar stays readable.
        $blocked_by_slot = array();
        foreach ($blocked_slots as $slot) {
            $key = $slot->appointment_date . '_' . $slot->appointment_time;
            if (!isset($blocked_by_slot[$key])) {
                $blocked_by_slot[$key] = array(
                    'date' => $slot->appointment_date,
                    'time' => $slot->appointment_time,
                    'offices' => array(),
                    'customer_name' => '',
                );
            }
            $blocked_by_slot[$key]['offices'][] = $slot->office;
            if (empty($blocked_by_slot[$key]['customer_name']) && !empty($slot->customer_name)) {
                $blocked_by_slot[$key]['customer_name'] = $slot->customer_name;
            }
        }

        $all_office_keys = array_keys(Waxing_Services::get_offices());

        foreach ($blocked_by_slot as $slot) {
            $covers_all = count(array_unique($slot['offices'])) >= count($all_office_keys);
            $office_labels = array();
            foreach (array_unique($slot['offices']) as $ov) {
                $office_labels[] = self::get_office_short_label($ov);
            }
            $scope_suffix = $covers_all ? '' : ' (' . implode(', ', $office_labels) . ')';

            $has_name = !empty($slot['customer_name']);
            $title = $has_name ? $slot['customer_name'] : 'Blocked';
            $title .= $scope_suffix;

            $calendar_events[] = array(
                'id' => 'blocked_' . $slot['date'] . '_' . str_replace(':', '', $slot['time']),
                'title' => $title,
                'start' => $slot['date'] . 'T' . $slot['time'],
                'end' => date('Y-m-d\TH:i:s', strtotime($slot['date'] . ' ' . $slot['time'] . ' +30 minutes')),
                'backgroundColor' => $has_name ? '#0073aa' : '#d63638',
                'borderColor' => $has_name ? '#005a87' : '#a02622',
                'extendedProps' => array(
                    'type' => 'blocked',
                    'date' => $slot['date'],
                    'time' => $slot['time'],
                    'offices' => array_values(array_unique($slot['offices'])),
                    'covers_all_offices' => $covers_all,
                    'blocked_for' => $slot['customer_name']
                )
            );
        }
        
        // A day counts as fully blocked when every distinct time slot is unavailable
        // (blocked or booked) across every office. We work per distinct time slot so
        // the per-office fan-out of blocked rows doesn't distort the comparison.
        $office_count = max(1, count($all_office_keys));
        $unavailable_by_date = array(); // date => [ time => count of offices blocked/booked ]

        foreach ($blocked_slots as $slot) {
            $unavailable_by_date[$slot->appointment_date][$slot->appointment_time]['blocked'][$slot->office] = true;
        }

        $fully_blocked_days = array();
        foreach ($unavailable_by_date as $date => $times) {
            $day_of_week = date('w', strtotime($date));
            if ($day_of_week == 0) continue;

            $expected_slots = Waxing_Services::get_time_slots_for_date($date);
            if (empty($expected_slots)) continue;

            // Pull booked/confirmed slots for this date so a booked slot still counts as "closed".
            $booked_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT appointment_time, office FROM $appointments_table WHERE appointment_date = %s AND status IN ('booked', 'confirmed')",
                $date
            ));
            foreach ($booked_rows as $b) {
                $unavailable_by_date[$date][$b->appointment_time]['blocked'][$b->office] = true;
            }

            $day_fully_blocked = true;
            foreach ($expected_slots as $slot_time) {
                if (strlen($slot_time) === 5) {
                    $slot_time = $slot_time . ':00';
                }
                $covered = isset($unavailable_by_date[$date][$slot_time]['blocked'])
                    ? count($unavailable_by_date[$date][$slot_time]['blocked'])
                    : 0;
                if ($covered < $office_count) {
                    $day_fully_blocked = false;
                    break;
                }
            }

            if ($day_fully_blocked) {
                $fully_blocked_days[] = $date;
            }
        }
        
        return array(
            'events' => $calendar_events,
            'fully_blocked_days' => $fully_blocked_days
        );
    }
    
    /**
     * Handle AJAX request to get calendar events
     */
    public static function handle_get_calendar_events() {
        Waxing_Calendar_Admin::require_auth();
        
        $data = self::get_calendar_data();
        wp_send_json_success($data);
    }
}

