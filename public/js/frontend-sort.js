/**
 * Restaurant Menu Manager — Frontend Drag-and-Drop Sort
 *
 * Only loaded for logged-in editors.
 * Activates per .rmm-items-grid when a sort mode toggle is clicked.
 */
( function( $ ) {
    'use strict';

    // ── Inject the sort toggle bar above each menu section ───────────────────
    $( '.rmm-menu-wrapper' ).each( function() {
        var menuId = $( this ).data( 'menu-id' );
        if ( ! menuId ) return;

        $( this ).find( '.rmm-section' ).each( function() {
            var $section = $( this );
            var $grid    = $section.find( '.rmm-items-grid' );
            if ( ! $grid.length ) return;

            var $bar = $(
                '<div class="rmm-sort-bar" data-menu-id="' + menuId + '">'
              + '<button type="button" class="rmm-sort-toggle">⠿ Reorder Items</button>'
              + '<span class="rmm-sort-bar-actions" style="display:none">'
              + '<button type="button" class="rmm-sort-confirm">✓ Save Order</button>'
              + '<button type="button" class="rmm-sort-cancel">✕ Cancel</button>'
              + '<span class="rmm-sort-fe-status"></span>'
              + '</span>'
              + '</div>'
            );

            $section.prepend( $bar );

            // ── Toggle sort mode ─────────────────────────────────────────────
            $bar.find( '.rmm-sort-toggle' ).on( 'click', function() {
                enterSortMode( $grid, $bar, menuId );
            } );
        } );
    } );

    function enterSortMode( $grid, $bar, menuId ) {
        var $toggle  = $bar.find( '.rmm-sort-toggle' );
        var $actions = $bar.find( '.rmm-sort-bar-actions' );
        var $status  = $bar.find( '.rmm-fe-status' );

        // Store original order for cancel
        var originalOrder = [];
        $grid.find( '.rmm-item' ).each( function() {
            originalOrder.push( $( this ) );
        } );

        $toggle.hide();
        $actions.show();
        $grid.addClass( 'rmm-sorting-active' );

        // Make items draggable
        $grid.sortable( {
            items:       '.rmm-item',
            axis:        'y',
            handle:      '.rmm-item',
            placeholder: 'rmm-fe-placeholder',
            tolerance:   'pointer',
            cursor:      'grabbing',
            start: function( e, ui ) {
                ui.placeholder.height( ui.item.outerHeight() );
                ui.item.css( 'opacity', '0.6' );
            },
            stop: function( e, ui ) {
                ui.item.css( 'opacity', '1' );
            },
        } );

        // ── Save ─────────────────────────────────────────────────────────────
        $bar.find( '.rmm-sort-confirm' ).one( 'click', function() {
            var $btn    = $( this );
            var $status = $bar.find( '.rmm-sort-fe-status' );

            $btn.prop( 'disabled', true ).text( 'Saving…' );

            var orderedIds = [];
            $grid.find( '.rmm-item' ).each( function() {
                var id = $( this ).data( 'id' );
                if ( id ) orderedIds.push( id );
            } );

            $.post( rmmFESort.ajaxUrl, {
                action:      'rmm_save_menu_order',
                nonce:       rmmFESort.nonce,
                menu_id:     menuId,
                ordered_ids: orderedIds,
            }, function( res ) {
                $btn.prop( 'disabled', false );
                exitSortMode( $grid, $bar, $toggle, $actions );

                if ( res.success ) {
                    $status.text( '✓ Order saved' ).addClass( 'rmm-sort-fe-ok' );
                    setTimeout( function() {
                        $status.text( '' ).removeClass( 'rmm-sort-fe-ok rmm-sort-fe-err' );
                    }, 3000 );
                } else {
                    $status.text( 'Error saving.' ).addClass( 'rmm-sort-fe-err' );
                }
            } ).fail( function() {
                $btn.prop( 'disabled', false ).text( '✓ Save Order' );
                $bar.find( '.rmm-sort-fe-status' ).text( 'Request failed.' ).addClass( 'rmm-sort-fe-err' );
            } );
        } );

        // ── Cancel ────────────────────────────────────────────────────────────
        $bar.find( '.rmm-sort-cancel' ).one( 'click', function() {
            // Restore original DOM order
            $.each( originalOrder, function( i, $el ) {
                $grid.append( $el );
            } );
            exitSortMode( $grid, $bar, $toggle, $actions );
        } );
    }

    function exitSortMode( $grid, $bar, $toggle, $actions ) {
        $grid.sortable( 'destroy' ).removeClass( 'rmm-sorting-active' );
        $actions.hide();
        $toggle.show();

        // Re-bind toggle for next use
        $bar.find( '.rmm-sort-toggle' ).off( 'click' ).on( 'click', function() {
            var menuId = $bar.data( 'menu-id' );
            enterSortMode( $grid, $bar, menuId );
        } );
    }

} )( jQuery );
