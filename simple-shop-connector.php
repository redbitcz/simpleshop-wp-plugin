<?php

/*
  Plugin Name: SimpleShop.cz (WP Plugin)
  Plugin URI: https://www.simpleshop.cz
  Description: Plugin pro propojení Wordpress a SimpleShop.cz
  Author:  SimpleShop.cz
  Version: 1.3
  Author URI: https://www.simpleshop.cz
 */

namespace SSC;

define('SSC_PLUGIN_DIR',plugin_dir_path(__FILE__));
define('SSC_PLUGIN_URL',plugin_dir_url(__FILE__));
define('SSC_PLUGIN_VERSION','1.3');
define('SSC_PREFIX', '_ssc_');

class SSC{

    public $secure_key = '';
    public $email = '';

    function __construct(){
        $this->require_classes();
        $this->secure_key = $this->get_secure_key();
        $this->email = $this->get_email();
        add_action('tgmpa_register',array($this,'register_required_plugins'));
    }

    function require_classes(){
        require_once('vendor/autoload.php');
        require_once('includes/ssc_helpers.php');
        require_once('lib/vyfakturuj-api/VyfakturujAPI.class.php');
        require_once('includes/class.ssc_settings.php');
        require_once('includes/class.ssc_admin.php');
        require_once('includes/class.ssc_groups.php');
        require_once('includes/class.ssc_membership.php');
        require_once('includes/class.ssc_rest.php');
        require_once('includes/class.ssc_cron.php');
        require_once('includes/class.ssc_metaboxes.php');
        require_once('includes/class.ssc_access.php');
        require_once('includes/class.ssc_shortcodes.php');
    }

    function generate_secure_key(){
        return bin2hex(random_bytes(22));
    }

    function save_secure_key($key){
        update_option('ssc_secure_key',$key);
    }

    function get_secure_key(){
        return ssc_get_option('ssc_api_key');
    }

    function get_email(){
        return ssc_get_option('ssc_api_email');
    }

    function validate_secure_key($key_to_validate){
        return $key_to_validate == sha1($this->secure_key);
    }

    /**
     * Register the required plugins for this plugin.
     */
    function register_required_plugins(){
        global $wp_version;
        if($wp_version < '4.7'){
            $plugins = array(
                array(
                    'name' => 'Wordpress Rest API',
                    'slug' => 'rest-api',
                    'required' => true,
                )
            );

            $config = array(
                'id' => 'ssc',
                'default_path' => '',
                'menu' => 'ssc-install-plugins',
                'parent_slug' => 'tools.php',
                'capability' => 'edit_theme_options',
                'has_notices' => true,
                'dismissable' => true,
                'dismiss_msg' => '',
                'is_automatic' => false,
                'message' => '',
            );

            tgmpa($plugins,$config);
        }
    }

    function get_post_types(){
        $args = array(
            'public' => true
        );

        return get_post_types($args);
    }

}

new SSC;

/**
 * Activation hook
 */
register_activation_hook(__FILE__,'SSC\ssc_activation_hook');

function ssc_activation_hook(){
    if(!function_exists('curl_init') || !function_exists('random_bytes')){
        echo '<h3>'.__('Aktivace se nezdařila. Kontaktuje prosím poskytovatele Vašeho hostingu a požádejte o instalaci rozšíření PHP - CURL a MCRYPT.','ssc').'</h3>';

        //Adding @ before will prevent XDebug output
        @trigger_error(__('Aktivace se nezdařila. Kontaktuje prosím poskytovatele Vašeho hostingu a požádejte o instalaci rozšíření PHP - CURL a MCRYPT.','ssc'),E_USER_ERROR);
    }


    // Generate and save the secure key
    $ssc = new SSC();
    $key = $ssc->generate_secure_key();
    $ssc->save_secure_key($key);
}