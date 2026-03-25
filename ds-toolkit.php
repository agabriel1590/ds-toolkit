<?php
/**
 * Plugin Name: DS Toolkit
 * Plugin URI:  https://github.com/agabriel1590/ds-toolkit
 * Description: Design Shop custom features and build toolkit.
 * Version:     0.5.0
 * Author:      Alipio Gabriel
 * Author URI:  https://github.com/agabriel1590
 * Text Domain: ds-toolkit
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DS_TOOLKIT_VERSION', '0.5.0' );
define( 'DS_TOOLKIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'DS_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );

require_once DS_TOOLKIT_PATH . 'includes/class-ds-toolkit.php';

register_activation_hook( __FILE__, 'ds_toolkit_activate' );
if ( ! function_exists( 'ds_toolkit_activate' ) ) {
    function ds_toolkit_activate() {
        $settings = get_option( 'ds_toolkit_settings', array() );
        if ( ! isset( $settings['enable_login_branding'] ) ) {
            $settings['enable_login_branding'] = 1;
            update_option( 'ds_toolkit_settings', $settings );
        }
    }
}

if ( ! function_exists( 'ds_toolkit_run' ) ) {
    function ds_toolkit_run() {
        $plugin = new DS_Toolkit();
        $plugin->run();
    }
    add_action( 'plugins_loaded', 'ds_toolkit_run' );
}