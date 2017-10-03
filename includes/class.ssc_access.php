<?php

/**
 * This class handles the access for the posts / pages
 */

namespace SSC;

class SSC_Access{

    function __construct(){
        add_action('template_redirect',array($this,'check_access'));
        add_filter('wp_setup_nav_menu_item',array($this,'setup_nav_menu_item'));
        add_action('wp_head',array($this,'hide_menu_items'));
    }

    /**
     * Check if the page is protected and the user has access to the page
     */
    function check_access(){
        $post_groups = $this->get_post_groups();

        // If the post is protected and user is not logged in, redirect him to login
        if($post_groups && !is_user_logged_in()){
            wp_safe_redirect(wp_login_url($_SERVER['REQUEST_URI']));
        }

        // Check if current user has access to the post, if not, redirect him to defined URL or home if the URL is not set
        if($post_groups && !$this->user_can_view_post() && !is_home() && !is_front_page()){
            $no_access_url = $this->get_no_access_redirect_url();

            $url = $no_access_url ? $no_access_url : site_url();
            wp_safe_redirect($url);
        }
    }

    /**
     * Check if user has permission to view the post
     * @param string $post_id
     * @param string $user_id
     * @return bool|\WP_Error
     */
    function user_can_view_post($post_id = '',$user_id = ''){
        // Admins can view all posts

        if($this->user_is_admin())
            return true;

        global $post;
        if(!$post_id)
            $post_id = $post->ID;

        if(!$user_id)
            $user_id = get_current_user_id();


        if(!($post_id > 0) || !($user_id >= 0)){
            return new \WP_Error('400','Wrong post ID or user ID');
        }

        $post_groups = $this->get_post_groups($post_id);


        if(!$post_groups || $post_groups == '')
            return true;


        foreach($post_groups as $post_group){
            $group = new SSC_Group($post_group);
            if($group->user_is_member_of_group($user_id)){
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user is admin
     * This is filterable,
     * @return mixed
     */
    function user_is_admin(){
        $is_admin = current_user_can('administrator') ? true : false;
        return apply_filters('ssc_user_is_admin',$is_admin);
    }

    /**
     * Get the URL to redirect the user if he has no access
     * @param string $post_id
     *
     * @return mixed
     */
    function get_no_access_redirect_url($post_id = ''){
        global $post;

        if(!$post_id)
            $post_id = $post->ID;

        // First try to get the ID and return permalink
        if($redirect_post_id = get_post_meta($post_id,'_ssc_no_access_redirect_post_id',true)){
            return get_the_permalink($redirect_post_id);
        }

        return get_post_meta($post_id,'_ssc_no_access_redirect',true);
    }

    /**
     * Setup the cart in menu
     * @param $item
     * @return mixed
     */
    function setup_nav_menu_item($item){

        if(!$this->user_can_view_post($item->object_id)){
            $item->classes[] = 'ssc-hide';
            $item->title = '';
            $item->url = '';
            return $item;
        }

        return $item;
    }

    function get_post_groups($post_id = ''){
        global $post;

        if(!$post_id)
            $post_id = $post->ID;

        return get_post_meta($post_id,'_ssc_groups',true);
    }

    /**
     * Hide items in menu
     */
    function hide_menu_items(){
        ?>
        <style type="text/css">
            .ssc-hide {
                display: none!important;
            }
        </style>
        <?php

    }

}

new SSC_Access();
