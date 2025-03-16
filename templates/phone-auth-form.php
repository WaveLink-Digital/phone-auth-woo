<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php
$instance_id = uniqid('phone-auth-');
?>
<div class="phone-auth-container" data-instance="<?php echo esc_attr($instance_id); ?>">
    <?php wp_nonce_field('phone_auth_form', 'phone_auth_nonce_' . $instance_id); ?>
    <div class="phone-auth-field">
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="phone_number_<?php echo esc_attr($instance_id); ?>"><?php esc_html_e('Phone Number', 'phone-auth-woo'); ?> <span class="required">*</span></label>
            <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text phone-number-input" 
                   name="phone_number" id="phone_number_<?php echo esc_attr($instance_id); ?>" 
                   autocomplete="tel" placeholder="218XXXXXXXXX" />
        </p>
    </div>

    <div class="phone-auth-otp-section" style="display: none;">
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="phone_otp_<?php echo esc_attr($instance_id); ?>"><?php esc_html_e('Verification Code', 'phone-auth-woo'); ?> <span class="required">*</span></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text phone-otp-input" 
                   name="phone_otp" id="phone_otp_<?php echo esc_attr($instance_id); ?>" 
                   autocomplete="one-time-code" maxlength="6" />
        </p>
        <div class="otp-timer">
            <span class="timer-text"><?php esc_html_e('Resend code in:', 'phone-auth-woo'); ?> </span>
            <span class="countdown">02:00</span>
        </div>
    </div>

    <div class="phone-auth-actions">
        <button type="button" class="button send-otp-button" id="send-otp-button_<?php echo esc_attr($instance_id); ?>">
            <?php esc_html_e('Send Verification Code', 'phone-auth-woo'); ?>
        </button>
        <button type="button" class="button resend-otp-button" id="resend-otp-button_<?php echo esc_attr($instance_id); ?>" style="display: none;">
            <?php esc_html_e('Resend Code', 'phone-auth-woo'); ?>
        </button>
    </div>

    <div class="phone-auth-messages"></div>
</div>