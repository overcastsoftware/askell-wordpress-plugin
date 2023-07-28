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

	public function __construct() {
		add_action( 'init', array( $this, 'block_init' ) );
		add_action( 'init', array( $this, 'enqueue_frontend_script' ) );
		add_filter( 'script_loader_tag', array( $this, 'load_frontend_script_as_module' ), 10, 3 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function block_init() {
		register_block_type( __DIR__ . '/build' );
	}

	public function enqueue_frontend_script() {
		wp_enqueue_script(
			'askell-registration-frontend',
			plugins_url( 'askell-registration/build/frontend.js' ),
			array( 'wp-api', 'react', 'react-dom' ),
			'0.1.0',
			false
		);
	}

	public function load_frontend_script_as_module( $tag, $handle, $src ) {
		if ( 'askell-registration-frontend' === $handle ) {
			$tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
		}
		return $tag;
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
			'/form_fields',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'form_fields_json_get' ),
				'permission_callback' => '__return_true'
			)
		);
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
			array_key_exists( 'lastName', $request_body ) )
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
				'user_login' => $request_body['username'],
				'user_email' => $request_body['emailAddress'],
				'first_name' => $request_body['firstName'],
				'last_name'  => $request_body['lastName'],
				'role'       => 'subscriber'
			)
		);

		if ( true === is_a( $new_user_id, 'WP_Error' ) ) {
			return $new_user_id;
		}

		$user = get_user_by('id', $new_user_id);

		if (false === $user) {
			return new WP_Error(
				'user_not_found',
				'The new user was not found'
			);
		}

		return $user->data;
	}

	function form_fields_json_get() {
		return [
			'plans' => [
				[
					'id'                => 1001,
					'name'              => 'The Peasant',
					'alternative_name'  => 'The least expensive option',
					'reference'         => 'OPT1',
					'interval'          => 'month',
					'interval_count'    => 1,
					'amount'            => '100.0000',
					'currency'          => 'ISK',
					'trial_period_days' => 0,
					'description'       => 'Be a cheapskate and get the cheapest option available.',
					'price_tag'         => $this->format_price_tag('ISK', '100.0000', 'month', 1, 0),
					'payment_info'      => $this->format_payment_information('ISK', '100.0000', 'month', 1, 0),
				],
				[
					'id'                => 1002,
					'name'              => 'The Rich Bastard',
					'alternative_name'  => 'The Middle of the Road',
					'reference'         => 'OPT2',
					'interval'          => 'month',
					'interval_count'    => 1,
					'amount'            => '250.0000',
					'currency'          => 'ISK',
					'trial_period_days' => 30,
					'description'       => 'This means you are a least a little supportive, which is good.',
					'price_tag'         => $this->format_price_tag('ISK', '250.0000', 'month', 1, 30),
					'payment_info'      => $this->format_payment_information('ISK', '250.0000', 'month', 1, 30),
				],
				[
					'id'                => 1003,
					'name'              => 'The Millionaire',
					'alternative_name'  => 'The Fast Lane',
					'reference'         => 'OPT3',
					'interval'          => 'month',
					'interval_count'    => 1,
					'amount'            => '1500.0000',
					'currency'          => 'ISK',
					'trial_period_days' => 30,
					'description'       => 'Gets you all the benefits of being a rich bastard, plus a selfie with the team.',
					'price_tag'         => $this->format_price_tag('ISK', '1500.0000', 'month', 1, 30),
					'payment_info'      => $this->format_payment_information('ISK', '1500.0000', 'month', 1, 30),
				]
			]
		];
	}

	/**
	 * Format an amount in a given currency, using the current WP locale
	 *
	 * If the required libraries as missing, it may be possible to use the
	 * plolyfill available at
	 * https://packagist.org/packages/symfony/polyfill-intl-icu for handling
	 * this.
	 */
	private function format_currency(string $currency, string $amount) {
		return msgfmt_format_message(
			get_locale(),
			"{0, number, :: currency/{$currency} unit-width-narrow}",
			[$amount]
		);
	}

	private function format_interval(string $interval, int $interval_count) {
		if ($interval_count === 1) {
			switch ($interval) {
				case 'day':
					return 'daily';
				case 'week':
					return 'weekly';
				case 'month':
					return 'monthly';
				case 'year':
					return 'annually';
				default:
					return false;
			}
		}

		switch ($interval) {
			case 'day':
				return sprintf('every %s days', $interval_count);
			case 'week':
				return sprintf('every %s weeks', $interval_count);
			case 'month':
				return sprintf('every %s months', $interval_count);
			case 'year':
				return sprintf('every %s years', $interval_count);
			default:
				return false;
		}

		return false;
	}

	private function format_price_tag(string $currency, string $amount, string $interval, int $interval_count, int $trial_period_days) {
		if ($trial_period_days > 0) {
			return ucfirst(sprintf(
				__('%s, %s (%s day free trial)', 'askell-registration'),
				self::format_currency($currency, $amount),
				self::format_interval($interval, $interval_count),
				$trial_period_days
			));
		}

		return ucfirst(sprintf(
			__('%s, %s', 'askell-registration'),
			self::format_currency($currency, $amount),
			self::format_interval($interval, $interval_count),
		));

	}

	private function format_payment_information(string $currency, string $amount, string $interval, int $interval_count, int $trial_period_days) {
		if ($trial_period_days > 0) {
			return sprintf(
				__(
					'Upon confirmation, your card will be charged %s, %s, after a free trial period of %s days. Your card may be tested and validated in the meantime.',
					'askell-registration'
				),
				self::format_currency($currency, $amount),
				self::format_interval($interval, $interval_count),
				$trial_period_days
			);
		}

		return sprintf(
			__('Upon confirmation, your card will be immedietly charged %s and then %s for the same amount.', 'askell-registration'),
			self::format_currency($currency, $amount),
			self::format_interval($interval, $interval_count),
		);
	}
}

$askell_registration = new AskellRegistration();
