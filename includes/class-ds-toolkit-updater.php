<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit_Updater {
    public function init() {
        $puc_path = DS_TOOLKIT_PATH . 'vendor/yahnis-elsts/plugin-update-checker/load-v5p6.php';
        if ( ! file_exists( $puc_path ) ) {
            return;
        }
        require_once $puc_path;

        $updateChecker = YahnisElsts\PluginUpdateChecker\v5p6\PucFactory::buildUpdateChecker(
            'https://github.com/agabriel1590/ds-toolkit/',
            DS_TOOLKIT_PATH . 'ds-toolkit.php',
            'ds-toolkit'
        );
        $updateChecker->setBranch( 'main' );
    }
}