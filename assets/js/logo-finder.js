/* global dsLogoFinder, jQuery */
(function ($) {
    'use strict';

    var allLogos = dsLogoFinder.logos; // [{ name, url }, ...]
    var selected = {};                 // { 'University Name': true }
    var visible  = allLogos.slice();

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    function escAttr( s ) {
        return String( s ).replace( /["&<>]/g, function ( c ) {
            return { '"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;' }[ c ];
        } );
    }
    function escHtml( s ) {
        return String( s ).replace( /[&<>]/g, function ( c ) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[ c ];
        } );
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------
    function renderGrid( list ) {
        visible = list;
        var $grid = $( '#dst-lf-grid' ).empty();

        if ( list.length === 0 ) {
            $grid.html( '<p class="dst-lf-empty">No logos match your search.</p>' );
            $( '#dst-lf-count' ).text( '0 logos' );
            return;
        }

        var html = '';
        $.each( list, function ( i, logo ) {
            var isSel = !! selected[ logo.name ];
            html +=
                '<div class="dst-lf-card' + ( isSel ? ' is-selected' : '' ) + '" data-name="' + escAttr( logo.name ) + '">' +
                    '<div class="dst-lf-img-wrap">' +
                        '<img src="' + escAttr( logo.url ) + '" alt="' + escAttr( logo.name ) + '" loading="lazy">' +
                    '</div>' +
                    '<p class="dst-lf-name">' + escHtml( logo.name ) + '</p>' +
                    '<button type="button" class="dst-lf-select-btn">' + ( isSel ? 'Selected ✓' : 'Select' ) + '</button>' +
                '</div>';
        } );

        $grid.html( html );
        $( '#dst-lf-count' ).text( list.length + ' logo' + ( list.length === 1 ? '' : 's' ) );
    }

    function updateImportBar() {
        var count = Object.keys( selected ).length;
        if ( count > 0 ) {
            $( '#dst-lf-selected-label' ).text( count + ' logo' + ( count === 1 ? '' : 's' ) + ' selected' );
            $( '#dst-lf-import-bar' ).addClass( 'is-visible' );
        } else {
            $( '#dst-lf-import-bar' ).removeClass( 'is-visible' );
        }
    }

    // -------------------------------------------------------------------------
    // Card select/deselect
    // -------------------------------------------------------------------------
    $( document ).on( 'click', '.dst-lf-select-btn', function ( e ) {
        e.stopPropagation();
        var $card = $( this ).closest( '.dst-lf-card' );
        var name  = $card.data( 'name' );
        if ( selected[ name ] ) {
            delete selected[ name ];
            $card.removeClass( 'is-selected' );
            $( this ).text( 'Select' );
        } else {
            selected[ name ] = true;
            $card.addClass( 'is-selected' );
            $( this ).text( 'Selected ✓' );
        }
        updateImportBar();
    } );

    // -------------------------------------------------------------------------
    // Search
    // -------------------------------------------------------------------------
    $( '#dst-lf-search' ).on( 'input', function () {
        var q = $( this ).val().trim().toLowerCase();
        var filtered = q === '' ? allLogos : allLogos.filter( function ( logo ) {
            return logo.name.toLowerCase().indexOf( q ) !== -1;
        } );
        renderGrid( filtered );
    } );

    // -------------------------------------------------------------------------
    // Select All (visible) / Clear
    // -------------------------------------------------------------------------
    $( '#dst-lf-select-all' ).on( 'click', function () {
        $.each( visible, function ( i, logo ) {
            selected[ logo.name ] = true;
        } );
        $( '.dst-lf-card' ).addClass( 'is-selected' ).find( '.dst-lf-select-btn' ).text( 'Selected ✓' );
        updateImportBar();
    } );

    $( '#dst-lf-clear' ).on( 'click', function () {
        selected = {};
        $( '.dst-lf-card' ).removeClass( 'is-selected' ).find( '.dst-lf-select-btn' ).text( 'Select' );
        updateImportBar();
    } );

    // -------------------------------------------------------------------------
    // Import — sequential AJAX, one logo at a time, live log
    // -------------------------------------------------------------------------
    $( '#dst-lf-import' ).on( 'click', function () {
        var names = Object.keys( selected );
        if ( ! names.length ) return;

        var $btn     = $( this );
        var $results = $( '#dst-lf-results' );

        $btn.prop( 'disabled', true ).text( 'Importing…' );
        $results.html( '<div class="dst-lf-log"></div>' );
        var $log = $results.find( '.dst-lf-log' );

        var index = 0;

        function next() {
            if ( index >= names.length ) {
                $btn.prop( 'disabled', false ).text( 'Import Selected' );
                return;
            }

            var name = names[ index++ ];
            var $row = $( '<div class="dst-lf-log-row pending"><span class="dst-lf-spinner"></span> ' + escHtml( name ) + '…</div>' );
            $log.append( $row );
            $log[0].scrollTop = $log[0].scrollHeight;

            $.post( dsLogoFinder.ajaxUrl, {
                action : 'ds_import_logo',
                nonce  : dsLogoFinder.nonce,
                name   : name
            } )
            .done( function ( res ) {
                if ( res.success ) {
                    var icon = res.data.status === 'exists' ? '⚠️' : '✅';
                    $row.removeClass( 'pending' ).addClass( res.data.status ).html( icon + ' ' + escHtml( res.data.message ) );
                } else {
                    $row.removeClass( 'pending' ).addClass( 'error' ).html( '❌ ' + escHtml( name ) + ' — ' + escHtml( res.data.message ) );
                }
                next();
            } )
            .fail( function () {
                $row.removeClass( 'pending' ).addClass( 'error' ).html( '❌ ' + escHtml( name ) + ' — Request failed.' );
                next();
            } );
        }

        next();
    } );

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------
    renderGrid( allLogos );
    updateImportBar();

}( jQuery ) );
