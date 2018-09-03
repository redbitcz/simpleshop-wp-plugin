<?php

/**
 * CMB2 Theme Options
 * @version 0.1.0
 *
 * @property-read string $key
 * @property-read string $metabox_id
 * @property-read string $title
 * @property-read string $options_page
 */
class SSC_Settings{

    /**
     * Holds an instance of the object
     *
     * @var self
     */
    protected static $instance;

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
     * Constructor
     * @since 0.1.0
     */
    protected function __construct(){
        // Set our title
        $this->title = __('Nastavení','ssc');
    }

    /**
     * Returns the running object
     *
     * @return self
     */
    public static function get_instance(){
        if(null === self::$instance){
            self::$instance = new self();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Initiate our hooks
     * @since 0.1.0
     */
    public function hooks(){
        add_action('admin_init',array($this,'init'));
        add_action('admin_menu',array($this,'add_options_page'));
        add_action('cmb2_admin_init',array($this,'add_options_page_metabox'));
    }

    /**
     * Register our setting to WP
     * @since  0.1.0
     */
    public function init(){
        register_setting($this->key,$this->key);
    }

    /**
     * Add menu options page
     * @since 0.1.0
     */
    public function add_options_page(){

        $this->options_page = add_submenu_page('simple_shop_settings',$this->title,$this->title,'manage_options','admin.php?page='.$this->key,array($this,'admin_page_display'));

        $this->options_page = add_menu_page($this->title,$this->title,'manage_options',$this->key,array($this,'admin_page_display'));
        remove_menu_page($this->key);
        // Include CMB CSS in the head to avoid FOUC
        add_action("admin_print_styles-{$this->options_page}",array('CMB2_hookup','enqueue_cmb_css'));
    }

    /**
     * Admin page markup. Mostly handled by CMB2
     * @since  0.1.0
     */
    public function admin_page_display(){
        ?>
        <div class="wrap cmb2-options-page <?php echo $this->key;?>">
            <h2><?php echo esc_html(get_admin_page_title());?></h2>
            <?php cmb2_metabox_form($this->metabox_id,$this->key);?>
        </div>
        <?php
    }

    /**
     * Add the options metabox to the array of metaboxes
     * @since  0.1.0
     */
    function add_options_page_metabox(){

        // hook in our save notices
        add_action("cmb2_save_options-page_fields_{$this->metabox_id}",array($this,'settings_notices'),10,2);

        $cmb = new_cmb2_box(array(
            'id' => $this->metabox_id,
            'hookup' => false,
            'cmb_styles' => true,
            'show_on' => array(
                // These are important, don't remove
                'key' => 'options-page',
                'value' => array($this->key,)
            ),
        ));

        #
        # MAIL
        #
        $cmb->add_field(array(
            'name' => 'Nastavení e-mailu, který se posílá novým členům:',
//            'desc' => 'This is a title description',
            'classes_cb' => array($this,'is_valid_api_keys'),
            'type' => 'title',
            'id' => 'ssc_email_title'
        ));
        $cmb->add_field(array(
            'name' => 'Poslat e-mail novému členovi?',
//            'desc' => 'Select an option',
            'id' => 'ssc_email_enable',
            'type' => 'select',
            'show_option_none' => false,
            'classes_cb' => array($this,'is_valid_api_keys'),
            'default' => '1',
            'options' => array(
                '1' => __('Ano, poslat každému novému členovi e-mail.','cmb2'),
                '2' => __('Ne, zakázat posílání e-mailu novým členům.','cmb2'),
            ),
        ));
        $cmb->add_field(array(
            'name' => __('Předmět e-mailu','ssc'),
//            'desc' => __('Najdete ho ve svém SimpleShop účtu v Nastavení -> WP Plugin','ssc'),
            'id' => 'ssc_email_subject',
            'classes_cb' => array($this,'is_valid_api_keys'),
            'type' => 'text',
            'default' => 'Byl Vám udělen přístup do členské sekce',
        ));


        $cmb->add_field(array(
            'name' => __('Text emailu','ssc'),
            'desc' => __('<u>Povolené zástupné znaky:</u><br/>'
                    .'<div style="font-style:normal;"><b>{login}</b> = login<br/>'
                    .'<b>{password}</b> = heslo<br/>'
                    .'<b>{login_url}</b> = adresa, na které je možné se přihlásit<br/>'
                    .'<b>{pages}</b> = seznam stránek, do kterých má uživatel zakoupený přístup<br/>'
                    .'<b>{mail}</b> = e-mail uživatele (většinou stejný jako login)<br/>'
                    .'</div>'
                    .'','ssc'),
            'id' => 'ssc_email_text',
            'type' => 'wysiwyg',
            'classes_cb' => array($this,'is_valid_api_keys'),
            'default' => 'Dobrý den,
byl udělen přístup do členské sekce.

Login: {login}
Heslo: {password}

Přihlásit se můžete na: '.wp_login_url().'

Váš zakoupený obsah:
{pages}

S pozdravem a přáním pěkného dne,
SimpleShop.cz - <i>S námi zvládne prodávat každý</i>'
        ));


        #
        # API
        #
        $cmb->add_field(array(
            'name' => 'Nastavení API - propojení s aplikací SimpleShop:',
//            'desc' => 'This is a title description',
//            'show_on_cb' => array($this,'is_valid_api_keys'),
            'type' => 'title',
            'id' => 'ssc_api_title'
        ));

        // Set our CMB2 fields
        $cmb->add_field(array(
            'name' => __('Přihlašovací email','ssc'),
            'desc' => __('Zadejte email, který používáte pro přihlášení do služby SimpleShop','ssc'),
            'id' => 'ssc_api_email',
            'type' => 'text',
        ));

        $cmb->add_field(array(
            'name' => __('SimpleShop API Klíč','ssc'),
            'desc' => __('Najdete ho ve svém SimpleShop účtu v Nastavení -> WP Plugin','ssc'),
            'id' => 'ssc_api_key',
            'type' => 'text',
        ));
    }

    /**
     * Register settings notices for display
     *
     * @since  0.1.0
     * @param  int $object_id Option key
     * @param  array $updated Array of updated fields
     * @return void
     */
    public function settings_notices($object_id,$updated){
        $ssc = new \SSC\SSC();
        $vyfakturuj_api = new \SSC\Vyfakturuj\VyfakturujAPI($ssc->email,$ssc->secure_key);
        $result = $vyfakturuj_api->initWPPlugin(site_url());
        if(isset($result['status']) && $result['status'] == 'success'){
            update_option('ssc_valid_api_keys',1);
        }else{
            update_option('ssc_valid_api_keys',0);
        }

        if($object_id !== $this->key || empty($updated)){
            return;
        }

        add_settings_error($this->key.'-notices','',__('Settings updated.','ssc'),'updated');
        settings_errors($this->key.'-notices');
    }

    /**
     * Check, if we already got valid API key, if not, add 'hidden' class to the settings that are not needed in the first step
     * We switched to this approach, because by just hiding the fields the default values are saved even on the first save,
     * but previously the fields were removed completely from the form, so the default values were not saved until the API keys were in place
     * @return array
     */
    function is_valid_api_keys(){
        // If the keys are valid, do nothing
        if (get_option('ssc_valid_api_keys') == 1)
            return array();

        return array( 'hidden' );

    }

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 *
	 * @param  string $field Field to retrieve
	 *
	 * @return mixed          Field value or exception is thrown
	 * @throws Exception
	 */
    public function __get($field){
        // Allowed fields to retrieve
        if(in_array($field,array('key','metabox_id','title','options_page'),true)){
            return $this->{$field};
        }

        throw new Exception('Invalid property: '.$field);
    }

}

/**
 * Helper function to get/return the Myprefix_Admin object
 * @since  0.1.0
 * @return SSC_Settings object
 */
function ssc_admin(){
    return SSC_Settings::get_instance();
}

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string $key Options array key
 * @param  mixed $default Optional default value
 * @return mixed           Option value
 */
function ssc_get_option($key = '',$default = null){
    if(function_exists('cmb2_get_option')){
        // Use cmb2_get_option as it passes through some key filters.
        return cmb2_get_option(ssc_admin()->key,$key,$default);
    }

    // Fallback to get_option if CMB2 is not loaded yet.
    $opts = get_option(ssc_admin()->key,$key,$default);

    $val = $default;

    if('all' == $key){
        $val = $opts;
    }elseif(is_array($opts) && array_key_exists($key,$opts) && false !== $opts[$key]){
        $val = $opts[$key];
    }

    return $val;
}

// Get it started
ssc_admin();
