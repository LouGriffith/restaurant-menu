<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Per-menu sort order.
 *
 * Storage: post meta `_rmm_sort_order_by_menu`
 *   Serialized array keyed by menu ID → integer position (1-based)
 *   e.g. [ 12 => 1, 12 => 2, 34 => 1, ... ]  per item
 *
 * Fallback: global `_rmm_sort_order` used when no per-menu value exists.
 */

// ── Helper: get position for one item in one menu ────────────────────────────
function rmm_get_item_menu_order( $item_id, $menu_id ) {
    $map = get_post_meta( $item_id, '_rmm_sort_order_by_menu', true );
    if ( is_array( $map ) && isset( $map[ $menu_id ] ) ) {
        return (int) $map[ $menu_id ];
    }
    // Fall back to global sort order
    $global = get_post_meta( $item_id, '_rmm_sort_order', true );
    return $global !== '' ? (int) $global : 999;
}

// ── Helper: set position for one item in one menu ────────────────────────────
function rmm_set_item_menu_order( $item_id, $menu_id, $position ) {
    $map = get_post_meta( $item_id, '_rmm_sort_order_by_menu', true );
    if ( ! is_array( $map ) ) $map = [];
    $map[ (int) $menu_id ] = (int) $position;
    update_post_meta( $item_id, '_rmm_sort_order_by_menu', $map );
}

// ── AJAX: save a full ordered list for a menu ─────────────────────────────────
// Receives: menu_id, ordered_ids[] (array of item IDs in new order)
add_action( 'wp_ajax_rmm_save_menu_order', 'rmm_ajax_save_menu_order' );
function rmm_ajax_save_menu_order() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }
    if ( ! check_ajax_referer( 'rmm_sort_order', 'nonce', false ) ) {
        wp_send_json_error( 'Bad nonce', 403 );
    }

    $menu_id     = absint( $_POST['menu_id']      ?? 0 );
    $ordered_ids = array_map( 'absint', (array) ( $_POST['ordered_ids'] ?? [] ) );

    if ( ! $menu_id || empty( $ordered_ids ) ) {
        wp_send_json_error( 'Missing data', 400 );
    }

    foreach ( $ordered_ids as $position => $item_id ) {
        if ( get_post_type( $item_id ) === 'rmm_menu_item' ) {
            rmm_set_item_menu_order( $item_id, $menu_id, $position + 1 );
        }
    }

    wp_send_json_success( [ 'saved' => count( $ordered_ids ) ] );
}

// ── AJAX: also available on frontend (same handler, same cap check) ───────────
add_action( 'wp_ajax_nopriv_rmm_save_menu_order', function() {
    wp_send_json_error( 'Unauthorized', 403 );
} );
