<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class DS_Toolkit_Admin {
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function add_menu() {
        add_options_page( 'DS Toolkit', 'DS Toolkit', 'manage_options', 'ds-toolkit', array( $this, 'render_page' ) );
    }

    public function register_settings() {
        register_setting( 'ds_toolkit_options', 'ds_toolkit_settings' );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'settings_page_ds-toolkit' ) return;
        wp_enqueue_media();
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $opts        = get_option( 'ds_toolkit_settings', array() );
        $enabled     = ! empty( $opts['enable_login_branding'] );
        $logo_id     = ! empty( $opts['login_logo_id'] ) ? absint( $opts['login_logo_id'] ) : 0;
        $logo_url    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        $default_url = DS_TOOLKIT_URL . 'assets/images/cropped-LA-circle-logo-1.png';
        ?>
        <div class="wrap dst-wrap">

            <style>
                .dst-wrap { max-width: 800px; }
                .dst-header {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    padding: 24px 28px;
                    background: #1d2327;
                    border-radius: 8px;
                    margin: 20px 0 24px;
                }
                .dst-header-icon {
                    width: 42px;
                    height: 42px;
                    background: #2271b1;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .dst-header-icon .dashicons {
                    color: #fff;
                    font-size: 22px;
                    width: 22px;
                    height: 22px;
                    line-height: 1;
                }
                .dst-header-text h1 {
                    color: #fff;
                    font-size: 18px;
                    font-weight: 600;
                    margin: 0 0 2px;
                    padding: 0;
                    line-height: 1.3;
                }
                .dst-header-text p {
                    color: #8c9aaa;
                    font-size: 12px;
                    margin: 0;
                }
                .dst-badge {
                    margin-left: auto;
                    background: #2271b1;
                    color: #fff;
                    font-size: 11px;
                    font-weight: 600;
                    padding: 3px 10px;
                    border-radius: 20px;
                    letter-spacing: 0.3px;
                }
                .dst-section-title {
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.8px;
                    color: #8c9aaa;
                    margin: 0 0 10px;
                }
                .dst-card {
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    margin-bottom: 12px;
                    overflow: hidden;
                }
                .dst-card-row {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    padding: 18px 22px;
                }
                .dst-card-row + .dst-card-row {
                    border-top: 1px solid #f0f2f4;
                }
                .dst-card-icon {
                    width: 36px;
                    height: 36px;
                    background: #f0f6ff;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .dst-card-icon .dashicons {
                    color: #2271b1;
                    font-size: 18px;
                    width: 18px;
                    height: 18px;
                    line-height: 1;
                }
                .dst-card-info { flex: 1; }
                .dst-card-info strong {
                    display: block;
                    font-size: 13px;
                    color: #1d2327;
                    margin-bottom: 2px;
                }
                .dst-card-info span {
                    font-size: 12px;
                    color: #8c9aaa;
                    line-height: 1.4;
                }
                /* Toggle switch */
                .dst-toggle { flex-shrink: 0; }
                .dst-toggle input { display: none; }
                .dst-toggle label {
                    display: block;
                    width: 40px;
                    height: 22px;
                    background: #c8d0d8;
                    border-radius: 22px;
                    cursor: pointer;
                    position: relative;
                    transition: background 0.2s;
                }
                .dst-toggle label::after {
                    content: '';
                    position: absolute;
                    top: 3px;
                    left: 3px;
                    width: 16px;
                    height: 16px;
                    background: #fff;
                    border-radius: 50%;
                    transition: left 0.2s;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
                }
                .dst-toggle input:checked + label { background: #2271b1; }
                .dst-toggle input:checked + label::after { left: 21px; }
                /* Logo picker */
                .dst-logo-picker {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    flex: 1;
                }
                .dst-logo-preview {
                    width: 64px;
                    height: 64px;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    overflow: hidden;
                    background: #f8f9fa;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                .dst-logo-preview img {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }
                .dst-logo-actions { display: flex; flex-direction: column; gap: 6px; }
                .dst-logo-actions .button { font-size: 12px; height: 28px; line-height: 26px; padding: 0 10px; }
                .dst-logo-label { flex: 1; }
                .dst-logo-label strong {
                    display: block;
                    font-size: 13px;
                    color: #1d2327;
                    margin-bottom: 2px;
                }
                .dst-logo-label span { font-size: 12px; color: #8c9aaa; }
                /* Footer */
                .dst-footer {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-top: 20px;
                }
                .dst-footer .button-primary {
                    height: 36px;
                    line-height: 34px;
                    padding: 0 20px;
                    font-size: 13px;
                    border-radius: 6px;
                }
                .dst-footer-meta { font-size: 11px; color: #c8d0d8; }
                .dst-footer-meta a { color: #c8d0d8; text-decoration: none; }
                .dst-footer-meta a:hover { color: #8c9aaa; }
            </style>

            <div class="dst-header">
                <div class="dst-header-icon"><span class="dashicons dashicons-hammer"></span></div>
                <div class="dst-header-text">
                    <h1>DS Toolkit</h1>
                    <p>Design Shop custom features and build toolkit</p>
                </div>
                <span class="dst-badge">v<?php echo esc_html( DS_TOOLKIT_VERSION ); ?></span>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'ds_toolkit_options' ); ?>

                <p class="dst-section-title">Features</p>
                <div class="dst-card">

                    <!-- Toggle row -->
                    <div class="dst-card-row">
                        <div class="dst-card-icon"><span class="dashicons dashicons-admin-appearance"></span></div>
                        <div class="dst-card-info">
                            <strong>LeagueApps Custom Login</strong>
                            <span>Custom logo, "Powered by LeagueApps Design Shop" branding, and support link on the WP login page.</span>
                        </div>
                        <div class="dst-toggle">
                            <input type="checkbox" id="enable_login_branding" name="ds_toolkit_settings[enable_login_branding]" value="1" <?php checked( $enabled ); ?>>
                            <label for="enable_login_branding"></label>
                        </div>
                    </div>

                    <!-- Logo picker row -->
                    <div class="dst-card-row">
                        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
                        <div class="dst-logo-label">
                            <strong>Login Logo</strong>
                            <span>Replaces the default LeagueApps logo on the login page.</span>
                        </div>
                        <div class="dst-logo-picker">
                            <div class="dst-logo-preview" id="dst-logo-preview">
                                <img id="dst-logo-img" src="<?php echo esc_url( $logo_url ?: $default_url ); ?>" alt="Login logo">
                            </div>
                            <div class="dst-logo-actions">
                                <input type="hidden" id="dst-logo-id" name="ds_toolkit_settings[login_logo_id]" value="<?php echo esc_attr( $logo_id ); ?>">
                                <button type="button" class="button" id="dst-logo-select">Select Logo</button>
                                <button type="button" class="button" id="dst-logo-remove" <?php echo $logo_id ? '' : 'style="display:none"'; ?>>Use Default</button>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="dst-footer">
                    <?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
                    <span class="dst-footer-meta">
                        <a href="https://github.com/agabriel1590/ds-toolkit" target="_blank" rel="noopener">GitHub</a>
                        &nbsp;&middot;&nbsp; By Alipio Gabriel
                    </span>
                </div>

            </form>
        </div>

        <script>
        (function($){
            var defaultSrc = <?php echo json_encode( $default_url ); ?>;
            var frame;

            $('#dst-logo-select').on('click', function(e){
                e.preventDefault();
                if ( frame ) { frame.open(); return; }
                frame = wp.media({
                    title: 'Select Login Logo',
                    button: { text: 'Use this logo' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#dst-logo-id').val( attachment.id );
                    $('#dst-logo-img').attr( 'src', attachment.url );
                    $('#dst-logo-remove').show();
                });
                frame.open();
            });

            $('#dst-logo-remove').on('click', function(e){
                e.preventDefault();
                $('#dst-logo-id').val('');
                $('#dst-logo-img').attr('src', defaultSrc);
                $(this).hide();
            });
        }(jQuery));
        </script>
        <?php
    }
}
