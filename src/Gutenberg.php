<?php

namespace Redbit\SimpleShop\WpPlugin;

class Gutenberg {
	/**
	 * @var Admin
	 */
	private $admin;

	public function __construct( Admin $admin ) {
		add_action( 'init', array( $this, 'load_block_assets' ) );
		add_action( 'admin_init', array( $this, 'load_products' ) );
		$this->admin = $admin;
	}

	function load_products() {
		if ( ! get_option( 'simpleshop_products' ) ) {
			update_option( 'simpleshop_products', $this->admin->get_simpleshop_products() );

		}
	}

	function load_block_assets() { // phpcs:ignore

		// Register block styles for both frontend + backend.
		wp_register_style(
			'simpleshop-gutenberg-style-css', // Handle.
			SIMPLESHOP_PLUGIN_URL . 'js/gutenberg/blocks.style.build.css', // Block style CSS.
			array( 'wp-editor' ), // Dependency to include the CSS after it.
			null // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: File modification time.
		);

		// Register block editor script for backend.
		wp_register_script(
			'simpleshop-gutenberg-block-js', // Handle.
			SIMPLESHOP_PLUGIN_URL . 'js/gutenberg/blocks.build.js', // Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), // Dependencies, defined above.
			null, // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
			true // Enqueue the script in the footer.
		);

		wp_localize_script(
			'simpleshop-gutenberg-block-js',
			'ssGutenbergVariables',
			[
				'products' => get_option( 'simpleshop_products', [] ),
			]
		);

		// Register block editor styles for backend.
		wp_register_style(
			'simpleshop-gutenberg-block-editor-css', // Handle.
			SIMPLESHOP_PLUGIN_URL . 'js/gutenberg/blocks.editor.build.css', // Block editor CSS.
			array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
			null // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: File modification time.
		);

		/**
		 * Register Gutenberg block on server-side.
		 * Register the block on server-side to ensure that the block
		 * scripts and styles for both frontend and backend are
		 * enqueued when the editor loads.
		 * @link https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type#enqueuing-block-scripts
		 * @since 1.16.0
		 */
		register_block_type(
			'simpleshop/simpleshop-form', array(
				// Enqueue blocks.style.build.css on both frontend & backend.
				'style'         => 'simpleshop-gutenberg-style-css',
				// Enqueue blocks.build.js in the editor only.
				'editor_script' => 'simpleshop-gutenberg-block-js',
				// Enqueue blocks.editor.build.css in the editor only.
				'editor_style'  => 'simpleshop-gutenberg-block-editor-css',
			)
		);
	}
}
