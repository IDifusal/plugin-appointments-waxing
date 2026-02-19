# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a WordPress plugin for waxing appointment booking with WooCommerce integration. The plugin allows customers to book appointments through a modal interface, pay a 20% deposit via WooCommerce, and includes a standalone calendar admin dashboard for managing time slots and generating Stripe payclassment links.

**Key Technologies:**

- WordPress 5.0+ with WooCommerce 3.0+
- PHP 7.4+
- Air Datepicker for date selection
- Stripe API for payment link generation
- Custom database tables for appointments

## Development Environment

This plugin is developed within a XAMPP environment. The WordPress installation is located at:
`/Applications/XAMPP/xamppfiles/htdocs/wordpress/`

### Testing the Plugin

1. **Local WordPress Site**: Access at `http://localhost/wordpress`
2. **Use shortcode** `[waxing_appointment_button]` on any page to test booking functionality
3. **Calendar Admin Dashboard**: Access at `http://localhost/wordpress/calendar-admin`
  - Default credentials: `admin` / `waxing2024` (configurable in WordPress options)
4. **WooCommerce**: Must be installed and active for checkout flow to work

### Important Development Rule

**Always increment the plugin version** in `waxing-appointments.php` header when making changes:

```php
* Version: X.X.X
```

Also update the constant:

```php
define('WAXING_APPOINTMENTS_VERSION', 'X.X.X');
```

## Architecture

### Plugin Entry Point

**File**: `waxing-appointments.php`

Main plugin class that initializes the system by:

- Loading all class files from `includes/`
- Registering WordPress hooks (actions/filters)
- Setting up AJAX endpoints
- Handling plugin activation (creates database tables)

### Core Classes

All classes are located in `includes/` and follow WordPress naming conventions:

#### `Waxing_Database`

- **Purpose**: Database schema management
- **Key Methods**:
  - `create_tables()` - Creates `wp_waxing_appointments` and `wp_waxing_payment_links` tables
- **Note**: The appointments table uses a UNIQUE constraint on `(appointment_date, appointment_time)` to prevent double-bookings

#### `Waxing_Services`

- **Purpose**: Business hours and service management
- **Key Methods**:
  - `get_business_hours($day_of_week)` - Returns operating hours for a given day (0=Sunday, 6=Saturday)
  - `get_time_slots_for_date($date)` - Generates hourly time slots based on business hours
  - `get_waxing_services()` - Fetches all simple WooCommerce products as bookable services
- **Business Hours**:
  - Sunday: Closed
  - Mon-Wed: 10:00-17:00
  - Thursday: 10:00-19:00
  - Friday: 10:00-17:00
  - Saturday: 09:00-14:00

#### `Waxing_Appointments_Handler`

- **Purpose**: Appointment booking logic and availability checking
- **Key Methods**:
  - `check_availability()` - AJAX handler that returns available time slots for a date
  - `handle_appointment_booking()` - AJAX handler for booking (creates WooCommerce checkout session)
  - `is_time_available()` - Validates if a time slot is free (checks for 'booked', 'confirmed', or 'blocked' status)
  - `is_business_hours()` - Validates if date/time falls within operating hours
- **Important**: Appointments are NOT saved to the database until payment is completed (see WooCommerce integration)

#### `Waxing_WooCommerce`

- **Purpose**: WooCommerce integration for deposit payments
- **Workflow**:
  1. `create_checkout_session()` - Adds product to cart with appointment metadata, redirects to checkout
  2. `set_cart_item_price()` - Modifies cart item price to 20% deposit
  3. `save_appointment_to_order()` - Stores appointment data in order item meta (including `_appointment_data`)
  4. `create_appointment_on_payment_complete()` - **Critical**: Creates appointment in database ONLY after payment succeeds
- **Payment Flow**: Customer → Modal Form → WooCommerce Checkout → Payment Complete Hook → Appointment Created
- **Race Condition Prevention**: Checks `is_time_available()` again before creating appointment to prevent double-booking

#### `Waxing_Frontend`

- **Purpose**: Frontend UI rendering
- **Key Methods**:
  - `enqueue_scripts()` - Loads CSS/JS (Air Datepicker, custom appointment.js)
  - `add_appointment_modal()` - Renders modal HTML in footer
  - `appointment_button_shortcode()` - Handles `[waxing_appointment_button]` shortcode

#### `Waxing_Admin`

