<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit_Admin {
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    public function add_menu() {
        add_menu_page( 'DS Toolkit', 'DS Toolkit', 'manage_options', 'ds-toolkit', array( $this, 'render_page' ), 'dashicons-hammer', 80 );
    }
    public function register_settings() {
        register_setting( 'ds_toolkit_options', 'ds_toolkit_settings' );
        add_settings_section( 'ds_toolkit_general', 'General Settings', null, 'ds-toolkit' );
        add_settings_field( 'ds_toolkit_shop_name', 'Shop Name', array( $this, 'field_shop_name' ), 'ds-toolkit', 'ds_toolkit_general' );
    }
    public function field_shop_name() {
        $opts = get_option( 'ds_toolkit_settings' );
        $val = isset( $opts['shop_name'] ) ? esc_attr( $opts['shop_name'] ) : '';
        echo '<input type="text" name="ds_toolkit_settings[shop_name]" value="' . $val . '" class="regular-text" />';
    }
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        echo '<div class="wrap"><h1>DS Toolkit v' . DS_TOOLKIT_VERSION . '</h1><form method="post" action="options.php">';
        settings_fields( 'ds_toolkit_options' );
        do_settings_sections( 'ds-toolkit' );
        submit_button();
        echo '</form></div>';
    }
}