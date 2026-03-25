<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit_Updater {

    private $slug    = 'ds-toolkit';
    private $repo    = 'agabriel1590/ds-toolkit';
    private $api_url = 'https://api.github.com/repos/agabriel1590/ds-toolkit/releases/latest';

    public function init() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
        add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $latest_version  = ltrim( $release['tag_name'], 'v' );
        $current_version = DS_TOOLKIT_VERSION;
        $plugin_file     = $this->slug . '/' . $this->slug . '.php';

        if ( version_compare( $latest_version, $current_version, '>' ) ) {
            $transient->response[ $plugin_file ] = (object) array(
                'slug'        => $this->slug,
                'plugin'      => $plugin_file,
                'new_version' => $latest_version,
                'url'         => 'https://github.com/' . $this->repo,
                'package'     => $release['zipball_url'],
            );
        }

        return $transient;
    }

    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || $args->slug !== $this->slug ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        return (object) array(
            'name'          => 'DS Toolkit',
            'slug'          => $this->slug,
            'version'       => ltrim( $release['tag_name'], 'v' ),
            'author'        => 'Alipio Gabriel',
            'homepage'      => 'https://github.com/' . $this->repo,
            'sections'      => array(
                'description' => 'Design Shop custom features and build toolkit.',
                'changelog'   => isset( $release['body'] ) ? $release['body'] : '',
            ),
            'download_link' => $release['zipball_url'],
        );
    }

    /**
     * Rename the extracted GitHub zip folder to match the plugin slug.
     * GitHub zipballs extract as "owner-repo-commithash/" which breaks WP installs.
     */
    public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
        global $wp_filesystem;

        if ( ! isset( $hook_extra['plugin'] ) || strpos( $hook_extra['plugin'], $this->slug ) === false ) {
            return $source;
        }

        $corrected = trailingslashit( $remote_source ) . $this->slug . '/';

        if ( $source !== $corrected ) {
            $wp_filesystem->move( $source, $corrected );
            return $corrected;
        }

        return $source;
    }

    private function get_latest_release() {
        $cache_key = 'ds_toolkit_latest_release';
        $cached    = get_transient( $cache_key );
        if ( $cached ) {
            return $cached;
        }

        $response = wp_remote_get( $this->api_url, array(
            'headers' => array( 'Accept' => 'application/vnd.github+json' ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['tag_name'] ) ) {
            return false;
        }

        set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
        return $data;
    }
}