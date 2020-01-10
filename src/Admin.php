<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use VyfakturujAPIException;

class Admin {
	const PRODUCTS_CACHE_TTL = 3600 * 24;
	const PRODUCTS_CACHE_FIELD = '__cache_timestamp';

	/**
	 * @var Plugin
	 */
	private $loader;
	/**
	 * @var string
	 */
	private $pluginDirUrl;

	/**
	 * @param Plugin $loader
	 */
	public function __construct( Plugin $loader ) {
		$this->loader = $loader;

		$this->pluginDirUrl = plugin_dir_url( $loader->get_plugin_main_file() );

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_filter( 'manage_edit-ssc_group_columns', [ $this, 'ssc_group_columns' ] );
		add_action( 'manage_ssc_group_posts_custom_column', [ $this, 'ssc_group_column_content' ], 10, 2 );
		add_action( 'init', [ $this, 'register_groups_cpt' ] );
		add_action( 'init', [ $this, 'tiny_mce_new_buttons' ] );
		add_filter( 'page_row_actions', [ $this, 'remove_quick_edit' ], 10, 2 );
		add_action( 'wp_head', [ $this, 'publishing_actions' ] );
		add_action( 'admin_head', [ $this, 'publishing_actions' ] );
		add_action( 'wp_ajax_load_simple_shop_products', [ $this, 'wp_ajax_load_simple_shop_products' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Get products from SimpleShop via API
	 */
	public function wp_ajax_load_simple_shop_products() {
		$this->update_simpleshop_products_cache();
		$products = $this->get_simpleshop_products();
		wp_send_json( $products );
	}

	/**
	 * Return products. If you need force refresh products from API, call `update_simpleshop_products_cache()` before
	 *
	 * @return array
	 * @throws VyfakturujAPIException
	 */
	public function get_simpleshop_products() {
		$products = $this->get_simpleshop_products_cache();

		if ( $products === null ) {
			$this->update_simpleshop_products_cache();
			$products = $this->get_simpleshop_products_cache();
		}

		return $products;
	}

	/**
	 * Returns current and valid products from cache or null if valid cache unavailable
	 *
	 * @return array|null
	 */
	protected function get_simpleshop_products_cache() {
		$cache    = get_option( 'ssc_cache_products', [] );
		$cacheKey = $this->loader->get_cache_user_key();

		// Check if cache is exists & is valid
		$cachedTime = isset( $cache[ $cacheKey ][ self::PRODUCTS_CACHE_FIELD ] ) ? (int) $cache[ $cacheKey ][ self::PRODUCTS_CACHE_FIELD ] : 0;
		$age        = time() - $cachedTime;

		if ( $age < self::PRODUCTS_CACHE_TTL ) {
			$products = $cache[ $cacheKey ];
			unset( $products[ self::PRODUCTS_CACHE_FIELD ] );

			return $products;
		}

		return null;
	}

	/**
	 * Update Products cache from Vyfakturuj API
	 *
	 * @throws VyfakturujAPIException
	 */
	public function update_simpleshop_products_cache() {
		$products = $this->load_simpleshop_products();

		$cacheKey   = $this->loader->get_cache_user_key();
		$cachedTime = time();

		$cache = [
			$cacheKey => array_merge(
				$products,
				[ self::PRODUCTS_CACHE_FIELD => $cachedTime ]
			)
		];

		update_option( 'ssc_cache_products', $cache );
	}


	/**
	 * Load products from Vyfakturuj API. Don't call method directly, use `get_simpleshop_products()` to use cache
	 *
	 * @return array
	 * @throws VyfakturujAPIException
	 */
	protected function load_simpleshop_products() {
		$values = [];
		if ( $this->loader->has_credentials() ) {
			$vyfakturuj_api = $this->loader->get_api_client();
			$ret            = $vyfakturuj_api->getProducts();

			if ( $ret ) {
				foreach ( $ret as $product ) {
					$values[ $product['code'] ] = $product['name'];
				}
			}
		}

		return $values;
	}

	/**
	 * Remove quick edit from groups
	 *
	 * @param $actions
	 * @param $post
	 *
	 * @return mixed
	 */
	public function remove_quick_edit( $actions, $post ) {
		if ( $post->post_type == 'ssc_group' ) {

			unset( $actions['inline hide-if-no-js'] );
		}

		return $actions;
	}

	/**
	 * Hide publishing actions in group detail
	 */
	public function publishing_actions() {
		$mg_post_type = 'ssc_group';
		global $post;
		if ( $post && $post->post_type == $mg_post_type ) {
			echo '<style type="text/css">
            .misc-pub-section.misc-pub-visibility,
            .misc-pub-section.curtime
            {
                display:none;
            }
            </style>';
		} ?>

        <!-- SSC TinyMCE Shortcode Plugin -->
        <script type='text/javascript'>
            var sscContentGroups = [];
            sscContentGroups.push({
                text: 'Vyberte skupinu',
                value: ''
            });
			<?php
			$group = new Group();
			$groups = $group->get_groups();
			foreach ($groups as $key => $group) { ?>
            sscContentGroups.push({
                text: '<?php echo $group; ?>',
                value: '<?php echo $key; ?>'
            });
			<?php }  ?>
        </script>

		<?php
	}

	/**
	 * Add a new TinyMCE button
	 */
	public function tiny_mce_new_buttons() {
		add_filter( 'mce_external_plugins', [ $this, 'tiny_mce_add_buttons' ] );
		add_filter( 'mce_buttons', [ $this, 'tiny_mce_register_buttons' ] );
	}

	/**
	 * Add the button files
	 *
	 * @param $plugins
	 *
	 * @return mixed
	 */
	public function tiny_mce_add_buttons( $plugins ) {
		$plugins['ssctinymceplugin'] = $this->pluginDirUrl . 'js/tiny-mce/tiny-mce.js';

		return $plugins;
	}

	/**
	 * Register the new TinyMCE Button
	 *
	 * @param $buttons
	 *
	 * @return mixed
	 */
	public function tiny_mce_register_buttons( $buttons ) {
		$newBtns = [
			'sscaddformbutton',
			'ssccontentbutton',
		];
		$buttons = array_merge( $buttons, $newBtns );

		return $buttons;
	}

	/**
	 * Register a ssc_groups post type.
	 */
	public function register_groups_cpt() {
		$labels = [
			'name'               => __( 'Member sections', 'simpleshop-cz' ),
			'singular_name'      => __( 'Group', 'simpleshop-cz' ),
			'menu_name'          => __( 'Member sections', 'simpleshop-cz' ),
			'name_admin_bar'     => __( 'Member sections', 'simpleshop-cz' ),
			'add_new'            => __( 'Add group', 'simpleshop-cz' ),
			'add_new_item'       => __( 'Add new group', 'simpleshop-cz' ),
			'new_item'           => __( 'Add new group', 'simpleshop-cz' ),
			'edit_item'          => __( 'Edit group', 'simpleshop-cz' ),
			'view_item'          => __( 'Show group', 'simpleshop-cz' ),
			'all_items'          => __( 'Member sections', 'simpleshop-cz' ),
			'search_items'       => __( 'Find groups', 'simpleshop-cz' ),
			'parent_item_colon'  => __( 'Parent group:', 'simpleshop-cz' ),
			'not_found'          => __( 'No Groups found', 'simpleshop-cz' ),
			'not_found_in_trash' => __( 'No Groups is a Trash', 'simpleshop-cz' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'simple_shop_settings',
			'query_var'          => true,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => true,
			'menu_position'      => null,
			'supports'           => [ 'title' ],
		];

		register_post_type( 'ssc_group', $args );
	}

	/**
	 * Register a custom menu page.
	 */
	public function add_settings_page() {
		add_menu_page(
			__( 'SimpleShop', 'simpleshop-cz' ),
			__( 'SimpleShop', 'simpleshop-cz' ),
			'manage_options',
			'simple_shop_settings',
			[ $this, 'render_settings_page' ],
			$this->pluginDirUrl . '/img/white_logo.png',
			99
		);
	}

	/**
	 * Add custom columns to admin groups listing
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function ssc_group_columns( $columns ) {
		$columns['ssc_id'] = 'SSC ID';

		return $columns;
	}

	/**
	 * Add content to custom columns in groups listing
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function ssc_group_column_content( $column, $post_id ) {
		global $post;

		switch ( $column ) {
			case 'ssc_id' :
				echo $post->ID;
				break;
		}
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style( 'ssc', $this->pluginDirUrl . 'css/ssc.css' );
		wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui' );
	}
}
