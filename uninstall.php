<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

delete_option( 'ds_toolkit_settings' );
delete_transient( 'ds_toolkit_latest_release' );
