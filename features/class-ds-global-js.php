<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Global_JS {

    public function init() {
        add_action( 'wp_footer', array( $this, 'output_js' ), 99 );
    }

    public function output_js() {
        // Always output the plugin-managed JS file (survives updates).
        $js = file_get_contents( DS_TOOLKIT_PATH . 'includes/defaults/global-js.js' );
        echo '<script id="ds-toolkit-global-js">' . "\n" . $js . "\n" . '</script>' . "\n";
    }
}
