<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin settings inside WooCommerce > Settings > Products.
 */
class Tet_Gift_Wrap_Settings {

	const OPTION_ENABLED = 'tet_gift_wrap_enabled';
	const OPTION_PRICE   = 'tet_gift_wrap_price';
	const OPTION_LABEL   = 'tet_gift_wrap_label';
	const OPTION_NOTE_LABEL = 'tet_gift_wrap_note_label';
	const OPTION_NOTE_ENABLED = 'tet_gift_wrap_note_enabled';
	const OPTION_POSITION_CLASSIC = 'tet_gift_wrap_position_classic';
	const OPTION_POSITION_BLOCKS  = 'tet_gift_wrap_position_blocks';

	/**
	 * Classic checkout positions: key => action hook. before_submit is inside
	 * the payment box, which WooCommerce re-renders on every checkout update,
	 * so the field state must always come from the session.
	 */
	const CLASSIC_POSITIONS = [
		'before_order_review' => 'woocommerce_checkout_before_order_review',
		'before_payment'      => 'woocommerce_review_order_before_payment',
		'before_submit'       => 'woocommerce_review_order_before_submit',
		'after_order_notes'   => 'woocommerce_after_order_notes',
	];

	/** Block checkout positions; src/gift-wrap-blocks.js maps each to a slot or a checkout section. */
	const BLOCK_POSITIONS = [ 'order_summary', 'before_payment', 'before_submit', 'after_order_notes' ];

	// Plugin title in admin is never translated (suite-wide uniformity rule).
	const PLUGIN_TITLE = 'Gift Wrap';

