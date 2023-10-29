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

namespace Askell;

use WP_Query;
use WP_User;
use WP_Post;
use WP_Error;
use MessageFormatter;

/**
 * The main Askell class
 *
 * @package askell-registration
 */
class Main {
	const REST_NAMESPACE = 'askell/v1';
	const USER_ROLE      = 'subscriber';
	const WEBHOOK_TYPES  = array( 'customer', 'subscription' );
	const POST_TYPES     = array( 'page', 'post' );
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
	 * The default value for the paywall heading
	 *
	 * @var string $default_paywall_heading The heading text.
	 */
	public string $default_paywall_heading;

	/**
	 * The default value for the paywall text body
	 *
	 * @var string $default_paywall_text_body The body text.
	 */
	public string $default_paywall_text_body;

	/**
	 * The class constructor
	 */
	public function __construct() {
		$this->default_paywall_heading = __(
			'Register or log in to see more',
			'askell-registration'
		);

		$this->default_paywall_text_body = __(
			'Registered subscribers can get access to this content. You can register to create an account or log in if you already have an account.',
			'askell-registration'
		);

		add_action( 'init', array( $this, 'block_init' ) );
		add_action( 'init', array( $this, 'schedule_sync_cron' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'enqueue_admin_script' ) );

		add_action( 'askell_sync_cron', array( $this, 'save_plans' ) );

		add_action(
			'wp_update_user',
			array( $this, 'push_customer_on_user_update' )
		);

		add_action(
			'delete_user',
			array( $this, 'delete_customer_on_user_delete' )
		);

		add_action(
			'wp_before_admin_bar_render',
			array( $this, 'remove_wp_logo_from_admin_bar' ),
			10,
			0
		);

		add_filter(
			'login_redirect',
			array( $this, 'redirect_subscribers_on_login' ),
			10,
			3
		);

		add_filter(
			'edit_profile_url',
			array( $this, 'filter_profile_url' ),
			10,
			2
		);

		add_action( 'init', array( $this, 'register_post_meta' ) );
		add_action(
			'enqueue_block_editor_assets',
			array( $this, 'enqueue_editor_sidebar_script' )
		);

		add_action( 'loop_start', array( $this, 'add_paywall_filter' ) );
		add_action( 'loop_end', array( $this, 'remove_paywall_filter' ) );

		add_action(
			'save_post',
			array( $this, 'set_register_url_on_post_save' ),
			-1,
			2
		);
	}

	/**
	 * Add the filter that enables the paywall for posts
	 *
	 * This is run on the `loop_start` hook.
	 *
	 * @param WP_Query $query The post query.
	 */
	public function add_paywall_filter( WP_Query $query ) {
		if ( $query->is_singular ) {
			add_filter(
				'the_content',
				array( $this, 'filter_post_content' ),
				-100
			);
		}
	}

	/**
	 * Remove the filter that enables the paywall for posts
	 *
	 * This is run on the `loop_end` hook.
	 *
	 * @param WP_Query $query The post query.
	 */
	public function remove_paywall_filter( WP_Query $query ) {
		if (
			has_filter(
				'the_content',
				array( $this, 'filter_post_content' )
			)
		) {
			remove_filter(
				'the_content',
				array( $this, 'filter_post_content' )
			);
		}
	}

	/**
	 * Render the paywall if the user does not have access to the post or output
	 * the content unmodified if the current user has access
	 *
	 * This uses the global $post variable to figure out the post attibutes.
	 *
	 * @param string $content The post content.
	 *
	 * @return string The content if access is granted, the paywall and excerpt
	 *                if not.
	 */
	public function filter_post_content( string $content ) {
		global $post;
		$user = wp_get_current_user();

		if ( true === $this->user_has_access_to_post( $user, $post ) ) {
			return $content;
		}

		return $this->paywall( $post, $content );
	}

