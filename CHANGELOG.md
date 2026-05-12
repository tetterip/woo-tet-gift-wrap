# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/tetterip/woo-tet-gift-wrap/releases/tag/v1.0.0
