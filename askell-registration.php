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
	public function __construct() {
		add_action( 'init', array( $this, 'block_init' ) );
		add_action( 'init', array( $this, 'enqueue_frontend_script' ) );
		add_filter( 'script_loader_tag', array( $this, 'load_frontend_script_as_module' ), 10, 3 );
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
}

$askell_registration = new AskellRegistration();
