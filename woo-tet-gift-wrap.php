<?php
/**
 * Plugin Name: Gift Wrap for WooCommerce
 * Plugin URI:  https://github.com/tetterip/woo-tet-gift-wrap
 * Description: Adds a gift wrapping option at WooCommerce checkout.
 * Version:     1.0.6
 * Author:      Michalis Tetteris
 * Author URI:  https://ttrp.gr
 * License:     GPL-2.0+
 * Text Domain: tet-gift-wrap
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Tested up to:      6.9.4
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to:   10.7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'TET_GIFT_WRAP_VERSION', '1.0.6' );
define( 'TET_GIFT_WRAP_PATH', plugin_dir_path( __FILE__ ) );
define( 'TET_GIFT_WRAP_URL', plugin_dir_url( __FILE__ ) );

// Declare HPOS and block checkout compatibility.
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * Guard against activation without WooCommerce.
 */
register_activation_hook( __FILE__, function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( esc_html__( 'Gift Wrap for WooCommerce requires WooCommerce to be installed and active.', 'tet-gift-wrap' ) );
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-settings.php';
	require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-checkout.php';
	require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-store-api.php';
	require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-order.php';

	Tet_Gift_Wrap_Settings::init();
	Tet_Gift_Wrap_Checkout::init();
	Tet_Gift_Wrap_Store_Api::init();
	Tet_Gift_Wrap_Order::init();

	// Register block checkout integration (fires after WC Blocks is ready).
	add_action(
		'woocommerce_blocks_checkout_block_registration',
		function ( $integration_registry ) {
			require_once TET_GIFT_WRAP_PATH . 'includes/class-gift-wrap-blocks.php';
			$integration_registry->register( new Tet_Gift_Wrap_Blocks_Integration() );
		}
	);
} );

require_once plugin_dir_path( __FILE__ ) . 'update-checker.php';
new TTRP_Update_Checker( __FILE__, 'woo-tet-gift-wrap' );
