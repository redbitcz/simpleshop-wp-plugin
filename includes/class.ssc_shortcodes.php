<?php

namespace SSC;

class SSC_Shortcodes{

    function __construct(){
        add_action('init',array($this,'initialize'));

    }

    function initialize() {
        add_shortcode('SimpleShop-form',array($this,'simple_shop_form'));
        add_shortcode('SimpleShop-content',array($this,'simple_shop_content'));
    }


    function simple_shop_form($atts){
        $url = substr($_SERVER['SERVER_NAME'],-2) === 'lc' ? 'http://form.simpleshop.czlc' : 'https://form.simpleshop.cz';
        return '<script type="text/javascript" src="'.$url.'/iframe/js/?id='.$atts['id'].'"></script>';
    }

    function simple_shop_content($atts, $content = ""){
        $atts = shortcode_atts( array(
            'group_id' => '',
            'is_member' => '',
            'days_to_view' => '',
            'specific_date_from' => '',
            'specific_date_to' => '',

        ), $atts, 'SimpleShop-content' );

        $group_id = $atts['group_id'];
        $is_member = $atts['is_member'];
        $specific_date_from = $atts['specific_date_from'];
        $specific_date_to = $atts['specific_date_to'];
        $days_to_view = $atts['days_to_view'];

        if (!empty($specific_date_from)) {
            // Check against the from date, this has nothing to do with groups or other settings
            if (date('Y-m-d') < $specific_date_from)
                return '';
        }

        if (!empty($specific_date_to)) {
            // Check against the to date, this has nothing to do with groups or other settings
            if (date('Y-m-d') > $specific_date_to)
                return '';
        }

        // Stop if there's no group_id or is_member, and no specific date is set
        if (empty($group_id) || empty($is_member) && empty($specific_date_from) && empty($specific_date_to))
            return '';

        $group = new SSC_Group($group_id);

        if ($is_member == 'yes') {
            // Check, if the user is logged in and is member of the group, if not, bail
            if (!is_user_logged_in() || !$group->user_is_member_of_group(get_current_user_id()))
                return '';
        } else if ($is_member == 'no') {
            // Check, if the user is NOT a member of specific group. This includes non-logged-in users
            if (is_user_logged_in() && $group->user_is_member_of_group(get_current_user_id()))
                return '';
        } else {
            // If the is_member isn't 'yes' or 'no', the parameter is wrong, so stop here
            return '';
        }

        // Group check done, check if there are some days set and if is_member is yes
        // it doesn't make sense to check days condition for users who should NOT be members of a group
        if (!empty($days_to_view) && $is_member == 'yes') {
            $membership = new SSC_Membership(get_current_user_id());
            $subscription_date = $membership->groups[$group_id]['subscription_date'];
            // Compare against today's date
            if (date('Y-m-d') < date('Y-m-d',strtotime("$subscription_date + $days_to_view days"))) {
                return '';
            }
        }

        // Support shortcodes inside shortcodes

        // Fix for MioWEB
        $hook = $this->remove_anonymous_object_filter('the_content','visualEditorPage','create_content');

        $content = apply_filters('the_content',$content);

        // Add the filter back if needed
        if ($hook)
            add_filter($hook[0],$hook[1],$hook[2]);

        return $content;

    }

    /**
     * An utility function to remove any hook from a class
     * @param $tag
     * @param $class
     * @param $method
     * @return array|bool|void
     */
    function remove_anonymous_object_filter( $tag, $class, $method )
    {
        $filters = $GLOBALS['wp_filter'][ $tag ];

        if ( empty ( $filters ) )
        {
            return;
        }

        foreach ( $filters as $priority => $filter )
        {
            foreach ( $filter as $identifier => $function )
            {
                if ( is_array( $function)
                    and is_a( $function['function'][0], $class )
                    and $method === $function['function'][1]
                )
                {

                    remove_filter(
                        $tag,
                        array ( $function['function'][0], $method ),
                        $priority
                    );
                    return array($tag,array ( $function['function'][0], $method ),$priority);
                }
            }
        }

        return false;
    }

    function list_hooks( $hook = '' ) {
        global $wp_filter;

        if ( isset( $wp_filter[$hook]->callbacks ) ) {
            array_walk( $wp_filter[$hook]->callbacks, function( $callbacks, $priority ) use ( &$hooks ) {
                foreach ( $callbacks as $id => $callback )
                    $hooks[] = array_merge( [ 'id' => $id, 'priority' => $priority ], $callback );
            });
        } else {
            return [];
        }

        foreach( $hooks as &$item ) {
            // skip if callback does not exist
            if ( !is_callable( $item['function'] ) ) continue;

            // function name as string or static class method eg. 'Foo::Bar'
            if ( is_string( $item['function'] ) ) {
                $ref = strpos( $item['function'], '::' ) ? new \ReflectionClass( strstr( $item['function'], '::', true ) ) : new \ReflectionFunction( $item['function'] );
                $item['file'] = $ref->getFileName();
                $item['line'] = get_class( $ref ) == 'ReflectionFunction'
                    ? $ref->getStartLine()
                    : $ref->getMethod( substr( $item['function'], strpos( $item['function'], '::' ) + 2 ) )->getStartLine();

                // array( object, method ), array( string object, method ), array( string object, string 'parent::method' )
            } elseif ( is_array( $item['function'] ) ) {

                $ref = new \ReflectionClass( $item['function'][0] );

                // $item['function'][0] is a reference to existing object
                $item['function'] = array(
                    is_object( $item['function'][0] ) ? get_class( $item['function'][0] ) : $item['function'][0],
                    $item['function'][1]
                );
                $item['file'] = $ref->getFileName();
                $item['line'] = strpos( $item['function'][1], '::' )
                    ? $ref->getParentClass()->getMethod( substr( $item['function'][1], strpos( $item['function'][1], '::' ) + 2 ) )->getStartLine()
                    : $ref->getMethod( $item['function'][1] )->getStartLine();

                // closures
            } elseif ( is_callable( $item['function'] ) ) {
                $ref = new \ReflectionFunction( $item['function'] );
                $item['function'] = get_class( $item['function'] );
                $item['file'] = $ref->getFileName();
                $item['line'] = $ref->getStartLine();

            }
        }

        return $hooks;
    }

}

new SSC_Shortcodes();


