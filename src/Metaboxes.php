<?php
/**
 * @package   Redbit\SimpleShop\WpPlugin
 * @license   MIT
 * @copyright 2016-2023 Redbit s.r.o.
 * @author    Redbit s.r.o. <info@simpleshop.cz>
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
		add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
		add_filter( 'cmb2_override_meta_value', [ $this, 'adjust_meta_values_get' ], 10, 4 );
		add_filter( 'cmb2_override_meta_save', [ $this, 'adjust_meta_values_save' ], 10, 4 );
		add_filter( 'cmb2_save_field', [ $this, 'save_field' ], 10, 4 );
	}

	public function register_metaboxes() {
		add_meta_box(
			'simpleshop-group-details',
			__( 'Group details', 'simpleshop-cz' ),
			[ $this, 'group_details' ],
			'ssc_group'
		);
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
					'name'    => __( 'Member sections with allowed access to the page', 'simpleshop-cz' ),
					'desc'    => __(
						'Access to this page is permitted only for users of the selected member sections. If no section is selected, all users are allowed to access this page.',
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
					'name'            => __( 'Page ID for redirect', 'simpleshop-cz' ),
					'desc'            => __(
						'Select one page to which the user will be redirected when logged in, but with no access to page. This is the preferred way. Redirect will work even if the target page URL is changed in the future.',
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
					'name' => __( 'Manual URL for redirect', 'simpleshop-cz' ),
					'desc' => __(
						'Enter URL to which the user will be redirected when logged in, but with no access to page. If you want to use this method, keep the previous field empty.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'no_access_redirect',
					'type' => 'text',
				]
			);


			$cmb->add_field(
				[
					'name' => __( 'Delay access to page (days after registration to the member section)',
						'simpleshop-cz' ),
					'desc' => __(
						'Enter the number of days to delay access to this page after the registration. For example: If the registration to the member section is on January 1st and you set delay to 5 days, the user gets access to the content from January 6th.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'days_to_access',
					'type' => 'text',
				]
			);

			$cmb->add_field(
				[
					'name' => __( 'Allow access only for specific number of days (days after registration to the member section)',
						'simpleshop-cz' ),
					'desc' => __(
						'Allow access only for specific number of days after registration. For example: If the registration to the member section is on January 1st and you set 14 days, the user will be able to access the page until January 14th included.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'expire_days_after_registration',
					'type' => 'text',
				]
			);

			$cmb->add_field(
				[
					'name'        => __( 'Allow access from this date', 'simpleshop-cz' ),
					'desc'        => __(
						'Select the date and time when the access to this page will be allowed',
						'simpleshop-cz'
					),
					'id'          => $this->prefix . 'date_to_access',
					'type'        => 'text_datetime_timestamp',
					'date_format' => 'Y-m-d',
				]
			);

			$cmb->add_field(
				[
					'name'        => __( 'Allow access till this date.', 'simpleshop-cz' ),
					'desc'        => __(
						'Select the date and time, until which the access to this page will be allowed',
						'simpleshop-cz'
					),
					'id'          => $this->prefix . 'date_until_to_access',
					'type'        => 'text_datetime_timestamp',
					'date_format' => 'Y-m-d',
				]
			);

			$cmb->add_field(
				[
					'name' => __( 'Subject of email - allowed access to page', 'simpleshop-cz' ),
					'desc' => __(
						'Enter the subject of the email automatically sent to the user, when the access to this page is allowed based on the days set above.',
						'simpleshop-cz'
					),
					'id'   => $this->prefix . 'email_subject_user_can_access',
					'type' => 'text',
				]
			);

			$cmb->add_field(
				[
					'name' => __( 'Email message - allowed access to page', 'simpleshop-cz' ),
					'desc' => __(
						'Enter the text of the email automatically sent to the user, when the access to this page is allowed based on the days set above.',
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

        <style>
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
                    <label for="custom_field"><?php
						_e( 'SimpleShop Groups', 'simpleshop-cz' ); ?></label>
                </th>
                <td>
                    <table>
                        <thead>
                        <tr>
                            <th><?php
								_e( 'Group name', 'simpleshop-cz' ); ?></th>
                            <th><?php
								_e( 'Is member', 'simpleshop-cz' ); ?></th>
                            <th><?php
								_e( 'Membership from', 'simpleshop-cz' ); ?></th>
                            <th><?php
								_e( 'Membership to', 'simpleshop-cz' ); ?></th>

                        </tr>
                        </thead>
                        <tbody>
						<?php
						foreach ( $groups as $group_id => $group_name ) { ?>
                            <tr>
                                <td><?php
									echo esc_html( $group_name ) ?></td>
                                <td>
									<?php
									if ( $access->user_is_admin() ) { ?>
                                        <input type="checkbox" name="ssc_groups[<?php
										echo esc_attr( $group_id ) ?>][is_member]"
                                               value="on" <?php
										checked( array_key_exists( $group_id, $membership->groups ) ) ?>>
										<?php
									} else {
										echo array_key_exists( $group_id, $membership->groups ) ? __( 'Yes',
											'simpleshop-cz' ) : __( 'No', 'simpleshop-cz' );
									} ?>
                                </td>
                                <td>
									<?php
									if ( $access->user_is_admin() ) { ?>
                                        <input type="text" class="datepicker"
                                               name="ssc_groups[<?php
										       echo esc_attr( $group_id ) ?>][subscription_date]"
                                               value="<?php
										       echo esc_attr( get_user_meta( $user->ID,
											       $this->prefix . 'group_subscription_date_' . $group_id,
											       true ) ) ?>">
										<?php
									} else {
										echo esc_html( get_user_meta( $user->ID,
											$this->prefix . 'group_subscription_date_' . $group_id,
											true ) );
									} ?>
                                </td>
                                <td>
									<?php
									if ( $access->user_is_admin() ) { ?>
                                        <input type="text" class="datepicker"
                                               name="ssc_groups[<?php
										       echo esc_attr( $group_id ) ?>][subscription_valid_to]"
                                               value="<?php
										       echo esc_attr( get_user_meta( $user->ID,
											       $this->prefix . 'group_subscription_valid_to_' . $group_id,
											       true ) ) ?>">
										<?php
									} else {
										echo esc_html( get_user_meta( $user->ID,
											$this->prefix . 'group_subscription_valid_to_' . $group_id,
											true ) );
									} ?>
                                </td>
                            </tr>
							<?php
						} ?>
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
		if ( ! $access->user_is_admin() || empty( $_POST['ssc_groups'] ) ) {
			return;
		}

		$groups = [];
		foreach ( $_POST['ssc_groups'] as $group_id => $group ) {
			if ( ! empty( $group['is_member'] ) ) {
				$groups[] = $group_id;
				if ( empty( $group['subscription_date'] ) ) {
					$group['subscription_date'] = date( 'Y-m-d' );
				}
			}

			update_user_meta( $user_id, $this->prefix . 'user_groups', $groups );
			update_user_meta( $user_id,
				$this->prefix . 'group_subscription_date_' . $group_id,
				empty( $group['subscription_date'] ) ? '' : date( 'Y-m-d',
					strtotime( $group['subscription_date'] ) ) );
			update_user_meta( $user_id,
				$this->prefix . 'group_subscription_valid_to_' . $group_id,
				empty( $group['subscription_valid_to'] ) ? '' : date( 'Y-m-d',
					strtotime( $group['subscription_valid_to'] ) ) );
		}
	}

	public function group_details( $post ) {
		$group   = new Group( $post->ID );
		$valid   = [];
		$invalid = [];
		foreach ( $group->get_users() as $user ) {
			$membership = new Membership( $user->ID );
			$data       = [
				'user'       => $user,
				'valid_from' => $membership->get_subscription_date( $group->id ),
				'valid_to'   => $membership->get_valid_to( $group->id ),
			];
			if ( $membership->is_valid_for_group( $group->id ) ) {
				$valid[] = $data;
			} else {
				$invalid[] = $data;
			}
		}
		?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $("#ss-user-search").keyup(function () {
                    let value = this.value.toLowerCase().trim();

                    $("table tr").each(function (index) {
                        if (!index) return;
                        $(this).find("td").each(function () {
                            let id = $(this).text().toLowerCase().trim();
                            let not_found = (id.indexOf(value) === -1);
                            $(this).closest('tr').toggle(!not_found);
                            return not_found;
                        });
                    });
                });
            });
        </script>
        <div>
            <label for="ss-user-search"><?php
				_e( 'Search users', 'simpleshop-cz' ); ?></label>
            <input type="text" name="ss-user-search" id="ss-user-search">
        </div>
		<?php
		if ( ! empty( $valid ) ) {
			$this->group_users_table( $valid, __( 'Active users', 'simpleshop-cz' ) );
		}
		if ( ! empty( $invalid ) ) {
			$this->group_users_table( $invalid, __( 'Inactive users', 'simpleshop-cz' ) );
		}
	}

	public function group_users_table( $items, $heading ) {
		?>
        <h3><?php
			echo $heading; ?></h3>
        <table class="wp-list-table widefat fixed striped table-view-list users">
            <thead>
            <tr>
                <th><?php
					_e( 'Name', 'simpleshop-cz' ) ?></th>
                <th><?php
					_e( 'Email', 'simpleshop-cz' ) ?></th>
                <th><?php
					_e( 'Valid from', 'simpleshop-cz' ) ?></th>
                <th><?php
					_e( 'Valid to', 'simpleshop-cz' ) ?></th>
            </tr>
            </thead>
            <tbody>
			<?php
			foreach ( $items as $item ) { ?>
                <tr>
                    <td>
                        <a href="<?php
						echo esc_attr( get_edit_user_link( $item['user']->ID ) ) ?>">
							<?php
							echo esc_html( $item['user']->display_name ) ?>
                        </a>
                    </td>
                    <td><?php
						echo esc_html( $item['user']->user_email ) ?></td>
                    <td><?php
						echo esc_html( $item['valid_from'] ) ?></td>
                    <td><?php
						echo esc_html( $item['valid_to'] ) ?></td>
                </tr>
				<?php
			} ?>
            </tbody>
        </table>
		<?php
	}

	public function adjust_meta_values_get( $override, $post_id, $args, $field ) {
		if ( $args['field_id'] === $this->prefix . 'date_to_access' ) {
			return strtotime( get_post_meta( $post_id, $this->prefix . 'date_to_access', true ) );
		}
		if ( $args['field_id'] === $this->prefix . 'date_until_to_access' ) {
			return strtotime( get_post_meta( $post_id, $this->prefix . 'date_until_to_access', true ) );
		}

		return $override;
	}

	public function adjust_meta_values_save( $override, $data, $args, $field ) {
		if ( $data['field_id'] === $this->prefix . 'date_to_access' || $data['field_id'] === $this->prefix . 'date_until_to_access' ) {
			update_post_meta( $data['id'], $data['field_id'], date( 'Y-m-d H:i:s', $data['value'] ) );

			return true;
		}

		return $override;
	}

	public function save_field( $field_id, $data, $args, $field ) {
		if ( $field_id === $this->prefix . 'date_to_access' || $field_id === $this->prefix . 'date_until_to_access' ) {
			if ( empty( $_POST[ $field_id ]['date'] ) ) {
				delete_post_meta( $_POST['post_ID'], $field_id );
			}
		}
	}

}
