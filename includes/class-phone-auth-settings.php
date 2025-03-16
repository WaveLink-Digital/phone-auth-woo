<?php
namespace PhoneAuthWoo;

class Phone_Auth_Settings {
    private $options_prefix = 'phone_auth_woo_';
    private $page_slug = 'phone-auth-woo-settings';

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Phone Authentication', 'phone-auth-woo'),
            __('Phone Authentication', 'phone-auth-woo'),
            'manage_woocommerce',
            $this->page_slug,
            [$this, 'render_settings_page']
        );
    }

    private function encrypt_value($value) {
        if (empty($value)) {
            return '';
        }
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($value);
        }
        $encryption_key = wp_salt('auth');
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $encryption_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decrypt_value($encrypted_value) {
        if (empty($encrypted_value)) {
            return '';
        }
        try {
            if (!function_exists('openssl_decrypt')) {
                return base64_decode($encrypted_value);
            }
            $encryption_key = wp_salt('auth');
            $decoded = base64_decode($encrypted_value);
            $iv_length = openssl_cipher_iv_length('AES-256-CBC');
            $iv = substr($decoded, 0, $iv_length);
            $encrypted = substr($decoded, $iv_length);
            return openssl_decrypt($encrypted, 'AES-256-CBC', $encryption_key, 0, $iv);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function api_key_callback() {
        $value = $this->get_api_key();
        printf(
            '<input type="password" id="api_key" name="%s" value="%s" class="regular-text">',
            esc_attr($this->options_prefix . 'api_key'),
            esc_attr($value)
        );
    }

    public function sender_id_callback() {
        $value = $this->get_sender_id();
        printf(
            '<input type="text" id="sender_id" name="%s" value="%s" class="regular-text" maxlength="11">',
            esc_attr($this->options_prefix . 'sender_id'),
            esc_attr($value)
        );
    }

    public function get_api_key() {
        $encrypted_value = get_option($this->options_prefix . 'api_key');
        return $this->decrypt_value($encrypted_value);
    }

    public function get_sender_id() {
        $encrypted_value = get_option($this->options_prefix . 'sender_id');
        return $this->decrypt_value($encrypted_value);
    }

    public function register_settings() {
        register_setting(
            'phone-auth-woo-settings',
            $this->options_prefix . 'api_key',
            [
                'sanitize_callback' => [$this, 'encrypt_value']
            ]
        );

        register_setting(
            'phone-auth-woo-settings',
            $this->options_prefix . 'sender_id',
            [
                'sanitize_callback' => [$this, 'encrypt_value']
            ]
        );

        add_settings_section(
            'phone_auth_api_settings',
            __('API Settings', 'phone-auth-woo'),
            [$this, 'settings_section_callback'],
            'phone-auth-woo-settings'
        );

        add_settings_field(
            'api_key',
            __('API Key', 'phone-auth-woo'),
            [$this, 'api_key_callback'],
            'phone-auth-woo-settings',
            'phone_auth_api_settings'
        );

        add_settings_field(
            'sender_id',
            __('Sender ID', 'phone-auth-woo'),
            [$this, 'sender_id_callback'],
            'phone-auth-woo-settings',
            'phone_auth_api_settings'
        );
    }

    public function settings_section_callback() {
        echo '<p>' . esc_html__('Configure your iSend SMS API settings below.', 'phone-auth-woo') . '</p>';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('phone-auth-woo-settings');  // Changed option group
                do_settings_sections('phone-auth-woo-settings'); // Changed page slug
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}