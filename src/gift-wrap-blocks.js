import { useState, useRef, useEffect, createPortal } from '@wordpress/element';
import { useSelect, select, dispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import {
	ExperimentalOrderMeta,
	extensionCartUpdate,
} from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'tet-gift-wrap_data', {} );
const {
	enabled = false,
	label = 'Add gift wrapping to my order',
	priceFormatted = '',
	noteEnabled = false,
	noteLabel = 'Gift note (optional)',
	position = 'order_summary',
} = settings;

/**
 * Positions in the main checkout column. The block checkout has no slot
 * there, so the field is portalled into a container placed next to the
 * checkout section's wrapper. 'order_summary' (and any position whose section
 * is missing from the page) uses the ExperimentalOrderMeta slot instead.
 */
const TARGETS = {
	before_payment: {
		selector: '.wp-block-woocommerce-checkout-payment-block',
		before: true,
	},
	before_submit: {
		selector: '.wp-block-woocommerce-checkout-actions-block',
		before: true,
	},
	after_order_notes: {
		selector: '.wp-block-woocommerce-checkout-order-note-block',
		before: false,
	},
};

const ACTIONS_SELECTOR = TARGETS.before_submit.selector;
const CART_STORE = 'wc/store/cart';

/**
 * extensionCartUpdate() replaces the cart with the server's copy, customer
 * address included. WooCommerce sends address edits to the server after a
 * short delay, so an address typed just before ticking the box (or typing
 * the note) would be overwritten with the old one. Push it first.
 */
const flushCustomerData = async () => {
	const { billingAddress, shippingAddress } =
		select( CART_STORE ).getCustomerData();
	try {
		await dispatch( CART_STORE ).updateCustomerData( {
			billing_address: billingAddress,
			shipping_address: shippingAddress,
		} );
	} catch ( e ) {
		// An incomplete address is rejected; WooCommerce shows its own errors.
	}
};

// Backstop: how long to wait for a lazily rendered section before using the fallback.
const FALLBACK_MS = 3000;

/**
 * Keeps a container element next to the target section, re-inserting it if
 * the checkout re-renders that section.
 *
 * @return {{container: HTMLElement|null, fallback: boolean}}
 */
const usePortalContainer = () => {
	const target = TARGETS[ position ];
	const [ container, setContainer ] = useState( null );
	const [ fallback, setFallback ] = useState( ! target );

	useEffect( () => {
		if ( ! target ) {
			return undefined;
		}
		const el = document.createElement( 'div' );
		el.className = 'tet-gift-wrap-portal';

		const place = () => {
			const anchor = document.querySelector( target.selector );
			if ( ! anchor || ! anchor.parentNode ) {
				return false;
			}
			const inPlace = target.before
				? anchor.previousSibling === el
				: anchor.nextSibling === el;
			if ( ! inPlace ) {
				anchor.parentNode.insertBefore(
					el,
					target.before ? anchor : anchor.nextSibling
				);
			}
			return true;
		};

		const sync = () => {
			if ( place() ) {
				setContainer( el );
				setFallback( false );
			} else if ( document.querySelector( ACTIONS_SELECTOR ) ) {
				// The Place order section is always on the page and renders
				// last, so once it's there the target section is missing.
				// If the target shows up later, the next sync moves it back.
				setFallback( true );
			}
		};
		sync();

		const observer = new window.MutationObserver( sync );
		observer.observe( document.body, { childList: true, subtree: true } );
		const timer = setTimeout( () => {
			if ( ! el.isConnected ) {
				setFallback( true );
			}
		}, FALLBACK_MS );

		return () => {
			observer.disconnect();
			clearTimeout( timer );
			el.remove();
		};
	}, [ target ] );

	return { container, fallback };
};

const GiftWrapCheckout = () => {
	const [ checked, setChecked ] = useState( false );
	const [ note, setNote ] = useState( '' );
	const noteTimer = useRef( null );
	const initialised = useRef( false );
	const { container, fallback } = usePortalContainer();

	// Start from the session state the server reports with the cart, so a
	// page reload keeps the box ticked while the fee is in the cart.
	const saved = useSelect(
		( select ) =>
			select( 'wc/store/cart' ).getCartData()?.extensions?.[
				'tet-gift-wrap'
			],
		[]
	);
	useEffect( () => {
		if ( ! initialised.current && saved ) {
			initialised.current = true;
			setChecked( !! saved.gift_wrap );
			setNote( saved.gift_wrap_note || '' );
		}
	}, [ saved ] );

	// Live price text from the cart ("Free" above the threshold); the static
	// setting is only a fallback until the cart data has loaded.
	const priceLabel =
		typeof saved?.price_label === 'string'
			? saved.price_label
			: priceFormatted;

	const sendUpdate = async ( isChecked, currentNote ) => {
		await flushCustomerData();
		extensionCartUpdate( {
			namespace: 'tet-gift-wrap',
			data: {
				gift_wrap: isChecked,
				gift_wrap_note: isChecked ? currentNote : '',
			},
		} );
	};

	const handleCheckboxChange = ( e ) => {
		const isChecked = e.target.checked;
		initialised.current = true;
		setChecked( isChecked );
		if ( ! isChecked ) {
			setNote( '' );
		}
		sendUpdate( isChecked, isChecked ? note : '' );
	};

	const handleNoteChange = ( e ) => {
		const newNote = e.target.value;
		setNote( newNote );
		clearTimeout( noteTimer.current );
		noteTimer.current = setTimeout( () => {
			sendUpdate( checked, newNote );
		}, 400 );
	};

	const field = (
		<div className={ `tet-gift-wrap-field tet-gift-wrap-field--${ position }` }>
			<label className="tet-gift-wrap-checkbox-label">
				<input
					type="checkbox"
					className="tet-gift-wrap-checkbox"
					checked={ checked }
					onChange={ handleCheckboxChange }
				/>
				{ label }
				{ priceLabel && (
					<span className="tet-gift-wrap-price">
						({ priceLabel })
					</span>
				) }
			</label>

			{ noteEnabled && checked && (
				<div className="tet-gift-wrap-note-wrap">
					<label className="tet-gift-wrap-note-label">
						{ noteLabel }
					</label>
					<textarea
						className="tet-gift-wrap-note"
						rows={ 2 }
						maxLength={ 200 }
						value={ note }
						onChange={ handleNoteChange }
					/>
				</div>
			) }
		</div>
	);

	if ( fallback ) {
		return <ExperimentalOrderMeta>{ field }</ExperimentalOrderMeta>;
	}
	return container ? createPortal( field, container ) : null;
};

if ( enabled ) {
	registerPlugin( 'tet-gift-wrap', {
		render: GiftWrapCheckout,
		scope: 'woocommerce-checkout',
	} );
}
