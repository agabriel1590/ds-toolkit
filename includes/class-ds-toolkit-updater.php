<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit_Updater {
    public function init() {
        require_once DS_TOOLKIT_PATH . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
        $updateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/agabriel1590/ds-toolkit/',
            DS_TOOLKIT_PATH . 'ds-toolkit.php',
            'ds-toolkit'
        );
        $updateChecker->setBranch( 'main' );
    }
}