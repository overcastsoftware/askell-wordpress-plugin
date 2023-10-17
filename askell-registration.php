<?php
/**
 * Plugin Name:       Askell Registration
 * Plugin URI:        https://askell.is/
 * Description:       Sign up for recurring subscriptions directly from WordPress using Askell
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Overcast Software
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       askell-registration
 * Domain Path:       askell
 *
 * @package           askell-registration
 */

class AskellRegistration {
	const REST_NAMESPACE = 'askell/v1';
	const USER_ROLE = 'subscriber';
	const PLUGIN_PATH = 'askell-registration';
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

	public function __construct() {
		add_action( 'init', array( $this, 'block_init' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'enqueue_admin_script' ) );

		add_action( 'askell_sync_cron', array( $this, 'save_plans' ) );
		add_action( 'init', array( $this, 'schedule_sync_cron' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

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

	public function schedule_sync_cron() {
		if ( ! wp_next_scheduled( 'askell_sync_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'askell_sync_cron' );
		}
	}

	public function block_init() {
		register_block_type(
			__DIR__ . '/build'
		);
	}

	public function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/customer',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'customer_rest_post' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/customer_payment_method',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'customer_payment_method_post' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/form_fields',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'form_fields_json_get' ),
				'permission_callback' => '__return_true'
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/settings',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'settings_rest_post' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				}
			)
		);
	}

	public function customer_payment_method_post(WP_REST_Request $request) {
		$request_body = (array) json_decode( $request->get_body() );

		$user_query = new WP_User_Query(
			array(
				'meta_key' => 'askell_registration_token',
				'meta_value' => $request_body['registrationToken']
			)
		);

		$users = $user_query->get_results();

		if ( 0 === count($users) ) {
			return new WP_Error(
				'user_not_found',
				'User Not Found',
				array( 'status' => 404 )
			);
		}

		$user = $users[0];
		$payment_token = $request_body['paymentToken'];
		$plan_id = $request_body['planID'];

		$this->assign_payment_method_to_user_in_askell($user, $payment_token);
		$this->assign_subscription_to_user_in_askell($user, $plan_id);

		update_user_meta( $user->ID, 'askell_payment_token', $payment_token );
	}

	public function settings_rest_post(WP_REST_Request $request) {
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

		return true;
	}

	public function customer_rest_post(WP_REST_Request $request) {
		$request_body = (array) json_decode( $request->get_body() );

		if ( true === is_null($request_body) ) {
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
				'user_login' => sanitize_user($request_body['username']),
				'user_email' => $request_body['emailAddress'],
				'first_name' => $request_body['firstName'],
				'last_name'  => $request_body['lastName'],
				'role'       => self::USER_ROLE
			)
		);

		# If there in an error in the user registration, wp_insert_user() will
		# spit out a WP_Error, which we need to cast into another one with
		# an appropriate HTTP status.
		if ( true === is_a( $new_user_id, 'WP_Error' ) ) {
			return new WP_Error(
				$new_user_id->get_error_code(),
				$new_user_id->get_error_message(),
				array( 'status' => 400 )
			);
		}

		$token = base64_encode(random_bytes(32));

		update_user_meta(
			$new_user_id,
			'askell_registration_token',
			$token
		);

		$user = get_user_by('id', $new_user_id);

		$this->register_user_in_askell($user);

		return array(
			'ID' => $user->data->ID,
			'registration_token' => $token,
		);
	}

	/**
	 * Add a subscription to a WordPress user
	 *
	 * @param WP_User $user The WP_User object representing the user.
	 * @param array   $subscription An array representing the subscription,
	 *                              including the keys `id`, `plan_id`,
	 *                              `trial_end`, `start_date`, `ended_at`,
	 *                              `active` and `is_on_trial`.
	 *
	 * @return int|bool The result of the WP update_user_meta call.
	 */
	public function add_subscription_to_user(
		WP_User $user,
		array $subscription
	) {
		$current_subscriptions = get_user_meta(
			$user->ID,
			'askell_subscriptions',
			true
		);

		if ( is_array( $current_subscriptions ) ) {
			array_push( $current_subscriptions, $subscription );
		} else {
			$current_subscriptions = array( $subscription );
		}

		return update_user_meta(
			$user->ID,
			'askell_subscriptions',
			$current_subscriptions
		);
	}

