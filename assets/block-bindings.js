/**
 * Adds the editor "Attributes" field-picker UI for Pods' existing
 * `pods/bindings-field` block binding source, plus a live editor preview.
 *
 * Pods already registers the PHP-side value resolver for this source name
 * (see the Pods plugin: src/Pods/WP/Bindings.php) — that's what runs on the
 * front end. This script additionally:
 *  1. Registers the source client-side with a `getFieldsList`, so the
 *     editor's Attributes panel offers a visual picker instead of requiring
 *     hand-edited block JSON.
 *  2. Implements `getValues` so the editor canvas shows an accurate preview
 *     instead of a blank/stale placeholder. Mirrors WordPress core's own
 *     `core/post-meta` binding source (packages/editor/src/bindings/post-meta.js),
 *     reading the value straight from the post's edited meta in the `core`
 *     data store — this only works for Pods fields backed by real post meta
 *     (Pods' default "Meta" storage type); it intentionally does not attempt
 *     to resolve table-storage or relationship/file fields, since those
 *     aren't available client-side without a REST round trip.
 */
( function ( wp, data ) {
	'use strict';

	if ( ! wp?.blocks?.registerBlockBindingsSource || ! data?.sources?.length ) {
		return;
	}

	const { select } = wp.data;

	/**
	 * Resolve one binding's value for the editor preview.
	 *
	 * @param {Object} context Block context (postId, postType, …).
	 * @param {Object} args    Binding args; Pods uses `field` as the field name.
	 * @return {string} The resolved value, or a human-readable fallback.
	 */
	function getPodsFieldValue( context, args ) {
		const fieldName = args?.field;
		if ( ! fieldName ) {
			return '';
		}

		// Without post context (e.g. in a template outside The Loop) there's
		// nothing to resolve; show the field name so the binding is still
		// visibly distinguishable in the canvas.
		if ( ! context?.postId || ! context?.postType ) {
			return fieldName;
		}

		const record = select( 'core' ).getEditedEntityRecord(
			'postType',
			context.postType,
			context.postId
		);

		const value = record?.meta?.[ fieldName ];

		// Pods relationship/file/image fields aren't stored as plain scalar
		// meta, so skip anything that isn't directly renderable as text.
		if ( value && typeof value === 'object' ) {
			return fieldName;
		}

		if ( value === undefined || value === null || value === '' ) {
			return fieldName;
		}

		return String( value );
	}

	data.sources.forEach( ( source ) => {
		wp.blocks.registerBlockBindingsSource( {
			name: source.name,
			label: source.label,
			usesContext: source.usesContext,
			getFieldsList( { context } ) {
				if ( ! source.contextKey ) {
					return Array.isArray( source.fields ) ? source.fields : [];
				}
				return source.fields[ context?.[ source.contextKey ] ] || [];
			},
			getValues( { context, bindings } ) {
				const values = {};
				for ( const [ attributeName, binding ] of Object.entries( bindings ) ) {
					values[ attributeName ] = getPodsFieldValue( context, binding.args );
				}
				return values;
			},
			canUserEditValue: () => false,
		} );
	} );
} )( window.wp, window.podsBlockBindings );
