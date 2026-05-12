<?php
defined( 'ABSPATH' ) || exit;

/**
 * Integrates with the WooCommerce Store API for the block checkout.
 *
 * Uses ExtendRestApi (WC 7–8.4) / ExtendSchema (WC 8.5+) to register both
 * the cart schema namespace and the update callback. Both registrations are
 * required: the schema declaration is what the client-side extensionCartUpdate
 * validates against before posting; the callback handles the actual data.
 */
class Tet_Gift_Wrap_Store_Api {

	public static function init(): void {
		// woocommerce_blocks_loaded fires after WC Blocks has fully initialised
		// its DI container, making ExtendRestApi / ExtendSchema available.
		add_action( 'woocommerce_blocks_loaded', [ __CLASS__, 'register_extension' ] );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ __CLASS__, 'save_meta' ] );
	}

	public static function register_extension(): void {
		$extend = self::get_extend();
		if ( ! $extend ) {
			return;
		}

		// Declare the namespace in the cart response schema. Without this the
		// client rejects extensionCartUpdate calls with "no such namespace".
		$extend->register_endpoint_data( [
			'endpoint'        => 'cart',
			'namespace'       => 'tet-gift-wrap',
			'data_callback'   => '__return_empty_array',
			'schema_callback' => '__return_empty_array',
			'schema_type'     => ARRAY_A,
		] );

		$extend->register_update_callback( [
			'namespace' => 'tet-gift-wrap',
			'callback'  => [ __CLASS__, 'update_session' ],
		] );
	}

	/**
	 * Returns the ExtendRestApi / ExtendSchema instance, handling both the
	 * WC 8.5+ standalone StoreApi package and the older Blocks-namespaced API.
	 *
	 * @return object|null
	 */
	private static function get_extend() {
		// WC 8.5+: StoreApi lives in its own package.
		if ( class_exists( '\Automattic\WooCommerce\StoreApi\StoreApi' )
			&& class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema' ) ) {
			return \Automattic\WooCommerce\StoreApi\StoreApi::container()->get(
				\Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class
			);
		}

		// WC 7.0–8.4: ExtendRestApi is still in the Blocks namespace.
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Package' )
			&& class_exists( '\Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi' ) ) {
			return \Automattic\WooCommerce\Blocks\Package::container()->get(
				\Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi::class
			);
		}

		return null;
	}

	/**
	 * Receives { gift_wrap: bool, gift_wrap_note: string } from the JS component
	 * and writes it to the WC session so the cart fee hook picks it up.
	 */
	public static function update_session( array $data ): void {
		if ( ! WC()->session ) {
			return;
		}

		$checked = ! empty( $data['gift_wrap'] );
		WC()->session->set( 'tet_gift_wrap', $checked );

		$note = $checked && isset( $data['gift_wrap_note'] )
			? sanitize_textarea_field( wp_unslash( $data['gift_wrap_note'] ) )
			: '';
		WC()->session->set( 'tet_gift_wrap_note', $note );
	}

	/**
	 * Saves order meta for block checkout orders (equivalent of
	 * woocommerce_checkout_create_order for the classic flow).
	 */
	public static function save_meta( WC_Order $order ): void {
		if ( ! WC()->session ) {
			return;
		}

		$checked = (bool) WC()->session->get( 'tet_gift_wrap' );
		$order->update_meta_data( '_tet_gift_wrap', $checked ? 'yes' : 'no' );

		if ( $checked && Tet_Gift_Wrap_Settings::is_note_enabled() ) {
			$note = (string) WC()->session->get( 'tet_gift_wrap_note' );
			$order->update_meta_data( '_tet_gift_wrap_note', $note );
		}

		WC()->session->set( 'tet_gift_wrap', false );
		WC()->session->set( 'tet_gift_wrap_note', '' );
	}
}
