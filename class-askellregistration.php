<?php
/**
 * The main class file
 *
 * This should only have the AskellRegistration class. If you need to add
 * another PHP class, then please add another file and include it from
 * askell-registration.php.
 *
 * @package askell-registration
 */

/**
 * The main AskellRegistration class
 *
 * @package askell-registration
 */
class AskellRegistration {
	const REST_NAMESPACE = 'askell/v1';
	const USER_ROLE      = 'subscriber';
	const WEBHOOK_TYPES  = array( 'customer', 'subscription' );
	const PLUGIN_PATH    = 'askell-registration';
	const ASSETS_VERSION = '0.1.0';

	const ADMIN_ICON = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdod'
		. 'D0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0c'
		. 'DovL3d3dy53My5vcmcvMjAwMC9zdmciPgoJPHBhdGggZmlsbD0iYmxhY2siIGQ9Im0gM'
		. 'TAuOTcsMS4xIC05LjU3MSw5LjY4IGMgLTAuMDcsMC4xIC0wLjA3LDAuMTcgMCwwLjI0I'
		. 'GwgMS4zOTUsMS40MSBjIDAuMDcsMC4xIDAuMTczLDAuMSAwLjI0MSwwIDAsMCAwLDAgM'
		. 'CwwIEwgMTEuMDksNC4yODQgYyAwLjEsLTAuMDcgMC4xOCwtMC4wNyAwLjI0LDAgMCwwI'
		. 'DAsMCAwLDAgbCAxLjQyLDEuNDM2IHYgMCBoIC0xLjEzIGMgLTAuMSwwIC0wLjE3LDAuM'
		. 'DggLTAuMTcsMC4xNyB2IDEuNDIxIGMgMCwwLjA5IDAuMSwwLjE3IDAuMTcsMC4xNyBoI'
		. 'DQuMjEgYyAwLjE5LDAgMC4zNCwtMC4xNSAwLjM0LC0wLjM0IFYgMi44NzMgYyAwLC0wL'
		. 'jA5IC0wLjEsLTAuMTcgLTAuMTcsLTAuMTcgaCAtMS40IGMgLTAuMSwwIC0wLjE3LDAuM'
		. 'DggLTAuMTcsMC4xNyB2IDEuMjM4IDAgTCAxMS40NSwxLjEgYyAtMC4xMywtMC4xMzMyI'
		. 'C0wLjM0LC0wLjEzNDcgLTAuNDgsMCAwLDEwZS00IDAsMCAwLDAgeiIgLz4KCTxwYXRoI'
		. 'GZpbGw9ImJsYWNrIiBkPSJNIDkuNDQ1LDE1Ljc1IDYuNDI5LDEyLjcyIGMgLTAuMDcsL'
		. 'TAuMSAtMC4xNzUsLTAuMSAtMC4yNCwwIDAsMCAwLDAgMCwwIGwgLTEuMzg1LDEuMzkgY'
		. 'yAtMC4wNywwLjEgLTAuMDcsMC4xOCAwLDAuMjQgbCA0LjUyMSw0LjU1IGMgMC4xMywwL'
		. 'jEzIDAuMzQ1LDAuMTMgMC40OCwwIDAsMCAwLDAgMCwwIEwgMTguNiwxMC4wNiBjIDAuM'
		. 'SwtMC4wNyAwLjEsLTAuMTc5IDAsLTAuMjQ0IEwgMTcuMjIsOC40MjEgYyAtMC4xLC0wL'
		. 'jA3IC0wLjE4LC0wLjA3IC0wLjI0LDAgMCwwIDAsMCAwLDAgTCA5LjY4NSwxNS43NSBjI'
		. 'C0wLjA3LDAuMSAtMC4xNzUsMC4xIC0wLjI0LDAgeiIgLz4KPC9zdmc+Cg==';

