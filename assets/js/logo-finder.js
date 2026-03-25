/* global dsLogoFinder, jQuery */
(function ($) {
    'use strict';

    var logos    = dsLogoFinder.logos;   // full array of names from PHP
    var selected = {};                   // { 'University Name': true }
    var visible  = logos.slice();        // currently shown after search

    // -------------------------------------------------------------------------
    // Render grid
    // -------------------------------------------------------------------------
    function renderGrid( list ) {
        visible = list;
        var $grid = $('#dst-lf-grid');
        $grid.empty();

        if ( list.length === 0 ) {
            $grid.html('<p class="dst-lf-empty">No logos match your search.</p>');
            updateCount( 0 );
            return;
        }

        $.each( list, function ( i, name ) {
            var isSelected = !! selected[ name ];
            var filename   = name + '.png';
            var imgUrl     = dsLogoFinder.logoUrl + encodeURIComponent( filename );

            var $card = $(
                '<div class="dst-lf-card' + ( isSelected ? ' is-selected' : '' ) + '" data-name="' + escAttr( name ) + '">' +
                    '<div class="dst-lf-card-check"><span class="dashicons dashicons-yes-alt"></span></div>' +
                    '<img src="' + escAttr( imgUrl ) + '" alt="' + escAttr( name ) + '" loading="lazy">' +
                    '<span>' + escHtml( name ) + '</span>' +
                '</div>'
            );
            $grid.append( $card );
        } );

        updateCount( list.length );
    }

    // -------------------------------------------------------------------------
    // Selection state
    // -------------------------------------------------------------------------
    function updateSelectedUI() {
        var count = Object.keys( selected ).length;
        $('#dst-lf-selected-num').text( count );
        if ( count > 0 ) {
            $('#dst-lf-footer').show();
        } else {
            $('#dst-lf-footer').hide();
        }
    }

    function updateCount( n ) {
        $('#dst-lf-count').text( n + ' logo' + ( n === 1 ? '' : 's' ) );
    }

    // -------------------------------------------------------------------------
    // Escape helpers
    // -------------------------------------------------------------------------
    function escAttr( s ) {
        return String( s ).replace( /[&"<>]/g, function ( c ) {
            return { '&': '&amp;', '"': '&quot;', '<': '&lt;', '>': '&gt;' }[ c ];
        } );
    }
    function escHtml( s ) {
        return String( s ).replace( /[&<>]/g, function ( c ) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[ c ];
        } );
    }

    // -------------------------------------------------------------------------
    // Events
    // -------------------------------------------------------------------------

    // Card click — toggle selection
    $( document ).on( 'click', '.dst-lf-card', function () {
        var name = $( this ).data( 'name' );
        if ( selected[ name ] ) {
            delete selected[ name ];
            $( this ).removeClass( 'is-selected' );
        } else {
            selected[ name ] = true;
            $( this ).addClass( 'is-selected' );
        }
        updateSelectedUI();
    } );

    // Live search
    $( '#dst-lf-search' ).on( 'input', function () {
        var q = $( this ).val().trim().toLowerCase();
        if ( q === '' ) {
            renderGrid( logos );
        } else {
            var filtered = logos.filter( function ( name ) {
                return name.toLowerCase().indexOf( q ) !== -1;
            } );
            renderGrid( filtered );
        }
    } );

    // Select All (only visible cards)
    $( '#dst-lf-select-all' ).on( 'click', function () {
        $.each( visible, function ( i, name ) {
            selected[ name ] = true;
        } );
        $( '.dst-lf-card' ).addClass( 'is-selected' );
        updateSelectedUI();
    } );

    // Clear selection
    $( '#dst-lf-clear' ).on( 'click', function () {
        selected = {};
        $( '.dst-lf-card' ).removeClass( 'is-selected' );
        updateSelectedUI();
    } );

    // -------------------------------------------------------------------------
    // Import — one logo at a time, sequential AJAX calls with live feedback
    // -------------------------------------------------------------------------
    $( '#dst-lf-import' ).on( 'click', function () {
        var names = Object.keys( selected );
        if ( names.length === 0 ) return;

        var $btn     = $( this );
        var $results = $( '#dst-lf-results' );

        $btn.prop( 'disabled', true ).text( 'Importing…' );
        $results.html( '<div class="dst-lf-result-list"></div>' );

        var $list = $results.find( '.dst-lf-result-list' );
        var index = 0;

        function importNext() {
            if ( index >= names.length ) {
                $btn.prop( 'disabled', false ).text( 'Import Selected' );
                return;
            }

            var name = names[ index ];
            index++;

            // Add a "pending" row
            var rowId  = 'dst-lf-row-' + index;
            var $row   = $( '<div class="dst-lf-result-row pending" id="' + rowId + '"><span class="dst-lf-spinner"></span> ' + escHtml( name ) + '…</div>' );
            $list.append( $row );
            $list[0].scrollTop = $list[0].scrollHeight;

            $.post( dsLogoFinder.ajaxUrl, {
                action : 'ds_import_logo',
                nonce  : dsLogoFinder.nonce,
                name   : name
            } )
            .done( function ( res ) {
                if ( res.success ) {
                    var icon = res.data.status === 'exists' ? '⚠️' : '✅';
                    $row.removeClass( 'pending' )
                        .addClass( res.data.status )
                        .html( icon + ' ' + escHtml( res.data.message ) );
                } else {
                    $row.removeClass( 'pending' )
                        .addClass( 'error' )
                        .html( '❌ ' + escHtml( name ) + ' — ' + escHtml( res.data.message ) );
                }
                importNext();
            } )
            .fail( function () {
                $row.removeClass( 'pending' )
                    .addClass( 'error' )
                    .html( '❌ ' + escHtml( name ) + ' — Request failed.' );
                importNext();
            } );
        }

        importNext();
    } );

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------
    renderGrid( logos );
    updateSelectedUI();

}( jQuery ) );
