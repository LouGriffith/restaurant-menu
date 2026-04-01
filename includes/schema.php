<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function rmm_build_schema( $menu, $items ) {
    $settings     = get_option( 'rmm_settings', [] );
    $rest_name    = $settings['restaurant_name']    ?? get_bloginfo( 'name' );
    $rest_url     = home_url();
    $rest_addr    = $settings['restaurant_address'] ?? '';
    $rest_phone   = $settings['restaurant_phone']   ?? '';
    $rest_cuisine = $settings['restaurant_cuisine'] ?? '';

    $sections_schema = [];
    foreach ( $items as $item ) {
        $item_sections = get_the_terms( $item->ID, 'rmm_section' );
        $sec_name = 'General';
        if ( $item_sections && ! is_wp_error( $item_sections ) ) {
            $sec_name = $item_sections[0]->name;
        }

        $price      = get_post_meta( $item->ID, '_rmm_price',      true );
        $short_desc = get_post_meta( $item->ID, '_rmm_short_desc', true );
        $img_url    = get_the_post_thumbnail_url( $item->ID, 'medium' );
        $badges     = get_the_terms( $item->ID, 'rmm_badge' );

        $item_schema = [ '@type' => 'MenuItem', 'name' => $item->post_title ];

        if ( $short_desc ) $item_schema['description'] = wp_strip_all_tags( $short_desc );
        if ( $img_url )    $item_schema['image']       = $img_url;
        if ( $price ) {
            $item_schema['offers'] = [
                '@type'         => 'Offer',
                'price'         => $price,
                'priceCurrency' => 'USD',
            ];
        }

        if ( $badges && ! is_wp_error( $badges ) ) {
            $diet_map = [
                'vegetarian'  => 'https://schema.org/VegetarianDiet',
                'gluten-free' => 'https://schema.org/GlutenFreeDiet',
            ];
            $diets = [];
            foreach ( $badges as $b ) {
                if ( isset( $diet_map[ $b->slug ] ) ) $diets[] = $diet_map[ $b->slug ];
            }
            if ( $diets ) {
                $item_schema['suitableForDiet'] = count( $diets ) === 1 ? $diets[0] : $diets;
            }
        }

        if ( ! isset( $sections_schema[ $sec_name ] ) ) {
            $sections_schema[ $sec_name ] = [
                '@type'       => 'MenuSection',
                'name'        => $sec_name,
                'hasMenuItem' => [],
            ];
        }
        $sections_schema[ $sec_name ]['hasMenuItem'][] = $item_schema;
    }

    $restaurant = [ '@type' => 'Restaurant', 'name' => $rest_name, 'url' => $rest_url ];
    if ( $rest_phone )   $restaurant['telephone']    = $rest_phone;
    if ( $rest_cuisine ) $restaurant['servesCuisine'] = $rest_cuisine;
    if ( $rest_addr ) {
        $restaurant['address'] = [ '@type' => 'PostalAddress', 'streetAddress' => $rest_addr ];
    }

    $schema = [
        '@context'       => 'https://schema.org',
        '@type'          => 'Menu',
        'name'           => $menu->post_title,
        'hasMenuSection' => array_values( $sections_schema ),
        'inLanguage'     => 'en',
        'provider'       => $restaurant,
    ];

    return '<script type="application/ld+json">'
         . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
         . '</script>' . "\n";
}
