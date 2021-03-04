import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import './editor.scss';

registerBlockType( 'directorist/listing-related', {
	apiVersion: 2,

	title: __( 'Single Listing Related', 'directorist' ),

	description: __( 'This block works only in Single Listing page.', 'directorist' ),

	category: 'widgets',

	icon: <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="none" d="M0 0h24v24H0V0z" /><path d="M19 13H5v-2h14v2z" /></svg>,

	supports: {
		html: false,
	},

	edit( props ) {
		return (
			<div { ...useBlockProps() }>
				{ __( 'This widget works only in Single Listing page.', 'directorist' ) }
			</div>
		);
	}
} );