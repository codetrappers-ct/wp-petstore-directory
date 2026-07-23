<?php
/**
 * Settings storage and (later) admin page.
 *
 * @package WP_Petstore_Directory
 */

namespace WP_Petstore_Directory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the plugin's options: the API base URL and the default status filter.
 *
 * This scaffold provides storage, defaults and typed getters. The admin page
 * UI (Settings API fields, sanitization, nonce) is added in a later step.
 */
class Settings {

	const OPTION_KEY = 'wppd_settings';

	const DEFAULT_API_BASE_URL = 'https://petstore.swagger.io/v2';
	const DEFAULT_STATUS       = 'available';

	/**
	 * Statuses the Petstore API recognises for findByStatus.
	 *
	 * Whitelisted because the API is case-sensitive and returns an empty 200
	 * (not an error) for anything unrecognised — free text would silently
	 * produce blank tables.
	 *
	 * @return array<string,string> value => human label.
	 */
	public static function allowed_statuses() {
		return array(
			'available' => __( 'Available', 'wp-petstore-directory' ),
			'pending'   => __( 'Pending', 'wp-petstore-directory' ),
			'sold'      => __( 'Sold', 'wp-petstore-directory' ),
		);
	}

	/**
	 * Default option values.
	 *
	 * @return array<string,string>
	 */
	public static function defaults() {
		return array(
			'api_base_url'   => self::DEFAULT_API_BASE_URL,
			'default_status' => self::DEFAULT_STATUS,
		);
	}

	/**
	 * Seed defaults on activation (without clobbering existing values).
	 */
	public static function seed_defaults() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, self::defaults() );
		}
	}

	/**
	 * All settings, merged over defaults.
	 *
	 * @return array<string,string>
	 */
	public function all() {
		$stored = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	/**
	 * Configured API base URL.
	 *
	 * @return string
	 */
	public function get_api_base_url() {
		return untrailingslashit( $this->all()['api_base_url'] );
	}

	/**
	 * Configured default status filter.
	 *
	 * @return string
	 */
	public function get_default_status() {
		$status = $this->all()['default_status'];
		return array_key_exists( $status, self::allowed_statuses() ) ? $status : self::DEFAULT_STATUS;
	}

	/**
	 * Register admin hooks. Filled in when the settings page lands.
	 */
	public function register_hooks() {
		// Settings page registration is added in the settings-page step.
	}
}
