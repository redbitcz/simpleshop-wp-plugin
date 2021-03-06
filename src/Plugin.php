<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2020 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use Redbit\SimpleShop\WpPlugin\Vyfakturuj\VyfakturujAPI;
use VyfakturujAPIException;

class Plugin {
	const DEFAUT_API_ENDPOINT = 'https://api.simpleshop.cz/2.0/';

	/** @var string */
	private $secure_key;
	/** @var string */
	private $email;
	/** @var Settings */
	private $settings;
	/** @var Access */
	private $access;
	/** @var Admin */
	private $admin;
	/** @var Group */
	private $group;
	/** @var string */
	private $pluginMainFile;

	/**
	 * @param string $mainFile Filename of main plugin path. Used for native WP functions
	 */
	public function __construct( $mainFile ) {
		$this->pluginMainFile = $mainFile;

		$this->init();
		$this->init_i18n();

		$this->secure_key = $this->load_api_key();
		$this->email      = $this->load_email();

		register_activation_hook( plugin_dir_path( $this->pluginMainFile ), [ $this, 'ssc_activation_hook' ] );
	}

	private function init() {
		$this->settings = new Settings( $this );
		$this->access   = new Access( $this->settings );

		$this->admin = new Admin( $this );
		$this->group = new Group();
		new Rest( $this );
		new Cron( $this );
		new Metaboxes( $this );
		new Shortcodes( $this->access, $this->settings );
		$this->init_gutenberg();
	}

	private function init_gutenberg() {
		if ( function_exists( 'register_block_type' ) === false ) {
			// Skip init Gutenberg features - Gurenberg not supported in WP
			return;
		}

		new Gutenberg( $this->admin, $this->group, $this->access, $this->pluginMainFile );
	}

	public function generate_secure_key() {
		return bin2hex( random_bytes( 22 ) );
	}

	public function save_secure_key( $key ) {
		update_option( 'ssc_secure_key', $key );
	}

	public function has_credentials() {
		return $this->email && $this->secure_key;
	}

	protected function load_email() {
		return $this->settings->ssc_get_option( 'ssc_api_email' );
	}

	/** @return string|null Cache key related to API identity, or null when unlogged */
	public function get_cache_user_key() {
		return $this->email ? md5( strtolower( $this->email ) ) : null;
	}

	public function get_api_email() {
		return $this->email;
	}

	protected function load_api_key() {
		return $this->settings->ssc_get_option( 'ssc_api_key' );
	}

	public function get_api_key() {
		return $this->secure_key;
	}

	public function validate_secure_key( $key_to_validate ) {
		return $key_to_validate == sha1( $this->secure_key );
	}

	public function get_settings() {
		return $this->settings;
	}

	public function get_access() {
		return $this->access;
	}
	
	public function get_post_types() {
		$args = [
			'public' => true
		];

		return get_post_types( $args );
	}


	public function ssc_activation_hook() {
		if ( ! function_exists( 'curl_init' ) || ! function_exists( 'random_bytes' ) ) {
			echo '<h3>' . __( 'Plugin activation failed. Please contact your provider and ask to install PHP extensions: cUrl and Mcrypt.',
					'simpleshop-cz' ) . '</h3>';

			//Adding @ before will prevent XDebug output
			@trigger_error( __( 'Plugin activation failed. Please contact your provider and ask to install PHP extensions: cUrl and Mcrypt.',
				'simpleshop-cz' ), E_USER_ERROR );
		}

		// Generate and save the secure key
		$key = $this->generate_secure_key();
		$this->save_secure_key( $key );
	}

	public function init_i18n() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain_i18n' ] );
	}

	public function load_textdomain_i18n() {
		$plugin_rel_path = str_replace(
			WP_PLUGIN_DIR . '/',
			'',
			plugin_dir_path( $this->pluginMainFile ) . 'languages/'
		);
		load_plugin_textdomain( 'simpleshop-cz', false, $plugin_rel_path );
	}

	public function get_plugin_main_file() {
		return $this->pluginMainFile;
	}

	/**
	 * @param string|null $overrideLogin
	 * @param string|null $overrideApiKey
	 *
	 * @return VyfakturujAPI
	 * @throws VyfakturujAPIException
	 */
	public function get_api_client( $overrideLogin = null, $overrideApiKey = null ) {
		$email  = $overrideLogin !== null ? $overrideLogin : $this->get_api_email();
		$apiKey = $overrideApiKey !== null ? $overrideApiKey : $this->get_api_key();

		// Cannot use `default` value of option because option can exists but empty ''
		$endpointUrl = $this->settings->ssc_get_option( 'ssc_api_endpoint_url' );
		if ( empty( $endpointUrl ) ) {
			$endpointUrl = self::DEFAUT_API_ENDPOINT;
		}

		$client = new VyfakturujAPI( $email, $apiKey, $endpointUrl );

		return $client;
	}
}
