<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Login_Branding {
    public function init() {
        add_action( 'login_enqueue_scripts', array( $this, 'custom_login_branding' ) );
        add_action( 'login_footer', array( $this, 'powered_by_text' ) );
        add_action( 'login_footer', array( $this, 'support_below_form' ) );
        add_filter( 'login_headerurl', array( $this, 'login_logo_url' ) );
        add_filter( 'login_headertext', array( $this, 'login_logo_text' ) );
    }

    public function custom_login_branding() {
        $opts     = get_option( 'ds_toolkit_settings', array() );
        $logo_id  = ! empty( $opts['login_logo_id'] ) ? absint( $opts['login_logo_id'] ) : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : DS_TOOLKIT_URL . 'assets/images/cropped-LA-circle-logo-1.png';
        ?><style>body.login div#login h1 a{background-image:url('<?php echo esc_url($logo_url);?>') !important;background-size:contain;background-repeat:no-repeat;background-position:center;width:100%;height:90px;margin:0 auto 10px;padding:0;}.ag-login-powered-by{text-align:center;font-size:13px;line-height:1.4;margin:0 0 18px;opacity:0.85;}</style><?php
    }

    public function powered_by_text() {
        ?><script>document.addEventListener('DOMContentLoaded',function(){var h1=document.querySelector('#login h1');if(!h1)return;if(document.querySelector('.ag-login-powered-by'))return;var t=document.createElement('div');t.className='ag-login-powered-by';t.textContent='Powered by LeagueApps Design Shop';h1.parentNode.insertBefore(t,h1.nextSibling);});</script><?php
    }

    public function support_below_form() {
        ?><style>.ag-login-support{text-align:center;font-size:13px;line-height:1.45;margin-top:14px;opacity:0.9;}.ag-login-support a{text-decoration:underline;}</style><script>document.addEventListener('DOMContentLoaded',function(){var form=document.querySelector('#loginform');if(!form)return;if(document.querySelector('.ag-login-support'))return;var wrap=document.createElement('div');wrap.className='ag-login-support';wrap.innerHTML='Need help?<br>Visit <a href="https://designacademy.leagueapps.com/" target="_blank" rel="noopener noreferrer">Design Shop Academy</a> to manage your site.';form.parentNode.insertBefore(wrap,form.nextSibling);});</script><?php
    }

    public function login_logo_url(){return home_url('/');}
    public function login_logo_text(){return get_bloginfo('name');}
}