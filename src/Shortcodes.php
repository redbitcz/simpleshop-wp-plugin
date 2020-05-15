<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2020 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Shortcodes {

	/** @var Access */
	private $access;

	/** @var Settings */
	private $settings;

	public function __construct( Access $access, Settings $settings ) {
		add_action( 'init', [ $this, 'initialize' ] );
		$this->access   = $access;
		$this->settings = $settings;
	}

	public function initialize() {
		add_shortcode( 'SimpleShop-form', [ $this, 'simple_shop_form' ] );
		add_shortcode( 'SimpleShop-content', [ $this, 'simple_shop_content' ] );
	}


	public function simple_shop_form( $atts ) {
		$formUrl = $this->settings->ssc_get_option( 'ssc_ss_form_url' );
		$query   = http_build_query( [ 'id' => $atts['id'] ] );
		$url     = sprintf(
			"%s/iframe/js/?%s",
			empty( $formUrl ) ? 'https://form.simpleshop.cz' : $formUrl,
			$query
		);

		return '<script type="text/javascript" src="' . htmlspecialchars( $url ) . '"></script>';
	}

	public function simple_shop_content( $atts, $content = '' ) {
		$atts = shortcode_atts( [
			'group_id'           => '',
			'is_member'          => '',
			'days_to_view'       => '',
			'specific_date_from' => '',
			'specific_date_to'   => '',

		], $atts, 'SimpleShop-content' );

		$args = [
			'group_id'           => $atts['group_id'],
			'is_member'          => $atts['is_member'],
			'is_logged_in'       => $atts['is_logged_in'],
			'days_to_view'       => $atts['days_to_view'],
			'specific_date_from' => $atts['specific_date_from'],
			'specific_date_to'   => $atts['specific_date_to'],
		];

		if ( ! $this->access->user_can_view_content( $args ) ) {
			return '';
		}

		// Support shortcodes inside shortcodes

		// Fix for MioWEB
		$hook = Helpers::ssc_remove_anonymous_object_filter( 'the_content', 'visualEditorPage', 'create_content' );

		$content = apply_filters( 'the_content', $content );

		// Add the filter back if needed
		if ( $hook ) {
			add_filter( $hook[0], $hook[1], $hook[2] );
		}

		return $content;
	}
}
