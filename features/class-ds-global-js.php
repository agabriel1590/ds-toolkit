<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Global_JS {

    private $settings;

    public function __construct( $settings = array() ) {
        $this->settings = $settings;
    }

    public function init() {
        add_action( 'wp_footer', array( $this, 'output_js' ), 99 );
    }

    public function output_js() {
        $js = isset( $this->settings['global_js_content'] ) ? trim( $this->settings['global_js_content'] ) : '';
        if ( empty( $js ) ) {
            return;
        }
        echo '<script id="ds-toolkit-global-js">' . "\n" . $js . "\n" . '</script>' . "\n";
    }
}
