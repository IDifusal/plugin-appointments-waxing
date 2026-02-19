<?php
/**
 * Calendar dashboard view template
 *
 * @package WaxingAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
        <html>
        <head>
            <title>Calendar Admin Dashboard</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    background: #f6f7f9;
                    line-height: 1.5;
                }
                
                /* Mobile responsive styles */
                @media (max-width: 768px) {
                    body {
                        padding: 10px;
                    }
                }
                .header { 
                    background: white; 
                    padding: 25px; 
                    margin-bottom: 25px; 
                    border-radius: 12px; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center;
                    border: 1px solid #e1e5e9;
                }
                
                @media (max-width: 768px) {
                    .header {
                        flex-direction: column;
                        gap: 15px;
                        padding: 20px;
                        text-align: center;
                    }
                    .header h1 {
                        font-size: 1.5rem;
                    }
                    .logout-btn {
                        width: 100%;
                        text-align: center;
                    }
                }
                .header h1 {
                    margin: 0;
                    color: #1a202c;
                    font-weight: 600;
                }
                .logout-btn { 
                    background: #e53e3e; 
                    color: white; 
                    padding: 10px 20px; 
                    text-decoration: none; 
                    border-radius: 8px;
                    font-weight: 500;
                    transition: background 0.2s;
                }
                .logout-btn:hover {
                    background: #c53030;
                }
                .calendar-container { 
                    background: white; 
                    padding: 25px; 
                    border-radius: 12px; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    border: 1px solid #e1e5e9;
                    margin-bottom: 25px;
                }
                
                @media (max-width: 768px) {
                    .calendar-container {
                        padding: 15px;
                        overflow-x: auto;
                    }
                }
                #calendar {
                    max-width: 100%;
                    margin: 0 auto;
                }
                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin-bottom: 25px;
                }
                
                @media (max-width: 768px) {
                    .stats-grid {
                        grid-template-columns: 1fr;
                        gap: 15px;
                    }
                }
                .stat-card {
                    background: white;
                    padding: 20px;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    border: 1px solid #e1e5e9;
                    text-align: center;
                }
                .stat-number {
                    font-size: 2.5rem;
                    font-weight: 700;
                    color: #0073aa;
                    margin: 0;
                }
                .stat-label {
                    color: #718096;
                    margin-top: 5px;
                    font-weight: 500;
                }
                .legend {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                }
                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .legend-color {
                    width: 16px;
                    height: 16px;
                    border-radius: 4px;
                }
                .legend-color.appointment {
                    background: #0073aa;
                }
                .legend-color.blocked {
                    background: #d63638;
                }
                .legend-color.available {
                    background: #00a32a;
                }
                .quick-actions {
                    background: white;
                    padding: 25px;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    border: 1px solid #e1e5e9;
                }
                
                @media (max-width: 768px) {
                    .quick-actions {
                        padding: 15px;
                    }
                }
                .quick-actions h3 {
                    margin-top: 0;
                    color: #1a202c;
                }
                .time-slot-selector {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                    gap: 10px;
                    margin: 20px 0;
                }
                .time-slot-btn {
                    padding: 10px;
                    border: 2px solid #e2e8f0;
                    background: white;
                    border-radius: 8px;
                    cursor: pointer;
                    text-align: center;
                    transition: all 0.2s;
                    font-size: 14px;
                }
                
                @media (max-width: 768px) {
                    .time-slot-selector {
                        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                        gap: 8px;
                    }
                    .time-slot-btn {
                        padding: 12px 8px;
                        font-size: 13px;
                    }
                }
                .time-slot-btn:hover {
                    border-color: #0073aa;
                    background: #f7fafc;
                }
                .time-slot-btn.blocked {
                    background: #fed7d7;
                    border-color: #fc8181;
                    color: #c53030;
                }
                /* FullCalendar customizations */
                .fc-event-title {
                    font-weight: 500;
                }
                .fc-toolbar {
                    margin-bottom: 20px !important;
                }
                .fc-button {
                    background: #0073aa !important;
                    border-color: #0073aa !important;
                }
                .fc-button:hover {
                    background: #005a87 !important;
                    border-color: #005a87 !important;
                }
                .fc-today-button {
                    background: #00a32a !important;
                    border-color: #00a32a !important;
                }
                
                @media (max-width: 768px) {
                    .fc-toolbar {
                        flex-direction: column;
                        gap: 10px;
                    }
                    .fc-toolbar-chunk {
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: center;
                        gap: 5px;
                        width: 100%;
                    }
                    .fc-toolbar-chunk:first-child,
                    .fc-toolbar-chunk:last-child {
                        order: 2;
                    }
                    .fc-toolbar-chunk:nth-child(2) {
                        order: 1;
                        width: 100%;
                    }
                    .fc-button {
                        padding: 10px 14px !important;
                        font-size: 13px !important;
                        min-width: 44px;
                        min-height: 44px;
                    }
                    .fc-toolbar-title {
                        font-size: 1.1em !important;
                        margin: 10px 0 !important;
                        text-align: center;
                    }
                    .fc-event-title {
                        font-size: 11px !important;
                    }
                    .fc-timegrid-slot {
                        font-size: 11px !important;
                    }
                    .fc-col-header-cell {
                        font-size: 11px !important;
                        padding: 8px 4px !important;
                    }
                    .fc-daygrid-day-number {
                        font-size: 12px !important;
                        padding: 4px !important;
                    }
                    .fc-event {
                        font-size: 11px !important;
                        padding: 2px 4px !important;
                    }
                    .fc-daygrid-event {
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                }
                
                @media (max-width: 480px) {
                    .stat-number {
                        font-size: 2rem;
                    }
                    .legend {
                        flex-direction: column;
                        gap: 10px;
                    }
                    .form-group input,
                    .form-group textarea,
                    .generate-btn {
                        font-size: 16px; /* Prevents zoom on iOS */
                    }
                }
                
                /* Selection styling */
                .fc-highlight {
                    background: rgba(0, 115, 170, 0.3) !important;
                    border: 2px dashed #0073aa !important;
                }
                .fc-select-mirror {
                    background: rgba(0, 115, 170, 0.2) !important;
                    border: 2px solid #0073aa !important;
                    color: #0073aa !important;
                    font-weight: bold;
                }
                
                /* Cursor changes for better UX */
                .fc-timegrid-slot {
                    cursor: crosshair;
                }
                .fc-daygrid-day {
                    cursor: pointer;
                }
                
                /* Selection instructions */
                .selection-instructions {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    text-align: center;
                    font-weight: 500;
                }
                .selection-instructions strong {
                    color: #ffd700;
                }
                
                @media (max-width: 768px) {
                    .selection-instructions {
                        padding: 12px;
                        font-size: 13px;
                        line-height: 1.4;
                    }
                }
                
                /* Tabs styling */
                .tabs-container {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                    border: 1px solid #e1e5e9;
                    margin-bottom: 25px;
                }
                .tabs-nav {
                    display: flex;
                    border-bottom: 2px solid #e1e5e9;
                    padding: 0;
                    margin: 0;
                    list-style: none;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                .tabs-nav li {
                    margin: 0;
                    flex-shrink: 0;
                }
                .tabs-nav button {
                    background: none;
                    border: none;
                    padding: 15px 25px;
                    cursor: pointer;
                    font-size: 16px;
                    font-weight: 500;
                    color: #718096;
                    transition: all 0.2s;
                    border-bottom: 3px solid transparent;
                    margin-bottom: -2px;
                    white-space: nowrap;
                }
                
                @media (max-width: 768px) {
                    .tabs-nav {
                        overflow-x: auto;
                        scrollbar-width: thin;
                    }
                    .tabs-nav button {
                        padding: 12px 20px;
                        font-size: 14px;
                        min-width: 120px;
                    }
                }
                .tabs-nav button:hover {
                    color: #0073aa;
                    background: #f7fafc;
                }
                .tabs-nav button.active {
                    color: #0073aa;
                    border-bottom-color: #0073aa;
                }
                .tab-content {
                    display: none;
                    padding: 25px;
                }
                .tab-content.active {
                    display: block;
                }
                
                @media (max-width: 768px) {
                    .tab-content {
                        padding: 15px;
                    }
                }
                
                /* Invoice generator styles */
                .invoice-form {
                    max-width: 600px;
                    margin: 0 auto;
                }
                
                @media (max-width: 768px) {
                    .invoice-form {
                        max-width: 100%;
                    }
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 500;
                    color: #1a202c;
                }
                .form-group input,
                .form-group textarea {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    font-size: 14px;
                    box-sizing: border-box;
                }
                .form-group textarea {
                    min-height: 100px;
                    resize: vertical;
                }
                .generate-btn {
                    background: #0073aa;
                    color: white;
                    padding: 12px 30px;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background 0.2s;
                    width: 100%;
                }
                .generate-btn:hover {
                    background: #005a87;
                }
                .generate-btn:disabled {
                    background: #cbd5e0;
                    cursor: not-allowed;
                }
                .result-container {
                    margin-top: 30px;
                    padding: 20px;
                    background: #f7fafc;
                    border-radius: 8px;
                    border: 1px solid #e2e8f0;
                    display: none;
                }
                .result-container.show {
                    display: block;
                }
                .payment-link {
                    word-break: break-all;
                    background: white;
                    padding: 15px;
                    border-radius: 6px;
                    margin: 15px 0;
                    border: 1px solid #e2e8f0;
                }
                .payment-link a {
                    color: #0073aa;
                    text-decoration: none;
                    font-weight: 500;
                }
                .payment-link a:hover {
                    text-decoration: underline;
                }
                .qr-code-container {
                    text-align: center;
                    margin: 20px 0;
                }
                .qr-code-container img {
                    max-width: 250px;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 10px;
                    background: white;
                }
                .copy-btn {
                    background: #00a32a;
                    color: white;
                    padding: 8px 15px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    margin-top: 10px;
                    font-size: 14px;
                }
                .copy-btn:hover {
                    background: #008a20;
                }
                
                @media (max-width: 768px) {
                    .copy-btn {
                        width: 100%;
                        padding: 12px;
                        font-size: 16px;
                    }
                    .qr-code-container img {
                        max-width: 200px;
                    }
                    .payment-link {
                        padding: 12px;
                        font-size: 12px;
                    }
                    .result-container {
                        padding: 15px;
                    }
                }
                .error-message {
                    background: #fed7d7;
                    color: #c53030;
                    padding: 15px;
                    border-radius: 8px;
                    margin-top: 20px;
                    border: 1px solid #fc8181;
                }
                .success-message {
                    background: #c6f6d5;
                    color: #22543d;
                    padding: 15px;
                    border-radius: 8px;
                    margin-top: 20px;
                    border: 1px solid #9ae6b4;
                }
                .day-block-btn {
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    padding: 4px 8px;
                    font-size: 11px;
                    background: #d63638;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    z-index: 10;
                    opacity: 0.8;
                    transition: opacity 0.2s;
                }
                .day-block-btn:hover {
                    opacity: 1;
                    background: #c53030;
                }
                .day-block-btn.blocked {
                    background: #00a32a;
                }
                .day-block-btn.blocked:hover {
                    background: #008a20;
                }
                
                @media (max-width: 768px) {
                    .day-block-btn {
                        padding: 6px 10px;
                        font-size: 12px;
                        min-width: 44px;
                        min-height: 44px;
                    }
                    .week-view-btn {
                        padding: 10px;
                        font-size: 13px;
                        min-width: 44px;
                        min-height: 44px;
                    }
                }
                .fc-daygrid-day-frame {
                    position: relative;
                }
                .week-view-btn {
                    font-size: 12px;
                    padding: 6px 12px;
                    width: 100%;
                    max-width: 150px;
                    margin: 0 auto;
                    display: block;
                }
                .fc-col-header-cell {
                    position: relative;
                }
                .fc-col-header-cell .day-block-btn {
                    position: static;
                    margin-top: 8px;
                }
                @media (max-width: 768px) {
                    .stats-grid { 
                        grid-template-columns: 1fr; 
                    }
                    .legend {
                        justify-content: center;
                    }
                    .header {
                        flex-direction: column;
                        gap: 15px;
                        text-align: center;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📅 Calendar Admin Dashboard</h1>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($appointments); ?></div>
                    <div class="stat-label">Upcoming Appointments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($blocked_slots); ?></div>
                    <div class="stat-label">Blocked Time Slots</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php 
                        $total_revenue = 0;
                        foreach ($appointments as $appointment) {
                            $total_revenue += $appointment->deposit_paid;
                        }
                        echo number_format($total_revenue, 2);
                    ?></div>
                    <div class="stat-label">Total Deposits</div>
                </div>
            </div>
            
            <!-- Tabs Container -->
            <div class="tabs-container">
                <ul class="tabs-nav">
                    <li><button class="tab-btn active" data-tab="calendar">📅 Calendar</button></li>
                    <li><button class="tab-btn" data-tab="invoices">💳 Invoice Generator</button></li>
                </ul>
                
                <!-- Calendar Tab -->
                <div id="tab-calendar" class="tab-content active">
                    <!-- Selection Instructions -->
                    <div class="selection-instructions">
                        <strong>🎯 How to use the calendar:</strong> 
                        <strong>DRAG</strong> to select multiple hours | 
                        <strong>CLICK</strong> events for details | 
                        Switch views with the buttons above
                    </div>
                    
                    <!-- Calendar Container -->
                    <div class="calendar-container">
                        <div class="legend">
                            <div class="legend-item">
                                <div class="legend-color appointment"></div>
                                <span>Appointments</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color blocked"></div>
                                <span>Blocked Times</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color available"></div>
                                <span>Available</span>
                            </div>
                        </div>
                        
                        <div id="calendar"></div>
                    </div>
                    
                    <!-- Quick Actions Panel -->
                    <div class="quick-actions">
                        <h3>🚀 Calendar Management Guide</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #0073aa;">
                                <h4 style="margin: 0 0 10px 0; color: #0073aa;">🖱️ Block Multiple Hours</h4>
                                <ol style="margin: 0; padding-left: 20px;">
                                    <li>Switch to <strong>Week</strong> or <strong>Day</strong> view</li>
                                    <li><strong>Click and drag</strong> across time slots</li>
                                    <li>Confirm to block the selected range</li>
                                </ol>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #d63638;">
                                <h4 style="margin: 0 0 10px 0; color: #d63638;">🔓 Manage Individual Slots</h4>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <li><strong>Click</strong> red blocks to unblock</li>
                                    <li><strong>Click</strong> empty slots to block</li>
                                    <li><strong>Click</strong> blue appointments for details</li>
                                </ul>
                            </div>
                        </div>
                        
                        
                        <div id="time-slots-container" style="display: none; margin-top: 20px;">
                            <div class="time-slot-selector" id="time-slots"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Invoice Generator Tab -->
                <div id="tab-invoices" class="tab-content">
                    <div class="invoice-form">
                        <h2 style="margin-top: 0; color: #1a202c;">💳 Generate Payment Link</h2>
                        <p style="color: #718096; margin-bottom: 25px;">Create a Stripe payment link that customers can use to pay. You can share the link or QR code.</p>
                        
                        <form id="invoice-form">
                            <div class="form-group">
                                <label for="invoice-amount">Amount (USD) *</label>
                                <input type="number" id="invoice-amount" name="amount" step="0.01" min="0.50" required placeholder="0.00">
                            </div>
                            
                            <div class="form-group">
                                <label for="invoice-description">Description</label>
                                <input type="text" id="invoice-description" name="description" placeholder="e.g., Waxing Service Payment">
                            </div>
                            
                            <div class="form-group">
                                <label for="invoice-customer-name">Customer Name (Optional)</label>
                                <input type="text" id="invoice-customer-name" name="customer_name" placeholder="Customer name">
                            </div>
                            
                            <div class="form-group">
                                <label for="invoice-customer-email">Customer Email (Optional)</label>
                                <input type="email" id="invoice-customer-email" name="customer_email" placeholder="customer@example.com">
                            </div>
                            
                            <button type="submit" class="generate-btn" id="generate-btn">
                                Generate Payment Link
                            </button>
                        </form>
                        
                        <div id="result-container" class="result-container">
                            <h3 style="margin-top: 0; color: #1a202c;">✅ Payment Link Generated</h3>
                            
                            <div class="payment-link">
                                <strong>Payment Link:</strong><br>
                                <a href="" id="payment-link-url" target="_blank"></a>
                                <button class="copy-btn" onclick="copyPaymentLink(event)">Copy Link</button>
                            </div>
                            
                            <div class="qr-code-container">
                                <strong>QR Code:</strong><br>
                                <img id="qr-code-image" src="" alt="QR Code">
                            </div>
                        </div>
                        
                        <div id="error-container" class="error-message" style="display: none;"></div>
                    </div>
                </div>
            </div>
            
            <script>
            // Tab switching functionality
            document.addEventListener('DOMContentLoaded', function() {
                // Tab switching
                const tabButtons = document.querySelectorAll('.tab-btn');
                const tabContents = document.querySelectorAll('.tab-content');
                
                tabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const targetTab = this.getAttribute('data-tab');
                        
                        // Remove active class from all buttons and contents
                        tabButtons.forEach(btn => btn.classList.remove('active'));
                        tabContents.forEach(content => content.classList.remove('active'));
                        
                        // Add active class to clicked button and corresponding content
                        this.classList.add('active');
                        document.getElementById('tab-' + targetTab).classList.add('active');
                    });
                });
                
                // Invoice form submission
                const invoiceForm = document.getElementById('invoice-form');
                if (invoiceForm) {
                    invoiceForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        generatePaymentLink();
                    });
                }
            });
            
            function generatePaymentLink() {
                const amount = document.getElementById('invoice-amount').value;
                const description = document.getElementById('invoice-description').value;
                const customerName = document.getElementById('invoice-customer-name').value;
                const customerEmail = document.getElementById('invoice-customer-email').value;
                const generateBtn = document.getElementById('generate-btn');
                const resultContainer = document.getElementById('result-container');
                const errorContainer = document.getElementById('error-container');
                
                // Validate amount
                if (!amount || parseFloat(amount) < 0.50) {
                    errorContainer.style.display = 'block';
                    errorContainer.textContent = 'Please enter a valid amount (minimum $0.50)';
                    resultContainer.classList.remove('show');
                    return;
                }
                
                // Show loading state
                generateBtn.disabled = true;
                generateBtn.textContent = 'Generating...';
                errorContainer.style.display = 'none';
                resultContainer.classList.remove('show');
                
                // Make AJAX request
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'generate_stripe_payment_link',
                        amount: amount,
                        description: description,
                        customer_name: customerName,
                        customer_email: customerEmail,
                        nonce: '<?php echo wp_create_nonce('waxing_appointments_nonce'); ?>'
                    },
                    success: function(response) {
                        generateBtn.disabled = false;
                        generateBtn.textContent = 'Generate Payment Link';
                        
                        if (response.success) {
                            const paymentLink = response.data.payment_link;
                            const qrCodeUrl = response.data.qr_code;
                            const emailSent = response.data.email_sent || 0;
                            
                            // Display results
                            document.getElementById('payment-link-url').href = paymentLink;
                            document.getElementById('payment-link-url').textContent = paymentLink;
                            document.getElementById('qr-code-image').src = qrCodeUrl;
                            
                            resultContainer.classList.add('show');
                            errorContainer.style.display = 'none';
                            
                            // Show email sent message if email was sent
                            if (emailSent && customerEmail) {
                                const emailMsg = document.createElement('div');
                                emailMsg.style.cssText = 'background: #c6f6d5; color: #22543d; padding: 12px; border-radius: 6px; margin-top: 15px; border: 1px solid #9ae6b4;';
                                emailMsg.textContent = '✅ Payment link sent to ' + customerEmail;
                                resultContainer.querySelector('.payment-link').appendChild(emailMsg);
                            }
                            
                            // Scroll to results
                            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        } else {
                            errorContainer.style.display = 'block';
                            errorContainer.textContent = 'Error: ' + (response.data || 'Failed to generate payment link');
                            resultContainer.classList.remove('show');
                        }
                    },
                    error: function(xhr, status, error) {
                        generateBtn.disabled = false;
                        generateBtn.textContent = 'Generate Payment Link';
                        errorContainer.style.display = 'block';
                        errorContainer.textContent = 'Network error: ' + error;
                        resultContainer.classList.remove('show');
                    }
                });
            }
            
            function copyPaymentLink(event) {
                // Get the payment link URL from the anchor element
                const linkElement = document.getElementById('payment-link-url');
                const link = linkElement.href || linkElement.textContent;
                
                // Use Clipboard API if available, otherwise fallback
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(link).then(function() {
                        const btn = event ? event.target : document.querySelector('.copy-btn');
                        const originalText = btn.textContent;
                        btn.textContent = 'Copied!';
                        btn.style.background = '#00a32a';
                        setTimeout(function() {
                            btn.textContent = originalText;
                            btn.style.background = '';
                        }, 2000);
                    }).catch(function(err) {
                        // Fallback for older browsers
                        fallbackCopyTextToClipboard(link, event);
                    });
                } else {
                    // Fallback for browsers without Clipboard API
                    fallbackCopyTextToClipboard(link, event);
                }
            }
            
            function fallbackCopyTextToClipboard(text, event) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        const btn = event ? event.target : document.querySelector('.copy-btn');
                        const originalText = btn.textContent;
                        btn.textContent = 'Copied!';
                        btn.style.background = '#00a32a';
                        setTimeout(function() {
                            btn.textContent = originalText;
                            btn.style.background = '';
                        }, 2000);
                    } else {
                        alert('Failed to copy link. Please copy manually: ' + text);
                    }
                } catch (err) {
                    alert('Failed to copy link. Please copy manually: ' + text);
                }
                
                document.body.removeChild(textArea);
            }
            
            // Days that are fully blocked
            var fullyBlockedDays = <?php echo json_encode($fully_blocked_days); ?>;
            
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                
                // Detect mobile and set appropriate initial view
                var isMobile = window.innerWidth <= 768;
                var initialView = isMobile ? 'dayGridMonth' : 'timeGridWeek';
                
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: initialView, // Start with week view for desktop, month for mobile
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: isMobile ? 'dayGridMonth,timeGridDay' : 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    height: 'auto',
                    events: <?php echo json_encode($calendar_events); ?>,
                    navLinks: true,
                    // Enable date/time selection
                    selectable: true,
                    selectMirror: true,
                    selectOverlap: false, // Don't allow selection over existing events
                    unselectAuto: true,
                    selectConstraint: {
                        daysOfWeek: [1, 2, 3, 4, 5, 6], // Monday to Saturday
                        startTime: '09:00',
                        endTime: '19:00'
                    },
                    
                    // Selection callback - triggered when user drags to select time range
                    select: function(selectionInfo) {
                        var startDate = selectionInfo.start;
                        var endDate = selectionInfo.end;
                        
                        // Calculate how many time slots are selected
                        var duration = (endDate - startDate) / (1000 * 60 * 60); // duration in hours
                        var slotsCount = Math.ceil(duration);
                        
                        var action = confirm(
                            'You have selected ' + slotsCount + ' hour(s) from:\n' +
                            startDate.toLocaleString() + ' to ' + endDate.toLocaleString() + '\n\n' +
                            'What would you like to do?\n\n' +
                            'OK = Block these time slots\n' +
                            'Cancel = Clear selection'
                        );
                        
                        if (action) {
                            blockTimeRange(startDate, endDate);
                        }
                        
                        // Clear the selection
                        calendar.unselect();
                    },
                    
                    eventClick: function(info) {
                        var event = info.event;
                        var extendedProps = event.extendedProps;
                        
                        if (extendedProps.type === 'appointment') {
                            alert('📅 Appointment Details:\n\n' +
                                '👤 Customer: ' + extendedProps.customer_name + '\n' +
                                '📧 Email: ' + extendedProps.customer_email + '\n' +
                                '📞 Phone: ' + extendedProps.customer_phone + '\n' +
                                '💄 Service: ' + extendedProps.service + '\n' +
                                '📊 Status: ' + extendedProps.status + '\n' +
                                '💰 Total: $' + extendedProps.total_price + '\n' +
                                '💳 Deposit: $' + extendedProps.deposit_paid);
                        } else if (extendedProps.type === 'blocked') {
                            if (confirm('🚫 This time slot is currently BLOCKED.\n\nDo you want to UNBLOCK it?')) {
                                unblockTimeSlot(extendedProps.date, extendedProps.time);
                            }
                        }
                    },
                    
                    dateClick: function(info) {
                        if (info.view.type === 'dayGridMonth') {
                        calendar.changeView('timeGridWeek', info.dateStr);
                        }
                    },
                    
                    dayCellContent: function(info) {
                        // Only show button in month view
                        if (info.view.type === 'dayGridMonth') {
                            var dateStr = info.date.toISOString().split('T')[0];
                            var dayOfWeek = info.date.getDay();
                            
                            // Don't show button for Sunday (closed)
                            if (dayOfWeek === 0) {
                                return { html: info.dayNumberText };
                            }
                            
                            // Check if day is fully blocked
                            var isBlocked = fullyBlockedDays.indexOf(dateStr) !== -1;
                            
                            var buttonHtml = '<button class="day-block-btn ' + (isBlocked ? 'blocked' : '') + '" ' +
                                'data-date="' + dateStr + '" ' +
                                'onclick="event.stopPropagation(); toggleDayBlock(\'' + dateStr + '\', this);" ' +
                                'title="' + (isBlocked ? 'Desbloquear día' : 'Bloquear día completo') + '">' +
                                (isBlocked ? '✓' : '🚫') +
                                '</button>';
                            
                            return { html: info.dayNumberText + buttonHtml };
                        }
                        return { html: info.dayNumberText };
                    },
                    
                    dayHeaderContent: function(info) {
                        // Show button in week and day views
                        if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                            var dateStr = info.date.toISOString().split('T')[0];
                            var dayOfWeek = info.date.getDay();
                            
                            // Don't show button for Sunday (closed)
                            if (dayOfWeek === 0) {
                                return { html: info.text };
                            }
                            
                            // Check if day is fully blocked
                            var isBlocked = fullyBlockedDays.indexOf(dateStr) !== -1;
                            
                            var buttonHtml = '<button class="day-block-btn week-view-btn ' + (isBlocked ? 'blocked' : '') + '" ' +
                                'data-date="' + dateStr + '" ' +
                                'onclick="event.stopPropagation(); toggleDayBlock(\'' + dateStr + '\', this);" ' +
                                'title="' + (isBlocked ? 'Desbloquear día' : 'Bloquear día completo') + '">' +
                                (isBlocked ? '✓ Bloqueado' : '🚫 Bloquear día') +
                                '</button>';
                            
                            return { html: info.text + '<div style="margin-top: 5px;">' + buttonHtml + '</div>' };
                        }
                        return { html: info.text };
                    },
                    
                    businessHours: [
                        {
                            daysOfWeek: [1, 2, 3], // Monday - Wednesday
                            startTime: '10:00',
                            endTime: '17:00'
                        },
                        {
                            daysOfWeek: [4], // Thursday
                            startTime: '10:00',
                            endTime: '19:00'
                        },
                        {
                            daysOfWeek: [5], // Friday
                            startTime: '10:00',
                            endTime: '17:00'
                        },
                        {
                            daysOfWeek: [6], // Saturday
                            startTime: '09:00',
                            endTime: '14:00'
                        }
                    ],
                    slotMinTime: '09:00:00',
                    slotMaxTime: '19:00:00',
                    slotDuration: '01:00:00', // 1 hour slots
                    snapDuration: '01:00:00' // Snap to 1 hour intervals
                });
                
                calendar.render();
                window.waxingCalendar = calendar;
            });
            
            function showBlockLoader(show = true) {
                let loader = document.getElementById('block-loader-overlay');
                if (!loader) {
                    loader = document.createElement('div');
                    loader.id = 'block-loader-overlay';
                    loader.style.position = 'fixed';
                    loader.style.top = '0';
                    loader.style.left = '0';
                    loader.style.width = '100vw';
                    loader.style.height = '100vh';
                    loader.style.background = 'rgba(255,255,255,0.7)';
                    loader.style.zIndex = '9999';
                    loader.style.display = 'flex';
                    loader.style.alignItems = 'center';
                    loader.style.justifyContent = 'center';
                    loader.innerHTML = '<div style="padding:30px;background:#fff;border-radius:8px;box-shadow:0 2px 8px #ccc;"><span class="loader-spinner" style="display:inline-block;width:32px;height:32px;border:4px solid #ccc;border-top:4px solid #333;border-radius:50%;animation:spin 1s linear infinite;"></span> <span style="margin-left:12px;vertical-align:middle;">Procesando bloqueo...</span></div>';
                    document.body.appendChild(loader);
                    // Spinner animation
                    const style = document.createElement('style');
                    style.innerHTML = '@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}';
                    document.head.appendChild(style);
                }
                loader.style.display = show ? 'flex' : 'none';
            }

                function getSlotsBetween(startDate, endDate) {
                    // Accept Date objects or ISO date strings. Use local date components to avoid timezone shifts.
                    var start = (typeof startDate === 'string') ? new Date(startDate) : new Date(startDate);
                    var end = (typeof endDate === 'string') ? new Date(endDate) : new Date(endDate);
                    var slots = [];
                    start.setMinutes(0,0,0,0);
                    var cur = new Date(start);
                    function pad(n){return n<10? '0'+n : ''+n}
                    while (cur < end) {
                        var isoDate = cur.getFullYear() + '-' + pad(cur.getMonth()+1) + '-' + pad(cur.getDate());
                        var hhmm = pad(cur.getHours()) + ':' + pad(cur.getMinutes());
                        slots.push({ date: isoDate, time: hhmm + ':00' });
                        cur.setHours(cur.getHours() + 1, 0, 0, 0);
                    }
                    return slots;
                }

            function addBlockedEventToCalendar(date, time) {
                try {
                    if (!window.waxingCalendar) return;
                    var parts = time.split(':');
                    var h = parseInt(parts[0],10);
                    var m = parts[1];
                    var start = date + 'T' + time;
                    var endHour = (h + 1) % 24;
                    var end = date + 'T' + (endHour<10? '0'+endHour : endHour) + ':' + m + ':00';
                    var eventId = 'blocked_' + date + '_' + time.replace(/:/g,'');
                    window.waxingCalendar.addEvent({
                        id: eventId,
                        title: 'Blocked',
                        start: start,
                        end: end,
                        backgroundColor: '#d63638',
                        borderColor: '#a02622',
                        extendedProps: { type: 'blocked', date: date, time: time }
                    });
                } catch (e) {
                    console.error('Failed to add blocked event to calendar', e);
                }
            }

            function blockTimeSlot(date, time) {
                // Normalize time format to HH:MM:SS
                if (time.length === 5) {
                    time = time + ':00';
                }
                
                showBlockLoader(true);
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'block_calendar_time',
                        date: date,
                        time: time,
                        nonce: '<?php echo wp_create_nonce('waxing_appointments_nonce'); ?>'
                    },
                    success: function(response) {
                        showBlockLoader(false);
                        if (response.success) {
                            // Reload calendar events to show the blocked slot
                            if (window.waxingCalendar) {
                                try {
                                    var refetchPromise = window.waxingCalendar.refetchEvents();
                                    if (refetchPromise && typeof refetchPromise.then === 'function') {
                                        refetchPromise.then(function() {
                                            window.waxingCalendar.render();
                                        }).catch(function() {
                                            window.waxingCalendar.render();
                                        });
                                    } else {
                                        setTimeout(function() {
                                            window.waxingCalendar.render();
                                        }, 300);
                                    }
                                } catch (e) {
                                    console.error('Error refetching events:', e);
                                    window.waxingCalendar.render();
                                }
                            }
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        showBlockLoader(false);
                        alert('Network error. Please try again.');
                    }
                });
            }
            
            function blockTimeRange(startDate, endDate) {
                showBlockLoader(true);
                var slots = getSlotsBetween(startDate, endDate); // returns [{date: 'YYYY-MM-DD', time: 'HH:MM:SS'}, ...]
                var pending = slots.length;
                var anyErrors = false;

                if (pending === 0) {
                    showBlockLoader(false);
                    return;
                }

                slots.forEach(function(slot) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'block_calendar_time',
                            date: slot.date,
                            time: slot.time,
                            nonce: '<?php echo wp_create_nonce('waxing_appointments_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                addBlockedEventToCalendar(slot.date, slot.time);
                            } else {
                                anyErrors = true;
                                console.warn('Failed to block slot', slot, response.data);
                            }
                        },
                        error: function() {
                            anyErrors = true;
                            console.error('Network error blocking slot', slot);
                        },
                        complete: function() {
                            pending--;
                            if (pending <= 0) {
                                showBlockLoader(false);
                                
                                // Reload calendar events after all slots are blocked
                                if (window.waxingCalendar) {
                                    try {
                                        var refetchPromise = window.waxingCalendar.refetchEvents();
                                        if (refetchPromise && typeof refetchPromise.then === 'function') {
                                            refetchPromise.then(function() {
                                                window.waxingCalendar.render();
                                            }).catch(function() {
                                                window.waxingCalendar.render();
                                            });
                                        } else {
                                            setTimeout(function() {
                                                window.waxingCalendar.render();
                                            }, 300);
                                        }
                                    } catch (e) {
                                        console.error('Error refetching events:', e);
                                        window.waxingCalendar.render();
                                    }
                                }
                                
                                if (anyErrors) {
                                    alert('Algunos horarios no pudieron ser bloqueados. Revisa la consola para más detalles.');
                                }
                            }
                        }
                    });
                });
            }

            function unblockTimeSlot(date, time) {
                showBlockLoader(true);
                var normalized = (typeof time === 'string' && time.length === 5) ? time + ':00' : time;
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'unblock_calendar_time',
                        date: date,
                        time: normalized,
                        nonce: '<?php echo wp_create_nonce('waxing_appointments_nonce'); ?>'
                    },
                    success: function(response) {
                        showBlockLoader(false);
                        if (response.success) {
                            // Reload calendar events to remove the blocked slot
                            if (window.waxingCalendar) {
                                try {
                                    var refetchPromise = window.waxingCalendar.refetchEvents();
                                    if (refetchPromise && typeof refetchPromise.then === 'function') {
                                        refetchPromise.then(function() {
                                            window.waxingCalendar.render();
                                        }).catch(function() {
                                            window.waxingCalendar.render();
                                        });
                                    } else {
                                        setTimeout(function() {
                                            window.waxingCalendar.render();
                                        }, 300);
                                    }
                                } catch (e) {
                                    console.error('Error refetching events:', e);
                                    window.waxingCalendar.render();
                                }
                            }
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        showBlockLoader(false);
                        alert('Network error. Please try again.');
                    }
                });
            }
            
            function debugSession() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'debug_calendar_session'
                    },
                    success: function(response) {
                        console.log('Debug Response:', response);
                        if (response.success) {
                            var info = response.data;
                            alert('📊 Session Debug Info:\n\n' +
                                'Session Status: ' + info.session_status + '\n' +
                                'Session ID: ' + info.session_id + '\n' +
                                'Logged In: ' + info.session_logged_in + '\n' +
                                'User: ' + info.session_user + '\n' +
                                'Login Time: ' + info.session_login_time + '\n' +
                                'Current Time: ' + info.current_time + '\n\n' +
                                'Check browser console for full details.');
                        } else {
                            alert('❌ Debug failed: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('❌ Network error during debug: ' + error);
                        console.log('Debug error:', xhr, status, error);
                    }
                });
            }
            
            function testBlockFunction() {
                var testDate = new Date();
                testDate.setDate(testDate.getDate() + 1); // Tomorrow
                var dateStr = testDate.toISOString().split('T')[0];
                var timeStr = '15:00:00'; // 3 PM
                
                console.log('Testing block function with:', dateStr, timeStr);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'block_calendar_time',
                        date: dateStr,
                        time: timeStr
                    },
                    success: function(response) {
                        console.log('Test Block Response:', response);
                        if (response.success) {
                            alert('✅ Test successful: ' + response.data);
                        } else {
                            alert('❌ Test failed: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('❌ Network error during test: ' + error + '\nStatus: ' + status);
                        console.log('Test error details:', xhr.responseText);
                    }
                });
            }
            
            function fixMissingTimeSlots() {
                if (!confirm('🕐 This will add missing 1PM (13:00) time slots to all dates.\n\nContinue?')) {
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'fix_missing_time_slots'
                    },
                    success: function(response) {
                        console.log('Fix Missing Slots Response:', response);
                        if (response.success) {
                            alert(response.data);
                            location.reload(); // Refresh to show new slots
                        } else {
                            alert('❌ Fix failed: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('❌ Network error during fix: ' + error);
                        console.log('Fix error:', xhr, status, error);
                    }
                });
            }
            
            function updateDayBlockButtons(dateStr, isBlocked) {
                // Update all buttons for this date across different views
                var buttons = document.querySelectorAll('.day-block-btn[data-date="' + dateStr + '"]');
                buttons.forEach(function(btn) {
                    if (isBlocked) {
                        btn.classList.add('blocked');
                        btn.innerHTML = btn.classList.contains('week-view-btn') ? '✓ Bloqueado' : '✓';
                        btn.title = 'Desbloquear día';
                    } else {
                        btn.classList.remove('blocked');
                        btn.innerHTML = btn.classList.contains('week-view-btn') ? '🚫 Bloquear día' : '🚫';
                        btn.title = 'Bloquear día completo';
                    }
                });
            }
            
            function toggleDayBlock(dateStr, buttonElement) {
                var isBlocked = buttonElement.classList.contains('blocked');
                var action = isBlocked ? 'unblock' : 'block';
                var confirmMsg = isBlocked 
                    ? '¿Desbloquear este día completo? Todos los horarios bloqueados estarán disponibles.'
                    : '¿Bloquear este día completo? Todos los horarios disponibles serán bloqueados.';
                
                if (!confirm(confirmMsg)) {
                    return;
                }
                
                showBlockLoader(true);
                
                var ajaxAction = isBlocked ? 'unblock_calendar_day' : 'block_calendar_day';
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: ajaxAction,
                        date: dateStr,
                        nonce: '<?php echo wp_create_nonce('waxing_appointments_nonce'); ?>'
                    },
                    success: function(response) {
                        showBlockLoader(false);
                        if (response.success) {
                            // Update fully blocked days array FIRST (before any rendering)
                            if (isBlocked) {
                                // Remove from fully blocked days array
                                var index = fullyBlockedDays.indexOf(dateStr);
                                if (index !== -1) {
                                    fullyBlockedDays.splice(index, 1);
                                }
                            } else {
                                // Add to fully blocked days array
                                if (fullyBlockedDays.indexOf(dateStr) === -1) {
                                    fullyBlockedDays.push(dateStr);
                                }
                            }
                            
                            // Update button state immediately (optimistic update)
                            updateDayBlockButtons(dateStr, !isBlocked);
                            
                            // Reload calendar events and update view
                            if (window.waxingCalendar) {
                                var currentView = window.waxingCalendar.view.type;
                                var currentDate = window.waxingCalendar.view.currentStart;
                                
                                // Refetch events and wait for completion before re-rendering
                                try {
                                    var refetchPromise = window.waxingCalendar.refetchEvents();
                                    
                                    // Check if refetchEvents returns a promise
                                    if (refetchPromise && typeof refetchPromise.then === 'function') {
                                        // Force re-render after events are loaded
                                        refetchPromise.then(function() {
                                            // Small delay to ensure array is updated in memory
                                            setTimeout(function() {
                                                // Update all buttons with the same date across all views
                                                updateDayBlockButtons(dateStr, !isBlocked);
                                                
                                                if (currentView === 'dayGridMonth' || currentView === 'timeGridWeek' || currentView === 'timeGridDay') {
                                                    // Force re-render by navigating to same date
                                                    window.waxingCalendar.gotoDate(currentDate);
                                                    window.waxingCalendar.render();
                                                } else {
                                                    window.waxingCalendar.render();
                                                }
                                            }, 100);
                                        }).catch(function() {
                                            // If refetch fails, still try to render
                                            updateDayBlockButtons(dateStr, !isBlocked);
                                            window.waxingCalendar.render();
                                        });
                                    } else {
                                        // If refetchEvents doesn't return a promise, just render after a delay
                                        setTimeout(function() {
                                            updateDayBlockButtons(dateStr, !isBlocked);
                                            if (currentView === 'dayGridMonth' || currentView === 'timeGridWeek' || currentView === 'timeGridDay') {
                                                window.waxingCalendar.gotoDate(currentDate);
                                                window.waxingCalendar.render();
                                            } else {
                                                window.waxingCalendar.render();
                                            }
                                        }, 300);
                                    }
                                } catch (e) {
                                    // If refetchEvents throws an error, still try to render
                                    console.error('Error refetching events:', e);
                                    updateDayBlockButtons(dateStr, !isBlocked);
                                    setTimeout(function() {
                                        window.waxingCalendar.render();
                                    }, 300);
                                }
                            }
                            
                            alert(response.data);
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        showBlockLoader(false);
                        alert('Error de red. Por favor intenta de nuevo.');
                    }
                });
            }
        </script>
        </body>
        </html>
