<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

class Metaboxes {
	public $prefix = '_ssc_';

	/**
	 * @var Plugin
	 */
	private $loader;

	public function __construct( Plugin $loader ) {
		$this->loader = $loader;
		add_action( 'cmb2_admin_init', [ $this, 'page_metaboxes' ] );
		add_action( 'cmb2_admin_init', [ $this, 'user_metaboxes' ] );
	}

	/**
	 * Add metabox to pages and posts
	 * TODO: find a way to add custom post types
	 */
	public function page_metaboxes() {

		$ssc_group  = new Group();
		$groups     = $ssc_group->get_groups();
		$ssc_access = $this->loader->get_access();
		$post_types = $this->loader->get_post_types();


		if ( $groups && $ssc_access->user_is_admin() ) {
			/**
			 * Initiate the metabox
			 */
			$cmb = new_cmb2_box( [
				'id'           => 'ssc_page_groups',
				'title'        => __( 'SimpleShop - Member sections', 'simpleshop-cz' ),
				'object_types' => $post_types,
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true,
			] );

			$cmb->add_field( [
				'name'    => __( 'Member section with allowed access to page', 'simpleshop-cz' ),
				'desc'    => __( 'Access to this page is permit only for users of selected Member Sections. If no one section selected, all users is permit to access this page.',
					'simpleshop-cz' ),
				'id'      => $this->prefix . 'groups',
				'type'    => 'multicheck',
				'options' => $groups,
			] );

			$tmp_post_types = $post_types;
			unset( $tmp_post_types['attachment'] );

			$cmb->add_field( [
				'name'            => __( 'Page ID to redirect', 'simpleshop-cz' ),
				'desc'            => __( 'Select one Page to which user will be redirected when uses is logged in, but have no access to page. This is preffered way. Redirect will works even if target page URL will be changer in future.',
					'simpleshop-cz' ),
				'id'              => $this->prefix . 'no_access_redirect_post_id',
				'type'            => 'post_search_text',
				'select_type'     => 'radio',
				'select_behavior' => 'replace',
				'post_type'       => $tmp_post_types
			] );


			$cmb->add_field( [
				'name' => __( 'Manual URL to redirect', 'simpleshop-cz' ),
				'desc' => __( 'Put URL to which user will be redirected when uses is logged in, but have no access to page. If you want to use this method, keep previous field empty.',
					'simpleshop-cz' ),
				'id'   => $this->prefix . 'no_access_redirect',
				'type' => 'text'
			] );

//            $cmb->add_field(array(
//                'name' => __('Přesměrovat na přihlášení', 'ssc'),
//                'desc' => __('Zaškrtněte, pokud chcete uživatele přesměrovat na přihlašovací formulář. Po přihlášení bude uživatel přesměrován zpět na tuto stránku.', 'ssc'),
//                'id' => $this->prefix . 'no_access_redirect_to_login_form',
//                'type' => 'checkbox'
//            ));


			$cmb->add_field( [
				'name' => __( 'Delay access to content (days from group assign)', 'simpleshop-cz' ),
				'desc' => __( 'Put number of days to delay before access to content is allowed after registration. For example: If registration to group is at January 1st and you set delay to 5 days, user get access to content from January 6th.',
					'simpleshop-cz' ),
				'id'   => $this->prefix . 'days_to_access',
				'type' => 'text'
			] );

			$cmb->add_field( [
				'name'        => __( 'Allow access from date', 'simpleshop-cz' ),
				'desc'        => __( 'Put date which will be access to content allowed (applied to all groups)',
					'simpleshop-cz' ),
				'id'          => $this->prefix . 'date_to_access',
				'type'        => 'text_date',
				'date_format' => 'Y-m-d',
			] );

			$cmb->add_field( [
				'name'        => __( 'Allow access to date', 'simpleshop-cz' ),
				'desc'        => __( 'Put date until will be access to content allowed (applied to all groups)',
					'simpleshop-cz' ),
				'id'          => $this->prefix . 'date_until_to_access',
				'type'        => 'text_date',
				'date_format' => 'Y-m-d',
			] );

			$cmb->add_field( [
				'name' => __( 'Subject of email when content access allowed', 'simpleshop-cz' ),
				'desc' => __( 'Enter the subject of the email that will automatically be sent to the user when gains access to this content based on the days set above.',
					'simpleshop-cz' ),
				'id'   => $this->prefix . 'email_subject_user_can_access',
				'type' => 'text'
			] );

			$cmb->add_field( [
				'name' => __( 'Email message when content access allowed', 'simpleshop-cz' ),
				'desc' => __( 'Enter an email that will automatically be sent to the user when gains access to this content based on the days set above.',
					'simpleshop-cz' ),
				'id'   => $this->prefix . 'email_user_can_access',
				'type' => 'wysiwyg'
			] );
		}
	}

	/**
	 * Add metabox to user profile
	 */
	public function user_metaboxes() {

		/**
		 * Initiate the metabox
		 */
		$cmb = new_cmb2_box( [
			'id'           => 'ssc_user_groups',
			'title'        => __( 'SimpleShop', 'simpleshop-cz' ),
			'object_types' => [ 'user' ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		] );

		$ssc_group = new Group();
		$groups    = $ssc_group->get_groups();


		$access = $this->loader->get_access();

		if ( $access->user_is_admin() ) {
			$cmb->add_field( [
				'name'    => __( 'SimpleShop - member sections<br/><small style=\"font-weight:normal;\">Choose which member sections the user should have access to.</small>',
					'simpleshop-cz' ),
//            'desc' => __('Vyberte, do kterých členských sekcí má mít uživatel přístup','ssc'),
				'id'      => $this->prefix . 'user_groups',
				'type'    => 'multicheck',
				'options' => $groups,
			] );

			foreach ( $groups as $key => $group ) {
				$cmb->add_field( [
					'name'        => '<small style="font-weight:normal;">' . sprintf( __( 'Registration date to group %s.',
							'simpleshop-cz' ), $group ) . '</small>',
					'id'          => $this->prefix . 'group_subscription_date_' . $key,
					'type'        => 'text_date',
					'date_format' => 'Y-m-d',
				] );
				$cmb->add_field( [
					'name'        => '<small style="font-weight:normal;">' . sprintf( __( 'Expiration date of registration to group %s.',
							'simpleshop-cz' ), $group ) . '</small>',
					'id'          => $this->prefix . 'group_subscription_valid_to_' . $key,
					'type'        => 'text_date',
					'date_format' => 'Y-m-d',
				] );
			}
		}
	}
}
