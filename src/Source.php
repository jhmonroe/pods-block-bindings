<?php
/**
 * Supplies the editor "Attributes" field-picker UI for Pods' own
 * `pods/bindings-field` block binding source.
 *
 * Pods 3.2+ already registers the PHP-side value resolver for this source
 * (see vendor Pods plugin: src/Pods/WP/Bindings.php -> register_block_bindings_source()),
 * wired through its Field block renderer (src/Pods/Blocks/Types/Field.php).
 * What it does NOT register is a client-side `registerBlockBindingsSource()`
 * call with a `getFieldsList`, so the editor's visual field picker never
 * shows Pods fields — bindings can currently only be set by hand-editing
 * block JSON. This plugin adds only that missing piece; it intentionally
 * does not re-register the PHP value callback to avoid duplicating/
 * conflicting with Pods' own registration.
 *
 * @package PodsBlockBindings
 */

namespace PodsBlockBindings;

defined( 'ABSPATH' ) || exit;

abstract class Source {

	/**
	 * Must match the source name Pods itself registered server-side
	 * (`pods/bindings-field`), so the editor UI we add here drives the
	 * value resolution Pods already provides.
	 */
	abstract protected function name(): string;

	abstract protected function label(): string;

	/**
	 * @return string[]
	 */
	abstract protected function contexts(): array;

	abstract protected function context_key(): string;

	/**
	 * Fields for the bindings UI, keyed by context value (post type, …).
	 *
	 * @return array<string, array>
	 */
	abstract protected function get_fields(): array;

	/**
	 * Config handed to the editor JS (localized, not sent over REST).
	 *
	 * @return array{name: string, label: string, usesContext: string[], contextKey: string, fields: array}
	 */
	public function get_editor_data(): array {
		return [
			'name'        => $this->name(),
			'label'       => $this->label(),
			'usesContext' => $this->contexts(),
			'contextKey'  => $this->context_key(),
			'fields'      => $this->get_fields(),
		];
	}

	/**
	 * Whether a Pods field is flagged to be excluded from block bindings.
	 *
	 * Uses Pods' existing "hidden" field option as the nearest equivalent of
	 * Meta Box's dedicated `hide_from_block_bindings` flag. Swap this out for
	 * a real custom field option if you add one via `pods_form_ui_field_*` hooks.
	 *
	 * @param mixed $field \Pods\Whatsit\Field or array.
	 */
	protected function is_hidden( $field ): bool {
		if ( is_object( $field ) && method_exists( $field, 'get_arg' ) ) {
			return (bool) $field->get_arg( 'hidden', false );
		}

		if ( is_array( $field ) ) {
			return ! empty( $field['options']['hidden'] ) || ! empty( $field['hidden'] );
		}

		return false;
	}

	/**
	 * Build the bindings-UI field list for one pod's fields.
	 *
	 * Args use `field` (not `id`) to match the arg name Pods' own
	 * `pods/bindings-field` value callback expects (see Field block's
	 * `render( $attributes )`, which reads `$attributes['field']`).
	 *
	 * @param iterable $fields Pods\Whatsit\Field[] or array of field arrays.
	 * @return list<array{label: string, type: string, args: array{field: string}}>
	 */
	protected function fields_to_options( iterable $fields ): array {
		$options = [];

		foreach ( $fields as $field ) {
			$name = is_object( $field ) ? $field->get_name() : ( $field['name'] ?? '' );
			if ( ! $name || $this->is_hidden( $field ) ) {
				continue;
			}

			$label = is_object( $field ) ? $field->get_label() : ( $field['label'] ?? $name );

			$options[] = [
				'label' => $label ?: $name,
				'type'  => 'string',
				'args'  => [ 'field' => $name ],
			];
		}

		usort( $options, static fn( $a, $b ) => strcasecmp( $a['label'], $b['label'] ) );

		return $options;
	}
}