	/**
	 * Set and overwrite a user's subscriptions
	 *
	 * @param WP_User $user The WP_User object representing the user.
	 * @param array   $subscriptions An array of subscription arrays.
	 */
	public function set_subscriptions_for_user(
		WP_User $user,
		array $subscriptions
	) {
		return update_user_meta(
			$user->ID,
			'askell_subscriptions',
			$subscriptions
		);
	}

	/**
	 * Remove a subscription from a user based on its ID
	 *
	 * @param WP_User $user The WP_User object representing the user.
	 * @param string  $subscription_id The ID of the subscription to remove.
	 */
	public function remove_subscription_from_user(
		WP_User $user,
		string $subscription_id
	) {
		$current_subscriptions = get_user_meta(
			$user->ID,
			'askell_subscriptions',
			true
		);

		if ( is_array( $current_subscriptions ) ) {
			foreach ( $current_subscriptions as $i => $s ) {
				if ( $subscription_id === $s['id'] ) {
					array_splice( $current_subscriptions, $i, 1 );
					break;
				}
			}
		} else {
			$current_subscriptions = array();
		}

		return update_user_meta(
			$user->ID,
			'askell_subscriptions',
			$current_subscriptions
		);
	}

	public function register_user_in_askell(WP_User $user) {
		$endpoint_url = 'https://askell.is/api/customers/';
		$api_key = get_option( 'askell_api_secret', '' );

		$request_body = array(
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'email' => $user->user_email,
			'customer_reference' => (string) $user->ID
		);

		return wp_remote_post(
			$endpoint_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => "Api-Key $api_key"
				),
				'body' => wp_json_encode($request_body)
			)
		);
	}

	public function assign_payment_method_to_user_in_askell(
		WP_User $user,
		$payment_token
	) {
		$user_reference = (string) $user->ID;
		$endpoint_url = "https://askell.is/api/customers/paymentmethod/";
		$api_key = get_option( 'askell_api_secret', '' );

		$request_body = array(
			'customer_reference' => $user_reference,
			'token' => $payment_token
		);

		return wp_remote_post(
			$endpoint_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => "Api-Key $api_key"
				),
				'body' => wp_json_encode($request_body)
			)
		);
	}

	public function assign_subscription_to_user_in_askell(WP_User $user, $plan_id) {
		$user_reference = $user->ID;
		$endpoint_url = "https://askell.is/api/customers/$user_reference/subscriptions/add/";
		$api_key = get_option( 'askell_api_secret', '' );
		$plan = $this->get_plan_by_id($plan_id);

		$request_body = array(
			"plan" => $plan_id,
			"reference" => $plan['reference']
		);

		return wp_remote_post(
			$endpoint_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => "Api-Key $api_key"
				),
				'body' => wp_json_encode($request_body)
			)
		);
	}

	public function add_menu_page() {
		add_menu_page(
			__('Askell', 'askell-registration'),
			__('Askell', 'askell-registration'),
			'manage_options',
			'askell-registration',
			array( $this, 'render_admin_page' ),
			self::ADMIN_ICON,
			91
		);
	}

	public function render_admin_page() {
		if ( false === current_user_can( 'manage_options' ) ) {
			return false;
		}

		require __DIR__ . '/views/main-page.php';
	}

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

	public function plans() {
		$plans = get_option( 'askell_plans', [] );
		foreach ( $plans as $k=>$p ) {
			$plans[$k]['price_tag'] = $this->format_price_tag(
				$plans[$k]['currency'],
				$plans[$k]['amount'],
				$plans[$k]['interval'],
				$plans[$k]['interval_count'],
				$plans[$k]['trial_period_days']
			);
			$plans[$k]['payment_info'] = $this->format_payment_information(
				$plans[$k]['currency'],
				$plans[$k]['amount'],
				$plans[$k]['interval'],
				$plans[$k]['interval_count'],
				$plans[$k]['trial_period_days']
			);
		}

		return $plans;
	}

	function get_public_plans() {
		return array_values(array_filter($this->plans(), function($a) {
			return ($a['private'] == false);
		}));
	}

	function get_plan_by_reference($reference) {
		return array_filter(
			$this->plans(),
			function($a) use ($reference) {
			return ($a['reference'] == $reference);
			}
		);
	}

	function get_plan_by_id($id) {
		return array_filter(
			$this->plans(),
			function($a) use ($id) {
			return ($a['id'] == $id);
			}
		)[0];
	}

	function form_fields_json_get() {
		return [
			'api_key' => get_option('askell_api_key'),
			'reference' => get_option('askell_reference', 'wordpress_id'),
			'styles_enabled' => get_option('askell_styles_enabled', true),
			'address_country_enabled' => get_option('askell_address_country_enabled', false),
			'plans' => $this->get_public_plans()
		];
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

		$subscriptions = $this->pull_customer_subscriptions( $user->ID );

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
	 * @return bool True on success, false on failure.
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
	 */
	private function format_currency(string $currency, string $amount) {
		return MessageFormatter::formatMessage(
			get_locale(),
			"{0, number, :: currency/{$currency} unit-width-narrow}",
			[$amount]
		);
	}

	private function format_interval(string $interval, int $interval_count) {
		if ($interval_count === 1) {
			switch ($interval) {
				case 'day':
					return __('daily', 'askell-registration');
				case 'week':
					return __('weekly', 'askell-registration');
				case 'month':
					return __('monthly', 'askell-registration');
				case 'year':
					return __('annually', 'askell-registration');
				default:
					return false;
			}
		}

		switch ($interval) {
			case 'day':
				return sprintf(
					__('every %d days', 'askell-registration'),
					$interval_count
				);
			case 'week':
				return sprintf(
					__('every %d weeks', 'askell-registration'),
					$interval_count
				);
			case 'month':
				return sprintf(
					__('every %d months', 'askell-registration'),
					$interval_count
				);
			case 'year':
				return sprintf(
					__('every %d years', 'askell-registration'),
					$interval_count
				);
			default:
				return false;
		}

		return false;
	}

	private function format_price_tag(
		string $currency,
		string $amount,
		string $interval,
		int $interval_count,
		int $trial_period_days
	) {
		if ($trial_period_days > 0) {
			return ucfirst(sprintf(
				/* translators: Indicates a price tag for subscription option with a free trial ($20, monthly (30 day free trial)) */
				__('%1$s, %2$s (%3$d day free trial)', 'askell-registration'),
				self::format_currency($currency, $amount),
				self::format_interval($interval, $interval_count),
				$trial_period_days
			));
		}

		return ucfirst(sprintf(
			/* translators: Indicates a price tag for subscription option without a free trial ($20, every 2 weeks) */
			__('%1$s, %2$s', 'askell-registration'),
			self::format_currency($currency, $amount),
			self::format_interval($interval, $interval_count),
		));

	}

	private function format_payment_information(
		string $currency,
		string $amount,
		string $interval,
		int $interval_count,
		int $trial_period_days
	) {
		if ($trial_period_days > 0) {
			return sprintf(
				/* translators: Appears in the credit card information form as an indicator of for how much, how and when the card would be charged. */
				__(
					'Upon confirmation, your card will be charged %1$s, %2$s, after a free trial period of %3$d days. Your card may be tested and validated in the meantime.',
					'askell-registration'
				),
				self::format_currency($currency, $amount),
				self::format_interval($interval, $interval_count),
				$trial_period_days
			);
		}

		return sprintf(
			__(
				'Upon confirmation, your card will be immediately charged %1$s and then %2$s for the same amount.',
				'askell-registration'
			),
			self::format_currency($currency, $amount),
			self::format_interval($interval, $interval_count),
		);
	}
}

$askell_registration = new AskellRegistration();

# Cleanup tasks for the plugin.
register_deactivation_hook( __FILE__, 'askell_registration_deactivate' );

function askell_registration_deactivate() {
    $timestamp = wp_next_scheduled( 'askell_sync_cron' );
    wp_unschedule_event( $timestamp, 'askell_sync_cron' );
}
