# Tet Gift Wrap for WooCommerce

Adds a gift wrapping option at WooCommerce checkout — a single checkbox that applies a configurable fee and stores the customer's choice on the order.

## Features

- Checkbox above the payment section on the classic shortcode checkout
- Configurable gift wrap fee added as a cart fee (taxes handled automatically by WooCommerce)
- Optional gift note textarea (slides in when the checkbox is ticked, max 200 characters)
- Gift wrap choice and note stored as order meta (`_tet_gift_wrap`, `_tet_gift_wrap_note`)
- Admin order view — green/red badge and note displayed below the billing address
- Gift wrap row injected into WooCommerce HTML and plain-text order emails
- Customer notice on the thank-you page and My Account → order detail
- Settings under **WooCommerce → Settings → Products → Gift Wrap**

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+

## Installation

1. Download or clone this repository into `wp-content/plugins/woo-tet-gift-wrap/`.
2. Activate the plugin via **Plugins** in the WordPress admin.
3. Go to **WooCommerce → Settings → Products → Gift Wrap** to configure.

## Configuration

| Option | Default | Description |
|---|---|---|
| Enable gift wrap | Yes | Master on/off switch |
| Gift wrap price | 3.00 | Fee added to the order (set to 0 for free) |
| Checkbox label | "Add gift wrapping to my order" | Text shown next to the checkbox |
| Enable gift note | Yes | Show the gift note textarea |
| Gift note label | "Gift note (optional)" | Label for the textarea |

## How it works

1. The customer ticks the checkbox at checkout.
2. JavaScript triggers a WooCommerce `update_checkout` call; the fee is added to the order total immediately.
3. The checkbox state is persisted in the WooCommerce session so the fee survives AJAX fragment refreshes and page reloads.
4. On order placement, `_tet_gift_wrap` (`yes`/`no`) and `_tet_gift_wrap_note` are saved to the order.
5. The order edit screen shows a badge and note; emails and the customer order page show a summary row.

## Developer notes

**No build step.** The plugin uses plain CSS and vanilla JS with jQuery (bundled by WooCommerce on the checkout page). Assets are only enqueued on the checkout page.

**Text domain:** `tet-gift-wrap`. Regenerate the POT file after changing strings:

```bash
wp i18n make-pot . languages/tet-gift-wrap.pot
```

**HPOS compatible.** All order meta reads and writes use the `WC_Order` API (`get_meta` / `update_meta_data`), so the plugin works with both the legacy `wp_postmeta` table and WooCommerce High-Performance Order Storage.

**WooCommerce hooks used by this plugin:**

| Hook | Purpose |
|---|---|
| `woocommerce_review_order_before_payment` | Render the checkbox and note textarea |
| `woocommerce_cart_calculate_fees` | Add the gift wrap fee |
| `woocommerce_checkout_create_order` | Save order meta |
| `woocommerce_admin_order_data_after_billing_address` | Admin order badge + note |
| `woocommerce_order_details_after_order_table` | Frontend thank-you / account notice |
| `woocommerce_email_order_meta` | Email row (HTML and plain text) |
| `woocommerce_get_sections_products` | Register settings section |
| `woocommerce_get_settings_products` | Render settings fields |

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
