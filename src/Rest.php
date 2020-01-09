<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Rest extends \WP_REST_Controller {

	/**
	 * @var Plugin
	 */
	private $loader;

	public function __construct( Plugin $loader ) {
		$this->loader = $loader;

		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'simpleshop/v' . $version;
		register_rest_route(
			$namespace,
			'/group',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_groups' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
			]
		);

		register_rest_route(
			$namespace,
			'/add-member',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
			]
		);
	}

	public function get_groups() {
		$ssc_group = new Group();

		return new \WP_REST_Response( $ssc_group->get_groups(), 200 );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_REST_Response
	 */
	public function create_item( $request ) {
		// Check if we got all the needed params
		$params_to_validate = [ 'email' ];
		foreach ( $params_to_validate as $param ) {
			if ( ! $request->get_param( $param ) ) {
				return new \WP_Error( 'required-param-missing',
					sprintf( __( 'Required parameter %s is missing', 'simpleshop-cz' ), $param ),
					[ 'status' => 500, 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ] );
			}
		}

		// Check if we got valid email
		$email = sanitize_email( $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'wrong-email-format', __( 'The email is in wrong format', 'simpleshop-cz' ),
				[ 'status' => 500, 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ] );
		}

		// Check if user with this email exists, if not, create a new user
		$_login    = $email;
		$_password = '<a href="' . wp_lostpassword_url( get_bloginfo( 'url' ) ) . '">Změnit ho můžete zde</a>';
		if ( ! email_exists( $email ) ) {
			$_password = wp_generate_password( 8, false );

			$userdata = [
				'user_login' => $email,
				'user_email' => $email,
				'first_name' => sanitize_text_field( $request->get_param( 'firstname' ) ),
				'last_name'  => sanitize_text_field( $request->get_param( 'lastname' ) ),
				'user_pass'  => $_password,
			];

			$userdata = apply_filters( 'ssc_new_user_data', $userdata );

			$user_id = wp_insert_user( $userdata );
//            wp_new_user_notification($user_id,$userdata['user_pass']); // poslani notifikacniho e-mailu

			do_action( 'ssc_new_user_created', $user_id );

			if ( is_wp_error( $user_id ) ) {
				return new \WP_Error( 'could-not-create-user', __( "The user couldn't be created", 'simpleshop-cz' ),
					[ 'status' => 500, 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ] );
			}
		} else {
			// Get user_by email
			$user    = get_user_by( 'email', $email );
			$user_id = $user->ID;
		}

		// Check if group exists
		$user_groups = [];
		foreach ( $request->get_param( 'user_group' ) as $group ) {
			$ssc_group = new Group( $group );

			// Add the user to group
			if ( $ssc_group->group_exists() ) {
				$ssc_group->add_user_to_group( $user_id );

				// Set the membership valid_to param
				$membership = new Membership( $user_id );
				$valid_to   = $request->get_param( 'valid_to' ) ?: '';
				$membership->set_valid_to( $group, $valid_to );

				$user_groups[] = $group;
			}
		}

		// If we are on multisite, add the user the site
		if ( is_multisite() ) {
			add_user_to_blog( get_current_blog_id(), $user_id, 'subscriber' );
		}

		// Get the posts that have some group assigned
		$args = [
			'posts_status' => [ 'published', 'draft' ],
			'meta_query'   => [
				[
					'key'     => '_ssc_groups',
					'compare' => 'EXISTS',
				],
			],
			'post_type'    => 'any',
		];

		$posts = get_posts( $args );

		// Get the post details
		$links = [];
		$i     = 0;

		// For each group from request
		// foreach($request->get_param('user_group') as $group){
		// Foreach each group
		$SSC_group = new Group();
		foreach ( $SSC_group->get_user_groups( $user_id ) as $group ) {
			// Scrub through posts and check, if some of the posts has that group assigned
			foreach ( $posts as $post ) {
				/** @var \WP_Post $post */
				$access = $this->loader->get_access();
				$groups = $access->get_post_groups( $post->ID );

				if ( in_array( $group, $groups ) ) {
					// Check if the post can be accessed already, if not, continue
					$specific_date  = $access->get_post_date_to_access( $post->ID );
					$days_to_access = $access->get_post_days_to_access( $post->ID );

					if ( $specific_date && date( 'Y-m-d' ) < $specific_date ) {
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

		$email_enable = nl2br( $this->loader->get_settings()->ssc_get_option( 'ssc_email_enable' ) );

		// It doesn't seem to make sense to send email without the links, so check first
		if ( ( (string) $email_enable ) != '2' ) { // pokud nemame zakazano posilat mail novym clenum
			$email_body    = nl2br( $this->loader->get_settings()->ssc_get_option( 'ssc_email_text' ) );
			$email_subject = nl2br( $this->loader->get_settings()->ssc_get_option( 'ssc_email_subject' ) );
			$pages         = '';
			foreach ( $links as $groupid => $linksInGroup ) {
				$post_details = get_post( $groupid );
				$pages        .= '<div><b>' . $post_details->post_title . '</b></div>'
				                 . '<ul>';
				foreach ( $linksInGroup as $link ) {
					$pages .= '<li><a href="' . $link['url'] . '">' . $link['title'] . '</a></li>';
				}
				$pages .= '</ul>';
			}

			$replaceArray = [ // pole ktera je mozne nahradit
				'{pages}'     => $pages, // zpetna kompatibilita s v1.1
				'{mail}'      => $email, // zpetna kompatibilita s v1.1
				'{login}'     => $_login,
				'{password}'  => $_password,
				'{login_url}' => wp_login_url(),
			];

			$email_body   = str_replace( array_keys( $replaceArray ), array_values( $replaceArray ), $email_body );
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

			// Send the email
			wp_mail( $email, $email_subject, $email_body, $headers );
		}

		return new \WP_REST_Response( [ 'status' => 'success', 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ],
			200 );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return $this->loader->validate_secure_key( $request->get_param( 'hash' ) );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		return [];
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @param \WP_REST_Request $request Request object
	 *
	 * @return array $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		return [];
	}

}
