<?php

namespace SSC;

class SSC_Membership
{
    private $user_id;
    public $groups = array();

    /**
     * SSC_Membership constructor.
     * Get user data if requested
     * @param string $user_id
     */
    function __construct($user_id = '')
    {
        if ($user_id) {
            $this->user_id = (int)$user_id;
            $this->get();
        }
    }

    /**
     * Get membership data for a specific user
     */
    function get()
    {
        $ssc_groups = new SSC_Group();
        $groups = $ssc_groups->get_user_groups($this->user_id);

        foreach ($groups as $group) {
            $this->groups[$group] = [
                'group_id' => $group,
                'subscription_date' => $this->get_subscription_date($group),
                'valid_to' => $this->get_valid_to($group)
            ];
        }
    }

    /**
     * Set the date until the memership is valid
     * @param $group_id
     * @param $valid_to
     * @return bool|int
     */
    function set_valid_to($group_id, $valid_to)
    {
        if (!$this->user_id)
            return false;

        return update_user_meta($this->user_id, '_ssc_group_subscription_valid_to_' . $group_id, $valid_to);
    }

    /**
     * Set the date of user subscription to the group
     * @param $group_id
     * @return bool|int
     */
    function set_subscription_date($group_id)
    {
        if (!$this->user_id)
            return false;

        return update_user_meta($this->user_id, '_ssc_group_subscription_date_' . $group_id, date('Y-m-d'));
    }

    /**
     * Get group subscription date
     * @param $group_id
     * @return mixed
     */
    function get_subscription_date($group_id)
    {
        return get_user_meta($this->user_id, '_ssc_group_subscription_date_' . $group_id, true);
    }

    /**
     * Get the date until the subscription is valid for specific group
     * @param $group_id
     * @return mixed
     */
    function get_valid_to($group_id)
    {
        return get_user_meta($this->user_id, '_ssc_group_subscription_valid_to_' . $group_id, true);
    }
}

