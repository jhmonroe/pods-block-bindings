<?php
/**
 * Plugin Name:       Pods Block Bindings
 * Description:       Adds the missing editor field-picker UI for Pods' own "pods/bindings-field" Block Bindings source (Pods already resolves values server-side; this only adds the visual Attributes picker, mirroring Meta Box's UI).
 * Version:           1.0.0
 * Requires Plugins:  pods
 * Requires PHP:      7.4
 * Author:            Custom
 * License:           GPL-2.0-or-later
 * Text Domain:       pods-block-bindings
 *
 * @package PodsBlockBindings
 */

namespace PodsBlockBindings;

defined( 'ABSPATH' ) || exit;

define( 'PODS_BLOCK_BINDINGS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PODS_BLOCK_BINDINGS_URL', plugin_dir_url( __FILE__ ) );
define( 'PODS_BLOCK_BINDINGS_VER', '1.0.0' );

add_action(
	'plugins_loaded',
	static function () {
		// Pods must be active and the Block Bindings API (WP 6.5+) must exist.
		if ( ! function_exists( 'pods' ) || ! function_exists( 'pods_api' ) ) {
			add_action( 'admin_notices', __NAMESPACE__ . '\\missing_pods_notice' );
			return;
		}

		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			add_action( 'admin_notices', __NAMESPACE__ . '\\missing_wp_notice' );
			return;
		}

		require_once PODS_BLOCK_BINDINGS_DIR . 'src/Source.php';
		require_once PODS_BLOCK_BINDINGS_DIR . 'src/PostSource.php';
		require_once PODS_BLOCK_BINDINGS_DIR . 'src/Loader.php';

		new Loader();
	},
	20 // after Pods itself (Pods bootstraps on plugins_loaded priority 0/default).
);

/**
 * Admin notice when Pods is not active.
 */
function missing_pods_notice(): void {
	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Pods Block Bindings requires the Pods plugin to be installed and active.', 'pods-block-bindings' );
	echo '</p></div>';
}

/**
 * Admin notice when WordPress core is too old for Block Bindings.
 */
function missing_wp_notice(): void {
	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Pods Block Bindings requires WordPress 6.5 or later (Block Bindings API).', 'pods-block-bindings' );
	echo '</p></div>';
}
