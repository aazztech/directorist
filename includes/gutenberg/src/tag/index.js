import { registerBlockType, createBlock } from '@wordpress/blocks';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import {
	useBlockProps,
	InspectorControls,
	BlockControls
} from '@wordpress/block-editor';

import {
	list,
	grid,
	mapMarker
} from '@wordpress/icons';

import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	Toolbar,
	ToolbarButton,
} from '@wordpress/components';

import { getAttsForTransform } from '../functions'
import blockAttributes from './attributes.json';
import getLogo from '../logo';
import './editor.scss';

registerBlockType( 'directorist/tag', {
	apiVersion: 2,

	title: __( 'Single Tag', 'directorist' ),

	description: __( 'Create directory listing tags grid view, list view or map view.', 'directorist' ),

	category: 'directorist-blocks-collection',

	icon: getLogo(),

	supports: {
		html: false
	},

	transforms: {
		from: [
			{
				type: 'shortcode',
				tag: 'directorist_tag',
				attributes: getAttsForTransform( blockAttributes )
			},
			{
				type: 'block',
				blocks: [ 'directorist/all-listing', 'directorist/category', 'directorist/location' ],
				transform: ( attributes ) => {
					return createBlock( 'directorist/tag', attributes );
				},
			},
		]
	},

	attributes: blockAttributes,

	edit( { attributes, setAttributes } ) {
		let {
			view,
			header,
			header_title,
			map_height,
			columns,
			listings_per_page,
			show_pagination,
			orderby,
			order,
			logged_in_user_only,
			map_zoom_level
		} = attributes;

		return (
			<Fragment>
				<BlockControls>
					<Toolbar>
						<ToolbarButton isPressed={view === 'grid'} icon={ grid } label={ __( 'Grid View', 'directorist' ) } onClick={ () => setAttributes( { view: 'grid' } ) } />
						<ToolbarButton isPressed={view === 'list'} icon={ list } label={ __( 'List View', 'directorist' ) } onClick={ () => setAttributes( { view: 'list' } ) } />
						<ToolbarButton isPressed={view === 'map'} icon={ mapMarker } label={ __( 'Map View', 'directorist' ) } onClick={ () => setAttributes( { view: 'map' } ) } />
					</Toolbar>
				</BlockControls>
				
				<InspectorControls>
					<PanelBody title={ __( 'General', 'directorist' ) } initialOpen={ true }>
						<SelectControl
							label={ __( 'View As', 'directorist' ) }
							labelPosition='side'
							value={ view }
							options={ [
								{ label: __( 'Grid', 'directorist' ), value: 'grid' },
								{ label: __( 'List', 'directorist' ), value: 'list' },
								{ label: __( 'Map', 'directorist' ), value: 'map' },
							] }
							onChange={ newState => setAttributes( { view: newState } ) }
							className='directorist-gb-fixed-control'
						/>
						{ view === 'grid' ? <SelectControl
							label={ __( 'Columns', 'directorist' ) }
							labelPosition='side'
							value={ columns }
							options={ [
								{ label: __( '1 Column', 'directorist' ), value: 1 },
								{ label: __( '2 Columns', 'directorist' ), value: 2 },
								{ label: __( '3 Columns', 'directorist' ), value: 3 },
								{ label: __( '4 Columns', 'directorist' ), value: 4 },
								{ label: __( '6 Columns', 'directorist' ), value: 6 },
							] }
							onChange={ newState => setAttributes( { columns: newState } ) }
							className='directorist-gb-fixed-control'
						/> : '' }
						<TextControl
							label={ __( 'Number Of Listing', 'directorist' ) }
							type='number'
							value={ listings_per_page }
							onChange={ newState => setAttributes( { listings_per_page: newState } ) }
							className='directorist-gb-fixed-control'
						/>
						<SelectControl
							label={ __( 'Order By', 'directorist' ) }
							labelPosition='side'
							value={ orderby }
							options={ [
								{ label: __( 'Title', 'directorist' ), value: 'title' },
								{ label: __( 'Date', 'directorist' ), value: 'date' },
								{ label: __( 'Random', 'directorist' ), value: 'rand' },
								{ label: __( 'Price', 'directorist' ), value: 'price' },
							] }
							onChange={ newState => setAttributes( { orderby: newState } ) }
							className='directorist-gb-fixed-control'
						/>
						<SelectControl
							label={ __( 'Order', 'directorist' ) }
							labelPosition='side'
							value={ order }
							options={ [
								{ label: __( 'ASC', 'directorist' ), value: 'asc' },
								{ label: __( 'DESC', 'directorist' ), value: 'desc' },
							] }
							onChange={ newState => setAttributes( { order: newState } ) }
							className='directorist-gb-fixed-control'
						/>
						<ToggleControl
							label={ __( 'Show Pagination?', 'directorist' ) }
							checked={ show_pagination }
							onChange={ newState => setAttributes( { show_pagination: newState } ) }
						/>
						<ToggleControl
							label={ __( 'Show Header?', 'directorist' ) }
							checked={ header }
							onChange={ newState => setAttributes( { header: newState } ) }
						/>
						{ header ? <TextControl
							label={ __( 'Header Title', 'directorist' ) }
							type='text'
							value={ header_title }
							onChange={ newState => setAttributes( { header_title: newState } ) }
						/> : setAttributes( { header_title: '' } ) }
						<ToggleControl
							label={ __( 'Logged In User Only?', 'directorist' ) }
							checked={ logged_in_user_only }
							onChange={ newState => setAttributes( { logged_in_user_only: newState } ) }
						/>
						{ view === 'map' ? <TextControl
							label={ __( 'Map Height', 'directorist' ) }
							type='number'
							value={ map_height }
							help={ __( 'Applicable for map view only', 'directorist' ) }
							onChange={ newState => setAttributes( { map_height: newState } ) }
							className={`directorist-gb-fixed-control ${view !== 'map' ? 'hidden' : ''}`}
						/> : '' }
						{ view === 'map' ? <TextControl
							label={ __( 'Map Zoom Level', 'directorist' ) }
							help={ __( 'Applicable for map view only', 'directorist' ) }
							type='number'
							value={ map_zoom_level }
							onChange={ newState => setAttributes( { map_zoom_level: newState } ) }
							className='directorist-gb-fixed-control'
						/> : '' }
					</PanelBody>
				</InspectorControls>

				<div { ...useBlockProps() }>
					<ServerSideRender
						block='directorist/tag'
						attributes={ attributes }
					/>
				</div>
			</Fragment>
		);
	}
} );
