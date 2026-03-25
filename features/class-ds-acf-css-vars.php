<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_ACF_CSS_Vars {

    private $settings;

    public function __construct( $settings = array() ) {
        $this->settings = $settings;
    }

    public function init() {
        add_action( 'wp_head', array( $this, 'output_css_vars' ), 1 );
    }

    public function output_css_vars() {
        if ( ! function_exists( 'get_field' ) ) {
            return;
        }

        $mappings = ! empty( $this->settings['acf_css_vars_mappings'] )
            ? $this->settings['acf_css_vars_mappings']
            : array();

        if ( empty( $mappings ) ) {
            return;
        }

        $css = '';

        foreach ( $mappings as $mapping ) {
            $acf_field = ! empty( $mapping['acf_field'] ) ? sanitize_key( $mapping['acf_field'] ) : '';
            $css_var   = ! empty( $mapping['css_var'] )   ? sanitize_text_field( $mapping['css_var'] ) : '';
            $fallback  = ! empty( $mapping['fallback'] )  ? sanitize_text_field( $mapping['fallback'] ) : '';

            if ( ! $acf_field || ! $css_var ) {
                continue;
            }

            $value = get_field( $acf_field, 'option' );

            if ( empty( $value ) && $fallback ) {
                $value = $fallback;
            }

            if ( ! $value ) {
                continue;
            }

            $css .= esc_attr( $css_var ) . ':' . esc_attr( $value ) . ';';
        }

        if ( $css ) {
            echo '<style id="dst-acf-css-vars">:root{' . $css . '}</style>' . "\n";
        }
    }
}
