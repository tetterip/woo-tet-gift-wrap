<?php
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Registers the plugin's block checkout integration with WooCommerce Blocks.
 *
 * Tells WC Blocks which script to load on the block checkout page and
 * passes the plugin settings to JS via get_script_data(), where they are
 * accessible as getSetting('tet-gift-wrap_data').
 */
class Tet_Gift_Wrap_Blocks_Integration implements IntegrationInterface {

	public function get_name(): string {
		return 'tet-gift-wrap';
	}

	public function get_version(): string {
		return TET_GIFT_WRAP_VERSION;
	}

	public function initialize(): void {
		wp_register_script(
			'tet-gift-wrap-blocks',
			TET_GIFT_WRAP_URL . 'assets/js/gift-wrap-blocks.js',
			$this->get_script_dependencies(),
			TET_GIFT_WRAP_VERSION,
			true
		);

		wp_register_style(
			'tet-gift-wrap-blocks',
			TET_GIFT_WRAP_URL . 'assets/css/gift-wrap.css',
			[],
			TET_GIFT_WRAP_VERSION
		);
	}

	public function get_script_handles(): array {
		return [ 'tet-gift-wrap-blocks' ];
	}

	public function get_editor_script_handles(): array {
		return [];
	}

	/**
	 * Returns settings passed to JS as getSetting('tet-gift-wrap_data').
	 */
	public function get_script_data(): array {
		$price = Tet_Gift_Wrap_Settings::get_price();

		return [
			'enabled'        => Tet_Gift_Wrap_Settings::is_enabled(),
			'price'          => $price,
			'priceFormatted' => $price > 0 ? wp_strip_all_tags( wc_price( $price ) ) : '',
			'label'          => Tet_Gift_Wrap_Settings::get_label(),
			'noteEnabled'    => Tet_Gift_Wrap_Settings::is_note_enabled(),
			'noteLabel'      => Tet_Gift_Wrap_Settings::get_note_label(),
		];
	}

	public function get_style_handles(): array {
		return [ 'tet-gift-wrap-blocks' ];
	}

	public function get_editor_style_handles(): array {
		return [];
	}

	/**
	 * Returns the wp-scripts asset file's dependency list when available,
	 * falling back to a safe static list.
	 */
	private function get_script_dependencies(): array {
		$asset_file = TET_GIFT_WRAP_PATH . 'assets/js/gift-wrap-blocks.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;
			return $asset['dependencies'] ?? [];
		}

		return [ 'react', 'wp-element', 'wp-plugins', 'wc-blocks-checkout', 'wc-settings' ];
	}
}
