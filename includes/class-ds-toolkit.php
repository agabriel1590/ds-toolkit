<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit {
    public function run() {
        require_once DS_TOOLKIT_PATH . 'admin/class-ds-toolkit-admin.php';
        $admin = new DS_Toolkit_Admin();
        $admin->init();

        $settings = get_option( 'ds_toolkit_settings', array() );
        if ( ! empty( $settings['enable_login_branding'] ) ) {
            require_once DS_TOOLKIT_PATH . 'features/class-ds-login-branding.php';
            $login = new DS_Login_Branding();
            $login->init();
        }
    }
}