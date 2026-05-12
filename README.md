# Tet Gift Wrap for WooCommerce

Adds a gift wrapping option at WooCommerce checkout — a single checkbox that applies a configurable fee and stores the customer's choice on the order.

## Features

- Checkbox above the payment section on both the **classic shortcode checkout** and the **WooCommerce block checkout**
- Configurable gift wrap fee added as a cart fee (taxes handled automatically by WooCommerce)
- Optional gift note textarea (slides in when the checkbox is ticked, max 200 characters)
- Gift wrap choice and note stored as order meta (`_tet_gift_wrap`, `_tet_gift_wrap_note`)
- Admin order view — green/red badge and note displayed below the billing address
- Gift wrap row injected into WooCommerce HTML and plain-text order emails
- Customer notice on the thank-you page and My Account → order detail
- Settings under **ttrp.gr Plugins → Gift Wrap**

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+

## Installation

1. Download the latest release ZIP from the [releases page](https://github.com/tetterip/woo-tet-gift-wrap/releases) or via the WordPress admin update screen.
2. Upload and activate via **Plugins → Add New → Upload Plugin**, or unzip into `wp-content/plugins/woo-tet-gift-wrap/`.
3. Activate via **Plugins** in the WordPress admin.
4. Configure under **ttrp.gr Plugins → Gift Wrap**.

## Configuration

| Option | Default | Description |
|---|---|---|
| Enable gift wrap | Yes | Master on/off switch |
| Gift wrap price | 3.00 | Fee added to the order (set to 0 for free) |
| Checkbox label | "Add gift wrapping to my order" | Text shown next to the checkbox |
| Enable gift note | Yes | Show the gift note textarea |
| Gift note label | "Gift note (optional)" | Label for the textarea |

## How it works

**Classic shortcode checkout (`[woocommerce_checkout]`)**

1. The customer ticks the checkbox at checkout.
2. jQuery triggers a WooCommerce `update_checkout` call; the fee is added to the order total immediately.
3. The checkbox state is persisted in the WooCommerce session so the fee survives AJAX fragment refreshes and page reloads.
4. On order placement, `_tet_gift_wrap` (`yes`/`no`) and `_tet_gift_wrap_note` are saved to the order.

**Block checkout**

1. A React component registered via `registerPlugin` renders the checkbox and note inside the order summary area.
2. On change, `extensionCartUpdate` posts to the Store API, which writes the state to the WC session; the shared `woocommerce_cart_calculate_fees` hook adds the fee from session so totals update in real time.
3. On order placement, `woocommerce_store_api_checkout_order_processed` fires and reads from session to write the same order meta.

The order edit screen, emails, and customer order pages work identically for both checkout types.

## Developer notes

**Build step (block checkout JS only).** The block checkout integration uses React/JSX compiled with `@wordpress/scripts`. Run `npm install` once, then:

```bash
npm run build   # production build → assets/js/gift-wrap-blocks.js
npm start       # development watch mode
```

The classic checkout still uses plain jQuery — no build needed for that path. The compiled `assets/js/gift-wrap-blocks.js` and its accompanying `gift-wrap-blocks.asset.php` are committed to the repository so the plugin works without a local Node install.

**Auto-updates.** The plugin includes `update-checker.php`, a shared TTRP updater that polls `https://plugins.ttrp.gr/` during WordPress's normal update cycle. Sites running this plugin will receive update notices and can update directly from the WordPress admin without the plugin being listed on WordPress.org.

**Releasing a new version.** Run `bash release.sh` from the project root. It builds the block JS, packages only the runtime files into `dist/woo-tet-gift-wrap-{version}.zip`, and skips dev files (`src/`, `node_modules/`, build config, etc.). Upload the ZIP to the update server and tag the release on GitHub.

**Text domain:** `tet-gift-wrap`. Regenerate the POT file after changing strings:

```bash
wp i18n make-pot . languages/tet-gift-wrap.pot
```

**HPOS compatible.** All order meta reads and writes use the `WC_Order` API (`get_meta` / `update_meta_data`), so the plugin works with both the legacy `wp_postmeta` table and WooCommerce High-Performance Order Storage.

**WooCommerce hooks used by this plugin:**

| Hook | Checkout type | Purpose |
|---|---|---|
| `woocommerce_review_order_before_payment` | Classic | Render the checkbox and note textarea |
| `woocommerce_cart_calculate_fees` | Both | Add the gift wrap fee from session |
| `woocommerce_checkout_create_order` | Classic | Save order meta |
| `woocommerce_store_api_register_update_callbacks` | Block | Receive `extensionCartUpdate` and write to session |
| `woocommerce_store_api_checkout_order_processed` | Block | Save order meta |
| `woocommerce_blocks_checkout_block_registration` | Block | Register `IntegrationInterface` |
| `woocommerce_admin_order_data_after_billing_address` | — | Admin order badge + note |
| `woocommerce_order_details_after_order_table` | — | Frontend thank-you / account notice |
| `woocommerce_email_order_meta` | — | Email row (HTML and plain text) |

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
