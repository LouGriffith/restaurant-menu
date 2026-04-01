<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'rmm_register_post_types' );
function rmm_register_post_types() {

    // ── Menu (container: Dinner Menu, Lunch Menu, etc.) ──────────────────────
    register_post_type( 'rmm_menu', [
        'labels' => [
            'name'               => 'Menus',
            'singular_name'      => 'Menu',
            'add_new_item'       => 'Add New Menu',
            'edit_item'          => 'Edit Menu',
            'new_item'           => 'New Menu',
            'view_item'          => 'View Menu',
            'search_items'       => 'Search Menus',
            'not_found'          => 'No menus found',
            'not_found_in_trash' => 'No menus found in trash',
            'menu_name'          => 'Menus',
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => 'edit.php?post_type=rmm_menu_item',
        'show_in_rest'    => false, // Classic Editor — no block editor
        'supports'        => [ 'title', 'thumbnail' ],
        'menu_icon'       => 'dashicons-food',
        'capability_type' => 'post',
    ] );

    // ── Menu Item ─────────────────────────────────────────────────────────────
    register_post_type( 'rmm_menu_item', [
        'labels' => [
            'name'               => 'Menu Items',
            'singular_name'      => 'Menu Item',
            'add_new_item'       => 'Add New Menu Item',
            'edit_item'          => 'Edit Menu Item',
            'new_item'           => 'New Menu Item',
            'view_item'          => 'View Menu Item',
            'search_items'       => 'Search Menu Items',
            'not_found'          => 'No menu items found',
            'not_found_in_trash' => 'No menu items found in trash',
            'menu_name'          => 'Menu Items',
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'show_in_rest'    => false, // Classic Editor — no block editor
        'supports'        => [ 'title', 'thumbnail' ],
        'menu_icon'       => 'dashicons-carrot',
        'capability_type' => 'post',
        'menu_position'   => 25,
    ] );
}

// ── Enforce Classic Editor for both post types ────────────────────────────────
add_filter( 'use_block_editor_for_post_type', 'rmm_disable_block_editor', 10, 2 );
function rmm_disable_block_editor( $use_block_editor, $post_type ) {
    if ( in_array( $post_type, [ 'rmm_menu', 'rmm_menu_item' ], true ) ) {
        return false;
    }
    return $use_block_editor;
}

// ── Remove default content editor from item edit screen ──────────────────────
add_action( 'init', 'rmm_remove_content_editor', 99 );
function rmm_remove_content_editor() {
    remove_post_type_support( 'rmm_menu_item', 'editor' );
    remove_post_type_support( 'rmm_menu',      'editor' );
}
