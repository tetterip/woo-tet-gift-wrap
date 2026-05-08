<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles the checkout checkbox, fee injection, and order meta saving.
 */
class Tet_Gift_Wrap_Checkout {

	public static function init(): void {
		add_action( 'woocommerce_review_order_before_payment', [ __CLASS__, 'render_field' ] );
		add_action( 'woocommerce_cart_calculate_fees', [ __CLASS__, 'add_fee' ] );
		add_action( 'woocommerce_checkout_process', [ __CLASS__, 'validate' ] );
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'save_meta' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	public static function enqueue_assets(): void {
		if ( ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'tet-gift-wrap',
			TET_GIFT_WRAP_URL . 'assets/css/gift-wrap.css',
			[],
			TET_GIFT_WRAP_VERSION
		);

		wp_enqueue_script(
			'tet-gift-wrap',
			TET_GIFT_WRAP_URL . 'assets/js/gift-wrap.js',
			[ 'jquery' ],
			TET_GIFT_WRAP_VERSION,
			true
		);

		wp_localize_script( 'tet-gift-wrap', 'tetGiftWrap', [
			'noteEnabled' => Tet_Gift_Wrap_Settings::is_note_enabled(),
		] );
	}

	public static function render_field(): void {
		if ( ! Tet_Gift_Wrap_Settings::is_enabled() ) {
			return;
		}

		$price        = Tet_Gift_Wrap_Settings::get_price();
		$label        = Tet_Gift_Wrap_Settings::get_label();
		$note_enabled = Tet_Gift_Wrap_Settings::is_note_enabled();
		$note_label   = Tet_Gift_Wrap_Settings::get_note_label();
		$checked      = ! empty( WC()->session ) && WC()->session->get( 'tet_gift_wrap' );
		$note_val     = ! empty( WC()->session ) ? (string) WC()->session->get( 'tet_gift_wrap_note' ) : '';

		$price_html = '';
		if ( $price > 0 ) {
			$price_html = ' <span class="tet-gift-wrap-price">(' . wc_price( $price ) . ')</span>';
		}
		?>
		<div class="tet-gift-wrap-field">
			<label class="tet-gift-wrap-checkbox-label">
				<input
					type="checkbox"
					id="tet_gift_wrap"
					name="tet_gift_wrap"
					value="1"
					class="tet-gift-wrap-checkbox"
					<?php checked( $checked ); ?>
				/>
				<?php echo wp_kses_post( $label . $price_html ); ?>
			</label>

			<?php if ( $note_enabled ) : ?>
			<div class="tet-gift-wrap-note-wrap" <?php echo $checked ? '' : 'style="display:none;"'; ?>>
				<label for="tet_gift_wrap_note" class="tet-gift-wrap-note-label">
					<?php echo esc_html( $note_label ); ?>
				</label>
				<textarea
					id="tet_gift_wrap_note"
					name="tet_gift_wrap_note"
					class="tet-gift-wrap-note"
					rows="2"
					maxlength="200"
				><?php echo esc_textarea( $note_val ); ?></textarea>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Adds the gift wrap fee to the cart when the checkbox is checked.
	 * WooCommerce recalculates totals via AJAX on checkout changes, so we
	 * read from POST (during AJAX) or session (on page load).
	 */
	public static function add_fee( WC_Cart $cart ): void {
		if ( ! Tet_Gift_Wrap_Settings::is_enabled() ) {
			return;
		}

		$price = Tet_Gift_Wrap_Settings::get_price();
		if ( $price <= 0 ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$checked = isset( $_POST['tet_gift_wrap'] ) ? (bool) $_POST['tet_gift_wrap'] : (bool) ( WC()->session ? WC()->session->get( 'tet_gift_wrap' ) : false );

		// Persist to session so the fee survives page reload.
		if ( WC()->session ) {
			WC()->session->set( 'tet_gift_wrap', $checked );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$note = isset( $_POST['tet_gift_wrap_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tet_gift_wrap_note'] ) ) : (string) WC()->session->get( 'tet_gift_wrap_note' );
			WC()->session->set( 'tet_gift_wrap_note', $note );
		}

		if ( $checked ) {
			$cart->add_fee( __( 'Gift Wrap', 'tet-gift-wrap' ), $price, false );
		}
	}

	public static function validate(): void {
		// Nothing to validate – the field is optional.
	}

	public static function save_meta( WC_Order $order, array $data ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$checked = ! empty( $_POST['tet_gift_wrap'] );
		$order->update_meta_data( '_tet_gift_wrap', $checked ? 'yes' : 'no' );

		if ( $checked && Tet_Gift_Wrap_Settings::is_note_enabled() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$note = isset( $_POST['tet_gift_wrap_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tet_gift_wrap_note'] ) ) : '';
			$order->update_meta_data( '_tet_gift_wrap_note', $note );
		}

		// Clear session.
		if ( WC()->session ) {
			WC()->session->set( 'tet_gift_wrap', false );
			WC()->session->set( 'tet_gift_wrap_note', '' );
		}
	}
}
