# Tet Gift Wrap – WooCommerce Plugin

## Idea

A lightweight WooCommerce plugin that lets customers opt into gift wrapping during checkout.
The main touchpoint is a single checkbox at checkout (its position is configurable per checkout type). When ticked, a configurable
fee is added to the order and the gift wrap choice is stored on the order for the shop to act on.

## Core Features (v1)

| Feature | Details |
|---|---|
| Checkout checkbox | Classic checkout: action hook chosen by the position setting (`Tet_Gift_Wrap_Settings::CLASSIC_POSITIONS`). Block checkout: React component via `registerPlugin`, rendered in `ExperimentalOrderMeta` or portalled next to a checkout section (position setting) |
| Gift wrap fee | Added as a cart fee via `woocommerce_cart_calculate_fees`; reads from WC session so it works for both checkout types |
| Optional gift note | A short textarea that slides in when the checkbox is ticked (max 200 chars) |
| Order meta | `_tet_gift_wrap` (yes/no) and `_tet_gift_wrap_note` stored on the WC_Order |
| Admin orders list | "Gift wrap" column (after status) + With / Without filter, HPOS and legacy posts (`class-gift-wrap-orders-list.php`) |
| Admin order view | `ttrp-badge--success/neutral` badge + note shown below the billing address; uses shared `ttrp-order-panel` from `assets/ttrp-admin.css` |
| Customer emails | Gift wrap row injected into WC order emails (HTML and plain-text) |
| Frontend order page | Notice on the thank-you page and account order detail |
| WC Settings | Section under **ttrp.gr Plugins → Gift Wrap** |

## Architecture

```
woo-tet-gift-wrap.php               Bootstrap: constants, require, hook classes, feature compat declarations
ttrp-common/                        Shared ttrp.gr library: updates + "All Plugins" page (synced from the update server repo; never edit)
includes/
  class-gift-wrap-settings.php      Settings page (ttrp.gr Plugins → Gift Wrap)
  class-gift-wrap-checkout.php      Classic checkout: checkbox render, fee injection, meta save
  class-gift-wrap-store-api.php     Block checkout: Store API extension (session write + order meta save)
  class-gift-wrap-blocks.php        Block checkout: IntegrationInterface (script registration + settings data)
  class-gift-wrap-order.php         Admin panel, frontend notice, email row
  class-gift-wrap-orders-list.php   Admin orders list: gift wrap column + filter (HPOS and legacy)
src/
  gift-wrap-blocks.js               JSX source for the block checkout React component
assets/
  css/gift-wrap.css                 Checkout field styles (frontend only — admin panel styles removed in v1.0.5)
  ttrp-admin.css                    Shared TTRP admin design system (badges, notices, order panel, header/footer)
  js/gift-wrap.js                   Classic checkout: update_checkout trigger + note toggle (jQuery)
  js/gift-wrap-blocks.js            Block checkout: compiled React component (do not edit directly)
  js/gift-wrap-blocks.asset.php     wp-scripts generated dependency manifest (committed to repo)
  ttrp-logo.svg                     Admin menu icon
languages/
  tet-gift-wrap.pot                 Translation template
  tet-gift-wrap-el.po / .mo         Greek translation (loaded on `init` via load_plugin_textdomain)
package.json                        Build tooling (@wordpress/scripts)
webpack.config.js                   Extends @wordpress/scripts webpack config with @woocommerce/* externals
tools/i18n/                         el.mjs (Greek strings), extract.mjs, build.mjs: regenerate POT/PO/MO with Node (not shipped)
release.sh                          Packages a clean distribution ZIP (runtime files only)
.github/workflows/release.yml       GitHub Actions: runs release.sh and publishes a GitHub release on v* tags
```

### Key decisions

- **Fee, not product** – Adding a cart fee (not a virtual product) keeps the order line items
  clean. WooCommerce handles fee taxes and display automatically.
- **Session for fee persistence** – `WC()->session` carries the checkbox state for both checkout
  types. Classic: every checkout refresh (`update_order_review`) sends the whole form as a
  URL-encoded `post_data` string (not as `$_POST` fields), so `capture_from_review()` parses it on
  `woocommerce_checkout_update_order_review`; on final submit the posted form is used (an unticked box
  is simply absent). Block: `extensionCartUpdate` → Store API callback. The shared
  `woocommerce_cart_calculate_fees` hook reads from session in both cases.
- **Block state after reload** – The Store API cart response exposes the session state as
  `extensions['tet-gift-wrap']` (`gift_wrap`, `gift_wrap_note`); the component starts from it.