	/**
	 * Render the paywall and post excerpt
	 *
	 * @todo This is a whole mess of Gutenberg code and may need to be moved to
	 *       the views directory.
	 *
	 * @param WP_Post $post The post.
	 * @param string  $content The post content.
	 *
	 * @return string The paywall content.
	 */
	public function paywall( WP_Post $post, string $content ) {
		$post_plan_ids = $this->post_plan_ids_array( $post );
		$login_url     = wp_login_url( get_permalink( $post ), true );
		$register_url  = get_option( 'askell_register_url', '' );

		if ( '' === $post->post_excerpt ) {
			$excerpt_text = wp_trim_words( $content );
		} else {
			$excerpt_text = $post->post_excerpt;
		}

		$excerpt = "<!-- wp:paragraph -->\n<p>" .
			wp_trim_words( $excerpt_text ) .
			"</p>\n<!-- /wp:paragraph -->";

		$nag = '<!-- wp:group {"className":"askell-register-or-log-in",' .
			'"layout":{"type":"flex","orientation":"vertical"}} -->' .
			'<div class="wp-block-group askell-register-or-log-in">' .
			'<!-- wp:heading -->' .
			'<h2 class="wp-block-heading">' .
			esc_html(
				get_option(
					'askell_paywall_heading',
					$this->default_paywall_heading
				)
			) .
			'</h2>' .
			'<!-- /wp:heading -->' .
			'<!-- wp:paragraph -->' .
			'<p>' .
			esc_html(
				get_option(
					'askell_paywall_text_body',
					$this->default_paywall_text_body
				)
			) .
			'</p>' .
			'<!-- /wp:paragraph -->';

		if ( 'specific_plans' === $post->askell_visibility ) {
			if ( 1 < count( $post_plan_ids ) ) {
				$nag .= '<!-- wp:paragraph --><p>' .
					__(
						'This content is only available to subscribers with the following plans:',
						'askell-registration'
					) .
					'</p><!-- /wp:paragraph -->';

				$nag .= '<!-- wp:list --><ul>';

				foreach ( $post_plan_ids as $post_plan_id ) {
					$plan = $this->get_plan_by_id( $post_plan_id );
					$nag .= '<!-- wp:list-item --><li>' .
						$plan['name'] .
						'</li><!-- /wp:list-item -->';
				}

				$nag .= '</ul><!-- /wp:list -->';
			} else {
				$plan = $this->get_plan_by_id( $post_plan_ids[0] );
				$nag .= '<!-- wp:paragraph --><p>' .
				sprintf(
					/* Translators: The %s stands for a single subscription plan name. */
					__(
						'This content is only available to subscribers with the ‘%s’ plan.',
						'askell-registration'
					),
					$plan['name']
				) .
				'</p><!-- /wp:paragraph -->';
			}
		}

		$nag .= '<!-- wp:group ' .
			'{"layout":{"type":"flex","flexWrap":"nowrap"}} -->' .
			'<div class="wp-block-group">';

		if ( '' !== $register_url ) {
			$nag .= '<!-- wp:buttons -->' .
				'<div class="wp-block-buttons">' .
				'<!-- wp:button {"className":"askell-register-link"} -->' .
				'<div class="wp-block-button askell-register-link">' .
				'<a class="wp-block-button__link wp-element-button" href="' .
				$register_url .
				'">' .
				__( 'Register', 'askell-registration' ) .
				'</a>' .
				'</div>' .
				'<!-- /wp:button --></div>' .
				'<!-- /wp:buttons -->';
		}

		$nag .= '<!-- wp:paragraph -->' .
		'<p><a href="' . esc_url( $login_url ) . '">' .
		__( 'Log in', 'askell-registration' ) .
		'</a></p>' .
		'<!-- /wp:paragraph --></div>' .
		'<!-- /wp:group --></div>' .
		'<!-- /wp:group -->';

		$blocks = parse_blocks( $excerpt . $nag );

		return render_block( $blocks[0] ) . render_block( $blocks[1] );
	}

