<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

/*
  Plugin Name: SimpleShop.cz (WP Plugin)
  Plugin URI: https://www.simpleshop.cz
  Description: Plugin pro propojení Wordpress a SimpleShop.cz
  Author:  SimpleShop.cz
  Version: dev-master
  Author URI: https://www.simpleshop.cz
 */

namespace Redbit\SimpleShop\WpPlugin;

require_once __DIR__ . '/vendor/autoload.php';

// TODO: Remove constants
define( 'SSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSC_PLUGIN_VERSION', 'dev-master' );
define( 'SSC_PREFIX', '_ssc_' );

/**
 * Start plugin
 */
$loader = new Loader();

/**
 * TODO: Move it do Loader
 * Activation hook
 */
register_activation_hook( __FILE__, '\Redbit\SimpleShop\WpPlugin\ssc_activation_hook' );

function ssc_activation_hook() {
	if ( ! function_exists( 'curl_init' ) || ! function_exists( 'random_bytes' ) ) {
		echo '<h3>' . __( 'Aktivace se nezdařila. Kontaktuje prosím poskytovatele Vašeho hostingu a požádejte o instalaci rozšíření PHP - CURL a MCRYPT.',
				'ssc' ) . '</h3>';

		//Adding @ before will prevent XDebug output
		@trigger_error( __( 'Aktivace se nezdařila. Kontaktuje prosím poskytovatele Vašeho hostingu a požádejte o instalaci rozšíření PHP - CURL a MCRYPT.',
			'ssc' ), E_USER_ERROR );
	}


	// Generate and save the secure key
	$ssc = new Loader();
	$key = $ssc->generate_secure_key();
	$ssc->save_secure_key( $key );
}