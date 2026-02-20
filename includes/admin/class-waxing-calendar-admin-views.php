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
        
        // Get calendar data using shared method
        $calendar_data = Waxing_Calendar_Admin::get_calendar_data();
        $calendar_events = $calendar_data['events'];
        $fully_blocked_days = $calendar_data['fully_blocked_days'];
        
        // Get appointments and blocked slots for stats display
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
        
        // Include the dashboard HTML template
        include WAXING_APPOINTMENTS_PLUGIN_DIR . 'includes/admin/views/calendar-dashboard.php';
    }
}

