<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap dst-wrap dst-logo-finder-wrap">

    <div class="dst-header">
        <div class="dst-header-icon"><span class="dashicons dashicons-images-alt2"></span></div>
        <div class="dst-header-text">
            <h1>University Team Logo Finder</h1>
            <p>Search, select, and import team logos directly into the Media Library</p>
        </div>
        <span class="dst-badge">v<?php echo esc_html( DS_TOOLKIT_VERSION ); ?></span>
    </div>

    <div class="dst-lf-toolbar">
        <input type="text" id="dst-lf-search" placeholder="Search university name or mascot..." autocomplete="off">
        <div class="dst-lf-toolbar-actions">
            <span id="dst-lf-count">0 logos</span>
            <button type="button" class="button" id="dst-lf-select-all">Select All</button>
            <button type="button" class="button" id="dst-lf-clear">Clear</button>
        </div>
    </div>

    <div id="dst-lf-grid"></div>

    <div class="dst-lf-footer" id="dst-lf-footer" style="display:none">
        <div class="dst-lf-selected-count"><span id="dst-lf-selected-num">0</span> selected</div>
        <button type="button" class="button button-primary" id="dst-lf-import">Import Selected</button>
    </div>

    <div id="dst-lf-results"></div>

</div>
