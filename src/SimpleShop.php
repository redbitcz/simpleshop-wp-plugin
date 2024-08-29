<?php
/**
 * @package   Redbit\SimpleShop\WpPlugin
 * @license   MIT
 * @copyright 2016-2023 Redbit s.r.o.
 * @author    Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class SimpleShop {

	/** @var string */
	public static $pluginMainFile = __DIR__ . '/../simpleshop-cz.php';

	/** @var Plugin|null */
	private static $instance;

	/**
	 * @return Plugin
	 */
	public static function getInstance(): Plugin {
		return self::$instance ?? self::$instance = new Plugin( self::$pluginMainFile );
	}
}