	/**
	 * Check if a user has access to a post
	 *
	 * @param WP_User $user The user.
	 * @param WP_Post $post The post.
	 *
	 * @return bool True if the user has access, false if not.
	 */
	public function user_has_access_to_post( WP_User $user, WP_Post $post ) {
		// Enable access of the post is publicly visible.
		if ( 'public' === $post->askell_visibility ) {
			return true;
		}

		// If the user is able to edit posts, they should be able to see them.
		if ( true === $user->has_cap( 'edit_posts' ) ) {
			return true;
		}

		// Reject if the user is logged out.
		if ( 0 === $user->ID ) {
			return false;
		}

		$user_subscriptions = get_user_meta( $user->ID, 'askell_subscriptions', true );

		if ( '' === $user_subscriptions ) {
			$user_subscriptions = array();
		}

		// Check if the user has an active subscription if the post is visible
		// to all subscribers.
		if ( 'subscribers' === $post->askell_visibility ) {
			foreach ( $user_subscriptions as $subscription ) {
				if ( true === $subscription['active'] ) {
					return true;
				}
			}
		}

		// Loop through the user's subscriptions and find an active subscription
		// that corresponds with the required plans for the post if it is
		// labelled as specific_plans.
		if ( 'specific_plans' === $post->askell_visibility ) {
			foreach ( $user_subscriptions as $subscription ) {
				if (
					( true === $subscription['active'] ) &&
					( true === in_array(
						$subscription['plan_id'],
						$this->post_plan_ids_array( $post ),
						true
					) )
				) {
					return true;
				}
			}
		}

		// Reject if no conditions are met.
		return false;
	}

	/**
	 * Get the subscription plan IDs for a post as an array of integers
	 *
	 * @param WP_Post $post The post.
	 *
	 * @return array
	 */
	public function post_plan_ids_array( WP_Post $post ) {
		$plan_ids = explode( ',', $post->askell_plan_ids );

		foreach ( $plan_ids as $k => $plan_id ) {
			$plan_ids[ $k ] = (int) $plan_id;
		}

		return $plan_ids;
	}

	/**
	 * Set the URL for the registration page/post if it has not been set yet
	 *
	 * This hooks into `save_post` and sets the `askell_register_url` if the
	 * page or post has the askell registration block and it has not been set.
	 *
	 * @param int     $post_id The post ID (unused).
	 * @param WP_Post $post The post.
	 */
	public function set_register_url_on_post_save(
		int $post_id,
		WP_Post $post
	) {
		if (
			( '' === get_option( 'askell_register_url', '' ) ) &&
			(
				true === has_block(
					'askell-registration/askell-registration',
					$post
				)
			) &&
			( 'publish' === $post->post_status )
		) {
			update_option( 'askell_register_url', get_permalink( $post ) );
		}
	}

	/**
	 * Enqueue the JS required for our editor sidebar
	 */
	public function enqueue_editor_sidebar_script() {
		wp_enqueue_script(
			'askell-registration-editor-sidebar',
			plugins_url( self::PLUGIN_PATH . '/build/editor-sidebar.js' ),
			array(
				'wp-edit-post',
				'wp-element',
				'wp-components',
				'wp-plugins',
				'wp-data',
			),
			self::ASSETS_VERSION,
			false
		);
	}

	/**
	 * Register required post meta
	 *
	 * Registers the askell_plan_ids and askell_visibility post meta attributes.
	 */
	public function register_post_meta() {
		register_meta(
			'post',
			'askell_plan_ids',
			array(
				'type'          => 'string',
				'description'   => 'A comma separated string of active Askell plan IDs that the user needs to have in their active subscriptions',
				'single'        => true,
				'default'       => '',
				'auth_callback' => array( $this, 'post_meta_auth_check' ),
				'show_in_rest'  => true,
			)
		);

		register_meta(
			'post',
			'askell_visibility',
			array(
				'type'          => 'string',
				'description'   => 'The post visibility for askell; may be public, subscribers and specific_plans',
				'single'        => true,
				'default'       => 'public',
				'auth_callback' => array( $this, 'post_meta_auth_check' ),
				'show_in_rest'  => true,
			)
		);
	}

