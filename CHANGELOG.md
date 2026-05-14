# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.9] - 2026-05-14

### Changed
- Derive `TET_GIFT_WRAP_VERSION` constant from the plugin header at runtime via `get_file_data()` — version now only needs to be updated in one place.

## [1.0.8] - 2026-05-13

### Fixed
- Add missing `assets/ttrp.svg` so the settings page header logo renders correctly.
- Include `assets/ttrp.svg` in the release script.

---

## [1.0.7] - 2026-05-13

### Changed
- Admin settings footer now uses `ttrp.svg` instead of `ttrp-logo.svg`.

---

## [1.0.6] - 2026-05-13

### Fixed
- Removed UTF-8 BOM from the main plugin file. The BOM was being output before the HTML document, causing a stray character to appear at the start of every page.

---

## [1.0.5] – 2026-05-13

### Added
- `.github/workflows/release.yml` — GitHub Actions workflow that runs `release.sh` and publishes a GitHub release on every `v*` tag push.

### Changed
- Admin settings page now uses `.ttrp-wrap`, `.ttrp-plugin-header` (with version pill), and `.ttrp-settings-footer` (ttrp.gr logo and link) for consistency across all ttrp.gr plugins.
- Admin order panel migrated from plugin-specific `tet-gift-wrap-admin-panel` / `tet-gift-wrap-badge--yes/no` classes to the shared `ttrp-order-panel` / `ttrp-badge--success/neutral` components. `ttrp-admin.css` is now also enqueued on order edit screens (HPOS and legacy) so the panel styles load correctly.
- Expanded `assets/ttrp-admin.css` into the full shared TTRP admin design system (badges, notices, order panel, header/footer).
- Removed redundant admin panel CSS from `assets/css/gift-wrap.css` — panel heading, badge, and note styles are now served by `ttrp-admin.css`.

### Fixed
- `release.sh` now includes `assets/ttrp-admin.css` in the distribution zip (previously omitted).

---

## [1.0.4] – 2026-05-13

### Fixed

- Release ZIP was built with Windows backslash path separators (`woo-tet-gift-wrap\file.php`) by PowerShell's `Compress-Archive`; PHP's `ZipArchive` on Linux treats `\` as a literal character so all files extracted to the archive root instead of the `woo-tet-gift-wrap/` subdirectory, causing WordPress to show duplicate plugin entries and a "plugin not found" error on activation. The `release.sh` Windows fallback now uses the .NET `ZipFile` API directly to write entry names with forward slashes.

## [1.0.3] – 2026-05-12

### Added

- `update-checker.php` — shared TTRP update checker; polls `https://plugins.ttrp.gr/` so sites receive automatic updates without the plugin being listed on WordPress.org
- `release.sh` — build and package script that produces a clean distribution ZIP containing only runtime files (excludes `src/`, `node_modules/`, build tooling, and dev docs)

### Fixed

- Block checkout Store API namespace registration: added `register_endpoint_data` call alongside `register_update_callback`; without the schema declaration the client rejected `extensionCartUpdate` calls with "no such namespace registered"
- Switched Store API registration from the `woocommerce_store_api_register_update_callbacks` hook to `ExtendRestApi`/`ExtendSchema` via the WC Blocks DI container (wrapped in `woocommerce_blocks_loaded`) for correct timing and WC 7–9 compatibility
- Gift Wrap Price setting was invisible — `price` is not a valid WC Settings API field type; changed to `text` with `type="number"` via `custom_attributes`
- Price label in block checkout showed raw HTML entities (`&nbsp;&euro;`); fixed with `html_entity_decode` after `wp_strip_all_tags`

## [1.0.2] – 2026-05-12

### Added

- Block checkout support — the gift wrap checkbox and note now appear in the WooCommerce block checkout (Gutenberg) via a React component registered with `registerPlugin` / `ExperimentalOrderMeta`
- `class-gift-wrap-store-api.php` — Store API extension that receives `extensionCartUpdate` calls from the JS component and writes to the WC session; saves order meta via `woocommerce_store_api_checkout_order_processed`
- `class-gift-wrap-blocks.php` — implements `IntegrationInterface` to register the block script and pass plugin settings to JS via `getSetting('tet-gift-wrap_data')`
- `src/gift-wrap-blocks.js` — JSX source for the block checkout component (debounced note updates to avoid excessive Store API calls)
- `webpack.config.js` — extends `@wordpress/scripts` webpack config to map `@woocommerce/*` imports to their runtime globals and generate the correct `asset.php` script dependency list
- `package.json` — build tooling (`npm run build` compiles the block JS; `npm start` watches)
- HPOS and block checkout feature compatibility declared via `FeaturesUtil`

### Fixed

- Classic checkout `save_meta` now returns early on REST requests, preventing it from writing `_tet_gift_wrap = no` on block checkout orders before the Store API handler runs

## [1.0.1] – 2026-05-10

### Changed

- Plugin name updated to "Gift Wrap for WooCommerce" for consistency with other plugins in the suite
- Author set to Michalis Tetteris; Author URI added (`https://ttrp.gr`)
- Added `Requires Plugins: woocommerce` and `WC tested up to: 9.9` to plugin header

## [1.0.0] – 2026-05-08

### Added

- Checkout checkbox above the payment section (`woocommerce_review_order_before_payment`)
- Configurable gift wrap fee added as a WooCommerce cart fee (not a product line item)
- Optional gift note textarea that slides in when the checkbox is ticked (max 200 characters)
- Session-backed fee persistence — checkbox state survives WooCommerce AJAX fragment refreshes and page reloads
- Order meta: `_tet_gift_wrap` (`yes`/`no`) and `_tet_gift_wrap_note` saved via the `WC_Order` API (HPOS-compatible)
- Admin order view: green "Yes – gift wrapped" / red "No" badge and gift note displayed below the billing address
- Gift wrap row injected into WooCommerce HTML and plain-text order confirmation emails
- Customer notice on the thank-you page and My Account → order detail
- WooCommerce Settings section: **Products → Gift Wrap** with five configurable options (master switch, price, checkbox label, note toggle, note label)
- CSS and JS assets enqueued only on the checkout page; JS uses jQuery already bundled by WooCommerce

[Unreleased]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.5...HEAD
[1.0.5]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/tetterip/woo-tet-gift-wrap/releases/tag/v1.0.0
