<?php
/**
 * MCP tab content.
 * Variables: $mcp_url, $wp_version_ok, $app_passwords_ok, $is_local,
 *            $mcp_posts_pages_enabled, $mcp_cpt_enabled, $mcp_taxonomies_enabled,
 *            $mcp_acf_enabled, $mcp_toolkit_settings_enabled, $mcp_bb_enabled, $mcp_acf_schema_enabled
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<!-- Status bar -->
<div class="dst-mcp-status">
    <?php if ( $wp_version_ok && $app_passwords_ok ) : ?>
        <span class="dst-mcp-dot dst-mcp-dot--on"></span>
        <strong>MCP Endpoint Active<?php echo $is_local ? ' — Local' : ''; ?></strong>
        <code class="dst-mcp-endpoint"><?php echo esc_html( $mcp_url ); ?></code>
        <button type="button" class="button dst-copy-url-btn" data-url="<?php echo esc_attr( $mcp_url ); ?>">Copy URL</button>
        <?php if ( $is_local ) : ?>
        <span class="dst-mcp-note" style="width:100%;margin-top:6px;">&#9432; LocalWP detected — using <strong>http://</strong> to avoid Node.js SSL issues with local certificates. Application Passwords work because <code>WP_ENVIRONMENT_TYPE=local</code> is set in wp-config.php.</span>
        <?php endif; ?>
    <?php elseif ( ! $wp_version_ok ) : ?>
        <span class="dst-mcp-dot dst-mcp-dot--off"></span>
        <strong>Requires WordPress 5.6+</strong>
        <span class="dst-mcp-note">Application Passwords were added in WP 5.6. Please upgrade WordPress.</span>
    <?php else : ?>
        <span class="dst-mcp-dot dst-mcp-dot--off"></span>
        <strong>Application Passwords Disabled</strong>
        <span class="dst-mcp-note">Application Passwords are disabled on this site. Enable them to use MCP.</span>
    <?php endif; ?>
</div>

<!-- Claude Access Controls -->
<form method="post" action="options.php">
    <?php settings_fields( 'ds_toolkit_options' ); ?>

    <p class="dst-section-title" style="margin-top:24px;">Claude Access Controls</p>
    <div class="dst-card">

        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-admin-page"></span></div>
            <div class="dst-card-info">
                <strong>Posts &amp; Pages</strong>
                <span>Allow Claude to list, read, create, edit, and delete standard WordPress posts and pages.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="mcp_posts_pages_enabled" name="ds_toolkit_settings[mcp_posts_pages_enabled]" value="1" <?php checked( $mcp_posts_pages_enabled ); ?>>
                <label for="mcp_posts_pages_enabled"></label>
            </div>
        </div>

        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-database"></span></div>
            <div class="dst-card-info">
                <strong>Custom Post Types</strong>
                <span>Allow Claude to access CPTs — Events, Athletes, Staff, Teams, and any future custom post types registered on this site.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="mcp_cpt_enabled" name="ds_toolkit_settings[mcp_cpt_enabled]" value="1" <?php checked( $mcp_cpt_enabled ); ?>>
                <label for="mcp_cpt_enabled"></label>
            </div>
        </div>

        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-tag"></span></div>
            <div class="dst-card-info">
                <strong>Taxonomies</strong>
                <span>Allow Claude to list, create, edit, and delete taxonomy terms — Athlete Categories, Staff Categories, Team Categories, and any other taxonomies.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="mcp_taxonomies_enabled" name="ds_toolkit_settings[mcp_taxonomies_enabled]" value="1" <?php checked( $mcp_taxonomies_enabled ); ?>>
                <label for="mcp_taxonomies_enabled"></label>
            </div>
        </div>

        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-forms"></span></div>
            <div class="dst-card-info">
                <strong>ACF / Custom Fields</strong>
                <span>Allow Claude to read and update ACF field values on posts and CPT entries. Uses ACF's <code>get_fields()</code> / <code>update_field()</code> if ACF is active.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="mcp_acf_enabled" name="ds_toolkit_settings[mcp_acf_enabled]" value="1" <?php checked( $mcp_acf_enabled ); ?>>
                <label for="mcp_acf_enabled"></label>
            </div>
        </div>

        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-admin-settings"></span></div>
            <div class="dst-card-info">
                <strong>DS Toolkit Settings</strong>
                <span>Allow Claude to read and update DS Toolkit feature settings — toggles, column counts, Global CSS/JS, template IDs, etc.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="mcp_toolkit_settings_enabled" name="ds_toolkit_settings[mcp_toolkit_settings_enabled]" value="1" <?php checked( $mcp_toolkit_settings_enabled ); ?>>
                <label for="mcp_toolkit_settings_enabled"></label>
            </div>
        </div>

        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-art"></span></div>
            <div class="dst-card-info">
                <strong>Beaver Builder</strong>
                <span>Allow Claude to read and update Beaver Builder Global Style colors — Primary, Accent, Headings, Body, and all other named colors.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="mcp_bb_enabled" name="ds_toolkit_settings[mcp_bb_enabled]" value="1" <?php checked( $mcp_bb_enabled ); ?>>
                <label for="mcp_bb_enabled"></label>
            </div>
        </div>

        <div class="dst-card-row">
            <div class="dst-card-icon"><span class="dashicons dashicons-networking"></span></div>
            <div class="dst-card-info">
                <strong>ACF Schema <span style="color:#b32d2e;font-size:11px;font-weight:600;background:#fce8e8;padding:1px 6px;border-radius:3px;margin-left:4px;">&#9888; Destructive</span></strong>
                <span>Allow Claude to create, update, and delete ACF Post Types and Taxonomies. <strong>Restricted to @leagueapps.com accounts only</strong> — even if enabled, non-LeagueApps users cannot use these tools.</span>
            </div>
            <div class="dst-toggle">
                <input type="checkbox" id="mcp_acf_schema_enabled" name="ds_toolkit_settings[mcp_acf_schema_enabled]" value="1" <?php checked( $mcp_acf_schema_enabled ); ?>>
                <label for="mcp_acf_schema_enabled"></label>
            </div>
        </div>

    </div>

    <?php submit_button( 'Save Access Controls' ); ?>
</form>

<!-- What is this -->
<p class="dst-section-title" style="margin-top:24px;">What is this?</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-rest-api"></span></div>
        <div class="dst-card-info">
            <strong>Claude ↔ WordPress — no extra plugins needed</strong>
            <span>DS Toolkit exposes a <a href="https://modelcontextprotocol.io" target="_blank" rel="noopener">Model Context Protocol</a> (MCP) endpoint. Connect Claude Desktop or Claude Code to this URL and Claude can read posts, edit pages, and change DS Toolkit settings directly — just by asking in conversation.</span>
        </div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-shield"></span></div>
        <div class="dst-card-info">
            <strong>Secured by WordPress Application Passwords</strong>
            <span>Authentication uses <strong>Application Passwords</strong> (built into WP 5.6+) — a separate token that never exposes your login password and can be revoked at any time from your profile page.</span>
        </div>
    </div>
</div>

<!-- Setup steps -->
<p class="dst-section-title" style="margin-top:24px;">Setup Instructions</p>
<div class="dst-card">

    <div class="dst-card-row">
        <div class="dst-card-icon dst-step-num">1</div>
        <div class="dst-card-info">
            <strong>Create an Application Password</strong>
            <span>Go to <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" target="_blank"><strong>WP Admin → Users → Your Profile</strong></a>, scroll to the <strong>Application Passwords</strong> section, enter a name (e.g. <em>Claude MCP</em>), click <em>Add New Application Password</em>, and copy the generated password. <strong>Save it — it won't be shown again.</strong></span>
        </div>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon dst-step-num">2</div>
        <div class="dst-card-info">
            <strong>Generate your Claude config below</strong>
            <span>Enter your WordPress username and the Application Password. The tool will generate the exact JSON config to paste into Claude Desktop, or the command to run for Claude Code.</span>
        </div>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon dst-step-num">3</div>
        <div class="dst-card-info">
            <strong>Restart Claude and start talking to WordPress</strong>
            <span>After adding the config, restart Claude Desktop (or reload in Claude Code). You'll see <em>DS Toolkit</em> listed as an MCP server, and Claude will be able to use the tools listed below.</span>
        </div>
    </div>

</div>

<!-- Config generator -->
<p class="dst-section-title" style="margin-top:24px;">Generate Claude Config</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-admin-users"></span></div>
        <div class="dst-card-info">
            <strong>WordPress Username</strong>
            <span>Your WP admin username (the one you log in with)</span>
        </div>
        <div class="dst-field-inline" style="min-width:220px;">
            <input type="text" id="dst-mcp-username" class="regular-text" placeholder="your_username" style="width:100%;">
        </div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-lock"></span></div>
        <div class="dst-card-info">
            <strong>Application Password</strong>
            <span>The password from Step 1 — spaces are fine</span>
        </div>
        <div class="dst-field-inline" style="min-width:220px;">
            <input type="text" id="dst-mcp-apppass" class="regular-text" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" style="width:100%;">
        </div>
    </div>
    <div class="dst-card-row" style="justify-content:flex-end;">
        <button type="button" class="button button-primary" id="dst-mcp-generate">Generate Config</button>
    </div>

    <div id="dst-mcp-output" style="display:none;">

        <div class="dst-card-row dst-mcp-config-block">
            <div style="width:100%;">
                <p class="dst-mcp-config-label">
                    <strong>Claude Desktop</strong>
                    <span>Add inside <code>mcpServers</code> in <code>~/Library/Application Support/Claude/claude_desktop_config.json</code></span>
                </p>
                <div class="dst-mcp-textarea-wrap">
                    <textarea id="dst-mcp-config-desktop" class="dst-mcp-textarea" readonly rows="14"></textarea>
                    <button type="button" class="button dst-mcp-copy" data-target="dst-mcp-config-desktop">Copy</button>
                </div>
            </div>
        </div>

        <div class="dst-card-row dst-mcp-config-block">
            <div style="width:100%;">
                <p class="dst-mcp-config-label">
                    <strong>Claude Code CLI</strong>
                    <span>Run this command in your terminal once to register the server</span>
                </p>
                <div class="dst-mcp-textarea-wrap">
                    <textarea id="dst-mcp-config-cli" class="dst-mcp-textarea" readonly rows="4"></textarea>
                    <button type="button" class="button dst-mcp-copy" data-target="dst-mcp-config-cli">Copy</button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Available tools -->
<p class="dst-section-title" style="margin-top:24px;">Available Tools</p>

<p class="dst-section-title dst-section-subtitle">Posts &amp; Pages</p>
<div class="dst-card" style="<?php echo $mcp_posts_pages_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info"><strong>list_posts</strong><span>List posts or pages. Filter by status or keyword.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-visibility"></span></div>
        <div class="dst-card-info"><strong>get_post</strong><span>Get full content of a post by ID — title, content, excerpt, status, author.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info"><strong>create_post</strong><span>Create a new post or page with title, content, status.</span></div>
        <span class="dst-mcp-cap">publish_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-edit"></span></div>
        <div class="dst-card-info"><strong>update_post</strong><span>Edit title, content, excerpt, or status of an existing post.</span></div>
        <span class="dst-mcp-cap">edit_post</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-trash"></span></div>
        <div class="dst-card-info"><strong>delete_post</strong><span>Trash or permanently delete a post.</span></div>
        <span class="dst-mcp-cap">delete_post</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">Custom Post Types</p>
<div class="dst-card" style="<?php echo $mcp_cpt_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-database"></span></div>
        <div class="dst-card-info"><strong>list_post_types</strong><span>Discover all registered CPTs — Events, Athletes, Staff, Teams, and any future types.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info"><strong>list_posts</strong><span>Same tool as above — pass <code>post_type: "athletes"</code> (or any CPT slug) to query CPT entries.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info"><strong>create_post / update_post / delete_post</strong><span>Same tools as above — pass the CPT slug as <code>post_type</code> to create/edit/delete CPT entries.</span></div>
        <span class="dst-mcp-cap">publish_posts</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">Taxonomies</p>
<div class="dst-card" style="<?php echo $mcp_taxonomies_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-tag"></span></div>
        <div class="dst-card-info"><strong>list_taxonomies</strong><span>List all public taxonomies and which post types they belong to.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info"><strong>list_terms</strong><span>List terms in a taxonomy (e.g. athlete-category). Supports search and pagination.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-visibility"></span></div>
        <div class="dst-card-info"><strong>get_term</strong><span>Get a single term by ID or slug.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info"><strong>create_term</strong><span>Add a new term to a taxonomy with optional slug, description, and parent.</span></div>
        <span class="dst-mcp-cap">manage_categories</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-edit"></span></div>
        <div class="dst-card-info"><strong>update_term</strong><span>Edit a term's name, slug, or description.</span></div>
        <span class="dst-mcp-cap">manage_categories</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-trash"></span></div>
        <div class="dst-card-info"><strong>delete_term</strong><span>Remove a term from a taxonomy.</span></div>
        <span class="dst-mcp-cap">manage_categories</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">ACF / Custom Fields</p>
<div class="dst-card" style="<?php echo $mcp_acf_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-forms"></span></div>
        <div class="dst-card-info"><strong>get_post_fields</strong><span>Read all ACF fields on a post or CPT entry. Falls back to post meta if ACF is not active.</span></div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-edit"></span></div>
        <div class="dst-card-info"><strong>update_post_fields</strong><span>Update ACF field values by passing a <code>field_name → value</code> map. Uses <code>update_field()</code> if ACF active.</span></div>
        <span class="dst-mcp-cap">edit_post</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">DS Toolkit Settings</p>
<div class="dst-card" style="<?php echo $mcp_toolkit_settings_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-admin-settings"></span></div>
        <div class="dst-card-info"><strong>get_toolkit_settings</strong><span>Read all DS Toolkit settings — feature toggles, column counts, template IDs, etc.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-hammer"></span></div>
        <div class="dst-card-info"><strong>update_toolkit_settings</strong><span>Update feature toggles, Global CSS/JS, column counts, and other settings.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">ACF Schema <span style="color:#b32d2e;font-size:11px;">&#9888; @leagueapps.com only</span></p>
<div class="dst-card" style="<?php echo $mcp_acf_schema_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info"><strong>acf_list_post_types</strong><span>List all ACF-managed post types — returns key, slug, labels, and settings.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info"><strong>acf_create_post_type</strong><span>Create a new CPT via ACF — slug, labels, supports, taxonomies, visibility.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-edit"></span></div>
        <div class="dst-card-info"><strong>acf_update_post_type</strong><span>Update an existing ACF post type by key. Only provided fields are changed.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-trash"></span></div>
        <div class="dst-card-info"><strong>acf_delete_post_type</strong><span>Permanently delete an ACF post type by key. Irreversible.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info"><strong>acf_list_taxonomies</strong><span>List all ACF-managed taxonomies — returns key, slug, labels, and associated post types.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info"><strong>acf_create_taxonomy</strong><span>Create a new taxonomy via ACF — slug, labels, hierarchical, associated post types.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-edit"></span></div>
        <div class="dst-card-info"><strong>acf_update_taxonomy</strong><span>Update an existing ACF taxonomy by key. Only provided fields are changed.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-trash"></span></div>
        <div class="dst-card-info"><strong>acf_delete_taxonomy</strong><span>Permanently delete an ACF taxonomy by key. Irreversible.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">ACF Field Groups <span style="color:#b32d2e;font-size:11px;">&#9888; @leagueapps.com only</span></p>
<div class="dst-card" style="<?php echo $mcp_acf_schema_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info"><strong>acf_list_field_groups</strong><span>List all ACF field groups — returns key, title, active status, and location rules.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-search"></span></div>
        <div class="dst-card-info"><strong>acf_get_field_group</strong><span>Get a single field group by key, including all its fields (key, label, name, type).</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info"><strong>acf_create_field_group</strong><span>Create a new ACF field group with optional location rules and fields in a single call.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-edit"></span></div>
        <div class="dst-card-info"><strong>acf_update_field_group</strong><span>Update an existing field group — title, location, position, label placement, or active state.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-trash"></span></div>
        <div class="dst-card-info"><strong>acf_delete_field_group</strong><span>Permanently delete a field group and all its fields. Irreversible.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">ACF Options Pages <span style="color:#b32d2e;font-size:11px;">&#9888; @leagueapps.com only &bull; ACF Pro 6.2+</span></p>
<div class="dst-card" style="<?php echo $mcp_acf_schema_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info"><strong>acf_list_options_pages</strong><span>List all ACF Pro options pages — returns key, title, menu_slug, and parent_slug.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info"><strong>acf_create_options_page</strong><span>Create a new ACF Pro options page with title, menu slug, parent, capability, and optional redirect.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-trash"></span></div>
        <div class="dst-card-info"><strong>acf_delete_options_page</strong><span>Permanently delete an ACF Pro options page. Irreversible.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
</div>

<p class="dst-section-title dst-section-subtitle">Beaver Builder</p>
<div class="dst-card" style="<?php echo $mcp_bb_enabled ? '' : 'opacity:.5;'; ?>">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-art"></span></div>
        <div class="dst-card-info"><strong>get_bb_global_colors</strong><span>Read all BB Global Style colors — returns a label → hex map (e.g. Primary, Accent, Headings).</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-color-picker"></span></div>
        <div class="dst-card-info"><strong>update_bb_global_colors</strong><span>Update named BB Global Style colors by label. Flushes BB CSS cache automatically so changes are live immediately.</span></div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>
</div>

<!-- Example prompts -->
<p class="dst-section-title" style="margin-top:24px;">Example Claude Prompts</p>
<div class="dst-card">
    <div class="dst-card-row dst-shortcode-docs">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-chat"></span></div>
        <div class="dst-card-info">
            <strong>Once connected, try asking Claude:</strong>
            <p>List all published pages on the site</p>
            <code>List all published pages on this WordPress site</code>
            <p>Edit a post</p>
            <code>Update the title of post ID 42 to "Our Updated Programs"</code>
            <p>Toggle a feature</p>
            <code>Disable the [current_year] shortcode in DS Toolkit settings</code>
            <p>Read and edit CSS</p>
            <code>Show me the Global CSS in DS Toolkit, then add a rule that makes all h2 headings #333</code>
        </div>
    </div>
</div>

<script type="text/javascript">
(function ($) {
    'use strict';

    var mcpUrl = <?php echo wp_json_encode( $mcp_url ); ?>;

    // Copy endpoint URL button
    $('.dst-copy-url-btn').on('click', function () {
        copyText(mcpUrl, $(this));
    });

    // Generate config
    $('#dst-mcp-generate').on('click', function () {
        var username = $('#dst-mcp-username').val().trim();
        var apppass  = $('#dst-mcp-apppass').val().trim();

        if ( ! username || ! apppass ) {
            alert('Please enter both your WordPress username and Application Password.');
            return;
        }

        // Normalise app password: remove spaces (WP accepts both forms)
        var credentials = username + ':' + apppass.replace(/\s+/g, '');
        var encoded     = btoa( unescape( encodeURIComponent( credentials ) ) );
        var authHeader  = 'Basic ' + encoded;

        var isLocal  = mcpUrl.indexOf('http://') === 0;
        var mcpArgs  = [ 'mcp-remote', mcpUrl ];
        if ( isLocal ) mcpArgs.push( '--allow-http' );
        mcpArgs.push( '--header', 'Authorization:' + authHeader );

        var desktopConfig = JSON.stringify({
            "mcpServers": {
                "ds-toolkit": {
                    "command": "npx",
                    "args": mcpArgs
                }
            }
        }, null, 2);

        var cliCommand =
            'claude mcp add ds-toolkit \\\n' +
            '  --transport http \\\n' +
            '  --header "Authorization: ' + authHeader + '" \\\n' +
            '  ' + mcpUrl;

        $('#dst-mcp-config-desktop').val( desktopConfig );
        $('#dst-mcp-config-cli').val( cliCommand );
        $('#dst-mcp-output').slideDown(200);
    });

    // Copy buttons
    $(document).on('click', '.dst-mcp-copy', function () {
        var targetId = $(this).data('target');
        var text     = $('#' + targetId).val();
        copyText(text, $(this));
    });

    function copyText(text, $btn) {
        var original = $btn.text();
        if ( navigator.clipboard ) {
            navigator.clipboard.writeText(text).then(function () {
                $btn.text('Copied!');
                setTimeout(function () { $btn.text(original); }, 1500);
            });
        } else {
            var el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            $btn.text('Copied!');
            setTimeout(function () { $btn.text(original); }, 1500);
        }
    }

}(jQuery));
</script>
