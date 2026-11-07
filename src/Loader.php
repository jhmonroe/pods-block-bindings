<?php
/**
 * Enqueues the editor field-picker UI for Pods' block binding source(s).
 *
 * Does NOT call register_block_bindings_source() in PHP — Pods itself
 * already owns and registers `pods/bindings-field` server-side
 * (src/Pods/WP/Bindings.php). This loader only adds the client-side
 * `registerBlockBindingsSource()` call with a `getFieldsList`, which is
 * the piece Pods doesn't ship, so the editor's Attributes panel can offer
 * a visual picker instead of requiring hand-edited block JSON.
 *
 * @package PodsBlockBindings
 */

namespace PodsBlockBindings;

defined( 'ABSPATH' ) || exit;

class Loader {

	/** @var Source[] */
	private array $sources;

	public function __construct() {
		$this->sources = [
			new PostSource(),
			// Add TaxonomySource / UserSource / SettingSource here following the
			// same Source contract once those Pods object types need a picker too.
		];

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		// Only offer the picker if Pods' own PHP-side source is actually
		// registered (it's gated behind the "display" dynamic feature flag
		// in Pods Admin > Settings); otherwise bindings would show in the
		// UI but silently resolve to nothing on the front end.
		if ( ! $this->is_pods_source_registered( 'pods/bindings-field' ) ) {
			return;
		}

		$data = [];

		foreach ( $this->sources as $source ) {
			$item = $source->get_editor_data();
			if ( empty( $item['fields'] ) ) {
				continue;
			}
			$data[] = $item;
		}

		if ( ! $data ) {
			return;
		}

		wp_enqueue_script(
			'pods-block-bindings',
			PODS_BLOCK_BINDINGS_URL . 'assets/block-bindings.js',
			[ 'wp-blocks', 'wp-i18n' ],
			PODS_BLOCK_BINDINGS_VER,
			true
		);

		wp_localize_script(
			'pods-block-bindings',
			'podsBlockBindings',
			[ 'sources' => $data ]
		);
	}

	private function is_pods_source_registered( string $name ): bool {
		if ( ! class_exists( '\WP_Block_Bindings_Registry' ) ) {
			return false;
		}

		return \WP_Block_Bindings_Registry::get_instance()->is_registered( $name );
	}
}
