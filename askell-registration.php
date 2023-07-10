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

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function askell_registration_askell_registration_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'askell_registration_askell_registration_block_init' );
