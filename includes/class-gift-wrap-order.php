<?php
defined( 'ABSPATH' ) || exit;

/**
 * Displays the gift wrap status in the admin order view, customer emails,
 * and the customer-facing order confirmation / account pages.
 */
class Tet_Gift_Wrap_Order {

	public static function init(): void {
		// Admin order page – meta box panel.
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ __CLASS__, 'render_admin_panel' ] );

		// Customer-facing order details (thank-you page & my-account).
		add_action( 'woocommerce_order_details_after_order_table', [ __CLASS__, 'render_frontend_notice' ] );

		// Order confirmation / processing emails.
		add_action( 'woocommerce_email_order_meta', [ __CLASS__, 'render_email_row' ], 10, 3 );
	}

	public static function render_admin_panel( WC_Order $order ): void {
		$wrapped = $order->get_meta( '_tet_gift_wrap' );
		if ( '' === $wrapped ) {
			return;
		}

		$note = (string) $order->get_meta( '_tet_gift_wrap_note' );
		?>
		<div class="tet-gift-wrap-admin-panel">
			<h4><?php esc_html_e( 'Gift Wrap', 'tet-gift-wrap' ); ?></h4>
			<p>
				<?php if ( 'yes' === $wrapped ) : ?>
					<span class="tet-gift-wrap-badge tet-gift-wrap-badge--yes">
						<?php esc_html_e( 'Yes – gift wrapped', 'tet-gift-wrap' ); ?>
					</span>
				<?php else : ?>
					<span class="tet-gift-wrap-badge tet-gift-wrap-badge--no">
						<?php esc_html_e( 'No', 'tet-gift-wrap' ); ?>
					</span>
				<?php endif; ?>
			</p>
			<?php if ( 'yes' === $wrapped && '' !== $note ) : ?>
				<p class="tet-gift-wrap-admin-note">
					<strong><?php esc_html_e( 'Gift note:', 'tet-gift-wrap' ); ?></strong>
					<?php echo esc_html( $note ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function render_frontend_notice( WC_Order $order ): void {
		if ( 'yes' !== $order->get_meta( '_tet_gift_wrap' ) ) {
			return;
		}

		$note = (string) $order->get_meta( '_tet_gift_wrap_note' );
		?>
		<section class="tet-gift-wrap-notice woocommerce-info">
			<p>
				<strong><?php esc_html_e( 'Gift Wrap:', 'tet-gift-wrap' ); ?></strong>
				<?php esc_html_e( 'Your order will be gift wrapped.', 'tet-gift-wrap' ); ?>
			</p>
			<?php if ( '' !== $note ) : ?>
				<p>
					<strong><?php esc_html_e( 'Gift note:', 'tet-gift-wrap' ); ?></strong>
					<?php echo esc_html( $note ); ?>
				</p>
			<?php endif; ?>
		</section>
		<?php
	}

	public static function render_email_row( WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
		if ( 'yes' !== $order->get_meta( '_tet_gift_wrap' ) ) {
			return;
		}

		$note = (string) $order->get_meta( '_tet_gift_wrap_note' );

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Gift Wrap: Yes', 'tet-gift-wrap' );
			if ( '' !== $note ) {
				echo "\n" . esc_html__( 'Gift note: ', 'tet-gift-wrap' ) . esc_html( $note );
			}
			echo "\n";
		} else {
			?>
			<tr>
				<th><?php esc_html_e( 'Gift Wrap', 'tet-gift-wrap' ); ?></th>
				<td><?php esc_html_e( 'Yes – your order will be gift wrapped.', 'tet-gift-wrap' ); ?></td>
			</tr>
			<?php if ( '' !== $note ) : ?>
			<tr>
				<th><?php esc_html_e( 'Gift note', 'tet-gift-wrap' ); ?></th>
				<td><?php echo esc_html( $note ); ?></td>
			</tr>
			<?php endif;
		}
	}
}
