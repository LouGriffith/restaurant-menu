# Changelog

All notable changes to Restaurant Menu Manager are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.5.2] - 2026-03-30

### Added
- `[rmm_info]` shortcode — output any restaurant info field from Settings in one place, use anywhere on the site
  - `[rmm_info field="name"]` — restaurant name
  - `[rmm_info field="phone"]` — phone number (plain text on desktop; tappable tel: link on mobile ≤767px)
  - `[rmm_info field="address"]` — address
  - `[rmm_info field="cuisine"]` — cuisine type
  - `before=""` and `after=""` attributes for wrapping HTML
  - `fallback=""` attribute for empty-field fallback text
  - Admins see an inline error if an invalid field value is used; visitors see nothing

---

## [1.5.1] - 2026-03-30

### Fixed
- Plugin slug and GitHub Pages URL corrected to match LouGriffith/restaurant-menu repo — WordPress update detection now works

---

## [1.5.0] - 2026-03-30

### Added
- layout shortcode attribute on [restaurant_menu]
- [rmm_featured_item] shortcode with random rotation per visit
- Typography size controls in Settings and CSS custom properties

---

## [1.4.0] - 2026-03-30

### Added
- Menus column on Menu Items list table with filterable pills
- Filter by Menu dropdown in list table toolbar
- Quick edit AJAX refreshes Menus column in place

---

## [1.3.0] - 2026-03-30

### Added
- Quick Edit support for all six item fields
- AJAX save with immediate DOM column refresh

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
