# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/tetterip/woo-tet-gift-wrap/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/tetterip/woo-tet-gift-wrap/releases/tag/v1.0.0
