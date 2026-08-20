/**
 * Restaurant Menu Manager — Frontend Inline Edit
 * Pencil icon on hover → inline panel → AJAX save → live DOM update
 */
( function( $ ) {
    'use strict';

    var activePanel = null;

    // ── Open / close panel ────────────────────────────────────────────────────
    $( document ).on( 'click', '.rmm-edit-trigger', function( e ) {
        e.preventDefault();
        e.stopPropagation();

        var $trigger = $( this );
        var $item    = $trigger.closest( '.rmm-item' );
        var itemId   = $trigger.data( 'id' );

        // Close any open panel first
        if ( activePanel ) {
            activePanel.slideUp( 150, function() { $( this ).remove(); } );
            if ( activePanel.data( 'item-id' ) === itemId ) {
                activePanel = null;
                return; // toggle off
            }
        }

        // Build and inject the panel
        var panel = buildPanel( $trigger );
        panel.data( 'item-id', itemId );
        $item.after( panel );
        panel.slideDown( 150 );
        activePanel = panel;
    } );

    // Close panel when clicking outside
    $( document ).on( 'click', function( e ) {
        if ( activePanel && ! $( e.target ).closest( '.rmm-inline-panel, .rmm-edit-trigger' ).length ) {
            activePanel.slideUp( 150, function() { $( this ).remove(); } );
            activePanel = null;
        }
    } );

    // ── Build the panel HTML ──────────────────────────────────────────────────
    function buildPanel( $trigger ) {
        var data     = $trigger.data();
        var sections = rmmFE.sections || [];

        // Section options
        var sectionOpts = '<option value="">— No section —</option>';
        $.each( sections, function( i, sec ) {
            var sel = ( parseInt( data.section ) === parseInt( sec.id ) ) ? ' selected' : '';
            sectionOpts += '<option value="' + sec.id + '"' + sel + '>' + sec.name + '</option>';
        } );

        var panel = $( '<div class="rmm-inline-panel"></div>' ).html(
            '<div class="rmm-ip-inner">'
          +   '<div class="rmm-ip-header">'
          +     '<strong>Edit: ' + $( '<span>' ).text( data.name ).html() + '</strong>'
          +     '<button type="button" class="rmm-ip-close" title="Close">✕</button>'
          +   '</div>'
          +   '<div class="rmm-ip-body">'

          +     '<div class="rmm-ip-row rmm-ip-row--half">'
          +       '<div class="rmm-ip-field">'
          +         '<label>Item Name</label>'
          +         '<input type="text" name="rmm_name" value="' + esc( data.name ) + '" />'
          +       '</div>'
          +       '<div class="rmm-ip-field">'
          +         '<label>Price ($)</label>'
          +         '<input type="text" name="rmm_price" value="' + esc( data.price ) + '" placeholder="0.00" />'
          +       '</div>'
          +     '</div>'

          +     '<div class="rmm-ip-field">'
          +       '<label>Description</label>'
          +       '<textarea name="rmm_desc" rows="3">' + esc( data.desc ) + '</textarea>'
          +     '</div>'

          +     '<div class="rmm-ip-field">'
          +       '<label>Section</label>'
          +       '<select name="rmm_section">' + sectionOpts + '</select>'
          +     '</div>'

          +     '<div class="rmm-ip-row rmm-ip-row--checks">'
          +       '<label class="rmm-ip-check">'
          +         '<input type="checkbox" name="rmm_available" value="1"' + ( data.available == '1' ? ' checked' : '' ) + '>'
          +         ' Available <span style="color:#888;font-size:11px">(uncheck to 86)</span>'
          +       '</label>'
          +       '<label class="rmm-ip-check">'
          +         '<input type="checkbox" name="rmm_featured" value="1"' + ( data.featured == '1' ? ' checked' : '' ) + '>'
          +         ' ⭐ Featured'
          +       '</label>'
          +     '</div>'

          +   '</div>'
          +   '<div class="rmm-ip-footer">'
          +     '<button type="button" class="rmm-ip-save button button-primary">Save Changes</button>'
          +     '<span class="rmm-ip-status"></span>'
          +   '</div>'
          + '</div>'
        );

        panel.find( '.rmm-ip-close' ).on( 'click', function() {
            panel.slideUp( 150, function() { $( this ).remove(); } );
            activePanel = null;
        } );

        panel.find( '.rmm-ip-save' ).on( 'click', function() {
            savePanel( panel, $trigger );
        } );

        return panel;
    }

    // ── Save via AJAX ─────────────────────────────────────────────────────────
    function savePanel( $panel, $trigger ) {
        var $status = $panel.find( '.rmm-ip-status' );
        var $btn    = $panel.find( '.rmm-ip-save' );
        var itemId  = $trigger.data( 'id' );

        $btn.prop( 'disabled', true ).text( 'Saving…' );
        $status.text( '' ).removeClass( 'rmm-ip-ok rmm-ip-err' );

        var postData = {
            action:        'rmm_frontend_save',
            nonce:         rmmFE.nonce,
            post_id:       itemId,
            rmm_name:      $panel.find( '[name=rmm_name]' ).val(),
            rmm_price:     $panel.find( '[name=rmm_price]' ).val(),
            rmm_desc:      $panel.find( '[name=rmm_desc]' ).val(),
            rmm_section:   $panel.find( '[name=rmm_section]' ).val(),
            rmm_available: $panel.find( '[name=rmm_available]' ).is( ':checked' ) ? '1' : '0',
            rmm_featured:  $panel.find( '[name=rmm_featured]' ).is( ':checked' ) ? '1' : '0',
        };

        $.post( rmmFE.ajaxUrl, postData, function( res ) {
            $btn.prop( 'disabled', false ).text( 'Save Changes' );

            if ( ! res.success ) {
                $status.text( res.data || 'Error saving.' ).addClass( 'rmm-ip-err' );
                return;
            }

            var d = res.data;

            // ── Update the trigger data attributes for next open ──────────────
            $trigger
                .data( 'name',      d.name )
                .data( 'price',     d.price )
                .data( 'desc',      d.desc )
                .data( 'available', d.available )
                .data( 'featured',  d.featured )
                .data( 'section',   d.section_id );

            // ── Update the visible item DOM ───────────────────────────────────
            var $item = $trigger.closest( '.rmm-item' );

            $item.find( '.rmm-item-name' ).text( d.name );

            if ( d.price ) {
                $item.find( '.rmm-item-price' ).html( '$' + d.price );
            } else {
                $item.find( '.rmm-item-price' ).html( '' );
            }

            if ( d.desc ) {
                if ( $item.find( '.rmm-item-desc' ).length ) {
                    $item.find( '.rmm-item-desc' ).text( d.desc );
                } else {
                    $item.find( '.rmm-item-body' ).append( '<p class="rmm-item-desc">' + $( '<span>' ).text( d.desc ).html() + '</p>' );
                }
            } else {
                $item.find( '.rmm-item-desc' ).remove();
            }

            // 86'd overlay
            if ( d.available === '0' ) {
                $item.addClass( 'rmm-unavailable' );
            } else {
                $item.removeClass( 'rmm-unavailable' );
            }

            // Featured highlight
            if ( d.featured === '1' ) {
                $item.addClass( 'rmm-featured' );
            } else {
                $item.removeClass( 'rmm-featured' );
            }

            $status.text( '✓ Saved' ).addClass( 'rmm-ip-ok' );
            setTimeout( function() {
                $panel.slideUp( 200, function() { $( this ).remove(); } );
                activePanel = null;
            }, 800 );
        } ).fail( function() {
            $btn.prop( 'disabled', false ).text( 'Save Changes' );
            $status.text( 'Request failed.' ).addClass( 'rmm-ip-err' );
        } );
    }

    // ── Escape HTML for inserting into attribute strings ─────────────────────
    function esc( str ) {
        return $( '<span>' ).text( str || '' ).html()
            .replace( /"/g, '&quot;' );
    }

} )( jQuery );
