<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'rmm_add_meta_boxes' );
function rmm_add_meta_boxes() {

    add_meta_box( 'rmm_item_details', '🍽️ Item Details',
        'rmm_render_item_details_box', 'rmm_menu_item', 'normal', 'high' );

    add_meta_box( 'rmm_item_menus', '📋 Assign to Menus',
        'rmm_render_item_menus_box', 'rmm_menu_item', 'side', 'default' );

    add_meta_box( 'rmm_menu_settings', '⚙️ Menu Display Settings',
        'rmm_render_menu_settings_box', 'rmm_menu', 'normal', 'high' );
}

// ── Item Details ──────────────────────────────────────────────────────────────
function rmm_render_item_details_box( $post ) {
    wp_nonce_field( 'rmm_save_item_details', 'rmm_item_nonce' );

    $price      = get_post_meta( $post->ID, '_rmm_price',      true );
    $price_note = get_post_meta( $post->ID, '_rmm_price_note', true );
    $featured   = get_post_meta( $post->ID, '_rmm_featured',   true );
    $avail      = get_post_meta( $post->ID, '_rmm_available',  true );
    $sort_order = get_post_meta( $post->ID, '_rmm_sort_order', true );
    $short_desc = get_post_meta( $post->ID, '_rmm_short_desc', true );
    $gallery    = get_post_meta( $post->ID, '_rmm_gallery',    true );

    if ( $avail === '' ) $avail = '1';
    if ( $sort_order === '' ) $sort_order = 10;
    ?>
    <style>
    .rmm-meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
    .rmm-meta-grid .full { grid-column:1/-1; }
    .rmm-meta-field label { display:block; font-weight:600; margin-bottom:4px; font-size:13px; }
    .rmm-meta-field input[type=text],
    .rmm-meta-field input[type=number],
    .rmm-meta-field textarea,
    .rmm-meta-field select { width:100%; padding:6px 8px; border:1px solid #ddd; border-radius:3px; }
    .rmm-meta-field textarea { min-height:80px; resize:vertical; }
    .rmm-toggle-row { display:flex; align-items:center; gap:8px; padding:6px 0; font-size:13px; }
    .rmm-gallery-thumbs { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
    .rmm-gallery-thumbs img { width:60px; height:60px; object-fit:cover; border-radius:4px; }
    </style>

    <div class="rmm-meta-grid">
        <div class="rmm-meta-field">
            <label for="rmm_price">Price ($)</label>
            <input type="text" id="rmm_price" name="rmm_price"
                value="<?php echo esc_attr( $price ); ?>" placeholder="0.00" />
        </div>
        <div class="rmm-meta-field">
            <label for="rmm_price_note">Price Note <span style="font-weight:400;color:#888">(e.g. "per person")</span></label>
            <input type="text" id="rmm_price_note" name="rmm_price_note"
                value="<?php echo esc_attr( $price_note ); ?>" />
        </div>
        <div class="rmm-meta-field">
            <label for="rmm_sort_order">Sort Order</label>
            <input type="number" id="rmm_sort_order" name="rmm_sort_order"
                value="<?php echo esc_attr( $sort_order ); ?>" min="1" step="1" style="width:80px;" />
        </div>
        <div class="rmm-meta-field">
            <label for="rmm_available">Availability</label>
            <select id="rmm_available" name="rmm_available">
                <option value="1" <?php selected( $avail, '1' ); ?>>✅ Available</option>
                <option value="0" <?php selected( $avail, '0' ); ?>>🚫 86'd (unavailable)</option>
            </select>
        </div>
        <div class="rmm-meta-field full">
            <label for="rmm_short_desc">Short Description</label>
            <textarea id="rmm_short_desc" name="rmm_short_desc"><?php echo esc_textarea( $short_desc ); ?></textarea>
        </div>
    </div>

    <div class="rmm-toggle-row">
        <input type="checkbox" id="rmm_featured" name="rmm_featured" value="1"
            <?php checked( $featured, '1' ); ?> />
        <label for="rmm_featured">⭐ Featured item</label>
    </div>

    <div class="rmm-meta-field" style="margin-top:16px">
        <label>Photo Gallery</label>
        <input type="hidden" id="rmm_gallery" name="rmm_gallery" value="<?php echo esc_attr( $gallery ); ?>" />
        <div class="rmm-gallery-thumbs" id="rmm_gallery_preview">
            <?php
            if ( $gallery ) {
                foreach ( explode( ',', $gallery ) as $img_id ) {
                    $img_id = absint( trim( $img_id ) );
                    if ( $img_id ) {
                        echo wp_get_attachment_image( $img_id, [ 60, 60 ], false, [ 'style' => 'width:60px;height:60px;object-fit:cover;border-radius:4px;' ] );
                    }
                }
            }
            ?>
        </div>
        <button type="button" id="rmm_gallery_btn" class="button" style="margin-top:8px">
            Add / Change Photos
        </button>
    </div>
    <?php
}

// ── Assign to Menus ───────────────────────────────────────────────────────────
function rmm_render_item_menus_box( $post ) {
    wp_nonce_field( 'rmm_save_item_menus', 'rmm_menus_nonce' );

    $assigned = (array) get_post_meta( $post->ID, '_rmm_menus', true );
    $all_menus = get_posts( [
        'post_type'      => 'rmm_menu',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    if ( empty( $all_menus ) ) {
        echo '<p style="color:#888;font-size:13px">No menus yet. <a href="' . esc_url( admin_url( 'post-new.php?post_type=rmm_menu' ) ) . '">Create one</a>.</p>';
        return;
    }

    foreach ( $all_menus as $menu ) {
        $checked = in_array( $menu->ID, array_map( 'intval', $assigned ), true ) ? 'checked' : '';
        echo '<label style="display:block;padding:4px 0;font-size:13px;cursor:pointer">'
           . '<input type="checkbox" name="rmm_menus[]" value="' . esc_attr( $menu->ID ) . '" ' . $checked . '> '
           . esc_html( $menu->post_title )
           . '</label>';
    }
}

// ── Menu Display Settings ─────────────────────────────────────────────────────
function rmm_render_menu_settings_box( $post ) {
    wp_nonce_field( 'rmm_save_menu_settings', 'rmm_menu_settings_nonce' );

    $layout   = get_post_meta( $post->ID, '_rmm_layout',        true ) ?: 'list';
    $show_img = get_post_meta( $post->ID, '_rmm_show_images',   true );
    $show_sec = get_post_meta( $post->ID, '_rmm_show_sections', true );
    $note     = get_post_meta( $post->ID, '_rmm_menu_note',     true );
    $shortcode_id = $post->ID;

    if ( $show_img === '' ) $show_img = '1';
    if ( $show_sec === '' ) $show_sec = '1';
    ?>
    <style>
    .rmm-settings-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
    .rmm-layout-option { cursor:pointer; border:2px solid #ddd; border-radius:6px; padding:12px; text-align:center; transition:border-color .2s; }
    .rmm-layout-option:has(input:checked) { border-color:#2271b1; background:#f0f6fc; }
    .rmm-layout-option input { display:none; }
    .rmm-layout-option .icon { font-size:22px; display:block; margin-bottom:4px; }
    .rmm-layout-option .label { font-size:12px; font-weight:600; }
    .rmm-check-row { display:flex; align-items:center; gap:8px; padding:8px 0; font-size:13px; }
    .rmm-shortcode-box { background:#f6f7f7; border:1px solid #ddd; border-radius:4px; padding:10px 14px; font-family:monospace; font-size:13px; color:#333; margin-top:12px; }
    </style>

    <p style="font-weight:600;margin-bottom:8px">Default Display Layout</p>
    <div class="rmm-settings-grid" style="margin-bottom:16px">
        <?php
        $layouts = [
            'list'       => [ '☰', 'Classic List' ],
            'two-column' => [ '⊞', 'Two Column' ],
            'cards'      => [ '⊟', 'Card Grid' ],
        ];
        foreach ( $layouts as $val => [ $icon, $label ] ) {
            printf(
                '<label class="rmm-layout-option"><input type="radio" name="rmm_layout" value="%s" %s><span class="icon">%s</span><span class="label">%s</span></label>',
                esc_attr( $val ),
                checked( $layout, $val, false ),
                $icon,
                esc_html( $label )
            );
        }
        ?>
    </div>

    <div class="rmm-check-row">
        <input type="checkbox" id="rmm_show_images" name="rmm_show_images" value="1" <?php checked( $show_img, '1' ); ?>>
        <label for="rmm_show_images">Show item photos</label>
    </div>
    <div class="rmm-check-row">
        <input type="checkbox" id="rmm_show_sections" name="rmm_show_sections" value="1" <?php checked( $show_sec, '1' ); ?>>
        <label for="rmm_show_sections">Show section headers (Appetizers, Entrées, etc.)</label>
    </div>

    <div style="margin-top:12px">
        <label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px">Menu Note <span style="font-weight:400;color:#888">(shown at bottom of menu)</span></label>
        <textarea name="rmm_menu_note" style="width:100%;min-height:60px"><?php echo esc_textarea( $note ); ?></textarea>
    </div>

    <?php if ( $post->post_status === 'publish' ) : ?>
    <div class="rmm-shortcode-box">
        📋 <strong>Shortcode:</strong>
        <code>[restaurant_menu id="<?php echo esc_html( $shortcode_id ); ?>"]</code>
        &nbsp;|&nbsp;
        <code>[restaurant_menu id="<?php echo esc_html( $shortcode_id ); ?>" layout="two-column"]</code>
        &nbsp;|&nbsp;
        <code>[restaurant_menu id="<?php echo esc_html( $shortcode_id ); ?>" layout="cards"]</code>
    </div>
    <?php endif; ?>
    <?php
}

// ── Save meta ─────────────────────────────────────────────────────────────────
add_action( 'save_post', 'rmm_save_meta', 10, 2 );
function rmm_save_meta( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Item details
    if ( isset( $_POST['rmm_item_nonce'] ) && wp_verify_nonce( $_POST['rmm_item_nonce'], 'rmm_save_item_details' ) ) {
        $fields = [
            '_rmm_price'      => sanitize_text_field( ltrim( $_POST['rmm_price']      ?? '', '$' ) ),
            '_rmm_price_note' => sanitize_text_field( $_POST['rmm_price_note'] ?? '' ),
            '_rmm_featured'   => isset( $_POST['rmm_featured'] ) ? '1' : '0',
            '_rmm_available'  => sanitize_text_field( $_POST['rmm_available']  ?? '1' ),
            '_rmm_sort_order' => absint( $_POST['rmm_sort_order'] ?? 10 ),
            '_rmm_gallery'    => sanitize_text_field( $_POST['rmm_gallery']    ?? '' ),
            '_rmm_short_desc' => wp_kses_post( $_POST['rmm_short_desc']        ?? '' ),
        ];
        foreach ( $fields as $key => $val ) {
            update_post_meta( $post_id, $key, $val );
        }
    }

    // Menu assignments
    if ( isset( $_POST['rmm_menus_nonce'] ) && wp_verify_nonce( $_POST['rmm_menus_nonce'], 'rmm_save_item_menus' ) ) {
        $menus = array_map( 'absint', (array) ( $_POST['rmm_menus'] ?? [] ) );
        update_post_meta( $post_id, '_rmm_menus', $menus );
    }

    // Menu display settings
    if ( isset( $_POST['rmm_menu_settings_nonce'] ) && wp_verify_nonce( $_POST['rmm_menu_settings_nonce'], 'rmm_save_menu_settings' ) ) {
        update_post_meta( $post_id, '_rmm_layout',        sanitize_text_field( $_POST['rmm_layout']        ?? 'list' ) );
        update_post_meta( $post_id, '_rmm_show_images',   isset( $_POST['rmm_show_images'] )   ? '1' : '0' );
        update_post_meta( $post_id, '_rmm_show_sections', isset( $_POST['rmm_show_sections'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_rmm_menu_note',     sanitize_textarea_field( $_POST['rmm_menu_note'] ?? '' ) );
    }
}
