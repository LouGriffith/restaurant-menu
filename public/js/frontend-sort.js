/**
 * Restaurant Menu Manager — Frontend Drag-and-Drop Sort
 * Only loaded for logged-in editors.
 */
( function( $ ) {
    'use strict';

    $( document ).ready( function() {

        // ── Inject sort bar above each section ───────────────────────────────
        $( '.rmm-menu-wrapper' ).each( function() {
            var menuId = $( this ).data( 'menu-id' );
            if ( ! menuId ) return;

            $( this ).find( '.rmm-section' ).each( function() {
                var $section = $( this );
                var $grid    = $section.find( '.rmm-items-grid' );
                if ( ! $grid.length || ! $grid.find( '.rmm-item' ).length ) return;

                var $bar = $(
                    '<div class="rmm-sort-bar" data-menu-id="' + menuId + '">'
                  + '<button type="button" class="rmm-sort-toggle">⠿ Reorder Items</button>'
                  + '<span class="rmm-sort-bar-actions" style="display:none;">'
                  + '<button type="button" class="rmm-sort-confirm">✓ Save Order</button>'
                  + '<button type="button" class="rmm-sort-cancel">✕ Cancel</button>'
                  + '<span class="rmm-sort-fe-status"></span>'
                  + '</span>'
                  + '</div>'
                );

                // Insert bar before the section header if present, else before grid
                var $header = $section.find( '.rmm-section-header' );
                if ( $header.length ) {
                    $header.after( $bar );
                } else {
                    $grid.before( $bar );
                }

                $bar.on( 'click', '.rmm-sort-toggle', function() {
                    enterSortMode( $grid, $bar, menuId );
                } );
            } );
        } );

    } );

    // ── Enter sort mode ───────────────────────────────────────────────────────
    function enterSortMode( $grid, $bar, menuId ) {
        var $toggle  = $bar.find( '.rmm-sort-toggle' );
        var $actions = $bar.find( '.rmm-sort-bar-actions' );

        // Snapshot original order for cancel
        var originalOrder = [];
        $grid.find( '.rmm-item' ).each( function() {
            originalOrder.push( this );
        } );

        $toggle.hide();
        $actions.show();
        $grid.addClass( 'rmm-sorting-active' );

        $grid.sortable( {
            items:               '.rmm-item',
            axis:                'y',
            placeholder:         'rmm-fe-placeholder',
            forcePlaceholderSize: true,
            tolerance:           'pointer',
            cursor:              'grabbing',
            start: function( e, ui ) {
                ui.placeholder.height( ui.item.outerHeight() );
                ui.item.css( 'opacity', '0.6' );
            },
            stop: function( e, ui ) {
                ui.item.css( 'opacity', '1' );
            },
        } );

        // ── Save ──────────────────────────────────────────────────────────────
        $bar.find( '.rmm-sort-confirm' ).one( 'click', function() {
            var $btn    = $( this );
            var $status = $bar.find( '.rmm-sort-fe-status' );

            $btn.prop( 'disabled', true ).text( 'Saving…' );

            // Collect ordered IDs from current DOM order
            var ids = [];
            $grid.find( '.rmm-item[data-id]' ).each( function() {
                var id = parseInt( $( this ).data( 'id' ) );
                if ( id ) ids.push( id );
            } );

            if ( ! ids.length ) {
                $btn.prop( 'disabled', false ).text( '✓ Save Order' );
                $status.text( 'No items found.' );
                return;
            }

            // Build POST data with explicit array keys
            var postData = {
                action:  'rmm_save_menu_order',
                nonce:   rmmFESort.nonce,
                menu_id: menuId,
            };
            $.each( ids, function( i, id ) {
                postData[ 'ordered_ids[' + i + ']' ] = id;
            } );

            $.post( rmmFESort.ajaxUrl, postData )
                .done( function( res ) {
                    $btn.prop( 'disabled', false ).text( '✓ Save Order' );
                    exitSortMode( $grid, $bar, $toggle, $actions );
                    if ( res.success ) {
                        $status.text( '✓ Saved' ).addClass( 'rmm-sort-fe-ok' );
                        setTimeout( function() {
                            $status.text( '' ).removeClass( 'rmm-sort-fe-ok rmm-sort-fe-err' );
                        }, 3000 );
                    } else {
                        $status.text( 'Error: ' + ( res.data || 'unknown' ) ).addClass( 'rmm-sort-fe-err' );
                    }
                } )
                .fail( function() {
                    $btn.prop( 'disabled', false ).text( '✓ Save Order' );
                    $status.text( 'Request failed.' ).addClass( 'rmm-sort-fe-err' );
                } );
        } );

        // ── Cancel ────────────────────────────────────────────────────────────
        $bar.find( '.rmm-sort-cancel' ).one( 'click', function() {
            // Restore DOM to original order
            $.each( originalOrder, function( i, el ) {
                $grid.append( el );
            } );
            exitSortMode( $grid, $bar, $toggle, $actions );
        } );
    }

    // ── Exit sort mode ────────────────────────────────────────────────────────
    function exitSortMode( $grid, $bar, $toggle, $actions ) {
        if ( $grid.data( 'ui-sortable' ) ) {
            $grid.sortable( 'destroy' );
        }
        $grid.removeClass( 'rmm-sorting-active' );
        $actions.hide();
        $toggle.show();

        // Re-bind for next use (one() consumed previous handler)
        $bar.find( '.rmm-sort-confirm, .rmm-sort-cancel' ).off( 'click' );
        $bar.off( 'click', '.rmm-sort-toggle' ).on( 'click', '.rmm-sort-toggle', function() {
            enterSortMode( $grid, $bar, $bar.data( 'menu-id' ) );
        } );
    }

} )( jQuery );
