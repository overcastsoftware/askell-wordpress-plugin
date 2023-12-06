<?php
/**
 * Plugin Name:       Askell Registration
 * Plugin URI:        https://askell.is/
 * Description:       Sign up for recurring subscriptions directly from WordPress using Askell
 * Requires at least: 6.1
 * Requires PHP:      8.0
 * Version:           0.2.0
 * Author:            Overcast Software
 * Author URI:        https://www.overcast.is/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       askell-registration
 * Domain Path:       /languages
 *
 * @package           askell-registration
 */

/**
 * Include the main class
 */
require_once 'classes/class-main.php';
$askell = new Askell\Main();

require_once 'classes/class-wprest.php';
$askell_wprest = new Askell\WpRest();

require_once 'classes/class-askellapi.php';
$askell_askellapi = new Askell\AskellApi();

/**
 * Register the deactivation hook for the plugin.
 */
register_deactivation_hook( __FILE__, 'askell_registration_deactivate' );

/**
 * Set the deactivation hook function for the plugin
 */
function askell_registration_deactivate() {
	$timestamp = wp_next_scheduled( 'askell_sync_cron' );
	wp_unschedule_event( $timestamp, 'askell_sync_cron' );
}
