<?php
/**
 * MCP tab content.
 * Variables available: $mcp_url, $wp_version_ok, $app_passwords_ok, $is_local
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
<div class="dst-card">

    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-list-view"></span></div>
        <div class="dst-card-info">
            <strong>list_posts</strong>
            <span>List posts or pages. Filter by type, status, or keyword. Returns ID, title, status, URL, and dates.</span>
        </div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-visibility"></span></div>
        <div class="dst-card-info">
            <strong>get_post</strong>
            <span>Get the full content of a post or page by ID — including title, content, excerpt, status, and author.</span>
        </div>
        <span class="dst-mcp-cap">edit_posts</span>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-plus-alt"></span></div>
        <div class="dst-card-info">
            <strong>create_post</strong>
            <span>Create a new post or page. Specify title, content, excerpt, status (draft/publish), and type.</span>
        </div>
        <span class="dst-mcp-cap">publish_posts</span>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-edit"></span></div>
        <div class="dst-card-info">
            <strong>update_post</strong>
            <span>Edit an existing post or page. Only the fields you provide are changed — title, content, excerpt, or status.</span>
        </div>
        <span class="dst-mcp-cap">edit_post</span>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-trash"></span></div>
        <div class="dst-card-info">
            <strong>delete_post</strong>
            <span>Move a post to trash, or permanently delete it (with <code>force: true</code>).</span>
        </div>
        <span class="dst-mcp-cap">delete_post</span>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-admin-settings"></span></div>
        <div class="dst-card-info">
            <strong>get_toolkit_settings</strong>
            <span>Read all current DS Toolkit settings — feature toggles, column counts, template IDs, etc.</span>
        </div>
        <span class="dst-mcp-cap">manage_options</span>
    </div>

    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-hammer"></span></div>
        <div class="dst-card-info">
            <strong>update_toolkit_settings</strong>
            <span>Change one or more DS Toolkit settings by passing a key-value map. Only allowed keys are accepted.</span>
        </div>
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

        var desktopConfig = JSON.stringify({
            "mcpServers": {
                "ds-toolkit": {
                    "command": "npx",
                    "args": [
                        "mcp-remote",
                        mcpUrl,
                        "--header",
                        "Authorization:" + authHeader
                    ]
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
