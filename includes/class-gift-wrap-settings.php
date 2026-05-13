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

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'maybe_register_brand_menu' ], 5 );
		add_action( 'admin_menu', [ __CLASS__, 'add_submenu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
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
		add_submenu_page(
			'ttrp-plugins',
			__( 'Gift Wrap', 'tet-gift-wrap' ),
			__( 'Gift Wrap', 'tet-gift-wrap' ),
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
				<h1><?php esc_html_e( 'Gift Wrap', 'tet-gift-wrap' ); ?></h1>
				<span class="ttrp-plugin-version">v1.0.5</span>
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
				<img src="<?php echo esc_url( plugins_url( '../assets/ttrp-logo.svg', __FILE__ ) ); ?>" alt="" />
				<span><?php esc_html_e( 'Gift Wrap by', 'tet-gift-wrap' ); ?> <a href="https://ttrp.gr" target="_blank">ttrp.gr</a></span>
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
				'title'   => __( 'Checkbox Label', 'tet-gift-wrap' ),
				'id'      => self::OPTION_LABEL,
				'type'    => 'text',
				'default' => __( 'Add gift wrapping to my order', 'tet-gift-wrap' ),
				'css'     => 'min-width:350px;',
			],
			[
				'title'   => __( 'Enable Gift Note', 'tet-gift-wrap' ),
				'desc'    => __( 'Let customers add a short personal note.', 'tet-gift-wrap' ),
				'id'      => self::OPTION_NOTE_ENABLED,
				'type'    => 'checkbox',
				'default' => 'yes',
			],
			[
				'title'   => __( 'Gift Note Label', 'tet-gift-wrap' ),
				'id'      => self::OPTION_NOTE_LABEL,
				'type'    => 'text',
				'default' => __( 'Gift note (optional)', 'tet-gift-wrap' ),
				'css'     => 'min-width:350px;',
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

	public static function get_label(): string {
		return (string) get_option( self::OPTION_LABEL, __( 'Add gift wrapping to my order', 'tet-gift-wrap' ) );
	}

	public static function is_note_enabled(): bool {
		return 'yes' === get_option( self::OPTION_NOTE_ENABLED, 'yes' );
	}

	public static function get_note_label(): string {
		return (string) get_option( self::OPTION_NOTE_LABEL, __( 'Gift note (optional)', 'tet-gift-wrap' ) );
	}
}
