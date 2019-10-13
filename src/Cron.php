<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Cron {
	/**
	 * @var Plugin
	 */
	private $loader;

	public function __construct(Plugin $loader) {
		$this->loader = $loader;

		if ( ! wp_next_scheduled( 'ssc_send_user_has_access_to_post_notification' ) ) {
			wp_schedule_event( time(), 'daily', 'ssc_send_user_has_access_to_post_notification' );
		}

		add_action( 'ssc_send_user_has_access_to_post_notification',
			[ $this, 'send_user_has_access_to_post_notification' ] );
	}

	public function send_user_has_access_to_post_notification() {
		// Get posts, that have set either days to view or specific date
		$args = [
			'post_type'      => 'any',
			'posts_per_page' => - 1,
			'meta_query'     => [
				'relation'                           => 'OR',
				SIMPLESHOP_PREFIX . 'days_to_access' => [
					'key'     => SIMPLESHOP_PREFIX . 'days_to_access',
					'compare' => 'EXISTS'
				],
				SIMPLESHOP_PREFIX . 'date_to_access' => [
					'key'     => SIMPLESHOP_PREFIX . 'date_to_access',
					'compare' => 'EXISTS'
				]
			]
		];

		$the_query = new \WP_Query( $args );


		if ( $the_query->have_posts() ) {

			// Get all users
			$users = get_users();

			// Get all groups to array
			$users_groups = [];
			foreach ( $users as $user ) {
				$membership                = new Membership( $user->ID );
				$users_groups[ $user->ID ] = $membership->groups;
			}

			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				global $post;

				// Check if the post has some email set, if not, continue
				$email_text = get_post_meta( $post->ID, SIMPLESHOP_PREFIX . 'email_user_can_access', true );
				if ( ! $email_text ) {
					continue;
				}

				$email_subject = get_post_meta( $post->ID, SIMPLESHOP_PREFIX . 'email_subject_user_can_access', true );

				$access = $this->loader->get_access();
				// Get post groups
				$groups = $access->get_post_groups();
				// Get days to access
				$days_to_access = $access->get_post_days_to_access();
				// Get date to access
				$date_to_access = $access->get_post_date_to_access();

				// TODO: Rewrite this to first find the groups that have access to the post, than find users for these groups.
				// That way we won't have to scrub through all the users all the time
				// Scrub through the groups and check, if the user is member of the group
				foreach ( $groups as $group ) {
					foreach ( $users_groups as $user_id => $user_groups ) {
						$send_email = false;

						// Check, if the user is member of this group
						if ( array_key_exists( $group, $user_groups ) ) {
							// If so, finally check, if we should send the email

							// First check, if today is the date when the post can be accessed
							if ( $date_to_access == date( 'Y-m-d' ) ) {
								// Cool, send email
								$send_email = true;
							} elseif ( $days_to_access ) {
								$subscribed      = $user_groups[ $group ]['subscription_date'];
								$date_to_compare = date( 'Y-m-d', strtotime( "$subscribed +$days_to_access days" ) );

								if ( date( 'Y-m-d' ) == $date_to_compare ) {
									$send_email = true;
								}
							}
						}

						if ( $send_email ) {
							// Woohoo, send the email
							$userdata = get_userdata( $user_id );
							if ( ! get_user_meta( $user_id, SIMPLESHOP_PREFIX . 'notification_email_sent_' . $post->ID,
								true ) ) {
								wp_mail( $userdata->user_email, $email_subject, $email_text );
								update_user_meta( $user_id, SIMPLESHOP_PREFIX . 'notification_email_sent_' . $post->ID, 1 );
							}
						}
					}
				}
			}
		}

		wp_reset_postdata();
	}
}
