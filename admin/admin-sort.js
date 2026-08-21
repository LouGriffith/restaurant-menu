/**
 * Restaurant Menu Manager — Admin Drag-and-Drop Sort
 * Active only when the list is filtered to a specific menu.
 */
jQuery( function( $ ) {
    'use strict';

    var menuId = rmmSort.menuId;
    if ( ! menuId ) return;

    var $tbody  = $( '#the-list' );
    var $notice = $( '#rmm-sort-notice' );
    var isDirty = false;
    var saveTimer;

    // ── Add drag handles to every row BEFORE making sortable ─────────────────
    $tbody.find( 'tr' ).each( function() {
        var $cb = $( this ).find( 'td.column-cb' );
        if ( $cb.length ) {
            $cb.after( '<td class="column-rmm-handle"><span class="rmm-drag-handle" title="Drag to reorder">⠿</span></td>' );
        }
    } );

    // ── Show sort bar ─────────────────────────────────────────────────────────
    $notice.css( 'display', 'flex' );

    // ── Make rows sortable ────────────────────────────────────────────────────
    $tbody.sortable( {
        items:       'tr',
        axis:        'y',
        handle:      '.rmm-drag-handle',
        placeholder: 'rmm-sort-placeholder',
        forcePlaceholderSize: true,
        tolerance:   'pointer',
        cursor:      'grabbing',
        start: function( e, ui ) {
            // Give placeholder same height as dragged row
            ui.placeholder.find( 'td' ).remove();
            ui.placeholder.append( '<td colspan="10" style="height:' + ui.item.outerHeight() + 'px"></td>' );
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
            clearTimeout( saveTimer );
            saveTimer = setTimeout( saveOrder, 2000 );
        },
    } );

    // ── Save button ───────────────────────────────────────────────────────────
    $notice.find( '.rmm-sort-save' ).on( 'click', function() {
        saveOrder();
    } );

    function getOrderedIds() {
        var ids = [];
        $tbody.find( 'tr[id^="post-"]' ).each( function() {
            var id = parseInt( $( this ).attr( 'id' ).replace( 'post-', '' ) );
            if ( id ) ids.push( id );
        } );
        return ids;
    }

    function saveOrder() {
        if ( ! isDirty ) return;
        clearTimeout( saveTimer );

        var $btn    = $notice.find( '.rmm-sort-save' );
        var $status = $notice.find( '.rmm-sort-status' );
        var ids     = getOrderedIds();

        if ( ! ids.length ) return;

        $btn.prop( 'disabled', true );
        $status.text( 'Saving…' ).removeClass( 'rmm-sort-dirty rmm-sort-ok rmm-sort-err' );

        // Build post data manually so PHP receives ordered_ids as an array
        var postData = {
            action:  'rmm_save_menu_order',
            nonce:   rmmSort.nonce,
            menu_id: menuId,
        };
        $.each( ids, function( i, id ) {
            postData[ 'ordered_ids[' + i + ']' ] = id;
        } );

        $.post( rmmSort.ajaxUrl, postData, function( res ) {
            $btn.prop( 'disabled', false );
            if ( res.success ) {
                isDirty = false;
                $status.text( '✓ Order saved' ).addClass( 'rmm-sort-ok' );
                setTimeout( function() { $status.text( '' ); }, 3000 );
            } else {
                $status.text( 'Error: ' + ( res.data || 'unknown' ) ).addClass( 'rmm-sort-err' );
            }
        } ).fail( function() {
            $btn.prop( 'disabled', false );
            $status.text( 'Request failed.' ).addClass( 'rmm-sort-err' );
        } );
    }

    $( window ).on( 'beforeunload', function() {
        if ( isDirty ) return 'You have unsaved sort order changes.';
    } );
} );
