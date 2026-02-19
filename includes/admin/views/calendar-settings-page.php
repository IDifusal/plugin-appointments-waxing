<?php
/**
 * Calendar settings page template
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Calendar Admin Settings</h1>
    <p>Configure the login credentials for the standalone calendar admin panel at: <strong><?php echo home_url('/calendar-admin'); ?></strong></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('waxing_calendar_settings'); ?>
        <h2>Login Credentials</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Username</th>
                <td>
                    <input type="text" name="calendar_username" value="<?php echo esc_attr($current_username); ?>" class="regular-text" required />
                    <p class="description">Username for calendar admin login</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Password</th>
                <td>
                    <input type="password" name="calendar_password" value="<?php echo esc_attr($current_password); ?>" class="regular-text" required />
                    <p class="description">Password for calendar admin login</p>
                </td>
            </tr>
        </table>

        <?php submit_button('Update Credentials', 'primary', 'submit'); ?>
    </form>
    
    <hr style="margin: 30px 0;">
    
    <form method="post" action="">
        <?php wp_nonce_field('waxing_stripe_settings'); ?>
        <h2>Stripe Payment Settings</h2>
        <p>Configure Stripe API keys for the invoice generator. Use test keys for sandbox mode.</p>
        <table class="form-table">
            <tr>
                <th scope="row">Mode</th>
                <td>
                    <select name="stripe_mode" class="regular-text">
                        <option value="sandbox" <?php selected($current_stripe_mode, 'sandbox'); ?>>Sandbox (Test Mode)</option>
                        <option value="live" <?php selected($current_stripe_mode, 'live'); ?>>Live (Production)</option>
                    </select>
                    <p class="description">Use sandbox mode for testing with test API keys</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Secret API Key</th>
                <td>
                    <input type="text" name="stripe_secret_key" value="<?php echo esc_attr($current_stripe_key); ?>" class="regular-text" placeholder="sk_test_..." />
                    <p class="description">
                        <?php if ($current_stripe_mode === 'sandbox'): ?>
                            Enter your Stripe test secret key (starts with sk_test_)
                        <?php else: ?>
                            Enter your Stripe live secret key (starts with sk_live_)
                        <?php endif; ?>
                        <br>
                        <a href="https://dashboard.stripe.com/apikeys" target="_blank">Get your API keys from Stripe Dashboard</a>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button('Update Stripe Settings', 'primary', 'submit_stripe'); ?>
    </form>
    
    <hr style="margin: 30px 0;">

    <form method="post" action="">
        <?php wp_nonce_field('waxing_twilio_settings'); ?>
        <h2>Twilio SMS Notifications</h2>
        <p>Configure Twilio to send SMS notifications to the admin when new appointments are booked.</p>

        <table class="form-table">
            <tr>
                <th scope="row">Enable SMS Notifications</th>
                <td>
                    <label>
                        <input type="checkbox" name="twilio_enabled" value="1" <?php checked($current_twilio_enabled, '1'); ?> />
                        Send SMS notification to admin when appointment is booked
                    </label>
                    <p class="description">Enable this to receive SMS alerts for new appointments</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Admin Phone Number</th>
                <td>
                    <input type="text" name="twilio_admin_number" value="<?php echo esc_attr($current_twilio_admin_number); ?>" class="regular-text" placeholder="+15551234567 or (555) 123-4567" />
                    <p class="description">Phone number to receive appointment notifications</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Twilio Account SID</th>
                <td>
                    <input type="text" name="twilio_account_sid" value="<?php echo esc_attr($current_twilio_sid); ?>" class="regular-text" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />
                    <p class="description">Your Twilio Account SID from the <a href="https://console.twilio.com/" target="_blank">Twilio Console</a></p>
                </td>
            </tr>
            <tr>
                <th scope="row">Twilio Auth Token</th>
                <td>
                    <input type="password" name="twilio_auth_token" value="<?php echo esc_attr($current_twilio_token); ?>" class="regular-text" placeholder="********************************" />
                    <p class="description">Your Twilio Auth Token (keep this secret!)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Twilio Phone Number</th>
                <td>
                    <input type="text" name="twilio_phone_number" value="<?php echo esc_attr($current_twilio_phone); ?>" class="regular-text" placeholder="+15551234567" />
                    <p class="description">Your Twilio phone number in E.164 format (e.g., +15551234567)</p>
                </td>
            </tr>
        </table>

        <?php submit_button('Update Twilio Settings', 'primary', 'submit_twilio'); ?>
    </form>

    <?php if (!empty($current_twilio_sid) && !empty($current_twilio_token) && !empty($current_twilio_phone)): ?>
    <div class="card" style="max-width: 600px; margin-top: 20px; padding: 20px;">
        <h3>Test Twilio Configuration</h3>
        <p>Verify your Twilio credentials and optionally send a test SMS.</p>

        <div id="twilio-test-form" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
            <button type="button" id="test-twilio-config" class="button button-secondary">Test Configuration</button>
            <button type="button" id="send-test-sms" class="button button-secondary">Send Test SMS</button>
            <span id="test-loading" style="display:none; margin-left: 10px;">
                <span class="spinner is-active" style="float:none;"></span>
            </span>
        </div>

        <?php if (!empty($current_twilio_admin_number)): ?>
            <p class="description">Test SMS will be sent to: <strong><?php echo esc_html($current_twilio_admin_number); ?></strong></p>
        <?php else: ?>
            <p class="description" style="color: #d63638;">⚠️ Configure the Admin Phone Number above to enable test SMS</p>
        <?php endif; ?>

        <div id="test-result" style="margin-top: 15px;"></div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var adminNumber = '<?php echo esc_js($current_twilio_admin_number); ?>';
        var loading = $('#test-loading');
        var result = $('#test-result');

        // Test Configuration Button
        $('#test-twilio-config').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true);
            $('#send-test-sms').prop('disabled', true);
            loading.show();
            result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_twilio_config',
                    nonce: '<?php echo wp_create_nonce('waxing_twilio_test'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var msg = '<div class="notice notice-success"><p><strong>✓ Configuration Valid!</strong><br>';
                        msg += 'Account: ' + response.data.account_name + '<br>';
                        msg += 'Phone Number: ' + response.data.phone_number + '<br>';
                        msg += 'Status: ' + response.data.status;
                        msg += '</p></div>';
                        result.html(msg);
                    } else {
                        result.html('<div class="notice notice-error"><p><strong>✗ Configuration Error:</strong> ' + response.data + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    result.html('<div class="notice notice-error"><p><strong>✗ Network Error:</strong> Could not connect to server.</p></div>');
                },
                complete: function() {
                    btn.prop('disabled', false);
                    $('#send-test-sms').prop('disabled', false);
                    loading.hide();
                }
            });
        });

        // Send Test SMS Button
        $('#send-test-sms').on('click', function() {
            var btn = $(this);

            if (!adminNumber) {
                result.html('<div class="notice notice-error"><p><strong>⚠️ Configuration Required:</strong> Please configure the Admin Phone Number above and save settings before testing.</p></div>');
                return;
            }

            btn.prop('disabled', true);
            $('#test-twilio-config').prop('disabled', true);
            loading.show();
            result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'send_twilio_test_sms',
                    test_number: adminNumber,
                    nonce: '<?php echo wp_create_nonce('waxing_twilio_test'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var msg = '<div class="notice notice-success"><p><strong>✓ SMS Sent!</strong> ' + response.data.message;
                        if (response.data.sid) {
                            msg += '<br><small>Message SID: ' + response.data.sid + '</small>';
                        }
                        msg += '</p></div>';
                        result.html(msg);
                    } else {
                        result.html('<div class="notice notice-error"><p><strong>✗ Error:</strong> ' + response.data + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    result.html('<div class="notice notice-error"><p><strong>✗ Network Error:</strong> Could not connect to server.</p></div>');
                },
                complete: function() {
                    btn.prop('disabled', false);
                    $('#test-twilio-config').prop('disabled', false);
                    loading.hide();
                }
            });
        });
    });
    </script>
    <?php endif; ?>

    <div class="card" style="max-width: 600px; margin-top: 20px;">
        <h3>How to Access Calendar Admin</h3>
        <ol>
            <li>Go to: <a href="<?php echo home_url('/calendar-admin'); ?>" target="_blank"><?php echo home_url('/calendar-admin'); ?></a></li>
            <li>Use the credentials configured above</li>
            <li>Manage appointment availability independently of WordPress</li>
        </ol>
        <p><strong>Note:</strong> This calendar admin system is completely independent of WordPress user accounts. Anyone with these credentials can access the calendar management interface.</p>
    </div>
</div>