	/**
	 * Check if the current user is an editor or higher
	 */
	public function post_meta_auth_check() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Remove the WP logo and home/dashboard link form the admin bar
	 * if the current user is a subscriber
	 *
	 * This replaces the "home" link with our own simplified version that does
	 * not have any subitems.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/wp_before_admin_bar_render/
	 */
	public function remove_wp_logo_from_admin_bar() {
		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		if ( true === in_array( self::USER_ROLE, $user_roles, true ) ) {
			global $wp_admin_bar;

			$wp_admin_bar->add_node(
				array(
					'id'    => 'my-account',
					'title' => $user->display_name,
				)
			);

			$wp_admin_bar->remove_menu( 'wp-logo' );
			$wp_admin_bar->remove_menu( 'site-name' );
			$wp_admin_bar->add_menu(
				array(
					'id'    => 'askell-home-link',
					'title' => get_bloginfo( 'name' ),
					'href'  => home_url(),
					'meta'  => array(
						'title' => __(
							'Go to the home page',
							'askell-registration'
						),
					),
				)
			);
		}
	}

	/**
	 * Redirect subscribers to the home url on login
	 *
	 * @see https://developer.wordpress.org/reference/hooks/login_redirect/.
	 *
	 * @param string           $redirect_to The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect
	 *                                                destination URL passed as
	 *                                                a parameter.
	 * @param WP_User|WP_Error $user WP_User object if login was successful,
	 *                               WP_Error object otherwise.
	 */
	public function redirect_subscribers_on_login(
		string $redirect_to,
		string $requested_redirect_to,
		WP_User|WP_Error $user
	) {
		if ( true === is_a( $user, 'WP_Error' ) ) {
			return $requested_redirect_to;
		}

		$user_roles = $user->roles;

		if ( true === in_array( self::USER_ROLE, $user_roles, true ) ) {
			return home_url();
		}
		return $requested_redirect_to;
	}

	/**
	 * Filter the profile URL for subsriber to make it point to the Askell
	 * "my profile" view instead of the built-in user profile
	 *
	 * @see https://developer.wordpress.org/reference/hooks/edit_profile_url/
	 *
	 * @param string $url The original profile URL.
	 * @param int    $user_id The user's ID.
	 *
	 * @return string The url to the 'my-profile' view from Askell.
	 */
	public function filter_profile_url( string $url, int $user_id ) {
		$user       = get_user_by( 'ID', $user_id );
		$user_roles = $user->roles;

		if ( true === in_array( self::USER_ROLE, $user_roles, true ) ) {
			return admin_url( 'admin.php?page=askell-registration-my-profile' );
		}
		return $url;
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
			__DIR__ . '/../build'
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

		if ( false === in_array( $this::USER_ROLE, $user->roles, true ) ) {
			return false;
		}

		// Prevent an infinite loop from happening if the update was requested
		// by a web hook. Web hooks always use the Hook-HMAC HTTP header so they
		// can be identified that way.
		if ( isset( $_SERVER['HTTP_HOOK_HMAC'] ) ) {
			return false;
		}

		return $this->push_customer( $user );
	}

