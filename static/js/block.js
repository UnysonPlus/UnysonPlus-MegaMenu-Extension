/**
 * Mega Menu block (block editor / FSE).
 *
 * A dynamic, server-rendered block — it picks a nav menu and the PHP render_callback outputs it
 * through the mega walker. No build step: plain JS against the wp.* globals, ServerSideRender for
 * the editor preview.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) { return; }

	var el  = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var CFG = window._fw_mm_block || { menus: [], i18n: {} };
	var i18n = CFG.i18n || {};
	var SSR = wp.serverSideRender; // @wordpress/server-side-render global

	var options = [ { label: i18n.pick || '— Select a menu —', value: 0 } ].concat(
		( CFG.menus || [] ).map( function ( m ) { return { label: m.name, value: m.id }; } )
	);

	wp.blocks.registerBlockType( 'unysonplus/mega-menu', {
		apiVersion: 2,
		title: i18n.title || 'Mega Menu',
		description: i18n.desc || 'Display a navigation menu with Mega Menu support.',
		icon: 'menu',
		category: 'widgets',
		keywords: [ 'menu', 'nav', 'navigation', 'mega' ],
		supports: { html: false, align: [ 'wide', 'full' ] },
		attributes: { menu: { type: 'number', default: 0 } },

		edit: function ( props ) {
			var controls = el(
				wp.blockEditor.InspectorControls, {},
				el(
					wp.components.PanelBody, { title: i18n.menu || 'Menu', initialOpen: true },
					el( wp.components.SelectControl, {
						label: i18n.menu || 'Menu',
						value: props.attributes.menu,
						options: options,
						onChange: function ( v ) { props.setAttributes( { menu: parseInt( v, 10 ) || 0 } ); }
					} )
				)
			);

			var body = ( props.attributes.menu && SSR )
				? el( SSR, { block: 'unysonplus/mega-menu', attributes: props.attributes } )
				: el( 'p', { className: 'unysonplus-mega-menu-block__placeholder' }, i18n.empty || 'Choose a menu to display in the block settings.' );

			return el( Fragment, {}, controls, body );
		},

		// Dynamic block — rendered by PHP.
		save: function () { return null; }
	} );
} )( window.wp );
