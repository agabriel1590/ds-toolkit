<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

$opts = get_option( 'ds_toolkit_settings', array() );
unset( $opts['shop_name'] );
if ( empty( $opts ) ) {
    delete_option( 'ds_toolkit_settings' );
} else {
    update_option( 'ds_toolkit_settings', $opts );
}