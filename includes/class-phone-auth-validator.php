<?php
namespace PhoneAuthWoo;

class Phone_Auth_Validator {
    private $settings;

    public function __construct($settings = null) {
        $this->settings = $settings ?: new Phone_Auth_Settings();
    }

    public function validate_phone_number($phone_number) {
        // Remove any whitespace, dashes, or parentheses
        $phone_number = preg_replace('/[^0-9]/', '', $phone_number);

        // Check if the number is empty
        if (empty($phone_number)) {
            return new \WP_Error(
                'invalid_phone',
                __('Phone number is required.', 'phone-auth-woo')
            );
        }

        // Ensure number starts with Libya country code (218)
        if (!preg_match('/^218[0-9]{9}$/', $phone_number)) {
            return new \WP_Error(
                'invalid_phone',
                __('Please enter a valid Libyan phone number starting with 218.', 'phone-auth-woo')
            );
        }

        return $phone_number;
    }

    public function validate_otp($otp) {
        // Remove any whitespace
        $otp = trim($otp);

        if (empty($otp)) {
            return new \WP_Error(
                'invalid_otp',
                __('OTP code is required.', 'phone-auth-woo')
            );
        }

        if (!preg_match('/^[0-9]{6}$/', $otp)) {
            return new \WP_Error(
                'invalid_otp',
                __('OTP must be 6 digits.', 'phone-auth-woo')
            );
        }

        return $otp;
    }

    public function is_phone_number_unique($phone_number) {
        $existing_user = get_users([
            'meta_key' => '_phone_number',
            'meta_value' => $phone_number,
            'number' => 1,
            'count_total' => false
        ]);

        return empty($existing_user);
    }

    public function validate_login_attempt($phone_number) {
        $user = get_users([
            'meta_key' => '_phone_number',
            'meta_value' => $phone_number,
            'number' => 1,
            'count_total' => false
        ]);

        if (empty($user)) {
            return new \WP_Error(
                'invalid_phone',
                __('No account found with this phone number.', 'phone-auth-woo')
            );
        }

        return $user[0];
    }

    public function check_verification_attempts($phone_number) {
        $attempts = get_transient('phone_auth_attempts_' . $phone_number);
        
        if ($attempts && $attempts >= 5) {
            return new \WP_Error(
                'too_many_attempts',
                __('Too many verification attempts. Please try again later.', 'phone-auth-woo')
            );
        }

        if (!$attempts) {
            set_transient('phone_auth_attempts_' . $phone_number, 1, HOUR_IN_SECONDS);
        } else {
            set_transient('phone_auth_attempts_' . $phone_number, $attempts + 1, HOUR_IN_SECONDS);
        }

        return true;
    }

    public function reset_verification_attempts($phone_number) {
        delete_transient('phone_auth_attempts_' . $phone_number);
    }

    public function sanitize_phone_input($phone_number) {
        // Remove all non-numeric characters
        $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
        
        // Ensure the number starts with country code if not present
        if (substr($phone_number, 0, 3) !== '218') {
            $phone_number = '218' . $phone_number;
        }

        return $phone_number;
    }
}