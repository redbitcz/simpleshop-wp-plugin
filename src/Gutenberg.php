<?php

namespace Redbit\SimpleShop\WpPlugin;

class Gutenberg {
	/**
	 * @var Admin
	 */
	private $admin;
	/**
	 * @var Group
	 */
	private $group;
	/**
	 * @var Access
	 */
	private $access;
	/**
	 * @var string
	 */
	private $pluginDirUrl;

	public function __construct( Admin $admin, Group $group, Access $access, $pluginMainFile ) {
		add_action( 'init', [ $this, 'load_block_assets' ] );
		add_action( 'admin_init', [ $this, 'load_products' ] );
		add_filter( 'render_block', [ $this, 'maybe_hide_block' ], 10, 2 );

		$this->admin        = $admin;
		$this->group        = $group;
		$this->access       = $access;
		$this->pluginDirUrl = plugin_dir_url( $pluginMainFile );
	}

	public function load_products() {
		return $this->admin->get_simpleshop_products();
	}

	public function load_block_assets() { // phpcs:ignore

		// Register block styles for both frontend + backend.
		wp_register_style(
			'simpleshop-gutenberg-style-css', // Handle.
			$this->pluginDirUrl . 'js/gutenberg/blocks.style.build.css', // Block style CSS.
			[ 'wp-editor' ], // Dependency to include the CSS after it.
			null // Version: File modification time.
		);

		// Register block editor script for backend.
		wp_register_script(
			'simpleshop-gutenberg-block-js', // Handle.
			$this->pluginDirUrl . 'js/gutenberg/blocks.build.js',
			// Block.build.js: We register the block here. Built with Webpack.
			[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ], // Dependencies, defined above.
			null, // Version: filemtime — Gets file modification time.
			true // Enqueue the script in the footer.
		);

		wp_localize_script(
			'simpleshop-gutenberg-block-js',
			'ssGutenbergVariables',
			[
				'products' => $this->load_products(),
				'groups'   => $this->group->get_groups(),
			]
		);

		// Register block editor styles for backend.
		wp_register_style(
			'simpleshop-gutenberg-block-editor-css', // Handle.
			$this->pluginDirUrl . 'js/gutenberg/blocks.editor.build.css', // Block editor CSS.
			[ 'wp-edit-blocks' ], // Dependency to include the CSS after it.
			null // Version: File modification time.
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
			'simpleshop/simpleshop-form', [
				// Enqueue blocks.style.build.css on both frontend & backend.
				'style'           => 'simpleshop-gutenberg-style-css',
				// Enqueue blocks.build.js in the editor only.
				'editor_script'   => 'simpleshop-gutenberg-block-js',
				// Enqueue blocks.editor.build.css in the editor only.
				'editor_style'    => 'simpleshop-gutenberg-block-editor-css',
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Maybe hide block from frontend based on the custom settings
	 *
	 * @param string $content
	 * @param array $block
	 *
	 * @return string
	 */
	public function maybe_hide_block( $content, $block ) {
		$args = [
			'group_id'           => isset( $block['attrs']['simpleShopGroup'] ) ? $block['attrs']['simpleShopGroup'] : '',
			'is_member'          => isset( $block['attrs']['simpleShopIsMember'] ) ? $block['attrs']['simpleShopIsMember'] : '',
			'is_logged_in'       => isset( $block['attrs']['simpleShopIsLoggedIn'] ) ? $block['attrs']['simpleShopIsLoggedIn'] : '',
			'days_to_view'       => isset( $block['attrs']['simpleShopDaysToView'] ) ? $block['attrs']['simpleShopDaysToView'] : '',
			'specific_date_from' => isset( $block['attrs']['simpleShopSpecificDateFrom'] ) ? $block['attrs']['simpleShopSpecificDateFrom'] : '',
			'specific_date_to'   => isset( $block['attrs']['simpleShopSpecificDateTo'] ) ? $block['attrs']['simpleShopSpecificDateTo'] : '',
		];

		if ( ! $this->access->user_can_view_content( $args ) ) {
			return '';
		}


		return $content;
	}

	/**
	 * Render the Gutenberg form block
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function render_block( $args ) {
		if ( is_admin() ) {
			return '';
		}

		return sprintf( '[SimpleShop-form ID="%s"]', $args['ssFormId'] );
	}

}
