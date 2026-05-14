# Tet Gift Wrap – WooCommerce Plugin

## Idea

A lightweight WooCommerce plugin that lets customers opt into gift wrapping during checkout.
The main touchpoint is a single checkbox above the payment section. When ticked, a configurable
fee is added to the order and the gift wrap choice is stored on the order for the shop to act on.

## Core Features (v1)

| Feature | Details |
|---|---|
| Checkout checkbox | Classic checkout: `woocommerce_review_order_before_payment`. Block checkout: React component via `registerPlugin` + `ExperimentalOrderMeta` |
| Gift wrap fee | Added as a cart fee via `woocommerce_cart_calculate_fees`; reads from WC session so it works for both checkout types |
| Optional gift note | A short textarea that slides in when the checkbox is ticked (max 200 chars) |
| Order meta | `_tet_gift_wrap` (yes/no) and `_tet_gift_wrap_note` stored on the WC_Order |
| Admin order view | `ttrp-badge--success/neutral` badge + note shown below the billing address; uses shared `ttrp-order-panel` from `assets/ttrp-admin.css` |
| Customer emails | Gift wrap row injected into WC order emails (HTML and plain-text) |
| Frontend order page | Notice on the thank-you page and account order detail |
| WC Settings | Section under **ttrp.gr Plugins → Gift Wrap** |

## Architecture

```
woo-tet-gift-wrap.php               Bootstrap: constants, require, hook classes, feature compat declarations
update-checker.php                  Shared TTRP auto-updater (polls plugins.ttrp.gr)
includes/
  class-gift-wrap-settings.php      Settings page (ttrp.gr Plugins → Gift Wrap)
  class-gift-wrap-checkout.php      Classic checkout: checkbox render, fee injection, meta save
  class-gift-wrap-store-api.php     Block checkout: Store API extension (session write + order meta save)
  class-gift-wrap-blocks.php        Block checkout: IntegrationInterface (script registration + settings data)
  class-gift-wrap-order.php         Admin panel, frontend notice, email row
src/
  gift-wrap-blocks.js               JSX source for the block checkout React component
assets/
  css/gift-wrap.css                 Checkout field styles (frontend only — admin panel styles removed in v1.0.5)
  ttrp-admin.css                    Shared TTRP admin design system (badges, notices, order panel, header/footer)
  js/gift-wrap.js                   Classic checkout: update_checkout trigger + note toggle (jQuery)
  js/gift-wrap-blocks.js            Block checkout: compiled React component (do not edit directly)
  js/gift-wrap-blocks.asset.php     wp-scripts generated dependency manifest (committed to repo)
  ttrp-logo.svg                     Admin menu icon
package.json                        Build tooling (@wordpress/scripts)
webpack.config.js                   Extends @wordpress/scripts webpack config with @woocommerce/* externals
release.sh                          Packages a clean distribution ZIP (runtime files only)
.github/workflows/release.yml       GitHub Actions: runs release.sh and publishes a GitHub release on v* tags
```

### Key decisions

- **Fee, not product** – Adding a cart fee (not a virtual product) keeps the order line items
  clean. WooCommerce handles fee taxes and display automatically.
- **Session for fee persistence** – `WC()->session` carries the checkbox state for both checkout
  types. The classic checkout writes via `$_POST`; the block checkout writes via `extensionCartUpdate`
  → Store API callback. The shared `woocommerce_cart_calculate_fees` hook reads from session in
  both cases.
- **No database table** – Everything lives in `wp_postmeta` (or `wc_orders_meta` for HPOS) as
  order meta. No migration needed.
- **Block component placement** – The React component uses the `ExperimentalOrderMeta` slot fill,
  which renders in the order summary sidebar of the block checkout. This is the most broadly
  supported placement across WC 7–9.
- **WC Settings API** – Settings rendered under the custom ttrp.gr Plugins admin menu using the
  WC Settings API for correct sanitisation and capability checks.
- **Auto-updates without WordPress.org** – `update-checker.php` is a shared TTRP class that hooks
  into `pre_set_site_transient_update_plugins` and `plugins_api` to deliver updates from
  `https://plugins.ttrp.gr/`. The update server returns a JSON info object; the checker handles
  version comparison, the WP admin update UI, and directory renaming after GitHub ZIP extraction.
  The class is guarded with `class_exists` so it is safe to bundle in multiple plugins on the same
  site without conflicts.

## Settings

| Option key | Type | Default | Description |
|---|---|---|---|
| `tet_gift_wrap_enabled` | checkbox | yes | Master switch |
| `tet_gift_wrap_price` | price | 3.00 | Fee amount (0 = free) |
| `tet_gift_wrap_label` | text | "Add gift wrapping to my order" | Checkbox label |
| `tet_gift_wrap_note_enabled` | checkbox | yes | Show gift note textarea |
| `tet_gift_wrap_note_label` | text | "Gift note (optional)" | Textarea label |

## Ideas for v2+

- **Per-product opt-out** – A product meta checkbox to exclude specific items from being wrapped
  (e.g. large furniture).
- **Multiple wrap styles** – Let the shop offer "standard" vs "premium" wrapping as a radio group,
  each with its own price.
- **Ribbon/message card upsell** – A second add-on checkbox for a printed message card.
- **Admin order list column** – Quick visual indicator in the WC orders table so the warehouse
  team doesn't need to open each order.
