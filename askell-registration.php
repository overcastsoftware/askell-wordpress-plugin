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
	}
}

$askell_registration = new AskellRegistration();
