const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

// WooCommerce packages ship as runtime globals in the WP environment.
// The default @wordpress/scripts config only knows about @wordpress/* externals.
// We replace its DependencyExtractionWebpackPlugin with one that also maps
// @woocommerce/* to their window.wc.* globals and script handles.
const WC_EXTERNALS = {
	'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
	'@woocommerce/settings': [ 'wc', 'wcSettings' ],
	'@woocommerce/block-data': [ 'wc', 'blockData' ],
};
const WC_HANDLES = {
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
	'@woocommerce/settings': 'wc-settings',
	'@woocommerce/block-data': 'wc-block-data',
};

const filteredPlugins = defaultConfig.plugins.filter(
	( plugin ) => ! ( plugin instanceof DependencyExtractionWebpackPlugin )
);

module.exports = {
	...defaultConfig,
	// Disable automatic output directory cleaning — assets/js also contains
	// the hand-authored gift-wrap.js for the classic checkout.
	output: {
		...defaultConfig.output,
		clean: false,
	},
	plugins: [
		...filteredPlugins,
		new DependencyExtractionWebpackPlugin( {
			requestToExternal( request ) {
				if ( WC_EXTERNALS[ request ] ) {
					return WC_EXTERNALS[ request ];
				}
			},
			requestToHandle( request ) {
				if ( WC_HANDLES[ request ] ) {
					return WC_HANDLES[ request ];
				}
			},
		} ),
	],
};
