import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import {
	getWithSharedAttributes,
	getPreview
} from '../functions'
import './editor.scss';
import getLogo from './../logo';

registerBlockType( 'directorist/checkout', {
	apiVersion: 2,

	title: __( 'Checkout', 'directorist' ),

	description: __( 'Create checkout page and this block only works on checkout page set from settings.', 'directorist' ),

	category: 'directorist-blocks-collection',

	icon: getLogo(),

	supports: {
		html: false,
	},

	transforms: {
		from: [
			{
				type: 'shortcode',
				tag: 'directorist_checkout',
				attributes: {}
			},
		]
	},

	example: {
		attributes: {
			isPreview: true
		}
	},

	attributes: getWithSharedAttributes(),

	edit( { attributes } ) {
		if ( attributes.isPreview ) {
			return <Fragment>{ getPreview( 'checkout' ) }</Fragment>
		}

		return (
			<div { ...useBlockProps() }>
				{ __( 'This block works only on Checkout page.', 'directorist' ) }
			</div>
		);
	}
} );
