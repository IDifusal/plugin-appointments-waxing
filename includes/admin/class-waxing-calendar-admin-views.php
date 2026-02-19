<?php
/**
 * Calendar Admin Views
 * Contains HTML templates for calendar admin interface
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Waxing_Calendar_Admin_Views {
    
    /**
     * Show login page
     */
    public static function show_login() {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Calendar Admin Login</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    background: #f1f1f1; 
                    margin: 0; 
                    padding: 50px 0;
                }
                .login-container { 
                    max-width: 400px; 
                    margin: 0 auto; 
                    background: white; 
                    padding: 30px; 
                    border-radius: 8px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .login-container h2 { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    color: #333;
                }
                .form-group { 
                    margin-bottom: 20px; 
                }
                .form-group label { 
                    display: block; 
                    margin-bottom: 5px; 
                    color: #555;
                }
                .form-group input { 
                    width: 100%; 
                    padding: 12px; 
                    border: 1px solid #ddd; 
                    border-radius: 4px; 
                    box-sizing: border-box;
                }
                .login-btn { 
                    width: 100%; 
                    padding: 12px; 
                    background: #0073aa; 
                    color: white; 
                    border: none; 
                    border-radius: 4px; 
                    cursor: pointer; 
                    font-size: 16px;
                }
                .login-btn:hover { 
                    background: #005a87; 
                }
                .error { 
                    color: #d63638; 
                    margin-bottom: 15px; 
                    text-align: center;
                }
            </style>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        </head>
        <body>
            <div class="login-container">
                <h2>Calendar Admin Login</h2>
                <div id="error-message" class="error" style="display:none;"></div>
                <form id="admin-login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                </form>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#admin-login-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'calendar_admin_login',
                            username: $('#username').val(),
                            password: $('#password').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                $('#error-message').text(response.data).show();
                            }
                        },
                        error: function() {
                            $('#error-message').text('Login error. Please try again.').show();
                        }
                    });
                });
            });
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Show dashboard
     */
    public static function show_dashboard() {
        global $wpdb;
        $appointments_table = Waxing_Database::get_table_name();
        
        // Get all appointments and blocked slots
        // For appointments: get next 60 days
        // For blocked slots: get ALL blocked slots regardless of date (they may be in the past or future)
        $today = date('Y-m-d');
        $future_date = date('Y-m-d', strtotime('+60 days'));
        
        // Get appointments for next 60 days
        $appointments_records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $appointments_table WHERE appointment_date >= %s AND appointment_date <= %s AND status != 'blocked' ORDER BY appointment_date, appointment_time",
            $today,
            $future_date
        ));
        
        // Get ALL blocked slots (no date restriction)
        $blocked_records = $wpdb->get_results(
            "SELECT * FROM $appointments_table WHERE status = 'blocked' ORDER BY appointment_date, appointment_time"
        );
        
        // Combine records
        $records = array_merge($appointments_records, $blocked_records);
        
        // Separate appointments from blocked slots
        $appointments = array();
        $blocked_slots = array();
        
        foreach ($records as $record) {
            if ($record->status === 'blocked') {
                $blocked_slots[] = $record;
            } else {
                $appointments[] = $record;
            }
        }
        
        // Prepare data for FullCalendar
        $calendar_events = array();
        
        // Add appointments as events
        foreach ($appointments as $appointment) {
            $service_name = str_replace('_', ' ', ucwords($appointment->service_id));
            $calendar_events[] = array(
                'id' => 'appointment_' . $appointment->id,
                'title' => $appointment->customer_name . ' - ' . $service_name,
                'start' => $appointment->appointment_date . 'T' . $appointment->appointment_time,
                'end' => date('Y-m-d\TH:i:s', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time . ' +1 hour')),
                'backgroundColor' => '#0073aa',
                'borderColor' => '#005a87',
                'extendedProps' => array(
                    'type' => 'appointment',
                    'customer_name' => $appointment->customer_name,
                    'customer_email' => $appointment->customer_email,
                    'customer_phone' => $appointment->customer_phone,
                    'service' => $service_name,
                    'status' => $appointment->status,
                    'total_price' => $appointment->total_price,
                    'deposit_paid' => $appointment->deposit_paid
                )
            );
        }
        
        // Add blocked time slots as events
        foreach ($blocked_slots as $slot) {
            $calendar_events[] = array(
                'id' => 'blocked_' . $slot->appointment_date . '_' . str_replace(':', '', $slot->appointment_time),
                'title' => 'Blocked',
                'start' => $slot->appointment_date . 'T' . $slot->appointment_time,
                'end' => date('Y-m-d\TH:i:s', strtotime($slot->appointment_date . ' ' . $slot->appointment_time . ' +1 hour')),
                'backgroundColor' => '#d63638',
                'borderColor' => '#a02622',
                'extendedProps' => array(
                    'type' => 'blocked',
                    'date' => $slot->appointment_date,
                    'time' => $slot->appointment_time
                )
            );
        }
        
        // Calculate which days are fully blocked
        $fully_blocked_days = array();
        $blocked_by_date = array();
        foreach ($blocked_slots as $slot) {
            if (!isset($blocked_by_date[$slot->appointment_date])) {
                $blocked_by_date[$slot->appointment_date] = 0;
            }
            $blocked_by_date[$slot->appointment_date]++;
        }
        
        // Check if all time slots for each date are blocked
        foreach ($blocked_by_date as $date => $blocked_count) {
            $day_of_week = date('w', strtotime($date));
            if ($day_of_week == 0) continue; // Skip Sunday
            
            $expected_slots = count(Waxing_Services::get_time_slots_for_date($date));
            // Get booked appointments for this date
            $booked_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $appointments_table WHERE appointment_date = %s AND status IN ('booked', 'confirmed')",
                $date
            ));
            
            // Day is fully blocked if all available slots are blocked
            if ($blocked_count >= ($expected_slots - $booked_count)) {
                $fully_blocked_days[] = $date;
            }
        }
        
        // Include the dashboard HTML template
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/views/calendar-dashboard.php';
    }
}

