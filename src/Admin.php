<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use Redbit\SimpleShop\WpPlugin\Vyfakturuj\VyfakturujAPI;

class Admin {

	/**
	 * @var Loader
	 */
	private $loader;

	public function __construct(Loader $loader) {
		$this->loader = $loader;

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_filter( 'manage_edit-ssc_group_columns', array( $this, 'ssc_group_columns' ) );
		add_action( 'manage_ssc_group_posts_custom_column', array( $this, 'ssc_group_column_content' ), 10, 2 );
		add_action( 'init', array( $this, 'register_groups_cpt' ) );
		add_action( 'init', array( $this, 'tiny_mce_new_buttons' ) );
		add_filter( 'page_row_actions', array( $this, 'remove_quick_edit' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'publishing_actions' ) );
		add_action( 'admin_head', array( $this, 'publishing_actions' ) );
		add_action( 'wp_ajax_load_simple_shop_products', array( $this, 'wp_ajax_load_simple_shop_products' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Get products from simple shop via API
	 */
	public function wp_ajax_load_simple_shop_products() {
		$values = array();
		if ( $this->loader->has_credentials() ) {
			$vyfakturuj_api = new VyfakturujAPI( $this->loader->get_api_email(), $this->loader->get_api_key() );
			$ret            = $vyfakturuj_api->getProducts();

			if ( $ret ) {
				foreach ( $ret as $product ) {
					$values[ $product['code'] ] = $product['name'];
				}
			}
		}
		echo json_encode( $values );
		exit();
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
		add_filter( 'mce_external_plugins', array( $this, 'tiny_mce_add_buttons' ) );
		add_filter( 'mce_buttons', array( $this, 'tiny_mce_register_buttons' ) );
	}

	/**
	 * Add the button files
	 *
	 * @param $plugins
	 *
	 * @return mixed
	 */
	public function tiny_mce_add_buttons( $plugins ) {
		$plugins['ssctinymceplugin'] = SIMPLESHOP_PLUGIN_URL . 'js/tiny-mce/tiny-mce.js';

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
		$newBtns = array(
			'sscaddformbutton',
			'ssccontentbutton'
		);
		$buttons = array_merge( $buttons, $newBtns );

		return $buttons;
	}

	/**
	 * Register a ssc_groups post type.
	 */
	public function register_groups_cpt() {
		$labels = array(
			'name'               => __( 'Členské sekce', 'ssc' ),
			'singular_name'      => __( 'Skupina', 'ssc' ),
			'menu_name'          => __( 'Členské sekce', 'ssc' ),
			'name_admin_bar'     => __( 'Členské sekce', 'ssc' ),
			'add_new'            => __( 'Přidat skupinu', 'ssc' ),
			'add_new_item'       => __( 'Přidat novou skupinu', 'ssc' ),
			'new_item'           => __( 'Nová skupina', 'ssc' ),
			'edit_item'          => __( 'Upravit skupinu', 'ssc' ),
			'view_item'          => __( 'Zobrazit skupinu', 'ssc' ),
			'all_items'          => __( 'Členské sekce', 'ssc' ),
			'search_items'       => __( 'Hledat skupiny', 'ssc' ),
			'parent_item_colon'  => __( 'Nadřazená skupina:', 'ssc' ),
			'not_found'          => __( 'Nebyly nalezeny žádné skupiny.', 'ssc' ),
			'not_found_in_trash' => __( 'Žádné skupiny v koši', 'ssc' )
		);

		$args = array(
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
			'supports'           => array( 'title' )
		);

		register_post_type( 'ssc_group', $args );
	}

	/**
	 * Register a custom menu page.
	 */
	public function add_settings_page() {
		add_menu_page(
			__( 'SimpleShop', 'ssc' ), __( 'SimpleShop', 'ssc' ), 'manage_options', 'simple_shop_settings',
			array( $this, 'render_settings_page' ), SIMPLESHOP_PLUGIN_URL . '/img/white_logo.png', 99
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
		wp_enqueue_style( 'ssc', SIMPLESHOP_PLUGIN_URL . 'css/ssc.css' );
		wp_register_style( 'jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui' );
	}
}
