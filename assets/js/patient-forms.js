/**
 * Patient intake forms: step navigation, conditional fields, validation
 * and AJAX submission.
 */
(function ($) {
    'use strict';

    function PatientForm(el) {
        this.$form = $(el);
        this.$steps = this.$form.find('.waxing-step');
        this.total = this.$steps.length;
        this.current = 1;

        this.$next = this.$form.find('.waxing-next');
        this.$prev = this.$form.find('.waxing-prev');
        this.$submit = this.$form.find('.waxing-submit');
        this.$message = this.$form.find('.waxing-form-message');

        this.bind();
        this.applyConditions();
        this.render();
    }

    PatientForm.prototype.bind = function () {
        var self = this;

        this.$next.on('click', function () {
            if (self.validateStep(self.current)) {
                self.go(self.current + 1);
            }
        });

        this.$prev.on('click', function () {
            self.go(self.current - 1);
        });

        // Any answer can be the trigger for a conditional follow-up, so
        // re-evaluate on every change rather than wiring per-field listeners.
        this.$form.on('change', 'input, textarea, select', function () {
            self.applyConditions();
            self.clearError($(this).closest('.waxing-field'));
        });

        this.$form.on('input', 'input, textarea', function () {
            self.clearError($(this).closest('.waxing-field'));
        });

        this.$form.on('submit', function (e) {
            e.preventDefault();
            self.submit();
        });
    };

    /**
     * Show/hide fields declaring data-show-if / data-hide-if as "field:value".
     */
    PatientForm.prototype.applyConditions = function () {
        var self = this;

        this.$form.find('[data-show-if], [data-hide-if]').each(function () {
            var $field = $(this);
            var show = $field.data('show-if');
            var hide = $field.data('hide-if');
            var visible = true;

            if (show) {
                visible = self.conditionMet(show);
            }
            if (hide && self.conditionMet(hide)) {
                visible = false;
            }

            $field.prop('hidden', !visible);

            // A hidden field must not block submission, and must not carry a
            // stale answer the patient can no longer see or correct.
            if (!visible) {
                self.clearError($field);
                $field.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
                $field.find('input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="number"], textarea').val('');
            }
        });
    };

    PatientForm.prototype.conditionMet = function (expr) {
        var parts = String(expr).split(':');
        var name = parts[0];
        var expected = parts.length > 1 ? parts[1] : '';

        var $inputs = this.$form.find('[name="' + name + '"], [name="' + name + '[]"]');
        if (!$inputs.length) {
            return false;
        }

        var matched = false;
        $inputs.each(function () {
            var $input = $(this);
            var type = $input.attr('type');

            if (type === 'radio' || type === 'checkbox') {
                if ($input.is(':checked') && String($input.val()) === expected) {
                    matched = true;
                }
            } else if (String($input.val()) === expected) {
                matched = true;
            }
        });

        return matched;
    };

    /**
     * Validate only the visible required fields of one step.
     */
    PatientForm.prototype.validateStep = function (step) {
        var self = this;
        var $step = this.$steps.filter('[data-step="' + step + '"]');
        var ok = true;
        var $firstBad = null;

        $step.find('.waxing-field').each(function () {
            var $field = $(this);
            if ($field.prop('hidden')) {
                return;
            }

            var $required = $field.find('[required]');
            if (!$required.length) {
                return;
            }

            var type = $required.attr('type');
            var valid = true;
            var message = 'This field is required.';

            if (type === 'radio' || type === 'checkbox') {
                valid = $field.find('input:checked').length > 0;
                if (type === 'checkbox') {
                    message = 'Please confirm to continue.';
                } else {
                    message = 'Please choose an option.';
                }
            } else {
                var value = $.trim($required.val() || '');
                valid = value !== '';

                if (valid && $required.attr('type') === 'email') {
                    valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    message = 'Please enter a valid email address.';
                }
            }

            if (!valid) {
                ok = false;
                self.showError($field, message);
                if (!$firstBad) {
                    $firstBad = $field;
                }
            }
        });

        if ($firstBad) {
            this.scrollTo($firstBad);
            $firstBad.find('input, textarea').first().trigger('focus');
        }

        return ok;
    };

    PatientForm.prototype.showError = function ($field, message) {
        $field.addClass('has-error').find('.waxing-field-error').first().text(message);
    };

    PatientForm.prototype.clearError = function ($field) {
        $field.removeClass('has-error').find('.waxing-field-error').first().text('');
    };

    PatientForm.prototype.scrollTo = function ($el) {
        var top = $el.offset().top - 80;
        $('html, body').animate({ scrollTop: top < 0 ? 0 : top }, 250);
    };

    PatientForm.prototype.go = function (step) {
        if (step < 1 || step > this.total) {
            return;
        }
        this.current = step;
        this.render();
        this.scrollTo(this.$form);
    };

    PatientForm.prototype.render = function () {
        var current = this.current;

        this.$steps.each(function () {
            var $step = $(this);
            $step.prop('hidden', parseInt($step.data('step'), 10) !== current);
        });

        this.$prev.prop('hidden', current === 1);
        this.$next.prop('hidden', current === this.total);
        this.$submit.prop('hidden', current !== this.total);

        this.$form.find('.waxing-step-current').text(current);
        this.$form.find('.waxing-progress-fill').css('width', (current / this.total * 100) + '%');
    };

    PatientForm.prototype.setLoading = function (loading) {
        this.$submit.prop('disabled', loading);
        this.$submit.find('.waxing-spinner').prop('hidden', !loading);
        this.$next.prop('disabled', loading);
        this.$prev.prop('disabled', loading);
    };

    PatientForm.prototype.submit = function () {
        var self = this;

        if (!this.validateStep(this.current)) {
            return;
        }

        // Earlier steps carry required fields too — a patient can reach the
        // last step, go back, clear a name, and return. Re-check everything.
        for (var step = 1; step <= this.total; step++) {
            if (!this.validateStep(step)) {
                this.go(step);
                return;
            }
        }

        this.$message.prop('hidden', true).text('');
        this.setLoading(true);

        var data = this.$form.serializeArray();
        data.push({ name: 'action', value: this.$form.data('action') });
        data.push({ name: 'nonce', value: waxing_patient_ajax.nonce });

        $.post(waxing_patient_ajax.ajax_url, $.param(data))
            .done(function (response) {
                if (response && response.success) {
                    self.showSuccess();
                    return;
                }

                var msg = 'Something went wrong. Please try again.';
                if (response && response.data && response.data.message) {
                    msg = response.data.message;
                }
                self.fail(msg, response && response.data ? response.data.field : null);
            })
            .fail(function () {
                self.fail('We could not reach the server. Please check your connection and try again.');
            })
            .always(function () {
                self.setLoading(false);
            });
    };

    PatientForm.prototype.fail = function (message, field) {
        this.$message.text(message).prop('hidden', false);

        if (field) {
            var $field = this.$form.find('[name="' + field + '"]').closest('.waxing-field');
            if ($field.length) {
                this.showError($field, message);

                // Jump back to the step holding the offending field so the
                // patient can actually see what needs fixing.
                var stepNumber = parseInt($field.closest('.waxing-step').data('step'), 10);
                if (stepNumber && stepNumber !== this.current) {
                    this.go(stepNumber);
                }
                this.scrollTo($field);
                return;
            }
        }

        this.scrollTo(this.$message);
    };

    PatientForm.prototype.showSuccess = function () {
        this.$form.find('.waxing-step, .waxing-form-nav, .waxing-progress, .waxing-form-header, .waxing-form-message').prop('hidden', true);
        this.$form.find('.waxing-form-success').prop('hidden', false);
        this.scrollTo(this.$form);
        this.$form.trigger('waxing:success');
    };

    /**
     * Return a form to its blank first-step state.
     *
     * On the shared kiosk this is a privacy requirement, not a convenience:
     * the next walk-in must never see the previous client's answers.
     */
    PatientForm.prototype.reset = function () {
        this.$form.get(0).reset();
        this.$form.find('.waxing-field').each(function () {
            $(this).removeClass('has-error').find('.waxing-field-error').text('');
        });
        this.$form.find('.waxing-form-success').prop('hidden', true);
        this.$form.find('.waxing-form-message').prop('hidden', true).text('');
        this.$form.find('.waxing-form-header, .waxing-progress').prop('hidden', false);
        this.$form.find('.waxing-form-nav').prop('hidden', false);
        this.current = 1;
        this.applyConditions();
        this.render();
    };

    /**
     * Kiosk chooser: swaps between the two forms and resets between clients.
     */
    function Kiosk(el, forms) {
        var $kiosk = $(el);
        var $choose = $kiosk.find('.waxing-kiosk-choose');
        var $panels = $kiosk.find('.waxing-kiosk-panel');
        var resetAfter = parseInt($kiosk.data('reset-after'), 10) || 0;
        var timer = null;

        function formsIn($panel) {
            return forms.filter(function (f) {
                return $.contains($panel.get(0), f.$form.get(0));
            });
        }

        function home() {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
            $panels.prop('hidden', true).each(function () {
                formsIn($(this)).forEach(function (f) { f.reset(); });
            });
            $choose.prop('hidden', false);
            $('html, body').animate({ scrollTop: Math.max($kiosk.offset().top - 40, 0) }, 200);
        }

        $kiosk.on('click', '[data-open]', function () {
            var target = $(this).data('open');
            $choose.prop('hidden', true);
            $panels.each(function () {
                $(this).prop('hidden', $(this).data('panel') !== target);
            });
            $('html, body').animate({ scrollTop: Math.max($kiosk.offset().top - 40, 0) }, 200);
        });

        $kiosk.on('click', '.waxing-kiosk-back', home);

        // After a submission, hold the confirmation briefly so the client sees
        // it, then clear the screen for whoever is next.
        if (resetAfter > 0) {
            forms.forEach(function (f) {
                f.$form.on('waxing:success', function () {
                    timer = window.setTimeout(home, resetAfter * 1000);
                });
            });
        }
    }

    $(function () {
        var forms = $('.waxing-patient-form').map(function () {
            return new PatientForm(this);
        }).get();

        $('.waxing-kiosk').each(function () {
            new Kiosk(this, forms);
        });
    });

})(jQuery);
