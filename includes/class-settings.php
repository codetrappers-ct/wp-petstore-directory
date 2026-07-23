<?php
/**
 * Settings storage and admin page.
 *
 * @package WP_Petstore_Directory
 */

namespace WP_Petstore_Directory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Owns the plugin's options (API base URL + default status filter) and renders
 * the admin settings page via the WordPress Settings API.
 *
 * Using the Settings API is a deliberate choice: it provides the nonce
 * (settings_fields()) and per-option sanitization for free, giving a smaller
 * custom security surface than a hand-rolled form + handler.
 */
class Settings {

	const OPTION_KEY   = 'wppd_settings';
	const OPTION_GROUP = 'wppd_settings_group';
	const PAGE_SLUG    = 'wp-petstore-directory';
	const SECTION_ID   = 'wppd_main_section';

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
	 * Configured API base URL (no trailing slash).
	 *
	 * @return string
	 */
	public function get_api_base_url() {
		return untrailingslashit( $this->all()['api_base_url'] );
	}

	/**
	 * Configured default status filter (guaranteed to be whitelisted).
	 *
	 * @return string
	 */
	public function get_default_status() {
		$status = $this->all()['default_status'];
		return array_key_exists( $status, self::allowed_statuses() ) ? $status : self::DEFAULT_STATUS;
	}

	/**
	 * Register admin hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( WPPD_PLUGIN_FILE ),
			array( $this, 'add_settings_link' )
		);
	}

	/**
	 * Add the settings page under the Settings menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Petstore Directory', 'wp-petstore-directory' ),
			__( 'Petstore Directory', 'wp-petstore-directory' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a convenience "Settings" link on the Plugins list row.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url  = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wp-petstore-directory' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Register the setting, section and fields.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			self::SECTION_ID,
			__( 'API Configuration', 'wp-petstore-directory' ),
			array( $this, 'render_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'api_base_url',
			__( 'API Base URL', 'wp-petstore-directory' ),
			array( $this, 'render_api_base_url_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID,
			array( 'label_for' => 'wppd_api_base_url' )
		);

		add_settings_field(
			'default_status',
			__( 'Default Status Filter', 'wp-petstore-directory' ),
			array( $this, 'render_default_status_field' ),
			self::PAGE_SLUG,
			self::SECTION_ID,
			array( 'label_for' => 'wppd_default_status' )
		);
	}

	/**
	 * Sanitize submitted settings and flush caches if the base URL changed.
	 *
	 * @param mixed $input Raw submitted value.
	 * @return array<string,string> Clean settings.
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$existing = $this->all();
		$clean    = self::defaults();

		// API base URL: must be a valid http(s) URL, else keep the previous value
		// and warn (rather than silently reverting to the packaged default).
		$raw_url = isset( $input['api_base_url'] ) ? trim( (string) $input['api_base_url'] ) : '';
		$url     = esc_url_raw( $raw_url, array( 'http', 'https' ) );
		if ( '' !== $url && preg_match( '#^https?://#i', $url ) ) {
			$clean['api_base_url'] = untrailingslashit( $url );
		} else {
			$clean['api_base_url'] = $existing['api_base_url'];
			add_settings_error(
				self::OPTION_KEY,
				'wppd_bad_url',
				__( 'API Base URL must be a valid http(s) URL. Kept the previous value.', 'wp-petstore-directory' ),
				'error'
			);
		}

		// Default status: must be one of the whitelisted values.
		$status = isset( $input['default_status'] ) ? sanitize_key( $input['default_status'] ) : '';
		$clean['default_status'] = array_key_exists( $status, self::allowed_statuses() )
			? $status
			: $existing['default_status'];

		// Changing the base URL invalidates every cached payload (they were
		// fetched from the old host). Flush so stale data can't linger.
		if ( untrailingslashit( $clean['api_base_url'] ) !== untrailingslashit( $existing['api_base_url'] ) ) {
			Api_Client::flush_all_caches();
		}

		return $clean;
	}

	/**
	 * Section intro copy.
	 */
	public function render_section_intro() {
		echo '<p>' . esc_html__(
			'Configure how the plugin talks to the Swagger Petstore API. These values are used by the Pet Table Elementor widget.',
			'wp-petstore-directory'
		) . '</p>';
	}

	/**
	 * Render the API base URL text field.
	 */
	public function render_api_base_url_field() {
		$value = $this->get_api_base_url();
		?>
		<input type="url"
			id="wppd_api_base_url"
			class="regular-text code"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[api_base_url]"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="<?php echo esc_attr( self::DEFAULT_API_BASE_URL ); ?>" />
		<p class="description">
			<?php
			printf(
				/* translators: %s: the API path that is appended automatically. */
				esc_html__( 'Base URL only, without a trailing slash. The plugin appends %s automatically.', 'wp-petstore-directory' ),
				'<code>/pet/findByStatus</code>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the default status select field.
	 */
	public function render_default_status_field() {
		$current = $this->get_default_status();
		?>
		<select id="wppd_default_status"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_status]">
			<?php foreach ( self::allowed_statuses() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Used when a Pet Table widget is set to inherit the default. Individual widgets can override this.', 'wp-petstore-directory' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the settings page shell.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP ); // Nonce + option_page fields.
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
