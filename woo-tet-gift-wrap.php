<?php
/**
 * Plugin Name: Tet Gift Wrap
 * Plugin URI:  https://github.com/tetterip/woo-tet-gift-wrap
 * Description: Adds a gift wrapping option at WooCommerce checkout.
 * Version:     1.0.0
 * Author:      Tet
 * License:     GPL-2.0+
 * Text Domain: tet-gift-wrap
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'TET_GIFT_WRAP_VERSION', '1.0.0' );
define( 'TET_GIFT_WRAP_PATH', plugin_dir_path( __FILE__ ) );
define( 'TET_GIFT_WRAP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Guard against activation without WooCommerce.
 */
register_activation_hook( __FILE__, function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'Tet Gift Wrap requires WooCommerce to be installed and active.', 'tet-gift-wrap' ) );
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-settings.php';
	require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-checkout.php';
	require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-order.php';

	Tet_Gift_Wrap_Settings::init();
	Tet_Gift_Wrap_Checkout::init();
	Tet_Gift_Wrap_Order::init();
} );
