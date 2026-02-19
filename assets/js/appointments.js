jQuery(document).ready(function($) {
    var modal = $('#waxing-appointment-modal');
    var loadingModal = $('#waxing-loading-modal');
    var btn = $('.btn-book-appointment');
    var span = $('.close');
    var cancelBtn = $('#cancel-appointment');
    var form = $('#appointment-form');
    var datepicker;
    
    // Function to show/hide loading modal
    function showLoading(show = true) {
        if (show) {
            loadingModal.show();
        } else {
            loadingModal.hide();
        }
    }
    
    btn.on('click', function() {
        modal.show();
        // Wait a bit for modal to be visible before initializing datepicker
        setTimeout(function() {
            initDatepicker();
        }, 100);
    });

    // Support triggering the modal from any element with class 'waxing_appointment_button'
    $(document).on('click', '.waxing_appointment_button', function(e) {
        e.preventDefault();
        modal.show();
        setTimeout(function() {
            initDatepicker();
        }, 100);
    });
    
    span.on('click', function() {
        modal.hide();
    });
    
    cancelBtn.on('click', function() {
        modal.hide();
    });
    
    $(window).on('click', function(event) {
        if (event.target == modal[0]) {
            modal.hide();
        }
    });
    
    function initDatepicker() {
        if (datepicker) {
            datepicker.destroy();
        }
        
        // Check if AirDatepicker is loaded
        if (typeof AirDatepicker === 'undefined') {
            console.error('AirDatepicker not loaded - using fallback');
            $('#appointment_date').attr('type', 'date');
            return;
        } else {
            console.log('AirDatepicker loaded. Constructor:', AirDatepicker);   
        }
        
        try {
            console.log('Initializing AirDatepicker...');
            
            // Check if element exists and is visible
            var dateInput = document.getElementById('appointment_date');
            if (!dateInput) {
                console.error('Date input element not found');
                return;
            }
            
            console.log('Date input element found:', dateInput);
            
            var today = new Date();
            var maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 60);
            
            console.log('Date range:', today, 'to', maxDate);
            
            // AirDatepicker configuration with proper event handling
            // Guard access to locale objects — some builds expose `locale` or `locales` differently
            // Local fallback locale (English) — use only this fallback
            var __ad_locale = {
                days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                today: 'Today',
                clear: 'Clear',
                dateFormat: 'MM/dd/yyyy',
                timeFormat: 'hh:mm aa',
                firstDay: 0
            };

            datepicker = new AirDatepicker(dateInput, {
                minDate: today,
                maxDate: maxDate,
                autoClose: true,
                dateFormat: 'MM/dd/yyyy',
                weekends: [0], // Only Sunday disabled
                container: '.modal-content', // Ensure datepicker is contained within modal
                locale: __ad_locale, // Use computed locale (external or fallback)
                onSelect: function({date, formattedDate, datepicker}) {
                    console.log('Date selected:', date, formattedDate);
                    
                    // Check if selected date is Sunday (closed)
                    if (date) {
                        var dayOfWeek = date.getDay();
                        if (dayOfWeek === 0) {
                            showError('Please select a day when we are open (Monday to Saturday)');
                            datepicker.clear();
                            $('#appointment_time').html('<option value="">Sunday - Closed</option>');
                            return;
                        }
                        
                        // Store ISO date (YYYY-MM-DD) and load available times using ISO
                        var isoDate = date.toISOString().split('T')[0];
                        $('#appointment_date_value').val(isoDate);
                        loadTimesForDate(isoDate);
                        $('.error-message').remove();
                    }
                },
                onRenderCell: function({date, cellType}) {
                    // Disable Sunday visually
                    if (cellType === 'day') {
                        var dayOfWeek = date.getDay();
                        if (dayOfWeek === 0) {
                            return {
                                disabled: true,
                                classes: 'weekend-disabled',
                                html: date.getDate()
                            };
                        }
                    }
                },
                onShow: function(isFinished) {
                    if (isFinished) {
                        console.log('AirDatepicker shown');
                    }
                },
                onHide: function(isFinished) {
                    if (isFinished) {
                        console.log('AirDatepicker hidden');
                    }
                }
            });
            
            console.log('AirDatepicker instance created:', datepicker);
            
        } catch (error) {
            console.error('AirDatepicker initialization error:', error);
            // Fallback to native date input with event handler
            $('#appointment_date').attr('type', 'date');
            $('#appointment_date').attr('min', today.toISOString().split('T')[0]);
            $('#appointment_date').attr('max', maxDate.toISOString().split('T')[0]);
            
            // Add change event for native date input
            $('#appointment_date').off('change.fallback').on('change.fallback', function() {
                var selectedDate = $(this).val();
                if (selectedDate) {
                    var date = new Date(selectedDate);
                    var dayOfWeek = date.getDay();
                    
                    if (dayOfWeek === 0) {
                        showError('Please select a day when we are open (Monday to Saturday)');
                        $(this).val('');
                        $('#appointment_time').html('<option value="">Sunday - Closed</option>');
                        return;
                    }
                    
                    $('#appointment_date_value').val(selectedDate);
                    loadTimesForDate(selectedDate);
                    $('.error-message').remove();
                }
            });
        }
    }
    
    function loadTimesForDate(date) {
        var timeSelect = $('#appointment_time');
        timeSelect.html('<option value="">Loading...</option>');
        showLoading(true);
        
        $.ajax({
            url: waxing_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'check_availability',
                date: date,
                nonce: waxing_ajax.nonce
            },
            success: function(response) {
                if (response && response.success) {
                    timeSelect.html('<option value="">Select a time...</option>');
                    if (Array.isArray(response.data) && response.data.length > 0) {
                        $.each(response.data, function(index, time) {
                            timeSelect.append('<option value="' + time.value + '">' + time.label + '</option>');
                        });
                    } else {
                        timeSelect.html('<option value="">No times available</option>');
                    }
                } else {
                    timeSelect.html('<option value="">Error loading times</option>');
                }
                showLoading(false);
            },
            error: function() {
                timeSelect.html('<option value="">Error loading times</option>');
                showLoading(false);
            }
        });
    }
    
    $('#service').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var price = selectedOption.data('price');

        if (price) {
            var deposit = Math.round(price * 0.2 * 100) / 100; // 20% deposit
            $('#total-price').text(price);
            $('#deposit-price').text(deposit.toFixed(2));
            $('#full-price').text(parseFloat(price).toFixed(2));
            $('#price-summary').show();
            $('#terms-checkbox-group').show();
        } else {
            $('#price-summary').hide();
            $('#terms-checkbox-group').hide();
        }
    });

    // Handle payment option selection
    $(document).on('change', 'input[name="payment_type"]', function() {
        $('.payment-radio-option').removeClass('selected');
        $(this).closest('.payment-radio-option').addClass('selected');
    });

    // Also handle click on the label itself
    $(document).on('click', '.payment-radio-option', function() {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });
    
    form.on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $('#book-appointment');
        var originalText = submitBtn.text();
        
        submitBtn.text('Processing...').prop('disabled', true);
        $('.error-message').remove();
        showLoading(true);
        
        // Use the hidden field value for the actual date
        var appointmentDate = $('#appointment_date_value').val() || $('#appointment_date').val();
        
        var formData = {
            action: 'book_appointment',
            customer_name: $('#customer_name').val(),
            customer_email: $('#customer_email').val(),
            customer_phone: $('#customer_phone').val(),
            service: $('#service').val(),
            appointment_date: appointmentDate,
            appointment_time: $('#appointment_time').val(),
            payment_type: $('input[name="payment_type"]:checked').val() || 'deposit',
            nonce: waxing_ajax.nonce
        };
        
        if (!validateForm(formData)) {
            submitBtn.text(originalText).prop('disabled', false);
            return;
        }
        
        $.ajax({
            url: waxing_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showLoading(false);
                    window.location.href = response.data.checkout_url;
                } else {
                    console.error('Booking response error:', response);
                    showError(response.data || 'An error occurred while booking your appointment.');
                    submitBtn.text(originalText).prop('disabled', false);
                    showLoading(false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Booking AJAX failed:', status, error, xhr.responseText);
                showError('Network error. Please try again.');
                submitBtn.text(originalText).prop('disabled', false);
                showLoading(false);
            }
        });
    });
    
    function validateForm(data) {
        var isValid = true;
        
        if (!data.customer_name.trim()) {
            showFieldError('#customer_name', 'Name is required');
            isValid = false;
        }
        
        if (!data.customer_email.trim()) {
            showFieldError('#customer_email', 'Email is required');
            isValid = false;
        } else if (!isValidEmail(data.customer_email)) {
            showFieldError('#customer_email', 'Please enter a valid email');
            isValid = false;
        }
        
        if (!data.customer_phone.trim()) {
            showFieldError('#customer_phone', 'Phone is required');
            isValid = false;
        } else if (!isValidUSPhone(data.customer_phone)) {
            showFieldError('#customer_phone', 'Invalid phone. Use 10 digits (e.g. (555) 555-5555)');
            isValid = false;
        }
        
        if (!data.service) {
            showFieldError('#service', 'Please select a service');
            isValid = false;
        }
        
        if (!data.appointment_date) {
            showFieldError('#appointment_date', 'Please select a date');
            isValid = false;
        }
        
        if (!data.appointment_time) {
            showFieldError('#appointment_time', 'Please select a time');
            isValid = false;
        }
        
        if (!$('#accept-terms').is(':checked')) {
            // Remove any existing error message
            $('#terms-checkbox-group .error-message').remove();
            // Add error message after the label
            $('#terms-checkbox-group label').after('<div class="error-message" style="margin-top:6px;color:#d63638;">You must accept the terms and conditions to proceed</div>');
            // Add visual indication to the checkbox
            $('#terms-checkbox-group label').css('border-color', '#d63638');
            isValid = false;
        } else {
            // Remove error styling if checkbox is checked
            $('#terms-checkbox-group label').css('border-color', '#e1e8f0');
        }
        
        return isValid;
    }
    
    function showFieldError(fieldSelector, message) {
        var field = $(fieldSelector);
        // remove any existing message for this field
        field.next('.error-message').remove();
        field.attr('aria-invalid', 'true');
        field.after('<div class="error-message" style="margin-top:6px;color:#d63638;">' + message + '</div>');
        try { field[0].focus(); } catch (e) {}
    }

    // US Phone helpers
    function getDigits(str) {
        return (str || '').toString().replace(/\D/g, '');
    }

    function formatPhoneUS(value) {
        var digits = getDigits(value);
        if (digits.length === 0) return '';
        if (digits.length <= 3) return '(' + digits;
        if (digits.length <= 6) return '(' + digits.slice(0,3) + ') ' + digits.slice(3);
        return '(' + digits.slice(0,3) + ') ' + digits.slice(3,6) + '-' + digits.slice(6,10);
    }

    function isValidUSPhone(value) {
        var digits = getDigits(value);
        return digits.length === 10;
    }

    // Attach phone formatting listeners (US formatting)
    $(document).on('input', '#customer_phone', function() {
        var orig = $(this).val();
        var formatted = formatPhoneUS(orig);
        $(this).val(formatted);
        // remove inline error when user types
        $(this).next('.error-message').remove();
        $(this).removeAttr('aria-invalid');
    });

    $(document).on('blur', '#customer_phone', function() {
        var val = $(this).val();
        if (val && !isValidUSPhone(val)) {
            showFieldError('#customer_phone', 'Invalid phone. Use 10 digits (e.g. (555) 555-5555)');
        }
    });
    
    function showError(message) {
        $('#appointment-form').prepend('<div class="error-message" style="margin-bottom: 15px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">' + message + '</div>');
    }
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Loader overlay
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
            loader.innerHTML = '<div style="padding:30px;background:#fff;border-radius:8px;box-shadow:0 2px 8px #ccc;"><span class="loader-spinner" style="display:inline-block;width:32px;height:32px;border:4px solid #ccc;border-top:4px solid #333;border-radius:50%;animation:spin 1s linear infinite;"></span> <span style="margin-left:12px;vertical-align:middle;">Processing...</span></div>';
            document.body.appendChild(loader);
            // Spinner animation
            const style = document.createElement('style');
            style.innerHTML = '@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}';
            document.head.appendChild(style);
        }
        loader.style.display = show ? 'flex' : 'none';
    }

    // Helpers and global blocking API (exposed to window so inline/admin JS can call)
    function getSlotsBetween(startDate, endDate) {
        var start = (typeof startDate === 'string') ? new Date(startDate) : new Date(startDate);
        var end = (typeof endDate === 'string') ? new Date(endDate) : new Date(endDate);
        var slots = [];
        // normalize to top of the hour (local time)
        start.setMinutes(0,0,0,0);
        var cur = new Date(start);
        function pad(n){return n<10? '0'+n : ''+n}
        while (cur < end) {
            // build local date YYYY-MM-DD
            var isoDate = cur.getFullYear() + '-' + pad(cur.getMonth()+1) + '-' + pad(cur.getDate());
            var hhmm = pad(cur.getHours()) + ':' + pad(cur.getMinutes());
            slots.push({ date: isoDate, time: hhmm + ':00' });
            cur.setHours(cur.getHours() + 1, 0, 0, 0);
        }
        return slots;
    }

    function blockTimeSlot(date, time, callbacks) {
        callbacks = callbacks || {};
        showBlockLoader(true);
        $.ajax({
            url: waxing_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'block_calendar_time',
                date: date,
                time: time,
                nonce: waxing_ajax.nonce
            },
            success: function(response) {
                showBlockLoader(false);
                if (callbacks.success) callbacks.success(response);
            },
            error: function(xhr, status, err) {
                showBlockLoader(false);
                if (callbacks.error) callbacks.error(xhr, status, err);
            }
        });
    }

    function unblockTimeSlot(date, time, callbacks) {
        callbacks = callbacks || {};
        showBlockLoader(true);
        $.ajax({
            url: waxing_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'unblock_calendar_time',
                date: date,
                time: time,
                nonce: waxing_ajax.nonce
            },
            success: function(response) {
                showBlockLoader(false);
                if (callbacks.success) callbacks.success(response);
            },
            error: function(xhr, status, err) {
                showBlockLoader(false);
                if (callbacks.error) callbacks.error(xhr, status, err);
            }
        });
    }

    function blockTimeRange(startDate, endDate, callbacks) {
        callbacks = callbacks || {};
        var slots = getSlotsBetween(startDate, endDate);
        if (!slots || slots.length === 0) {
            if (callbacks.complete) callbacks.complete();
            return;
        }
        showBlockLoader(true);
        var pending = slots.length;
        var errors = [];
        slots.forEach(function(slot) {
            $.ajax({
                url: waxing_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'block_calendar_time',
                    date: slot.date,
                    time: slot.time,
                    nonce: waxing_ajax.nonce
                },
                success: function(resp) {
                    // optionally collect success
                },
                error: function(xhr, status, err) {
                    errors.push({slot: slot, error: err});
                },
                complete: function() {
                    pending--;
                    if (pending === 0) {
                        showBlockLoader(false);
                        if (errors.length && callbacks.error) callbacks.error(errors);
                        if (!errors.length && callbacks.success) callbacks.success();
                        if (callbacks.complete) callbacks.complete(errors);
                    }
                }
            });
        });
    }

    // Expose to global scope so embedded admin JS can call them
    window.waxingBlockTimeSlot = blockTimeSlot;
    window.waxingBlockTimeRange = blockTimeRange;
    window.waxingUnblockTimeSlot = unblockTimeSlot;
});