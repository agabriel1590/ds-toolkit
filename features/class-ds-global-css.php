<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Global_CSS {

    private $settings;

    public function __construct( $settings = array() ) {
        $this->settings = $settings;
    }

    public function init() {
        add_action( 'wp_head', array( $this, 'output_css' ), 99 );
    }

    public function output_css() {
        $css = isset( $this->settings['global_css_content'] ) ? trim( $this->settings['global_css_content'] ) : '';
        if ( empty( $css ) ) {
            return;
        }
        echo '<style id="ds-toolkit-global-css">' . "\n" . $css . "\n" . '</style>' . "\n";
    }
}
