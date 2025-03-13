<?php
/**
 * Plugin Name: Phone Authentication for WooCommerce
 * Plugin URI: https://github.com/WaveLink-Digital/phone-auth-woo
 * Description: Adds secure phone number authentication and registration functionality to WooCommerce using OTP verification.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://wavelink.digital
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: phone-auth-woo
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package PhoneAuthWoo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PHONE_AUTH_WOO_VERSION', '1.0.0');
define('PHONE_AUTH_WOO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PHONE_AUTH_WOO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PHONE_AUTH_WOO_META_KEY', '_phone_number');

// Load plugin files
require_once PHONE_AUTH_WOO_PLUGIN_DIR . 'includes/class-phone-auth-settings.php';
require_once PHONE_AUTH_WOO_PLUGIN_DIR . 'includes/class-phone-auth-validator.php';
require_once PHONE_AUTH_WOO_PLUGIN_DIR . 'includes/class-phone-auth-api.php';
require_once PHONE_AUTH_WOO_PLUGIN_DIR . 'includes/class-phone-auth-woo.php';

// Load text domain
add_action('plugins_loaded', function() {
    load_plugin_textdomain('phone-auth-woo', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Plugin activation check
register_activation_hook(__FILE__, function() {
    global $wp_version;
    
    if (version_compare($wp_version, '5.8', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WordPress version 5.8 or higher.');
    }

    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and activated.');
    }
});

// Initialize the plugin
function phone_auth_woo_init() {
    $phone_auth = new PhoneAuthWoo\Phone_Auth_Woo();
    $phone_auth->init();
}
add_action('plugins_loaded', 'phone_auth_woo_init', 20);

// Add settings link to plugins page
function phone_auth_woo_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=phone-auth-woo-settings">' . __('Settings', 'phone-auth-woo') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'phone_auth_woo_add_settings_link');