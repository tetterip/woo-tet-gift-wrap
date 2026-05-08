( function ( $ ) {
	'use strict';

	function toggleNote( checked ) {
		var $noteWrap = $( '.tet-gift-wrap-note-wrap' );
		if ( checked ) {
			$noteWrap.slideDown( 200 );
		} else {
			$noteWrap.slideUp( 200 );
			$noteWrap.find( '.tet-gift-wrap-note' ).val( '' );
		}
	}

	$( document ).on( 'change', '#tet_gift_wrap', function () {
		var checked = $( this ).is( ':checked' );

		// Trigger WooCommerce cart totals update so the fee appears instantly.
		$( 'body' ).trigger( 'update_checkout' );

		if ( tetGiftWrap.noteEnabled ) {
			toggleNote( checked );
		}
	} );

	// Re-attach after WooCommerce replaces checkout fragments.
	$( document.body ).on( 'updated_checkout', function () {
		var checked = $( '#tet_gift_wrap' ).is( ':checked' );
		if ( tetGiftWrap.noteEnabled ) {
			var $noteWrap = $( '.tet-gift-wrap-note-wrap' );
			if ( ! checked ) {
				$noteWrap.hide();
			}
		}
	} );
} )( jQuery );
