<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles the checkout checkbox, fee injection, and order meta saving.
 */
class Tet_Gift_Wrap_Checkout {

	public static function init(): void {
		$hook = Tet_Gift_Wrap_Settings::CLASSIC_POSITIONS[ Tet_Gift_Wrap_Settings::get_position_classic() ];
		add_action( $hook, [ __CLASS__, 'render_field' ] );
		add_action( 'woocommerce_checkout_update_order_review', [ __CLASS__, 'capture_from_review' ] );
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
	 * Classic checkout AJAX refresh (update_order_review). WooCommerce sends
	 * the whole form as a URL-encoded post_data string, not as $_POST fields,
	 * so the checkbox and note are read from it and kept in the session. That
	 * session state drives both the fee and the re-rendered field.
	 *
	 * @param string $post_data
	 */
	public static function capture_from_review( $post_data ): void {
		if ( ! WC()->session || ! is_string( $post_data ) ) {
			return;
		}
		parse_str( $post_data, $fields );
		self::store_in_session( $fields );
	}

	/**
	 * @param array $fields Checkout form fields, already unslashed.
	 */
	private static function store_in_session( array $fields ): void {
		$checked = ! empty( $fields['tet_gift_wrap'] );
		$note    = $checked && isset( $fields['tet_gift_wrap_note'] )
			? sanitize_textarea_field( (string) $fields['tet_gift_wrap_note'] )
			: '';
		WC()->session->set( 'tet_gift_wrap', $checked );
		WC()->session->set( 'tet_gift_wrap_note', $note );
	}

	/**
	 * Adds the gift wrap fee while the checkbox is checked. The state comes
	 * from the session (kept current by capture_from_review() or the Store
	 * API), except when the classic form is submitted: then the posted form is
	 * the source of truth, and an unchecked box simply isn't posted.
	 */
	public static function add_fee( WC_Cart $cart ): void {
		if ( ! Tet_Gift_Wrap_Settings::is_enabled() ) {
			return;
		}

		$price = Tet_Gift_Wrap_Settings::get_price();
		if ( $price <= 0 ) {
			return;
		}

		if ( did_action( 'woocommerce_before_checkout_process' ) && WC()->session ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC_Checkout verified the nonce.
			self::store_in_session( wp_unslash( $_POST ) );
		}

		$checked = WC()->session && WC()->session->get( 'tet_gift_wrap' );

		if ( $checked ) {
			$cart->add_fee( __( 'Gift Wrap', 'tet-gift-wrap' ), $price, false );
		}
	}

	public static function validate(): void {
		// Nothing to validate – the field is optional.
	}

	public static function save_meta( WC_Order $order, array $data ): void {
		// Block checkout orders go through the Store API; meta is handled there.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

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
