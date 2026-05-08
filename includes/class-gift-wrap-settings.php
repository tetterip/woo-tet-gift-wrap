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
		add_filter( 'woocommerce_get_sections_products', [ __CLASS__, 'add_section' ] );
		add_filter( 'woocommerce_get_settings_products', [ __CLASS__, 'add_settings' ], 10, 2 );
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
				'type'              => 'price',
				'default'           => '3.00',
				'css'               => 'max-width:80px;',
				'desc_tip'          => true,
				'custom_attributes' => [ 'min' => '0', 'step' => '0.01' ],
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
