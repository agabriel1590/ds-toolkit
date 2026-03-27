<?php
/**
 * Global CSS tab — utility class reference.
 * The CSS is managed directly in includes/defaults/global-css.css (plugin file).
 * This page documents all available utility classes for developers and page builders.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="dst-card" style="margin-bottom:20px;">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-editor-code"></span></div>
        <div class="dst-card-info">
            <strong>Global CSS — Plugin Managed</strong>
            <span>Styles are defined in <code>includes/defaults/global-css.css</code> inside the plugin. Add or update classes there and they apply to all sites on the next plugin update. Use the utility classes below directly in Beaver Builder row/column/module CSS class fields.</span>
        </div>
    </div>
</div>

<!-- Image Heights -->
<p class="dst-section-title" style="margin-top:20px;">Image Heights</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="dst-card-info"><strong>.height-tall</strong><span>Sets images and carousels to <code>65vh</code> height — full-bleed hero banners.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="dst-card-info"><strong>.height-medium</strong><span>Sets images and carousels to <code>45vh</code> height — mid-size feature sections.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="dst-card-info"><strong>.height-small</strong><span>Sets images and carousels to <code>35vh</code> height — compact image rows.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="dst-card-info"><strong>.height-avatar</strong><span>Sets images to <code>350px</code> fixed height, top-center crop — ideal for headshot/profile photos.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="dst-card-info"><strong>.small-icon</strong><span>Limits images to <code>55px</code> max height — for small logos or inline icons.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="dst-card-info"><strong>.clip-photo</strong><span>Applies a hexagonal clip-path to images — geometric photo cutout effect.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-format-image"></span></div>
        <div class="dst-card-info"><strong>.add-white-bg</strong><span>Adds white background, border-radius, and padding to images — clean logo display on dark rows.</span></div>
    </div>
</div>

<!-- Text Colors -->
<p class="dst-section-title" style="margin-top:20px;">Text Colors</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-editor-textcolor"></span></div>
        <div class="dst-card-info"><strong>.color-white</strong><span>Forces text to <code>--fl-global-white</code> — all child elements included.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-editor-textcolor"></span></div>
        <div class="dst-card-info"><strong>.color-primary</strong><span>Forces text to <code>--fl-global-primary</code>.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-editor-textcolor"></span></div>
        <div class="dst-card-info"><strong>.color-accent</strong><span>Forces text to <code>--fl-global-accent</code>.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-editor-textcolor"></span></div>
        <div class="dst-card-info"><strong>.color-dark</strong><span>Forces text to <code>--fl-global-dark-background</code>.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-editor-textcolor"></span></div>
        <div class="dst-card-info"><strong>.quote</strong><span>Bold (<code>900</code> weight) primary-color text — pull quotes or highlighted statements.</span></div>
    </div>
</div>

<!-- Outline Text -->
<p class="dst-section-title" style="margin-top:20px;">Outline Text</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-text"></span></div>
        <div class="dst-card-info"><strong>.outline-white-text</strong><span>Transparent fill with <code>2px</code> white stroke — outlined headline on dark backgrounds.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-text"></span></div>
        <div class="dst-card-info"><strong>.outline-primary-text</strong><span>Transparent fill with <code>2px</code> primary color stroke.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-text"></span></div>
        <div class="dst-card-info"><strong>.outline-accent-text</strong><span>Transparent fill with <code>2px</code> accent color stroke.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-text"></span></div>
        <div class="dst-card-info"><strong>.h2-home</strong><span>Sets h2 to <code>76px</code> desktop / <code>46px</code> mobile — oversized hero headline.</span></div>
    </div>
</div>

<!-- Layout Utilities -->
<p class="dst-section-title" style="margin-top:20px;">Layout Utilities</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-layout"></span></div>
        <div class="dst-card-info"><strong>.overflow-hidden</strong><span>Clips overflowing content on the row and its direct child — useful for image zoom hover effects.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-layout"></span></div>
        <div class="dst-card-info"><strong>.border-radius</strong><span>Applies <code>10px</code> border-radius to the element and all children.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-layout"></span></div>
        <div class="dst-card-info"><strong>.zindex-medium</strong><span>Sets <code>position: relative; z-index: 100</code> — brings element above standard stacking context.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-layout"></span></div>
        <div class="dst-card-info"><strong>.zindex-high</strong><span>Sets <code>position: relative; z-index: 1000</code> — brings element above overlays and most widgets.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-layout"></span></div>
        <div class="dst-card-info"><strong>.grid-list</strong><span>Converts a UABB Info List into a <code>3-column</code> card grid (2 tablet, 1 mobile) with card styling and hover background.</span></div>
    </div>
</div>

<!-- Blend Modes -->
<p class="dst-section-title" style="margin-top:20px;">Blend Modes</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-admin-appearance"></span></div>
        <div class="dst-card-info"><strong>.text-mix-blend-difference</strong><span>Applies <code>mix-blend-mode: difference</code> — inverts colors against the background for contrast text effects.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-admin-appearance"></span></div>
        <div class="dst-card-info"><strong>.mix-blend-multiply</strong><span>Applies <code>mix-blend-mode: multiply</code> on pseudo-elements — blends image overlays naturally with background.</span></div>
    </div>
</div>

<!-- Menu -->
<p class="dst-section-title" style="margin-top:20px;">Menu</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-menu"></span></div>
        <div class="dst-card-info"><strong>.menu-justify</strong><span>Flexes the primary menu to the right with full-width stretch — for horizontal right-aligned nav layouts.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-menu"></span></div>
        <div class="dst-card-info"><strong>.justify-menu</strong><span>Distributes menu items evenly across full width with equal spacing and borders.</span></div>
    </div>
</div>

<!-- Sticky Header -->
<p class="dst-section-title" style="margin-top:20px;">Sticky Header</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-admin-appearance"></span></div>
        <div class="dst-card-info"><strong>.sticky-row</strong><span>Makes a BB row stick to the top on scroll — background switches to <code>--header-scrolled-bar-color</code> and logo shrinks to <code>80×60px</code>. Requires the DS Toolkit global JS scroll listener.</span></div>
    </div>
</div>

<!-- Carousels -->
<p class="dst-section-title" style="margin-top:20px;">Carousels</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-images-alt2"></span></div>
        <div class="dst-card-info"><strong>.sponsor-carousel</strong><span>Styles UABB Image Carousel images as white-background sponsor logos — <code>90px</code> fixed height, contained, with grey border.</span></div>
    </div>
</div>

<!-- CTA / Infobox -->
<p class="dst-section-title" style="margin-top:20px;">CTA / Infobox</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-megaphone"></span></div>
        <div class="dst-card-info"><strong>.cta-tile</strong><span>Rounds UABB Info Box 2 corners and adds a white gradient fade at the bottom — card-style CTA tile.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-megaphone"></span></div>
        <div class="dst-card-info"><strong>.border-cta</strong><span>Adds a <code>1px</code> accent-color border around UABB Info Box 2.</span></div>
    </div>
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-megaphone"></span></div>
        <div class="dst-card-info"><strong>.info-box-cta</strong><span>On hover, title and body text switch to white — use on dark or image-background info boxes.</span></div>
    </div>
</div>

<!-- Animation -->
<p class="dst-section-title" style="margin-top:20px;">Animation</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-arrow-down"></span></div>
        <div class="dst-card-info"><strong>.v-line-container + .v-line</strong><span>Animated vertical scroll-down indicator line. Place a <code>div.v-line-container > div.v-line</code> inside a BB HTML module on a hero row.</span></div>
    </div>
</div>

<!-- Social -->
<p class="dst-section-title" style="margin-top:20px;">Social Icons</p>
<div class="dst-card">
    <div class="dst-card-row">
        <div class="dst-card-icon"><span class="dashicons dashicons-share"></span></div>
        <div class="dst-card-info"><strong>.top-social</strong><span>Flexes a UABB Info List into a horizontal right-aligned social icon row — removes extra margins and sets auto width per item.</span></div>
    </div>
</div>

<!-- Full CSS source -->
<p class="dst-section-title" style="margin-top:28px;">Full CSS Source <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#8c9aaa;font-size:11px;">— read only · edit in <code>includes/defaults/global-css.css</code></span></p>
<div class="dst-code-editor-wrap">
    <textarea readonly rows="40" style="cursor:default;"><?php echo esc_textarea( file_get_contents( DS_TOOLKIT_PATH . 'includes/defaults/global-css.css' ) ); ?></textarea>
</div>
