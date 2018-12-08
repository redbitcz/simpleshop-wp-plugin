<?php

namespace Redbit\SimpleShop\WpPlugin;

use LogicException;

class SimpleShop {

	/**
	 * @var Loader|null
	 */
	private static $loader;

	/**
	 * @param Loader $loader
	 */
	public static function set_loader( Loader $loader ) {
		self::$loader = $loader;
	}

	/**
	 * @throws LogicException When is plugin not yet initialized
	 */
	public static function check_ready() {
		if ( self::$loader === null ) {
			throw new LogicException( 'Plugin invalid state: Unable to use ' . __CLASS__ . ' before plugin initialization.' );
		}
	}

	/**
	 * @return string|null
	 */
	public function get_redirect_url() {
		self::check_ready();
		return self::$loader->get_settings()->ssc_get_option( 'ssc_redirect_url' );
	}

}
