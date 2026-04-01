/**
 * Restaurant Menu Manager — Quick Edit JS v1.4.0
 * - Pre-populates the quick edit panel from per-row data attributes.
 * - Saves custom fields via AJAX (no page reload).
 * - Refreshes Price, Status, Featured, and Menus columns in place.
 */
jQuery( function( $ ) {

    // Cache the original WP handler so we can extend it.
    var wpInlineEdit = inlineEditPost.edit;

    /**
     * Override inlineEditPost.edit.
     * Called by WordPress when the user clicks "Quick Edit" on a row.
     */
    inlineEditPost.edit = function( id ) {

        // Run WordPress's own handler first (renders the panel).
        wpInlineEdit.apply( this, arguments );

        // Resolve post ID.
        var postId = ( typeof id === 'object' ) ? parseInt( this.getId( id ) ) : id;
        if ( ! postId ) return;

        // Find the data span for this row.
        var $row  = $( '#post-' + postId );
        var $data = $row.find( '.rmm-qe-data' );
        if ( ! $data.length ) return; // not an rmm_menu_item row

        // Read stored values.
        var price     = $data.data( 'price' )    || '';
        var sort      = $data.data( 'sort' )     || '';
        var avail     = String( $data.data( 'avail' ) );
        var featured  = String( $data.data( 'featured' ) );
        var sectionId = parseInt( $data.data( 'section' ) ) || 0;
        var menus     = $data.data( 'menus' );

        if ( typeof menus === 'string' ) {
            try { menus = JSON.parse( menus ); } catch(e) { menus = []; }
        }
        if ( ! $.isArray( menus ) ) menus = [];

        var $editRow = $( '#edit-' + postId );

        // ── Populate fields ──────────────────────────────────────────────────
        $editRow.find( 'input.rmm-qe-price' ).val( price );
        $editRow.find( 'input.rmm-qe-sort' ).val( sort );
        $editRow.find( 'select.rmm-qe-avail' ).val( avail === '0' ? '0' : '1' );
        $editRow.find( 'input.rmm-qe-featured' ).prop( 'checked', featured === '1' );
        $editRow.find( 'select.rmm-qe-section' ).val( sectionId || '' );

        // Menu checkboxes.
        $editRow.find( 'input.rmm-qe-menu-cb' ).prop( 'checked', false );
        $.each( menus, function( i, menuId ) {
            $editRow.find( 'input.rmm-qe-menu-cb[value="' + menuId + '"]' ).prop( 'checked', true );
        } );
    };

    /**
     * Intercept the Save button to persist our custom fields via AJAX.
     * WP's own save (title, status, etc.) still fires via its handler.
     */
    $( document ).on( 'click', '.save.button.button-primary', function() {
        var $btn     = $( this );
        var $editRow = $btn.closest( 'tr.inline-edit-row' );
        if ( ! $editRow.length ) return;

        var rowId  = $editRow.attr( 'id' ) || '';
        var postId = parseInt( rowId.replace( 'edit-', '' ) );
        if ( ! postId ) return;

        // Collect values.
        var price    = $editRow.find( 'input.rmm-qe-price' ).val();
        var sort     = $editRow.find( 'input.rmm-qe-sort' ).val();
        var avail    = $editRow.find( 'select.rmm-qe-avail' ).val();
        var featured = $editRow.find( 'input.rmm-qe-featured' ).is( ':checked' ) ? '1' : '0';
        var section  = $editRow.find( 'select.rmm-qe-section' ).val();

        var menus = [];
        $editRow.find( 'input.rmm-qe-menu-cb:checked' ).each( function() {
            menus.push( $( this ).val() );
        } );

        var postData = {
            action:         'rmm_save_quick_edit',
            nonce:          rmmQE.nonce,
            post_id:        postId,
            rmm_price:      price,
            rmm_sort_order: sort,
            rmm_available:  avail,
            rmm_featured:   featured,
            rmm_section_qe: section || '',
        };

        $.each( menus, function( i, v ) {
            postData[ 'rmm_menus[' + i + ']' ] = v;
        } );

        $.post( rmmQE.ajaxUrl, postData, function( res ) {
            if ( ! res.success ) return;

            var $row = $( '#post-' + postId );

            // Refresh visible column cells immediately.
            $row.find( '.column-rmm_price' ).html( res.data.price_html );
            $row.find( '.column-rmm_avail' ).html( res.data.avail_html );
            $row.find( '.column-rmm_featured' ).html( res.data.feat_html );
            $row.find( '.column-rmm_menus' ).html( res.data.menus_html );

            // Update the data span so re-opening Quick Edit shows new values.
            var $dataSpan = $row.find( '.rmm-qe-data' );
            $dataSpan.data( 'price',    price );
            $dataSpan.data( 'sort',     sort );
            $dataSpan.data( 'avail',    avail );
            $dataSpan.data( 'featured', featured );
            $dataSpan.data( 'section',  section ? parseInt( section ) : 0 );
            $dataSpan.data( 'menus',    menus.map( Number ) );
        } );
    } );

} );
