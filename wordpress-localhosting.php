<?php
/**
 * Plugin bootstrap file
 *
 * @link              https://github.com/Magnacarter/wordpress-localhosting
 * @since             1.0.0
 * @package           MAGNACARTER\WordPress_Localhosting\
 *
 * @wordpress-plugin
 * Plugin Name:     WordPress Localhosting
 * Plugin URI:      https://github.com/Magnacarter/wordpress-localhosting
 * Description:     Pull in images from the production servers uploads dir
 * Version:         1.0.0
 * Author:          MAGNACARTER
 * Author URI:      https://adamkristopher.co
 * Text Domain:     wordpress-localhosting
 * Domain Path:     /languages
 */
namespace MAGNACARTER\WordPress_Localhosting;

new Prod_Images();

class Prod_Images {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'options_page' ) );
		add_filter( 'wp_get_attachment_url', array( $this, 'filter_image_url' ) );
	}

	/**
	 * Filter image url
	 * REPLACE IMAGES URLS WITH PRODUCTION IMAGE URLS
	 *
	 * @param string $url
	 * @return string $url
	 * @action wp_get_attachment_url
	 */
	public function filter_image_url( $url ) {
		// Get options from WP options table
		$options = get_option( 'prod_images_options' );
		if ( ! isset( $options ) ) {
			return;
		}
		$liveurl  = trim( $options['liveurl'] );
		$localurl = trim( $options['localurl'] );
		$url      = str_replace( $localurl, $liveurl, $url );
		return $url;
	}

	/**
	 * Setttings Init
	 *
	 * Register settings for our custom plugin page
	 * @return void
	 */
	public function settings_init() {
		// register a new setting for "prod_images" page
		register_setting( 'prod_images', 'prod_images_options' );
		// register a new section in the "prod_images" page
		add_settings_section(
			'prod_images_dev_section',
			__( 'Get Production Images Locally.', 'prod_images' ),
			array( $this, 'dev_callback' ),
			'prod_images'
		);
		// register a new field in the "prod_images_dev_section" section, inside the "prod_images" page
		add_settings_field(
			'prod_images_field', // as of WP 4.6 this value is used only internally
			// use $args' label_for to populate the id inside the callback
			__( 'URLS', 'prod_images' ),
			array( $this, 'user_input_callback' ),
			'prod_images',
			'prod_images_dev_section',
			[
				'liveurl'     => 'prod_url',
				'localurl'    => 'local_url',
				'custom_data' => 'custom',
			]
		);
	}

	/**
	 * Dev callback
	 *
	 * @param array
	 * @return void
	 */
	public function dev_callback( $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Use trailing slashes on urls.', 'prod_images' ); ?></p>
		<?php
	}

	/**
	 * field callbacks can accept an $args parameter, which is an array.
	 * $args is defined at the add_settings_field() function.
	 * wordpress has magic interaction with the following keys: label_for, class.
	 * the "label_for" key value is used for the "for" attribute of the <label>.
	 * the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array
	 * @return void
	 */
	public function user_input_callback( $args ) {
		// get the value of the setting we've registered with register_setting()
		$options = get_option( 'prod_images_options' );
		$live    = trim( $options['liveurl'] );
		$local   = trim( $options['localurl'] );
		?>
			<p>Enter Live URL</p>
			<input type="text" name="prod_images_options[liveurl]" value="<?php echo isset( $live ) ? ( $live ) : (''); ?>">
			<p>Enter Local URL</p>
			<input type="text" name="prod_images_options[localurl]" value="<?php echo isset( $local ) ? ( $local ) : (''); ?>">
		<?php
	}

	/**
	 * options page
	 *
	 * @return void
	 */
	public function options_page() {
		// add top level menu page
		add_menu_page(
			'Production Images',
			'Production Images Options',
			'manage_options',
			'prod_images',
			array( $this, 'prod_images_page_html' )
		);
	}

	/**
	 * prod images page html
	 *
	 * @return void
	 */
	public function prod_images_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/**
		 * add error/update messages
		 * check if the user have submitted the settings
		 * wordpress will add the "settings-updated" $_GET parameter to the url
		 */
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'prod_images_messages', 'prod_images_message', __( 'Settings Saved', 'prod_images' ), 'updated' );
		}
		// show error/update messages
		settings_errors( 'prod_images_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "prod_images"
				settings_fields( 'prod_images' );
				// output setting sections and their fields
				// (sections are registered for "prod_images", each field is registered to a specific section)
				do_settings_sections( 'prod_images' );
				// output save settings button
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}
}
