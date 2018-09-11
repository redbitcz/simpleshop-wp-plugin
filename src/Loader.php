<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Loader {
	public $secure_key = '';
	public $email = '';

	function __construct() {
		$this->require_classes();
		$this->secure_key = $this->get_secure_key();
		$this->email      = $this->get_email();
		add_action( 'tgmpa_register', array( $this, 'register_required_plugins' ) );
	}

	private function require_classes() {
		require_once __DIR__ . '/../includes/class.ssc_settings.php';
		new Admin();
		new Rest();
		new Cron();
		new Metaboxes();
		new Shortcodes();
		new Access();
	}

	public function generate_secure_key() {
		return bin2hex( random_bytes( 22 ) );
	}

	public function save_secure_key( $key ) {
		update_option( 'ssc_secure_key', $key );
	}

	protected function get_secure_key() {
		return ssc_get_option( 'ssc_api_key' );
	}

	protected function get_email() {
		return ssc_get_option( 'ssc_api_email' );
	}

	public function validate_secure_key( $key_to_validate ) {
		return $key_to_validate == sha1( $this->secure_key );
	}

	/**
	 * Register the required plugins for this plugin.
	 */
	public function register_required_plugins() {
		global $wp_version;
		if ( $wp_version < '4.7' ) {
			$plugins = array(
				array(
					'name'     => 'Wordpress Rest API',
					'slug'     => 'rest-api',
					'required' => true,
				)
			);

			$config = array(
				'id'           => 'ssc',
				'default_path' => '',
				'menu'         => 'ssc-install-plugins',
				'parent_slug'  => 'tools.php',
				'capability'   => 'edit_theme_options',
				'has_notices'  => true,
				'dismissable'  => true,
				'dismiss_msg'  => '',
				'is_automatic' => false,
				'message'      => '',
			);

			tgmpa( $plugins, $config );
		}
	}

	public function get_post_types() {
		$args = array(
			'public' => true
		);

		return get_post_types( $args );
	}
}
