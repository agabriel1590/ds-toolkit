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

        // Always load admin styles (shared: header, tabs, cards)
        wp_enqueue_style(
            'ds-toolkit-admin',
            DS_TOOLKIT_URL . 'assets/css/admin.css',
            array(),
            DS_TOOLKIT_VERSION
        );

        if ( $active_tab === 'logos' ) {
            // Logo finder tab — load logo finder assets only
            $this->logo_finder->enqueue_assets();
        } else {
            // Features tab — load media picker and admin JS
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

        $active_tab   = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'features';
        $features_url = admin_url( 'options-general.php?page=ds-toolkit' );
        $logos_url    = admin_url( 'options-general.php?page=ds-toolkit&tab=logos' );
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
                <a href="<?php echo esc_url( $features_url ); ?>" class="dst-tab<?php echo $active_tab !== 'logos' ? ' is-active' : ''; ?>">
                    Features
                </a>
                <a href="<?php echo esc_url( $logos_url ); ?>" class="dst-tab<?php echo $active_tab === 'logos' ? ' is-active' : ''; ?>">
                    University Logo Finder
                </a>
            </div>

            <?php if ( $active_tab === 'logos' ) : ?>
                <?php require DS_TOOLKIT_PATH . 'admin/views/page-logo-finder.php'; ?>
            <?php else : ?>
                <?php
                $opts                               = get_option( 'ds_toolkit_settings', array() );
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
                require DS_TOOLKIT_PATH . 'admin/views/page-settings.php';
                ?>
            <?php endif; ?>

        </div>
        <?php
    }
}
