<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'rmm_register_taxonomies' );
function rmm_register_taxonomies() {

    // ── Menu Section (Appetizers, Salads, Entrées, Desserts, etc.) ────────────
    register_taxonomy( 'rmm_section', 'rmm_menu_item', [
        'labels' => [
            'name'          => 'Menu Sections',
            'singular_name' => 'Menu Section',
            'add_new_item'  => 'Add New Section',
            'edit_item'     => 'Edit Section',
            'new_item_name' => 'New Section Name',
            'search_items'  => 'Search Sections',
            'all_items'     => 'All Sections',
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'rewrite'           => false,
    ] );

    // ── Dietary / Badge Flags ─────────────────────────────────────────────────
    register_taxonomy( 'rmm_badge', 'rmm_menu_item', [
        'labels' => [
            'name'          => 'Dietary Badges',
            'singular_name' => 'Badge',
            'add_new_item'  => 'Add New Badge',
            'all_items'     => 'All Badges',
        ],
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'rewrite'           => false,
    ] );
}

// ── Seed default badges on first run ─────────────────────────────────────────
add_action( 'init', 'rmm_seed_default_badges', 20 );
function rmm_seed_default_badges() {
    $defaults = [
        'gluten-free' => 'Gluten-Free',
        'vegetarian'  => 'Vegetarian / Vegan',
        'chefs-rec'   => "Chef's Recommendation",
        'seasonal'    => 'Seasonal / Limited',
    ];
    foreach ( $defaults as $slug => $name ) {
        if ( ! term_exists( $slug, 'rmm_badge' ) ) {
            wp_insert_term( $name, 'rmm_badge', [ 'slug' => $slug ] );
        }
    }
}
