import { useState, useRef } from '@wordpress/element';
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
} = settings;

const GiftWrapCheckout = () => {
	const [ checked, setChecked ] = useState( false );
	const [ note, setNote ] = useState( '' );
	const noteTimer = useRef( null );

	if ( ! enabled ) {
		return null;
	}

	const sendUpdate = ( isChecked, currentNote ) => {
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

	return (
		<ExperimentalOrderMeta>
			<div className="tet-gift-wrap-field">
				<label className="tet-gift-wrap-checkbox-label">
					<input
						type="checkbox"
						className="tet-gift-wrap-checkbox"
						checked={ checked }
						onChange={ handleCheckboxChange }
					/>
					{ label }
					{ priceFormatted && (
						<span className="tet-gift-wrap-price">
							({ priceFormatted })
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
		</ExperimentalOrderMeta>
	);
};

registerPlugin( 'tet-gift-wrap', {
	render: GiftWrapCheckout,
	scope: 'woocommerce-checkout',
} );
