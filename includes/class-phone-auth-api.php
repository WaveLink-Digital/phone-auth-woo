<?php
namespace PhoneAuthWoo;

class Phone_Auth_API {
    private $api_url = 'https://isend.com.ly/api/v3/sms/send';
    private $settings;

    public function __construct($settings = null) {
        $this->settings = $settings ?: new Phone_Auth_Settings();
    }

    public function send_otp($phone_number) {
        $otp = $this->generate_otp();
        $message = sprintf(
            __('Your verification code is: %s', 'phone-auth-woo'),
            $otp
        );

        $response = $this->send_sms($phone_number, $message);

        if ($response['status'] === 'success') {
            // Store OTP in transient for validation
            // Expires in 5 minutes
            set_transient(
                'phone_auth_otp_' . $phone_number,
                $otp,
                5 * MINUTE_IN_SECONDS
            );
            return ['success' => true];
        }

        return [
            'success' => false,
            'message' => isset($response['message']) ? $response['message'] : __('Failed to send verification code', 'phone-auth-woo')
        ];
    }

    public function verify_otp($phone_number, $otp) {
        $stored_otp = get_transient('phone_auth_otp_' . $phone_number);
        
        if (!$stored_otp) {
            return false;
        }

        delete_transient('phone_auth_otp_' . $phone_number);
        return $stored_otp === $otp;
    }

    private function send_sms($recipient, $message) {
        $api_key = $this->settings->get_api_key();
        $sender_id = $this->settings->get_sender_id();

        if (empty($api_key) || empty($sender_id)) {
            $this->log_error('API credentials not configured');
            return [
                'status' => 'error',
                'message' => 'API credentials not configured'
            ];
        }

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'recipient' => $recipient,
                'sender_id' => $sender_id,
                'type' => 'plain',
                'message' => $message
            ])
        ];

        $response = wp_remote_post($this->api_url, $args);

        if (is_wp_error($response)) {
            $this->log_error('API request failed', [
                'error' => $response->get_error_message(),
                'recipient' => $recipient
            ]);
            return [
                'status' => 'error',
                'message' => $response->get_error_message()
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!$body) {
            $this->log_error('Invalid API response', [
                'response' => wp_remote_retrieve_body($response)
            ]);
        }

        return $body ?: [
            'status' => 'error',
            'message' => 'Invalid response from API'
        ];
    }

    private function log_error($message, $data = []) {
        if (WP_DEBUG) {
            error_log(sprintf(
                '[Phone Auth Woo] %s | Data: %s',
                $message,
                json_encode($data)
            ));
        }
    }

    private function generate_otp($length = 6) {
        return str_pad(
            (string) wp_rand(0, pow(10, $length) - 1),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }
}