<?php
/**
 * @package Redbit\SimpleShop\WpPlugin\Vyfakturuj
 * @license MIT
 * @copyright 2016-2020 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin\Vyfakturuj;

/** Rozšíření třídy \VyfakturujAPI o metody, které SSC potřebuje */
class VyfakturujAPI extends \VyfakturujAPI {
	public function initWPPlugin( $domain ) {
		return $this->fetchPost(
			'wpplugin/init/',
			[ 'domain' => $domain, 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ]
		);
	}
}
