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

function rmm_sanitize_settings( $input ) {
    $clean = [];

    // Restaurant info
    $clean['restaurant_name']    = sanitize_text_field( $input['restaurant_name']    ?? '' );
    $clean['restaurant_address'] = sanitize_text_field( $input['restaurant_address'] ?? '' );
    $clean['restaurant_phone']   = sanitize_text_field( $input['restaurant_phone']   ?? '' );
    $clean['restaurant_cuisine'] = sanitize_text_field( $input['restaurant_cuisine'] ?? '' );

    // Typography — validate numeric values, allow empty (means "use CSS default")
    $size_fields = [
        'font_size_base', 'font_size_item', 'font_size_section',
        'font_size_sm', 'font_size_note',
        'font_size_spotlight_name', 'font_size_spotlight_desc',
        'font_size_spotlight_price', 'font_size_spotlight_meta',
    ];
    foreach ( $size_fields as $f ) {
        $val = trim( $input[ $f ] ?? '' );
        // Accept values like "16", "16px", "1rem", "1.2em"
        $clean[ $f ] = preg_match( '/^\d+(\.\d+)?(px|rem|em|%)?$/', $val ) ? $val : '';
    }

    return $clean;
}

/**
 * Output inline CSS variables driven by settings values.
 * Runs on wp_head so it overrides the stylesheet defaults.
 */
add_action( 'wp_head', 'rmm_output_typography_css' );
function rmm_output_typography_css() {
    $s = get_option( 'rmm_settings', [] );

    $map = [
        'font_size_base'           => '--rmm-font-size-base',
        'font_size_item'           => '--rmm-font-size-item',
        'font_size_section'        => '--rmm-font-size-section',
        'font_size_sm'             => '--rmm-font-size-sm',
        'font_size_note'           => '--rmm-font-size-note',
        'font_size_spotlight_name' => '--rmm-font-size-spotlight-name',
        'font_size_spotlight_desc' => '--rmm-font-size-spotlight-desc',
        'font_size_spotlight_price'=> '--rmm-font-size-spotlight-price',
        'font_size_spotlight_meta' => '--rmm-font-size-spotlight-meta',
    ];

    $vars = [];
    foreach ( $map as $key => $prop ) {
        if ( ! empty( $s[ $key ] ) ) {
            // Auto-append px if user typed a bare number
            $val = preg_match( '/^\d+(\.\d+)?$/', $s[ $key ] ) ? $s[ $key ] . 'px' : $s[ $key ];
            $vars[] = '    ' . $prop . ': ' . esc_attr( $val ) . ';';
        }
    }

    if ( empty( $vars ) ) return;

    echo "<style id=\"rmm-typography\">\n:root {\n" . implode( "\n", $vars ) . "\n}\n</style>\n";
}

