<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2022 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use WP_Error;

/** Handles the access for the posts / pages */
class Access {
	/** @var Settings */
	private $settings;

	/**
	 * @param  Settings  $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		$redirect = true;
		if ( apply_filters( 'ssc_redirect_on_locked_content', $redirect ) === true ) {
			add_action( 'template_redirect', [ $this, 'check_access' ] );
		}

		add_filter( 'wp_setup_nav_menu_item', [ $this, 'setup_nav_menu_item' ] );
		add_action( 'wp_head', [ $this, 'hide_menu_items' ] );
		add_action( 'init', [ $this, 'mioweb_remove_login_redirect' ] );
		add_filter( 'login_redirect', [ $this, 'login_redirect' ], 10, 3 );
		add_filter( 'pre_get_posts', [ $this, 'hide_protected_from_rss' ] );
		add_action( 'simpleshop_send_welcome_email', [ $this, 'send_welcome_email' ], 10, 2 );
	}

	/**
	 * TODO: Setup the correct redirect
	 *
	 * @param $redirect
	 * @param $request
	 * @param $user
	 *
	 * @return mixed
	 */
	public function login_redirect( $redirect, $request, $user ) {
		if ( ! $user || is_wp_error( $user ) ) {
			return $redirect;
		}

		if ( isset( $user->roles ) && is_array( $user->roles ) && in_array( 'administrator', $user->roles ) ) {
			// redirect admins to the default place
			return $redirect;
		}

		$redirect_url = $this->settings->ssc_get_option( 'ssc_redirect_url' );
		if ( $redirect_url ) {
			$redirect = remove_query_arg( [ 'redirect_to' ], $redirect_url );
		}

		return $redirect;
	}

	/**
	 * Remove the MioWeb filter that redirects the user to homepage
	 */
	public function mioweb_remove_login_redirect() {
		Helpers::ssc_remove_anonymous_object_filter( 'login_redirect', 'visualEditorPage', 'login_redirect' );
	}

	/**
	 * Check if the page is protected and the user has access to the page
	 */
	public function check_access() {
		if ( ! is_singular() ) {
			return;
		}

		// Check if current user has access to the post, if not, redirect him to defined URL or home if the URL is not set
		if ( ! $this->user_can_view_post() && ! is_home() && ! is_front_page() ) {
			$no_access_url     = remove_query_arg( [ 'redirect_to' ], $this->get_no_access_redirect_url() );
			$main_redirect_url = is_user_logged_in() ? site_url() : wp_login_url( get_permalink() );
			$url               = $no_access_url ?: $main_redirect_url;
			nocache_headers();
			wp_redirect( $url );
			exit();
		}
	}

	public function get_post_groups( $post_id = '' ) {
		global $post;

		if ( ! $post_id ) {
			$post_id = $post->ID;
		}

		return get_post_meta( $post_id, '_ssc_groups', true );
	}

