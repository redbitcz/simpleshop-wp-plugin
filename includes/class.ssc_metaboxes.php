<?php

namespace SSC;

class SSC_Metaboxes{

    public $prefix = '_ssc_';

    function __construct(){
        add_action('cmb2_admin_init',array($this,'page_metaboxes'));
        add_action('cmb2_admin_init',array($this,'user_metaboxes'));
    }

    /**
     * Add metabox to pages and posts
     * TODO: find a way to add custom post types
     */
    function page_metaboxes(){

        $ssc_group = new SSC_Group();
        $groups = $ssc_group->get_groups();
        $ssc_access = new SSC_Access();
        $ssc = new SSC();
        $post_types = $ssc->get_post_types();


        if($groups && $ssc_access->user_is_admin()){
            /**
             * Initiate the metabox
             */
            $cmb = new_cmb2_box(array(
                'id' => 'ssc_page_groups',
                'title' => __('SimpleShop - členské sekce','ssc'),
                'object_types' => $post_types,
                'context' => 'normal',
                'priority' => 'high',
                'show_names' => true,
            ));

            $cmb->add_field(array(
                'name' => __('Členské sekce, které mají přístup na stránku','ssc'),
                'desc' => __('Pouze uživatelé v zaškrtnutých členskéch sekcích mají přístup na tuto stránku. Pokud nic nezaškrtnete, stránku uvidí všichni uživatelé','ssc'),
                'id' => $this->prefix.'groups',
                'type' => 'multicheck',
                'options' => $groups,
            ));

            $tmp_post_types = $post_types;
            unset($tmp_post_types['attachment']);

            $ssc = new SSC();
            $cmb->add_field(array(
                'name' => __('ID stránky pro přesměrování','ssc'),
                'desc' => __('Vyberte stránku, na kterou uživatel bude přesměrován, pokud je přihlášen, ale nemá oprávnění k přístupu. Toto je preferovaný způsob - přsměrování bude fungovat, i pokud se v budoucnu změní adresa stránky','ssc'),
                'id' => $this->prefix.'no_access_redirect_post_id',
                'type' => 'post_search_text',
                'select_type' => 'radio',
                'select_behavior' => 'replace',
                'post_type' => $tmp_post_types
            ));


            $cmb->add_field(array(
                'name' => __('Manuální adresa přesměrování','ssc'),
                'desc' => __('Zadejte ručně adresu, na kterou uživatel bude přesměrován, pokud je přihlášen, ale nemá oprávnění k přístupu. Pokud využijete tuto volbu, pole výše musí být prázdné.','ssc'),
                'id' => $this->prefix.'no_access_redirect',
                'type' => 'text'
            ));
        }
    }

    /**
     * Add metabox to user profile
     */
    function user_metaboxes(){

        /**
         * Initiate the metabox
         */
        $cmb = new_cmb2_box(array(
            'id' => 'ssc_user_groups',
            'title' => __('SimpleShop','ssc'),
            'object_types' => array('user'),
            'context' => 'normal',
            'priority' => 'high',
            'show_names' => true,
        ));

        $ssc_group = new SSC_Group();
        $groups = $ssc_group->get_groups();

        $cmb->add_field(array(
            'name' => __('SimpleShop - členské sekce<br/><small style="font-weight:normal;">Vyberte, do kterých členských sekcí má mít uživatel přístup.</small>','ssc'),
//            'desc' => __('Vyberte, do kterých členských sekcí má mít uživatel přístup','ssc'),
            'id' => $this->prefix.'user_groups',
            'type' => 'multicheck',
            'options' => $groups,
        ));
    }

}

new SSC_Metaboxes();
