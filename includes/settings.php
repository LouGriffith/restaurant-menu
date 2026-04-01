<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'rmm_add_settings_page' );
function rmm_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=rmm_menu_item',
        'Restaurant Menu Settings',
        'Settings',
        'manage_options',
        'rmm-settings',
        'rmm_render_settings_page'
    );
}

add_action( 'admin_init', 'rmm_register_settings' );
function rmm_register_settings() {
    register_setting( 'rmm_settings_group', 'rmm_settings', 'rmm_sanitize_settings' );
}

// Centralised element definitions — label, CSS var, default size, placeholder class
function rmm_typography_elements() {
    return [
        'menu_display' => [
            'label' => 'Menu Display',
            'elements' => [
                'base'    => [ 'label' => 'Body / Description',   'prop' => '--rmm-font-size-base',    'default' => '1rem',      'placeholder' => 'rcc_body_description'   ],
                'item'    => [ 'label' => 'Item Name',             'prop' => '--rmm-font-size-item',    'default' => '1.0625rem', 'placeholder' => 'rcc_item_name'          ],
                'price'   => [ 'label' => 'Price',                 'prop' => '--rmm-font-size-item',    'default' => '1.0625rem', 'placeholder' => 'rcc_price'              ],
                'section' => [ 'label' => 'Section Header',        'prop' => '--rmm-font-size-section', 'default' => '1.25rem',   'placeholder' => 'rcc_section_header'     ],
                'sm'      => [ 'label' => 'Price Notes & Badges',  'prop' => '--rmm-font-size-sm',      'default' => '0.875rem',  'placeholder' => 'rcc_price_notes'        ],
                'note'    => [ 'label' => 'Menu Footnote',         'prop' => '--rmm-font-size-note',    'default' => '0.875rem',  'placeholder' => 'rcc_menu_footnote'      ],
            ],
        ],
        'spotlight' => [
            'label' => 'Featured Item Spotlight',
            'elements' => [
                'spotlight_name'  => [ 'label' => 'Item Name',            'prop' => '--rmm-font-size-spotlight-name',  'default' => '1.5rem',    'placeholder' => 'rcc_spotlight_name'  ],
                'spotlight_price' => [ 'label' => 'Price',                'prop' => '--rmm-font-size-spotlight-price', 'default' => '1.125rem',  'placeholder' => 'rcc_spotlight_price' ],
                'spotlight_desc'  => [ 'label' => 'Description',          'prop' => '--rmm-font-size-spotlight-desc',  'default' => '1rem',      'placeholder' => 'rcc_spotlight_desc'  ],
                'spotlight_meta'  => [ 'label' => 'Section / Menu Label', 'prop' => '--rmm-font-size-spotlight-meta',  'default' => '0.8125rem', 'placeholder' => 'rcc_spotlight_meta'  ],
            ],
        ],
    ];
}

function rmm_sanitize_settings( $input ) {
    $clean = [];

    $clean['restaurant_name']    = sanitize_text_field( $input['restaurant_name']    ?? '' );
    $clean['restaurant_address'] = sanitize_text_field( $input['restaurant_address'] ?? '' );
    $clean['restaurant_phone']   = sanitize_text_field( $input['restaurant_phone']   ?? '' );
    $clean['restaurant_cuisine'] = sanitize_text_field( $input['restaurant_cuisine'] ?? '' );

    foreach ( rmm_typography_elements() as $group ) {
        foreach ( $group['elements'] as $key => $el ) {
            $size = trim( $input[ 'font_size_' . $key ] ?? '' );
            $clean[ 'font_size_' . $key ] = preg_match( '/^\d+(\.\d+)?(px|rem|em|%)?$/', $size ) ? $size : '';

            $class   = trim( $input[ 'css_class_' . $key ] ?? '' );
            $classes = array_filter( array_map( 'sanitize_html_class', explode( ' ', $class ) ) );
            $clean[ 'css_class_' . $key ] = implode( ' ', $classes );
        }
    }

    return $clean;
}

