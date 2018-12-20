<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Shortcodes {

	public function __construct() {
		add_action( 'init', array( $this, 'initialize' ) );
	}

	public function initialize() {
		add_shortcode( 'SimpleShop-form', array( $this, 'simple_shop_form' ) );
		add_shortcode( 'SimpleShop-content', array( $this, 'simple_shop_content' ) );
	}


	public function simple_shop_form( $atts ) {
		$url = substr( $_SERVER['SERVER_NAME'],
			- 2 ) === 'lc' ? 'http://form.simpleshop.czlc' : 'https://form.simpleshop.cz';

		return '<script type="text/javascript" src="' . $url . '/iframe/js/?id=' . $atts['id'] . '"></script>';
	}

	public function simple_shop_content( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'group_id'           => '',
			'is_member'          => '',
			'days_to_view'       => '',
			'specific_date_from' => '',
			'specific_date_to'   => '',

		), $atts, 'SimpleShop-content' );

		$group_id           = $atts['group_id'];
		$is_member          = $atts['is_member'];
		$is_logged_in       = $atts['is_logged_in'];
		$specific_date_from = $atts['specific_date_from'];
		$specific_date_to   = $atts['specific_date_to'];
		$days_to_view       = $atts['days_to_view'];

		if ( ! empty( $specific_date_from ) ) {
			// Check against the from date, this has nothing to do with groups or other settings
			if ( date( 'Y-m-d' ) < $specific_date_from ) {
				return '';
			}
		}

		if ( ! empty( $specific_date_to ) ) {
			// Check against the to date, this has nothing to do with groups or other settings
			if ( date( 'Y-m-d' ) > $specific_date_to ) {
				return '';
			}
		}

		// Stop if there's no group_id or is_member, and no specific date is set
		if ( empty( $group_id ) || ( empty( $is_member ) && empty( $specific_date_from ) && empty( $specific_date_to ) ) ) {
			return '';
		}

		$group = new Group( $group_id );

		if ( $is_member == 'yes' ) {
			// Check, if the user is logged in and is member of the group, if not, bail
			if ( ! is_user_logged_in() || ! $group->user_is_member_of_group( get_current_user_id() ) ) {
				return '';
			}
		} elseif ( $is_member == 'no' ) {
			// Check, if the user is NOT a member of specific group. This includes non-logged-in users
			if ( is_user_logged_in() && $group->user_is_member_of_group( get_current_user_id() ) ) {
				return '';
			}
		} else {
			// If the is_member isn't 'yes' or 'no', the parameter is wrong, so stop here
			return '';
		}

		// Check if we should display content for logged-in or non-logged-in user
		if ($is_logged_in == 'yes' && !is_user_logged_in()) {
			return '';
		} else if ($is_logged_in == 'no' && is_user_logged_in()) {
			return '';
		}

		// Group check done, check if there are some days set and if is_member is yes
		// it doesn't make sense to check days condition for users who should NOT be members of a group
		if ( ! empty( $days_to_view ) && $is_member == 'yes' ) {
			$membership        = new Membership( get_current_user_id() );
			$subscription_date = $membership->groups[ $group_id ]['subscription_date'];
			// Compare against today's date
			if ( date( 'Y-m-d' ) < date( 'Y-m-d', strtotime( "$subscription_date + $days_to_view days" ) ) ) {
				return '';
			}
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
