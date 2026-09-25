<?php
defined( 'ABSPATH' ) || exit;

/**
 * "Gift wrap" column and filter in the admin orders list, so the warehouse
 * can see which orders need wrapping without opening each one.
 *
 * Supports both order storage modes: HPOS (WooCommerce → Orders,
 * admin.php?page=wc-orders) and legacy posts (edit.php?post_type=shop_order).
 */
class Tet_Gift_Wrap_Orders_List {

	const COLUMN       = 'tet_gift_wrap';
	const FILTER_PARAM = 'tet_gift_wrap';
	const NOTE_PREVIEW = 40; // characters of the gift note shown in the column

	public static function init(): void {
		// HPOS.
		add_filter( 'manage_woocommerce_page_wc-orders_columns', [ __CLASS__, 'add_column' ] );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ __CLASS__, 'render_column' ], 10, 2 );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', [ __CLASS__, 'render_filter_hpos' ], 10, 2 );
		add_filter( 'woocommerce_order_list_table_prepare_items_query_args', [ __CLASS__, 'filter_query_hpos' ] );

		// Legacy posts storage.
		add_filter( 'manage_edit-shop_order_columns', [ __CLASS__, 'add_column' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ __CLASS__, 'render_column' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ __CLASS__, 'render_filter_legacy' ] );
		add_action( 'pre_get_posts', [ __CLASS__, 'filter_query_legacy' ] );
	}

	/**
	 * Inserts the column right after the order status.
	 */
	public static function add_column( array $columns ): array {
		$out = [];
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$out[ self::COLUMN ] = __( 'Gift wrap', 'tet-gift-wrap' );
			}
		}
		if ( ! isset( $out[ self::COLUMN ] ) ) {
			$out[ self::COLUMN ] = __( 'Gift wrap', 'tet-gift-wrap' );
		}
		return $out;
	}

	/**
	 * @param string       $column
	 * @param WC_Order|int $order  Order object (HPOS) or post ID (legacy).
	 */
	public static function render_column( $column, $order ): void {
		if ( self::COLUMN !== $column ) {
			return;
		}
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
		if ( ! $order || 'yes' !== $order->get_meta( '_tet_gift_wrap' ) ) {
			echo '<span aria-hidden="true">&ndash;</span><span class="screen-reader-text">' . esc_html__( 'No', 'tet-gift-wrap' ) . '</span>';
			return;
		}

		echo '<span class="ttrp-badge ttrp-badge--success">' . esc_html__( 'Yes', 'tet-gift-wrap' ) . '</span>';

		$note = trim( (string) $order->get_meta( '_tet_gift_wrap_note' ) );
		if ( '' !== $note ) {
			$preview = mb_strlen( $note ) > self::NOTE_PREVIEW
				? rtrim( mb_substr( $note, 0, self::NOTE_PREVIEW ) ) . '…'
				: $note;
			printf(
				'<br /><small class="tet-gift-wrap-list-note" title="%1$s">%2$s</small>',
				esc_attr( $note ),
				esc_html( '“' . $preview . '”' )
			);
		}
	}

	/* ── Filter ─────────────────────────────────────────────── */

	/** 'yes', 'no' or '' (no filter). */
	private static function current_filter(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
		$value = isset( $_GET[ self::FILTER_PARAM ] ) ? sanitize_key( wp_unslash( $_GET[ self::FILTER_PARAM ] ) ) : '';
		return in_array( $value, [ 'yes', 'no' ], true ) ? $value : '';
	}

	private static function render_filter(): void {
		$current = self::current_filter();
		$options = [
			''    => __( 'Gift wrap: all', 'tet-gift-wrap' ),
			'yes' => __( 'With gift wrap', 'tet-gift-wrap' ),
			'no'  => __( 'Without gift wrap', 'tet-gift-wrap' ),
		];
		?>
		<label for="filter-by-tet-gift-wrap" class="screen-reader-text"><?php esc_html_e( 'Filter by gift wrap', 'tet-gift-wrap' ); ?></label>
		<select name="<?php echo esc_attr( self::FILTER_PARAM ); ?>" id="filter-by-tet-gift-wrap">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Meta query for the current filter. "Without" also matches orders placed
	 * before the plugin was active, which have no gift wrap meta at all.
	 */
	private static function meta_query(): ?array {
		switch ( self::current_filter() ) {
			case 'yes':
				return [ 'key' => '_tet_gift_wrap', 'value' => 'yes' ];
			case 'no':
				return [
					'relation' => 'OR',
					[ 'key' => '_tet_gift_wrap', 'compare' => 'NOT EXISTS' ],
					[ 'key' => '_tet_gift_wrap', 'value' => 'yes', 'compare' => '!=' ],
				];
		}
		return null;
	}

	/**
	 * @param string $order_type
	 * @param string $which      'top' or 'bottom'.
	 */
	public static function render_filter_hpos( $order_type, $which = 'top' ): void {
		if ( 'shop_order' === $order_type && 'top' === $which ) {
			self::render_filter();
		}
	}

	public static function filter_query_hpos( array $args ): array {
		$meta_query = self::meta_query();
		if ( $meta_query ) {
			$args['meta_query']   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : [];
			$args['meta_query'][] = $meta_query;
		}
		return $args;
	}

	/** @param string $post_type */
	public static function render_filter_legacy( $post_type ): void {
		if ( 'shop_order' === $post_type ) {
			self::render_filter();
		}
	}

	/** @param WP_Query $query */
	public static function filter_query_legacy( $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'shop_order' !== $query->get( 'post_type' ) ) {
			return;
		}
		$meta_query = self::meta_query();
		if ( $meta_query ) {
			$existing   = (array) $query->get( 'meta_query' );
			$existing[] = $meta_query;
			$query->set( 'meta_query', $existing );
		}
	}
}
