<?php

namespace SSC;

class SSC_Admin{

    function __construct(){
        add_action('admin_menu',array($this,'add_settings_page'));
        add_filter('manage_edit-ssc_group_columns',array($this,'ssc_group_columns'));
        add_action('manage_ssc_group_posts_custom_column',array($this,'ssc_group_column_content'),10,2);
        add_action('init',array($this,'register_groups_cpt'));
        add_action('init',array($this,'tiny_mce_new_buttons'));
        add_filter('page_row_actions',array($this,'remove_quick_edit'),10,2);
        add_action('admin_head-post.php',array($this,'publishing_actions'));
        add_action('admin_head-post-new.php',array($this,'publishing_actions'));
        add_action('wp_ajax_load_simple_shop_products',array($this,'wp_ajax_load_simple_shop_products'));
    }

    /**
     * Get products from simple shop via API
     */
    function wp_ajax_load_simple_shop_products(){
        $ssc = new SSC();

        $values = array();
        if($ssc->email && $ssc->secure_key){
            $vyfakturuj_api = new \VyfakturujAPI($ssc->email,$ssc->secure_key);
            $ret = $vyfakturuj_api->getProducts();

            if($ret){
                foreach($ret as $product){
                    $values[$product['code']] = $product['name'];
                }
            }
        }
        echo json_encode($values);
        exit();
    }

    /**
     * Remove quick edit from groups
     * @param $actions
     * @param $post
     * @return mixed
     */
    function remove_quick_edit($actions,$post){
        if($post->post_type == "ssc_group"){

            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    /**
     * Hide publishing actions in group detail
     */
    function publishing_actions(){
        $mg_post_type = 'ssc_group';
        global $post;
        if($post->post_type == $mg_post_type){
            echo '<style type="text/css">
            .misc-pub-section.misc-pub-visibility,
            .misc-pub-section.curtime
            {
                display:none;
            }
            </style>';
        }
    }

    /**
     * Add a new TinyMCE button
     */
    function tiny_mce_new_buttons(){
        add_filter('mce_external_plugins',array($this,'tiny_mce_add_buttons'));
        add_filter('mce_buttons',array($this,'tiny_mce_register_buttons'));
    }

    /**
     * Add the button files
     * @param $plugins
     * @return mixed
     */
    function tiny_mce_add_buttons($plugins){
        $plugins['ssctinymceplugin'] = SSC_PLUGIN_URL.'js/tiny-mce/tiny-mce.js';
        return $plugins;
    }

    /**
     * Register the new TinyMCE Button
     * @return mixed
     */
    function tiny_mce_register_buttons($buttons){
        $newBtns = array(
            'sscaddformbutton'
        );
        $buttons = array_merge($buttons,$newBtns);
        return $buttons;
    }

    /**
     * Register a ssc_groups post type.
     */
    function register_groups_cpt(){
        $labels = array(
            'name' => __('Členské sekce','ssc'),
            'singular_name' => __('Skupina','ssc'),
            'menu_name' => __('Členské sekce','ssc'),
            'name_admin_bar' => __('Členské sekce','ssc'),
            'add_new' => __('Přidat skupinu','ssc'),
            'add_new_item' => __('Přidat novou skupinu','ssc'),
            'new_item' => __('Nová skupina','ssc'),
            'edit_item' => __('Upravit skupinu','ssc'),
            'view_item' => __('Zobrazit skupinu','ssc'),
            'all_items' => __('Členské sekce','ssc'),
            'search_items' => __('Hledat skupiny','ssc'),
            'parent_item_colon' => __('Nadřazená skupina:','ssc'),
            'not_found' => __('Nebyly nalezeny žádné skupiny.','ssc'),
            'not_found_in_trash' => __('Žádné skupiny v koši','ssc')
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'simple_shop_settings',
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => true,
            'menu_position' => null,
            'supports' => array('title')
        );

        register_post_type('ssc_group',$args);
    }

    /**
     * Register a custom menu page.
     */
    function add_settings_page(){
        add_menu_page(
                __('SimpleShop','ssc'),__('SimpleShop','ssc'),'manage_options','simple_shop_settings',array($this,'render_settings_page'),SSC_PLUGIN_URL.'/img/white_logo.png',99
        );
    }

    /**
     * Add custom columns to admin groups listing
     * @param $columns
     * @return mixed
     */
    function ssc_group_columns($columns){
        $columns['ssc_id'] = 'SSC ID';

        return $columns;
    }

    /**
     * Add content to custom columns in groups listing
     * @param $column
     * @param $post_id
     */
    function ssc_group_column_content($column,$post_id){
        global $post;

        switch($column){
            case 'ssc_id' :
                echo $post->ID;
                break;
        }
    }

}

new SSC_Admin();
