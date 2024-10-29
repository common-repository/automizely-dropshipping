<?php
/**
 * Plugin Name: Automizely - Free AliExpress Dropshipping Plugin. Drop Ship Fast 3-10 days to the US
 * Description: One-stop-shop for Dropshipping with over 100,000 winning AliExpress Dropshipping products. Reliable, fast, and trackable, 3-10 days shipping to the US.
 * Version: 1.0.9
 * Author: aftership
 * Author URI: https://www.automizely.com/dropshipping/
 * Copyright: © AfterShip
 * License: GPL2
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

define('AUTOMIZELY_DROPSHIPPING_VERSION', '1.0.9');
define('AUTOMIZELY_DROPSHIPPING_PATH', dirname(__FILE__));
define('AUTOMIZELY_DROPSHIPPING_FOLDER', basename(AUTOMIZELY_DROPSHIPPING_PATH));
define('AUTOMIZELY_DROPSHIPPING_URL', plugins_url() . '/' . AUTOMIZELY_DROPSHIPPING_FOLDER);
require __DIR__ . '/includes/api/dropshipping/v1/class-am-dp-rest-settings-controller.php';


class AUTOMIZELY_DROPSHIPPING_Plugin_Base
{
    public function __construct()
    {
        add_filter('rest_shop_order_collection_params', array( $this, 'add_collection_params' ), 10, 1);
        add_filter('rest_shop_coupon_collection_params', array( $this, 'add_collection_params' ), 10, 1);
        add_filter('rest_product_collection_params', array( $this, 'add_collection_params' ), 10, 1);
        add_filter('woocommerce_rest_orders_prepare_object_query', array( $this, 'add_query' ), 10, 2);
        add_filter('woocommerce_rest_product_object_query', array( $this, 'add_query' ), 10, 2);
        add_filter('woocommerce_rest_shop_coupon_object_query', array( $this, 'add_query' ), 10, 2);
        add_filter('woocommerce_rest_customer_query', array( $this, 'add_customer_query' ), 10, 2);
        // Activation Hook
        register_activation_hook(__FILE__, 'automizely_dropshipping_activation_hook');
        // Deactivation Hook
        register_deactivation_hook(__FILE__, 'automizely_dropshipping_deactivation_hook');

        add_action('admin_enqueue_scripts', array($this, 'automizely_dropshipping_add_admin_css'));
        // register admin pages for the plugin
        add_action('admin_menu', array($this, 'automizely_dropshipping_admin_pages_callback'));

        // Translation-ready
        add_action('plugins_loaded', array($this, 'automizely_dropshipping_add_textdomain'));

        // Check if woocommerce active
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
            add_filter('woocommerce_rest_api_get_rest_namespaces', [ $this, 'add_rest_api' ]);
        }
        /**
         * Admin Initialization calls registration
         * We need this to send user to plugin's Admin page on activation
         */
        add_action('admin_init', 'automizely_dropshipping_plugin_redirect');

        add_action('admin_footer', array($this, 'deactivate_modal'));
    }

    /**
     * Register REST API endpoints
     *
     * @param  $controllers
     * @return mixed
     */
    function add_rest_api($controllers )
    {
        $controllers['wc/dropshipping/v1']['settings'] = 'AM_DP_REST_Settings_Controller';
        return $controllers;
    }

    /**
     * Add 'modified_after' and 'modified_before' for data query
     *
     * @param  array           $args
     * @param  WP_REST_Request $request
     * @return array
     */
    function add_query( array $args, $request )
    {
        $modified_after  = $request->get_param('modified_after');
        $modified_before = $request->get_param('modified_before');
        if (! $modified_after || ! $modified_before ) {
            return $args;
        };
        $args['date_query'][] = array(
            'column' => 'post_modified',
            'after'  => $modified_after,
            'before' => $modified_before,
        );
        return $args;
    }

    /**
     * Add 'modified_after' and 'modified_before' for data query
     *
     * @param  array           $args
     * @param  WP_REST_Request $request
     * @return array
     */
    function add_customer_query( array $args, $request )
    {
        $order           = $request->get_param('order');
        $modified_after  = $request->get_param('modified_after');
        $modified_before = $request->get_param('modified_before');
        if (! $modified_after || ! $modified_before ) {
            return $args;
        };
        // @notice may overwrite other service's query
        // @notice currently only AfterShip use modified_after & modified_before
        $args['meta_query'] = array(
            'modified' => array(
                'key'     => 'last_update',
                'value'   => array( strtotime($modified_after), strtotime($modified_before) ),
                'type'    => 'numeric',
                'compare' => 'BETWEEN',
            ),
        );
        $args['orderby']    = array(
            'modified' => $order ? $order : 'DESC',
        );
        return $args;
    }

    /**
     * Add 'modified' to orderby enum
     *
     * @param array $params
     */
    public function add_collection_params( $params )
    {
        $enums = $params['orderby']['enum'];
        if (! in_array('modified', $enums) ) {
            $params['orderby']['enum'][] = 'modified';
        }
        return $params;
    }

    /**
     * Description: Will add the backend CSS required for the display of automizely-dropshipping settings page.
     * Parameters:  hook | Not used.
     */
    public function automizely_dropshipping_add_admin_css($hook)
    {
        if ('toplevel_page_Automizely-Dropshipping' === $hook) {
            wp_register_style('automizely-dropshipping-admin', plugins_url('assets/css/index.css', __FILE__), array(), '1.0');
            wp_enqueue_style('automizely-dropshipping-admin');
            wp_register_style('automizely-dropshipping-admin', plugins_url('assets/css/normalize.css', __FILE__), array(), '1.0');
            wp_enqueue_style('automizely-dropshipping-admin');
        }
    }

    /**
     * Description: Will add the landing page into the Menu System of Wordpress
     * Parameters:  None
     */
    public function automizely_dropshipping_admin_pages_callback()
    {
        add_menu_page("Automizely Dropshipping", "Automizely Dropshipping", 'manage_options', 'Automizely-Dropshipping', array($this, 'automizely_dropshipping_admin_view'), AUTOMIZELY_DROPSHIPPING_URL . '/assets/images/favicon_dropshipping.svg');
    }


    /**
     * Description: The URL link is added to render the view setup as per the function
     * Parameters:  None
     */
    public function automizely_dropshipping_admin_view()
    {
        include_once AUTOMIZELY_DROPSHIPPING_PATH . '/views/automizely_dropshipping_admin_view.php';
    }


    /**
     * Description: --
     * Parameters:  None
     */
    public function automizely_dropshipping_add_textdomain()
    {
        load_plugin_textdomain('automizely_dropshipping', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

    public function deactivate_modal()
    {
        if (current_user_can('manage_options')) {
            global $pagenow;

            if ('plugins.php' !== $pagenow) {
                return;
            }
        }
    }

}

/**
 * Description: Called via admin_init action in Constructor
 *              Will redirect to the plugin page if the automizely_dropshipping_plugin_redirection is setup.
 *              Once redirection is pushed, the key is removed.
 * Return:      void
 **/
function automizely_dropshipping_plugin_redirect()
{
    if (get_option('automizely_dropshipping_plugin_redirection', false)) {
        delete_option('automizely_dropshipping_plugin_redirection');
        exit(wp_redirect("admin.php?page=Automizely-Dropshipping"));
    }
}


/**
 * Description: On installation of the plugin this will be called.
 *              We want to setup/update automizely_dropshipping related options at this time.
 * Return:      void
 **/
function automizely_dropshipping_activation_hook()
{
    // We want to take the user to the Plugin Page on installation.
    add_option('automizely_dropshipping_plugin_redirection', true);

}


/**
 * Description: On deactivation of the plugin this will be called.
 *              We want to delete automizely_dropshipping related options at this time.
 * Return:      void
 **/
function automizely_dropshipping_deactivation_hook()
{
    // If At all this was not removed already
    delete_option('automizely_dropshipping_plugin_redirection');
}

new AUTOMIZELY_DROPSHIPPING_Plugin_Base();
?>
