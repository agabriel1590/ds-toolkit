<?php
/**
 * Settings page view.
 * Variables available: $enabled, $logo_id, $logo_url, $default_url
 */
if ( ! defined( 'ABSPATH' ) ) exit;
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

    <form method="post" action="options.php">
        <?php settings_fields( 'ds_toolkit_options' ); ?>

        <p class="dst-section-title">Features</p>
        <div class="dst-card">

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

            <div class="dst-card-row" id="dst-logo-row" <?php echo $enabled ? '' : 'style="display:none"'; ?>>
                <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
                <div class="dst-logo-label">
                    <strong>Login Logo</strong>
                    <span>Replaces the default LeagueApps logo on the login page.</span>
                </div>
                <div class="dst-logo-picker">
                    <div class="dst-logo-preview">
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
