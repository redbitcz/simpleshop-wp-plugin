<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2019 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class SimpleShop {
	/**
	 * @var string
	 */
	public static $pluginMainFile = __DIR__ . '/../simpleshop-cz.php';

	/**
	 * @var Plugin|null
	 */
	private static $instance;

	/**
	 * @return Plugin
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = self::factory();
		}

		return self::$instance;
	}

	/**
	 * @return Plugin
	 */
	protected static function factory() {
		return new Plugin( self::$pluginMainFile );
	}
}
