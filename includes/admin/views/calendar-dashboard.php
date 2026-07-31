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
                
                /* Toast notification styles */
                .toast-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                .toast {
                    background: white;
                    padding: 16px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border-left: 4px solid #0073aa;
                    min-width: 300px;
                    max-width: 400px;
                    animation: slideInRight 0.3s ease-out;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .toast.success {
                    border-left-color: #00a32a;
                }
                .toast.error {
                    border-left-color: #d63638;
                }
                .toast-icon {
                    font-size: 20px;
                    flex-shrink: 0;
                }
                .toast-message {
                    flex: 1;
                    color: #1a202c;
                    font-weight: 500;
                }
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
                .toast.hiding {
                    animation: slideOutRight 0.3s ease-in forwards;
                }
                
                @media (max-width: 768px) {
                    .toast-container {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                    }
                    .toast {
                        min-width: auto;
                        max-width: 100%;
                    }
                }
                
                /* Custom modal styles */
                .custom-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    z-index: 10001;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.2s ease-out;
                }
                .custom-modal {
                    background: white;
                    border-radius: 12px;
                    padding: 0;
                    max-width: 500px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                    animation: slideUp 0.3s ease-out;
                }
                .custom-modal-header {
                    padding: 20px 24px;
                    border-bottom: 1px solid #e1e5e9;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .custom-modal-title {
                    margin: 0;
                    font-size: 20px;
                    font-weight: 600;
                    color: #1a202c;
                }
                .custom-modal-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #718096;
                    padding: 0;
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 4px;
                    transition: background 0.2s;
                }
                .custom-modal-close:hover {
                    background: #f7fafc;
                    color: #1a202c;
                }
                .custom-modal-body {
                    padding: 24px;
                    color: #4a5568;
                    line-height: 1.6;
                }
                .custom-modal-footer {
                    padding: 16px 24px;
                    border-top: 1px solid #e1e5e9;
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                }
                .custom-modal-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                .custom-modal-btn-primary {
                    background: #0073aa;
                    color: white;
                }
                .custom-modal-btn-primary:hover {
                    background: #005a87;
                }
                .custom-modal-btn-secondary {
                    background: #e2e8f0;
                    color: #4a5568;
                }
                .custom-modal-btn-secondary:hover {
                    background: #cbd5e0;
                }
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from {
                        transform: translateY(20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                
                @media (max-width: 768px) {
                    .custom-modal {
                        width: 95%;
                        margin: 20px;
                    }
                    .custom-modal-header,
                    .custom-modal-body,
                    .custom-modal-footer {
                        padding: 16px;
                    }
                    .custom-modal-footer {
                        flex-direction: column-reverse;
                    }
                    .custom-modal-btn {
                        width: 100%;
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
                            <div class="legend-item">
                                <div class="legend-color" style="background:#0073aa;"></div>
                                <span>HBG — Harrisburg</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background:#7b3fbf;"></div>
                                <span>IT — Indian Trail</span>
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
            // Toast notification functions
            function showToast(message, type) {
                type = type || 'success';
                var container = document.getElementById('toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-container';
                    container.className = 'toast-container';
                    document.body.appendChild(container);
                }
                
                var toast = document.createElement('div');
                toast.className = 'toast ' + type;
                var icon = type === 'success' ? '✓' : '✕';
                toast.innerHTML = '<span class="toast-icon">' + icon + '</span><span class="toast-message">' + message + '</span>';
                
                container.appendChild(toast);
                
                setTimeout(function() {
                    toast.classList.add('hiding');
                    setTimeout(function() {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 3000);
            }
            
            // Custom modal function
            function showCustomModal(title, message, onConfirm, onCancel) {
                // Prevent duplicate modals
                var existingOverlay = document.querySelector('.custom-modal-overlay');
                if (existingOverlay) {
                    existingOverlay.remove();
                }
                
                var overlay = document.createElement('div');
                overlay.className = 'custom-modal-overlay';
                
                var modal = document.createElement('div');
                modal.className = 'custom-modal';
                
                modal.innerHTML = '<div class="custom-modal-header">' +
                    '<h3 class="custom-modal-title">' + title + '</h3>' +
                    '<button class="custom-modal-close" onclick="this.closest(\'.custom-modal-overlay\').remove()">×</button>' +
                    '</div>' +
                    '<div class="custom-modal-body">' + message + '</div>' +
                    '<div class="custom-modal-footer">' +
                    '<button class="custom-modal-btn custom-modal-btn-secondary" data-action="cancel">Cancel</button>' +
                    '<button class="custom-modal-btn custom-modal-btn-primary" data-action="confirm">OK</button>' +
                    '</div>';
                
                overlay.appendChild(modal);
                document.body.appendChild(overlay);
                
                var closeModal = function() {
                    overlay.remove();
                };
                
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        closeModal();
                        if (onCancel) onCancel();
                    }
                });
                
                modal.querySelector('[data-action="cancel"]').addEventListener('click', function() {
                    if (onCancel) onCancel();
                    closeModal();
                });
                
                modal.querySelector('[data-action="confirm"]').addEventListener('click', function() {
                    if (onConfirm) {
                        onConfirm();
                    }
                    closeModal();
                });
                
                modal.querySelector('.custom-modal-close').addEventListener('click', function() {
                    closeModal();
                    if (onCancel) onCancel();
                });
            }
            
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
            
            // Days that are fully blocked (will be updated via AJAX)
            var fullyBlockedDays = <?php echo json_encode($fully_blocked_days); ?>;
            
            // Function to fetch calendar events via AJAX
            function fetchCalendarEvents(fetchInfo, successCallback, failureCallback) {
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_calendar_events'
                    },
                    success: function(response) {
                        if (response.success) {
                            fullyBlockedDays = response.data.fully_blocked_days || [];
                            if (successCallback) {
                                successCallback(response.data.events || []);
                            }
                        } else {
                            if (failureCallback) {
                                failureCallback();
                            }
                        }
                    },
                    error: function() {
                        if (failureCallback) {
                            failureCallback();
                        }
                    }
                });
            }
            
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
                    events: fetchCalendarEvents,
                    navLinks: true,
                    // Enable date/time selection
                    selectable: true,
                    selectMirror: true,
                    selectOverlap: false, // Don't allow selection over existing events
                    unselectAuto: true,
                    selectMinDistance: 5, // Require at least 5px drag to trigger selection
                    selectConstraint: {
                        daysOfWeek: [1, 2, 3, 4, 5, 6], // Monday to Saturday
                        startTime: '09:00',
                        endTime: '19:00'
                    },
                    
                    // Selection callback - triggered when user drags to select time range
                    select: function(selectionInfo) {
                        var startDate = selectionInfo.start;
                        var endDate = selectionInfo.end;
                        
                        // Only show prompt if it's actually a range (more than 1 slot)
                        var duration = (endDate - startDate) / (1000 * 60 * 60);
                        if (duration >= 1) {
                            promptBlockTimeRange(startDate, endDate);
                        }
                        // For single slot clicks, dateClick will handle it
                    },
                    
                    eventClick: function(info) {
                        var event = info.event;
                        var extendedProps = event.extendedProps;
                        
                        if (extendedProps.type === 'appointment') {
                            var message = '<div style="line-height: 1.8;">' +
                                '<strong>👤 Customer:</strong> ' + extendedProps.customer_name + '<br>' +
                                '<strong>📧 Email:</strong> ' + extendedProps.customer_email + '<br>' +
                                '<strong>📞 Phone:</strong> ' + extendedProps.customer_phone + '<br>' +
                                '<strong>📍 Location:</strong> ' + (extendedProps.office_name || '—') + '<br>' +
                                '<strong>💄 Service:</strong> ' + extendedProps.service + '<br>' +
                                '<strong>📊 Status:</strong> ' + extendedProps.status + '<br>' +
                                '<strong>💰 Total:</strong> $' + extendedProps.total_price + '<br>' +
                                '<strong>💳 Deposit:</strong> $' + extendedProps.deposit_paid +
                                '</div>';
                            
                            showCustomModal('📅 Appointment Details', message, null, null);
                        } else if (extendedProps.type === 'blocked') {
                            var blockedMessage = 'This time slot is currently BLOCKED.';
                            if (extendedProps.blocked_for) {
                                blockedMessage += '<br><br><strong>Blocked for:</strong> ' + extendedProps.blocked_for;
                            }
                            blockedMessage += '<br><br>Do you want to UNBLOCK it?';
                            
                            showCustomModal(
                                '🚫 Unblock Time Slot',
                                blockedMessage,
                                function() {
                                    unblockTimeSlot(extendedProps.date, extendedProps.time);
                                },
                                null
                            );
                        }
                    },
                    
                    dateClick: function(info) {
                        // Prevent event propagation to avoid conflicts
                        info.jsEvent.stopPropagation();
                        
                        if (info.view.type === 'dayGridMonth') {
                            calendar.changeView('timeGridWeek', info.dateStr);
                        } else if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                            // Only allow clicking empty slots (not on events)
                            // Check if there's an event at this time
                            var clickedEvents = calendar.getEvents().filter(function(event) {
                                var eventStart = event.start;
                                var clickDate = info.date;
                                return eventStart.getFullYear() === clickDate.getFullYear() &&
                                       eventStart.getMonth() === clickDate.getMonth() &&
                                       eventStart.getDate() === clickDate.getDate() &&
                                       eventStart.getHours() === clickDate.getHours();
                            });
                            
                            // Only show prompt if no event exists at this time
                            if (clickedEvents.length === 0) {
                                // Extract date in YYYY-MM-DD format
                                var clickDate = info.date;
                                var year = clickDate.getFullYear();
                                var month = String(clickDate.getMonth() + 1).padStart(2, '0');
                                var day = String(clickDate.getDate()).padStart(2, '0');
                                var dateStr = year + '-' + month + '-' + day;
                                
                                // Extract time in HH:MM format
                                var hours = String(clickDate.getHours()).padStart(2, '0');
                                var minutes = String(clickDate.getMinutes()).padStart(2, '0');
                                var timeStr = hours + ':' + minutes;
                                
                                promptBlockTimeSlot(dateStr, timeStr);
                            }
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
                    slotDuration: '00:30:00', // 30 minute slots
                    snapDuration: '00:30:00' // Snap to 30 minute intervals
                });
                
                calendar.render();
                window.waxingCalendar = calendar;
            });
            
            function showBlockLoader(show, message) {
                message = message || 'Processing...';
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
                    document.body.appendChild(loader);
                    // Spinner animation
                    const style = document.createElement('style');
                    style.innerHTML = '@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}';
                    document.head.appendChild(style);
                }
                loader.innerHTML = '<div style="padding:30px;background:#fff;border-radius:8px;box-shadow:0 2px 8px #ccc;"><span class="loader-spinner" style="display:inline-block;width:32px;height:32px;border:4px solid #ccc;border-top:4px solid #333;border-radius:50%;animation:spin 1s linear infinite;"></span> <span style="margin-left:12px;vertical-align:middle;">' + message + '</span></div>';
                loader.style.display = show ? 'flex' : 'none';
            }

                function getSlotsBetween(startDate, endDate) {
                    // Accept Date objects. Use local date components to avoid timezone shifts.
                    var start = new Date(startDate);
                    var end = new Date(endDate);
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

            // Track ongoing AJAX requests to prevent duplicates
            var ongoingBlockRequests = {};
            
            // Helper function to normalize date to YYYY-MM-DD format
            function normalizeDate(dateInput) {
                if (typeof dateInput === 'string') {
                    // If it's a datetime string, extract just the date part
                    if (dateInput.indexOf('T') !== -1) {
                        return dateInput.split('T')[0];
                    }
                    // If it has timezone info, remove it
                    if (dateInput.length > 10 && dateInput.indexOf('-') !== -1) {
                        return dateInput.substring(0, 10);
                    }
                    return dateInput;
                } else if (dateInput instanceof Date) {
                    // If it's a Date object, format it
                    var year = dateInput.getFullYear();
                    var month = String(dateInput.getMonth() + 1).padStart(2, '0');
                    var day = String(dateInput.getDate()).padStart(2, '0');
                    return year + '-' + month + '-' + day;
                }
                return dateInput;
            }
            
            // Helper function to normalize time to HH:MM:SS format
            function normalizeTime(timeInput) {
                if (typeof timeInput === 'string') {
                    // Remove timezone info if present
                    if (timeInput.indexOf('-') !== -1 || timeInput.indexOf('+') !== -1) {
                        timeInput = timeInput.split(/[+-]/)[0];
                    }
                    // Add seconds if missing
                    if (timeInput.length === 5) {
                        return timeInput + ':00';
                    }
                    return timeInput;
                }
                return timeInput;
            }
            
            function blockTimeSlot(date, time, blockedFor) {
                blockedFor = blockedFor || '';
                
                // Normalize date and time formats
                date = normalizeDate(date);
                time = normalizeTime(time);
                
                // Create unique key for this slot
                var requestKey = date + '_' + time;
                
                // Prevent duplicate requests for the same slot
                if (ongoingBlockRequests[requestKey]) {
                    return;
                }
                
                ongoingBlockRequests[requestKey] = true;
                showBlockLoader(true, 'Blocking...');
                
                var ajaxData = {
                    action: 'block_calendar_time',
                    date: date,
                    time: time
                };
                
                if (blockedFor) {
                    ajaxData.blocked_for = blockedFor;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        delete ongoingBlockRequests[requestKey];
                        if (response.success) {
                            // Wait for calendar to update before showing toast
                            if (window.waxingCalendar) {
                                var refetchResult = window.waxingCalendar.refetchEvents();
                                if (refetchResult && typeof refetchResult.then === 'function') {
                                    // refetchEvents returns a promise
                                    refetchResult.then(function() {
                                        showBlockLoader(false);
                                        showToast(response.data || 'Time slot blocked successfully', 'success');
                                    }).catch(function() {
                                        showBlockLoader(false);
                                        showToast(response.data || 'Time slot blocked successfully', 'success');
                                    });
                                } else {
                                    // refetchEvents doesn't return a promise, wait a bit for render
                                    setTimeout(function() {
                                        showBlockLoader(false);
                                        showToast(response.data || 'Time slot blocked successfully', 'success');
                                    }, 300);
                                }
                            } else {
                                showBlockLoader(false);
                                showToast(response.data || 'Time slot blocked successfully', 'success');
                            }
                        } else {
                            showBlockLoader(false);
                            showToast('Error: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        delete ongoingBlockRequests[requestKey];
                        showBlockLoader(false);
                        showToast('Network error. Please try again.', 'error');
                    }
                });
            }
            
            function promptBlockTimeSlot(date, time) {
                // Prevent duplicate prompts
                if (document.querySelector('.custom-modal-overlay')) {
                    return;
                }
                
                var timeFormatted = time.length === 5 ? time : time.substring(0, 5);
                var message = '<div style="margin-bottom: 15px;">Block time slot <strong>' + timeFormatted + '</strong> on <strong>' + date + '</strong>?</div>' +
                    '<div style="margin-top: 15px;">' +
                    '<label for="blocked-for-input" style="display: block; margin-bottom: 8px; font-weight: 500; color: #4a5568;">Blocked for (optional):</label>' +
                    '<input type="text" id="blocked-for-input" placeholder="e.g., John Doe, Maintenance, etc." ' +
                    'style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; box-sizing: border-box;" />' +
                    '<div style="margin-top: 8px; font-size: 12px; color: #718096;">Leave empty to block without a name</div>' +
                    '</div>';
                
                showCustomModal(
                    'Block Time Slot',
                    message,
                    function() {
                        var input = document.getElementById('blocked-for-input');
                        var blockedFor = input ? input.value.trim() : '';
                        blockTimeSlot(date, time, blockedFor);
                    },
                    null
                );
                
                // Focus the input field
                setTimeout(function() {
                    var input = document.getElementById('blocked-for-input');
                    if (input) {
                        input.focus();
                        input.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                var blockedFor = input.value.trim();
                                blockTimeSlot(date, time, blockedFor);
                                var overlay = document.querySelector('.custom-modal-overlay');
                                if (overlay) {
                                    overlay.remove();
                                }
                            }
                        });
                    }
                }, 100);
            }
            
            function blockTimeRange(startDate, endDate, blockedFor) {
                blockedFor = blockedFor || '';
                
                // Create unique key for this range
                var rangeKey = startDate.getTime() + '_' + endDate.getTime();
                
                // Prevent duplicate requests for the same range
                if (ongoingBlockRequests[rangeKey]) {
                    return;
                }
                
                ongoingBlockRequests[rangeKey] = true;
                showBlockLoader(true, 'Blocking...');
                var slots = getSlotsBetween(startDate, endDate); // returns [{date: 'YYYY-MM-DD', time: 'HH:MM:SS'}, ...]
                var pending = slots.length;
                var anyErrors = false;
                var successCount = 0;

                if (pending === 0) {
                    delete ongoingBlockRequests[rangeKey];
                    showBlockLoader(false);
                    return;
                }

                slots.forEach(function(slot) {
                    var slotKey = slot.date + '_' + slot.time;
                    
                    // Skip if this slot is already being processed
                    if (ongoingBlockRequests[slotKey]) {
                        pending--;
                        if (pending <= 0) {
                            delete ongoingBlockRequests[rangeKey];
                            showBlockLoader(false);
                            if (window.waxingCalendar) {
                                window.waxingCalendar.refetchEvents();
                            }
                        }
                        return;
                    }
                    
                    ongoingBlockRequests[slotKey] = true;
                    
                    var ajaxData = {
                        action: 'block_calendar_time',
                        date: slot.date,
                        time: slot.time
                    };
                    
                    if (blockedFor) {
                        ajaxData.blocked_for = blockedFor;
                    }
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: ajaxData,
                        success: function(response) {
                            delete ongoingBlockRequests[slotKey];
                            if (response.success) {
                                successCount++;
                            } else {
                                anyErrors = true;
                                console.warn('Failed to block slot', slot, response.data);
                            }
                        },
                        error: function() {
                            delete ongoingBlockRequests[slotKey];
                            anyErrors = true;
                            console.error('Network error blocking slot', slot);
                        },
                        complete: function() {
                            pending--;
                            if (pending <= 0) {
                                delete ongoingBlockRequests[rangeKey];
                                
                                // Wait for calendar to update before showing toast
                                if (window.waxingCalendar) {
                                    var refetchResult = window.waxingCalendar.refetchEvents();
                                    if (refetchResult && typeof refetchResult.then === 'function') {
                                        // refetchEvents returns a promise
                                        refetchResult.then(function() {
                                            showBlockLoader(false);
                                            if (anyErrors) {
                                                showToast('Some time slots could not be blocked. Check console for details.', 'error');
                                            } else {
                                                var message = successCount + ' time slot(s) blocked successfully';
                                                if (blockedFor) {
                                                    message += ' for ' + blockedFor;
                                                }
                                                showToast(message, 'success');
                                            }
                                        }).catch(function() {
                                            showBlockLoader(false);
                                            if (anyErrors) {
                                                showToast('Some time slots could not be blocked. Check console for details.', 'error');
                                            } else {
                                                var message = successCount + ' time slot(s) blocked successfully';
                                                if (blockedFor) {
                                                    message += ' for ' + blockedFor;
                                                }
                                                showToast(message, 'success');
                                            }
                                        });
                                    } else {
                                        // refetchEvents doesn't return a promise, wait a bit for render
                                        setTimeout(function() {
                                            showBlockLoader(false);
                                            if (anyErrors) {
                                                showToast('Some time slots could not be blocked. Check console for details.', 'error');
                                            } else {
                                                var message = successCount + ' time slot(s) blocked successfully';
                                                if (blockedFor) {
                                                    message += ' for ' + blockedFor;
                                                }
                                                showToast(message, 'success');
                                            }
                                        }, 300);
                                    }
                                } else {
                                    showBlockLoader(false);
                                    if (anyErrors) {
                                        showToast('Some time slots could not be blocked. Check console for details.', 'error');
                                    } else {
                                        var message = successCount + ' time slot(s) blocked successfully';
                                        if (blockedFor) {
                                            message += ' for ' + blockedFor;
                                        }
                                        showToast(message, 'success');
                                    }
                                }
                            }
                        }
                    });
                });
            }
            
            function promptBlockTimeRange(startDate, endDate) {
                // Prevent duplicate prompts
                if (document.querySelector('.custom-modal-overlay')) {
                    return;
                }
                
                var duration = (endDate - startDate) / (1000 * 60 * 60);
                var slotsCount = Math.ceil(duration);
                var startFormatted = startDate.toLocaleString();
                var endFormatted = endDate.toLocaleString();
                
                var message = '<div style="margin-bottom: 15px;">You have selected <strong>' + slotsCount + ' hour(s)</strong> from:<br>' +
                    '<strong>' + startFormatted + '</strong> to <strong>' + endFormatted + '</strong></div>' +
                    '<div style="margin-top: 15px;">' +
                    '<label for="blocked-for-range-input" style="display: block; margin-bottom: 8px; font-weight: 500; color: #4a5568;">Blocked for (optional):</label>' +
                    '<input type="text" id="blocked-for-range-input" placeholder="e.g., John Doe, Maintenance, etc." ' +
                    'style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; box-sizing: border-box;" />' +
                    '<div style="margin-top: 8px; font-size: 12px; color: #718096;">Leave empty to block without a name</div>' +
                    '</div>';
                
                showCustomModal(
                    'Block Time Slots',
                    message,
                    function() {
                        var input = document.getElementById('blocked-for-range-input');
                        var blockedFor = input ? input.value.trim() : '';
                        blockTimeRange(startDate, endDate, blockedFor);
                    },
                    null
                );
                
                // Focus the input field
                setTimeout(function() {
                    var input = document.getElementById('blocked-for-range-input');
                    if (input) {
                        input.focus();
                        input.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                var blockedFor = input.value.trim();
                                blockTimeRange(startDate, endDate, blockedFor);
                                var overlay = document.querySelector('.custom-modal-overlay');
                                if (overlay) {
                                    overlay.remove();
                                }
                            }
                        });
                    }
                }, 100);
            }

            function unblockTimeSlot(date, time) {
                showBlockLoader(true, 'Unblocking...');
                
                // Normalize date and time formats
                date = normalizeDate(date);
                var normalized = normalizeTime(time);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'unblock_calendar_time',
                        date: date,
                        time: normalized
                    },
                    success: function(response) {
                        if (response.success) {
                            // Wait for calendar to update before showing toast
                            if (window.waxingCalendar) {
                                var refetchResult = window.waxingCalendar.refetchEvents();
                                if (refetchResult && typeof refetchResult.then === 'function') {
                                    // refetchEvents returns a promise
                                    refetchResult.then(function() {
                                        showBlockLoader(false);
                                        showToast('Time slot unblocked successfully', 'success');
                                    }).catch(function() {
                                        showBlockLoader(false);
                                        showToast('Time slot unblocked successfully', 'success');
                                    });
                                } else {
                                    // refetchEvents doesn't return a promise, wait a bit for render
                                    setTimeout(function() {
                                        showBlockLoader(false);
                                        showToast('Time slot unblocked successfully', 'success');
                                    }, 300);
                                }
                            } else {
                                showBlockLoader(false);
                                showToast('Time slot unblocked successfully', 'success');
                            }
                        } else {
                            showBlockLoader(false);
                            showToast('Error: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        showBlockLoader(false);
                        showToast('Network error. Please try again.', 'error');
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
                
                if (isBlocked) {
                    // Unblock day - simple confirmation
                    showCustomModal(
                        'Unblock Day',
                        'Unblock this entire day? All blocked time slots will be available.',
                        function() {
                            showBlockLoader(true, 'Unblocking...');
                            
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: {
                                    action: 'unblock_calendar_day',
                                    date: dateStr
                                },
                                success: function(response) {
                                    if (response.success) {
                                        // Wait for calendar to update before showing toast
                                        if (window.waxingCalendar) {
                                            var refetchResult = window.waxingCalendar.refetchEvents();
                                            if (refetchResult && typeof refetchResult.then === 'function') {
                                                // refetchEvents returns a promise
                                                refetchResult.then(function() {
                                                    updateDayBlockButtons(dateStr, false);
                                                    showBlockLoader(false);
                                                    showToast(response.data, 'success');
                                                }).catch(function() {
                                                    updateDayBlockButtons(dateStr, false);
                                                    showBlockLoader(false);
                                                    showToast(response.data, 'success');
                                                });
                                            } else {
                                                // refetchEvents doesn't return a promise, wait a bit for render
                                                setTimeout(function() {
                                                    updateDayBlockButtons(dateStr, false);
                                                    showBlockLoader(false);
                                                    showToast(response.data, 'success');
                                                }, 300);
                                            }
                                        } else {
                                            updateDayBlockButtons(dateStr, false);
                                            showBlockLoader(false);
                                            showToast(response.data, 'success');
                                        }
                                    } else {
                                        showBlockLoader(false);
                                        showToast('Error: ' + response.data, 'error');
                                    }
                                },
                                error: function() {
                                    showBlockLoader(false);
                                    showToast('Network error. Please try again.', 'error');
                                }
                            });
                        },
                        null
                    );
                } else {
                    // Block day - simple confirmation without name input
                    var message = '<div style="margin-bottom: 15px;">Block this entire day?<br><br>All available time slots will be blocked. Existing appointments and already blocked slots will be preserved.</div>';
                    
                    showCustomModal(
                        'Block Day',
                        message,
                        function() {
                            showBlockLoader(true, 'Blocking...');
                            
                            var ajaxData = {
                                action: 'block_calendar_day',
                                date: dateStr
                            };
                            
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                type: 'POST',
                                data: ajaxData,
                                success: function(response) {
                                    if (response.success) {
                                        // Wait for calendar to update before showing toast
                                        if (window.waxingCalendar) {
                                            var refetchResult = window.waxingCalendar.refetchEvents();
                                            if (refetchResult && typeof refetchResult.then === 'function') {
                                                // refetchEvents returns a promise
                                                refetchResult.then(function() {
                                                    updateDayBlockButtons(dateStr, true);
                                                    showBlockLoader(false);
                                                    showToast(response.data, 'success');
                                                }).catch(function() {
                                                    updateDayBlockButtons(dateStr, true);
                                                    showBlockLoader(false);
                                                    showToast(response.data, 'success');
                                                });
                                            } else {
                                                // refetchEvents doesn't return a promise, wait a bit for render
                                                setTimeout(function() {
                                                    updateDayBlockButtons(dateStr, true);
                                                    showBlockLoader(false);
                                                    showToast(response.data, 'success');
                                                }, 300);
                                            }
                                        } else {
                                            updateDayBlockButtons(dateStr, true);
                                            showBlockLoader(false);
                                            showToast(response.data, 'success');
                                        }
                                    } else {
                                        showBlockLoader(false);
                                        showToast('Error: ' + response.data, 'error');
                                    }
                                },
                                error: function() {
                                    showBlockLoader(false);
                                    showToast('Network error. Please try again.', 'error');
                                }
                            });
                        },
                        null
                    );
                }
            }
        </script>
        </body>
        </html>
