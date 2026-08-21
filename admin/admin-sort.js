/**
 * Restaurant Menu Manager — Admin Drag-and-Drop Sort
 *
 * Only active when the list is filtered to a specific menu
 * (rmm_filter_menu param is set in the URL).
 * Uses jQuery UI Sortable (bundled with WordPress).
 */
jQuery( function( $ ) {
    'use strict';

    // Only run when a menu filter is active
    var menuId = rmmSort.menuId;
    if ( ! menuId ) return;

    var $tbody   = $( '#the-list' );
    var $notice  = $( '#rmm-sort-notice' );
    var isDirty  = false;
    var saveTimer;

    // ── Show the sort bar ─────────────────────────────────────────────────────
    $notice.show();

    // ── Make rows sortable ────────────────────────────────────────────────────
    $tbody.sortable( {
        items:       'tr',
        axis:        'y',
        handle:      '.rmm-drag-handle',
        placeholder: 'rmm-sort-placeholder',
        tolerance:   'pointer',
        cursor:      'grabbing',

        start: function( e, ui ) {
            ui.placeholder.height( ui.item.outerHeight() );
            ui.item.css( 'opacity', '0.6' );
        },
        stop: function( e, ui ) {
            ui.item.css( 'opacity', '1' );
        },
        update: function() {
            isDirty = true;
            $notice.find( '.rmm-sort-status' )
                .text( 'Unsaved changes — click Save Order.' )
                .removeClass( 'rmm-sort-ok rmm-sort-err' )
                .addClass( 'rmm-sort-dirty' );

            // Auto-save after 2 s of inactivity
            clearTimeout( saveTimer );
            saveTimer = setTimeout( saveOrder, 2000 );
        },
    } );

    // ── Drag handle column header ─────────────────────────────────────────────
    // Add a handle cell to each row
    $tbody.find( 'tr' ).each( function() {
        $( this ).find( 'td.column-cb' ).after(
            '<td class="column-rmm-handle">'
          + '<span class="rmm-drag-handle" title="Drag to reorder">⠿</span>'
          + '</td>'
        );
    } );

    // ── Save button ───────────────────────────────────────────────────────────
    $notice.find( '.rmm-sort-save' ).on( 'click', function() {
        saveOrder();
    } );

    function saveOrder() {
        if ( ! isDirty ) return;
        clearTimeout( saveTimer );

        var $btn    = $notice.find( '.rmm-sort-save' );
        var $status = $notice.find( '.rmm-sort-status' );

        $btn.prop( 'disabled', true );
        $status.text( 'Saving…' ).removeClass( 'rmm-sort-dirty rmm-sort-ok rmm-sort-err' );

        var orderedIds = [];
        $tbody.find( 'tr' ).each( function() {
            var id = $( this ).attr( 'id' );
            if ( id ) orderedIds.push( id.replace( 'post-', '' ) );
        } );

        $.post( rmmSort.ajaxUrl, {
            action:      'rmm_save_menu_order',
            nonce:       rmmSort.nonce,
            menu_id:     menuId,
            ordered_ids: orderedIds,
        }, function( res ) {
            $btn.prop( 'disabled', false );
            if ( res.success ) {
                isDirty = false;
                $status.text( '✓ Order saved' ).addClass( 'rmm-sort-ok' );
                setTimeout( function() { $status.text( '' ); }, 3000 );
            } else {
                $status.text( 'Error saving order.' ).addClass( 'rmm-sort-err' );
            }
        } ).fail( function() {
            $btn.prop( 'disabled', false );
            $status.text( 'Request failed.' ).addClass( 'rmm-sort-err' );
        } );
    }

    // Warn before leaving with unsaved changes
    $( window ).on( 'beforeunload', function() {
        if ( isDirty ) return 'You have unsaved sort order changes.';
    } );
} );
