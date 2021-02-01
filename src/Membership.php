<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2020 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Membership {
	private $user_id;
	public $groups = [];

	/**
	 * Membership constructor.
	 * Get user data if requested
	 *
	 * @param string $user_id
	 */
	public function __construct( $user_id = '' ) {
		if ( $user_id ) {
			$this->user_id = (int) $user_id;
			$this->get();
		}
	}

	/**
	 * Get membership data for a specific user
	 */
	public function get() {
		$ssc_groups = new Group();
		$groups     = $ssc_groups->get_user_groups( $this->user_id );

		foreach ( $groups as $group ) {
			$this->groups[ $group ] = [
				'group_id'          => $group,
				'subscription_date' => $this->get_subscription_date( $group ),
				'valid_to'          => $this->get_valid_to( $group )
			];
		}
	}

	/**
	 * Set the date until the memership is valid
	 *
	 * @param $group_id
	 * @param $valid_to
	 *
	 * @return bool|int
	 */
	public function set_valid_to( $group_id, $valid_to ) {
		if ( ! $this->user_id ) {
			return false;
		}

		return update_user_meta( $this->user_id, '_ssc_group_subscription_valid_to_' . $group_id, $valid_to );
	}

	/**
	 * Set the date of user subscription to the group
	 *
	 * @param $group_id
	 *
	 * @return bool|int
	 */
	public function set_subscription_date( $group_id ) {
		if ( ! $this->user_id ) {
			return false;
		}

		return update_user_meta( $this->user_id, '_ssc_group_subscription_date_' . $group_id, date( 'Y-m-d' ) );
	}

	/**
	 * Get group subscription date
	 *
	 * @param $group_id
	 *
	 * @return mixed
	 */
	public function get_subscription_date( $group_id ) {
		return get_user_meta( $this->user_id, '_ssc_group_subscription_date_' . $group_id, true );
	}

	/**
	 * Get the date until the subscription is valid for specific group
	 *
	 * @param $group_id
	 *
	 * @return mixed
	 */
	public function get_valid_to( $group_id ) {
		return get_user_meta( $this->user_id, '_ssc_group_subscription_valid_to_' . $group_id, true );
	}

	/**
	 * Check if the membership is valid for specific group
	 *
	 * @param $group_id
	 *
	 * @return bool
	 */
	public function is_valid_for_group( $group_id ) {
		foreach ( $this->groups as $group ) {
			if ( $group['group_id'] != $group_id ) {
				continue;
			}

			if (!empty($group['subscription_date']) && $group['subscription_date'] >= date( 'Y-m-d' )) {
				return false;
			}

			if (!empty($group['valid_to']) && $group['valid_to'] <= date( 'Y-m-d' )) {
				return false;
			}

			return true;
		}

		return false;
	}
}

