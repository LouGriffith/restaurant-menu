# Restaurant Menu Manager

A custom WordPress plugin for restaurant menu management. Build and display multiple menus with shortcodes, flexible layouts, dietary badges, featured item spotlights, and Schema.org structured data for Google rich results. Updates automatically via GitHub Releases.

---

## Features

- **Multiple menus** — Dinner, Lunch, Wine List, etc. each managed independently
- **Sections** — group items within a menu (Appetizers, Entrées, Desserts…)
- **Dietary badges** — pre-seeded with Gluten-Free, Vegetarian/Vegan, Chef's Recommendation, Seasonal/Limited
- **Three display layouts** — Classic List, Two-Column, Card Grid — set per menu or override per shortcode
- **Featured item spotlight** — pin a specific item or rotate a random featured item on every visit
- **Restaurant info shortcodes** — output name, phone, address, or cuisine from one place across the entire site
- **Photo support** — featured image per item plus a multi-photo gallery
- **86 toggle** — mark items unavailable without deleting them
- **Schema.org JSON-LD** — full `Menu`, `MenuItem`, and `Restaurant` structured data for Google rich results
- **Quick Edit** — edit price, availability, featured flag, section, menus, and sort order without opening the item
- **Admin filter** — filter the item list by menu with one click
- **Typography controls** — adjust font sizes from the Settings page or via CSS custom properties
- **Auto-updates** — WordPress update notifications and one-click updates via GitHub Releases

---

## Shortcodes

### `[restaurant_menu]`

Displays a full menu with sections, layouts, and filtering.

| Attribute | Default | Description |
|---|---|---|
| `id` | *(required)* | Menu post ID |
| `layout` | menu setting | `list` · `two-column` · `cards` |
| `featured` | `false` | `true` to show only featured items |
| `section` | — | Slug of a single section to display |
| `show_images` | menu setting | `true` or `false` |
| `show_sections` | menu setting | `true` or `false` |
| `badge` | — | Filter by badge slug |
| `limit` | `0` (all) | Max number of items to show |

```
[restaurant_menu id="42"]
[restaurant_menu id="42" layout="cards" show_images="true"]
[restaurant_menu id="42" layout="two-column" section="entrees"]
[restaurant_menu id="42" featured="true" limit="6"]
[restaurant_menu id="42" badge="gluten-free"]
```

---

### `[rmm_featured_item]`

Spotlight a single item or rotate a random featured item from a menu on every page visit.

| Attribute | Default | Description |
|---|---|---|
| `id` | — | Specific item post ID |
| `menu_id` | — | Pull a random featured item from this menu |
| `show_image` | `true` | Show item photo |
| `show_desc` | `true` | Show description |
| `show_section` | `true` | Show section label |
| `show_menu` | `true` | Show menu name |
| `show_price` | `true` | Show price |
| `class` | — | Extra CSS class for custom styling |

```
[rmm_featured_item id="99"]
[rmm_featured_item menu_id="42"]
[rmm_featured_item menu_id="42" show_section="true" show_menu="false"]
[rmm_featured_item menu_id="42" class="homepage-hero"]
```

---

### `[rmm_info]`

Output any restaurant info field from Settings — one place to edit, use anywhere on the site.

| Attribute | Options |
|---|---|
| `field` | `name` · `phone` · `address` · `cuisine` |
| `before` | HTML prepended to output |
| `after` | HTML appended to output |
| `fallback` | Text shown when field is empty |

```
[rmm_info field="name"]
[rmm_info field="phone"]
[rmm_info field="address"]
[rmm_info field="address" before="<address>" after="</address>"]
[rmm_info field="phone" before="<strong>Reservations: </strong>"]
```

> **Phone** renders as plain text on desktop and a tappable `tel:` link on mobile (≤767px).

---

## CSS Custom Properties

Override typography in your theme's Additional CSS without touching the plugin:

```css
:root {
  --rmm-font-size-base:           16px;
  --rmm-font-size-item:           17px;
  --rmm-font-size-section:        20px;
  --rmm-font-size-sm:             14px;
  --rmm-font-size-note:           14px;
  --rmm-font-size-spotlight-name: 24px;
  --rmm-font-size-spotlight-price:18px;
  --rmm-font-size-spotlight-desc: 16px;
  --rmm-font-size-spotlight-meta: 13px;
}
```

Sizes can also be set from **Menu Items → Settings → Typography**.

---

## Badge Slugs

| Badge | Slug |
|---|---|
| Gluten-Free | `gluten-free` |
| Vegetarian / Vegan | `vegetarian` |
| Chef's Recommendation | `chefs-rec` |
| Seasonal / Limited | `seasonal` |

---

## Installation

1. Download the latest `restaurant-menu-plugin.zip` from [Releases](https://github.com/LouGriffith/restaurant-menu/releases)
2. In WordPress: **Plugins → Add New → Upload Plugin**
3. Activate the plugin
4. Go to **Menu Items → Settings** and fill in restaurant name, address, phone, and cuisine
5. Go to **Menu Items → Sections** and create your sections (Appetizers, Entrées, Desserts…)
6. Go to **Menus → Add New** to create a menu (e.g. Dinner Menu) and set its default layout
7. Go to **Menu Items → Add New** to add items, assign them to a menu and section
8. Copy the shortcode from the menu's edit screen and paste it into any page

---

## Releasing an Update

```bash
git add .
git commit -m "feat: description of changes"
git push origin main
git tag v1.5.2
git push origin v1.5.2
```

GitHub Actions automatically builds the installable zip, updates `docs/info.json`, and creates a GitHub Release. WordPress sites see the update notification within 12 hours.

---

## Repository Structure

```
restaurant-menu/
├── admin/
│   ├── admin-columns.php   # List table columns, quick edit, menu filter
│   ├── admin-script.js     # Media gallery uploader
│   ├── admin-style.css     # Admin styles
│   └── quick-edit.js       # Quick edit JS
├── docs/
│   └── info.json           # Update manifest (served via GitHub Pages)
├── includes/
│   ├── info-shortcodes.php # [rmm_info] shortcode
│   ├── meta-boxes.php      # Item fields and menu settings meta boxes
│   ├── post-types.php      # Custom post types
│   ├── schema.php          # Schema.org JSON-LD
│   ├── settings.php        # Settings page + typography controls
│   ├── shortcode.php       # [restaurant_menu] and [rmm_featured_item]
│   ├── taxonomies.php      # Sections and badges
│   └── updater.php         # GitHub update detection
├── public/
│   ├── css/menu-display.css
│   └── js/menu-display.js
├── .github/workflows/
│   ├── ci.yml              # PHP syntax validation on push
│   └── release.yml         # Build zip + update manifest on tag
├── restaurant-menu.php     # Plugin entry point
└── CHANGELOG.md
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
