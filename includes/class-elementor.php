<?php
/**
 * Elementor integration: custom category + widget registration.
 *
 * @package WP_Petstore_Directory
 */

namespace WP_Petstore_Directory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin's custom Elementor category, the Pet Table widget, and
 * the widget's front-end assets.
 */
class Elementor_Manager {

	const CATEGORY_SLUG = 'petstore';
	const ASSET_HANDLE  = 'wppd-pet-table';

	/**
	 * API client dependency.
	 *
	 * @var Api_Client
	 */
	private $api_client;

	/**
	 * Settings dependency.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Api_Client $api_client API client.
	 * @param Settings   $settings   Settings.
	 */
	public function __construct( Api_Client $api_client, Settings $settings ) {
		$this->api_client = $api_client;
		$this->settings   = $settings;
	}

	/**
	 * Register Elementor + asset hooks.
	 */
	public function register_hooks() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register (not enqueue) assets so the widget can declare them as
		// dependencies — Elementor then loads them only on pages that use it.
		// wp_enqueue_scripts also fires for the editor preview iframe.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
	}

	/**
	 * Add the custom "Petstore" category to the editor panel.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor categories manager.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY_SLUG,
			array(
				'title' => __( 'Petstore', 'wp-petstore-directory' ),
				'icon'  => 'fa fa-paw',
			)
		);
	}

	/**
	 * Register the Pet Table widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once WPPD_PLUGIN_DIR . 'widgets/class-pet-table-widget.php';
		$widgets_manager->register( new Pet_Table_Widget() );
	}

	/**
	 * Register the widget's CSS and JS handles.
	 */
	public function register_assets() {
		wp_register_style(
			self::ASSET_HANDLE,
			WPPD_PLUGIN_URL . 'assets/css/pet-table.css',
			array(),
			WPPD_VERSION
		);

		wp_register_script(
			self::ASSET_HANDLE,
			WPPD_PLUGIN_URL . 'assets/js/pet-table.js',
			array(),
			WPPD_VERSION,
			true
		);
	}
}
