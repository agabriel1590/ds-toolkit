<?php
/**
 * Global CSS tab content.
 * Rendered inside the shared wrap + header + tabs in DS_Toolkit_Admin::render_page().
 * Variables available: $global_css_enabled, $global_css_content
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<form method="post" action="options.php">
    <?php settings_fields( 'ds_toolkit_options' ); ?>

    <div class="dst-card">
        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-editor-code"></span></div>
            <div class="dst-card-info">
                <strong>Global CSS</strong>
                <span>Injected into <code>&lt;head&gt;</code> on every page of the site. Toggle off to disable output without losing your code.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="global_css_enabled" name="ds_toolkit_settings[global_css_enabled]" value="1" <?php checked( $global_css_enabled ); ?>>
                <label for="global_css_enabled"></label>
            </div>
        </div>
    </div>

    <div class="dst-code-editor-wrap">
        <textarea id="global_css_content" name="ds_toolkit_settings[global_css_content]" rows="40"><?php echo esc_textarea( $global_css_content ); ?></textarea>
    </div>

    <div class="dst-footer">
        <?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
        <span class="dst-footer-meta">
            <a href="https://github.com/agabriel1590/ds-toolkit" target="_blank" rel="noopener">GitHub</a>
            &nbsp;&middot;&nbsp; By Alipio Gabriel
        </span>
    </div>

</form>
