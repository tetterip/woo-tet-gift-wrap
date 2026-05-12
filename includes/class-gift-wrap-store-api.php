<?php
defined( 'ABSPATH' ) || exit;

/**
 * Integrates with the WooCommerce Store API for the block checkout.
 *
 * Receives extensionCartUpdate calls from the JS component, persists the
 * gift wrap state to the WC session (the existing fee hook already reads
 * from session, so the fee works for both classic and block checkout), and
 * saves order meta when a block checkout order is processed.
 */
class Tet_Gift_Wrap_Store_Api {

	public static function init(): void {
		add_action( 'woocommerce_store_api_register_update_callbacks', [ __CLASS__, 'register_update_callback' ] );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ __CLASS__, 'save_meta' ] );
	}

	public static function register_update_callback( $callback_registry ): void {
		$callback_registry->register( [
			'namespace' => 'tet-gift-wrap',
			'callback'  => [ __CLASS__, 'update_session' ],
		] );
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
