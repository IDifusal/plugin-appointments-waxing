# Waxing Appointments Plugin

A simple WordPress plugin for waxing companies to manage appointment bookings with WooCommerce integration.

## Features

- **Shortcode Button**: Flexible appointment button placement via `[waxing_appointment_button]`
- **Beautiful Date Picker**: Air Datepicker integration for intuitive date selection
- **Modal Popup Form**: Clean, responsive form for booking appointments
- **Service Selection**: Pre-defined waxing services with pricing (easily customizable)
- **Real-time Availability**: Live time slot checking when date is selected
- **40% Deposit System**: Automatic calculation and payment through WooCommerce
- **Admin Panel**: View and manage all appointments
- **Database Integration**: Secure storage of appointments and availability

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure WooCommerce is installed and activated
4. The plugin will automatically create necessary database tables and the appointment product

## Configuration

### Services Array

The services are defined in the main plugin file. To modify services, edit the `$service_prices` array in `waxing-appointments.php:241`:

```php
$service_prices = array(
    'eyebrow_wax' => 25,
    'upper_lip' => 15,
    'full_leg' => 80,
    'half_leg' => 45,
    'bikini' => 35,
    'brazilian' => 65
);
```

And update the corresponding options in the HTML form at `waxing-appointments.php:119-126`.

### Availability

The plugin creates availability slots for:
- **Days**: Monday to Friday (weekends excluded)
- **Times**: 9:00 AM to 5:00 PM (hourly slots)
- **Duration**: 60 days from activation

To modify default availability, edit the `populate_default_availability()` method.

## Usage

### Shortcode

Use the shortcode `[waxing_appointment_button]` to display the booking button anywhere on your site:

**Basic Usage:**
```
[waxing_appointment_button]
```

**With Custom Text:**
```
[waxing_appointment_button text="Schedule Now"]
```

**With Custom Styling:**
```
[waxing_appointment_button style="inline" text="Book Today" class="my-custom-class"]
```

**Available Parameters:**
- `text` - Button text (default: "Book Appointment")
- `style` - Button style: `default`, `inline`, or `custom`
- `class` - Additional CSS classes

### For Customers

1. Click the "Book Appointment" button (placed via shortcode)
2. Fill out the form with personal information
3. Select desired service
4. **Use the beautiful date picker** to select an appointment date
   - Click the date field to open Air Datepicker
   - Weekends are automatically disabled (Mon-Fri only)
   - Cannot select past dates or dates beyond 60 days
   - Modern, mobile-friendly interface
5. Choose from available time slots (automatically loaded)
6. Review pricing (40% deposit required)
7. Complete payment through WooCommerce checkout

### For Administrators

1. Go to WordPress Admin → Appointments
2. View all bookings with customer details
3. Monitor appointment status and payments
4. Export data as needed

## File Structure

```
waxing-appointments/
├── waxing-appointments.php    # Main plugin file
├── assets/
│   ├── css/
│   │   └── appointments.css   # Styling for modal and form
│   └── js/
│       └── appointments.js    # Frontend functionality
└── README.md                  # This file
```

## Database Tables

The plugin creates two tables:

- `wp_waxing_appointments`: Stores appointment details
- `wp_waxing_availability`: Manages time slot availability

## Customization

### Styling

Edit `assets/css/appointments.css` to customize:
- Button appearance and position
- Modal design and colors
- Form styling
- Responsive behavior

### Services

1. Update the service array in PHP (line 241)
2. Update the HTML options (lines 119-126)
3. Restart to ensure consistency

### Availability Rules

Modify the `populate_default_availability()` method to change:
- Operating days
- Time slots
- Advance booking period

## Security Features

- AJAX nonce verification
- Input sanitization
- SQL injection prevention
- XSS protection

## Support

This plugin is designed to be simple and maintainable. For customizations:

1. Always backup before making changes
2. Test changes in a staging environment
3. Follow WordPress coding standards
4. Maintain database consistency

## License

GPL v2 or later