- **Block address race** – `extensionCartUpdate` replaces the cart with the server copy, customer
  address included, while WooCommerce pushes address edits with a delay. The component calls
  `wc/store/cart` `updateCustomerData()` with the local address first, so a just-typed address isn't reverted.
- **No database table** – Everything lives in `wp_postmeta` (or `wc_orders_meta` for HPOS) as
  order meta. No migration needed.
- **Block component placement** – `order_summary` (default) uses the `ExperimentalOrderMeta` slot fill
  (order summary sidebar). The block checkout has no slot in the main column, so the other positions
  portal the field into a container inserted next to a checkout section wrapper
  (`.wp-block-woocommerce-checkout-payment-block`, `…-actions-block`, `…-order-note-block`). A
  MutationObserver re-inserts it if WooCommerce re-renders the section. If the section is missing
  (detected once the always-present actions block has rendered, or after 3 s), it falls back to
  `ExperimentalOrderMeta`. These class names are not a formal API: re-check them on major WC updates.
- **Classic positions and fragments** – `before_submit` is inside the payment box, which WooCommerce
  re-renders on every refresh; the field is then re-rendered from the session, which
  `capture_from_review()` has already updated from `post_data`.
- **Free above** – `Tet_Gift_Wrap_Checkout::current_price()` is the single source for the fee and the
  label (`price_label()`): products total after discounts incl. tax (`cart_base()`) ≥ threshold → 0.
  The label updates live: classic via the `.tet-gift-wrap-price` order-review fragment (the field isn't
  always inside a re-rendered fragment), block via `extensions['tet-gift-wrap'].price_label` in every
  cart response.
- **Empty labels** – Clearing a label field stores `''`; `get_option()` returns it as-is, so the
  getters fall back to the translated default (`default_label()` / `default_note_label()`), which the
  settings page also shows as the field placeholder.
- **WC Settings API** – Settings rendered under the custom ttrp.gr Plugins admin menu using the
  WC Settings API for correct sanitisation and capability checks.
- **Auto-updates without WordPress.org** – `ttrp-common/` (shared ttrp.gr library) hooks
  into `pre_set_site_transient_update_plugins` and `plugins_api` to deliver updates from
  `https://plugins.ttrp.gr/`. The update server returns a JSON info object; the checker handles
  version comparison, the WP admin update UI, and directory renaming after GitHub ZIP extraction.
  Every ttrp.gr plugin bundles a copy; only the newest copy on a site is loaded. It also adds the
  **ttrp.gr Plugins → All Plugins** page.

## Settings

| Option key | Type | Default | Description |
|---|---|---|---|
| `tet_gift_wrap_enabled` | checkbox | yes | Master switch |
| `tet_gift_wrap_price` | price | 3.00 | Fee amount (0 = free) |
| `tet_gift_wrap_free_above` | price | '' | Free when products total (after discounts, incl. tax) ≥ this; empty = off |
| `tet_gift_wrap_label` | text | '' (→ "Add gift wrapping to my order") | Checkbox label; empty uses the translated default |
| `tet_gift_wrap_note_enabled` | checkbox | yes | Show gift note textarea |
| `tet_gift_wrap_note_label` | text | '' (→ "Gift note (optional)") | Textarea label; empty uses the translated default |
| `tet_gift_wrap_position_classic` | select | before_payment | before_order_review / before_payment / before_submit / after_order_notes |
| `tet_gift_wrap_position_blocks` | select | order_summary | order_summary / before_payment / before_submit / after_order_notes |

## Ideas for v2+

- **Per-product opt-out** – A product meta checkbox to exclude specific items from being wrapped
  (e.g. large furniture).
- **Multiple wrap styles** – Let the shop offer "standard" vs "premium" wrapping as a radio group,
  each with its own price.
- **Ribbon/message card upsell** – A second add-on checkbox for a printed message card.
- **Packing slip integration** – Print a gift wrap indicator on WooCommerce PDF packing slips
  (compatible with the WooCommerce PDF Invoices & Packing Slips plugin).
- **Block checkout slot stabilisation** – `ExperimentalOrderMeta` is still prefixed "Experimental",
  and the main-column positions rely on checkout section class names. Track WC Blocks for stable
  slots / inner-block areas and move to them when available.

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
- Translations: all user-facing strings use the `tet-gift-wrap` text domain (loaded on `init`
  from `languages/`). Greek strings live in `tools/i18n/el.mjs`. When strings change, update it and
  run `node tools/i18n/build.mjs`: it extracts the strings from the PHP files, regenerates the
  POT / PO / MO and fails on a missing or unused translation or a placeholder mismatch (no WP-CLI or
  gettext needed; same tool as in woo-tet-cod-manager). Checkout labels are stored options, so saved
  values are not re-translated; empty ones use the translated defaults.
