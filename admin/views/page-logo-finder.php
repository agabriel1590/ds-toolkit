<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap dst-wrap dst-logo-finder-wrap">

    <div class="dst-header">
        <div class="dst-header-icon"><span class="dashicons dashicons-images-alt2"></span></div>
        <div class="dst-header-text">
            <h1>University Team Logo Finder</h1>
            <p>Search, select, and import team logos into the Media Library</p>
        </div>
        <span class="dst-badge">v<?php echo esc_html( DS_TOOLKIT_VERSION ); ?></span>
    </div>

    <div class="dst-lf-toolbar">
        <input type="text" id="dst-lf-search" placeholder="Search by university or mascot name…" autocomplete="off">
        <span id="dst-lf-count"></span>
        <button type="button" class="button" id="dst-lf-select-all">Select All</button>
        <button type="button" class="button" id="dst-lf-clear">Clear</button>
    </div>

    <div id="dst-lf-grid"></div>

    <!-- Floating import bar — shown when at least one logo is selected -->
    <div id="dst-lf-import-bar">
        <span id="dst-lf-selected-label"></span>
        <button type="button" class="button button-primary" id="dst-lf-import">Import Selected</button>
    </div>

    <div id="dst-lf-results"></div>

</div>
