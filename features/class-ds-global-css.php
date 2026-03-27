<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Global_CSS {

    public function init() {
        add_action( 'wp_head', array( $this, 'output_css' ), 99 );
    }

    public function output_css() {
        // Always output the plugin-managed CSS file (survives updates).
        $plugin_css = file_get_contents( DS_TOOLKIT_PATH . 'includes/defaults/global-css.css' );
        echo '<style id="ds-toolkit-global-css">' . "\n" . $plugin_css . "\n" . '</style>' . "\n";

        // Inject site-specific CSS variable overrides stored in wp_options (survives updates).
        $settings  = get_option( 'ds_toolkit_settings', array() );
        $overrides = isset( $settings['global_css_overrides'] ) ? trim( $settings['global_css_overrides'] ) : '';
        if ( $overrides ) {
            echo '<style id="ds-toolkit-css-overrides">' . "\n" . $overrides . "\n" . '</style>' . "\n";
        }
    }
}
