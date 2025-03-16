jQuery(function($) {
    $('.phone-auth-container').each(function() {
        const container = $(this);
        const instanceId = container.data('instance');
        const otpSection = container.find('.phone-auth-otp-section');
        const messageBox = container.find('.phone-auth-messages');
        const sendButton = container.find('#send-otp-button_' + instanceId);
        const resendButton = container.find('#resend-otp-button_' + instanceId);
        const phoneInput = container.find('#phone_number_' + instanceId);
        const otpInput = container.find('#phone_otp_' + instanceId);
        let timer;

        function startTimer() {
            let timeLeft = 120; // 2 minutes
            const countdownDisplay = container.find('.countdown');
            resendButton.addClass('resend-disabled');
        
        timer = setInterval(function() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            countdownDisplay.text(
                (minutes < 10 ? '0' : '') + minutes + ':' + 
                (seconds < 10 ? '0' : '') + seconds
            );

            if (--timeLeft < 0) {
                clearInterval(timer);
                resendButton.removeClass('resend-disabled');
                countdownDisplay.text('00:00');
            }
        }, 1000);
    }

    function showMessage(message, type) {
        messageBox
            .removeClass('error success')
            .addClass(type)
            .html(message)
            .show();
    }

        sendButton.add(resendButton).on('click', function() {
            const phoneNumber = phoneInput.val();
            const button = $(this);

            if (!phoneNumber) {
                showMessage('Please enter your phone number.', 'error');
                return;
            }

        button.prop('disabled', true);

        $.ajax({
            url: phoneAuthWoo.ajax_url,
            type: 'POST',
            data: {
                action: 'send_phone_otp',
                phone_number: phoneNumber,
                nonce: phoneAuthWoo.nonce,
                _ajax_nonce: phoneAuthWoo.nonce
            },
            success: function(response) {
                if (response.success) {
                    otpSection.show();
                    sendButton.hide();
                    resendButton.show();
                    startTimer();
                    showMessage('Verification code sent successfully!', 'success');
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

        otpInput.on('input', function() {
            const otp = $(this).val();
            
            if (otp.length === 6) {
            $.ajax({
                url: phoneAuthWoo.ajax_url,
                type: 'POST',
                data: {
                    action: 'verify_phone_otp',
                    phone_number: phoneInput.val(),
                    otp: otp,
                    nonce: phoneAuthWoo.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('Phone number verified successfully!', 'success');
                        clearInterval(timer);
                        // Submit the form after successful verification
                        setTimeout(() => {
                            container.closest('form').submit();
                        }, 1000);
                    } else {
                        showMessage(response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showMessage(
                        'Network error occurred. Please try again. Status: ' + 
                        status + '. Error: ' + (error || 'Unknown error'),
                        'error'
                    );
                },
                timeout: 10000 // 10 second timeout
            });
        }
            });
        });
    });