- **Packing slip integration** – Print a gift wrap indicator on WooCommerce PDF packing slips
  (compatible with the WooCommerce PDF Invoices & Packing Slips plugin).
- **Free wrapping threshold** – Automatically waive the gift wrap fee above a cart total threshold.
- **Block checkout slot stabilisation** – `ExperimentalOrderMeta` is still prefixed "Experimental".
  Track when WC Blocks promotes it to a stable API and update accordingly.

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+

## Development notes

- Run the plugin inside a local WP install (LocalWP, Lando, etc.) with WooCommerce active.
- **Build step required for block checkout JS.** Run `npm install` once, then `npm run build` to
  compile `src/gift-wrap-blocks.js` → `assets/js/gift-wrap-blocks.js`. Use `npm start` during
  development for watch mode. The compiled file is committed to the repo.
- The classic checkout path (jQuery) has no build step.
- Translations: all user-facing strings use the `tet-gift-wrap` text domain. Run
  `wp i18n make-pot . languages/tet-gift-wrap.pot` to generate the POT file when strings change.

## Development workflow

### Releasing

1. Bump the version in the plugin header (`Version:`) and `package.json`. `TET_GIFT_WRAP_VERSION` is derived automatically from the header via `get_file_data()` — do not update it separately.
2. Update `CHANGELOG.md` and commit.
3. Run `bash release.sh` — it builds the JS and produces `dist/woo-tet-gift-wrap-{version}.zip`.
4. Upload the ZIP to the update server (`https://plugins.ttrp.gr/`).
5. Create a GitHub release tagged `v{version}` and attach the ZIP.

Files included in the release ZIP (everything else is excluded):

| Path | Notes |
|---|---|
| `woo-tet-gift-wrap.php` | Main plugin file |
| `update-checker.php` | Auto-updater |
| `includes/*.php` | All PHP classes |
| `assets/css/gift-wrap.css` | Styles |
| `assets/js/gift-wrap.js` | Classic checkout JS |
| `assets/js/gift-wrap-blocks.js` | Compiled block checkout JS |
| `assets/js/gift-wrap-blocks.asset.php` | Script dependency manifest |
| `assets/ttrp-logo.svg` | Admin menu icon |
| `languages/` | Translation files (if present) |

### Setup

1. Copy / symlink the plugin folder into `wp-content/plugins/woo-tet-gift-wrap/`.
2. Run `npm install && npm run build` to compile the block checkout JS.
3. Activate via **Plugins** screen (WooCommerce must be active first).
4. Configure under **ttrp.gr Plugins → Gift Wrap**.

### Manual testing checklist

**Classic checkout**
- [ ] Enable plugin; checkbox appears above payment section on `/checkout`
- [ ] Tick checkbox → order total updates (fee added via AJAX)
- [ ] Untick checkbox → fee removed
- [ ] Gift note textarea slides in/out with the checkbox state
- [ ] Note is cleared when checkbox is unticked
- [ ] Place order → `_tet_gift_wrap = yes` and `_tet_gift_wrap_note` saved on order

**Block checkout**
- [ ] Switch checkout page to use the WooCommerce block checkout
- [ ] Checkbox appears in the order summary sidebar
- [ ] Tick checkbox → order total updates (fee added via Store API)
- [ ] Untick → fee removed, note cleared
- [ ] Place order → same order meta written as for classic checkout

**Shared**
- [ ] Admin order view shows green "Yes – gift wrapped" badge + note
- [ ] Customer confirmation email contains "Gift Wrap" row
- [ ] Thank-you page and My Account → Orders → order detail show gift wrap notice
- [ ] Set price to 0 → fee line does not appear, checkbox still works
- [ ] Disable plugin via master switch → checkbox hidden on both checkout types

### Code style

- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- Lint with PHPCS: `phpcs --standard=WordPress .`
- All output must be escaped (`esc_html`, `esc_attr`, `wp_kses_post`).
- All input must be sanitised (`sanitize_text_field`, `sanitize_textarea_field`, etc.).

### WooCommerce hooks used

| Hook | Class | Checkout type | Purpose |
|---|---|---|---|
| `woocommerce_review_order_before_payment` | Checkout | Classic | Render checkbox + note |
| `woocommerce_cart_calculate_fees` | Checkout | Both | Add/remove fee from session |
| `woocommerce_checkout_process` | Checkout | Classic | Validation (no-op; field is optional) |
| `woocommerce_checkout_create_order` | Checkout | Classic | Save order meta |
| `wp_enqueue_scripts` | Checkout | Classic | Enqueue CSS + JS on checkout only |
| `woocommerce_store_api_register_update_callbacks` | StoreApi | Block | Register `extensionCartUpdate` callback |
| `woocommerce_store_api_checkout_order_processed` | StoreApi | Block | Save order meta |
| `woocommerce_blocks_checkout_block_registration` | Bootstrap | Block | Register `IntegrationInterface` |
| `before_woocommerce_init` | Bootstrap | — | Declare HPOS + block checkout compatibility |
| `woocommerce_admin_order_data_after_billing_address` | Order | — | Admin badge + note |
| `woocommerce_order_details_after_order_table` | Order | — | Frontend thank-you / account notice |
| `woocommerce_email_order_meta` | Order | — | HTML + plain-text email row |
