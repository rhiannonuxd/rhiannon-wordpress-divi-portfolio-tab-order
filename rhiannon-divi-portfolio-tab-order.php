<?php
/**
 * Plugin Name: Rhiannon - Divi Portfolio Tab Order
 * Description: Sets a custom order for category tabs in Divi Filterable Portfolio modules.
 * Version: 1.0.1
 * Author: Rhiannon
 * License: GPL-2.0-or-later
 * Text Domain: rhiannon-divi-portfolio-tab-order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rhiannon_Divi_Portfolio_Tab_Order {
	private const OPTION_NAME = 'rhiannon_dpto_settings';
	private const SCRIPT_HANDLE = 'rhiannon-dpto-frontend';

	/**
	 * Default settings.
	 *
	 * @return array<string, string>
	 */
	private static function defaults(): array {
		return array(
			'target_class' => 'rhiannon-portfolio',
			'order'        => "user-experience\nproduct-management\nsoftware-development\ngraphic-design",
		);
	}

	/**
	 * Start the plugin.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_script' ) );
	}

	/**
	 * Add the settings page under Settings.
	 */
	public static function add_settings_page(): void {
		add_options_page(
			__( 'Rhiannon - Divi Portfolio Tab Order', 'rhiannon-divi-portfolio-tab-order' ),
			__( 'Divi Portfolio Tabs', 'rhiannon-divi-portfolio-tab-order' ),
			'manage_options',
			'rhiannon-divi-portfolio-tab-order',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public static function register_settings(): void {
		register_setting(
			'rhiannon_dpto_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'rhiannon_dpto_main_section',
			__( 'Portfolio filter settings', 'rhiannon-divi-portfolio-tab-order' ),
			array( __CLASS__, 'render_section_intro' ),
			'rhiannon-divi-portfolio-tab-order'
		);

		add_settings_field(
			'rhiannon_dpto_target_class',
			__( 'Target module CSS class', 'rhiannon-divi-portfolio-tab-order' ),
			array( __CLASS__, 'render_target_class_field' ),
			'rhiannon-divi-portfolio-tab-order',
			'rhiannon_dpto_main_section'
		);

		add_settings_field(
			'rhiannon_dpto_order',
			__( 'Category order', 'rhiannon-divi-portfolio-tab-order' ),
			array( __CLASS__, 'render_order_field' ),
			'rhiannon-divi-portfolio-tab-order',
			'rhiannon_dpto_main_section'
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $input Raw settings.
	 * @return array<string, string>
	 */
	public static function sanitize_settings( $input ): array {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		$target_class = isset( $input['target_class'] ) ? (string) $input['target_class'] : $defaults['target_class'];
		$target_class = ltrim( trim( $target_class ), '.' );
		$target_class = sanitize_html_class( $target_class, $defaults['target_class'] );

		$order_raw = isset( $input['order'] ) ? (string) $input['order'] : $defaults['order'];
		$entries   = preg_split( '/[\r\n,]+/', $order_raw );
		$entries   = is_array( $entries ) ? $entries : array();
		$slugs     = array();

		foreach ( $entries as $entry ) {
			$slug = sanitize_title( trim( $entry ) );
			if ( '' !== $slug && 'all' !== $slug && ! in_array( $slug, $slugs, true ) ) {
				$slugs[] = $slug;
			}
		}

		return array(
			'target_class' => $target_class,
			'order'        => implode( "\n", $slugs ),
		);
	}

	/**
	 * Render settings-page intro.
	 */
	public static function render_section_intro(): void {
		echo '<p>' . esc_html__( 'Add the target class to a Divi Filterable Portfolio module, then list the category slugs in the order you want. The All tab stays first automatically.', 'rhiannon-divi-portfolio-tab-order' ) . '</p>';
	}

	/**
	 * Get merged settings.
	 *
	 * @return array<string, string>
	 */
	private static function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, array() );
		$saved = is_array( $saved ) ? $saved : array();
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Render CSS class field.
	 */
	public static function render_target_class_field(): void {
		$settings = self::get_settings();
		?>
		<input
			type="text"
			class="regular-text code"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[target_class]"
			value="<?php echo esc_attr( $settings['target_class'] ); ?>"
			spellcheck="false"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter one class without a leading period. Add this same class in the module’s Advanced → CSS ID & Classes → CSS Class field.', 'rhiannon-divi-portfolio-tab-order' ); ?>
		</p>
		<?php
	}

	/**
	 * Render category order field.
	 */
	public static function render_order_field(): void {
		$settings = self::get_settings();
		?>
		<textarea
			class="large-text code"
			rows="8"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[order]"
			spellcheck="false"
		><?php echo esc_textarea( $settings['order'] ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Use one category name or slug per line. Any unlisted categories remain after these, in Divi’s existing order.', 'rhiannon-divi-portfolio-tab-order' ); ?>
		</p>
		<?php
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Rhiannon - Divi Portfolio Tab Order', 'rhiannon-divi-portfolio-tab-order' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'rhiannon_dpto_settings_group' );
				do_settings_sections( 'rhiannon-divi-portfolio-tab-order' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Load the front-end sorter.
	 */
	public static function enqueue_frontend_script(): void {
		if ( is_admin() ) {
			return;
		}

		$settings     = self::get_settings();
		$target_class = sanitize_html_class( $settings['target_class'] );
		$order         = preg_split( '/[\r\n,]+/', $settings['order'] );
		$order         = is_array( $order ) ? array_values( array_filter( array_map( 'sanitize_title', $order ) ) ) : array();

		if ( '' === $target_class || empty( $order ) ) {
			return;
		}

		$script_path = plugin_dir_path( __FILE__ ) . 'assets/js/frontend.js';
		$version     = file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1.0.0';

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			plugin_dir_url( __FILE__ ) . 'assets/js/frontend.js',
			array(),
			$version,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'RHIANNON_DPTO_CONFIG',
			array(
				'targetClass' => $target_class,
				'order'       => $order,
			)
		);
	}
}

Rhiannon_Divi_Portfolio_Tab_Order::init();