	/**
	 * Check if user has permission to view the post
	 *
	 * @param  string  $post_id
	 * @param  string  $user_id
	 *
	 * @return bool|WP_Error
	 */
	public function user_can_view_post( $post_id = '', $user_id = '' ) {
		// Admins can view all posts

		if ( $this->user_is_admin() ) {
			return true;
		}

		global $post;
		if ( ! $post_id ) {
			$post_id = $post->ID;
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}


		if ( ! ( $post_id > 0 ) || ! ( $user_id >= 0 ) ) {
			return new WP_Error( '400', 'Wrong post ID or user ID' );
		}

		// Check, if the post has set date, after which it can be accessed
		if ( $date_to_access = $this->get_post_date_to_access() ) {
			if ( wp_date( 'Y-m-d H:i:s' ) < $date_to_access ) {
				// The post should not be accessed yet, not depending on group, so just return false
				return false;
			}
		}

		// Check, if the post has set date, until which it can be accessed
		if ( $date_to_access = $this->get_post_date_until_to_access() ) {
			if ( wp_date( 'Y-m-d H:i:s' ) > $date_to_access ) {
				// The post should not be accessed yet, not depending on group, so just return false
				return false;
			}
		}

		$post_groups = $this->get_post_groups( $post_id );

		if ( ! $post_groups || $post_groups == '' ) {
			return true;
		}


		foreach ( $post_groups as $post_group ) {
			$group = new Group( $post_group );
			if ( $group->user_is_member_of_group( $user_id ) ) {
				// Ok, the user is member of at least one group that has access to this post

				// The user is member of some group, check if the post has minimum days to access set
				$membership = new Membership( $user_id );

				// Check if the subscription is valid
				if ( isset( $membership->groups[ $post_group ]['valid_to'] ) && $membership->groups[ $post_group ]['valid_to'] ) {
					if ( $membership->groups[ $post_group ]['valid_to'] < date( 'Y-m-d' ) ) {
						// if the the subscription expired, just break the loop here, as the user might have multiple subscriptions
						continue;
					}
				}

				if ( $days_to_access = $this->get_post_days_to_access() ) {
					$subscription_date = $membership->groups[ $post_group ]['subscription_date'];
					// Get the date of subscription to the group
					if ( $subscription_date > wp_date( 'Y-m-d', strtotime( "now -$days_to_access days" ) ) ) {
						// if the user does not have access YET, just break the loop here, as the user might have multiple subscriptions
						continue;
					}
				}

				// Check if the user has a valid membership.
				if ( ! $membership->is_valid_for_group( $post_group ) ) {
					continue;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the user is admin
	 * This is filterable,
	 * @return mixed
	 */
	public function user_is_admin() {
		$is_admin = false;
		if ( current_user_can( 'administrator' ) || current_user_can( 'editor' ) ) {
			$is_admin = true;
		}

		return apply_filters( 'ssc_user_is_admin', $is_admin );
	}

	/**
	 * Get the date to access the post
	 *
	 * @param  string  $post_id
	 *
	 * @return mixed
	 */
	public function get_post_date_to_access( $post_id = '', $format = 'Y-m-d H:i:s' ) {
		global $post;

		if ( ! $post_id && $post ) {
			$post_id = $post->ID;
		}

		if ( ! $post_id ) {
			return false;
		}

		return wp_date($format, strtotime(get_post_meta( $post_id, SIMPLESHOP_PREFIX . 'date_to_access', true )));
	}

	/**
	 * Get the date until the access to the post is allowed
	 *
	 * @param  string  $post_id
	 *
	 * @return mixed
	 */
	public function get_post_date_until_to_access( $post_id = '', $format = 'Y-m-d H:i:s' ) {
		global $post;

		if ( ! $post_id && $post ) {
			$post_id = $post->ID;
		}

		if ( ! $post_id ) {
			return false;
		}

		return wp_date($format, strtotime(get_post_meta( $post_id, SIMPLESHOP_PREFIX . 'date_until_to_access', true )));
	}

	/**
	 * Get the number of days the user has to be subscribed to have access to the post
	 *
	 * @param  string  $post_id
	 *
	 * @return mixed
	 */
	public function get_post_days_to_access( $post_id = '' ) {
		global $post;

		if ( ! $post_id ) {
			$post_id = $post->ID;
		}

		return get_post_meta( $post_id, SIMPLESHOP_PREFIX . 'days_to_access', true );
	}

	/**
	 * Get the URL to redirect the user if he has no access
	 *
	 * @param  string  $post_id
	 *
	 * @return mixed
	 */
	public function get_no_access_redirect_url( $post_id = '' ) {
		global $post;

		if ( ! $post_id ) {
			$post_id = $post->ID;
		}

		// First check, if we should redirect the user to login form
//        if ($redirect_post_id = get_post_meta($post_id, SSC_PREFIX . 'no_access_redirect_to_login_form', true)) {
//            $actual_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
//            return wp_login_url($actual_url);
//        }

		// Next try to get the ID and return permalink
		if ( $redirect_post_id = get_post_meta( $post_id, '_ssc_no_access_redirect_post_id', true ) ) {
			return get_the_permalink( $redirect_post_id );
		}

		return get_post_meta( $post_id, '_ssc_no_access_redirect', true );
	}

	/**
	 * Check if the user is allowed to view specific content (shortcode or Gutenberg block)
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public function user_can_view_content( $args = [] ) {
		$defaults = [
			'group_ids'          => [],
			'is_member'          => '',
			'is_logged_in'       => '',
			'specific_date_from' => '',
			'specific_date_to'   => '',
			'days_to_view'       => '',
		];

		$args               = wp_parse_args( $args, $defaults );
		$group_ids          = $args['group_ids'];
		$is_member          = $args['is_member'];
		$is_logged_in       = $args['is_logged_in'];
		$specific_date_from = $args['specific_date_from'];
		$specific_date_to   = $args['specific_date_to'];
		$days_to_view       = $args['days_to_view'];


		if ( ! empty( $specific_date_from ) ) {
			// Check against the from date, this has nothing to do with groups or other settings
			if ( wp_date( 'Y-m-d H:i:s' ) < $specific_date_from ) {
				return false;
			}
		}

		if ( ! empty( $specific_date_to ) ) {
			// Fix shortcode.
			if ( wp_date( 'H:i:s', strtotime( $specific_date_to ) ) == '00:00:00' ) {
				$specific_date_to = date( 'Y-m-d', strtotime( $specific_date_to ) ) . ' 23:59:59';
			}

			// Check against the to date, this has nothing to do with groups or other settings
			if ( wp_date( 'Y-m-d H:i:s' ) > $specific_date_to ) {
				return false;
			}
		}

		// Stop if there's no group_id or is_member, and no specific date is set
//		if ( empty( $group_id ) || ( empty( $is_member ) && empty( $specific_date_from ) && empty( $specific_date_to ) ) ) {
//			return false;
//		}

		if ( $is_member === 'yes' && ! empty( $group_ids ) ) {
			// User is not logged in, so he cannot be in any group
			if ( ! is_user_logged_in() ) {
				return false;
			}

			// Scrub through the groups and check if the user is member of the group and has valid membership
			$found = false;
			foreach ( $group_ids as $group_id ) {
				$group      = new Group( $group_id );
				$membership = new Membership( get_current_user_id() );

				// If the membership is not valid, bail
				if ( ! $membership->is_valid_for_group( $group_id ) ) {
					continue;
				}

				// TODO: confirm if this is duplicate of the condition above
				if ( ! $group->user_is_member_of_group( get_current_user_id() ) ) {
					continue;
				}

				$found = true;
				break;
			}
			// The user is not member of any group, return false
			if ( ! $found ) {
				return false;
			}
		}

		// Check if the user is NOT member of any selected groups
		// Obviously only logged in users can be members of a group
		if ( $is_member === 'no' && ! empty( $group_ids ) && is_user_logged_in() ) {
			foreach ( $group_ids as $group_id ) {
				$membership = new Membership( get_current_user_id() );

				// If the membership is valid, return false
				if ( $membership->is_valid_for_group( $group_id ) ) {
					return false;
				}
			}
		}

		// Check if we should display content for logged-in or non-logged-in user
		if ( $is_logged_in == 'yes' && ! is_user_logged_in() ) {
			return false;
		} elseif ( $is_logged_in == 'no' && is_user_logged_in() ) {
			return false;
		}

		// Group check done, check if there are some days set and if is_member is yes
		// it doesn't make sense to check days condition for users who should NOT be members of a group
		if ( ! empty( $days_to_view ) && $is_member == 'yes' && ! empty( $group_ids ) ) {
			$found = false;
			foreach ( $group_ids as $group_id ) {
				$membership        = new Membership( get_current_user_id() );
				$subscription_date = $membership->groups[ $group_id ]['subscription_date'];
				// Compare against today's date
				if ( wp_date( 'Y-m-d' ) < wp_date( 'Y-m-d', strtotime( "$subscription_date + $days_to_view days" ) ) ) {
					continue;
				}
				$found = true;
				break;
			}
			if ( ! $found ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Setup the cart in menu
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public function setup_nav_menu_item( $item ) {
		if ( ! $this->user_can_view_post( $item->object_id ) ) {
			$item->classes[] = 'ssc-hide';
			$item->title     = '';
			$item->url       = '';

			return $item;
		}

		return $item;
	}

	/**
	 * Hide items in menu
	 */
	public function hide_menu_items() {
		?>
        <style type="text/css">
            .ssc-hide {
                display: none !important;
            }
        </style>
		<?php
	}

	/**
	 * Hide protected posts from RSS if requested
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function hide_protected_from_rss( $query ) {
		if ( ! $query->is_admin && $query->is_feed && $this->settings->ssc_get_option( 'ssc_hide_from_rss' ) ) {
			$meta_query = $query->get( 'meta_query' );
			if ( ! $meta_query ) {
				$meta_query = [];
			}
			$meta_query[] = [
				'key'     => '_ssc_groups',
				'compare' => 'NOT EXISTS',
			];
			$query->set( 'meta_query', $meta_query ); // id of page or post
		}

		return $query;
	}

	public function send_welcome_email( $user_id ) {
		// Get the posts that have some group assigned
		$args = [
			'posts_status'   => [ 'published', 'draft' ],
			'meta_query'     => [
				[
					'key'     => '_ssc_groups',
					'compare' => 'EXISTS',
				],
			],
			'post_type'      => 'any',
			'posts_per_page' => - 1,
		];

		$posts = get_posts( $args );

		// Get the post details
		$links = [];
		$i     = 0;

		// For each group from request
		// foreach($request->get_param('user_group') as $group){
		// Foreach each group
		$SSC_group  = new Group();
		$membership = new Membership( $user_id );

		foreach ( $SSC_group->get_user_groups( $user_id ) as $group ) {
			// Check if the subscription start date is not in the future, if so, bail
			if ( $membership->get_subscription_date( $group ) && $membership->get_subscription_date( $group ) > date( 'Y-m-d' ) ) {
				continue;
			}

			// Check if the subscription ended already, if so, bail
			if ( $membership->get_valid_to( $group ) && $membership->get_valid_to( $group ) < date( 'Y-m-d' ) ) {
				continue;
			}

			// Scrub through posts and check, if some of the posts has that group assigned
			foreach ( $posts as $post ) {
				/** @var \WP_Post $post */
				$groups = $this->get_post_groups( $post->ID );

				if ( in_array( $group, $groups ) ) {
					// Check if the post can be accessed already, if not, continue
					$specific_date  = $this->get_post_date_to_access( $post->ID );
					$days_to_access = $this->get_post_days_to_access( $post->ID );

					if ( $specific_date && wp_date( 'Y-m-d' ) < $specific_date ) {
						continue;
					}

					if ( $days_to_access && $days_to_access > 0 ) {
						continue;
					}

					// If so, get the post details and add it to the links array
					$links[ $group ][ $i ]['title'] = $post->post_title;
					$links[ $group ][ $i ]['url']   = get_permalink( $post->ID );
					$i ++;
				}
			}
		}

		$email_enable = nl2br( $this->settings->ssc_get_option( 'ssc_email_enable' ) );

		// It doesn't seem to make sense to send email without the links, so check first
		if ( ( (string) $email_enable ) != '2' ) { // pokud nemame zakazano posilat mail novym clenum
			$email_body    = nl2br( $this->settings->ssc_get_option( 'ssc_email_text' ) );
			$email_subject = nl2br( $this->settings->ssc_get_option( 'ssc_email_subject' ) );
			$pages         = '';
			$user          = get_user_by( 'ID', $user_id );

			// Check if we should generate new password
			if ( ! get_user_meta( $user_id, '_ssc_new_user', true ) ) {
				$password = '<i>' . sprintf(
						__(
							'Your current password (which you set or we sent to you in a previous email). If you have lost your password, <a href="%s">you can reset it here</a>.',
							'simpleshop-cz'
						),
						esc_attr( wp_lostpassword_url( get_bloginfo( 'url' ) ) )
					) . '</i>';
			} else {
				$password = wp_generate_password( 8, false );
				wp_set_password( $password, $user_id );
				delete_user_meta( $user_id, '_ssc_new_user' );
			}

			foreach ( $links as $group_id => $linksInGroup ) {
				$post_details = get_post( $group_id );
				$pages        .= '<div><b>' . $post_details->post_title . '</b></div>'
				                 . '<ul>';
				foreach ( $linksInGroup as $link ) {
					$pages .= '<li><a href="' . $link['url'] . '">' . $link['title'] . '</a></li>';
				}
				$pages .= '</ul>';
			}

			$replaceArray = [ // pole ktera je mozne nahradit
				'{pages}'     => $pages, // zpetna kompatibilita s v1.1
				'{mail}'      => $user->user_email, // zpetna kompatibilita s v1.1
				'{login}'     => $user->user_login,
				'{password}'  => $password,
				'{login_url}' => wp_login_url(),
			];
			$email_body   = str_replace( array_keys( $replaceArray ), array_values( $replaceArray ), $email_body );
			$headers      = [ 'Content-Type: text/html; charset=UTF-8' ];

			// Send the email
			wp_mail( $user->user_email, $email_subject, $email_body, $headers );
		}
	}
}
