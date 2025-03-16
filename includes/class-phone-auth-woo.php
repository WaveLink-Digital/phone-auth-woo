<?php
namespace PhoneAuthWoo;

class Phone_Auth_Woo {
    private $settings;
    private $api;
    private $validator;

    public function init() {
        // Load dependencies
        $this->settings = new Phone_Auth_Settings();
        $this->api = new Phone_Auth_API($this->settings);
        $this->validator = new Phone_Auth_Validator($this->settings);

        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('woocommerce_login_form', [$this, 'add_phone_login_field']);
        add_action('woocommerce_register_form', [$this, 'add_phone_register_field']);
        add_action('woocommerce_edit_account_form', [$this, 'add_phone_login_field']);
        add_filter('woocommerce_process_login_errors', [$this, 'validate_phone_login'], 10, 3);
        add_filter('woocommerce_process_registration_errors', [$this, 'validate_phone_registration'], 10, 4);
        add_action('woocommerce_save_account_details', [$this, 'validate_account_details']);
        
        // AJAX handlers
        add_action('wp_ajax_nopriv_send_phone_otp', [$this, 'send_phone_otp']);
        add_action('wp_ajax_send_phone_otp', [$this, 'send_phone_otp']);
        add_action('wp_ajax_nopriv_verify_phone_otp', [$this, 'verify_phone_otp']);
        add_action('wp_ajax_verify_phone_otp', [$this, 'verify_phone_otp']);
    }

    public function enqueue_scripts() {
        if (is_account_page()) {
            wp_enqueue_style(
                'phone-auth-woo',
                PHONE_AUTH_WOO_PLUGIN_URL . 'assets/css/phone-auth-woo.css',
                [],
                PHONE_AUTH_WOO_VERSION
            );

            wp_enqueue_script(
                'phone-auth-woo',
                PHONE_AUTH_WOO_PLUGIN_URL . 'assets/js/phone-auth-woo.js',
                ['jquery'],
                PHONE_AUTH_WOO_VERSION,
                true
            );

            wp_localize_script('phone-auth-woo', 'phoneAuthWoo', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('phone-auth-woo'),
            ]);
        }
    }

    public function add_phone_login_field() {
        include PHONE_AUTH_WOO_PLUGIN_DIR . 'templates/phone-auth-form.php';
    }

    public function add_phone_register_field() {
        include PHONE_AUTH_WOO_PLUGIN_DIR . 'templates/phone-auth-form.php';
    }

    public function send_phone_otp() {
        if (!check_ajax_referer('phone-auth-woo', 'nonce', false)) {
            wp_send_json_error('Invalid security token. Please refresh the page and try again.');
            return;
        }

        $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
        
        $validated_number = $this->validator->validate_phone_number($phone_number);
        if (is_wp_error($validated_number)) {
            wp_send_json_error($validated_number->get_error_message());
        }

        // Check verification attempts
        $attempt_check = $this->validator->check_verification_attempts($validated_number);
        if (is_wp_error($attempt_check)) {
            wp_send_json_error($attempt_check->get_error_message());
        }

        // Send OTP
        $result = $this->api->send_otp($validated_number);
        if (!$result['success']) {
            wp_send_json_error($result['message']);
        }

        wp_send_json_success();
    }

    public function verify_phone_otp() {
        check_ajax_referer('phone-auth-woo', 'nonce');

        $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
        $otp = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

        $validated_number = $this->validator->validate_phone_number($phone_number);
        if (is_wp_error($validated_number)) {
            wp_send_json_error($validated_number->get_error_message());
        }

        $validated_otp = $this->validator->validate_otp($otp);
        if (is_wp_error($validated_otp)) {
            wp_send_json_error($validated_otp->get_error_message());
        }

        // Verify OTP
        if (!$this->api->verify_otp($validated_number, $validated_otp)) {
            wp_send_json_error(__('Invalid verification code.', 'phone-auth-woo'));
        }

        // Reset verification attempts on success
        $this->validator->reset_verification_attempts($validated_number);

        wp_send_json_success();
    }

    public function validate_phone_login($validation_error, $username, $password) {
        $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
        
        if (empty($phone_number)) {
            return new \WP_Error('phone_required', __('Phone verification is required.', 'phone-auth-woo'));
        }

        $user = $this->validator->validate_login_attempt($phone_number);
        if (is_wp_error($user)) {
            return $user;
        }
        
        // Set the username to the user we found by phone number
        // This ensures WooCommerce will log in the correct user
        $_POST['username'] = $user->user_login;
        
        return $validation_error;
    }

    public function validate_phone_registration($validation_error, $username, $email, $password = null) {
        $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
        
        if (empty($phone_number)) {
            return new \WP_Error('phone_required', __('Phone verification is required.', 'phone-auth-woo'));
        }

        $validated_number = $this->validator->validate_phone_number($phone_number);
        if (is_wp_error($validated_number)) {
            return $validated_number;
        }

        if (!$this->validator->is_phone_number_unique($validated_number)) {
            return new \WP_Error('phone_exists', __('This phone number is already registered.', 'phone-auth-woo'));
        }

        // Store the validated phone number for later use
        add_action('user_register', function($user_id) use ($validated_number) {
            update_user_meta($user_id, '_phone_number', $validated_number);
        });

        return $validation_error;
    }

    public function validate_account_details($user_id) {
        try {
            $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
            
            if (empty($phone_number)) {
                throw new \Exception(__('Phone verification is required.', 'phone-auth-woo'));
            }

            $validated_number = $this->validator->validate_phone_number($phone_number);
            if (is_wp_error($validated_number)) {
                throw new \Exception($validated_number->get_error_message());
            }

            // Check if the phone number belongs to another user
            $existing_user = get_users([
                'meta_key' => '_phone_number',
                'meta_value' => $validated_number,
                'exclude' => [$user_id],
                'number' => 1
            ]);

            if (!empty($existing_user)) {
                throw new \Exception(__('This phone number is already registered to another account.', 'phone-auth-woo'));
            }

            // Update the phone number
            update_user_meta($user_id, '_phone_number', $validated_number);
        } catch (\Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_safe_redirect(wc_get_account_endpoint_url('edit-account'));
            exit;
        }
    }
}