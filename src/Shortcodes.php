<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Shortcodes {

	/**
	 * @var Access
	 */
	private $access;

	public function __construct( Access $access ) {
		add_action( 'init', [ $this, 'initialize' ] );
		$this->access = $access;
	}

	public function initialize() {
		add_shortcode( 'SimpleShop-form', [ $this, 'simple_shop_form' ] );
		add_shortcode( 'SimpleShop-content', [ $this, 'simple_shop_content' ] );
	}


	public function simple_shop_form( $atts ) {
		$productCode = $atts['id'];
		$formUrl     = 'https://form.simpleshop.cz/prj/js/SimpleShopService.js';

		$template = /** @lang TEXT */
			<<<'EOD'
<!-- www.SimpleShop.cz form-code/%1$s start -->
<script>
	(function(i, s, o, g, r, a, m) {
		i[r] = i[r] || function(){
			(i[r].q = i[r].q || []).push(arguments)
		}, i[r].l = 1 * new Date();
		a = s.createElement(o),
		m = s.getElementsByTagName(o)[0];
		a.async = 1;
		a.src = g;
		m.parentNode.insertBefore(a, m)
	})(window, document, "script", %2$s, "sss");
	sss("createForm", %3$s);
</script>
<div data-SimpleShopForm="%1$s"><div>%4$s</div></div>
<!-- www.SimpleShop.cz form-code/%1$s end -->
EOD;

		$html = sprintf( $template,
			htmlspecialchars( $productCode, ENT_QUOTES ),
			json_encode( $formUrl ),
			json_encode( $productCode ),
			__(
				'Sales form is created in a system <a href="https://www.simpleshop.cz/" title="Sell online anything you want via sales form SimpleShop">Simpleshop.cz</a>.',
				'simpleshop-cz'
			)
		);

		return $html;
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