function rmm_render_settings_page() {
    $s = get_option( 'rmm_settings', [] );
    ?>
    <div class="wrap">
        <h1>🍽️ Restaurant Menu Manager — Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'rmm_settings_group' ); ?>

            <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;margin-top:20px;align-items:start;">

                <!-- ── Left column: settings ── -->
                <div>

                    <!-- Restaurant Info -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">📍 Restaurant Information</h2>
                        </div>
                        <div class="inside">
                            <p style="color:#646970;margin-top:0">Used for Google Schema.org structured data.</p>
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th><label for="rmm_name">Restaurant Name</label></th>
                                    <td><input type="text" id="rmm_name" name="rmm_settings[restaurant_name]"
                                        value="<?php echo esc_attr( $s['restaurant_name'] ?? '' ); ?>"
                                        class="regular-text" placeholder="Napa Café" /></td>
                                </tr>
                                <tr>
                                    <th><label for="rmm_address">Address</label></th>
                                    <td><input type="text" id="rmm_address" name="rmm_settings[restaurant_address]"
                                        value="<?php echo esc_attr( $s['restaurant_address'] ?? '' ); ?>"
                                        class="regular-text" placeholder="123 Main St, Memphis, TN 38103" /></td>
                                </tr>
                                <tr>
                                    <th><label for="rmm_phone">Phone</label></th>
                                    <td><input type="text" id="rmm_phone" name="rmm_settings[restaurant_phone]"
                                        value="<?php echo esc_attr( $s['restaurant_phone'] ?? '' ); ?>"
                                        class="regular-text" placeholder="+1 901-555-0100" /></td>
                                </tr>
                                <tr>
                                    <th><label for="rmm_cuisine">Cuisine Type</label></th>
                                    <td>
                                        <input type="text" id="rmm_cuisine" name="rmm_settings[restaurant_cuisine]"
                                            value="<?php echo esc_attr( $s['restaurant_cuisine'] ?? '' ); ?>"
                                            class="regular-text" placeholder="American, Italian, etc." />
                                        <p class="description">Used by Google to categorise your restaurant in search results.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Typography -->
                    <div class="postbox" style="margin-top:16px">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">🔤 Typography Sizes</h2>
                        </div>
                        <div class="inside">
                            <p style="color:#646970;margin-top:0">
                                Leave blank to use the stylesheet defaults. Accepts <code>px</code>, <code>rem</code>, or <code>em</code> — e.g. <code>16px</code> or <code>1rem</code>. A bare number is treated as <code>px</code>.
                                You can also override these directly in your theme CSS using the CSS custom properties listed in the reference panel.
                            </p>

                            <h3 style="margin-top:0;font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#646970">Menu Display</h3>
                            <table class="form-table rmm-typo-table" role="presentation">
                                <?php
                                $menu_fields = [
                                    'font_size_base'    => [ 'Body / description text',   '1rem (≈ 16px)'   ],
                                    'font_size_item'    => [ 'Item name',                 '1.0625rem'        ],
                                    'font_size_section' => [ 'Section header',            '1.25rem'          ],
                                    'font_size_sm'      => [ 'Price notes & badges',      '0.875rem'         ],
                                    'font_size_note'    => [ 'Menu footnote',             '0.875rem'         ],
                                ];
                                foreach ( $menu_fields as $key => [ $label, $default ] ) :
                                ?>
                                <tr>
                                    <th style="width:220px"><label for="rmm_<?php echo esc_attr($key); ?>"><?php echo esc_html( $label ); ?></label></th>
                                    <td>
                                        <input type="text" id="rmm_<?php echo esc_attr($key); ?>"
                                            name="rmm_settings[<?php echo esc_attr($key); ?>]"
                                            value="<?php echo esc_attr( $s[$key] ?? '' ); ?>"
                                            style="width:120px" placeholder="<?php echo esc_attr( $default ); ?>" />
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>

                            <h3 style="font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#646970">Featured Item Spotlight</h3>
                            <table class="form-table rmm-typo-table" role="presentation">
                                <?php
                                $spotlight_fields = [
                                    'font_size_spotlight_name'  => [ 'Item name',    '1.5rem'     ],
                                    'font_size_spotlight_price' => [ 'Price',         '1.125rem'   ],
                                    'font_size_spotlight_desc'  => [ 'Description',   '1rem'       ],
                                    'font_size_spotlight_meta'  => [ 'Section / menu label', '0.8125rem' ],
                                ];
                                foreach ( $spotlight_fields as $key => [ $label, $default ] ) :
                                ?>
                                <tr>
                                    <th style="width:220px"><label for="rmm_<?php echo esc_attr($key); ?>"><?php echo esc_html( $label ); ?></label></th>
                                    <td>
                                        <input type="text" id="rmm_<?php echo esc_attr($key); ?>"
                                            name="rmm_settings[<?php echo esc_attr($key); ?>]"
                                            value="<?php echo esc_attr( $s[$key] ?? '' ); ?>"
                                            style="width:120px" placeholder="<?php echo esc_attr( $default ); ?>" />
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <?php submit_button( 'Save Settings' ); ?>

                </div>

                <!-- ── Right column: reference ── -->
                <div>

                    <!-- Shortcode reference -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">📋 Shortcode Reference</h2>
                        </div>
                        <div class="inside" style="font-size:13px">

                            <p style="font-weight:600;margin-bottom:2px">[restaurant_menu]</p>
                            <p style="color:#646970;margin-top:0">Displays a full menu with sections, layouts, and filters.</p>

                            <p><strong>Basic:</strong><br>
                            <code>[restaurant_menu id="123"]</code></p>

                            <p><strong>Layouts:</strong><br>
                            <code>[restaurant_menu id="123" layout="list"]</code><br>
                            <code>[restaurant_menu id="123" layout="two-column"]</code><br>
                            <code>[restaurant_menu id="123" layout="cards"]</code></p>

                            <p><em>Omit <code>layout</code> to use the layout set on the menu edit screen.</em></p>

                            <p><strong>With images / sections:</strong><br>
                            <code>[restaurant_menu id="123" show_images="true" show_sections="true"]</code></p>

                            <p><strong>Featured items only:</strong><br>
                            <code>[restaurant_menu id="123" featured="true"]</code></p>

                            <p><strong>Single section:</strong><br>
                            <code>[restaurant_menu id="123" section="appetizers"]</code></p>

                            <p><strong>Filter by badge:</strong><br>
                            <code>[restaurant_menu id="123" badge="gluten-free"]</code></p>

                            <p><strong>Limit items:</strong><br>
                            <code>[restaurant_menu id="123" limit="6"]</code></p>

                            <hr>

                            <p style="font-weight:600;margin-bottom:2px">[rmm_featured_item]</p>
                            <p style="color:#646970;margin-top:0">Spotlight a specific item or rotate a random featured item from a menu on every visit.</p>

                            <p><strong>Specific item:</strong><br>
                            <code>[rmm_featured_item id="456"]</code></p>

                            <p><strong>Random featured from a menu:</strong><br>
                            <code>[rmm_featured_item menu_id="123"]</code></p>

                            <p><strong>Control what shows:</strong><br>
                            <code>[rmm_featured_item menu_id="123"<br>
                            &nbsp;show_image="true"<br>
                            &nbsp;show_desc="true"<br>
                            &nbsp;show_section="true"<br>
                            &nbsp;show_menu="true"<br>
                            &nbsp;show_price="true"]</code></p>

                            <p><strong>Custom CSS class:</strong><br>
                            <code>[rmm_featured_item menu_id="123" class="sidebar-pick"]</code></p>

                            <hr>

                            <p style="font-weight:600;margin-bottom:4px">CSS Custom Properties</p>
                            <p style="color:#646970;margin-top:0">Override in your theme's Additional CSS:</p>
                            <pre style="background:#f6f7f7;padding:10px;border-radius:4px;font-size:11px;overflow-x:auto;white-space:pre-wrap">:root {
  --rmm-font-size-base:    16px;
  --rmm-font-size-item:    17px;
  --rmm-font-size-section: 20px;
  --rmm-font-size-sm:      14px;
  --rmm-font-size-note:    14px;
  --rmm-font-size-spotlight-name:  24px;
  --rmm-font-size-spotlight-price: 18px;
  --rmm-font-size-spotlight-desc:  16px;
  --rmm-font-size-spotlight-meta:  13px;
}</pre>

                            <hr>
                            <p><strong>Layout options:</strong> <code>list</code> · <code>two-column</code> · <code>cards</code></p>
                            <p><strong>Built-in badge slugs:</strong><br>
                            <code>gluten-free</code> · <code>vegetarian</code> · <code>chefs-rec</code> · <code>seasonal</code></p>
                        </div>
                    </div>

                    <!-- Quick start -->
                    <div class="postbox" style="margin-top:16px">
                        <div class="postbox-header">
                            <h2 class="hndle" style="padding:12px 16px">🚀 Quick Start</h2>
                        </div>
                        <div class="inside" style="font-size:13px">
                            <ol style="padding-left:18px;line-height:1.9">
                                <li>Go to <strong>Sections</strong> → create your sections (Appetizers, Entrées…)</li>
                                <li>Go to <strong>All Menus</strong> → <strong>Add New</strong> (e.g. "Dinner Menu") → set layout</li>
                                <li>Go to <strong>Add New Item</strong> → fill in name, description, price, assign to a menu and section</li>
                                <li>Copy the shortcode from the Menu's edit screen and paste it into any page</li>
                            </ol>
                        </div>
                    </div>

                </div><!-- /right col -->

            </div><!-- /grid -->
        </form>
    </div>
    <?php
}