- **Purpose**: WordPress admin panel integration
- **Adds**: Admin menu item "Appointments" that displays booking list

#### `Waxing_Calendar_Admin`

- **Purpose**: Standalone calendar admin dashboard (NOT WordPress admin)
- **Features**:
  - Custom route at `/calendar-admin` (no WordPress authentication required)
  - Session-based authentication with username/password
  - Block/unblock specific time slots or entire days
  - Generate Stripe payment links for services
- **Key Methods**:
  - `handle_route()` - Intercepts `/calendar-admin` URL
  - `handle_login()` - Session-based authentication
  - `handle_block_time()` / `handle_unblock_time()` - Manages blocked slots
  - `handle_block_day()` / `handle_unblock_day()` - Manages full day closures
- **Views**: Located in `includes/admin/` (dashboard, login, settings pages)

#### `Waxing_Stripe`

- **Purpose**: Stripe payment link generation (for calendar admin use)
- **Key Method**: `generate_payment_link()` - Creates Stripe payment link via API
- **Configuration**: Requires Stripe secret key in WordPress options (`waxing_stripe_secret_key`)

#### `Waxing_Twilio`

- **Purpose**: SMS notifications via Twilio API
- **Key Methods**:
  - `send_sms($to, $message)` - Send SMS to a phone number (E.164 format)
  - `normalize_phone_number($phone)` - Converts US phone numbers to E.164 format
  - `send_appointment_confirmation($appointment_data)` - Sends confirmation SMS after booking
  - `send_appointment_reminder($appointment_data)` - Sends reminder SMS (for future use)
  - `send_test_sms()` - AJAX handler for testing SMS configuration
- **Configuration**: Requires Twilio Account SID, Auth Token, and Phone Number in WordPress options
- **Auto-send**: Automatically sends confirmation SMS when appointment is created (if enabled)

### Database Schema

#### Table: `wp_waxing_appointments`

```sql
- id (primary key)
- customer_name, customer_email, customer_phone
- service_id (matches WooCommerce product SKU or sanitized name)
- appointment_date (DATE)
- appointment_time (TIME) - stored as HH:MM:SS
- total_price, deposit_paid (DECIMAL)
- status (VARCHAR) - 'pending', 'booked', 'confirmed', or 'blocked'
- order_id (WooCommerce order ID)
- created_at (DATETIME)
- UNIQUE KEY (appointment_date, appointment_time)
```

**Status Values**:

- `pending` - Appointment in cart but not yet paid
- `booked` - Deprecated, use 'confirmed'
- `confirmed` - Payment completed, appointment active
- `blocked` - Time slot manually blocked by admin (no customer data)

#### Table: `wp_waxing_payment_links`

```sql
- id (primary key)
- amount, description
- customer_name, customer_email
- payment_link (Stripe URL)
- qr_code_url
- email_sent (boolean)
- created_at (DATETIME)
```

### Time Format Handling

**Critical**: Time values are stored as `HH:MM:SS` in the database but often handled as `HH:MM` in the code. Always normalize:

```php
// When comparing or inserting
if (strlen($time) === 5) {
    $time = $time . ':00';
}

// When displaying to users
$formatted_time = date('g:i A', strtotime($time));
```

### Frontend Assets

- **CSS**: `assets/css/appointments.css` - Modal styling, button styles, loading states
- **JS**: `assets/js/appointments.js` - AJAX handlers, Air Datepicker initialization, form validation

### AJAX Endpoints

All AJAX actions use WordPress AJAX hooks:

**Public (logged in + logged out)**:

- `wp_ajax_book_appointment` / `wp_ajax_nopriv_book_appointment`
- `wp_ajax_check_availability` / `wp_ajax_nopriv_check_availability`
- `wp_ajax_calendar_admin_login` / `wp_ajax_nopriv_calendar_admin_login`
- `wp_ajax_block_calendar_time` / `wp_ajax_nopriv_block_calendar_time`
- `wp_ajax_unblock_calendar_time` / `wp_ajax_nopriv_unblock_calendar_time`
- `wp_ajax_block_calendar_day` / `wp_ajax_nopriv_block_calendar_day`
- `wp_ajax_unblock_calendar_day` / `wp_ajax_nopriv_unblock_calendar_day`
- `wp_ajax_generate_stripe_payment_link` / `wp_ajax_nopriv_generate_stripe_payment_link`
- `wp_ajax_get_payment_links_history` / `wp_ajax_nopriv_get_payment_links_history`

