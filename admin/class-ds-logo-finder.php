<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Logo_Finder {

    public function init() {
        add_action( 'admin_menu',            array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_ds_import_logo', array( $this, 'ajax_import_logo' ) );
    }

    public function add_menu() {
        add_options_page(
            'Team Logo Finder',
            'Team Logos',
            'manage_options',
            'ds-logo-finder',
            array( $this, 'render_page' )
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'settings_page_ds-logo-finder' ) {
            return;
        }
        wp_enqueue_style(
            'ds-logo-finder',
            DS_TOOLKIT_URL . 'assets/css/logo-finder.css',
            array(),
            DS_TOOLKIT_VERSION
        );
        wp_enqueue_script(
            'ds-logo-finder',
            DS_TOOLKIT_URL . 'assets/js/logo-finder.js',
            array( 'jquery' ),
            DS_TOOLKIT_VERSION,
            true
        );
        wp_localize_script( 'ds-logo-finder', 'dsLogoFinder', array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ds_import_logo' ),
            'logoUrl'  => DS_TOOLKIT_URL . 'assets/images/team_logos/',
            'logos'    => $this->get_logo_list(),
        ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        require DS_TOOLKIT_PATH . 'admin/views/page-logo-finder.php';
    }

    /**
     * Returns an array of all logo filenames (without extension) sorted alphabetically.
     */
    public function get_logo_list() {
        $dir   = DS_TOOLKIT_PATH . 'assets/images/team_logos/';
        $files = glob( $dir . '*.png' );
        if ( ! $files ) {
            return array();
        }
        $logos = array();
        foreach ( $files as $file ) {
            $logos[] = basename( $file, '.png' );
        }
        sort( $logos );
        return $logos;
    }

    /**
     * AJAX handler — imports a single logo into the WP Media Library.
     * Called once per logo so the JS can report progress in real time.
     */
    public function ajax_import_logo() {
        check_ajax_referer( 'ds_import_logo', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        if ( ! $name ) {
            wp_send_json_error( array( 'message' => 'No logo name provided.' ) );
        }

        $filename = $name . '.png';
        $source   = DS_TOOLKIT_PATH . 'assets/images/team_logos/' . $filename;

        if ( ! file_exists( $source ) ) {
            wp_send_json_error( array( 'message' => 'File not found: ' . $filename ) );
        }

        // Check if already imported by looking for attachment with same filename in meta
        $existing = get_posts( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => '_wp_attached_file',
                    'value'   => $filename,
                    'compare' => 'LIKE',
                ),
            ),
        ) );

        if ( ! empty( $existing ) ) {
            wp_send_json_success( array(
                'status'  => 'exists',
                'message' => $name . ' — already in Media Library',
            ) );
        }

        // Load WP sideload helpers
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Copy to temp file so media_handle_sideload can manage it
        $tmp = wp_tempnam( $filename );
        if ( ! copy( $source, $tmp ) ) {
            wp_send_json_error( array( 'message' => 'Could not copy file to temp location.' ) );
        }

        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp,
            'type'     => 'image/png',
            'error'    => 0,
            'size'     => filesize( $tmp ),
        );

        $attachment_id = media_handle_sideload( $file_array, 0, $name );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
        }

        wp_send_json_success( array(
            'status'        => 'imported',
            'message'       => $name . ' — imported',
            'attachment_id' => $attachment_id,
        ) );
    }
}
