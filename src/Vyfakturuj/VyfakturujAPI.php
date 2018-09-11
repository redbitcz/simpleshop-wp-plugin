<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 * @author Ing. Martin Dostál
 */

namespace Redbit\SimpleShop\WpPlugin\Vyfakturuj;

/**
 * Rozšíření třídy \VyfakturujAPI o metody, které SSC potřebuje
 */
class VyfakturujAPI extends \VyfakturujAPI {
	public function initWPPlugin( $domain ) {
		return $this->fetchPost(
			'wpplugin/init/',
			array( 'domain' => $domain, 'plugin_version' => SSC_PLUGIN_VERSION )
		);
	}
}
