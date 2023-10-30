<?php
/**
 * The Askell WP REST class file
 *
 * @package askell-registration
 */

namespace Askell;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_User_Query;

/**
 * The Askell WP REST class
 *
 * Handles WP REST routing for the Askell plugin, including web hooks
 *
 * @package askell-registration
 */
class WpRest {
	const REST_NAMESPACE = 'askell/v1';
	const WEBHOOK_TYPES  = array( 'customer', 'subscription' );

	/**
	 * The class constructor
	 */
	public function __construct() {
		add_action(
			'rest_api_init',
			array( $this, 'register_public_rest_routes' )
		);
		add_action(
			'rest_api_init',
			array( $this, 'register_webhook_rest_routes' )
		);
		add_action(
			'rest_api_init',
			array( $this, 'register_admin_rest_routes' )
		);
		add_action(
			'rest_api_init',
			array( $this, 'register_subscriber_rest_routes' )
		);
	}

	/**
	 * Register public WP REST routes
	 *
	 * Those are generally used by the Gutenberg block or other public-facing
	 * widgets.
	 */
	public function register_public_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/plans',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'plans_rest_get' ),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/customer',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'customer_rest_post' ),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/customer_payment_method',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array(
					$this,
					'customer_payment_method_post',
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/form_fields',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array(
					$this,
					'form_fields_json_get',
				),
			)
		);
	}

	/**
	 * Register REST routes for webhooks used by Askell
	 */
	public function register_webhook_rest_routes() {
		$permission_callback = array( $this, 'check_hmac' );

		register_rest_route(
			self::REST_NAMESPACE,
			'/webhooks/customer',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'webhooks_customer_post',
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/webhooks/subscription',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'webhooks_subscription_post',
				),
			)
		);
	}

	/**
	 * Register WP REST routes for admin functions
	 *
	 * Those are functions that are only accessible to administrative users.
	 */
	public function register_admin_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => array(
					$this,
					'settings_rest_post',
				),
			)
		);
	}

	/**
	 * Register WP REST routes only accessible to subscribers
	 */
	public function register_subscriber_rest_routes() {
		$permission_callback = array(
			$this,
			'check_user_is_logged_in',
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/my_user_info',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'user_info_rest_post',
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/my_password',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'user_password_rest_post',
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/my_account',
			array(
				'methods'             => 'DELETE',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'user_account_rest_delete',
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/my_subscriptions/(?P<id>\d+)/cancel',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'user_subscription_cancel_rest_post',
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/my_subscriptions/(?P<id>\d+)/activate',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'user_subscription_activate_rest_post',
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/my_subscriptions/(?P<id>\d+)/add',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission_callback,
				'callback'            => array(
					$this,
					'user_subscription_add_rest_post',
				),
			)
		);
	}

	/**
	 * The WP REST GET handler for plans
	 *
	 * @return array An array of all plans.
	 */
	public function plans_rest_get() {
		global $askell;

		return $askell->plans();
	}

	/**
	 * The HTTP POST request handler for registering a new user
	 *
	 * As a part of the registration process a registration token is assigned to
	 * the new user in order to finalise the process in the next step.
	 *
	 * @param WP_REST_Request $request The WP REST request.
	 *
	 * @return WP_Error|array An array containing the user ID and registration
	 *                        token or WP_Error on failure.
	 */
	public function customer_rest_post( WP_REST_Request $request ) {
		global $askell;
		global $askell_askellapi;

		$request_body = (array) json_decode( $request->get_body() );

		if ( true === is_null( $request_body ) ) {
			return new WP_Error(
				'invalid_request_body',
				'Invalid Request Body',
				array( 'status' => 400 )
			);
		}

		if ( false === (
			array_key_exists( 'password', $request_body ) &&
			array_key_exists( 'username', $request_body ) &&
			array_key_exists( 'emailAddress', $request_body ) &&
			array_key_exists( 'firstName', $request_body ) &&
			array_key_exists( 'lastName', $request_body ) &&
			array_key_exists( 'planId', $request_body ) &&
			array_key_exists( 'planReference', $request_body ) )
		) {
			return new WP_Error(
				'invalid_request_body',
				'Invalid Request Body',
				array( 'status' => 400 )
			);
		}

		$new_user_id = wp_insert_user(
			array(
				'user_pass'  => $request_body['password'],
				'user_login' => sanitize_user( $request_body['username'] ),
				'user_email' => $request_body['emailAddress'],
				'first_name' => $request_body['firstName'],
				'last_name'  => $request_body['lastName'],
				'role'       => $askell::USER_ROLE,
			)
		);

		// If there in an error in the user registration, wp_insert_user() will
		// spit out a WP_Error, which we need to cast into another one with
		// an appropriate HTTP status.
		if ( true === is_a( $new_user_id, 'WP_Error' ) ) {
			return new WP_Error(
				$new_user_id->get_error_code(),
				$new_user_id->get_error_message(),
				array( 'status' => 400 )
			);
		}

		$registration_token = base64_encode( random_bytes( 32 ) );

		update_user_meta(
			$new_user_id,
			'askell_registration_token',
			$registration_token
		);

		$user = get_user_by( 'id', $new_user_id );

		$askell_user_id = $askell_askellapi->register_user_in_askell( $user );

		if ( false === $askell_user_id ) {
			return false;
		}

		update_user_meta( $new_user_id, 'askell_customer_id', $askell_user_id );

		return array(
			'ID'                 => $user->data->ID,
			'registration_token' => $registration_token,
			'askell_id'          => $askell_user_id,
		);
	}

	/**
	 * Handle the HTTP POST request for assigning payment method to customer
	 *
	 * This is the latter step of the user registration process via the React
	 * component. The user is already assumed to be in the WP user table,
	 * so this is only used for assigning a payment method and subscription to
	 * that user, based on a registration token that is already assigned.
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 *
	 * @return WP_Error|bool
	 */
	public function customer_payment_method_post( WP_REST_Request $request ) {
		global $askell;
		global $askell_askellapi;

		$request_body = (array) json_decode( $request->get_body() );

		$user_query = new WP_User_Query(
			array(
				'count'      => 1,
				'meta_key'   => 'askell_registration_token',
				'meta_value' => $request_body['registrationToken'],
			)
		);

		$users = $user_query->get_results();

		if ( 0 === count( $users ) ) {
			return new WP_Error(
				'user_not_found',
				'User Not Found',
				array( 'status' => 404 )
			);
		}

		$user          = $users[0];
		$payment_token = $request_body['paymentToken'];
		$plan_id       = $request_body['planID'];

		// Save the user's payment token as user meta, as we may want to use it
		// later.
		update_user_meta( $user->ID, 'askell_payment_token', $payment_token );

		// Create a payment method for the user in the Askell API.
		if (
			false === $askell_askellapi->assign_payment_method_to_user_in_askell(
				$user,
				$payment_token
			)
		) {
			return new WP_Error(
				'payment_method_not_set',
				'Payment Method Not Set',
				array( 'status' => 500 )
			);
		}
		usleep( 100000 ); // 100 ms pause to prevent race conditions.

		// Assign the subscription to the user in Askell.
		if (
			false === $askell_askellapi->assign_subscription_to_user_in_askell(
				$user,
				$plan_id
			)
		) {
			return new WP_Error(
				'subscription_not_assigned',
				'Subscription Not Assigned',
				array( 'status' => 500 )
			);
		}
		usleep( 100000 ); // 100 ms pause to prevent race conditions.

		// Pull the subscription information in from the Askell API.
		if ( false === $askell->save_customer_subscriptions_to_user( $user ) ) {
			return new WP_Error(
				'subscription_not_saved_to_user',
				'Subscription Not Saved to User',
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * The WP REST GET handler for the form-fields endpoint
	 */
	public function form_fields_json_get() {
		global $askell;

		$api_key        = get_option( 'askell_api_key', '' );
		$reference      = get_option( 'askell_reference', 'wordpress_id' );
		$styles_enabled = get_option( 'askell_styles_enabled', true );
		$tos_url        = get_option( 'askell_tos_url', '' );
		$plans          = $askell->get_public_plans();

		return array(
			'api_key'        => $api_key,
			'reference'      => $reference,
			'styles_enabled' => $styles_enabled,
			'tos_url'        => $tos_url,
			'plans'          => $plans,
		);
	}

	/**
	 * Route customer.* webhook request
	 *
	 * Routes a customer.* webhook request to the appropriate function. If the
	 * webhook is not supported, the endpoint responds with 'Webhook event not
	 * supported', but a 200 status to prevent the responses from clogging our
	 * error logs.
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 *
	 * @return WP_REST_Response|WP_Error WP_REST_Response on success or if the
	 *                                   webhook is not supported. WP_Error on
	 *                                   failure.
	 */
	public function webhooks_customer_post( WP_REST_Request $request ) {
		global $askell;

		$request_body = json_decode( $request->get_body() );

		switch ( $request_body->event ) {
			case 'customer.changed':
				return $this->webhooks_customer_changed_post( $request );
		}

		return new WP_REST_Response( 'Webhook event not supported yet', 200 );
	}

	/**
	 * The webhooks post handler for subscription.* events
	 *
	 * This one is very rudamentary, as it currently does not use data from the
	 * request body. Instead, it sends a request to the Askell API each time to
	 * fetch the user's subscriptions.
	 *
	 * @todo Read the information from the request body and use that instead of
	 *       sending a separate request to the API.
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 *
	 * @return WP_REST_Response|WP_Error WP_REST_Response on success or if the
	 *                                   webhook is not supported. WP_Error on
	 *                                   failure.
	 */
	public function webhooks_subscription_post( WP_REST_Request $request ) {
		global $askell;

		$request_body = json_decode( $request->get_body() );

		$user = get_user_by( 'ID', $request_body->data->customer_reference );

		if ( ( false === $user ) || ( false === $user->exists() ) ) {
			return new WP_Error(
				'user_not_found',
				'User Not Found',
				array( 'status' => 404 )
			);
		}

		if ( false === in_array( $askell::USER_ROLE, $user->roles, true ) ) {
			return new WP_Error(
				'invalid_user_role',
				'Invalid User Role, must be ' . $askell::USER_ROLE,
				array( 'status' => 400 )
			);
		}

		if ( false === $askell->save_customer_subscriptions_to_user( $user ) ) {
			return new WP_Error(
				'subscriptions_not_updated',
				"Subscriptions not updated for user $user->ID",
				array( 'status' => 304 )
			);
		}

		return new WP_REST_Response(
			"Subscriptions updated for user $user->ID",
			200
		);
	}

	/**
	 * Handle a customer.changed webhook request
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 *
	 * @return WP_REST_Response|WP_Error WP_REST_Response on success or if the
	 *                                   webhook is not supported. WP_Error on
	 *                                   failure.
	 */
	private function webhooks_customer_changed_post( WP_REST_Request $request ) {
		global $askell;

		$request_body = json_decode( $request->get_body() );

		if (
			false === isset(
				$request_body->data->customer_reference,
				$request_body->data->first_name,
				$request_body->data->last_name,
				$request_body->data->email
			)
		) {
			return new WP_Error(
				'invalid_request_body',
				'Invalid Request Body',
				array( 'status' => 400 )
			);
		}

		$user = get_user_by( 'ID', $request_body->data->customer_reference );

		if ( ( false === $user ) || ( false === $user->exists() ) ) {
			return new WP_Error(
				'user_not_found',
				'User Not Found',
				array( 'status' => 404 )
			);
		}

		if ( false === in_array( $askell::USER_ROLE, $user->roles, true ) ) {
			return new WP_Error(
				'invalid_user_role',
				'Invalid User Role, must be ' . $askell::USER_ROLE,
				array( 'status' => 400 )
			);
		}

		$user->first_name   = $request_body->data->first_name;
		$user->last_name    = $request_body->data->last_name;
		$user->user_email   = $request_body->data->email;
		$user->display_name = "{$user->first_name} {$user->last_name}";

		$update_user = wp_update_user( $user );

		if ( false === is_int( $update_user ) ) {
			return new WP_Error(
				'user_not_udated',
				'User Not Updated',
				array( 'status' => 304 )
			);
		}

		return new WP_REST_Response( "User $user->ID updated", 200 );
	}

	/**
	 * Handle the WP REST request for updating settings from the admin view
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 */
	public function settings_rest_post( WP_REST_Request $request ) {
		global $askell;

		$request_body = (array) json_decode( $request->get_body() );

		if ( array_key_exists( 'api_key', $request_body ) ) {
			update_option(
				'askell_api_key',
				$request_body['api_key']
			);
		}

		if ( array_key_exists( 'api_secret', $request_body ) ) {
			update_option(
				'askell_api_secret',
				$request_body['api_secret']
			);
		}

		if ( array_key_exists( 'enable_css', $request_body ) ) {
			update_option(
				'askell_enable_css',
				$request_body['enable_css']
			);
		}

		if ( array_key_exists( 'customer_webhook_secret', $request_body ) ) {
			update_option(
				'askell_customer_webhook_secret',
				$request_body['customer_webhook_secret']
			);
		}

		if ( array_key_exists( 'subscription_webhook_secret', $request_body ) ) {
			update_option(
				'askell_subscription_webhook_secret',
				$request_body['subscription_webhook_secret']
			);
		}

		if ( array_key_exists( 'paywall_heading', $request_body ) ) {
			update_option(
				'askell_paywall_heading',
				$request_body['paywall_heading']
			);
		}

		if ( array_key_exists( 'paywall_text_body', $request_body ) ) {
			update_option(
				'askell_paywall_text_body',
				$request_body['paywall_text_body']
			);
		}

		if ( array_key_exists( 'register_url', $request_body ) ) {
			update_option(
				'askell_register_url',
				$request_body['register_url']
			);
		}

		if ( array_key_exists( 'tos_url', $request_body ) ) {
			update_option(
				'askell_tos_url',
				$request_body['tos_url']
			);
		}

		$askell->save_plans();

		return true;
	}

	/**
	 * Handle the WP REST POST request to update subsriber's user information
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 *
	 * @return WP_Error|bool True on success, WP_Error on failure.
	 */
	public function user_info_rest_post( WP_REST_Request $request ) {
		global $askell;

		$user         = wp_get_current_user();
		$request_body = (array) json_decode( $request->get_body() );

		if ( 0 === $user->ID ) {
			return new WP_Error(
				'no_user_logged_in',
				'No user logged in'
			);
		}

		if ( false === in_array( $askell::USER_ROLE, $user->roles, true ) ) {
			return new WP_Error(
				'user_not_a_subscriber',
				'The user is not a subscriber',
				array( 'status' => 400 )
			);
		}

		if ( array_key_exists( 'first_name', $request_body ) ) {
			$user->first_name = $request_body['first_name'];
		}

		if ( array_key_exists( 'last_name', $request_body ) ) {
			$user->last_name = $request_body['last_name'];
		}

		if ( array_key_exists( 'email', $request_body ) ) {
			if ( false === is_email( $request_body['email'] ) ) {
				return new WP_Error(
					'invalid_email_address',
					'Invalid email address',
					array( 'status' => 400 )
				);
			}
			$user->user_email = $request_body['email'];
		}

		$user->display_name = "{$user->first_name} {$user->last_name}";

		if ( false === is_int( wp_update_user( $user ) ) ) {
			return new WP_Error(
				'could_not_update_user',
				'Could not update user'
			);
		}

		return true;
	}

	/**
	 * Handle the WP REST DELETE request for a subscriber user
	 *
	 * @return WP_Error|bool True on success, WP_Error on failure.
	 */
	public function user_account_rest_delete() {
		require ABSPATH . 'wp-admin/includes/user.php';

		global $askell;

		$user = wp_get_current_user();

		if ( 0 === $user->ID ) {
			return new WP_Error(
				'no_user_logged_in',
				'No user logged in'
			);
		}

		if ( false === in_array( $askell::USER_ROLE, $user->roles, true ) ) {
			return new WP_Error(
				'user_not_a_subscriber',
				'The user is not a subscriber',
				array( 'status' => 400 )
			);
		}

		if ( false === wp_delete_user( $user->ID ) ) {
			return new WP_Error(
				'user_not_deleted',
				'The user was not deleted'
			);
		}

		return true;
	}

	/**
	 * The WP REST handler for cancelling a subscription
	 *
	 * @param WP_REST_Request $request The WP REST request.
	 *
	 * @return WP_Error|bool true on success, WP_Error on failure.
	 */
	public function user_subscription_cancel_rest_post(
		WP_REST_Request $request
	) {
		global $askell;
		global $askell_askellapi;

		$sub_id = $request['id'];
		$user   = wp_get_current_user();

		if ( false === $askell->user_has_subscription( $user, $sub_id ) ) {
			return new WP_Error(
				'user_not_signed_up',
				'You are not signed up for this plan'
			);
		}

		if ( false === $askell_askellapi->cancel_subscription_in_askell( $sub_id ) ) {
			return new WP_Error(
				'cant_cancel_plan_in_askell',
				'Unable to cancel the plan, please try reloading the page and try again'
			);
		}

		sleep( 5 );

		return true;
	}

	/**
	 * The WP REST handler for (re)activating a subscription
	 *
	 * @param WP_REST_Request $request The WP REST request.
	 *
	 * @return WP_Error|bool true on success, WP_Error on failure.
	 */
	public function user_subscription_activate_rest_post(
		WP_REST_Request $request
	) {
		global $askell;
		global $askell_askellapi;

		$sub_id = $request['id'];
		$user   = wp_get_current_user();

		if ( false === $askell->user_has_subscription( $user, $sub_id ) ) {
			return new WP_Error(
				'user_not_signed_up',
				'You are not signed up for this plan'
			);
		}

		if ( false === $askell_askellapi->activate_subscription_in_askell( $sub_id ) ) {
			return new WP_Error(
				'cant_cancel_plan_in_askell',
				'Unable to activate the plan, please try reloading the page and try again'
			);
		}

		sleep( 5 );

		return true;
	}

	/**
	 * The WP REST handler for adding a subscription plan to a user
	 *
	 * @param WP_REST_Request $request The WP REST request.
	 *
	 * @return WP_Error|bool true on success, WP_Error on failure.
	 */
	public function user_subscription_add_rest_post(
		WP_REST_Request $request
	) {
		global $askell;
		global $askell_askellapi;

		$plan_id = $request['id'];
		$user    = wp_get_current_user();

		if ( true === $askell->user_has_subscription_plan( $user, $plan_id ) ) {
			return new WP_Error(
				'user_already_has_subscription_plan',
				'You already subscribe to this plan'
			);
		}

		$plan = $askell->get_plan_by_id( $plan_id );

		if ( false === $plan ) {
			return new WP_Error(
				'plan_not_found',
				'Unable to find the plan, try freloading the page, try reloading the page and try again'
			);
		}

		if ( false === $askell_askellapi->add_subscription_to_user_in_askell(
			$user,
			$plan_id,
			$plan['reference']
		) ) {
			return new WP_Error(
				'cant_assign_plan_to_user_in_askell',
				'Unable to subscribe you to this plan, try reloading the page and try again'
			);
		}

		return true;
	}

	/**
	 * Handle the WP REST POST request to set a new password
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 *
	 * @return WP_Error|bool True on success, WP_Error on failure.
	 */
	public function user_password_rest_post( WP_REST_Request $request ) {
		global $askell;

		$user         = wp_get_current_user();
		$request_body = (array) json_decode( $request->get_body() );

		if ( 0 === $user->ID ) {
			return new WP_Error(
				'no_user_logged_in',
				'No user logged in'
			);
		}

		if ( false === in_array( $askell::USER_ROLE, $user->roles, true ) ) {
			return new WP_Error(
				'user_not_a_subscriber',
				'The user is not a subscriber',
				array( 'status' => 400 )
			);
		}

		if ( false === array_key_exists( 'password', $request_body ) ) {
			return new WP_Error(
				'password_not_set',
				'Password not set'
			);
		}

		if ( false === array_key_exists( 'password_confirm', $request_body ) ) {
			return new WP_Error(
				'password_confirm_not_set',
				'Password confirmation not set'
			);
		}

		if ( 8 > strlen( $request_body['password'] ) ) {
			return new WP_Error(
				'password_too_short',
				'The password is too short'
			);
		}

		if ( $request_body['password'] !== $request_body['password_confirm'] ) {
			return new WP_Error(
				'passwords_dont_match',
				'The passwords do not match'
			);
		}

		wp_set_password( $request_body['password'], $user->ID );

		return true;
	}

	/**
	 * Check if the current user is logged in
	 *
	 * @return bool True if the user is logged in, false if not.
	 */
	public function check_user_is_logged_in() {
		$current_user = wp_get_current_user();

		if ( 0 === $current_user->ID ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate HMAC header
	 *
	 * This is used as the permission callback for webhook requests.
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 *
	 * @return bool|WP_Error True if the HMAC header matches the body's checksum
	 *                       and webhook secret. WP_Error on error.
	 */
	private function check_hmac( WP_REST_Request $request ) {
		$request_body = json_decode( $request->get_body() );

		if ( false === isset( $request_body->event ) ) {
			return new WP_Error(
				'invalid_request_body',
				'Invalid Request Body: Event attribute missing',
				array( 'status' => 400 )
			);
		}

		$event      = $request_body->event;
		$event_type = $this->webhook_event_type( $event );
		$secret     = $this->webhook_event_type_secret( $event_type );

		if ( false === $secret ) {
			return new WP_Error(
				'unsupported_webhook_event',
				"Unsupported webhook event: $event",
				array( 'status' => 400 )
			);
		}

		$raw_body    = $request->get_body();
		$hmac_header = $request->get_header( 'Hook-HMAC' );

		if ( null === $hmac_header ) {
			return new WP_Error(
				'hmac_header_missing',
				'HMAC HTTP header missing',
				array( 'status' => 400 )
			);
		}

		$hmac = base64_encode(
			hash_hmac( 'sha512', $raw_body, $secret, true )
		);

		return ( $hmac === $hmac_header );
	}

	/**
	 * Get the event type from the `event` attribute in a webhook request
	 *
	 * @param string $event The full event indicator, such as
	 *                      `subscription.changed`.
	 *
	 * @return string The event type, such as `subscription`.
	 */
	private function webhook_event_type( string $event ) {
		return explode( '.', $event )[0];
	}

	/**
	 * Get the wehook secret for an event type
	 *
	 * @param string $event_type The event type, such as `subscription`.
	 *
	 * @return string|bool The webhook secret on success. False if it has not
	 *                     been set.
	 */
	private function webhook_event_type_secret( string $event_type ) {
		if ( false === in_array( $event_type, $this::WEBHOOK_TYPES, true ) ) {
			return false;
		}

		return get_option( "askell_{$event_type}_webhook_secret" );
	}
}
