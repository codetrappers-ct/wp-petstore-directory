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
 * Registers the plugin's custom Elementor category and the Pet Table widget.
 *
 * This scaffold wires the object up; the category and widget registration are
 * implemented in the Elementor-widget step.
 */
class Elementor_Manager {

	const CATEGORY_SLUG = 'petstore';

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
	 * Register Elementor hooks. Category + widget registration land next.
	 */
	public function register_hooks() {
		// elementor/elements/categories_registered + elementor/widgets/register
		// are added in the Elementor-widget step.
	}
}
