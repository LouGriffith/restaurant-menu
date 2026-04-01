<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [rmm_info] shortcode
 *
 * Outputs a single restaurant info field from plugin settings.
 * One place to edit, use anywhere on the site.
 *
 * Usage:
 *   [rmm_info field="name"]
 *   [rmm_info field="phone"]
 *   [rmm_info field="address"]
 *   [rmm_info field="cuisine"]
 *
 * Optional attributes:
 *   before=""   — HTML prepended to the output (only if field has a value)
 *   after=""    — HTML appended to the output (only if field has a value)
 *   fallback="" — Text shown when the field is empty (default: silent)
 *
 * Examples:
 *   Call us: [rmm_info field="phone"]
 *   [rmm_info field="address" before="<address>" after="</address>"]
 */

add_shortcode( 'rmm_info', 'rmm_info_shortcode' );

function rmm_info_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'field'    => '',
        'before'   => '',
        'after'    => '',
        'fallback' => '',
    ], $atts, 'rmm_info' );

    $s = get_option( 'rmm_settings', [] );

    // Map shortcode field name → settings key
    $field_map = [
        'name'    => 'restaurant_name',
        'phone'   => 'restaurant_phone',
        'address' => 'restaurant_address',
        'cuisine' => 'restaurant_cuisine',
    ];

    $key = $field_map[ strtolower( trim( $atts['field'] ) ) ] ?? '';

    if ( ! $key ) {
        // Invalid field attribute — fail silently in production
        if ( current_user_can( 'manage_options' ) ) {
            return '<span style="color:#d63638;font-size:12px">[rmm_info: unknown field "' . esc_html( $atts['field'] ) . '". Use: name, phone, address, cuisine]</span>';
        }
        return '';
    }

    $value = trim( $s[ $key ] ?? '' );

    if ( $value === '' ) {
        return esc_html( $atts['fallback'] );
    }

    // ── Field-specific rendering ──────────────────────────────────────────────

    if ( $key === 'restaurant_phone' ) {
        $output = rmm_render_phone( $value );
    } elseif ( $key === 'restaurant_address' ) {
        $output = '<span class="rmm-info-address">' . esc_html( $value ) . '</span>';
    } else {
        $output = '<span class="rmm-info-' . sanitize_html_class( $atts['field'] ) . '">' . esc_html( $value ) . '</span>';
    }

    return $atts['before'] . $output . $atts['after'];
}

/**
 * Render the phone number.
 * On mobile screens the number is wrapped in a tel: link.
 * On desktop it is plain text.
 * We use a CSS class + inline style to avoid JavaScript.
 */
function rmm_render_phone( $phone ) {
    // Strip everything except digits and + for the tel: href
    $tel = preg_replace( '/[^\d+]/', '', $phone );

    // The link is hidden on desktop via CSS; shown only on mobile.
    // The plain-text span is shown on desktop; hidden on mobile.
    return
        '<span class="rmm-phone-desktop" aria-hidden="true">' . esc_html( $phone ) . '</span>' .
        '<a class="rmm-phone-mobile" href="tel:' . esc_attr( $tel ) . '">' . esc_html( $phone ) . '</a>';
}

/**
 * Inject the tiny CSS needed for the mobile-only phone link.
 * Only outputs when the shortcode is actually used on the page.
 */
add_action( 'wp_head', 'rmm_info_phone_css' );
function rmm_info_phone_css() {
    ?>
<style id="rmm-info-styles">
/* rmm_info phone: desktop shows plain text, mobile shows the link */
.rmm-phone-mobile  { display: none; }
@media ( max-width: 767px ) {
    .rmm-phone-desktop { display: none; }
    .rmm-phone-mobile  {
        display: inline;
        color: inherit;
        text-decoration: none;
        border-bottom: 1px solid currentColor;
    }
}
</style>
    <?php
}
