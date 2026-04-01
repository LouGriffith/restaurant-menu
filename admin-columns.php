<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════════════════════
// COLUMNS
// ══════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_rmm_menu_item_posts_columns', 'rmm_item_columns' );
function rmm_item_columns( $cols ) {
    $new = [];
    foreach ( $cols as $k => $v ) {
        $new[$k] = $v;
        if ( $k === 'title' ) {
            $new['rmm_thumb']    = 'Photo';
            $new['rmm_price']    = 'Price';
            $new['rmm_featured'] = '⭐';
            $new['rmm_avail']    = 'Status';
            $new['rmm_menus']    = 'Menus';
        }
    }
    return $new;
}

add_action( 'manage_rmm_menu_item_posts_custom_column', 'rmm_item_column_content', 10, 2 );
function rmm_item_column_content( $col, $post_id ) {
    switch ( $col ) {
        case 'rmm_thumb':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, [ 48, 48 ], [ 'style' => 'border-radius:4px;object-fit:cover' ] );
            } else {
                echo '<span style="color:#ccc;font-size:22px">📷</span>';
            }
            break;

        case 'rmm_price':
            $p = get_post_meta( $post_id, '_rmm_price', true );
            $n = get_post_meta( $post_id, '_rmm_price_note', true );
            echo $p
                ? '<strong>$' . esc_html( $p ) . '</strong>' . ( $n ? ' <small style="color:#888">' . esc_html( $n ) . '</small>' : '' )
                : '—';
            break;

        case 'rmm_featured':
            echo get_post_meta( $post_id, '_rmm_featured', true ) === '1' ? '⭐' : '—';
            break;

        case 'rmm_avail':
            $a = get_post_meta( $post_id, '_rmm_available', true );
            echo $a === '0'
                ? '<span style="color:#d63638">🚫 86\'d</span>'
                : '<span style="color:#00a32a">✅ Available</span>';
            break;

        case 'rmm_menus':
            echo rmm_get_menu_pills( $post_id );
            break;
    }
}

/**
 * Build the menu-pill HTML for a given item.
 * Extracted as a standalone function so the AJAX handler can reuse it.
 */
function rmm_get_menu_pills( $post_id ) {
    $menu_ids = (array) get_post_meta( $post_id, '_rmm_menus', true );
    $menu_ids = array_filter( array_map( 'absint', $menu_ids ) );

    if ( empty( $menu_ids ) ) {
        return '<span style="color:#aaa;">—</span>';
    }

    $pills = [];
    foreach ( $menu_ids as $mid ) {
        $title = get_the_title( $mid );
        if ( ! $title ) continue;

        // Each pill is a link that filters the list to this menu.
        $url = add_query_arg( [
            'post_type'        => 'rmm_menu_item',
            'rmm_filter_menu'  => $mid,
        ], admin_url( 'edit.php' ) );

        $pills[] = '<a href="' . esc_url( $url ) . '" class="rmm-menu-pill">'
                 . esc_html( $title )
                 . '</a>';
    }

    return $pills ? implode( ' ', $pills ) : '<span style="color:#aaa;">—</span>';
}

// ── Sortable columns ──────────────────────────────────────────────────────────
add_filter( 'manage_edit-rmm_menu_item_sortable_columns', function( $cols ) {
    $cols['rmm_price']    = 'rmm_price';
    $cols['rmm_featured'] = 'rmm_featured';
    return $cols;
} );

// ── Column CSS widths ─────────────────────────────────────────────────────────
add_action( 'admin_head', function() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'rmm_menu_item' ) return;
    ?>
    <style>
    .column-rmm_thumb    { width: 60px; }
    .column-rmm_price    { width: 100px; }
    .column-rmm_featured { width: 36px; text-align: center !important; }
    .column-rmm_avail    { width: 130px; }
    .column-rmm_menus    { width: 200px; }

    /* Menu pills */
    .rmm-menu-pill {
        display: inline-block;
        padding: 2px 8px;
        background: #e8f0fe;
        color: #1a56db;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        line-height: 1.6;
    }
    .rmm-menu-pill:hover {
        background: #1a56db;
        color: #fff;
    }
    /* Highlighted pill when that menu is actively filtered */
    .rmm-menu-pill.is-active-filter {
        background: #1a56db;
        color: #fff;
    }
    </style>
    <?php
} );

