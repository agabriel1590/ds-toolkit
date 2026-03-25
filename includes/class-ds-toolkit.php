<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit {

    /**
     * Feature registry.
     * Key = settings option that enables the feature.
     * Value = file path (relative to DS_TOOLKIT_PATH) and class name to instantiate.
     *
     * To add a new feature: drop a class file in features/ and add one entry here.
     */
    private $features = array(
        'enable_login_branding' => array(
            'file'  => 'features/class-ds-login-branding.php',
            'class' => 'DS_Login_Branding',
        ),
        'hide_fl_assistant' => array(
            'file'  => 'features/class-ds-hide-fl-assistant.php',
            'class' => 'DS_Hide_FL_Assistant',
        ),
        'acf_css_vars_enabled' => array(
            'file'  => 'features/class-ds-acf-css-vars.php',
            'class' => 'DS_ACF_CSS_Vars',
        ),
    );

    public static function activate() {
        $settings = get_option( 'ds_toolkit_settings', array() );

        foreach ( self::get_defaults() as $key => $value ) {
            if ( ! isset( $settings[ $key ] ) ) {
                $settings[ $key ] = $value;
            }
        }

        update_option( 'ds_toolkit_settings', $settings );
    }

    private static function get_defaults() {
        return array(
            'enable_login_branding' => 1,
            'hide_fl_assistant'     => 1,
            'acf_css_vars_enabled'  => 1,
            'acf_css_vars_mappings' => array(
                array(
                    'acf_field' => 'header_scrolled_bar_color',
                    'css_var'   => '--header-scrolled-bar-color',
                    'fallback'  => 'var(--fl-global-accent)',
                ),
            ),
        );
    }

    /**
     * Fills in any missing settings keys with their defaults.
     * Runs on every load so existing installs pick up new feature defaults automatically.
     */
    private function maybe_set_defaults() {
        $settings = get_option( 'ds_toolkit_settings', array() );
        $changed  = false;

        foreach ( self::get_defaults() as $key => $value ) {
            if ( ! isset( $settings[ $key ] ) ) {
                $settings[ $key ] = $value;
                $changed          = true;
            }
        }

        if ( $changed ) {
            update_option( 'ds_toolkit_settings', $settings );
        }
    }

    public function run() {
        $this->maybe_set_defaults();

        if ( is_admin() ) {
            require_once DS_TOOLKIT_PATH . 'admin/class-ds-toolkit-admin.php';
            $admin = new DS_Toolkit_Admin();
            $admin->init();

            require_once DS_TOOLKIT_PATH . 'includes/class-ds-toolkit-updater.php';
            $updater = new DS_Toolkit_Updater();
            $updater->init();
        }

        $settings = get_option( 'ds_toolkit_settings', array() );

        foreach ( $this->features as $key => $feature ) {
            if ( ! empty( $settings[ $key ] ) ) {
                require_once DS_TOOLKIT_PATH . $feature['file'];
                $instance = new $feature['class']( $settings );
                $instance->init();
            }
        }
    }
}
