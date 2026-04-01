jQuery( function( $ ) {

    // ── Gallery media uploader ────────────────────────────────────────────────
    var mediaFrame;

    $( '#rmm_gallery_btn' ).on( 'click', function( e ) {
        e.preventDefault();

        if ( mediaFrame ) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media( {
            title:    'Select Item Photos',
            button:   { text: 'Use These Photos' },
            multiple: true,
            library:  { type: 'image' },
        } );

        mediaFrame.on( 'select', function() {
            var selection = mediaFrame.state().get( 'selection' );
            var ids  = [];
            var html = '';

            selection.map( function( attachment ) {
                attachment = attachment.toJSON();
                ids.push( attachment.id );
                var thumb = attachment.sizes && attachment.sizes.thumbnail
                    ? attachment.sizes.thumbnail.url
                    : attachment.url;
                html += '<img src="' + thumb + '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" />';
            } );

            $( '#rmm_gallery' ).val( ids.join( ',' ) );
            $( '#rmm_gallery_preview' ).html( html );
        } );

        mediaFrame.open();
    } );

} );