- The admin plugin title (menu entry, settings `<h1>`, footer) is `Tet_Gift_Wrap_Settings::PLUGIN_TITLE`
  and is deliberately **not** translated (suite-wide rule, see the root `CLAUDE.md`). Don't wrap it in `__()`.
- The settings page is added to `woocommerce_screen_ids` (`Tet_Gift_Wrap_Settings::add_screen_id()`) so WC
  loads its admin JS there; without it the `desc_tip` help icons show empty tooltips.

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
| `ttrp-common/` | Updates + "All Plugins" page |
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
- [ ] Enable plugin; checkbox appears at the chosen position on `/checkout` (try all 4 positions)
- [ ] Tick checkbox → order total updates (fee added via AJAX)
- [ ] Untick checkbox → fee removed
- [ ] Gift note textarea slides in/out with the checkbox state
- [ ] Note is cleared when checkbox is unticked
- [ ] Place order → `_tet_gift_wrap = yes` and `_tet_gift_wrap_note` saved on order

**Block checkout**
- [ ] Switch checkout page to use the WooCommerce block checkout
- [ ] Checkbox appears at the chosen position (try all 4; with order notes off, "below the order notes" falls back to the summary)
- [ ] Reload with the box ticked → it stays ticked, fee still in totals
- [ ] Edit the address, then tick the box right away → the address is kept
- [ ] Tick checkbox → order total updates (fee added via Store API)
- [ ] Untick → fee removed, note cleared
- [ ] Place order → same order meta written as for classic checkout

**Shared**
- [ ] Admin order view shows green "Yes – gift wrapped" badge + note
- [ ] Orders list: "Gift wrap" column after status; With / Without filter (test HPOS and legacy storage)
- [ ] Customer confirmation email contains "Gift Wrap" row
- [ ] Thank-you page and My Account → Orders → order detail show gift wrap notice
- [ ] Set price to 0 → fee line does not appear, checkbox still works
- [ ] Free above 50: cart €20 → "(€3.00, free from €50.00)" + fee; cart €60 → "(Free)", no fee; a coupon that drops it below 50 brings the fee back (both checkout types)
- [ ] Clear both label fields and save → checkout shows the default texts
- [ ] Disable plugin via master switch → checkbox hidden on both checkout types

### Code style

- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- Lint with PHPCS: `phpcs --standard=WordPress .`
- All output must be escaped (`esc_html`, `esc_attr`, `wp_kses_post`).
- All input must be sanitised (`sanitize_text_field`, `sanitize_textarea_field`, etc.).

### WooCommerce hooks used

| Hook | Class | Checkout type | Purpose |
|---|---|---|---|
| Position hook (`CLASSIC_POSITIONS`, default `woocommerce_review_order_before_payment`) | Checkout | Classic | Render checkbox + note |
| `woocommerce_checkout_update_order_review` | Checkout | Classic | Read checkbox + note from `post_data` into session |
| `woocommerce_cart_calculate_fees` | Checkout | Both | Add/remove fee from session (0 above "Free above") |
| `woocommerce_update_order_review_fragments` | Checkout | Classic | Refresh the price text |
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
| `manage_woocommerce_page_wc-orders_columns` / `manage_edit-shop_order_columns` | OrdersList | — | Add the column (HPOS / legacy) |
| `manage_woocommerce_page_wc-orders_custom_column` / `manage_shop_order_posts_custom_column` | OrdersList | — | Render the column |
| `woocommerce_order_list_table_restrict_manage_orders` / `restrict_manage_posts` | OrdersList | — | Filter dropdown |
| `woocommerce_order_list_table_prepare_items_query_args` / `pre_get_posts` | OrdersList | — | Apply the filter (meta query; "no" = `NOT EXISTS` or `!= yes`) |

## Proposed features / backlog (review 2026-09-26)

Proposals from a suite-wide review, **not yet approved or scheduled** — discuss with the user before implementing. Remove or tick items here as they ship.

(Extends "Ideas for v2+" above.)
1. Per-product opt-out.
2. Multiple wrap styles with different prices.
3. Per-item wrapping instead of whole order.
4. Printable gift-note slip without prices, for packing.
- **Tests + CI (suite item S1)** — port the zero-dependency runner (`tests/run.php`, `tests/stubs.php`) and `.github/workflows/tests.yml` (PHP lint + tests on 7.4 / 8.1 / 8.2) from `woo-tet-acs-tracking`. Extract pure logic into testable functions first; aim for 10–20 tests on the decisions that matter.