add_action( 'wp_head', 'rmm_output_typography_css' );
function rmm_output_typography_css() {
    $s    = get_option( 'rmm_settings', [] );
    $vars = [];
    $css  = '';

    foreach ( rmm_typography_elements() as $group ) {
        foreach ( $group['elements'] as $key => $el ) {
            $size = $s[ 'font_size_' . $key ] ?? '';
            if ( $size !== '' ) {
                $val    = preg_match( '/^\d+(\.\d+)?$/', $size ) ? $size . 'px' : $size;
                $vars[] = '    ' . $el['prop'] . ': ' . $val . ';';
            }
            $extra = trim( $s[ 'css_class_' . $key ] ?? '' );
            if ( $extra !== '' ) {
                $selectors = array_map(
                    fn( $c ) => '.' . sanitize_html_class( $c ),
                    array_filter( explode( ' ', $extra ) )
                );
                if ( $selectors ) {
                    $css .= implode( ', ', $selectors ) . " { font-size: var(" . $el['prop'] . "); }\n";
                }
            }
        }
    }

    $output = '';
    if ( $vars ) $output .= ":root {\n" . implode( "\n", $vars ) . "\n}\n";
    $output .= $css;
    if ( $output ) echo "<style id=\"rmm-typography\">\n{$output}</style>\n";
}

