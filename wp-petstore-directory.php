<?php
/**
 * Plugin Name:       WP Petstore Directory
 * Description:       Pulls pet listings from the Swagger Petstore API and displays them as a table via a custom Elementor widget.
 * Version:           0.1.0
 * Author:            CodeTrappers
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-petstore-directory
 * Requires PHP:      7.4
 * Requires at least: 6.0
 *
 * @package WP_Petstore_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'WPPD_VERSION', '0.1.0' );
define( 'WPPD_PLUGIN_FILE', __FILE__ );
define( 'WPPD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPPD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation hook.
 *
 * No custom tables or CPTs are needed, so activation is intentionally minimal:
 * we only seed default options so the settings page has sane values on first
 * load and the widget can render without the user visiting settings first.
 */
function wppd_activate() {
	require_once WPPD_PLUGIN_DIR . 'includes/class-settings.php';
	WP_Petstore_Directory\Settings::seed_defaults();
}
register_activation_hook( __FILE__, 'wppd_activate' );

/**
 * Deactivation hook.
 *
 * Flush cached API responses so a re-activation starts fresh. Options are left
 * in place (removed on uninstall) so settings survive a deactivate/reactivate.
 */
function wppd_deactivate() {
	require_once WPPD_PLUGIN_DIR . 'includes/class-api-client.php';
	WP_Petstore_Directory\Api_Client::flush_all_caches();
}
register_deactivation_hook( __FILE__, 'wppd_deactivate' );

/**
 * Bootstrap the plugin once all plugins are loaded (so we can detect Elementor).
 */
function wppd_bootstrap() {
	require_once WPPD_PLUGIN_DIR . 'includes/class-plugin.php';
	WP_Petstore_Directory\Plugin::instance();
}
add_action( 'plugins_loaded', 'wppd_bootstrap' );
