<?php
/**
 * Settings page view.
 * Variables available: $enabled, $logo_id, $logo_url, $default_url, $hide_fl_assistant,
 *                      $acf_css_vars_enabled, $acf_css_vars_mappings
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

        <div class="dst-card">
            <div class="dst-card-row">
                <div class="dst-card-icon"><span class="dashicons dashicons-cloud"></span></div>
                <div class="dst-card-info">
                    <strong>Hide Beaver Builder Cloud Icon for Non-LeagueApps Users</strong>
                    <span>Hides the FL Assistant cloud button in the Beaver Builder toolbar for all users except @leagueapps.com accounts.</span>
                </div>
                <div class="dst-toggle">
                    <input type="checkbox" id="hide_fl_assistant" name="ds_toolkit_settings[hide_fl_assistant]" value="1" <?php checked( $hide_fl_assistant ); ?>>
                    <label for="hide_fl_assistant"></label>
                </div>
            </div>
        </div>

        <!-- ACF CSS Variables -->
        <div class="dst-card">
            <div class="dst-card-row">
                <div class="dst-card-icon"><span class="dashicons dashicons-editor-code"></span></div>
                <div class="dst-card-info">
                    <strong>ACF Theme Options → CSS Variables</strong>
                    <span>Map ACF option fields to CSS custom properties output in <code>:root</code> on every page.</span>
                </div>
                <div class="dst-toggle">
                    <input type="checkbox" id="acf_css_vars_enabled" name="ds_toolkit_settings[acf_css_vars_enabled]" value="1" <?php checked( $acf_css_vars_enabled ); ?>>
                    <label for="acf_css_vars_enabled"></label>
                </div>
            </div>

            <div class="dst-card-row dst-mapping-wrap" id="dst-acf-mappings-row" <?php echo $acf_css_vars_enabled ? '' : 'style="display:none"'; ?>>
                <div class="dst-mapping-container">
                    <table class="dst-mapping-table" id="dst-mappings-table">
                        <thead>
                            <tr>
                                <th>ACF Field Name</th>
                                <th>CSS Variable</th>
                                <th>Fallback Value</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="dst-mappings-tbody">
                            <?php foreach ( $acf_css_vars_mappings as $i => $mapping ) : ?>
                            <tr class="dst-mapping-row">
                                <td><input type="text" class="regular-text" name="ds_toolkit_settings[acf_css_vars_mappings][<?php echo $i; ?>][acf_field]" value="<?php echo esc_attr( $mapping['acf_field'] ?? '' ); ?>" placeholder="acf_field_name"></td>
                                <td><input type="text" class="regular-text" name="ds_toolkit_settings[acf_css_vars_mappings][<?php echo $i; ?>][css_var]" value="<?php echo esc_attr( $mapping['css_var'] ?? '' ); ?>" placeholder="--css-variable-name"></td>
                                <td><input type="text" class="regular-text" name="ds_toolkit_settings[acf_css_vars_mappings][<?php echo $i; ?>][fallback]" value="<?php echo esc_attr( $mapping['fallback'] ?? '' ); ?>" placeholder="optional"></td>
                                <td><button type="button" class="button dst-remove-mapping" title="Remove">&#x2715;</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" class="button dst-add-mapping" id="dst-add-mapping" data-count="<?php echo count( $acf_css_vars_mappings ); ?>">+ Add Mapping</button>
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
