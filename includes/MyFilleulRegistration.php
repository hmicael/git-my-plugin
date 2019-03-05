<?php
// my-plugin/includes/MyFilleulRegistration.php

class MyFilleulRegistration
{
    public function __construct()
    {
        add_action('init', array($this, 'before_registration'), 2);
        add_action('user_register', array($this, 'registration'), 10, 1);
        add_filter('woocommerce_add_error', array($this, 'my_woocommerce_add_error'));
    }

    /**
     * Function which filter error message on customer registration
     * @param $error
     * @return string $error
     */
    public function my_woocommerce_add_error($error) {
        if (isset($_SESSION['my-message']) && !is_admin()) {
            $error .= $_SESSION['my-message'];
            unset($_SESSION['my-message']);
        }
        return $error;
    }

    /**
     * Function which is called to check if a filleul want to register to woocoomerce
     */
    public function before_registration()
    {
        if (!is_user_logged_in() && isset($_GET['parrainage']) && $_GET['parrainage'] == '1') {
            if (isset($_POST['parrain_user_id']) && !empty($_POST['parrain_user_id'])) {
                $_SESSION['parrain_user_id'] = (int)sanitize_text_field($_POST['parrain_user_id']);
            }
        }
    }

    /**
     * Function which is called when a customer wants to register in woocommerce
     * @param $user_id
     */
    public function registration($user_id)
    {
        // Normally, a customer doesn't has to have a parrain so we set parrainId to 0
        // shop manager'll assign parrain to these customer in so that they become
        // indirect filleul
        $filleulRequest = new MyFilleulRequest();
        $user = get_user_by('ID', $user_id);
        $role = (array)$user->roles;
        $role = $role[0];
        if ($role == 'customer') {
            $data = [
                'user_id' => $user_id,
                'user_name' => $user->user_login,
                'email' => $user->user_email,
                'parrainId' => 0
            ];
            // if there is a specified parrain, parrainId'll be set to the corresponding parrain
            if (isset($_GET['parrainage']) && $_GET['parrainage'] == '1') {
                $data['parrainId'] = $_SESSION['parrain_user_id'];
                unset($_SESSION['parrain_user_id']);
            }
            // we process the request
            $response = $filleulRequest->newFilleul($data);
            // do something if the request failed, like deleting the new created woo user :)
            if ($response == false) {
                require_once(ABSPATH.'wp-admin/includes/user.php');
                $user_id = wp_delete_user($user_id);
                $_SESSION['my-message'] = '<li>
                                                Une erreur est survenue lors de la creation du compte. Veuillez ressayer !
                                           </li>';
                wp_redirect(home_url('/my-account'));
                exit();
            }
        }
    }
}
