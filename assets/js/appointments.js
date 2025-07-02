jQuery(document).ready(function($) {
    var modal = $('#waxing-appointment-modal');
    var btn = $('.btn-book-appointment');
    var span = $('.close');
    var cancelBtn = $('#cancel-appointment');
    var form = $('#appointment-form');
    
    btn.on('click', function() {
        modal.show();
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
    
    $('#service').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var price = selectedOption.data('price');
        
        if (price) {
            var deposit = Math.round(price * 0.4 * 100) / 100;
            $('#total-price').text(price);
            $('#deposit-price').text(deposit.toFixed(2));
            $('#price-summary').show();
        } else {
            $('#price-summary').hide();
        }
    });
    
    $('#appointment_date').on('change', function() {
        var selectedDate = $(this).val();
        var timeSelect = $('#appointment_time');
        
        if (selectedDate) {
            timeSelect.html('<option value="">Loading...</option>');
            
            $.ajax({
                url: waxing_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'check_availability',
                    date: selectedDate,
                    nonce: waxing_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        timeSelect.html('<option value="">Select a time...</option>');
                        
                        if (response.data.length > 0) {
                            $.each(response.data, function(index, time) {
                                timeSelect.append('<option value="' + time.value + '">' + time.label + '</option>');
                            });
                        } else {
                            timeSelect.html('<option value="">No times available</option>');
                        }
                    } else {
                        timeSelect.html('<option value="">Error loading times</option>');
                    }
                },
                error: function() {
                    timeSelect.html('<option value="">Error loading times</option>');
                }
            });
        } else {
            timeSelect.html('<option value="">Select date first...</option>');
        }
    });
    
    form.on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $('#book-appointment');
        var originalText = submitBtn.text();
        
        submitBtn.text('Processing...').prop('disabled', true);
        $('.error-message').remove();
        
        var formData = {
            action: 'book_appointment',
            customer_name: $('#customer_name').val(),
            customer_email: $('#customer_email').val(),
            customer_phone: $('#customer_phone').val(),
            service: $('#service').val(),
            appointment_date: $('#appointment_date').val(),
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
                    window.location.href = response.data.checkout_url;
                } else {
                    showError(response.data || 'An error occurred while booking your appointment.');
                    submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showError('Network error. Please try again.');
                submitBtn.text(originalText).prop('disabled', false);
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
    
    var today = new Date().toISOString().split('T')[0];
    $('#appointment_date').attr('min', today);
    
    var maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 60);
    $('#appointment_date').attr('max', maxDate.toISOString().split('T')[0]);
});