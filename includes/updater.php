<?php
/**
 * GitHub Updater for Restaurant Menu Manager
 *
 * Polls a info.json manifest hosted on GitHub Pages.
 * When a new version is found, WordPress shows the standard
 * "Update Available" notice and handles the one-click update.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class RMM_GitHub_Updater {

    // ── Configuration ─────────────────────────────────────────────────────────
    // Must match the installed folder name exactly: folder/main-file.php
    const PLUGIN_SLUG = 'restaurant-menu/restaurant-menu.php';

    // GitHub Pages URL to your info.json manifest
    const INFO_URL = 'https://LouGriffith.github.io/restaurant-menu/info.json';

    // Transient cache key
    const CACHE_KEY = 'rmm_update_info';

    // How long to cache the remote check (12 hours)
    const CACHE_TTL = 43200;

    // ── Bootstrap ─────────────────────────────────────────────────────────────
    public static function init() {
        $instance = new self();
        add_filter( 'pre_set_site_transient_update_plugins', [ $instance, 'check_for_update' ] );
        add_filter( 'plugins_api',                           [ $instance, 'plugin_info' ], 20, 3 );
        add_filter( 'upgrader_post_install',                 [ $instance, 'after_install' ], 10, 3 );
        add_action( 'upgrader_process_complete',             [ $instance, 'flush_cache' ], 10, 2 );
    }

    // ── Fetch & cache remote info.json ────────────────────────────────────────
    private function get_remote_info() {
        $cached = get_transient( self::CACHE_KEY );
        if ( $cached !== false ) return $cached;

        $response = wp_remote_get( self::INFO_URL, [
            'timeout'    => 10,
            'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
        ] );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            // Cache failure briefly so we don't hammer GitHub on every page load
            set_transient( self::CACHE_KEY, null, 300 );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );
        if ( empty( $body->version ) ) return null;

        set_transient( self::CACHE_KEY, $body, self::CACHE_TTL );
        return $body;
    }

    // ── Hook: tell WordPress a new version is available ───────────────────────
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) return $transient;

        $info = $this->get_remote_info();
        if ( ! $info ) return $transient;

        $installed = $transient->checked[ self::PLUGIN_SLUG ] ?? null;
        if ( ! $installed ) return $transient;

        if ( version_compare( $info->version, $installed, '>' ) ) {
            $transient->response[ self::PLUGIN_SLUG ] = (object) [
                'slug'         => dirname( self::PLUGIN_SLUG ),
                'plugin'       => self::PLUGIN_SLUG,
                'new_version'  => $info->version,
                'url'          => $info->homepage      ?? '',
                'package'      => $info->download_url,
                'icons'        => [],
                'banners'      => [],
                'tested'       => $info->tested        ?? '',
                'requires'     => $info->requires      ?? '',
                'requires_php' => $info->requires_php  ?? '',
            ];
        }

        return $transient;
    }

    // ── Hook: populate the "View version X.X details" modal ──────────────────
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) return $result;
        if ( ! isset( $args->slug ) ) return $result;
        if ( $args->slug !== dirname( self::PLUGIN_SLUG ) ) return $result;

        $info = $this->get_remote_info();
        if ( ! $info ) return $result;

        return (object) [
            'name'         => $info->name           ?? 'Restaurant Menu Manager',
            'slug'         => dirname( self::PLUGIN_SLUG ),
            'version'      => $info->version,
            'author'       => $info->author         ?? '',
            'homepage'     => $info->homepage       ?? '',
            'requires'     => $info->requires       ?? '6.0',
            'tested'       => $info->tested         ?? '',
            'requires_php' => $info->requires_php   ?? '7.4',
            'downloaded'   => 0,
            'last_updated' => $info->last_updated   ?? '',
            'sections'     => [
                'description' => $info->sections->description ?? '',
                'changelog'   => $info->sections->changelog   ?? '',
            ],
            'download_link' => $info->download_url,
        ];
    }

    // ── Hook: rename extracted folder to match plugin slug ───────────────────
    // GitHub zips extract as "repo-name-tagname/" — this renames it correctly.
    public function after_install( $response, $hook_extra, $result ) {
        if ( ! isset( $hook_extra['plugin'] ) ) return $response;
        if ( $hook_extra['plugin'] !== self::PLUGIN_SLUG ) return $response;

        global $wp_filesystem;
        $plugin_dir    = WP_PLUGIN_DIR . '/' . dirname( self::PLUGIN_SLUG );
        $wp_filesystem->move( $result['destination'], $plugin_dir, true );
        $result['destination'] = $plugin_dir;

        // Re-activate the plugin after update
        activate_plugin( self::PLUGIN_SLUG );

        return $result;
    }

    // ── Hook: clear cache after a successful update ───────────────────────────
    public function flush_cache( $upgrader, $options ) {
        if (
            $options['action'] === 'update'
            && $options['type'] === 'plugin'
            && isset( $options['plugins'] )
            && in_array( self::PLUGIN_SLUG, $options['plugins'], true )
        ) {
            delete_transient( self::CACHE_KEY );
        }
    }
}

RMM_GitHub_Updater::init();
