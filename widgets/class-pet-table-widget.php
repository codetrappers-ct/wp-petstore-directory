<?php
/**
 * Pet Table Elementor widget.
 *
 * @package WP_Petstore_Directory
 */

namespace WP_Petstore_Directory;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Petstore listings as a table, with per-instance content + style
 * controls. Data and caching are delegated to Api_Client; this class is only
 * concerned with controls and presentation.
 */
class Pet_Table_Widget extends Widget_Base {

	/**
	 * Machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wppd_pet_table';
	}

	/**
	 * Editor title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Pet Table', 'wp-petstore-directory' );
	}

	/**
	 * Editor icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-table';
	}

	/**
	 * Custom category placement.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( Elementor_Manager::CATEGORY_SLUG );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'pet', 'petstore', 'table', 'directory', 'listing' );
	}

	/**
	 * Front-end style dependency.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( Elementor_Manager::ASSET_HANDLE );
	}

	/**
	 * Front-end script dependency.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( Elementor_Manager::ASSET_HANDLE );
	}

	/**
	 * Register content + style controls.
	 */
	protected function register_controls() {

		// --- Content ---------------------------------------------------------
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'wp-petstore-directory' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		// Status override — the "Inherit" default resolves the global-vs-widget
		// tension: the admin default wins unless a widget explicitly overrides.
		$status_options = array( '' => __( 'Inherit (use global default)', 'wp-petstore-directory' ) );
		foreach ( Settings::allowed_statuses() as $value => $label ) {
			$status_options[ $value ] = $label;
		}

		$this->add_control(
			'status',
			array(
				'label'   => __( 'Status filter', 'wp-petstore-directory' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $status_options,
			)
		);

		// Rows-per-page is behavioural, not stylistic, so it lives here (not in
		// Style) — and it drives client-side pagination since the API has no
		// server-side limit/offset.
		$this->add_control(
			'rows_per_page',
			array(
				'label'       => __( 'Rows per page', 'wp-petstore-directory' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 100,
				'step'        => 1,
				'default'     => 10,
				'description' => __( 'Pagination is applied in the browser.', 'wp-petstore-directory' ),
			)
		);

		$this->add_control(
			'show_photo',
			array(
				'label'        => __( 'Show photo column', 'wp-petstore-directory' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'wp-petstore-directory' ),
				'label_off'    => __( 'No', 'wp-petstore-directory' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// --- Style -----------------------------------------------------------
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Table Style', 'wp-petstore-directory' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// Real widget extension: these drive live CSS via selectors, not inline
		// PHP — so the editor preview updates instantly.
		$this->add_control(
			'border_color',
			array(
				'label'     => __( 'Border color', 'wp-petstore-directory' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wppd-pet-table table' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .wppd-pet-table th'    => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .wppd-pet-table td'    => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'header_bg',
			array(
				'label'     => __( 'Header background', 'wp-petstore-directory' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wppd-pet-table thead th' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'header_text_color',
			array(
				'label'     => __( 'Header text color', 'wp-petstore-directory' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .wppd-pet-table thead th' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Resolve the effective status: widget override, else the global default.
	 *
	 * @param array $settings Widget settings.
	 * @return string
	 */
	private function resolve_status( array $settings ) {
		$chosen  = isset( $settings['status'] ) ? $settings['status'] : '';
		$allowed = Settings::allowed_statuses();

		if ( '' === $chosen || ! array_key_exists( $chosen, $allowed ) ) {
			return Plugin::instance()->settings()->get_default_status();
		}
		return $chosen;
	}

	/**
	 * Render the widget on the front end (and in the editor preview).
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$plugin   = Plugin::instance();

		$status       = $this->resolve_status( $settings );
		$allowed      = Settings::allowed_statuses();
		$is_edit_mode = $this->is_edit_mode();

		$rows_per_page = isset( $settings['rows_per_page'] ) ? (int) $settings['rows_per_page'] : 10;
		if ( $rows_per_page < 1 ) {
			$rows_per_page = 10;
		}
		$show_photo = ! empty( $settings['show_photo'] ) && 'yes' === $settings['show_photo'];

		$pets = $plugin->api_client()->get_pets( $status );

		// Error state — API unreachable and no cached fallback.
		if ( is_wp_error( $pets ) ) {
			$this->log_error( $pets, $status );

			// Visitors see a generic, reassuring message; the page builder gets
			// the specific reason so config can be fixed without reading logs.
			$message = $is_edit_mode
				? sprintf(
					/* translators: 1: error code, 2: error detail. */
					__( 'Pet Table could not load pets (%1$s: %2$s). Check the API Base URL under Settings → Petstore Directory.', 'wp-petstore-directory' ),
					$pets->get_error_code(),
					$pets->get_error_message()
				)
				: __( 'The pet directory is temporarily unavailable. Please try again later.', 'wp-petstore-directory' );

			$this->render_message( 'wppd-error', $message );
			return;
		}

		// Variables consumed by the template, prefixed so the partial never
		// relies on (or leaks) unprefixed names — presentation and escaping
		// happen there.
		$wppd_pets          = $pets;
		$wppd_rows_per_page = $rows_per_page;
		$wppd_show_photo    = $show_photo;
		$wppd_status_label  = isset( $allowed[ $status ] ) ? $allowed[ $status ] : $status;
		$wppd_is_edit_mode  = $is_edit_mode;

		include WPPD_PLUGIN_DIR . 'templates/pet-table.php';
	}

	/**
	 * Whether we are rendering inside the Elementor editor/preview.
	 *
	 * @return bool
	 */
	private function is_edit_mode() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		$elementor = \Elementor\Plugin::instance();
		return isset( $elementor->editor ) && $elementor->editor->is_edit_mode();
	}

	/**
	 * Log an API failure server-side (only when debugging), without exposing
	 * internals to visitors.
	 *
	 * @param \WP_Error $error  The error returned by the client.
	 * @param string    $status Status that was requested.
	 */
	private function log_error( $error, $status ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'[wp-petstore-directory] get_pets("%s") failed: %s (%s)',
					$status,
					$error->get_error_message(),
					$error->get_error_code()
				)
			);
		}
	}

	/**
	 * Render a single-message box (error or empty state) with escaped output.
	 *
	 * @param string $class   Extra CSS class on the paragraph.
	 * @param string $message Human-readable, unescaped message.
	 */
	private function render_message( $class, $message ) {
		printf(
			'<div class="wppd-pet-table wppd-pet-table--message"><p class="%1$s">%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message )
		);
	}
}
