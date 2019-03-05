<?php
// my-plugin/MyPlugin.php

/*
Plugin Name: MyPlugin
Author: Henintsoa Micael
Description: Un plugin utilise pour le projet :)
*/
define('DEMANDE', 'apigateway/demande');
define('CA', 'apigateway/ca');
define('SECURITY', 'apigateway/security');
define('AFFILIATION', 'apigateway/affiliation');
setlocale(LC_ALL, 'fr_FR');

class MyPlugin
{

    public $products_id;

    /**
     * MyPlugin constructor.
     */
    public function __construct()
    {
        include_once plugin_dir_path(__FILE__) . '/includes/autoload.php';
        include_once plugin_dir_path(__FILE__) . '/widget/W_Registration.php';
        new MyAdminMenu();
        new MyFilleulRegistration();
        new MyOrderAdmin();
        new MyAdmission();
        new MyLoadScript();
        new MyUserAdmin();
        $this->products_id = [];
        add_action('init', array($this, 'start_session'), 1);
        add_action('wp_login', array($this, 'end_session'), 1);
        add_action('wp_logout', array($this, 'end_session'), 1);
        add_action('wp_logout', array($this, 'destroy_token'), 1);
        add_action('wp_loaded', array($this, 'set_current_user_products'));
        add_filter('woocommerce_is_purchasable', array($this, 'filter_woocommerce_is_purchasable'), 10, 2);
        add_action('widgets_init', function () {
            if (is_user_logged_in()) {
                register_widget('W_Registration');
            }
        });
        add_action('authenticate', array($this, 'connexion'), 30, 3);
        add_action('wp_dashboard_setup', array($this, 'my_custom_dashboard_widgets'));
        add_action('admin_notices', array($this, 'sample_admin_notice'), 20, 1);
        /*global $wp_filter; // test is register action name with callback function
        //print_r($wp_filter); exit;*/
    }

    /**
     * Display a notice
     * @param $message
     */
    public function sample_admin_notice($message)
    {
        if (is_admin()) {
            if (isset($_SESSION['my-message'])) {
                echo $_SESSION['my-message'];
                unset($_SESSION['my-message']);
            } else {
                echo $message;
            }
        }
    }


    /**
     * Fonction called when a user connect
     */
    public function connexion($user, $username, $password)
    {
        $role = (array)$user->roles;
        $role = $role[0];
        if (!is_wp_error($user) &&
            in_array($role, ['yith_vendor', 'shop_manager', 'administrator'])
        ) {
            $userRequest = new MyUserRequest();
            return $userRequest->connexion($username, $user->user_pass) ? $user : false;
        }
        return $user;
    }

    /**
     * Function which start the php sesion in wordpress way
     */
    public function start_session()
    {
        if (!session_id()) {
            session_start();
        }
        if (in_array(
                $this->get_current_user_role(),
                ['yith_vendor', 'shop_manager', 'administrator']
            ) &&
            !isset($_COOKIE['token'])
        ) {
            wp_logout();
        }
    }

    /**
     * Function which return the current user's role
     * @return mixed string $role or boolean false
     */
    public function get_current_user_role()
    {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $role = (array)$user->roles;
            return $role[0];
        } else {
            return false;
        }
    }

    /**
     * Function which end the php sesion in wordpress way
     */
    public function end_session()
    {
        session_destroy();
    }

    /**
     * Function which destroy token when the user log out
     */
    public function destroy_token()
    {
        setcookie('token', null, -1, '/');
    }

    /**
     * Function which return the list of the current user product
     * @return void
     */
    public function set_current_user_products()
    {
        $current_user_products = get_posts(['author' => get_current_user_id(),
            'post_type' => 'product']);
        $IDs = [];
        foreach ($current_user_products as $products) {
            $IDs[] = $products->ID;
        }
        $this->products_id = $IDs;
    }

    /**
     * Function which define the woocommerce_is_purchasable callback
     * @param $this_exists_publish_this_get_status_current_user_can_edit_post_this_get_id_this_get_price
     * @param $instance
     * @return boolean
     */
    public function filter_woocommerce_is_purchasable(
        $this_exists_publish_this_get_status_current_user_can_edit_post_this_get_id_this_get_price,
        $instance
    )
    {
        return is_user_logged_in() && in_array($instance->id, $this->products_id) ? false : true;
    }

    /**
     * Function which show notice about number of non-validated demande in the dashboard
     */
    public function my_demande_dashboard_widgets()
    {
        $params = array(
            'headers' => array(
                'Content-type' => 'application/json; charset=utf8',
                'Authorization' => 'Bearer ' . $_COOKIE['token']
            ),
            'timeout' => 15
        );
        $url = sprintf('http://%s/demandes/count', DEMANDE);
        $response = wp_remote_get($url, $params);
        if (wp_remote_retrieve_response_code($response) == 401) {
            wp_logout();
        }
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            echo '<p> Donnees temporairement indisponible </p>';
        }
        // token expired
        if (wp_remote_retrieve_response_code($response) == 200) {
            $response = wp_remote_retrieve_body($response);
            $url = home_url('/wp-admin/admin.php?page=my-demandes');
            echo '<p>
                    Il y a ' . $response . ' <a href="' . $url . '">demande(s)</a> d\'adhesion a la communaute 
                  </p>';
        }
    }

    /**
     * Function which initialize the dashboard widget
     */
    public function my_custom_dashboard_widgets()
    {
        global $wp_meta_boxes;
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'custom_help_widget',
                'Nouveau Demande d\'adhesion',
                array($this, 'my_demande_dashboard_widgets')
            );
        }
    }
}

new MyPlugin();
