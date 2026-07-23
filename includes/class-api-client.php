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
 * Caching strategy (stale-while-error), per PLAN.md:
 *  - A short-lived "fresh" transient (CACHE_TTL) answers most reads with no
 *    network call.
 *  - A long-lived "fallback" transient (FALLBACK_TTL) holds the last-good
 *    payload. When the network fails, we serve the fallback instead of an error.
 *  - The graceful error is only surfaced on a cold cache (no fallback yet).
 *
 * The cache key depends only on base URL + status — the two inputs that change
 * the fetch. Display-only widget controls (rows-per-page, colours) never
 * invalidate it.
 */
class Api_Client {

	/** Prefix for every transient this plugin writes. */
	const CACHE_PREFIX = 'wppd_pets_';

	/** Extra marker for the long-lived fallback copy (still under CACHE_PREFIX). */
	const FALLBACK_MARKER = 'fb_';

	/** Fresh window: how long before we try the network again. */
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/** How long the last-good fallback copy survives for stale-while-error. */
	const FALLBACK_TTL = 7 * DAY_IN_SECONDS;

	/** Request timeout in seconds (observed API latency is ~1–2s). */
	const REQUEST_TIMEOUT = 8;

	/** Path appended to the configured base URL. */
	const FIND_BY_STATUS_PATH = '/pet/findByStatus';

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
	 * Build the "fresh" transient key for a base URL + status pair.
	 *
	 * @param string $base_url API base URL.
	 * @param string $status   Status filter.
	 * @return string
	 */
	public static function cache_key( $base_url, $status ) {
		return self::CACHE_PREFIX . md5( $base_url . '|' . $status );
	}

	/**
	 * Build the long-lived fallback key for a base URL + status pair.
	 *
	 * @param string $base_url API base URL.
	 * @param string $status   Status filter.
	 * @return string
	 */
	public static function fallback_key( $base_url, $status ) {
		return self::CACHE_PREFIX . self::FALLBACK_MARKER . md5( $base_url . '|' . $status );
	}

	/**
	 * Fetch normalized pets for a status, using the cache where possible.
	 *
	 * Return contract:
	 *  - array of normalized pets on success (possibly empty — an empty result
	 *    is a valid state the API returns as HTTP 200, not an error).
	 *  - WP_Error only when the network fails AND no fallback copy exists.
	 *
	 * @param string $status Status filter (expected to be pre-validated).
	 * @return array|\WP_Error
	 */
	public function get_pets( $status ) {
		$base_url     = $this->settings->get_api_base_url();
		$fresh_key    = self::cache_key( $base_url, $status );
		$fallback_key = self::fallback_key( $base_url, $status );

		// 1) Fresh cache hit — no network call. An empty array is a real cached
		// value here (get_transient returns false only when absent/expired).
		$fresh = get_transient( $fresh_key );
		if ( false !== $fresh ) {
			return $fresh;
		}

		// 2) Fresh copy is stale/absent — go to the network.
		$url      = $base_url . self::FIND_BY_STATUS_PATH . '?status=' . rawurlencode( $status );
		$response = $this->request( $url );

		if ( is_wp_error( $response ) ) {
			// 3) Network failed — serve the last-good copy if we have one.
			$fallback = get_transient( $fallback_key );
			if ( false !== $fallback ) {
				return $fallback;
			}
			// Cold cache: nothing to fall back on, surface the error gracefully.
			return $response;
		}

		// 4) Success — normalize, refresh both the fresh and fallback copies.
		$pets = $this->normalize_pets( $response );
		set_transient( $fresh_key, $pets, self::CACHE_TTL );
		set_transient( $fallback_key, $pets, self::FALLBACK_TTL );

		return $pets;
	}

	/**
	 * Perform the HTTP GET and decode the JSON body.
	 *
	 * @param string $url Fully-qualified request URL.
	 * @return array|\WP_Error Decoded array on success, WP_Error otherwise.
	 */
	private function request( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new \WP_Error(
				'wppd_http_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'The Petstore API returned an unexpected response (HTTP %d).', 'wp-petstore-directory' ),
					$code
				)
			);
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $decoded ) ) {
			return new \WP_Error(
				'wppd_bad_json',
				__( 'The Petstore API returned an unreadable response.', 'wp-petstore-directory' )
			);
		}

		return $decoded;
	}

	/**
	 * Normalize a raw API array into a predictable, escaped-at-output shape.
	 *
	 * @param array $raw Decoded API response.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_pets( array $raw ) {
		$pets = array();
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$pets[] = $this->normalize_pet( $item );
		}
		return $pets;
	}

	/**
	 * Normalize a single pet record.
	 *
	 * The public API is full of dirty data: missing names/categories, category
	 * as a nested object that may be absent, and photoUrls that are frequently
	 * not valid URLs ("kangs url", "string"). We defend against all of it and
	 * leave empty strings where a value is unusable — the template decides how
	 * to present the gap.
	 *
	 * @param array $item Raw pet record.
	 * @return array<string,mixed>
	 */
	private function normalize_pet( array $item ) {
		$name = ( isset( $item['name'] ) && is_string( $item['name'] ) )
			? sanitize_text_field( $item['name'] )
			: '';

		$status = ( isset( $item['status'] ) && is_string( $item['status'] ) )
			? sanitize_text_field( $item['status'] )
			: '';

		$category = '';
		if ( isset( $item['category'] ) && is_array( $item['category'] )
			&& isset( $item['category']['name'] ) && is_string( $item['category']['name'] ) ) {
			$category = sanitize_text_field( $item['category']['name'] );
		}

		$photo_url = '';
		if ( isset( $item['photoUrls'] ) && is_array( $item['photoUrls'] ) ) {
			$photo_url = $this->first_valid_url( $item['photoUrls'] );
		}

		return array(
			'id'        => isset( $item['id'] ) ? (int) $item['id'] : 0,
			'name'      => $name,
			'category'  => $category,
			'status'    => $status,
			'photo_url' => $photo_url,
		);
	}

	/**
	 * Return the first entry that is a genuine absolute http(s) URL.
	 *
	 * @param array $urls Candidate strings from the API.
	 * @return string Valid URL, or '' if none qualify.
	 */
	private function first_valid_url( array $urls ) {
		foreach ( $urls as $url ) {
			if ( ! is_string( $url ) ) {
				continue;
			}
			$url = trim( $url );
			if ( '' === $url ) {
				continue;
			}
			if ( filter_var( $url, FILTER_VALIDATE_URL ) && preg_match( '#^https?://#i', $url ) ) {
				return $url;
			}
		}
		return '';
	}

	/**
	 * Invalidate this plugin's cached responses (fresh + fallback). Called when
	 * settings change and on deactivation.
	 */
	public static function flush_all_caches() {
		global $wpdb;

		$like = $wpdb->esc_like( '_transient_' . self::CACHE_PREFIX ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB

		$like_timeout = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_PREFIX ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_timeout ) ); // phpcs:ignore WordPress.DB
	}
}
