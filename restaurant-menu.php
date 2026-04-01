<?php
/**
 * Plugin Name: Restaurant Menu Manager
 * Plugin URI:  https://github.com/LouGriffith/restaurant-menu
 * Description: A complete restaurant menu management system with multiple menus, categories, featured items, dietary badges, photo support, shortcodes, and Google-friendly Schema.org structured data.
 * Version:     1.5.2
 * Author:      Your Agency
 * License:     GPL-2.0+
 * Text Domain: restaurant-menu
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RMM_VERSION',     '1.5.2' );
define( 'RMM_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'RMM_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// ─── Load modules ────────────────────────────────────────────────────────────
require_once RMM_PLUGIN_DIR . 'includes/post-types.php';
require_once RMM_PLUGIN_DIR . 'includes/taxonomies.php';
require_once RMM_PLUGIN_DIR . 'includes/meta-boxes.php';
require_once RMM_PLUGIN_DIR . 'includes/shortcode.php';
require_once RMM_PLUGIN_DIR . 'includes/schema.php';
require_once RMM_PLUGIN_DIR . 'includes/settings.php';
require_once RMM_PLUGIN_DIR . 'includes/info-shortcodes.php';
require_once RMM_PLUGIN_DIR . 'includes/updater.php';
require_once RMM_PLUGIN_DIR . 'admin/admin-columns.php';

// ─── Activation / deactivation ───────────────────────────────────────────────
register_activation_hook( __FILE__, 'rmm_activate' );
function rmm_activate() {
    rmm_register_post_types();
    rmm_register_taxonomies();
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, function() { flush_rewrite_rules(); } );

// ─── Enqueue front-end assets ────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'rmm_enqueue_public_assets' );
function rmm_enqueue_public_assets() {
    wp_enqueue_style(
        'rmm-public',
        RMM_PLUGIN_URL . 'public/css/menu-display.css',
        [],
        RMM_VERSION
    );
    wp_enqueue_script(
        'rmm-public',
        RMM_PLUGIN_URL . 'public/js/menu-display.js',
        [ 'jquery' ],
        RMM_VERSION,
        true
    );
}

// ─── Enqueue admin assets ─────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'rmm_enqueue_admin_assets' );
function rmm_enqueue_admin_assets( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, [ 'rmm_menu_item', 'rmm_menu' ] ) ) return;

    wp_enqueue_style(
        'rmm-admin',
        RMM_PLUGIN_URL . 'admin/admin-style.css',
        [],
        RMM_VERSION
    );

    wp_enqueue_script(
        'rmm-admin',
        RMM_PLUGIN_URL . 'admin/admin-script.js',
        [ 'jquery', 'wp-media' ],
        RMM_VERSION,
        true
    );
}
