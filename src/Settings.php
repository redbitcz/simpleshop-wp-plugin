<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use Redbit\SimpleShop\WpPlugin\Vyfakturuj\VyfakturujAPI;

/**
 * CMB2 Theme Options
 * @version 0.1.0
 * @property-read string $key
 * @property-read string $metabox_id
 * @property-read string $title
 * @property-read string $options_page
 */
class Settings {

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Option key, and option page slug
	 * @var string
	 */
	private $key = 'ssc_options';

	/**
	 * Options page metabox id
	 * @var string
	 */
	private $metabox_id = 'ssc_option_metabox';
	/**
	 * @var Loader
	 */
	private $loader;

	/**
	 * Constructor
	 * @since 0.1.0
	 *
	 * @param Loader $loader
	 */
	public function __construct( Loader $loader ) {
		// Set our title
		$this->title = 'Settings';
		$this->register_hooks();
		$this->loader = $loader;
	}

	/**
	 * Initiate our hooks
	 * @since 0.1.0
	 */
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		add_filter( 'cmb2_render_disconnect_button', array( $this, 'field_type_disconnect_button' ), 10, 5 );
		add_action( 'admin_init', array( $this, 'maybe_disconnect_simpleshop' ) );
	}

	public function field_type_disconnect_button( $field, $escaped_value, $object_id, $object_type, $field_type_object ) { ?>
        <a href="<?php echo admin_url( 'admin.php?page=ssc_options&disconnect_simpleshop=1' ) ?>"><?php _e( 'Disconnect Simple Shop', 'simpleshop-cz' ); ?></a>
		<?php
	}

	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 * @since 0.1.0
	 */
	public function add_options_page() {
		$translatedTitle = __( $this->title, 'simpleshop-cz' );

		add_submenu_page(
			'simple_shop_settings',
			$translatedTitle,
			$translatedTitle,
			'manage_options',
			'admin.php?page=' . $this->key,
			array( $this, 'admin_page_display' )
		);

		$this->options_page = add_menu_page(
			$translatedTitle,
			$translatedTitle,
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' )
		);
		remove_menu_page( $this->key );
		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  0.1.0
	 */
	public function admin_page_display() {
		?>
        <div class="wrap cmb2-options-page <?php echo $this->key; ?>">
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
        </div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  0.1.0
	 */
	public function add_options_page_metabox() {

		// hook in our save notices
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

		$cmb = new_cmb2_box(
			array(
				'id'         => $this->metabox_id,
				'hookup'     => false,
				'cmb_styles' => true,
				'show_on'    => array(
					// These are important, don't remove
					'key'   => 'options-page',
					'value' => array( $this->key, ),
				),
			)
		);

		#
		# MAIL
		#
		$cmb->add_field(
			array(
				'name'       => 'Nastavení e-mailu, který se posílá novým členům:',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
				'type'       => 'title',
				'id'         => 'ssc_email_title',
			)
		);
		$cmb->add_field(
			array(
				'name'             => 'Poslat e-mail novému členovi?',
				'id'               => 'ssc_email_enable',
				'type'             => 'select',
				'show_option_none' => false,
				'classes_cb'       => array( $this, 'is_valid_api_keys' ),
				'default'          => '1',
				'options'          => array(
					'1' => __( 'Yes, send email to new member.', 'cmb2', 'simpleshop.cz', 'simpleshop-cz' ),
					'2' => __( 'No, doesn\'t send email to new members.', 'cmb2', 'simpleshop.cz', 'simpleshop-cz' ),
				),
			)
		);
		$cmb->add_field(
			array(
				'name'       => __( 'Email subject', 'simpleshop-cz' ),
//            'desc' => __('Najdete ho ve svém SimpleShop účtu v Nastavení -> WP Plugin','ssc'),
				'id'         => 'ssc_email_subject',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
				'type'       => 'text',
				'default'    => 'Byl Vám udělen přístup do členské sekce',
			)
		);


		$cmb->add_field(
			array(
				'name'       => __( 'Email message', 'simpleshop-cz' ),
				'desc'       => __( '<u>Povolené zástupné znaky:</u><br/>'
				                    . '<div style="font-style:normal;"><b>{login}</b> = login<br/>'
				                    . '<b>{password}</b> = heslo<br/>'
				                    . '<b>{login_url}</b> = adresa, na které je možné se přihlásit<br/>'
				                    . '<b>{pages}</b> = seznam stránek, do kterých má uživatel zakoupený přístup<br/>'
				                    . '<b>{mail}</b> = e-mail uživatele (většinou stejný jako login)<br/>'
				                    . '</div>'
				                    . '', 'simpleshop-cz' ),
				'id'         => 'ssc_email_text',
				'type'       => 'wysiwyg',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
				'default'    => 'Dobrý den,
byl udělen přístup do členské sekce.

Login: {login}
Heslo: {password}

Přihlásit se můžete na: ' . wp_login_url() . '

Váš zakoupený obsah:
{pages}

S pozdravem a přáním pěkného dne,
SimpleShop.cz - <i>S námi zvládne prodávat každý</i>',
			)
		);


		#
		# API
		#
		$cmb->add_field(
			array(
				'name' => 'Nastavení API - propojení s aplikací SimpleShop:',
//            'desc' => 'This is a title description',
//            'show_on_cb' => array($this,'is_valid_api_keys'),
				'type' => 'title',
				'id'   => 'ssc_api_title',
			)
		);

		// Set our CMB2 fields
		$cmb->add_field(
			array(
				'name' => __( 'Username (email)', 'simpleshop-cz' ),
				'desc' => __( 'Put email used for login to SimpleShop', 'simpleshop-cz' ),
				'id'   => 'ssc_api_email',
				'type' => 'text',
			)
		);

		$cmb->add_field(
			array(
				'name' => __( 'SimpleShop API Key', 'simpleshop-cz' ),
				'desc' => __( 'You found it at SimpleShop in Settings (Nastavení) -> WP Plugin', 'simpleshop-cz' ),
				'id'   => 'ssc_api_key',
				'type' => 'text',
			)
		);

		$cmb->add_field(
			array(
				'name'       => 'Obecná nastavení',
				'type'       => 'title',
				'id'         => 'ssc_general_settings_title',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
			)
		);

		$cmb->add_field(
			array(
				'name'       => 'Přesměrování po přihlášení',
				'type'       => 'text',
				'id'         => 'ssc_redirect_url',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
			)
		);
		$cmb->add_field(
			array(
				'name'       => 'Přesměrování po přihlášení',
				'type'       => 'text',
				'id'         => 'ssc_redirect_url',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
			)
		);


		$cmb->add_field(
			array(
				'name'       => 'Odebrat propojení',
				'type'       => 'title',
				'id'         => 'ssc_remove_api_title',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
			)
		);

		$cmb->add_field(
			array(
				'name'       => __( 'Disconnect Simple Shop', 'simpleshop-cz' ),
				'desc'       => __( 'You found it at SimpleShop in Settings (Nastavení) -> WP Plugin', 'simpleshop-cz' ),
				'id'         => 'ssc_api_disconnect',
				'type'       => 'disconnect_button',
				'classes_cb' => array( $this, 'is_valid_api_keys' ),
			)
		);
	}

	/**
	 * Maybe delete the API keys
	 */
	public function maybe_disconnect_simpleshop() {
		if ( ! isset( $_GET['disconnect_simpleshop'] ) || $_GET['disconnect_simpleshop'] !== '1' ) {
			return;
		}

		// Unset only API keys, leave the other settings saved
		$options = get_option( $this->key );
		unset( $options['ssc_api_email'] );
		unset( $options['ssc_api_key'] );

		// Set valid API keys to false
		update_option( 'ssc_valid_api_keys', 0 );

		// Update the SS options
		update_option( $this->key, $options );
	}

	/**
	 * Register settings notices for display
	 * @since  0.1.0
	 *
	 * @param  int $object_id Option key
	 * @param  array $updated Array of updated fields
	 *
	 * @return void
	 */
	public function settings_notices( $object_id, $updated ) {
		$api_email = $this->loader->get_api_email();
		$api_key   = $this->loader->get_api_key();

		if ( ! $api_email && isset( $_POST['ssc_api_email'] ) ) {
			$api_email = sanitize_email( $_POST['ssc_api_email'] );
		}

		if ( ! $api_key && isset( $_POST['ssc_api_key'] ) ) {
			$api_key = sanitize_text_field( $_POST['ssc_api_key'] );
		}

		$vyfakturuj_api = new VyfakturujAPI( $api_email, $api_key );
		$result         = $vyfakturuj_api->initWPPlugin( site_url() );
		if ( isset( $result['status'] ) && $result['status'] == 'success' ) {
			update_option( 'ssc_valid_api_keys', 1 );
		} else {
			update_option( 'ssc_valid_api_keys', 0 );
		}


		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', 'simpleshop-cz' ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}

	/**
	 * Check, if we already got valid API key, if not, add 'hidden' class to the settings that are not needed in the first step
	 * We switched to this approach, because by just hiding the fields the default values are saved even on the first save,
	 * but previously the fields were removed completely from the form, so the default values were not saved until the API keys were in place
	 * @return array
	 */
	function is_valid_api_keys() {
		// If the keys are valid, do nothing
		if ( get_option( 'ssc_valid_api_keys' ) == 1 ) {
			return array();
		}

		return array( 'hidden' );
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 *
	 * @param  string $field Field to retrieve
	 *
	 * @return mixed          Field value or exception is thrown
	 * @throws \Exception
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new \Exception( 'Invalid property: ' . $field );
	}

	/**
	 * Wrapper function around cmb2_get_option
	 * @since  0.1.0
	 *
	 * @param  string $key Options array key
	 * @param  mixed $default Optional default value
	 *
	 * @return mixed           Option value
	 */
	public function ssc_get_option( $key = '', $default = null ) {
		if ( function_exists( 'cmb2_get_option' ) ) {
			// Use cmb2_get_option as it passes through some key filters.
			return cmb2_get_option( $this->key, $key, $default );
		}

		// Fallback to get_option if CMB2 is not loaded yet.
		$opts = get_option( $this->key, $key, $default );

		$val = $default;

		if ( 'all' == $key ) {
			$val = $opts;
		} elseif ( is_array( $opts ) && array_key_exists( $key, $opts ) && false !== $opts[ $key ] ) {
			$val = $opts[ $key ];
		}

		return $val;
	}


}