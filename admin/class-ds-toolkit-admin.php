<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit_Admin {

    private $logo_finder;

    public function init() {
        add_action( 'admin_menu',            array( $this, 'add_menu' ) );
        add_action( 'admin_init',            array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        require_once DS_TOOLKIT_PATH . 'admin/class-ds-logo-finder.php';
        $this->logo_finder = new DS_Logo_Finder();
        $this->logo_finder->init();
    }

    public function add_menu() {
        add_options_page( 'DS Toolkit', 'DS Toolkit', 'manage_options', 'ds-toolkit', array( $this, 'render_page' ) );
    }

    public function register_settings() {
        register_setting( 'ds_toolkit_options', 'ds_toolkit_settings' );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'settings_page_ds-toolkit' ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'features';

        // Shared admin styles (header, tabs, cards, toggles)
        wp_enqueue_style(
            'ds-toolkit-admin',
            DS_TOOLKIT_URL . 'assets/css/admin.css',
            array(),
            DS_TOOLKIT_VERSION
        );

        if ( $active_tab === 'logos' ) {
            $this->logo_finder->enqueue_assets();

        } elseif ( $active_tab === 'global-css' ) {
            $settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
            if ( $settings ) {
                wp_add_inline_script(
                    'code-editor',
                    sprintf(
                        'jQuery( function() { wp.codeEditor.initialize( "global_css_content", %s ); } );',
                        wp_json_encode( $settings )
                    )
                );
            }

        } elseif ( $active_tab === 'global-js' ) {
            $settings = wp_enqueue_code_editor( array( 'type' => 'text/javascript' ) );
            if ( $settings ) {
                wp_add_inline_script(
                    'code-editor',
                    sprintf(
                        'jQuery( function() { wp.codeEditor.initialize( "global_js_content", %s ); } );',
                        wp_json_encode( $settings )
                    )
                );
            }

        } else {
            // Features tab
            wp_enqueue_media();
            wp_enqueue_script(
                'ds-toolkit-admin',
                DS_TOOLKIT_URL . 'assets/js/admin.js',
                array( 'jquery', 'media-upload' ),
                DS_TOOLKIT_VERSION,
                true
            );
            wp_localize_script( 'ds-toolkit-admin', 'dstAdmin', array(
                'defaultLogoUrl' => DS_TOOLKIT_URL . 'assets/images/cropped-LA-circle-logo-1.png',
            ) );
        }
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab    = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'features';
        $base_url      = admin_url( 'options-general.php?page=ds-toolkit' );
        ?>
        <div class="wrap dst-wrap">

            <div class="dst-header">
                <div class="dst-header-icon"><span class="dashicons dashicons-hammer"></span></div>
                <div class="dst-header-text">
                    <h1>DS Toolkit</h1>
                    <p>Design Shop custom features and build toolkit</p>
                </div>
                <span class="dst-badge">v<?php echo esc_html( DS_TOOLKIT_VERSION ); ?></span>
            </div>

            <div class="dst-tabs">
                <a href="<?php echo esc_url( $base_url ); ?>" class="dst-tab<?php echo $active_tab === 'features' ? ' is-active' : ''; ?>">
                    Features
                </a>
                <a href="<?php echo esc_url( $base_url . '&tab=logos' ); ?>" class="dst-tab<?php echo $active_tab === 'logos' ? ' is-active' : ''; ?>">
                    University Logo Finder
                </a>
                <a href="<?php echo esc_url( $base_url . '&tab=global-css' ); ?>" class="dst-tab<?php echo $active_tab === 'global-css' ? ' is-active' : ''; ?>">
                    Global CSS
                </a>
                <a href="<?php echo esc_url( $base_url . '&tab=global-js' ); ?>" class="dst-tab<?php echo $active_tab === 'global-js' ? ' is-active' : ''; ?>">
                    Global JS
                </a>
            </div>

            <?php
            $opts = get_option( 'ds_toolkit_settings', array() );

            if ( $active_tab === 'logos' ) {
                require DS_TOOLKIT_PATH . 'admin/views/page-logo-finder.php';

            } elseif ( $active_tab === 'global-css' ) {
                $global_css_enabled = ! empty( $opts['global_css_enabled'] );
                $global_css_content = isset( $opts['global_css_content'] ) ? $opts['global_css_content'] : '';
                require DS_TOOLKIT_PATH . 'admin/views/page-global-css.php';

            } elseif ( $active_tab === 'global-js' ) {
                $global_js_enabled = ! empty( $opts['global_js_enabled'] );
                $global_js_content = isset( $opts['global_js_content'] ) ? $opts['global_js_content'] : '';
                require DS_TOOLKIT_PATH . 'admin/views/page-global-js.php';

            } else {
                $enabled                            = ! empty( $opts['enable_login_branding'] );
                $logo_id                            = ! empty( $opts['login_logo_id'] ) ? absint( $opts['login_logo_id'] ) : 0;
                $logo_url                           = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
                $default_url                        = DS_TOOLKIT_URL . 'assets/images/cropped-LA-circle-logo-1.png';
                $hide_fl_assistant                  = ! empty( $opts['hide_fl_assistant'] );
                $acf_css_vars_enabled               = ! empty( $opts['acf_css_vars_enabled'] );
                $acf_css_vars_mappings              = ! empty( $opts['acf_css_vars_mappings'] ) ? $opts['acf_css_vars_mappings'] : array();
                $getsubmenu_enabled                 = ! empty( $opts['getsubmenu_enabled'] );
                $current_year_enabled               = ! empty( $opts['current_year_enabled'] );
                $forminator_email_partner_enabled   = ! empty( $opts['forminator_email_partner_enabled'] );
                $forminator_email_partner_fallback  = ! empty( $opts['forminator_email_partner_fallback'] )
                    ? $opts['forminator_email_partner_fallback']
                    : 'designshop@leagueapps.com';
                $child_pages_enabled         = ! empty( $opts['child_pages_enabled'] );
                $child_pages_template_id     = ! empty( $opts['child_pages_template_id'] ) ? $opts['child_pages_template_id'] : '56369';
                $child_pages_columns         = ! empty( $opts['child_pages_columns'] )        ? (int) $opts['child_pages_columns']        : 3;
                $child_pages_columns_tablet  = ! empty( $opts['child_pages_columns_tablet'] ) ? (int) $opts['child_pages_columns_tablet'] : 2;
                $child_pages_columns_mobile  = ! empty( $opts['child_pages_columns_mobile'] ) ? (int) $opts['child_pages_columns_mobile'] : 1;
                require DS_TOOLKIT_PATH . 'admin/views/page-settings.php';
            }
            ?>

        </div>
        <?php
    }
}
