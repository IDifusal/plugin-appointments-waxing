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
            datepicker = new AirDatepicker(dateInput, {
                minDate: today,
                maxDate: maxDate,
                autoClose: true,
                dateFormat: 'MM/dd/yyyy',
                weekends: [6, 0], // Saturday and Sunday
                container: '.modal-content', // Ensure datepicker is contained within modal
                locale: AirDatepicker.locale.en, // Set English as default language
                onSelect: function({date, formattedDate, datepicker}) {
                    console.log('Date selected:', date, formattedDate);
                    
                    // Check if selected date is a weekend
                    if (date) {
                        var dayOfWeek = date.getDay();
                        if (dayOfWeek === 0 || dayOfWeek === 6) {
                            showError('Please select a weekday (Monday to Friday)');
                            datepicker.clear();
                            $('#appointment_time').html('<option value="">Weekends not available</option>');
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
                    // Disable weekends visually
                    if (cellType === 'day') {
                        var dayOfWeek = date.getDay();
                        if (dayOfWeek === 0 || dayOfWeek === 6) {
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
                    
                    if (dayOfWeek === 0 || dayOfWeek === 6) {
                        showError('Please select a weekday (Monday to Friday)');
                        $(this).val('');
                        $('#appointment_time').html('<option value="">Weekends not available</option>');
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
            $('#price-summary').show();
        } else {
            $('#price-summary').hide();
        }
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
                    showError(response.data || 'An error occurred while booking your appointment.');
                    submitBtn.text(originalText).prop('disabled', false);
                    showLoading(false);
                }
            },
            error: function() {
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
        
        return isValid;
    }
    
    function showFieldError(fieldSelector, message) {
        var field = $(fieldSelector);
        field.after('<div class="error-message">' + message + '</div>');
        field.focus();
    }
    
    function showError(message) {
        $('#appointment-form').prepend('<div class="error-message" style="margin-bottom: 15px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;">' + message + '</div>');
    }
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});