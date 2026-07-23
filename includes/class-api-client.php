<?php
/**
 * Petstore API client with transient caching.
 *
 * @package WP_Petstore_Directory
 */

namespace WP_Petstore_Directory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches and normalizes pet listings, caching responses in transients.
 *
 * This scaffold provides the cache plumbing and flush helpers. The actual
 * fetch + normalization is implemented in the API-client step.
 */
class Api_Client {

	/** Prefix for every transient this plugin writes. */
	const CACHE_PREFIX = 'wppd_pets_';

	/** Fresh window: how long before we try the network again. */
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Settings dependency.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings component.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Build the transient key for a given base URL + status pair.
	 *
	 * Keyed only on what changes the fetch, so display-only controls
	 * (rows-per-page, colours) never invalidate the cache.
	 *
	 * @param string $base_url API base URL.
	 * @param string $status   Status filter.
	 * @return string
	 */
	public static function cache_key( $base_url, $status ) {
		return self::CACHE_PREFIX . md5( $base_url . '|' . $status );
	}

	/**
	 * Invalidate this plugin's cached responses. Called when settings change.
	 */
	public static function flush_all_caches() {
		global $wpdb;

		// Transient option names are prefixed by WordPress; match both the value
		// and timeout rows for any key we created.
		$like = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB

		$like_timeout = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_timeout ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Fetch normalized pets for a status. Implemented in the API-client step.
	 *
	 * @param string $status Status filter.
	 * @return array|\WP_Error
	 */
	public function get_pets( $status ) {
		return new \WP_Error( 'wppd_not_implemented', __( 'Pet fetching is not implemented yet.', 'wp-petstore-directory' ) );
	}
}
