<?php
/**
 * @package   Redbit\SimpleShop\WpPlugin
 * @license   MIT
 * @copyright 2016-2023 Redbit s.r.o.
 * @author    Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Gutenberg {
	/** @var Admin */
	private $admin;
	/** @var Group */
	private $group;
	/** @var Access */
	private $access;
	/** @var string */
	private $pluginDirUrl;
	/** @var Shortcodes */
	private $shortcodes;

	public function __construct( Admin $admin, Group $group, Access $access, $pluginMainFile, Shortcodes $shortcodes ) {
		add_action( 'init', [ $this, 'load_block_assets' ] );
		add_action( 'admin_init', [ $this, 'load_products' ] );
		add_filter( 'render_block', [ $this, 'maybe_hide_block' ], 10, 2 );

		$this->admin        = $admin;
		$this->group        = $group;
		$this->access       = $access;
		$this->pluginDirUrl = plugin_dir_url( $pluginMainFile );
		$this->shortcodes   = $shortcodes;
	}

	public function load_products() {
		return $this->admin->get_simpleshop_products();
	}

	public function load_block_assets() { // phpcs:ignore

		// Register block editor script for backend.
		wp_register_script(
			'simpleshop-gutenberg-block-js',
			$this->pluginDirUrl . 'build/ss-gutenberg.js',
			[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
			null,
			true
		);

		wp_localize_script(
			'simpleshop-gutenberg-block-js',
			'ssGutenbergVariables',
			[
				'groups' => $this->group->get_groups(),
			]
		);

		// Register block editor styles for backend.
		wp_register_style(
			'simpleshop-gutenberg-block-editor-css', // Handle.
			$this->pluginDirUrl . 'js/gutenberg/blocks.editor.build.css', // Block editor CSS.
			[ 'wp-edit-blocks' ], // Dependency to include the CSS after it.
			null // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: File modification time.
		);

		/**
		 * Register Gutenberg block on server-side.
		 * Register the block on server-side to ensure that the block
		 * scripts and styles for both frontend and backend are
		 * enqueued when the editor loads.
		 * @link  https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type#enqueuing-block-scripts
		 * @since 1.16.0
		 */
		register_block_type(
			'simpleshop/simpleshop-form',
			[
				'editor_script'   => 'simpleshop-gutenberg-block-js',
				'editor_style'    => 'simpleshop-gutenberg-block-editor-css',
				'render_callback' => [ $this, 'render_form' ],
				'attributes'      => [
					'ssFormId' => [
						'type'    => 'string',
						'default' => '(' . __( 'Choose the Product at the right panel', 'simpleshop-cz' ) . ')',
					],
				],
			]
		);

		wp_set_script_translations( 'simpleshop-gutenberg-block-js', 'simpleshop-cz' );
	}

	/**
	 * Maybe hide block from frontend based on the custom settings
	 *
	 * @param $content
	 * @param $block
	 *
	 * @return string
	 */
	public function maybe_hide_block( $content, $block ) {
		$ignore_dates = isset( $block['attrs']['simpleShopIgnoreDates'] ) && $block['attrs']['simpleShopIgnoreDates'];

		$use_dates = $ignore_dates === false;

		// Back compatibility to old method from sifgle-group
		if ( ! empty( $block['attrs']['simpleShopGroup'] ) && ! isset( $block['attrs']['simpleShopGroups'] ) ) {
			$block['attrs']['simpleShopGroups'] = [ $block['attrs']['simpleShopGroup'] ];
		}

		$args = [
			'group_ids'          => isset( $block['attrs']['simpleShopGroups'] ) ? $block['attrs']['simpleShopGroups'] : '',
			'is_member'          => isset( $block['attrs']['simpleShopIsMember'] ) ? $block['attrs']['simpleShopIsMember'] : '',
			'is_logged_in'       => isset( $block['attrs']['simpleShopIsLoggedIn'] ) ? $block['attrs']['simpleShopIsLoggedIn'] : '',
			'days_to_view'       => isset( $block['attrs']['simpleShopDaysToView'] ) ? $block['attrs']['simpleShopDaysToView'] : '',
			'specific_date_from' => ( $use_dates && isset( $block['attrs']['simpleShopSpecificDateFrom'] ) )
				? iso8601_to_datetime( $block['attrs']['simpleShopSpecificDateFrom'] )
				: '',
			'specific_date_to'   => ( $use_dates && isset( $block['attrs']['simpleShopSpecificDateTo'] ) )
				? iso8601_to_datetime( $block['attrs']['simpleShopSpecificDateTo'] )
				: '',
		];

		if ( ! $this->access->user_can_view_content( $args ) ) {
			return '';
		}

		return $content;
	}

	/**
	 * Render form from Gutenberg Block
	 *
	 * @param $attributes
	 *
	 * @return string
	 */
	public function render_form( $attributes ) {
		return $this->shortcodes->simple_shop_form( [ 'id' => $attributes['ssFormId'] ] );
	}
}
