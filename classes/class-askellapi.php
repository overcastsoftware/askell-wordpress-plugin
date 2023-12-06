<?php
/**
 * The Askell API handler class file
 *
 * This should only have the AskellApi class in it. If you intend to add a new
 * class, create a new file for it in the same directory.
 *
 * @package askell-registration
 */

namespace Askell;

use WP_User;

/**
 * The Askell API hander class
 *
 * @package askell-registration
 */
class AskellApi {
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
			'customer_reference' => $user->user_login,
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

		$user = json_decode( $api_response['body'], true );

		if ( 201 !== $api_response['response']['code'] ) {
			return false;
		}

		return $user['id'];
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
			'customer_reference' => $user->user_login,
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
		global $askell;

		if ( false === $user->exists() ) {
			return false;
		}

		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$plan = $askell->get_plan_by_id( $plan_id );

		if ( false === $plan ) {
			return false;
		}

		$endpoint_url = 'https://askell.is/api/customers/' .
			$user->user_login .
			'/subscriptions/add/';

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
			"https://askell.is/api/customers/{$user->user_login}/",
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
			"https://askell.is/api/customers/{$user->user_login}/",
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
			'askell_id'  => $user['id'],
		);
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

		$api_response = wp_remote_get(
			'https://askell.is/api/customers/' .
				$user->user_login .
				'/subscriptions/',
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
				'id'           => $s['id'],
				'plan_id'      => $s['plan']['id'],
				'trial_end'    => $s['trial_end'],
				'start_date'   => $s['start_date'],
				'ended_at'     => $s['ended_at'],
				'active_until' => $s['ended_at'],
				'active'       => $s['active'],
				'is_on_trial'  => $s['is_on_trial'],
				'token'        => $s['token'],
			);
		}

		return $subscriptions;
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
	 * Delete user from the Askell API based on user ID
	 *
	 * This does not perform any checks on the user.
	 *
	 * @param int $user_id The user's ID and Askell customer reference.
	 *
	 * @return bool True on success. False on failure.
	 */
	public function delete_customer_in_askell_by_user_id( int $user_id ) {
		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );

		$api_response = wp_remote_request(
			"https://askell.is/api/customers/{$user->user_login}/",
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
	 * Cancel a subscription in askell
	 *
	 * @param int $subscription_id The subscription ID from the
	 *            $user->askell_subscriptions property.
	 */
	public function cancel_subscription_in_askell( int $subscription_id ) {
		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$api_response = wp_remote_request(
			"https://askell.is/api/subscriptions/{$subscription_id}/cancel/",
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
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
	 * Reactivate a subscription in Askell
	 *
	 * This only works if the current payment period has not lapsed.
	 *
	 * @param int $subscription_id The subscription ID from the
	 *            $user->askell_subscriptions property.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function activate_subscription_in_askell( int $subscription_id ) {
		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$api_response = wp_remote_request(
			"https://askell.is/api/subscriptions/{$subscription_id}/activate/",
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
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
	 * Sign a user to a subscription plan in Askell
	 *
	 * @param WP_User $user The user.
	 * @param int     $plan_id The ID for the plan.
	 * @param string  $plan_reference The reference for the plan.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function add_subscription_to_user_in_askell(
		WP_User $user,
		int $plan_id,
		string $plan_reference
	) {
		$private_key = get_option( 'askell_api_secret' );

		if ( false === $private_key ) {
			return false;
		}

		$request_body = array(
			'plan'      => $plan_id,
			'reference' => $plan_reference,
		);

		$api_response = wp_remote_request(
			'https://askell.is/api/customers/' .
				$user->user_login .
				'/subscriptions/add/',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'accept'        => 'application/json',
					'Authorization' => "Api-Key {$private_key}",
				),
				'body'    => wp_json_encode( $request_body ),
			)
		);

		if ( 201 !== $api_response['response']['code'] ) {
			return false;
		}

		return true;
	}
}
