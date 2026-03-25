<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit_Admin {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        require_once DS_TOOLKIT_PATH . 'admin/class-ds-logo-finder.php';
        $logo_finder = new DS_Logo_Finder();
        $logo_finder->init();
    }

    public function add_menu() {
        add_options_page( 'DS Toolkit', 'DS Toolkit', 'manage_options', 'ds-toolkit', array( $this, 'render_page' ) );
    }

    public function register_settings() {
        register_setting( 'ds_toolkit_options', 'ds_toolkit_settings' );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'settings_page_ds-toolkit' ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style(
            'ds-toolkit-admin',
            DS_TOOLKIT_URL . 'assets/css/admin.css',
            array(),
            DS_TOOLKIT_VERSION
        );
        wp_enqueue_script(
            'ds-toolkit-admin',
            DS_TOOLKIT_URL . 'assets/js/admin.js',
            array( 'jquery', 'media-upload' ),
            DS_TOOLKIT_VERSION,
            true
        );
        wp_localize_script( 'ds-toolkit-admin', 'dstAdmin', array(
            'defaultLogoUrl' => DS_TOOLKIT_URL . 'assets/images/cropped-LA-circle-logo-1.png',
        ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $opts                  = get_option( 'ds_toolkit_settings', array() );
        $enabled               = ! empty( $opts['enable_login_branding'] );
        $logo_id               = ! empty( $opts['login_logo_id'] ) ? absint( $opts['login_logo_id'] ) : 0;
        $logo_url              = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        $default_url           = DS_TOOLKIT_URL . 'assets/images/cropped-LA-circle-logo-1.png';
        $hide_fl_assistant     = ! empty( $opts['hide_fl_assistant'] );
        $acf_css_vars_enabled  = ! empty( $opts['acf_css_vars_enabled'] );
        $acf_css_vars_mappings = ! empty( $opts['acf_css_vars_mappings'] ) ? $opts['acf_css_vars_mappings'] : array();
        $getsubmenu_enabled                 = ! empty( $opts['getsubmenu_enabled'] );
        $current_year_enabled               = ! empty( $opts['current_year_enabled'] );
        $forminator_email_partner_enabled   = ! empty( $opts['forminator_email_partner_enabled'] );
        $forminator_email_partner_fallback  = ! empty( $opts['forminator_email_partner_fallback'] )
            ? $opts['forminator_email_partner_fallback']
            : 'designshop@leagueapps.com';

        require DS_TOOLKIT_PATH . 'admin/views/page-settings.php';
    }
}
