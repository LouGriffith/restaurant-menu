<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════════════════════
// [restaurant_menu] — full menu display
// ══════════════════════════════════════════════════════════════════════════════

add_shortcode( 'restaurant_menu', 'rmm_shortcode_handler' );

function rmm_shortcode_handler( $atts ) {
    $atts = shortcode_atts( [
        'id'            => '',       // Menu post ID (required)
        'featured'      => 'false',  // 'true' = only featured items
        'section'       => '',       // slug of a single section to show
        'layout'        => '',       // list | two-column | cards  (falls back to menu setting)
        'show_images'   => '',       // true | false
        'show_sections' => '',       // true | false
        'limit'         => 0,        // max items (0 = all)
        'badge'         => '',       // filter by badge slug
    ], $atts, 'restaurant_menu' );

    if ( empty( $atts['id'] ) ) {
        return '<p class="rmm-error">⚠️ <strong>restaurant_menu</strong>: please set <code>id=""</code>.</p>';
    }

    $menu_id = absint( $atts['id'] );
    $menu    = get_post( $menu_id );
    if ( ! $menu || $menu->post_type !== 'rmm_menu' ) {
        return '<p class="rmm-error">⚠️ Menu not found (id=' . $menu_id . ').</p>';
    }

    // ── Resolve display options (shortcode attr overrides menu-level setting) ─
    $layout = $atts['layout']
        ?: ( get_post_meta( $menu_id, '_rmm_layout', true ) ?: 'list' );

    $show_images = $atts['show_images'] !== ''
        ? filter_var( $atts['show_images'], FILTER_VALIDATE_BOOLEAN )
        : ( get_post_meta( $menu_id, '_rmm_show_images', true ) !== '0' );

    $show_sections = $atts['show_sections'] !== ''
        ? filter_var( $atts['show_sections'], FILTER_VALIDATE_BOOLEAN )
        : ( get_post_meta( $menu_id, '_rmm_show_sections', true ) !== '0' );

    $featured_only = filter_var( $atts['featured'], FILTER_VALIDATE_BOOLEAN );
    $menu_note     = get_post_meta( $menu_id, '_rmm_menu_note', true );

    // ── Query items ───────────────────────────────────────────────────────────
    $meta_query = [
        [ 'key' => '_rmm_menus',     'value' => $menu_id, 'compare' => 'LIKE' ],
        [ 'key' => '_rmm_available', 'value' => '1',      'compare' => '='    ],
    ];
    if ( $featured_only ) {
        $meta_query[] = [ 'key' => '_rmm_featured', 'value' => '1', 'compare' => '=' ];
    }

    $tax_query = [];
    if ( $atts['section'] ) {
        $tax_query[] = [ 'taxonomy' => 'rmm_section', 'field' => 'slug', 'terms' => $atts['section'] ];
    }
    if ( $atts['badge'] ) {
        $tax_query[] = [ 'taxonomy' => 'rmm_badge', 'field' => 'slug', 'terms' => $atts['badge'] ];
    }

    $query_args = [
        'post_type'      => 'rmm_menu_item',
        'post_status'    => 'publish',
        'posts_per_page' => $atts['limit'] ? absint( $atts['limit'] ) : -1,
        'meta_query'     => $meta_query,
        'meta_key'       => '_rmm_sort_order',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    ];
    if ( $tax_query ) {
        $query_args['tax_query'] = $tax_query;
    }

    $items = get_posts( $query_args );
    if ( empty( $items ) ) {
        return '<p class="rmm-empty">No menu items found.</p>';
    }

    // ── Group by section ──────────────────────────────────────────────────────
    $sections = [];
    foreach ( $items as $item ) {
        $item_sections = get_the_terms( $item->ID, 'rmm_section' );
        $sec_name  = 'Other';
        $sec_slug  = 'other';
        $sec_order = 999;
        if ( $item_sections && ! is_wp_error( $item_sections ) ) {
            $s = $item_sections[0];
            $sec_name  = $s->name;
            $sec_slug  = $s->slug;
            $sec_order = $s->term_id;
        }
        $sections[ $sec_slug ]['name']    = $sec_name;
        $sections[ $sec_slug ]['order']   = $sec_order;
        $sections[ $sec_slug ]['items'][] = $item;
    }
    uasort( $sections, fn( $a, $b ) => $a['order'] <=> $b['order'] );

    // ── Schema.org JSON-LD ────────────────────────────────────────────────────
    $schema_output = function_exists( 'rmm_build_schema' ) ? rmm_build_schema( $menu, $items ) : '';

    // ── Render ────────────────────────────────────────────────────────────────
    $layout_class = 'rmm-layout-' . sanitize_html_class( $layout );
    $img_class    = $show_images ? 'rmm-has-images' : 'rmm-no-images';

    ob_start();
    echo $schema_output;
    ?>
    <div class="rmm-menu-wrapper <?php echo esc_attr( $layout_class ); ?> <?php echo esc_attr( $img_class ); ?>"
         data-menu-id="<?php echo esc_attr( $menu_id ); ?>"
         itemscope itemtype="https://schema.org/Menu">

        <?php foreach ( $sections as $sec_slug => $sec_data ) : ?>
        <div class="rmm-section" id="rmm-section-<?php echo esc_attr( $sec_slug ); ?>">

            <?php
            $show_header = $show_sections && (
                count( $sections ) > 1
                || ( count( $sections ) === 1 && $sec_slug !== 'other' )
            );
            if ( $show_header ) :
            ?>
                <h3 class="rmm-section-header"><?php echo esc_html( $sec_data['name'] ); ?></h3>
            <?php endif; ?>

            <div class="rmm-items-grid">
                <?php foreach ( $sec_data['items'] as $item ) : ?>
                    <?php rmm_render_item( $item, $show_images, $layout ); ?>
                <?php endforeach; ?>
            </div>

        </div>
        <?php endforeach; ?>

        <?php if ( $menu_note ) : ?>
            <p class="rmm-menu-note"><?php echo esc_html( $menu_note ); ?></p>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

// ── Render a single menu item ─────────────────────────────────────────────────
function rmm_render_item( $item, $show_images, $layout ) {
    $price      = get_post_meta( $item->ID, '_rmm_price',      true );
    $price_note = get_post_meta( $item->ID, '_rmm_price_note', true );
    $featured   = get_post_meta( $item->ID, '_rmm_featured',   true );
    $short_desc = get_post_meta( $item->ID, '_rmm_short_desc', true );
    $badges     = get_the_terms( $item->ID, 'rmm_badge' );
    $has_image  = has_post_thumbnail( $item->ID );
    $item_class = 'rmm-item' . ( $featured === '1' ? ' rmm-featured' : '' );
    ?>
    <div class="<?php echo esc_attr( $item_class ); ?>"
         itemscope itemprop="hasMenuItem" itemtype="https://schema.org/MenuItem">

        <?php if ( $show_images && $has_image ) : ?>
        <div class="rmm-item-image">
            <?php echo get_the_post_thumbnail( $item->ID, 'medium', [ 'itemprop' => 'image' ] ); ?>
        </div>
        <?php endif; ?>

        <div class="rmm-item-body">
            <div class="rmm-item-header">
                <h4 class="rmm-item-name" itemprop="name"><?php echo esc_html( $item->post_title ); ?></h4>
                <?php if ( $price ) : ?>
                <span class="rmm-item-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                    <span itemprop="price" content="<?php echo esc_attr( $price ); ?>">$<?php echo esc_html( $price ); ?></span>
                    <?php if ( $price_note ) : ?>
                        <small class="rmm-price-note"><?php echo esc_html( $price_note ); ?></small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if ( $short_desc ) : ?>
            <p class="rmm-item-desc" itemprop="description"><?php echo wp_kses_post( $short_desc ); ?></p>
            <?php endif; ?>

            <?php if ( $badges && ! is_wp_error( $badges ) ) : ?>
            <div class="rmm-badges">
                <?php foreach ( $badges as $badge ) : ?>
                <span class="rmm-badge rmm-badge-<?php echo esc_attr( $badge->slug ); ?>">
                    <?php echo esc_html( $badge->name ); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
    <?php
}


// ══════════════════════════════════════════════════════════════════════════════
// [rmm_featured_item] — spotlight a single item or random featured from a menu
// ══════════════════════════════════════════════════════════════════════════════

add_shortcode( 'rmm_featured_item', 'rmm_featured_item_shortcode' );

function rmm_featured_item_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'id'           => '',      // Specific item post ID
        'menu_id'      => '',      // Pull a random featured item from this menu
        'show_image'   => 'true',  // true | false
        'show_desc'    => 'true',  // true | false
        'show_section' => 'true',  // true | false
        'show_menu'    => 'true',  // true | false
        'show_price'   => 'true',  // true | false
        'class'        => '',      // extra CSS class for custom styling
    ], $atts, 'rmm_featured_item' );

    $item = null;

    // ── Mode 1: specific item by ID ───────────────────────────────────────────
    if ( ! empty( $atts['id'] ) ) {
        $post = get_post( absint( $atts['id'] ) );
        if ( $post && $post->post_type === 'rmm_menu_item' && $post->post_status === 'publish' ) {
            $item = $post;
        }
    }

    // ── Mode 2: random featured item from a menu ──────────────────────────────
    if ( ! $item && ! empty( $atts['menu_id'] ) ) {
        $menu_id = absint( $atts['menu_id'] );

        $candidates = get_posts( [
            'post_type'      => 'rmm_menu_item',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'rand',   // randomised at DB level
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => '_rmm_menus',     'value' => '"' . $menu_id . '"', 'compare' => 'LIKE' ],
                [ 'key' => '_rmm_featured',  'value' => '1',                  'compare' => '='    ],
                [ 'key' => '_rmm_available', 'value' => '1',                  'compare' => '='    ],
            ],
        ] );

        if ( ! empty( $candidates ) ) {
            $item = $candidates[0]; // already randomised by ORDER BY RAND()
        }
    }

    if ( ! $item ) {
        return '<!-- rmm_featured_item: no item found -->';
    }

    // ── Resolve display flags ─────────────────────────────────────────────────
    $show_image   = filter_var( $atts['show_image'],   FILTER_VALIDATE_BOOLEAN );
    $show_desc    = filter_var( $atts['show_desc'],    FILTER_VALIDATE_BOOLEAN );
    $show_section = filter_var( $atts['show_section'], FILTER_VALIDATE_BOOLEAN );
    $show_menu    = filter_var( $atts['show_menu'],    FILTER_VALIDATE_BOOLEAN );
    $show_price   = filter_var( $atts['show_price'],   FILTER_VALIDATE_BOOLEAN );

    // ── Fetch metadata ────────────────────────────────────────────────────────
    $price      = get_post_meta( $item->ID, '_rmm_price',      true );
    $price_note = get_post_meta( $item->ID, '_rmm_price_note', true );
    $short_desc = get_post_meta( $item->ID, '_rmm_short_desc', true );
    $has_image  = has_post_thumbnail( $item->ID );

    // Section
    $section_label = '';
    if ( $show_section ) {
        $sec_terms = get_the_terms( $item->ID, 'rmm_section' );
        if ( $sec_terms && ! is_wp_error( $sec_terms ) ) {
            $section_label = $sec_terms[0]->name;
        }
    }

    // Menus this item belongs to
    $menu_labels = [];
    if ( $show_menu ) {
        $menu_ids = (array) get_post_meta( $item->ID, '_rmm_menus', true );
        foreach ( array_filter( array_map( 'absint', $menu_ids ) ) as $mid ) {
            $t = get_the_title( $mid );
            if ( $t ) $menu_labels[] = $t;
        }
    }

    $extra_class = $atts['class'] ? ' ' . sanitize_html_class( $atts['class'] ) : '';

    // ── Render ────────────────────────────────────────────────────────────────
    ob_start();
    ?>
    <div class="rmm-featured-spotlight<?php echo esc_attr( $extra_class ); ?>"
         itemscope itemtype="https://schema.org/MenuItem">

        <?php if ( $show_image && $has_image ) : ?>
        <div class="rmm-spotlight-image">
            <?php echo get_the_post_thumbnail( $item->ID, 'large', [ 'itemprop' => 'image' ] ); ?>
        </div>
        <?php endif; ?>

        <div class="rmm-spotlight-body">

            <?php if ( $section_label || $menu_labels ) : ?>
            <div class="rmm-spotlight-meta">
                <?php if ( $section_label ) : ?>
                    <span class="rmm-spotlight-section"><?php echo esc_html( $section_label ); ?></span>
                <?php endif; ?>
                <?php if ( $menu_labels ) : ?>
                    <span class="rmm-spotlight-menus"><?php echo esc_html( implode( ', ', $menu_labels ) ); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <h3 class="rmm-spotlight-name" itemprop="name">
                <?php echo esc_html( $item->post_title ); ?>
            </h3>

            <?php if ( $show_price && $price ) : ?>
            <div class="rmm-spotlight-price"
                 itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <span itemprop="price" content="<?php echo esc_attr( $price ); ?>">
                    $<?php echo esc_html( $price ); ?>
                </span>
                <?php if ( $price_note ) : ?>
                    <small class="rmm-price-note"><?php echo esc_html( $price_note ); ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ( $show_desc && $short_desc ) : ?>
            <p class="rmm-spotlight-desc" itemprop="description">
                <?php echo wp_kses_post( $short_desc ); ?>
            </p>
            <?php endif; ?>

        </div>

    </div>
    <?php
    return ob_get_clean();
}