// ══════════════════════════════════════════════════════════════════════════════
// MENU FILTER DROPDOWN
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render the "Filter by Menu" dropdown above the items list.
 * WordPress fires restrict_manage_posts on the Edit screen toolbar.
 */
add_action( 'restrict_manage_posts', 'rmm_filter_by_menu_dropdown', 10, 2 );
function rmm_filter_by_menu_dropdown( $post_type, $which ) {
    if ( $post_type !== 'rmm_menu_item' || $which !== 'top' ) return;

    $menus = get_posts( [
        'post_type'      => 'rmm_menu',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    if ( empty( $menus ) ) return;

    $selected = absint( $_GET['rmm_filter_menu'] ?? 0 );
    ?>
    <select name="rmm_filter_menu" id="rmm_filter_menu">
        <option value=""><?php esc_html_e( 'All Menus', 'restaurant-menu' ); ?></option>
        <?php foreach ( $menus as $menu ) : ?>
            <option value="<?php echo esc_attr( $menu->ID ); ?>"
                <?php selected( $selected, $menu->ID ); ?>>
                <?php echo esc_html( $menu->post_title ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Apply the menu filter to the WP_Query when the dropdown is set.
 * We filter by _rmm_menus post meta containing the selected menu ID.
 */
add_action( 'pre_get_posts', 'rmm_apply_menu_filter' );
function rmm_apply_menu_filter( $query ) {
    // Only on the admin list screen for our post type, main query only.
    if (
        ! is_admin()
        || ! $query->is_main_query()
        || $query->get( 'post_type' ) !== 'rmm_menu_item'
    ) {
        return;
    }

    $menu_id = absint( $_GET['rmm_filter_menu'] ?? 0 );
    if ( ! $menu_id ) return;

    // _rmm_menus is stored as a PHP serialized integer array, e.g. a:1:{i:0;i:393;}
    // Integers have no quotes in serialized form, so we match ;i:{ID}; (mid-array)
    // and :i:{ID};} (last element) to cover all positions.
    $query->set( 'meta_query', [
        'relation' => 'OR',
        [
            'key'     => '_rmm_menus',
            'value'   => ';i:' . $menu_id . ';',
            'compare' => 'LIKE',
        ],
        [
            'key'     => '_rmm_menus',
            'value'   => ':' . $menu_id . ';}',
            'compare' => 'LIKE',
        ],
    ] );
}

/**
 * Highlight the active-filter pill on rows when a filter is applied.
 * Injected as inline JS so we don't need an extra enqueue.
 */
add_action( 'admin_footer', 'rmm_highlight_active_filter_pill' );
function rmm_highlight_active_filter_pill() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'rmm_menu_item' ) return;

    $active = absint( $_GET['rmm_filter_menu'] ?? 0 );
    if ( ! $active ) return;
    ?>
    <script>
    jQuery( function( $ ) {
        $( '.rmm-menu-pill' ).each( function() {
            var href   = $( this ).attr( 'href' ) || '';
            var params = new URLSearchParams( href.split( '?' )[1] || '' );
            if ( parseInt( params.get( 'rmm_filter_menu' ) ) === <?php echo $active; ?> ) {
                $( this ).addClass( 'is-active-filter' );
            }
        } );
    } );
    </script>
    <?php
}

// ══════════════════════════════════════════════════════════════════════════════
// QUICK EDIT
// ══════════════════════════════════════════════════════════════════════════════

/**
 * 1. Render the quick edit panel HTML.
 *    WordPress calls this once per page load (it's cloned per row via JS).
 */
add_action( 'quick_edit_custom_box', 'rmm_quick_edit_box', 10, 2 );
function rmm_quick_edit_box( $column_name, $post_type ) {

    // Only add our panel once, keyed off the first column we own.
    if ( $post_type !== 'rmm_menu_item' || $column_name !== 'rmm_price' ) {
        return;
    }

    $all_menus = get_posts( [
        'post_type'      => 'rmm_menu',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    $all_sections = get_terms( [
        'taxonomy'   => 'rmm_section',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );

    ?>
    <fieldset class="inline-edit-col-left rmm-quick-edit-fieldset">
        <div class="inline-edit-col">
            <h4 style="margin:0 0 10px;font-size:13px;color:#1d2327;">🍽️ Menu Item Details</h4>

            <?php wp_nonce_field( 'rmm_quick_edit_save', 'rmm_quick_edit_nonce' ); ?>

            <div class="rmm-qe-grid">

                <?php /* Price */ ?>
                <label class="rmm-qe-label">
                    <span class="title"><?php esc_html_e( 'Price ($)', 'restaurant-menu' ); ?></span>
                    <input type="text"
                           name="rmm_price"
                           class="rmm-qe-price"
                           placeholder="0.00"
                           style="width:100px;" />
                </label>

                <?php /* Sort Order */ ?>
                <label class="rmm-qe-label">
                    <span class="title"><?php esc_html_e( 'Sort Order', 'restaurant-menu' ); ?></span>
                    <input type="number"
                           name="rmm_sort_order"
                           class="rmm-qe-sort"
                           min="1"
                           step="1"
                           style="width:70px;" />
                </label>

                <?php /* Availability */ ?>
                <label class="rmm-qe-label rmm-qe-full">
                    <span class="title"><?php esc_html_e( 'Availability', 'restaurant-menu' ); ?></span>
                    <select name="rmm_available" class="rmm-qe-avail">
                        <option value="1"><?php esc_html_e( '✅ Available', 'restaurant-menu' ); ?></option>
                        <option value="0"><?php esc_html_e( '🚫 86\'d (unavailable)', 'restaurant-menu' ); ?></option>
                    </select>
                </label>

                <?php /* Featured */ ?>
                <label class="rmm-qe-label rmm-qe-full rmm-qe-check">
                    <input type="checkbox" name="rmm_featured" class="rmm-qe-featured" value="1" />
                    <span class="title"><?php esc_html_e( '⭐ Featured item', 'restaurant-menu' ); ?></span>
                </label>

                <?php /* Section */ ?>
                <?php if ( ! empty( $all_sections ) && ! is_wp_error( $all_sections ) ) : ?>
                <label class="rmm-qe-label rmm-qe-full">
                    <span class="title"><?php esc_html_e( 'Section', 'restaurant-menu' ); ?></span>
                    <select name="rmm_section_qe" class="rmm-qe-section">
                        <option value=""><?php esc_html_e( '— No section —', 'restaurant-menu' ); ?></option>
                        <?php foreach ( $all_sections as $term ) : ?>
                            <option value="<?php echo esc_attr( $term->term_id ); ?>">
                                <?php echo esc_html( $term->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>

                <?php /* Assign to Menus */ ?>
                <?php if ( ! empty( $all_menus ) ) : ?>
                <div class="rmm-qe-label rmm-qe-full">
                    <span class="title" style="display:block;margin-bottom:4px;">
                        <?php esc_html_e( 'Assign to Menus', 'restaurant-menu' ); ?>
                    </span>
                    <div class="rmm-qe-menus-list">
                        <?php foreach ( $all_menus as $menu ) : ?>
                        <label class="rmm-qe-menu-check">
                            <input type="checkbox"
                                   name="rmm_menus[]"
                                   class="rmm-qe-menu-cb"
                                   value="<?php echo esc_attr( $menu->ID ); ?>" />
                            <?php echo esc_html( $menu->post_title ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /.rmm-qe-grid -->
        </div>
    </fieldset>

    <style>
    .rmm-quick-edit-fieldset {
        border-top: 1px solid #dcdcde;
        margin-top: 8px;
        padding-top: 12px;
    }
    .rmm-qe-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        align-items: flex-start;
    }
    .rmm-qe-label { display: flex; flex-direction: column; gap: 4px; }
    .rmm-qe-label .title { font-size: 12px; font-weight: 600; color: #50575e; }
    .rmm-qe-full  { flex: 1 1 100%; }
    .rmm-qe-check { flex-direction: row; align-items: center; gap: 6px; }
    .rmm-qe-check .title { font-weight: normal; color: #1d2327; }
    .rmm-qe-menus-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 16px;
        padding: 8px;
        background: #f6f7f7;
        border: 1px solid #dcdcde;
        border-radius: 3px;
    }
    .rmm-qe-menu-check {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        cursor: pointer;
        white-space: nowrap;
    }
    .rmm-qe-avail,
    .rmm-qe-section { max-width: 280px; }
    </style>
    <?php
}

/**
 * 2. Enqueue the quick edit JS.
 */
add_action( 'admin_enqueue_scripts', 'rmm_quick_edit_scripts' );
function rmm_quick_edit_scripts( $hook ) {
    $screen = get_current_screen();
    if (
        ! $screen
        || $screen->base !== 'edit'
        || $screen->post_type !== 'rmm_menu_item'
    ) {
        return;
    }

    wp_enqueue_script(
        'rmm-quick-edit',
        RMM_PLUGIN_URL . 'admin/quick-edit.js',
        [ 'jquery', 'inline-edit-post' ],
        RMM_VERSION,
        true
    );

    wp_localize_script( 'rmm-quick-edit', 'rmmQE', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'rmm_quick_edit_save' ),
    ] );
}

/**
 * 3. Embed per-row data as a hidden span so the JS can pre-populate fields.
 */
add_filter( 'post_row_actions', 'rmm_add_quick_edit_data', 10, 2 );
function rmm_add_quick_edit_data( $actions, $post ) {
    if ( $post->post_type !== 'rmm_menu_item' ) {
        return $actions;
    }

    $price    = get_post_meta( $post->ID, '_rmm_price',      true );
    $sort     = get_post_meta( $post->ID, '_rmm_sort_order', true );
    $avail    = get_post_meta( $post->ID, '_rmm_available',  true );
    $featured = get_post_meta( $post->ID, '_rmm_featured',   true );
    $menus    = (array) get_post_meta( $post->ID, '_rmm_menus', true );

    $section_terms = wp_get_post_terms( $post->ID, 'rmm_section', [ 'fields' => 'ids' ] );
    $section_id    = ( ! is_wp_error( $section_terms ) && ! empty( $section_terms ) )
                     ? (int) $section_terms[0]
                     : 0;

    if ( $avail === '' ) $avail = '1';

    echo '<span class="rmm-qe-data hidden"'
        . ' data-price="'    . esc_attr( $price )    . '"'
        . ' data-sort="'     . esc_attr( $sort )     . '"'
        . ' data-avail="'    . esc_attr( $avail )    . '"'
        . ' data-featured="' . esc_attr( $featured ) . '"'
        . ' data-menus="'    . esc_attr( wp_json_encode( array_map( 'intval', $menus ) ) ) . '"'
        . ' data-section="'  . esc_attr( $section_id ) . '"'
        . '></span>';

    return $actions;
}

/**
 * 4. AJAX handler — save quick edit values.
 */
add_action( 'wp_ajax_rmm_save_quick_edit', 'rmm_ajax_save_quick_edit' );
function rmm_ajax_save_quick_edit() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }
    if ( ! check_ajax_referer( 'rmm_quick_edit_save', 'nonce', false ) ) {
        wp_send_json_error( 'Bad nonce', 403 );
    }

    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'rmm_menu_item' ) {
        wp_send_json_error( 'Invalid post', 400 );
    }

    // Price
    $price = ltrim( sanitize_text_field( $_POST['rmm_price'] ?? '' ), '$' );
    update_post_meta( $post_id, '_rmm_price', $price );

    // Sort Order
    update_post_meta( $post_id, '_rmm_sort_order', absint( $_POST['rmm_sort_order'] ?? 10 ) );

    // Availability
    $avail = ( sanitize_text_field( $_POST['rmm_available'] ?? '1' ) === '0' ) ? '0' : '1';
    update_post_meta( $post_id, '_rmm_available', $avail );

    // Featured
    update_post_meta( $post_id, '_rmm_featured', isset( $_POST['rmm_featured'] ) ? '1' : '0' );

    // Section
    $section_id = absint( $_POST['rmm_section_qe'] ?? 0 );
    wp_set_post_terms( $post_id, $section_id ? [ $section_id ] : [], 'rmm_section' );

    // Menus
    $menus = array_map( 'absint', (array) ( $_POST['rmm_menus'] ?? [] ) );
    update_post_meta( $post_id, '_rmm_menus', $menus );

    // Return refreshed column HTML for immediate DOM update.
    $p_new     = get_post_meta( $post_id, '_rmm_price',     true );
    $avail_new = get_post_meta( $post_id, '_rmm_available', true );
    $feat_new  = get_post_meta( $post_id, '_rmm_featured',  true );

    wp_send_json_success( [
        'price_html' => $p_new
            ? '<strong>$' . esc_html( $p_new ) . '</strong>'
            : '—',
        'avail_html' => $avail_new === '0'
            ? '<span style="color:#d63638">🚫 86\'d</span>'
            : '<span style="color:#00a32a">✅ Available</span>',
        'feat_html'  => $feat_new === '1' ? '⭐' : '—',
        'menus_html' => rmm_get_menu_pills( $post_id ),
    ] );
}
