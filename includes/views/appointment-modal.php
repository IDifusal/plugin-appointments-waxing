<?php
/**
 * Appointment modal template
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- Loading Modal -->
<div id="waxing-loading-modal" class="modal loading-modal">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p class="loading-text">Processing your request...</p>
    </div>
</div>

<!-- Main Appointment Modal -->
<div id="waxing-appointment-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Book Your Waxing Appointment</h2>
        <form id="appointment-form">
            <div class="form-group">
                <label for="customer_name">Full Name *</label>
                <input type="text" id="customer_name" name="customer_name" required>
            </div>
            
            <div class="form-group">
                <label for="customer_email">Email *</label>
                <input type="email" id="customer_email" name="customer_email" required>
            </div>
            
            <div class="form-group">
                <label for="customer_phone">Phone *</label>
                <input type="tel" id="customer_phone" name="customer_phone" required>
            </div>
            
            <div class="form-group">
                <label for="service">Service *</label>
                <select id="service" name="service" required>
                    <option value="">Select a service...</option>
                    <?php foreach ($services as $service): ?>
                    <option value="<?php echo esc_attr($service['value']); ?>" data-price="<?php echo esc_attr($service['price']); ?>" data-product-id="<?php echo esc_attr($service['id']); ?>">
                        <?php echo esc_html($service['name']); ?> - $<?php echo number_format($service['price'], 2); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="appointment_date">Date *</label>
                <input type="text" id="appointment_date" name="appointment_date" required placeholder="Select a date..." autocomplete="off">
                <input type="hidden" id="appointment_date_value" name="appointment_date_value">
            </div>
            
            <div class="form-group">
                <label for="appointment_time">Time *</label>
                <select id="appointment_time" name="appointment_time" required>
                    <option value="">Select date first...</option>
                </select>
            </div>
            
            <div id="price-summary" style="display:none;">
                <div class="price-info">
                    <p class="service-price-display"><strong>Service Price:</strong> $<span id="total-price">0</span></p>

                    <div class="payment-option-section">
                        <label class="payment-option-label">Payment Option:</label>
                        <div class="payment-options">
                            <label class="payment-radio-option selected">
                                <input type="radio" name="payment_type" value="deposit" checked>
                                <div class="payment-option-content">
                                    <div class="payment-option-header">
                                        <span class="payment-option-title">Pay Deposit</span>
                                        <span class="payment-option-badge recommended">Recommended</span>
                                    </div>
                                    <div class="payment-option-amount">$<span id="deposit-price">0</span></div>
                                    <div class="payment-option-description">20% deposit now, rest at appointment</div>
                                </div>
                            </label>

                            <label class="payment-radio-option">
                                <input type="radio" name="payment_type" value="full">
                                <div class="payment-option-content">
                                    <div class="payment-option-header">
                                        <span class="payment-option-title">Pay in Full</span>
                                    </div>
                                    <div class="payment-option-amount">$<span id="full-price">0</span></div>
                                    <div class="payment-option-description">100% payment now</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" id="terms-checkbox-group" style="display:none;">
                <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="accept-terms" name="accept_terms" required style="width:auto;margin-top: 4px; flex-shrink: 0;">
                    <span>By booking I accept that there are no refunds and I accept the <a href="https://southbeachwaxing.com/refund_returns/" target="_blank" rel="noopener noreferrer">terms and conditions</a>.</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" id="cancel-appointment">Cancel</button>
                <button type="submit" id="book-appointment">Continue to Payment</button>
            </div>
        </form>
    </div>
</div>