	/**
	 * Check if a user has a certain subscription plan ID assigned
	 *
	 * @param WP_User $user The user.
	 * @param int     $plan_id The plan ID from the $user->askell_subscriptions
	 *                         property.
	 *
	 * @return bool True if the user has the plan, false if not.
	 */
	public function user_has_subscription_plan(
		WP_User $user,
		int $plan_id
	) {
		foreach ( $user->askell_subscriptions as $subscription ) {
			if ( $subscription['plan_id'] === $plan_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a user has a certain subscription ID assigned
	 *
	 * @param WP_User $user The user.
	 * @param int     $subscription_id The subscription ID from the
	 *                $user->askell_subscriptions property.
	 *
	 * @return boolean True if the user has the subscription, false if not.
	 */
	public function user_has_subscription(
		WP_User $user,
		int $subscription_id
	) {
		foreach ( $user->askell_subscriptions as $subscription ) {
			if ( $subscription['id'] === $subscription_id ) {
				return true;
			}
		}
		return false;
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
			"https://askell.is/api/customers/{$user->ID}/subscriptions/add/",
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
	 *
	 * This also removes the "Users" option from the sidebar if the logged-in
	 * user is a subscriber.
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'Askell Settings', 'askell-registration' ),
			__( 'Askell', 'askell-registration' ),
			'manage_options',
			'askell-registration',
			array( $this, 'render_admin_page' ),
			self::ADMIN_ICON,
			91
		);

		add_submenu_page(
			'askell-registration',
			__( 'Subscribers', 'askell-registration' ),
			__( 'Subscribers', 'askell-registration' ),
			'manage_options',
			'askell-registration-subscribers',
			array( $this, 'render_subscribers_admin_page' ),
		);

		$current_user_roles = wp_get_current_user()->roles;

		if ( true === in_array( self::USER_ROLE, $current_user_roles, true ) ) {
			add_menu_page(
				__( 'My Profile', 'askell-registration' ),
				__( 'My Profile', 'askell-registration' ),
				'read',
				'askell-registration-my-profile',
				array( $this, 'render_profile_editor' ),
				'dashicons-admin-users',
				92
			);

			remove_menu_page( 'index.php' );
			remove_menu_page( 'profile.php' );
		}
	}

	/**
	 * Render the settings admin page
	 *
	 * This reads in the 'amin.php' view file.
	 */
	public function render_admin_page() {
		if ( false === current_user_can( 'manage_options' ) ) {
			return false;
		}

		require __DIR__ . '/../views/admin.php';
	}

	/**
	 * Render the subscribers admin page
	 *
	 * This reads in the 'subscribers-admin.php' view file.
	 */
	public function render_subscribers_admin_page() {
		if ( false === current_user_can( 'manage_options' ) ) {
			return false;
		}

		require __DIR__ . '/views/subscribers-admin.php';
	}

	/**
	 * Render the "my profile" admin page for subscribers
	 */
	public function render_profile_editor() {
		require __DIR__ . '/views/profile-editor.php';
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
	 * Get an array of pubic plan IDs
	 *
	 * @return array An array of IDs.
	 */
	public function get_public_plan_ids() {
		$plan_ids = array();

		foreach ( $this->get_public_plans() as $plan ) {
			$plan_ids[] = $plan['id'];
		}

		return $plan_ids;
	}

	/**
	 * Get the plan IDs for a user's subscriptions
	 *
	 * @param WP_User $user The user.
	 *
	 * @return array An array of IDs.
	 */
	public function user_subscription_plan_ids( WP_User $user ) {
		$plan_ids = array();

		foreach ( $user->askell_subscriptions as $subscription ) {
			$plan_ids[] = $subscription['plan_id'];
		}

		return $plan_ids;
	}

	/**
	 * Get an array of the IDs for public plans that are open to a user
	 *
	 * @param WP_User $user The user.
	 *
	 * @return array An array of IDs.
	 */
	public function public_plan_ids_available_to_user( WP_User $user ) {
		return array_diff(
			$this->get_public_plan_ids(),
			$this->user_subscription_plan_ids( $user )
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

		update_metadata(
			'user',
			$user->ID,
			'askell_customer_id',
			$customer['askell_id']
		);

		return wp_update_user( $user );
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
			'askell_id'  => $user['id'],
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
	 * Get a comma-separated list of the subscription plans assigned to a user
	 *
	 * @param WP_User $user The WP_User object representing the user.
	 *
	 * @return string A comma-separated string on success, empty string if no
	 *                subscriptions are found.
	 */
	public function plan_names_for_user( WP_User $user ) {
		if ( true === empty( $user->askell_subscriptions ) ) {
			return '';
		}

		$plan_names = array();
		foreach ( $user->askell_subscriptions as $s ) {
			if ( true === $s['active'] ) {
				$plan = $this->get_plan_by_id( $s['plan_id'] );
				if ( false != $plan ) {
					$plan_names[] = $plan['name'];
				}
			}
		}

		return implode( ', ', $plan_names );
	}
}
