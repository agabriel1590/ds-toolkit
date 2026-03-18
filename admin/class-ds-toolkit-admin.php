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
        add_settings_section( 'ds_toolkit_features', 'Features', null, 'ds-toolkit' );
        add_settings_field( 'enable_login_branding', 'Enable LeagueApps Custom Login', array( $this, 'field_login_branding' ), 'ds-toolkit', 'ds_toolkit_features' );
    }
    public function field_shop_name() {
        $opts = get_option( 'ds_toolkit_settings' );
        $val  = isset( $opts['shop_name'] ) ? esc_attr( $opts['shop_name'] ) : '';
        echo '<input type="text" name="ds_toolkit_settings[shop_name]" value="' . $val . '" class="regular-text" />';
    }
    public function field_login_branding() {
        $opts    = get_option( 'ds_toolkit_settings', array() );
        $checked = ! empty( $opts['enable_login_branding'] ) ? 'checked' : '';
        echo '<label><input type="checkbox" name="ds_toolkit_settings[enable_login_branding]" value="1" ' . $checked . '> Enable custom login logo, branding, and support link</label>';
    }
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?><div class="wrap"><h1>DS Toolkit <span style="font-size:13px;color:#999;">v<?php echo DS_TOOLKIT_VERSION; ?></span></h1><form method="post" action="options.php"><?php settings_fields('ds_toolkit_options');do_settings_sections('ds-toolkit');submit_button();?></form></div><?php
    }
}