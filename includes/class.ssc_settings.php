<?php

//TODO: Remove this file

/**
 * Helper function to get/return the Myprefix_Admin object
 * @since  0.1.0
 * @return \Redbit\SimpleShop\WpPlugin\SSC_Settings object
 */
function ssc_admin(){
    return \Redbit\SimpleShop\WpPlugin\Settings::get_instance();
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