	/**
	 * The class constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'block_init' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'enqueue_admin_script' ) );

		add_action( 'askell_sync_cron', array( $this, 'save_plans' ) );
		add_action( 'init', array( $this, 'schedule_sync_cron' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_action(
			'wp_update_user',
			array( $this, 'push_customer_on_user_update' )
		);

		add_action(
			'delete_user',
			array( $this, 'delete_customer_on_user_delete' )
		);
	}

	/**
	 * Load the text domain for the plugin
	 *
	 * Loads the 'askell-registration' text domain for both PHP rendering and
	 * the React frontend.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'askell-registration',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		wp_set_script_translations(
			'askell-registration-askell-registration-view-script',
			'askell-registration',
			plugin_dir_path( __FILE__ ) . '/languages'
		);

		wp_set_script_translations(
			'askell-registration-askell-registration-editor-script',
			'askell-registration',
			plugin_dir_path( __FILE__ ) . '/languages'
		);
	}

	/**
	 * Initialiser for the wp-cron job used by the plugin
	 */
	public function schedule_sync_cron() {
		if ( ! wp_next_scheduled( 'askell_sync_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'askell_sync_cron' );
		}
	}

	/**
	 * Initialise the block
	 */
	public function block_init() {
		register_block_type(
			__DIR__ . '/build'
		);
	}

	/**
	 * Register the WP REST routes used by the plugin
	 */
	public function register_rest_routes() {
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
		register_rest_route(
			self::REST_NAMESPACE,
			'/webhooks/customer',
			array(
				'methods'             => 'POST',
				'permission_callback' => array(
					$this,
					'check_hmac',
				),
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
				'permission_callback' => array(
					$this,
					'check_hmac',
				),
				'callback'            => array(
					$this,
					'webhooks_subscription_post',
				),
			)
		);
	}

	/**
	 * Delete customer data from the Askell API based on user ID
	 *
	 * This is our wp_delete_user handler. An error is logged if a failure
	 * in communication with the Askell API occurs.
	 *
	 * @todo Create a wp-cron job that re-attempts the deletion after a certain
	 *       amount of time, if the deletion fails on first try.
	 *
	 * @param int $user_id The user's ID and Askell customer reference.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function delete_customer_on_user_delete( int $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		if ( false === $user ) {
			return false;
		}

		if ( false === in_array( self::USER_ROLE, $user->roles, true ) ) {
			return false;
		}

		$deletion = $this->delete_customer_in_askell_by_user_id( $user_id );
		if ( false === $deletion ) {
			error_log( "Unable to delete user $user_id via the Askell API" );
		}

		return $deletion;
	}

	/**
	 * Delete user from the Askell API based on user ID
	 *
	 * This does not perform any checks on the user.
	 *
	 * @param int $user_id The user's ID and Askell customer reference.
	 *
	 * @return bool True on success. False on failure.
	 */
	private function delete_customer_in_askell_by_user_id( int $user_id ) {
		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$api_response = wp_remote_request(
			"https://askell.is/api/customers/{$user_id}/",
			array(
				'method'  => 'DELETE',
				'headers' => array(
					'accept'        => 'application/json',
					'Authorization' => "Api-Key {$private_key}",
				),
			)
		);

		if ( 204 !== $api_response['response']['code'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Push user information to the Aksell customer endpoint
	 *
	 * This is our wp_update_user hook handler.
	 *
	 * @param int $user_id The ID of the user to push information for.
	 *
	 * @return boolean True on success. False on failure.
	 */
	public function push_customer_on_user_update( int $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		// Prevent an infinite loop from happening if the update was requested
		// by a web hook. Web hooks always use the Hook-HMAC HTTP header so they
		// can be identified that way.
		if ( isset( $_SERVER['HTTP_HOOK_HMAC'] ) ) {
			return false;
		}

		return $this->push_customer( $user );
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
	public function check_hmac( WP_REST_Request $request ) {
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
		$request_body = json_decode( $request->get_body() );

		$user = get_user_by( 'ID', $request_body->data->customer_reference );

		if ( ( false === $user ) || ( false === $user->exists() ) ) {
			return new WP_Error(
				'user_not_found',
				'User Not Found',
				array( 'status' => 404 )
			);
		}

		if ( false === in_array( self::USER_ROLE, $user->roles, true ) ) {
			return new WP_Error(
				'invalid_user_role',
				'Invalid User Role, must be ' . self::USER_ROLE,
				array( 'status' => 400 )
			);
		}

		if ( false === $this->save_customer_subscriptions_to_user( $user ) ) {
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
		$request_body = json_decode( $request->get_body() );

		switch ( $request_body->event ) {
			case 'customer.changed':
				return $this->webhooks_customer_changed_post( $request );
		}

		return new WP_REST_Response( 'Webhook event not supported yet', 200 );
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
	public function webhooks_customer_changed_post( WP_REST_Request $request ) {
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

		if ( false === in_array( self::USER_ROLE, $user->roles, true ) ) {
			return new WP_Error(
				'invalid_user_role',
				'Invalid User Role, must be ' . self::USER_ROLE,
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
			false === $this->assign_payment_method_to_user_in_askell(
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
			false === $this->assign_subscription_to_user_in_askell(
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
		if ( false === $this->save_customer_subscriptions_to_user( $user ) ) {
			return new WP_Error(
				'subscription_not_saved_to_user',
				'Subscription Not Saved to User',
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Handle the WP REST request for updating settings from the admin view
	 *
	 * @param WP_REST_Request $request The WordPress REST request.
	 */
	public function settings_rest_post( WP_REST_Request $request ) {
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

		if ( array_key_exists( 'enable_address_country', $request_body ) ) {
			update_option(
				'askell_enable_address_country',
				$request_body['enable_address_country']
			);
		}

		if ( array_key_exists( 'enable_css', $request_body ) ) {
			update_option(
				'askell_enable_css',
				$request_body['enable_css']
			);
		}

		if ( array_key_exists( 'reference', $request_body ) ) {
			update_option(
				'askell_reference',
				$request_body['reference']
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

		return true;
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
				'role'       => self::USER_ROLE,
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

		$this->register_user_in_askell( $user );

		return array(
			'ID'                 => $user->data->ID,
			'registration_token' => $registration_token,
		);
	}

	/**
	 * Set and overwrite a user's subscriptions
	 *
	 * @param WP_User $user The WP_User object representing the user.
	 * @param array   $subscriptions An array of subscription arrays.
	 *
	 * @return bool True on success. False if the user does not exist.
	 */
	public function set_subscriptions_for_user(
		WP_User $user,
		array $subscriptions
	) {
		if ( false === $user->exists() ) {
			return false;
		}

		update_user_meta(
			$user->ID,
			'askell_subscriptions',
			$subscriptions
		);

		return true;
	}

	/**
	 * Register a WordPress user as customer in the Askell API
	 *
	 * @param WP_User $user The WP_User object representing the user.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function register_user_in_askell( WP_User $user ) {
		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$endpoint_url = 'https://askell.is/api/customers/';

		$request_body = array(
			'first_name'         => $user->first_name,
			'last_name'          => $user->last_name,
			'email'              => $user->user_email,
			'customer_reference' => (string) $user->ID,
		);

		$api_response = wp_remote_post(
			$endpoint_url,
			array(
				'body'    => wp_json_encode( $request_body ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => "Api-Key $private_key",
				),
			)
		);

		if ( 201 !== $api_response['response']['code'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Assign payment method to a customer/user in the Askell API
	 *
	 * This needs to be done before a subscription is assigned to the customer.
	 *
	 * @param WP_User $user The WordPress user object for the customer.
	 * @param string  $payment_token the payment token from the
	 *                `customer_payment_method_post` call.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function assign_payment_method_to_user_in_askell(
		WP_User $user,
		string $payment_token
	) {
		if ( false === $user->exists() ) {
			return false;
		}

		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$endpoint_url = 'https://askell.is/api/customers/paymentmethod/';

		$request_body = array(
			'customer_reference' => $user->ID,
			'token'              => $payment_token,
		);

		$api_response = wp_remote_post(
			$endpoint_url,
			array(
				'body'    => wp_json_encode( $request_body ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => "Api-Key $private_key",
				),
			)
		);

		if ( 201 !== $api_response['response']['code'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Assign a subscription to customer by WP user and plan ID
	 *
	 * @param WP_User $user The WordPress user object for the customer.
	 * @param int     $plan_id The ID of the subscription plan.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function assign_subscription_to_user_in_askell(
		WP_User $user,
		int $plan_id
	) {
		if ( false === $user->exists() ) {
			return false;
		}

		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$plan = $this->get_plan_by_id( $plan_id );

		if ( false === $plan ) {
			return false;
		}

		$endpoint_url = "https://askell.is/api/customers/$user->ID/subscriptions/add/";

		$request_body = array(
			'plan'      => $plan_id,
			'reference' => $plan['reference'],
		);

		$api_response = wp_remote_post(
			$endpoint_url,
			array(
				'body'    => wp_json_encode( $request_body ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => "Api-Key $private_key",
				),
			)
		);

		if ( 201 !== $api_response['response']['code'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Add the menu page item to the wp-admin sidebar
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'Askell', 'askell-registration' ),
			__( 'Askell', 'askell-registration' ),
			'manage_options',
			'askell-registration',
			array( $this, 'render_admin_page' ),
			self::ADMIN_ICON,
			91
		);
	}

	/**
	 * Render the admin page
	 *
	 * This reads in the 'main-page' view file.
	 */
	public function render_admin_page() {
		if ( false === current_user_can( 'manage_options' ) ) {
			return false;
		}

		require __DIR__ . '/views/main-page.php';
	}

	/**
	 * Enqueue scripts and styles for the admin interface
	 */
	public function enqueue_admin_script() {
		wp_enqueue_script(
			'askell-registration-admin-view',
			plugins_url( self::PLUGIN_PATH . '/build/admin.js' ),
			array( 'wp-api' ),
			self::ASSETS_VERSION,
			false
		);

		wp_enqueue_style(
			'askell-registration-admin-style',
			plugins_url( self::PLUGIN_PATH . '/build/admin.scss.css' ),
			array(),
			self::ASSETS_VERSION,
			false
		);
	}

	/**
	 * Get the all plans that have been synced from the Askell API
	 *
	 * Gets the plans that have been synced from the Askell API. The plans are
	 * saved as a WP option and the payment_info and price_tag attributes go
	 * through a localisation process in the PHP backend.
	 *
	 * @return array An array of plans. An empty array if no plans have been
	 *               pulled from the Askell API.
	 */
	public function plans() {
		$plans = get_option( 'askell_plans', array() );
		foreach ( $plans as $k => $p ) {
			$plans[ $k ]['price_tag']    = $this->format_price_tag(
				$plans[ $k ]['currency'],
				$plans[ $k ]['amount'],
				$plans[ $k ]['interval'],
				$plans[ $k ]['interval_count'],
				$plans[ $k ]['trial_period_days']
			);
			$plans[ $k ]['payment_info'] = $this->format_payment_information(
				$plans[ $k ]['currency'],
				$plans[ $k ]['amount'],
				$plans[ $k ]['interval'],
				$plans[ $k ]['interval_count'],
				$plans[ $k ]['trial_period_days']
			);
		}

		return $plans;
	}

	/**
	 * Get the public plans
	 *
	 * This filters the values from the `plans()` function so that only the ones
	 * with the `private` attribute equalling `false`. This is used by the React
	 * component to display the plans available for new customers.
	 *
	 * @return array A filtered array of plans.
	 */
	public function get_public_plans() {
		return array_values(
			array_filter(
				$this->plans(),
				function ( $a ) {
					return ( false === $a['private'] );
				}
			)
		);
	}

	/**
	 * Get a single plan by reference
	 *
	 * @param string $reference The reference code for the plan.
	 *
	 * @return array|bool A one-dimensional array representing a single plan.
	 *                    False on failure.
	 */
	public function get_plan_by_reference( string $reference ) {
		$plans = array_values(
			array_filter(
				$this->plans(),
				function ( $a ) use ( $reference ) {
					return ( $a['reference'] === $reference );
				}
			)
		);

		if ( 0 === count( $plans ) ) {
			return false;
		}

		return $plans[0];
	}

	/**
	 * Get plan by ID
	 *
	 * @param int $id The ID of the plan.
	 *
	 * @return array|bool A one-dimensional array representing a single plan.
	 *                    False on failure.
	 */
	public function get_plan_by_id( int $id ) {
		$plans = array_values(
			array_filter(
				$this->plans(),
				function ( $a ) use ( $id ) {
					return ( $a['id'] === $id );
				}
			)
		);

		if ( 0 === count( $plans ) ) {
			return false;
		}

		return $plans[0];
	}

	/**
	 * The WP REST GET handler for the form-fields endpoint
	 */
	public function form_fields_json_get() {
		$api_key        = get_option( 'askell_api_key', '' );
		$reference      = get_option( 'askell_reference', 'wordpress_id' );
		$styles_enabled = get_option( 'askell_styles_enabled', true );
		$plans          = $this->get_public_plans();

		return array(
			'api_key'        => $api_key,
			'reference'      => $reference,
			'styles_enabled' => $styles_enabled,
			'plans'          => $plans,
		);
	}

	/**
	 * Push customer information to the Askell API
	 *
	 * @param WP_User $user The WordPress user object.
	 */
	public function push_customer( WP_User $user ) {
		if ( false === $user->exists() ) {
			return false;
		}

		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$request_body = wp_json_encode(
			array(
				'first_name' => $user->first_name,
				'last_name'  => $user->last_name,
				'email'      => $user->user_email,
			)
		);

		$api_response = wp_remote_request(
			"https://askell.is/api/customers/{$user->ID}/",
			array(
				'method'  => 'PATCH',
				'body'    => $request_body,
				'headers' => array(
					'accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'Authorization' => "Api-Key {$private_key}",
				),
			)
		);

		if ( 200 !== $api_response['response']['code'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Save customer's information to the WordPress users table
	 *
	 * @param WP_User $user The WordPress user object.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function save_customer_to_user( WP_User $user ) {
		if ( false === $user->exists() ) {
			return false;
		}

		$customer = $this->pull_customer( $user );

		if ( false === $customer ) {
			return false;
		}

		$user->first_name = $customer['first_name'];
		$user->last_name  = $customer['last_name'];
		$user->user_email = $customer['email'];

		return wp_update_user( $customer );
	}

	/**
	 * Pull customer information from the Askell API
	 *
	 * @param WP_User $user The WordPress user object.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function pull_customer( WP_User $user ) {
		if ( false === $user->exists() ) {
			return false;
		}

		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$api_response = wp_remote_get(
			"https://askell.is/api/customers/{$user->ID}/",
			array(
				'headers' => array(
					'accept'        => 'application/json',
					'Authorization' => "Api-Key {$private_key}",
				),
			)
		);

		if ( 200 !== $api_response['response']['code'] ) {
			return false;
		}

		$user = json_decode( $api_response['body'], true );

		return array(
			'first_name' => $user['first_name'],
			'last_name'  => $user['last_name'],
			'email'      => $user['email'],
		);
	}

	/**
	 * Pull in and save subscriptions for a specific user from the Askell API
	 *
	 * @param WP_User $user The WordPress user object.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function save_customer_subscriptions_to_user( WP_User $user ) {
		if ( false === $user->exists() ) {
			return false;
		}

		$subscriptions = $this->pull_customer_subscriptions( $user );

		if ( ! is_array( $subscriptions ) ) {
			return false;
		}

		return $this->set_subscriptions_for_user( $user, $subscriptions );
	}

	/**
	 * Pull subscription for a specific user from the Askell API
	 *
	 * @param WP_User $user The WordPress user object.
	 *
	 * @return array|bool Array of subscriptions on success. False on failure.
	 */
	public function pull_customer_subscriptions( WP_User $user ) {
		if ( false === $user->exists() ) {
			return false;
		}

		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$user_id = (int) $user->ID;

		$api_response = wp_remote_get(
			"https://askell.is/api/customers/{$user_id}/subscriptions/",
			array(
				'headers' => array(
					'accept'        => 'application/json',
					'Authorization' => "Api-Key {$private_key}",
				),
			)
		);

		if ( 200 !== $api_response['response']['code'] ) {
			return false;
		}

		$askell_subscriptions = json_decode( $api_response['body'], true );

		$subscriptions = array();
		foreach ( $askell_subscriptions as $s ) {
			$subscriptions[] = array(
				'id'          => $s['id'],
				'plan_id'     => $s['plan']['id'],
				'trial_end'   => $s['trial_end'],
				'start_date'  => $s['start_date'],
				'ended_at'    => $s['ended_at'],
				'active'      => $s['active'],
				'is_on_trial' => $s['is_on_trial'],
				'token'       => $s['token'],
			);
		}

		return $subscriptions;
	}

	/**
	 * Pull in and save plans from the Askell API
	 *
	 * @return bool True if the plans are updated, false on failure or if the
	 *              plans are unchanged.
	 */
	public function save_plans() {
		$plans = $this->pull_plans();
		if ( false === $plans ) {
			return false;
		}

		return update_option( 'askell_plans', $plans );
	}

	/**
	 * Pull the subscription plans from the Askell API
	 *
	 * @return array|bool An array of subscription plans on success or false on
	 *                    failure. Failure can occur if the API secret key has
	 *                    not been set or if the API does not respond with an
	 *                    "OK" status.
	 */
	public function pull_plans() {
		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$api_response = wp_remote_get(
			'https://askell.is/api/plans/',
			array(
				'headers' => array(
					'accept'        => 'application/json',
					'Authorization' => "Api-Key {$private_key}",
				),
			)
		);

		if ( 200 !== $api_response['response']['code'] ) {
			return false;
		}

		$plans = json_decode( $api_response['body'], true );
		return $plans;
	}

	/**
	 * Format an amount in a given currency, using the current WP locale
	 *
	 * @todo If the required libraries as missing, it may be possible to use the
	 * plolyfill available at
	 * https://packagist.org/packages/symfony/polyfill-intl-icu for handling
	 * this.
	 *
	 * @param string $currency The 3-digit currency code.
	 * @param string $amount   The numeric amount to format.
	 *
	 * @return string|bool The formatted currency value with symbol.
	 *                     False on error.
	 */
	private function format_currency( string $currency, string $amount ) {
		return MessageFormatter::formatMessage(
			get_locale(),
			"{0, number, :: currency/{$currency} unit-width-narrow}",
			array( $amount )
		);
	}

	/**
	 * Format a subscription interval, using the current WP locale
	 *
	 * @param string $interval The interval (day, week, month, year).
	 * @param int    $interval_count The interval frequecy.
	 */
	private function format_interval( string $interval, int $interval_count ) {
		if ( 1 === $interval_count ) {
			switch ( $interval ) {
				case 'day':
					return __( 'daily', 'askell-registration' );
				case 'week':
					return __( 'weekly', 'askell-registration' );
				case 'month':
					return __( 'monthly', 'askell-registration' );
				case 'year':
					return __( 'annually', 'askell-registration' );
				default:
					return false;
			}
		}

		switch ( $interval ) {
			case 'day':
				return sprintf(
					/* translators: The subscription reviews every %d days */
					__( 'every %d days', 'askell-registration' ),
					$interval_count
				);
			case 'week':
				return sprintf(
					/* translators: The subscription renews every %d weeks */
					__( 'every %d weeks', 'askell-registration' ),
					$interval_count
				);
			case 'month':
				return sprintf(
					/* translators: The subscription renews every %d months */
					__( 'every %d months', 'askell-registration' ),
					$interval_count
				);
			case 'year':
				return sprintf(
					/* translators: The subscription renews every %d years */
					__( 'every %d years', 'askell-registration' ),
					$interval_count
				);
			default:
				return false;
		}

		return false;
	}

	/**
	 * Format a full price tag using the current WP locale
	 *
	 * @param string $currency The 3-digit currency code.
	 * @param string $amount The amount value.
	 * @param string $interval The interval (day, week, month, year).
	 * @param int    $interval_count The interval frequecy.
	 * @param int    $trial_period_days the number of days for the trial period.
	 *
	 * @return string
	 */
	private function format_price_tag(
		string $currency,
		string $amount,
		string $interval,
		int $interval_count,
		int $trial_period_days
	) {
		if ( $trial_period_days > 0 ) {
			return ucfirst(
				sprintf(
					/* translators: Indicates a price tag for subscription option with a free trial ($20, monthly (30 day free trial)) */
					__( '%1$s, %2$s (%3$d day free trial)', 'askell-registration' ),
					self::format_currency( $currency, $amount ),
					self::format_interval( $interval, $interval_count ),
					$trial_period_days
				)
			);
		}

		return ucfirst(
			sprintf(
			/* translators: Indicates a price tag for subscription option without a free trial ($20, every 2 weeks) */
				__( '%1$s, %2$s', 'askell-registration' ),
				self::format_currency( $currency, $amount ),
				self::format_interval( $interval, $interval_count ),
			)
		);
	}

	/**
	 * Format payment information (brief terms) using the current WP locale
	 *
	 * @param string $currency The 3-digit currency code.
	 * @param string $amount   The numerical currency value.
	 * @param string $interval The interval (day, week, month, year).
	 * @param int    $interval_count The interval frequecy.
	 * @param int    $trial_period_days The length of the trial period in days.
	 *
	 * @return string
	 */
	private function format_payment_information(
		string $currency,
		string $amount,
		string $interval,
		int $interval_count,
		int $trial_period_days
	) {
		if ( $trial_period_days > 0 ) {
			return sprintf(
				/* translators: Appears in the credit card information form as an indicator of for how much, how and when the card would be charged, in addition to the length of the trial period. */
				__(
					'Upon confirmation, your card will be charged %1$s, %2$s, after a free trial period of %3$d days. Your card may be tested and validated in the meantime.',
					'askell-registration'
				),
				self::format_currency( $currency, $amount ),
				self::format_interval( $interval, $interval_count ),
				$trial_period_days
			);
		}

		return sprintf(
			/* translators: Appears in the credit card information form as an indicator of for how much, how and when the card would be charged. */
			__(
				'Upon confirmation, your card will be immediately charged %1$s and then %2$s for the same amount.',
				'askell-registration'
			),
			self::format_currency( $currency, $amount ),
			self::format_interval( $interval, $interval_count ),
		);
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
