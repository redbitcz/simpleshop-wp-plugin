<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2020 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

/*
 * Plugin Name: SimpleShop.cz
 * Plugin URI: https://www.simpleshop.cz/category/wordpress-plugin/
 * Description: Plugin implement SimpleShop.cz into WordPress
 * Author:  Redbit s.r.o.
 * Author URI: https://www.redbit.cz
 * Version: dev-master
 * Text Domain: simpleshop-cz
 * Requires at least: 4.6
 */

namespace Redbit\SimpleShop\WpPlugin;

require_once __DIR__ . '/vendor/autoload.php';

define( 'SIMPLESHOP_PLUGIN_VERSION', 'dev-master' );
define( 'SIMPLESHOP_PREFIX', '_ssc_' );

/** Start plugin */
SimpleShop::$pluginMainFile = __FILE__;
SimpleShop::getInstance();
