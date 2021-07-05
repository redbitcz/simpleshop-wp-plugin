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
		/**
		 * @noinspection BadExpressionStatementJS
		 * @noinspection JSUnresolvedFunction
		 * @noinspection JSUnnecessarySemicolon
		 * @noinspection CommaExpressionJS
		 */
		$template =
			'<script>(function(i,s,o,g,r,a,m){i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},'
			. 'i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.src=g;'
			. 'm.parentNode.insertBefore(a,m)})(window,document,"script",%s,"sss");'
			. 'sss("createForm",%s)</script>' . PHP_EOL
			. '<div data-SimpleShopForm="%s"><div>Prodejní formulář je vytvořen v systému '
			. '<a href="https://www.simpleshop.cz/" target="_blank">SimpleShop.cz</a>.'
			. '</div></div>';

		$formUrl   = rtrim( $this->settings->ssc_get_option( 'ssc_ss_form_url', 'https://form.simpleshop.cz' ), '/' );
		$scriptUrl = $formUrl . '/prj/js/SimpleShopService.js';
		$formKey   = $atts['id'];

		return sprintf( $template,
			json_encode( $scriptUrl ),
			json_encode( $formKey ),
			htmlspecialchars( $formKey, ENT_QUOTES )
		);
	}

	public function simple_shop_content( $atts, $content = '' ) {
		$atts = shortcode_atts(
			[
				'group_id'           => '',
				'is_member'          => '',
				'is_logged_in'       => '',
				'days_to_view'       => '',
				'specific_date_from' => '',
				'specific_date_to'   => '',

			],
			$atts,
			'SimpleShop-content' );

		$args = [
			'group_ids'          => explode( ',', $atts['group_id'] ),
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
