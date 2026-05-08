# Tet Gift Wrap – WooCommerce Plugin

## Idea

A lightweight WooCommerce plugin that lets customers opt into gift wrapping during checkout.
The main touchpoint is a single checkbox above the payment section. When ticked, a configurable
fee is added to the order and the gift wrap choice is stored on the order for the shop to act on.

## Core Features (v1)

| Feature | Details |
|---|---|
| Checkout checkbox | Appears above the payment section via `woocommerce_review_order_before_payment` |
| Gift wrap fee | Added as a cart fee via `woocommerce_cart_calculate_fees`; configurable in WC Settings |
| Optional gift note | A short textarea that slides in when the checkbox is ticked (max 200 chars) |
| Order meta | `_tet_gift_wrap` (yes/no) and `_tet_gift_wrap_note` stored on the WC_Order |
| Admin order view | Badge + note shown below the billing address on the order edit screen |
| Customer emails | Gift wrap row injected into WC order emails (HTML and plain-text) |
| Frontend order page | Notice on the thank-you page and account order detail |
| WC Settings | Section under WooCommerce → Settings → Products → Gift Wrap |

## Architecture

```
woo-tet-gift-wrap.php               Bootstrap: constants, require, hook classes
includes/
  class-gift-wrap-settings.php      WC Settings API integration (options)
  class-gift-wrap-checkout.php      Checkbox render, fee injection, meta save
  class-gift-wrap-order.php         Admin panel, frontend notice, email row
assets/
  css/gift-wrap.css                 Checkout field + admin badge styles
  js/gift-wrap.js                   Checkbox → update_checkout trigger + note toggle
```

### Key decisions

- **Fee, not product** – Adding a cart fee (not a virtual product) keeps the order line items
  clean. WooCommerce handles fee taxes and display automatically.
- **Session for fee persistence** – `WC()->session` carries the checkbox state between the AJAX
  `update_checkout` call and page reload so the fee total stays consistent.
- **No database table** – Everything lives in `wp_postmeta` (or `wc_orders_meta` for HPOS) as
  order meta. No migration needed.
- **WC Settings API** – Settings live under WooCommerce → Products → Gift Wrap, following the
  standard WC pattern so they get the right sanitisation and capability checks for free.

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
- **Block Checkout support** – The current implementation targets the classic shortcode checkout.
  The Gutenberg block checkout needs a separate `registerCheckoutBlock` integration.
- **Free wrapping threshold** – Automatically waive the gift wrap fee above a cart total threshold.
- **HPOS compatibility audit** – Confirm all meta reads/writes use the WC_Order API (done in v1)
  and add the `woocommerce_feature_custom_order_tables_enabled` compatibility declaration.

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+

## Development notes

- Run the plugin inside a local WP install (LocalWP, Lando, etc.) with WooCommerce active.
- No build step – plain CSS and vanilla JS with jQuery (already bundled by WC on the checkout page).
- Translations: all user-facing strings use the `tet-gift-wrap` text domain. Run
  `wp i18n make-pot . languages/tet-gift-wrap.pot` to generate the POT file when strings change.

## Development workflow

### Setup

1. Copy / symlink the plugin folder into `wp-content/plugins/woo-tet-gift-wrap/`.
2. Activate via **Plugins** screen (WooCommerce must be active first).
3. Configure under **WooCommerce → Settings → Products → Gift Wrap**.

### Manual testing checklist

- [ ] Enable plugin; checkbox appears above payment section on `/checkout`
- [ ] Tick checkbox → order total updates (fee added via AJAX)
- [ ] Untick checkbox → fee removed
- [ ] Gift note textarea slides in/out with the checkbox state
- [ ] Note is cleared when checkbox is unticked
- [ ] Place order → `_tet_gift_wrap = yes` and `_tet_gift_wrap_note` saved on order
- [ ] Admin order view shows green "Yes – gift wrapped" badge + note
- [ ] Customer confirmation email contains "Gift Wrap" row
- [ ] Thank-you page and My Account → Orders → order detail show gift wrap notice
- [ ] Set price to 0 → fee line does not appear, checkbox still works
- [ ] Disable plugin via master switch → checkbox hidden entirely

### Code style

- Follow the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- Lint with PHPCS: `phpcs --standard=WordPress .`
- All output must be escaped (`esc_html`, `esc_attr`, `wp_kses_post`).
- All input must be sanitised (`sanitize_text_field`, `sanitize_textarea_field`, etc.).

### WooCommerce hooks used

| Hook | Class | Purpose |
|---|---|---|
| `woocommerce_get_sections_products` | Settings | Register "Gift Wrap" section |
| `woocommerce_get_settings_products` | Settings | Render settings fields |
| `woocommerce_review_order_before_payment` | Checkout | Render checkbox + note |
| `woocommerce_cart_calculate_fees` | Checkout | Add/remove fee |
| `woocommerce_checkout_process` | Checkout | Validation (no-op; field is optional) |
| `woocommerce_checkout_create_order` | Checkout | Save order meta |
| `wp_enqueue_scripts` | Checkout | Enqueue CSS + JS on checkout only |
| `woocommerce_admin_order_data_after_billing_address` | Order | Admin badge + note |
| `woocommerce_order_details_after_order_table` | Order | Frontend thank-you / account notice |
| `woocommerce_email_order_meta` | Order | HTML + plain-text email row |
