<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Login_Branding {

    const ACADEMY_URL = 'https://designacademy.leagueapps.com/';

    private $settings;

    public function __construct( $settings = array() ) {
        $this->settings = $settings;
    }

    public function init() {
        add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_assets' ) );
        add_filter( 'login_headerurl', array( $this, 'login_logo_url' ) );
        add_filter( 'login_headertext', array( $this, 'login_logo_text' ) );
    }

    public function enqueue_login_assets() {
        wp_enqueue_style(
            'ds-toolkit-login',
            DS_TOOLKIT_URL . 'assets/css/login.css',
            array(),
            DS_TOOLKIT_VERSION
        );

        $logo_id  = ! empty( $this->settings['login_logo_id'] ) ? absint( $this->settings['login_logo_id'] ) : 0;
        $logo_url = $logo_id
            ? wp_get_attachment_image_url( $logo_id, 'full' )
            : DS_TOOLKIT_URL . 'assets/images/cropped-LA-circle-logo-1.png';

        wp_add_inline_style(
            'ds-toolkit-login',
            'body.login div#login h1 a {
                background-image: url("' . esc_url( $logo_url ) . '") !important;
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                width: 100%;
                height: 90px;
                margin: 0 auto 10px;
                padding: 0;
            }'
        );

        wp_enqueue_script(
            'ds-toolkit-login',
            DS_TOOLKIT_URL . 'assets/js/login.js',
            array(),
            DS_TOOLKIT_VERSION,
            true
        );

        wp_localize_script( 'ds-toolkit-login', 'dstLogin', array(
            'academyUrl' => self::ACADEMY_URL,
        ) );
    }

    public function login_logo_url() {
        return home_url( '/' );
    }

    public function login_logo_text() {
        return get_bloginfo( 'name' );
    }
}
