<?php
/**
 * Editor field-picker data for Pods fields attached to post types.
 *
 * @package PodsBlockBindings
 */

namespace PodsBlockBindings;

defined( 'ABSPATH' ) || exit;

class PostSource extends Source {

	protected function name(): string {
		// Matches Pods' own server-registered source name exactly.
		return 'pods/bindings-field';
	}

	protected function label(): string {
		return __( 'Pods Field', 'pods-block-bindings' );
	}

	protected function contexts(): array {
		return [ 'postId', 'postType' ];
	}

	protected function context_key(): string {
		return 'postType';
	}

	/**
	 * Fields for the bindings UI, keyed by post type.
	 *
	 * Only pods of type `post_type` are included; each pod's fields become
	 * selectable "string" binding options. Pods' own Field block renderer
	 * (bound server-side via `pods/bindings-field`) already flattens complex
	 * field types to text, so no per-type value-key fan-out is needed here.
	 *
	 * @return array<string, array>
	 */
	protected function get_fields(): array {
		$result = [];

		if ( ! function_exists( 'pods_api' ) ) {
			return $result;
		}

		$pods = pods_api()->load_pods(
			[
				'type'   => 'post_type',
				'fields' => true, // Need field definitions, not just pod config.
			]
		);

		if ( ! $pods ) {
			return $result;
		}

		foreach ( $pods as $pod ) {
			$post_type = is_object( $pod ) ? $pod->get_name() : ( $pod['name'] ?? '' );
			if ( ! $post_type ) {
				continue;
			}

			$fields = is_object( $pod ) ? $pod->get_fields() : ( $pod['fields'] ?? [] );
			if ( ! $fields ) {
				continue;
			}

			$options = $this->fields_to_options( $fields );
			if ( $options ) {
				$result[ $post_type ] = $options;
			}
		}

		return $result;
	}
}
