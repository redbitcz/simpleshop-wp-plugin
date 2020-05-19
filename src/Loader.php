<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2020 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

/**
 * Back-compatibility of deprecated plugin loader
 * @package Redbit\SimpleShop\WpPlugin
 */
class Loader {
	public function __construct() {
		$class = SimpleShop::class;
		trigger_error(
			"Manually start of plugin is deprecated, use '$class::getInstance()' instead",
			E_USER_DEPRECATED
		);
	}

	/**
	 * @param string $method
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call( $method, array $arguments ) {
		$currentClass = self::class;
		$class        = SimpleShop::class;
		trigger_error(
			"'$currentClass' is deprecated, use '$class::getInstance()->$method()' instead",
			E_USER_DEPRECATED
		);

		return call_user_func_array( [ SimpleShop::getInstance(), $method ], $arguments );
	}
}
