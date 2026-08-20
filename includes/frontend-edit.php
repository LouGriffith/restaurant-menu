<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Enqueue assets for logged-in editors only ─────────────────────────────────
add_action( 'wp_enqueue_scripts', 'rmm_enqueue_frontend_edit_assets' );
function rmm_enqueue_frontend_edit_assets() {
    if ( ! current_user_can( 'edit_posts' ) ) return;

    wp_enqueue_style(
        'rmm-frontend-edit',
        RMM_PLUGIN_URL . 'public/css/frontend-edit.css',
        [ 'rmm-public' ],
        RMM_VERSION
    );

    wp_enqueue_script(
        'rmm-frontend-edit',
        RMM_PLUGIN_URL . 'public/js/frontend-edit.js',
        [ 'jquery' ],
        RMM_VERSION,
        true
    );

    // Fetch all sections to populate the dropdown in the edit panel.
    $sections     = get_terms( [
        'taxonomy'   => 'rmm_section',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ] );
    $section_data = [];
    if ( $sections && ! is_wp_error( $sections ) ) {
        foreach ( $sections as $term ) {
            $section_data[] = [ 'id' => $term->term_id, 'name' => $term->name ];
        }
    }

    wp_localize_script( 'rmm-frontend-edit', 'rmmFE', [
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'rmm_frontend_edit' ),
        'sections' => $section_data,
    ] );
}

// ── AJAX handler ──────────────────────────────────────────────────────────────
add_action( 'wp_ajax_rmm_frontend_save', 'rmm_ajax_frontend_save' );
function rmm_ajax_frontend_save() {
    // Auth
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }
    if ( ! check_ajax_referer( 'rmm_frontend_edit', 'nonce', false ) ) {
        wp_send_json_error( 'Bad nonce', 403 );
    }

    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( ! $post_id || get_post_type( $post_id ) !== 'rmm_menu_item' ) {
        wp_send_json_error( 'Invalid post', 400 );
    }

    // ── Item Name → update post title ────────────────────────────────────────
    $name = sanitize_text_field( $_POST['rmm_name'] ?? '' );
    if ( $name ) {
        wp_update_post( [
            'ID'         => $post_id,
            'post_title' => $name,
            'post_name'  => sanitize_title( $name ),
        ] );
    }

    // ── Price ─────────────────────────────────────────────────────────────────
    $price = ltrim( sanitize_text_field( $_POST['rmm_price'] ?? '' ), '$' );
    update_post_meta( $post_id, '_rmm_price', $price );

    // ── Description ───────────────────────────────────────────────────────────
    $desc = sanitize_textarea_field( $_POST['rmm_desc'] ?? '' );
    update_post_meta( $post_id, '_rmm_short_desc', $desc );

    // ── Availability ──────────────────────────────────────────────────────────
    $available = ( sanitize_text_field( $_POST['rmm_available'] ?? '1' ) === '0' ) ? '0' : '1';
    update_post_meta( $post_id, '_rmm_available', $available );

    // ── Featured ──────────────────────────────────────────────────────────────
    $featured = ( sanitize_text_field( $_POST['rmm_featured'] ?? '0' ) === '1' ) ? '1' : '0';
    update_post_meta( $post_id, '_rmm_featured', $featured );

    // ── Section (taxonomy) ────────────────────────────────────────────────────
    $section_id = absint( $_POST['rmm_section'] ?? 0 );
    wp_set_post_terms( $post_id, $section_id ? [ $section_id ] : [], 'rmm_section' );

    // Fetch the updated post title in case it was sanitized differently
    $updated = get_post( $post_id );

    wp_send_json_success( [
        'name'       => $updated->post_title,
        'price'      => get_post_meta( $post_id, '_rmm_price',      true ),
        'desc'       => get_post_meta( $post_id, '_rmm_short_desc', true ),
        'available'  => get_post_meta( $post_id, '_rmm_available',  true ),
        'featured'   => get_post_meta( $post_id, '_rmm_featured',   true ),
        'section_id' => $section_id,
    ] );
}
