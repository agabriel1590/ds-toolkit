<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit {
    public function run() {
        require_once DS_TOOLKIT_PATH . 'admin/class-ds-toolkit-admin.php';
        $admin = new DS_Toolkit_Admin();
        $admin->init();
    }
}