	/** Screen id of the settings page, set once the submenu is registered. */
	private static string $page_hook = '';

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'maybe_register_brand_menu' ], 5 );
		add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		add_filter( 'woocommerce_screen_ids', [ __CLASS__, 'add_screen_id' ] );
	}

	/**
	 * Treat the settings page as a WooCommerce screen so WC loads its admin JS
	 * (woocommerce_admin + tipTip). Without it the desc_tip help icons render
	 * but show no tooltip.
	 */
	public static function add_screen_id( array $screen_ids ): array {
		if ( self::$page_hook ) {
			$screen_ids[] = self::$page_hook;
		}
		return $screen_ids;
	}

	public static function enqueue_admin_assets(): void {
		$on_settings_page = isset( $_GET['page'] ) && 'ttrp-gift-wrap' === $_GET['page'];
		$on_order_page    = ( isset( $_GET['page'] ) && 'wc-orders' === $_GET['page'] && isset( $_GET['action'] ) && 'edit' === $_GET['action'] )
		                 || ( isset( $GLOBALS['post_type'] ) && 'shop_order' === $GLOBALS['post_type'] );
		if ( ! $on_settings_page && ! $on_order_page ) {
			return;
		}
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'ttrp-admin', TET_GIFT_WRAP_URL . 'assets/ttrp-admin.css', [], TET_GIFT_WRAP_VERSION );
	}

	public static function maybe_register_brand_menu(): void {
		global $menu;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'ttrp-plugins' === $item[2] ) {
					return;
				}
			}
		}
		if ( ! function_exists( 'ttrp_brand_dashboard' ) ) {
			function ttrp_brand_dashboard(): void {
				global $submenu;
				$target = null;
				if ( ! empty( $submenu['ttrp-plugins'] ) ) {
					foreach ( $submenu['ttrp-plugins'] as $item ) {
						if ( $item[2] !== 'ttrp-plugins' ) {
							$target = $item[2];
							break;
						}
					}
				}
				if ( $target ) {
					$url = esc_url( admin_url( 'admin.php?page=' . $target ) );
					echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
					echo '<script>window.location.replace("' . $url . '");</script>';
				}
			}
		}
		$svg_path = dirname( __FILE__ ) . '/../assets/ttrp-logo.svg';
		$icon     = file_exists( $svg_path )
			? 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $svg_path ) )
			: 'dashicons-admin-plugins';
		add_menu_page(
			'ttrp.gr Plugins',
			'ttrp.gr Plugins',
			'manage_options',
			'ttrp-plugins',
			'ttrp_brand_dashboard',
			$icon,
			56
		);
		add_action( 'admin_menu', function () {
			remove_submenu_page( 'ttrp-plugins', 'ttrp-plugins' );
		}, 999 );
	}

	public static function add_submenu(): void {
		self::$page_hook = (string) add_submenu_page(
			'ttrp-plugins',
			self::PLUGIN_TITLE,
			self::PLUGIN_TITLE,
			'manage_options',
			'ttrp-gift-wrap',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['save_ttrp_gift_wrap'] ) && check_admin_referer( 'ttrp_gift_wrap_save' ) ) {
			woocommerce_update_options( self::add_settings( [], 'tet_gift_wrap' ) );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'tet-gift-wrap' ) . '</p></div>';
		}
		?>
		<div class="wrap woocommerce ttrp-wrap">
			<div class="ttrp-plugin-header">
				<h1><?php echo esc_html( self::PLUGIN_TITLE ); ?></h1>
				<span class="ttrp-plugin-version">v<?php echo esc_html( TET_GIFT_WRAP_VERSION ); ?></span>
			</div>
			<form method="post">
				<?php
				wp_nonce_field( 'ttrp_gift_wrap_save' );
				woocommerce_admin_fields( self::add_settings( [], 'tet_gift_wrap' ) );
				?>
				<p class="submit">
					<input type="submit" name="save_ttrp_gift_wrap" class="button-primary" value="<?php esc_attr_e( 'Save settings', 'tet-gift-wrap' ); ?>" />
				</p>
			</form>
			<div class="ttrp-settings-footer">
				<img src="<?php echo esc_url( plugins_url( '../assets/ttrp.svg', __FILE__ ) ); ?>" alt="" />
				<span><?php echo esc_html( self::PLUGIN_TITLE ); ?> by <a href="https://ttrp.gr" target="_blank">ttrp.gr</a></span>
			</div>
		</div>
		<?php
	}

	public static function add_section( array $sections ): array {
		$sections['tet_gift_wrap'] = __( 'Gift Wrap', 'tet-gift-wrap' );
		return $sections;
	}

	public static function add_settings( array $settings, string $current_section ): array {
		if ( 'tet_gift_wrap' !== $current_section ) {
			return $settings;
		}

		return [
			[
				'title' => __( 'Gift Wrap Options', 'tet-gift-wrap' ),
				'type'  => 'title',
				'id'    => 'tet_gift_wrap_section_start',
			],
			[
				'title'   => __( 'Enable Gift Wrap', 'tet-gift-wrap' ),
				'desc'    => __( 'Show a gift wrap option on the checkout page.', 'tet-gift-wrap' ),
				'id'      => self::OPTION_ENABLED,
				'type'    => 'checkbox',
				'default' => 'yes',
			],
			[
				'title'             => __( 'Gift Wrap Price', 'tet-gift-wrap' ),
				'desc'              => __( 'Set to 0 to offer gift wrapping for free.', 'tet-gift-wrap' ),
				'id'                => self::OPTION_PRICE,
				'type'              => 'text',
				'default'           => '3.00',
				'css'               => 'max-width:80px;',
				'desc_tip'          => true,
				'custom_attributes' => [ 'type' => 'number', 'min' => '0', 'step' => '0.01' ],
			],
			[
				'title'       => __( 'Checkbox Label', 'tet-gift-wrap' ),
				'desc'        => __( 'Label shown next to the gift wrap checkbox at checkout. Leave empty to use the default text.', 'tet-gift-wrap' ),
				'id'          => self::OPTION_LABEL,
				'type'        => 'text',
				'default'     => '',
				'placeholder' => self::default_label(),
				'css'         => 'min-width:350px;',
				'desc_tip'    => true,
			],
			[
				'title'   => __( 'Enable Gift Note', 'tet-gift-wrap' ),
				'desc'    => __( 'Let customers add a short personal note.', 'tet-gift-wrap' ),
				'id'      => self::OPTION_NOTE_ENABLED,
				'type'    => 'checkbox',
				'default' => 'yes',
			],
			[
				'title'       => __( 'Gift Note Label', 'tet-gift-wrap' ),
				'desc'        => __( 'Label shown above the optional gift note textarea at checkout. Leave empty to use the default text.', 'tet-gift-wrap' ),
				'id'          => self::OPTION_NOTE_LABEL,
				'type'        => 'text',
				'default'     => '',
				'placeholder' => self::default_note_label(),
				'css'         => 'min-width:350px;',
				'desc_tip'    => true,
			],
			[
				'title'    => __( 'Position (classic checkout)', 'tet-gift-wrap' ),
				'desc'     => __( 'Where the gift wrap option appears on the classic (shortcode) checkout.', 'tet-gift-wrap' ),
				'id'       => self::OPTION_POSITION_CLASSIC,
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'default'  => 'before_payment',
				'desc_tip' => true,
				'options'  => [
					'before_order_review' => __( 'Above the order summary', 'tet-gift-wrap' ),
					'before_payment'      => __( 'Above the payment methods', 'tet-gift-wrap' ),
					'before_submit'       => __( 'Above the Place order button', 'tet-gift-wrap' ),
					'after_order_notes'   => __( 'Below the order notes', 'tet-gift-wrap' ),
				],
			],
			[
				'title'    => __( 'Position (block checkout)', 'tet-gift-wrap' ),
				'desc'     => __( 'Where the gift wrap option appears on the block checkout. If that section is not on your checkout page (e.g. order notes are turned off), it is shown in the order summary.', 'tet-gift-wrap' ),
				'id'       => self::OPTION_POSITION_BLOCKS,
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'default'  => 'order_summary',
				'desc_tip' => true,
				'options'  => [
					'order_summary'     => __( 'In the order summary (sidebar)', 'tet-gift-wrap' ),
					'before_payment'    => __( 'Above the payment methods', 'tet-gift-wrap' ),
					'before_submit'     => __( 'Above the Place order button', 'tet-gift-wrap' ),
					'after_order_notes' => __( 'Below the order notes', 'tet-gift-wrap' ),
				],
			],
			[
				'type' => 'sectionend',
				'id'   => 'tet_gift_wrap_section_end',
			],
		];
	}

	public static function is_enabled(): bool {
		return 'yes' === get_option( self::OPTION_ENABLED, 'yes' );
	}

	public static function get_price(): float {
		return (float) get_option( self::OPTION_PRICE, '3.00' );
	}

	/**
	 * A cleared label field is saved as '', which get_option() returns as-is
	 * (its default only applies to a missing option), so empty falls back here.
	 */
	public static function get_label(): string {
		$label = trim( (string) get_option( self::OPTION_LABEL, '' ) );
		return '' !== $label ? $label : self::default_label();
	}

	public static function default_label(): string {
		return __( 'Add gift wrapping to my order', 'tet-gift-wrap' );
	}

	public static function is_note_enabled(): bool {
		return 'yes' === get_option( self::OPTION_NOTE_ENABLED, 'yes' );
	}

	public static function get_note_label(): string {
		$label = trim( (string) get_option( self::OPTION_NOTE_LABEL, '' ) );
		return '' !== $label ? $label : self::default_note_label();
	}

	public static function default_note_label(): string {
		return __( 'Gift note (optional)', 'tet-gift-wrap' );
	}

	public static function get_position_classic(): string {
		$position = (string) get_option( self::OPTION_POSITION_CLASSIC, 'before_payment' );
		return isset( self::CLASSIC_POSITIONS[ $position ] ) ? $position : 'before_payment';
	}

	public static function get_position_blocks(): string {
		$position = (string) get_option( self::OPTION_POSITION_BLOCKS, 'order_summary' );
		return in_array( $position, self::BLOCK_POSITIONS, true ) ? $position : 'order_summary';
	}
}
