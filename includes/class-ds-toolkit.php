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
    );

    public static function activate() {
        $settings = get_option( 'ds_toolkit_settings', array() );
        if ( ! isset( $settings['enable_login_branding'] ) ) {
            $settings['enable_login_branding'] = 1;
            update_option( 'ds_toolkit_settings', $settings );
        }
    }

    public function run() {
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
