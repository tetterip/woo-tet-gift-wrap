<p align="center">
  <img src=".github/icon.svg" width="112" height="112" alt="">
</p>

<h1 align="center">Tet Gift Wrap for WooCommerce</h1>

<p align="center"><strong>A gift wrapping checkbox at checkout, with a configurable fee and an optional gift note.</strong></p>

<p align="center">
  <a href="https://github.com/tetterip/woo-tet-gift-wrap/releases/latest"><img alt="Latest release" src="https://img.shields.io/github/v/release/tetterip/woo-tet-gift-wrap?label=release&color=7c3aed"></a>
  <a href="LICENSE"><img alt="License" src="https://img.shields.io/github/license/tetterip/woo-tet-gift-wrap?color=2563eb"></a>
  <img alt="WordPress 6.0+" src="https://img.shields.io/badge/WordPress-6.0%2B-21759B?logo=wordpress&logoColor=white">
  <img alt="WooCommerce 7.0+" src="https://img.shields.io/badge/WooCommerce-7.0%2B-96588A?logo=woocommerce&logoColor=white">
  <img alt="PHP 8.0+" src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white">
</p>

<p align="center">
  <a href="https://github.com/tetterip/woo-tet-gift-wrap/releases/latest"><strong>Download</strong></a> ·
  <a href="https://plugins.ttrp.gr/#woo-tet-gift-wrap">plugins.ttrp.gr</a> ·
  <a href="CHANGELOG.md">Changelog</a> ·
  <a href="https://github.com/tetterip/woo-tet-gift-wrap/issues/new/choose">Report a bug</a> ·
  <a href=".github/CONTRIBUTING.md">Contributing</a>
</p>

<!--
  Screenshots: save PNGs (about 1600 px wide) in .github/screenshots/ and list them here, e.g.
  <p align="center"><img src=".github/screenshots/checkout.png" width="720" alt="…"></p>
-->

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

**Auto-updates.** The plugin includes `ttrp-common/`, the shared ttrp.gr library that adds the **ttrp.gr Plugins → All Plugins** page and polls `https://plugins.ttrp.gr/` during WordPress's normal update cycle. Sites running this plugin will receive update notices and can update directly from the WordPress admin without the plugin being listed on WordPress.org. The update server also supplies the changelog and the plugin icon/banner shown in WordPress.

**Releasing a new version.** Run `bash release.sh` from the project root. It builds the block JS, packages only the runtime files into `dist/woo-tet-gift-wrap-{version}.zip`, and skips dev files (`src/`, `node_modules/`, build config, etc.). Upload the ZIP to the update server and tag the release on GitHub.

**Translations.** Text domain `tet-gift-wrap`, loaded on `init` from `languages/`. A Greek translation (`tet-gift-wrap-el.po` / `.mo`) ships with the plugin. Regenerate the POT file after changing strings, then update and recompile the `.po`:

```bash
wp i18n make-pot . languages/tet-gift-wrap.pot
msgmerge -U languages/tet-gift-wrap-el.po languages/tet-gift-wrap.pot
msgfmt -o languages/tet-gift-wrap-el.mo languages/tet-gift-wrap-el.po
```

Note: the checkout checkbox and gift note labels are settings. Once the settings page has been saved, the stored text is shown as-is. To change the language of those labels, edit them under **ttrp.gr Plugins → Gift Wrap**.

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

## Updates and privacy

This plugin gets its updates from the ttrp.gr update server (`https://plugins.ttrp.gr/`), not from WordPress.org.
When WordPress checks for updates, and when you open **ttrp.gr Plugins → All Plugins**, your site sends that server:

- the plugin's slug and installed version,
- the WordPress version and the site's address (in the request's `User-Agent` header),
- the admin language (All Plugins page only).

The server logs these requests, with the IP address, for 180 days and keeps a list of which sites run which plugin
version. No order, customer or other personal data is sent. Questions: info@ttrp.gr. Details: [privacy policy](https://plugins.ttrp.gr/?page=privacy&lang=en) and [terms of use](https://plugins.ttrp.gr/?page=terms&lang=en); administrators see a one-time notice about this in wp-admin.

## Contributing

Bug reports, ideas and pull requests are welcome. See [CONTRIBUTING.md](.github/CONTRIBUTING.md) for how to set up, test and translate the plugin.

## Security

Please report vulnerabilities privately to **info@ttrp.gr**, not in a public issue. See [SECURITY.md](SECURITY.md).

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