function rmm_render_settings_page() {
    $s = get_option( 'rmm_settings', [] );
    ?>
    <div class="wrap">
        <h1>🍽️ Restaurant Menu Manager — Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'rmm_settings_group' ); ?>

            <div style="display:grid;grid-template-columns:1fr 400px;gap:24px;margin-top:20px;align-items:start;">

                <!-- ════════════════════════════════════════════
                     LEFT: Quick Start + Shortcode Reference
                     ════════════════════════════════════════════ -->
                <div>

                    <!-- Quick Start -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">🚀 Quick Start</h2>
                        </div>
                        <div class="inside" style="font-size:13px">
                            <ol style="padding-left:18px;line-height:2">
                                <li>Go to <strong>Sections</strong> → create your sections (Appetizers, Entrées…)</li>
                                <li>Go to <strong>All Menus → Add New</strong> → name it and choose a default layout</li>
                                <li>Go to <strong>Add New Item</strong> → fill in name, description, price, assign to a menu and section</li>
                                <li>Copy the shortcode from the menu edit screen and paste it into any page</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Shortcode Reference -->
                    <div class="postbox" style="margin-top:16px">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">📋 Shortcode Reference</h2>
                        </div>
                        <div class="inside" style="font-size:13px">

                            <p style="font-weight:600;margin-bottom:2px">[restaurant_menu]</p>
                            <p style="color:#646970;margin-top:0">Displays a full menu with sections, layouts, and filters.</p>
                            <p>
                                <code>[restaurant_menu id="123"]</code><br>
                                <code>[restaurant_menu id="123" layout="list"]</code><br>
                                <code>[restaurant_menu id="123" layout="two-column"]</code><br>
                                <code>[restaurant_menu id="123" layout="cards"]</code><br>
                                <code>[restaurant_menu id="123" show_images="true" show_sections="true"]</code><br>
                                <code>[restaurant_menu id="123" featured="true"]</code><br>
                                <code>[restaurant_menu id="123" section="appetizers"]</code><br>
                                <code>[restaurant_menu id="123" badge="gluten-free"]</code><br>
                                <code>[restaurant_menu id="123" limit="6"]</code>
                            </p>
                            <p style="color:#646970;font-style:italic;margin-top:-4px">Omit <code>layout</code> to use the layout set on the menu edit screen.</p>

                            <hr>

                            <p style="font-weight:600;margin-bottom:2px">[rmm_featured_item]</p>
                            <p style="color:#646970;margin-top:0">Spotlight a specific item or rotate a random featured item on every visit.</p>
                            <p>
                                <code>[rmm_featured_item id="456"]</code><br>
                                <code>[rmm_featured_item menu_id="123"]</code><br>
                                <code>[rmm_featured_item menu_id="123" show_image="true" show_desc="true"<br>&nbsp;&nbsp;show_section="true" show_menu="true" show_price="true"]</code><br>
                                <code>[rmm_featured_item menu_id="123" class="sidebar-pick"]</code>
                            </p>

                            <hr>

                            <p style="font-weight:600;margin-bottom:2px">[rmm_info]</p>
                            <p style="color:#646970;margin-top:0">Output restaurant info anywhere on the site — one place to edit.</p>
                            <p>
                                <code>[rmm_info field="name"]</code><br>
                                <code>[rmm_info field="phone"]</code><br>
                                <code>[rmm_info field="address"]</code><br>
                                <code>[rmm_info field="cuisine"]</code><br>
                                <code>[rmm_info field="phone" before="&lt;strong&gt;Call: &lt;/strong&gt;"]</code><br>
                                <code>[rmm_info field="address" before="&lt;address&gt;" after="&lt;/address&gt;"]</code>
                            </p>

                            <hr>
                            <p><strong>Layout options:</strong> <code>list</code> · <code>two-column</code> · <code>cards</code></p>
                            <p><strong>Badge slugs:</strong> <code>gluten-free</code> · <code>vegetarian</code> · <code>chefs-rec</code> · <code>seasonal</code></p>

                        </div>
                    </div>

                    <?php submit_button( 'Save Settings' ); ?>

                </div><!-- /left col -->

                <!-- ════════════════════════════════════════════
                     RIGHT: Restaurant Info + Typography
                     ════════════════════════════════════════════ -->
                <div>

                    <!-- Restaurant Information -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">📍 Restaurant Information</h2>
                        </div>
                        <div class="inside">
                            <p style="color:#646970;margin-top:0;font-size:12px">Used for Google Schema.org structured data and <code>[rmm_info]</code> shortcodes.</p>
                            <style>
                            .rmm-info-field { margin-bottom:12px; }
                            .rmm-info-field label { display:block; font-weight:600; font-size:13px; margin-bottom:4px; color:#1d2327; }
                            .rmm-info-field input[type=text] { width:100%; box-sizing:border-box; }
                            .rmm-info-field .description { margin-top:4px; font-size:12px; }
                            </style>
                            <div class="rmm-info-field">
                                <label for="rmm_name">Name</label>
                                <input type="text" id="rmm_name" name="rmm_settings[restaurant_name]"
                                    value="<?php echo esc_attr( $s['restaurant_name'] ?? '' ); ?>"
                                    placeholder="Napa Café" />
                            </div>
                            <div class="rmm-info-field">
                                <label for="rmm_address">Address</label>
                                <input type="text" id="rmm_address" name="rmm_settings[restaurant_address]"
                                    value="<?php echo esc_attr( $s['restaurant_address'] ?? '' ); ?>"
                                    placeholder="123 Main St, Memphis, TN 38103" />
                            </div>
                            <div class="rmm-info-field">
                                <label for="rmm_phone">Phone</label>
                                <input type="text" id="rmm_phone" name="rmm_settings[restaurant_phone]"
                                    value="<?php echo esc_attr( $s['restaurant_phone'] ?? '' ); ?>"
                                    placeholder="+1 901-555-0100" />
                            </div>
                            <div class="rmm-info-field">
                                <label for="rmm_cuisine">Cuisine</label>
                                <input type="text" id="rmm_cuisine" name="rmm_settings[restaurant_cuisine]"
                                    value="<?php echo esc_attr( $s['restaurant_cuisine'] ?? '' ); ?>"
                                    placeholder="American, Italian, etc." />
                                <p class="description">Used by Google to categorise your restaurant.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Typography -->
                    <div class="postbox" style="margin-top:16px">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">🔤 Typography</h2>
                        </div>
                        <div class="inside">

                            <p style="color:#646970;margin-top:0;font-size:12px;line-height:1.6">
                                Leave <strong>Size</strong> blank to use the stylesheet default — accepts <code>px</code>, <code>rem</code>, or <code>em</code>. Bare numbers treated as <code>px</code>.<br>
                                <strong>Class Name</strong> applies the same font size to any matching CSS class in your theme.
                            </p>

                            <style>
                            .rmm-typo-table { width:100%; border-collapse:collapse; margin-bottom:4px; }
                            .rmm-typo-table thead th {
                                font-size:11px; font-weight:600; text-transform:uppercase;
                                letter-spacing:.05em; color:#646970;
                                padding:0 6px 6px; border-bottom:2px solid #dcdcde; text-align:left;
                            }
                            .rmm-typo-table tbody td {
                                padding:6px 6px; border-bottom:1px solid #f0f0f1; vertical-align:middle;
                            }
                            .rmm-typo-table tbody tr:last-child td { border-bottom:none; }
                            .rmm-typo-table .col-label { font-size:12px; color:#1d2327; white-space:nowrap; }
                            .rmm-typo-table input[type=text] { width:100%; box-sizing:border-box; font-size:12px; padding:4px 6px; }
                            .rmm-typo-group-head {
                                display:block; font-size:11px; font-weight:700; text-transform:uppercase;
                                letter-spacing:.07em; color:#fff; background:#50575e;
                                padding:5px 8px; margin:16px 0 0;
                            }
                            .rmm-typo-group-head:first-of-type { margin-top:0; }
                            </style>

                            <?php foreach ( rmm_typography_elements() as $group ) : ?>

                            <span class="rmm-typo-group-head"><?php echo esc_html( $group['label'] ); ?></span>
                            <table class="rmm-typo-table">
                                <thead>
                                    <tr>
                                        <th style="width:36%">Element</th>
                                        <th style="width:38%">Class Name</th>
                                        <th style="width:26%">Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ( $group['elements'] as $key => $el ) :
                                    $size_val  = esc_attr( $s[ 'font_size_' . $key ]  ?? '' );
                                    $class_val = esc_attr( $s[ 'css_class_' . $key ] ?? '' );
                                ?>
                                <tr>
                                    <td class="col-label"><?php echo esc_html( $el['label'] ); ?></td>
                                    <td>
                                        <input type="text"
                                            name="rmm_settings[css_class_<?php echo esc_attr( $key ); ?>]"
                                            value="<?php echo $class_val; ?>"
                                            placeholder="<?php echo esc_attr( $el['placeholder'] ); ?>" />
                                    </td>
                                    <td>
                                        <input type="text"
                                            name="rmm_settings[font_size_<?php echo esc_attr( $key ); ?>]"
                                            value="<?php echo $size_val; ?>"
                                            placeholder="<?php echo esc_attr( $el['default'] ); ?>" />
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php endforeach; ?>

                            <details style="margin-top:12px">
                                <summary style="cursor:pointer;font-size:12px;color:#646970;user-select:none">
                                    CSS custom properties (for theme overrides)
                                </summary>
                                <pre style="background:#f6f7f7;padding:10px;border-radius:4px;font-size:11px;overflow-x:auto;white-space:pre-wrap;margin-top:6px">:root {
  --rmm-font-size-base:            16px;
  --rmm-font-size-item:            17px;
  --rmm-font-size-section:         20px;
  --rmm-font-size-sm:              14px;
  --rmm-font-size-note:            14px;
  --rmm-font-size-spotlight-name:  24px;
  --rmm-font-size-spotlight-price: 18px;
  --rmm-font-size-spotlight-desc:  16px;
  --rmm-font-size-spotlight-meta:  13px;
}</pre>
                            </details>

                        </div>
                    </div>

                </div><!-- /right col -->

            </div><!-- /grid -->
        </form>
    </div>
    <?php
}
