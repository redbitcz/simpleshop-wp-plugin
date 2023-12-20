<?php
/**
 * @package   Redbit\SimpleShop\WpPlugin
 * @license   MIT
 * @copyright 2016-2023 Redbit s.r.o.
 * @author    Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use WP_Post;

class Group {

	public $id = '';
	public $name = '';

	public function __construct( $id = '' ) {
		if ( $id ) {
			$this->id = $id;
			$this->get_group();
		}
	}

	/**
	 * Get all groups (custom post type)
	 * @return array
	 */
	public function get_groups() {
		$args = [
			'post_type'      => 'ssc_group',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		];

		$groups = [];

		$posts = get_posts( $args );

		foreach ( $posts as $item ) {
			$groups[ $item->ID ] = $item->post_title;
		}

		return $groups;
	}

	/**
	 * Get a single group
	 * @return bool
	 */
	public function get_group() {
		$group = get_post( $this->id );
		if ( $group ) {
			// Set the group details

			$this->name = $group->post_title;
		}

		return (bool) $group;
	}

	/**
	 * Check if group exists
	 * @return array|null|WP_Post
	 */
	public function group_exists() {
		return get_post( $this->id );
	}

	/**
	 * Get groups ids the user belongs to
	 *
	 * @param string $user_id
	 *
	 * @return array
	 */
	public function get_user_groups( $user_id = '' ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return get_user_meta( $user_id, '_ssc_user_groups', true ) ?: [];
	}

	/**
	 * Add user to a group
	 *
	 * @param $user_id
	 */
	public function add_user_to_group( $user_id, string $valid_from ) {
		$groups = $this->get_user_groups( $user_id );

		if (in_array($this->id, $groups)) {
			return;
		}

		$groups[] = $this->id;
		update_user_meta( $user_id, '_ssc_user_groups', $groups );

		// Set the date of user registration to the group
		$membership = new Membership( $user_id );

		if (!$membership->get_subscription_date($this->id)) {
			$membership->set_subscription_date( $this->id, $valid_from ?: null );
		}
	}

	/**
	 * Check if user is a member of a group
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function user_is_member_of_group( $user_id ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$groups = $this->get_user_groups( $user_id );

		return in_array( $this->id, $groups );
	}

	public function get_users() {
		$args = [
			'number'     => - 1,
			'meta_query' => [
				[
					'key'     => '_ssc_user_groups',
					'compare' => 'EXISTS',
				],
			],
		];

		return array_filter( get_users( $args ), function ( $item ) {
			return $this->user_is_member_of_group( $item->ID );
		} );
	}
}
