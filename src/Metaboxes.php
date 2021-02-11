<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2020 Redbit s.r.o.
 * @author Redbit s.r.o. <info@simpleshop.cz>
 */

namespace Redbit\SimpleShop\WpPlugin;

use WP_User;

class Metaboxes {
	public $prefix = '_ssc_';

	/** @var Plugin */
	private $loader;

	public function __construct( Plugin $loader ) {
		$this->loader = $loader;
		add_action( 'cmb2_admin_init', [ $this, 'page_metaboxes' ] );
		add_action( 'show_user_profile', [ $this, 'render_user_profile_groups' ] );
		add_action( 'edit_user_profile', [ $this, 'render_user_profile_groups' ] );
		add_action( 'personal_options_update', [ $this, 'save_user_profile_groups' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_user_profile_groups' ] );
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
			$cmb = new_cmb2_box(
				[
					'id'           => 'ssc_page_groups',
					'title'        => __( 'SimpleShop - Member sections', 'simpleshop-cz' ),
					'object_types' => $post_types,
					'context'      => 'normal',
					'priority'     => 'high',
					'show_names'   => true,
				]
			);

			$cmb->add_field(
				[
					'name'    => __( 'Member section with allowed access to page', 'simpleshop-cz' ),
					'desc'    => __(
						'Access to this page is permit only for users of selected Member Sections. If no one section selected, all users is permit to access this page.',
						'simpleshop-cz'
					),
					'id'      => $this->prefix . 'groups',
					'type'    => 'multicheck',
					'options' => $groups,
				]
			);

			$tmp_post_types = $post_types;
			unset( $tmp_post_types['attachment'] );

			$cmb->add_field(
				[
					'name'            => __( 'Page ID to redirect', 'simpleshop-cz' ),
					'desc'            => __(
						'Select one Page to which user will be redirected when uses is logged in, but have no access to page. This is preffered way. Redirect will works even if target page URL will be changer in future.',
						'simpleshop-cz'
					),
					'id'              => $this->prefix . 'no_access_redirect_post_id',
					'type'            => 'post_search_text',
					'select_type'     => 'radio',
					'select_behavior' => 'replace',
					'post_type'       => $tmp_post_types,
				]
			);


			$cmb->add_field(
				[
					'name' => __( 'Manual URL to redirect', 'simpleshop-cz' ),
					'desc' => __(
						'Put URL to which user will be redirected when uses is logged in, but have no access to page. If you want to use this method, keep previous field empty.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'no_access_redirect',
					'type' => 'text',
				]
			);

//            $cmb->add_field(array(
//                'name' => __('Přesměrovat na přihlášení', 'ssc'),
//                'desc' => __('Zaškrtněte, pokud chcete uživatele přesměrovat na přihlašovací formulář. Po přihlášení bude uživatel přesměrován zpět na tuto stránku.', 'ssc'),
//                'id' => $this->prefix . 'no_access_redirect_to_login_form',
//                'type' => 'checkbox'
//            ));


			$cmb->add_field(
				[
					'name' => __( 'Delay access to content (days from group assign)', 'simpleshop-cz' ),
					'desc' => __(
						'Put number of days to delay before access to content is allowed after registration. For example: If registration to group is at January 1st and you set delay to 5 days, user get access to content from January 6th.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'days_to_access',
					'type' => 'text',
				]
			);

			$cmb->add_field(
				[
					'name'        => __( 'Allow access from date', 'simpleshop-cz' ),
					'desc'        => __(
						'Put date which will be access to content allowed (applied to all groups)',
						'simpleshop-cz'
					),
					'id'          => $this->prefix . 'date_to_access',
					'type'        => 'text_date',
					'date_format' => 'Y-m-d',
				]
			);

			$cmb->add_field(
				[
					'name'        => __( 'Allow access to date', 'simpleshop-cz' ),
					'desc'        => __(
						'Put date until will be access to content allowed (applied to all groups)',
						'simpleshop-cz'
					),
					'id'          => $this->prefix . 'date_until_to_access',
					'type'        => 'text_date',
					'date_format' => 'Y-m-d',
				]
			);

			$cmb->add_field(
				[
					'name' => __( 'Subject of email when content access allowed', 'simpleshop-cz' ),
					'desc' => __(
						'Enter the subject of the email that will automatically be sent to the user when gains access to this content based on the days set above.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'email_subject_user_can_access',
					'type' => 'text',
				]
			);

			$cmb->add_field(
				[
					'name' => __( 'Email message when content access allowed', 'simpleshop-cz' ),
					'desc' => __(
						'Enter an email that will automatically be sent to the user when gains access to this content based on the days set above.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'email_user_can_access',
					'type' => 'wysiwyg',
				]
			);
		}
	}

	/**
	 * Add groups table to user profile
	 *
	 * @param WP_User $user WP User
	 */
	public function render_user_profile_groups( $user ) {
		$ssc_group  = new Group();
		$groups     = $ssc_group->get_groups();
		$membership = new Membership( $user->ID );
		$access     = $this->loader->get_access(); ?>

		<style type="text/css">
			#simpleshop__groups th {
				padding: 15px 10px;
			}
		</style>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $(".datepicker").datepicker(
                    {
                        dateFormat: 'yy-mm-dd'
                    }
                );
            });
        </script>
        <table id="custom_user_field_table" class="form-table">
			<tr id="simpleshop__groups">
				<th>
					<label for="custom_field"><?php _e( 'Simpleshop Groups', 'simpleshop-cz' ); ?></label>
				</th>
				<td>
					<table>
						<thead>
						<tr>
							<th><?php _e( 'Group name', 'simpleshop-cz' ); ?></th>
							<th><?php _e( 'Is member', 'simpleshop-cz' ); ?></th>
							<th><?php _e( 'Membership from', 'simpleshop-cz' ); ?></th>
							<th><?php _e( 'Membership to', 'simpleshop-cz' ); ?></th>

						</tr>
						</thead>
						<tbody>
						<?php foreach ( $groups as $group_id => $group_name ) { ?>
							<tr>
								<td><?php echo $group_name; ?></td>
								<td>
									<?php if ( $access->user_is_admin() ) { ?>
										<input type="checkbox" name="ssc_groups[<?php echo $group_id ?>][is_member]" value="on" <?php checked( array_key_exists( $group_id, $membership->groups ), true ); ?>/>
									<?php } else {
										echo array_key_exists( $group_id, $membership->groups ) ? __( 'Yes', 'simpleshop-cz' ) : __( 'No', 'simpleshop-cz' );
									} ?>
								</td>
								<td>
									<?php if ( $access->user_is_admin() ) { ?>
										<input type="text" class="datepicker" name="ssc_groups[<?php echo $group_id ?>][subscription_date]" value="<?php echo get_user_meta( $user->ID, $this->prefix . 'group_subscription_date_' . $group_id, true ) ?>"/>
									<?php } else {
										echo get_user_meta( $user->ID, $this->prefix . 'group_subscription_date_' . $group_id, true );
									} ?>
								</td>
								<td>
									<?php if ( $access->user_is_admin() ) { ?>
										<input type="text" class="datepicker" name="ssc_groups[<?php echo $group_id ?>][subscription_valid_to]" value="<?php echo get_user_meta( $user->ID, $this->prefix . 'group_subscription_valid_to_' . $group_id, true ) ?>"/>
									<?php } else {
										echo get_user_meta( $user->ID, $this->prefix . 'group_subscription_valid_to_' . $group_id, true );
									} ?>
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save user groups to profile
	 *
	 * @param $user_id
	 */
	public function save_user_profile_groups( $user_id ) {
		$access = $this->loader->get_access();
		if ( ! $access->user_is_admin() ) {
			return;
		}

		$groups = [];
		foreach ( $_POST['ssc_groups'] as $group_id => $group ) {
			if ( ! empty( $group['is_member'] ) && $group['is_member'] ) {
				$groups[] = $group_id;
				if ( empty( $group['subscription_date'] ) ) {
					$group['subscription_date'] = date( 'Y-m-d' );
				}
			}

			update_user_meta( $user_id, $this->prefix . 'user_groups', $groups );
			update_user_meta( $user_id, $this->prefix . 'group_subscription_date_' . $group_id, empty( $group['subscription_date'] ) ? '' : date( 'Y-m-d', strtotime( $group['subscription_date'] ) ) );
			update_user_meta( $user_id, $this->prefix . 'group_subscription_valid_to_' . $group_id, empty( $group['subscription_valid_to'] ) ? '' : date( 'Y-m-d', strtotime( $group['subscription_valid_to'] ) ) );
		}
	}
}
