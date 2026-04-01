# Changelog

All notable changes to Restaurant Menu Manager are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.5.6] - 2026-04-01

### Fixed
- Restaurant Information fields were overflowing the right column box — replaced WordPress `form-table` two-column layout with stacked label-above-input divs; labels now sit above their field and inputs fill 100% of the available width

---

## [1.5.5] - 2026-04-01

### Changed
- Settings page layout: Quick Start moved to top of left column; Restaurant Information moved to top of right column, above Typography
- Typography class name fields now show `rcc_`-prefixed placeholder suggestions per element (e.g. `rcc_item_name`, `rcc_section_header`, `rcc_spotlight_name`) to encourage a consistent naming convention

---

## [1.5.4] - 2026-04-01

### Fixed
- Filter by Menu dropdown on the Menu Items list now correctly returns items — the LIKE query was matching a quoted string (`"393"`) but WordPress serializes integer arrays without quotes (`a:1:{i:0;i:393;}`), so no results were ever returned. Query now uses `;i:{ID};` and `:i:{ID};}` patterns with an OR relation to cover all array positions
- Same PHP serialization bug fixed in `[restaurant_menu]` shortcode — items assigned to a menu were not displaying on the front end
- Same fix applied to `[rmm_featured_item]` random rotation query

---

## [1.5.3] - 2026-04-01

### Changed
- Settings page redesigned: Typography controls moved to right column; Shortcode Reference and Quick Start moved to left column
- Typography table now has three columns per element: Element | Class Name | Size
- Class Name field applies the element's font-size CSS variable to any matching class in your theme, output as an inline `<style>` block on `wp_head`
- CSS custom properties reference collapsed into a `<details>` disclosure to keep the panel compact

---

## [1.5.2] - 2026-03-30

### Added
- `[rmm_info]` shortcode — output any restaurant info field from Settings anywhere on the site with one tag
  - `field="name"` · `field="phone"` · `field="address"` · `field="cuisine"`
  - `before=""` and `after=""` attributes for wrapping HTML
  - `fallback=""` attribute for empty-field text
  - Phone renders as plain text on desktop and a tappable `tel:` link on mobile (≤767px)
  - Admins see an inline error for invalid `field` values; visitors see nothing

---

## [1.5.1] - 2026-03-30

### Fixed
- `PLUGIN_SLUG` in `updater.php` corrected from `restaurant-menu-plugin` to `restaurant-menu` to match the actual GitHub repo folder name — WordPress update detection was silently failing
- `INFO_URL` updated to `https://LouGriffith.github.io/restaurant-menu/info.json`
- `slug`, `homepage`, and `download_url` in `docs/info.json` updated to match `LouGriffith/restaurant-menu`
- `Plugin URI` in main plugin header updated to correct GitHub repo URL

---

## [1.5.0] - 2026-03-30

### Added
- `layout` attribute on `[restaurant_menu]` shortcode — override the menu's default layout per placement without editing the menu edit screen. Accepts `list`, `two-column`, `cards`. Falls back to the layout set on the menu when omitted
- `[rmm_featured_item]` shortcode — spotlight component for use anywhere on the site
  - `id=""` pins a specific item by post ID
  - `menu_id=""` pulls a random available featured item from a menu, rotating on every page load via `ORDER BY RAND()`
  - `show_image`, `show_desc`, `show_section`, `show_menu`, `show_price` toggle each element independently (all default `true`)
  - `class=""` adds a custom CSS class for per-placement styling
- Typography size controls in Settings — adjust font sizes for all menu display and spotlight elements via admin UI
- CSS custom properties (`--rmm-font-size-*`) documented in Settings and stylesheet for direct theme overrides
- Spotlight component styles added to `public/css/menu-display.css`

---

## [1.4.0] - 2026-03-30

### Added
- Menus column on the Menu Items list table — shows which menus each item belongs to as linked, filterable pills; clicking a pill filters the list to that menu
- Filter by Menu dropdown in the list table toolbar — filters items to a single menu using `pre_get_posts`
- Active-filter pill highlighting — the pill matching the current filter renders in solid blue so the active filter is obvious
- Quick Edit AJAX save now also refreshes the Menus column in place after saving

### Changed
- `rmm_ajax_save_quick_edit()` AJAX response extended with `menus_html` for live Menus column refresh

---

## [1.3.0] - 2026-03-30

### Added
- Quick Edit support on the Menu Items list table for all six fields: Price, Availability (86'd toggle), Featured flag, Section, Assign to Menus, Sort Order
- Per-row hidden `<span class="rmm-qe-data">` element populated via `post_row_actions` — feeds JS pre-population without an extra AJAX round-trip
- `rmm_save_quick_edit` AJAX action with nonce verification — saves all custom fields and returns refreshed column HTML (Price, Status, Featured, Menus) for immediate DOM update without page reload
- `admin/quick-edit.js` — overrides `inlineEditPost.edit` to pre-populate all panel fields when Quick Edit opens; intercepts the Save button to persist custom fields via AJAX

---

## [1.2.0] - 2026-03-22

### Changed
- Enforced Classic Editor for `rmm_menu_item` and `rmm_menu` post types via `use_block_editor_for_post_type` filter
- Removed default WordPress content editor from item and menu edit screens — custom meta boxes handle all content input

---

## [1.1.0] - 2026-03-22

### Added
- GitHub Actions CI workflow — validates PHP syntax on every push to `main` or `dev`
- GitHub Actions release workflow — triggers on version tags; bumps plugin header, builds installable zip excluding dev files, updates `docs/info.json`, creates a GitHub Release with the zip attached
- `includes/updater.php` — polls `docs/info.json` manifest hosted on GitHub Pages, hooks into WordPress update system so sites see one-click update notifications
- `docs/info.json` — GitHub Pages–hosted manifest with version, download URL, and changelog for WordPress update API

---

## [1.0.0] - 2026-03-22

### Added
- Custom post types: `rmm_menu` (menu containers) and `rmm_menu_item` (individual items)
- Taxonomies: `rmm_section` (hierarchical, e.g. Appetizers / Entrées / Desserts) and `rmm_badge` (dietary flags, pre-seeded with Gluten-Free, Vegetarian/Vegan, Chef's Recommendation, Seasonal/Limited)
- Item meta fields: price, price note, short description, availability toggle (86'd), featured flag, sort order, photo gallery
- Menu assignment meta: items assigned to one or more menus via checkbox list on the item edit screen
- Menu display settings: default layout (list / two-column / cards), show/hide images, show/hide section headers, menu footnote
- `[restaurant_menu]` shortcode with `id`, `featured`, `section`, `layout`, `show_images`, `show_sections`, `limit`, `badge` attributes
- Three front-end display layouts: Classic List, Two-Column, Card Grid — all with section headers and featured item highlighting
- Schema.org `Menu` + `MenuItem` + `Restaurant` JSON-LD structured data injected automatically for Google rich results; `suitableForDiet` wired to Gluten-Free and Vegetarian badges
- Admin list columns: thumbnail, price, featured star, availability status; sortable by price and featured
- Settings page with restaurant name, address, phone, cuisine fields (used for Schema.org); shortcode reference card and quick-start guide
- Print stylesheet included in `public/css/menu-display.css`
