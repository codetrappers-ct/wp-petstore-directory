<?php
/**
 * Plugin bootstrap.
 *
 * @package WP_Petstore_Directory
 */

namespace WP_Petstore_Directory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the plugin's components together. Kept deliberately thin: it loads
 * dependencies and registers hooks, delegating real work to the components.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings component.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * API client component.
	 *
	 * @var Api_Client
	 */
	private $api_client;

	/**
	 * Retrieve (and lazily create) the singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load dependencies and register hooks.
	 */
	private function __construct() {
		$this->load_dependencies();

		$this->settings   = new Settings();
		$this->api_client = new Api_Client( $this->settings );

		$this->settings->register_hooks();

		// Elementor integration is optional: only wire it if Elementor is present.
		// Elementor loads alphabetically before this plugin, so 'elementor/loaded'
		// has usually fired already by the time we bootstrap — hooking it now would
		// be too late and the widget would never register. Detect the already-loaded
		// case and initialise immediately; otherwise fall back to the hook (covers
		// the rare case where this plugin loads first).
		if ( did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ) ) {
			$this->init_elementor();
		} else {
			add_action( 'elementor/loaded', array( $this, 'init_elementor' ) );
		}
	}

	/**
	 * Require the component classes.
	 */
	private function load_dependencies() {
		require_once WPPD_PLUGIN_DIR . 'includes/class-settings.php';
		require_once WPPD_PLUGIN_DIR . 'includes/class-api-client.php';
	}

	/**
	 * Boot the Elementor integration once Elementor itself has loaded.
	 */
	public function init_elementor() {
		require_once WPPD_PLUGIN_DIR . 'includes/class-elementor.php';
		$elementor = new Elementor_Manager( $this->api_client, $this->settings );
		$elementor->register_hooks();
	}

	/**
	 * Expose the API client (used by the Elementor widget).
	 *
	 * @return Api_Client
	 */
	public function api_client() {
		return $this->api_client;
	}

	/**
	 * Expose the settings component.
	 *
	 * @return Settings
	 */
	public function settings() {
		return $this->settings;
	}
}