**Note**: Calendar admin AJAX endpoints require session authentication, not WordPress nonce.

**Twilio (admin only)**:

- `wp_ajax_send_twilio_test_sms` - Send test SMS to verify Twilio configuration

## Common Workflows

### Adding a New Service

Services are pulled from WooCommerce products. To add a service:

1. Create a new WooCommerce product (Simple Product type)
2. Set price
3. Publish product
4. Service automatically appears in booking modal dropdown

### Modifying Business Hours

Edit `Waxing_Services::get_business_hours()` in `includes/class-waxing-services.php`:

```php
// Example: Close on Saturday
if ($day_of_week == 6) {
    return null; // Closed
}
```

### Debugging Appointment Creation Issues

1. Check WooCommerce order meta for `_appointment_data` field
2. Verify `woocommerce_payment_complete` hook fired
3. Check database for duplicate entries (UNIQUE constraint may fail silently)
4. Look for error logs: `error_log('WaxingAppointments: ...')`

### Testing Calendar Admin

1. Navigate to `/calendar-admin`
2. Login with credentials from WordPress options
3. Use calendar to block/unblock slots
4. Check database `status` column for 'blocked' entries

## Security Considerations

- All user input is sanitized using WordPress functions (`sanitize_text_field()`, `sanitize_email()`)
- AJAX requests use nonce verification (except calendar admin which uses sessions)
- Database queries use `$wpdb->prepare()` for SQL injection prevention
- Calendar admin credentials stored in WordPress options (not hardcoded)

## Known Quirks

1. **Time Normalization**: Time values must be normalized between HH:MM and HH:MM:SS formats throughout the code
2. **Race Conditions**: The plugin checks availability twice - once before checkout and once after payment - to prevent double-booking
3. **Calendar Admin Authentication**: Uses PHP sessions instead of WordPress authentication to allow standalone access
4. **Deposit Calculation**: Hardcoded to 20% in `Waxing_Appointments_Handler::handle_appointment_booking()` and `Waxing_WooCommerce::set_cart_item_price()`
5. **Service Matching**: Services are matched by sanitized product name (via `sanitize_title()`), not by product ID

## Testing Checklist

When making changes, test:

- Booking flow: Select date → Select time → Fill form → Complete checkout → Verify appointment created
- Availability checking: Book a slot → Verify it disappears from available times
- Business hours: Try booking on closed days/times → Should be prevented
- Calendar admin: Block a time → Verify it's unavailable on frontend
- WooCommerce: Verify cart shows correct deposit amount (20% of product price)
- Duplicate prevention: Try booking same slot twice simultaneously

## SMS Notifications

**Configuration**: Navigate to WordPress Admin → Appointments → Calendar Settings → Twilio SMS Notifications

**Required Settings**:

- Twilio Account SID (from Twilio Console)
- Twilio Auth Token (keep secret)
- Twilio Phone Number (E.164 format: +15551234567)
- Business Name (appears in SMS messages)
- Enable checkbox (to activate notifications)

**Phone Number Format**:

- Input: US numbers can be entered as `(555) 123-4567` or `5551234567`
- Storage: Always normalized to E.164 format (`+15551234567`)
- Twilio API: Requires E.164 format for all operations

**Automatic SMS Workflow**:

1. Customer completes payment → `create_appointment_on_payment_complete()` called
2. Appointment created in database
3. If Twilio is enabled → `send_appointment_confirmation()` called automatically
4. SMS sent with appointment details (service, date, time, business name)
5. Success/failure logged to error_log

**Testing SMS**:

1. Configure Twilio settings in Calendar Settings
2. Use "Test SMS Integration" section at bottom of page
3. Enter your phone number
4. Click "Send Test SMS"
5. Verify receipt and check error messages if failed

## Common Files to Edit

- **Services/Business Logic**: `includes/class-waxing-services.php`
- **Booking Flow**: `includes/class-waxing-appointments-handler.php`
- **Payment Integration**: `includes/class-waxing-woocommerce.php`
- **SMS Notifications**: `includes/class-waxing-twilio.php`
- **Frontend Modal**: `includes/views/appointment-modal.php`
- **Frontend JavaScript**: `assets/js/appointments.js`
- **Modal Styling**: `assets/css/appointments.css`
- **Calendar Admin UI**: `includes/admin/views/calendar-dashboard.php`
- **Settings Page**: `includes/admin/views/calendar-settings-page.php`

