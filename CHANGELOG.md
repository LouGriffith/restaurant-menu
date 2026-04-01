# Changelog

All notable changes to Restaurant Menu Manager are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.5.3] - 2026-04-01

### Changed
- Settings page layout: Typography moved to right column; Shortcode Reference and Quick Start moved to left column alongside Restaurant Information
- Typography controls now show three columns per element: Element | Class Name | Size
- Class Name field applies the selected font size to any matching CSS class in your theme, output as a CSS rule on wp_head
- CSS custom properties reference collapsed into a details/summary disclosure to keep the panel compact

---

## [1.5.2] - 2026-03-30

### Added
- [rmm_info] shortcode for name, phone, address, cuisine — phone is a tappable link on mobile only

---

## [1.5.1] - 2026-03-30

### Fixed
- Plugin slug and GitHub Pages URL corrected for proper update detection

---

## [1.5.0] - 2026-03-30

### Added
- layout shortcode attribute on [restaurant_menu]
- [rmm_featured_item] shortcode with random rotation
- Typography size controls and CSS custom properties

---

## [1.4.0] - 2026-03-30

### Added
- Menus column and Filter by Menu dropdown on Menu Items list table

---

## [1.3.0] - 2026-03-30

### Added
- Full Quick Edit support for all six item fields

---

## [1.2.0] - 2026-03-22

### Changed
- Enforced Classic Editor for menu post types

---

## [1.1.0] - 2026-03-22

### Added
- GitHub Actions CI and automated release workflow
- WordPress update detection via docs/info.json

---

## [1.0.0] - 2026-03-22

### Added
- Initial release

---

## [1.5.4] - 2026-04-01

### Fixed
- Filter by Menu dropdown on Menu Items list now correctly finds items — the LIKE query was searching for a quoted string (`"393"`) but WordPress serializes integer arrays without quotes (`a:1:{i:0;i:393;}`), so no results were ever returned. Now uses `;i:{ID};` and `:i:{ID};}` patterns with an OR relation to match all array positions.
- Same serialization bug fixed in `[restaurant_menu]` shortcode — items assigned to a menu were not displaying.
- Same fix applied to `[rmm_featured_item]` shortcode random rotation query.
