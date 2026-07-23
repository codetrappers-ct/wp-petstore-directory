<?php
/**
 * Uninstall cleanup.
 *
 * Runs when the plugin is deleted from the Plugins screen. Removes the option
 * and any cached transients so nothing is left behind. Settings deliberately
 * survive deactivation (handled elsewhere) — only a full uninstall clears them.
 *
 * @package WP_Petstore_Directory
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-api-client.php';

delete_option( \WP_Petstore_Directory\Settings::OPTION_KEY );
\WP_Petstore_Directory\Api_Client::flush_all_caches();
