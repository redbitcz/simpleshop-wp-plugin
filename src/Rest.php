<?php
/**
 * @package   Redbit\SimpleShop\WpPlugin
 * @license   MIT
 * @copyright 2016-2023 Redbit s.r.o.
 * @author    Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Rest extends WP_REST_Controller {
	/** @var Plugin */
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
					'methods'             => WP_REST_Server::READABLE,
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
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				],
			]
		);
	}

	public function get_groups() {
		$ssc_group = new Group();

		return new WP_REST_Response( $ssc_group->get_groups(), 200 );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$st = $this->loader->getStopwatch()->getStopwatch();
		$st->start( 'ssc:validate_email' );
		// Check if we got all the needed params
		$params_to_validate = [ 'email' ];
		foreach ( $params_to_validate as $param ) {
			if ( ! $request->get_param( $param ) ) {
				return new WP_Error( 'required-param-missing',
					sprintf( __( 'Required parameter %s is missing', 'simpleshop-cz' ), $param ),
					[ 'status' => 500, 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ] );
			}
		}

		// Check if we got valid email
		$email = sanitize_email( $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'wrong-email-format', __( 'The email is in wrong format', 'simpleshop-cz' ),
				[ 'status' => 500, 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ] );
		}
		$st->stop( 'ssc:validate_email' );

		$st->start( 'ssc:lock' );
		Plugin::lock();
		$st->stop( 'ssc:lock' );

		// Check if user with this email exists, if not, create a new user
		if ( ! email_exists( $email ) ) {
			$se = $st->start( 'ssc:create_account' );
			$userdata = [
				'user_login' => $email,
				'user_email' => $email,
				'first_name' => sanitize_text_field( $request->get_param( 'firstname' ) ),
				'last_name'  => sanitize_text_field( $request->get_param( 'lastname' ) ),
			];

			$se->lap();
			$userdata = apply_filters( 'ssc_new_user_data', $userdata );

			$se->lap();
			$user_id = wp_insert_user( $userdata );

			$se->lap();
			do_action( 'ssc_new_user_created', $user_id );

			if ( is_wp_error( $user_id ) ) {
				$this->loader->getStopwatch()->dumpStopwatch();
				return new WP_Error( 'could-not-create-user', __( "The user couldn't be created", 'simpleshop-cz' ),
					[ 'status' => 500, 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ] );
			}
			$se->lap();
			update_user_meta( $user_id, '_ssc_new_user', 1 );
			$se->stop();
		} else {
			// Get user_by email
			$se = $st->start( 'ssc:get_account' );
			$user    = get_user_by( 'email', $email );
			$user_id = $user->ID;
			$se->stop();
		}

		$send_email = false;
		foreach ( $request->get_param( 'user_group' ) as $group ) {
			$se = $st->start( 'ssc:process_group_' . $group );
			$ssc_group = new Group( $group );

			if ( ! $ssc_group->group_exists() ) {
				$se->stop();
				continue;
			}
			// Add the user to group
			$valid_to        = $request->get_param( 'valid_to' ) ?: '';
			$valid_to_months = $request->get_param( 'valid_to_months' ) ?: '';
			$valid_from      = $request->get_param( 'valid_from' ) ?: '';

			$se->lap();
			$membership = new Membership( $user_id );
			// Check if the user is already member of the group, if so, adjust the valid to date
			if ( ! empty( $membership->groups[ $group ]['valid_to'] ) && $valid_to_months !== '' ) {
				$original_valid_to   = $membership->groups[ $group ]['valid_to'];
				$original_valid_from = $membership->groups[ $group ]['valid_from'] ?? '';
				$date                = max( $original_valid_from,
					$original_valid_to,
					$valid_from,
					wp_date( 'Y-m-d' ) );
				// Add number of months to either current date or original date in the case it's in the future
				$valid_to = wp_date( 'Y-m-d', strtotime( '+' . $valid_to_months . ' month', strtotime( $date ) ) );
			}

			$se->lap();
			// Add user to the group
			$ssc_group->add_user_to_group( $user_id, $valid_from );

			// Refresh the membership data
			$se->lap();
			$membership = new Membership( $user_id );
			// Set valid from, either from the request, or current date
			$membership->set_valid_to( $group, $valid_to );
			// Schedule the action to send out welcome email if the valid_from is in the future
			if ( $valid_from > date( 'Y-m-d' ) ) {
				wp_schedule_single_event( strtotime( sprintf( '%s 02:00:00', $valid_from ) ),
					'simpleshop_send_welcome_email',
					[ $user_id ] );
			} else {
				$send_email = true;
			}
			$se->stop();
		}

		// If we are on multisite, add the user the site
		if ( is_multisite() ) {
			add_user_to_blog( get_current_blog_id(), $user_id, 'subscriber' );
		}
		$st->start( 'ssc:lock' );
		Plugin::releaseLock();
		$st->stop( 'ssc:lock' );

		if ( $send_email ) {
			$st->start( 'ssc:send_email' );
			$this->loader->get_access()->send_welcome_email( $user_id );
			$st->stop( 'ssc:send_email' );
		}

		$this->loader->getStopwatch()->dumpStopwatch();

		return new WP_REST_Response( [ 'status' => 'success', 'plugin_version' => SIMPLESHOP_PLUGIN_VERSION ], 200 );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		return $this->loader->validate_secure_key( $request->get_param( 'hash' ) );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array<string, mixed>
	 */
	public function prepare_item_for_response( $item, $request ) {
		return [];
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 *
	 * @return array<string, mixed>
	 */
	protected function prepare_item_for_database( $request ) {
		return [];
	}

}
