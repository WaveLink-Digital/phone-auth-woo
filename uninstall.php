<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('phone_auth_woo_api_key');
delete_option('phone_auth_woo_sender_id');

// Delete user meta data
$users = get_users(['fields' => 'ID']);
foreach ($users as $user_id) {
    delete_user_meta($user_id, '_phone_number');
}

// Clean up any transients
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options} 
    WHERE option_name LIKE '_transient_phone_auth_attempts_%' 
    OR option_name LIKE '_transient_timeout_phone_auth_attempts_%'"
);