<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

require_once plugin_dir_path(__FILE__) . 'includes/IconnectSettings.php';

delete_option(IconnectSettings::GLOBAL_SETTINGS);
delete_option(IconnectSettings::EMAIL_SETTINGS);

$users = get_users();
foreach ($users as $user) {
    if(isset($user->caps['seller'])) {
        delete_user_meta($user->ID, IconnectSettings::VENDOR_SETTINGS);
    }
}
?>