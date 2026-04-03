/* global dsLogoFinder, jQuery */
(function ($) {
    'use strict';

    var PER_PAGE = 50;

    var allLogos = dsLogoFinder.logos; // [{ name, url }, ...]
    var selected = {};
    var filtered = allLogos.slice();   // current search result
    var shown    = 0;                  // how many cards are currently rendered

    // -------------------------------------------------------------------------
    // Extra keywords — acronyms that differ from auto-generated initials
    // -------------------------------------------------------------------------
    var EXTRA_KEYWORDS = {
        'American University Eagles':   'AU',
        'Auburn Tigers':                'AU',
        'Ohio State Buckeyes':          'OSU',
        'Oklahoma State Cowboys':       'OSU',
        'Penn State Nittany Lions':     'PSU',
        'Michigan State Spartans':      'MSU',
        'Mississippi State Bulldogs':   'MSU',
        'Florida State Seminoles':      'FSU',
        'Michigan Wolverines':          'UM UofM',
        'Minnesota Golden Gophers':     'UM UMN',
        'Ole Miss Rebels':              'UM',
        'Miami Hurricanes':             'UM The U',
        'Alabama Crimson Tide':         'UA Bama',
        'Arkansas Razorbacks':          'UA',
        'Arizona State Sun Devils':     'ASU',
        'North Carolina Tar Heels':     'UNC',
        'Kansas Jayhawks':              'KU',
        'Kentucky Wildcats':            'UK',
        'Indiana Hoosiers':             'IU',
        'West Virginia Mountaineers':   'WVU',
        'Virginia Cavaliers':           'UVA',
        'Iowa Hawkeyes':                'UI',
        'Illinois Fighting Illini':     'UI UIUC',
        'Nebraska Cornhuskers':         'NU',
        'Northwestern Wildcats':        'NU',
        'Oregon Ducks':                 'UO',
        'Washington Huskies':           'UW',
        'Wisconsin Badgers':            'UW',
        'Georgia Bulldogs':             'UGA',
        'Tennessee Volunteers':         'UT Vols',
        'Texas Longhorns':              'UT',
        'Texas Am Aggies':              'TAMU',
        'Oklahoma Sooners':             'OU',
        'Colorado Buffaloes':           'CU',
        'California Golden Bears':      'Cal UCB UC Berkeley',
        'Notre Dame Fighting Irish':    'ND',
        'Purdue Boilermakers':          'PU',
        'Maryland Terrapins':           'UMD Terps',
        'Rutgers Scarlet Knights':      'RU',
        'Ucla Bruins':                  'UCLA',
        'Usc Trojans':                  'USC',
        'Ucf Knights':                  'UCF',
        'Uconn Huskies':                'UConn',
        'Uab Blazers':                  'UAB',
        'Uc Davis Aggies':              'UCD',
        'Uc Irvine Anteaters':          'UCI',
        'Uc Riverside Highlanders':     'UCR',
        'Uc San Diego Tritons':         'UCSD',
        'Uc Santa Barbara Gauchos':     'UCSB',
        'Lsu Tigers':                   'LSU',
        'Byu Cougars':                  'BYU',
        'Tcu Horned Frogs':             'TCU',
        'Smu Mustangs':                 'SMU',
        'Vcu Rams':                     'VCU',
        'Vmi Keydets':                  'VMI',
        'Siu Edwardsville Cougars':     'SIUE',
        'Uic Flames':                   'UIC',
        'Njit Highlanders':             'NJIT',
        'Umbc Retrievers':              'UMBC',
        'Umass Lowell River Hawks':     'UML',
        'Unlv Rebels':                  'UNLV',
        'Utep Miners':                  'UTEP',
        'Utsa Roadrunners':             'UTSA',
        'Ualbany Great Danes':          'UAlbany',
        'Ut Arlington Mavericks':       'UTA',
        'Ut Martin Skyhawks':           'UTM',
        'Ut Rio Grande Valley Vaqueros': 'UTRGV',
        'Iu Indianapolis Jaguars':      'IUPUI',
    };

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

    // Auto-generate initials: "American University Eagles" → "AUE"
    function getAcronym( name ) {
        return name.split( ' ' ).map( function ( w ) { return w.charAt( 0 ); } ).join( '' );
    }

    // Everything we search against for a logo
    function getSearchable( logo ) {
        return ( logo.name + ' ' + getAcronym( logo.name ) + ' ' + ( EXTRA_KEYWORDS[ logo.name ] || '' ) ).toLowerCase();
    }

    // -------------------------------------------------------------------------
    // Render — appends `count` cards starting at `offset`, returns cards added
    // -------------------------------------------------------------------------
    function renderCards( list, offset, count ) {
        var slice = list.slice( offset, offset + count );
        var html  = '';
        $.each( slice, function ( i, logo ) {
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
        $( '#dst-lf-grid' ).append( html );
        return slice.length;
    }

    function updateCount() {
        var total = filtered.length;
        var label = shown < total
            ? 'Showing ' + shown + ' of ' + total + ' logos'
            : total + ' logo' + ( total === 1 ? '' : 's' );
        $( '#dst-lf-count' ).text( label );
    }

    function updateLoadMore() {
        if ( shown < filtered.length ) {
            $( '#dst-lf-load-more' ).show();
        } else {
            $( '#dst-lf-load-more' ).hide();
        }
    }

    function renderGrid( list ) {
        filtered = list;
        shown    = 0;
        $( '#dst-lf-grid' ).empty();

        if ( list.length === 0 ) {
            $( '#dst-lf-grid' ).html( '<p class="dst-lf-empty">No logos match your search.</p>' );
            $( '#dst-lf-count' ).text( '0 logos' );
            $( '#dst-lf-load-more' ).hide();
            return;
        }

        shown = renderCards( list, 0, PER_PAGE );
        updateCount();
        updateLoadMore();
    }

    // -------------------------------------------------------------------------
    // Load More
    // -------------------------------------------------------------------------
    $( '#dst-lf-load-more' ).on( 'click', function () {
        shown += renderCards( filtered, shown, PER_PAGE );
        updateCount();
        updateLoadMore();
    } );

    // -------------------------------------------------------------------------
    // Import bar
    // -------------------------------------------------------------------------
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
    // Card select / deselect
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
    // Search — multi-token, searches name + auto-acronym + extra keywords
    // -------------------------------------------------------------------------
    $( '#dst-lf-search' ).on( 'input', function () {
        var q      = $( this ).val().trim().toLowerCase();
        var tokens = q === '' ? [] : q.split( /\s+/ );
        var result = tokens.length === 0 ? allLogos : allLogos.filter( function ( logo ) {
            var s = getSearchable( logo );
            return tokens.every( function ( t ) { return s.indexOf( t ) !== -1; } );
        } );
        renderGrid( result );
    } );

    // -------------------------------------------------------------------------
    // Select All (rendered cards only) / Clear
    // -------------------------------------------------------------------------
    $( '#dst-lf-select-all' ).on( 'click', function () {
        $( '.dst-lf-card' ).each( function () {
            var name = $( this ).data( 'name' );
            selected[ name ] = true;
            $( this ).addClass( 'is-selected' ).find( '.dst-lf-select-btn' ).text( 'Selected ✓' );
        } );
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
