jQuery(function($) {
    const phoneAuthContainer = $('.phone-auth-container');
    const otpSection = $('.phone-auth-otp-section');
    const messageBox = $('.phone-auth-messages');
    let timer;

    function startTimer() {
        let timeLeft = 120; // 2 minutes
        const countdownDisplay = $('.countdown');
        const resendButton = $('#resend-otp-button');

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

    $('#send-otp-button, #resend-otp-button').on('click', function() {
        const phoneNumber = $('#phone_number').val();
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
                nonce: phoneAuthWoo.nonce
            },
            success: function(response) {
                if (response.success) {
                    otpSection.show();
                    $('#send-otp-button').hide();
                    $('#resend-otp-button').show();
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

    $('#phone_otp').on('input', function() {
        const otp = $(this).val();
        
        if (otp.length === 6) {
            $.ajax({
                url: phoneAuthWoo.ajax_url,
                type: 'POST',
                data: {
                    action: 'verify_phone_otp',
                    phone_number: $('#phone_number').val(),
                    otp: otp,
                    nonce: phoneAuthWoo.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('Phone number verified successfully!', 'success');
                        clearInterval(timer);
                        // Submit the form after successful verification
                        setTimeout(() => {
                            phoneAuthContainer.closest('form').submit();
                        }, 1000);
                    } else {
                        showMessage(response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showMessage(
                        'Network error occurred. Please try again. Error: ' + 
                        (error || 'Unknown error'),
                        'error'
                    );
                },
                timeout: 10000 // 10 second timeout
            });
        }
    });
});