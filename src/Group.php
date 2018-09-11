<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Group{

    public $id = '';
    public $name = '';

    function __construct($id = ''){
        if($id){
            $this->id = $id;
            $this->get_group();
        }
    }

    /**
     * Get all groups (custom post type)
     * @return array
     */
    function get_groups(){

        $args = array(
            'post_type' => 'ssc_group',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $groups = array();

        $posts = get_posts($args);

        foreach ($posts as $item) {
            $groups[$item->ID] = $item->post_title;
        }

        return $groups;
    }

    /**
     * Get a single group
     * @return bool
     */
    function get_group(){
        $group = get_post($this->id);
        if($group){
            // Set the group details

            $this->name = $group->post_title;
            return true;
        }else{
            return false;
        }
    }

    /**
     * Check if group exists
     * @return array|null|\WP_Post
     */
    function group_exists(){
        return get_post($this->id);
    }

    /**
     * Get groups the user belongs to
     * @param string $user_id
     * @return mixed
     */
    function get_user_groups($user_id = ''){
        if(!$user_id)
            $user_id = get_current_user_id();

        return get_user_meta($user_id,'_ssc_user_groups',true);
    }

    /**
     * Add user to a group
     * @param $user_id
     */
    function add_user_to_group($user_id){
        $groups = $this->get_user_groups($user_id);

        if(!$groups)
            $groups = array();

        if(!in_array($this->id,$groups)){
            $groups[] = $this->id;
            update_user_meta($user_id,'_ssc_user_groups',$groups);

            // Set the date of user registration to the group
            $membership = new Membership($user_id);
            $membership->set_subscription_date($this->id);
        }
    }

    /**
     * Check if user is a member of a group
     * @param $user_id
     * @return bool
     */
    function user_is_member_of_group($user_id){
        if(!$user_id)
            $user_id = get_current_user_id();

        $groups = $this->get_user_groups($user_id);

        if(!is_array($groups)){
            return false;
        }
        if(in_array($this->id,$groups)){
            return true;
        }else{
            return false;
        }
    }

}

