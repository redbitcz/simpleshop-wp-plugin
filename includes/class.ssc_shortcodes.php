<?php

namespace SSC;

class SSC_Shortcodes{

    function __construct(){
        add_shortcode('SimpleShop-form',array($this,'simple_shop_form'));
    }

    function simple_shop_form($atts){
        $url = substr($_SERVER['SERVER_NAME'],-2) === 'lc' ? 'http://form.simpleshop.czlc' : 'https://form.simpleshop.cz';
        return '<script type="text/javascript" src="'.$url.'/iframe/js/?id='.$atts['id'].'"></script>';
    }

}

new SSC_Shortcodes();


