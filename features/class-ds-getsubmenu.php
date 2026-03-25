<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [getsubmenu] Shortcode
 *
 * Outputs the child pages (sub-pages) of any page as a navigation list.
 *
 * Usage:
 *   [getsubmenu]                       — shows sub-pages of the current page
 *   [getsubmenu id="42"]               — shows sub-pages of page with ID 42
 *   [getsubmenu parent="about-us"]     — shows sub-pages of the page with slug "about-us"
 */
class DS_Getsubmenu {

    private $settings;

    public function __construct( $settings = array() ) {
        $this->settings = $settings;
    }

    public function init() {
        add_shortcode( 'getsubmenu', array( $this, 'render' ) );
    }

    public function render( $atts ) {
        global $post;

        $atts = shortcode_atts(
            array(
                'id'     => '',
                'parent' => '',
            ),
            $atts,
            'getsubmenu'
        );

        // Determine the parent page ID
        if ( ! empty( $atts['id'] ) ) {
            $parent_id = absint( $atts['id'] );
        } elseif ( ! empty( $atts['parent'] ) ) {
            $parent_page = get_page_by_path( sanitize_title( $atts['parent'] ) );
            $parent_id   = $parent_page ? $parent_page->ID : 0;
        } else {
            $parent_id = isset( $post->ID ) ? $post->ID : 0;
        }

        if ( ! $parent_id ) {
            return '';
        }

        $children = get_pages( array(
            'parent'      => $parent_id,
            'sort_column' => 'menu_order',
            'sort_order'  => 'ASC',
        ) );

        if ( empty( $children ) ) {
            return '';
        }

        $output = '<ul class="ds-getsubmenu">';
        foreach ( $children as $child ) {
            $output .= '<li><a href="' . esc_url( get_permalink( $child->ID ) ) . '">' . esc_html( $child->post_title ) . '</a></li>';
        }
        $output .= '</ul>';

        return $output;
    }
}
