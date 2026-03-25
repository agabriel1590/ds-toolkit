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
        add_filter( 'plugin_action_links_' . $this->plugin_file(), array( $this, 'add_check_update_link' ) );
        add_action( 'admin_init', array( $this, 'handle_check_update' ) );
    }

    /**
     * Returns the plugin's actual file path relative to the plugins directory.
     * Works regardless of what the plugin folder is currently named.
     * e.g. "ds-toolkit-0.5.4/ds-toolkit.php" or "ds-toolkit/ds-toolkit.php"
     */
    private function plugin_file() {
        return plugin_basename( DS_TOOLKIT_PATH . $this->slug . '.php' );
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
        $plugin_file     = $this->plugin_file();

        if ( version_compare( $latest_version, $current_version, '>' ) ) {
            $transient->response[ $plugin_file ] = (object) array(
                'slug'        => $this->slug,
                'plugin'      => $plugin_file,
                'new_version' => $latest_version,
                'url'         => 'https://github.com/' . $this->repo,
                'package'     => $this->get_download_url( $release ),
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
            'download_link' => $this->get_download_url( $release ),
        );
    }

    /**
     * Rename the extracted zip folder to ds-toolkit/ regardless of what it was called.
     * Confirms it's our plugin by checking for ds-toolkit.php inside the extracted folder.
     */
    public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) {
        global $wp_filesystem;

        // Only act when this is a plugin upgrade
        if ( ! isset( $hook_extra['plugin'] ) ) {
            return $source;
        }

        // Confirm the extracted folder actually contains our plugin file
        if ( ! $wp_filesystem->exists( trailingslashit( $source ) . $this->slug . '.php' ) ) {
            return $source;
        }

        $corrected = trailingslashit( $remote_source ) . $this->slug;

        if ( untrailingslashit( $source ) !== $corrected ) {
            $wp_filesystem->move( $source, $corrected );
        }

        return trailingslashit( $corrected );
    }

    public function add_check_update_link( $links ) {
        $url = wp_nonce_url(
            add_query_arg( 'ds_toolkit_check_update', '1', admin_url( 'plugins.php' ) ),
            'ds_toolkit_check_update'
        );
        $links[] = '<a href="' . esc_url( $url ) . '">Check for Updates</a>';
        return $links;
    }

    public function handle_check_update() {
        if ( ! isset( $_GET['ds_toolkit_check_update'] ) ) {
            return;
        }
        check_admin_referer( 'ds_toolkit_check_update' );
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        delete_transient( 'ds_toolkit_latest_release' );
        delete_site_transient( 'update_plugins' );
        wp_safe_redirect( admin_url( 'plugins.php' ) );
        exit;
    }

    /**
     * Prefer the attached release asset zip (correct folder name inside)
     * over GitHub's raw zipball (which uses owner-repo-hash as folder name).
     */
    private function get_download_url( $release ) {
        if ( ! empty( $release['assets'] ) ) {
            foreach ( $release['assets'] as $asset ) {
                if ( isset( $asset['name'] ) && $asset['name'] === $this->slug . '.zip' ) {
                    return $asset['browser_download_url'];
                }
            }
        }
        return $release['zipball_url'];
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
