<?php
// my-plugin/includes/MyAdmission.php

class MyAdmission
{
    private $demandeRequest;

    public function __construct()
    {
        $this->demandeRequest = new MyDemandeRequest();
        add_action('edited_terms', array($this, 'validate_demande'), 10, 2);
        add_action('wp_loaded', array($this, 'insert_yith_shop_vendor_term'), 10);
    }

    public function insert_yith_shop_vendor_term()
    {
        if (isset($_GET['demande']) && !empty($_GET['demande'])) {
            $id = (int)sanitize_text_field($_GET['demande']);
            $response = $this->demandeRequest->getDemande($id);
            // if the resquest success
            if ($response) {
                $user = get_user_by('ID', $response['user_id']);
                $_SESSION['user_id'] = $response['user_id'];
                $_SESSION['user_email'] = $response['email'];
                $_SESSION['user_name'] = $user->user_login;
                $_SESSION['demandeId'] = $response['id'];
                // if the vendor already exist
                if (term_exists($response['shop_name'])) {
                    // get the term
                    $term = get_term_by('slug', $response['shop_name'], 'yith_shop_vendor');
                    // create the url to the page where user update the term
                    $url = get_edit_term_link($term->term_id, 'yith_shop_vendor', 'product') . '&create=' . $response['id'];
                    // redireaction to the url
                    wp_redirect($url);
                    exit;
                }
                // create a new yith_shop_vendor term
                $ids = wp_insert_term(
                    $response['shop_name'],
                    'yith_shop_vendor',
                    array(
                        'description' => $response['description'],
                        'slug' => $response['shop_name']
                    )
                );
                if (!is_wp_error($ids)) { // when there isn't any error
                    $url = get_edit_term_link($ids['term_id'], 'yith_shop_vendor', 'product') . '&create=' . $response['id']; // create the url to the page where user update the term
                    wp_redirect($url);
                    exit;
                } else {
                    // in case of error, we'll unset session variables to avoid any errors in the
                    // future demande validation
                    unset($_SESSION['user_id']);
                    unset($_SESSION['demandeId']);
                    unset($_SESSION['user_email']);
                    unset($_SESSION['user_name']);
                    ?>
                    <script type="text/javascript">
                        alert("Une erreur s'est produit. Veuillez ressayer !");
                    </script>
                    <?php
                }
            } elseif (401 == wp_remote_retrieve_response_code($response)) {
                // when user isn't authorized : do snthg
                wp_logout();
            } else {
                // when the get request fail
                $_SESSION['my-message'] = '<div class="notice notice-error is-dismissible">
                                                <p>
                                                    Une erreur est survenue. Veuillez ressayer!
                                                </p>
                                           </div>';
                wp_redirect(home_url('/wp-admin/admin.php?page=my-demandes&offset=1'));
                exit();
            }
        }
    }

    /**
     * Function called when a new yith_shop_vendor taxonomy is created
     * @param int $term_id
     * @param $taxonomy
     * @return void
     */
    public function validate_demande($term_id, $taxonomy)
    {
        if ($taxonomy != 'yith_shop_vendor' &&
            empty($_GET['create']) &&
            !current_user_can('manage_options')
        ) {
            return;
        }
        if (isset($_SESSION['demandeId']) && !empty($_SESSION['demandeId'])) {
            $demandeId = (int)sanitize_text_field($_SESSION['demandeId']);
            $response = $this->demandeRequest->toggleState($demandeId);
            if ($response == false || $response['code'] != 200) {
                //stop the current action must stop the current action but how :) ?
                // I choose to delete the term which was created for the new member
                wp_delete_term($term_id, $taxonomy);
                $_SESSION['my-message'] = '<div class="notice notice-error is-dismissible">
                                                <p>Une erreur est survenue. Veuillez ressayer !</p>
                                           </div>';
                wp_redirect(home_url('/wp-admin/admin.php?page=my-demandes&offset=1'));
                exit();
            } else {
                // if the request succeeded we create a community account for the user
                $userRequest = new MyUserRequest();
                $user = get_user_by('ID', $_SESSION['user_id']);
                $body = [
                    'user_id' => $_SESSION['user_id'],
                    'email' => $_SESSION['user_email'],
                    'username' => $_SESSION['user_name'],
                    'role' => 'ROLE_USER',
                    'plainPassword' => [
                        'first' => $user->user_pass,
                        'second' => $user->user_pass
                    ]
                ];
                $response = $userRequest->newUser($body);
                if ($response == false) {
                    //stop the current action and undo demande validation
                    $i = 3;
                    do {
                        $response = $this->demandeRequest->toggleState($demandeId);
                    } while ($i >= 0 && ($response == false || $response['code'] != 200));
                    // then delete the term which was created for the new member
                    wp_delete_term($term_id, $taxonomy);
                    $_SESSION['my-message'] = '<div class="notice notice-error is-dismissible">
                                                    <p>
                                                        Une erreur est survenue. Veuillez ressayer!
                                                    </p>
                                               </div>';
                    wp_redirect(home_url('/wp-admin/admin.php?page=my-demandes&offset=1'));
                    exit();
                }
                // if no error then do something to notify the user
                $result = wp_mail($_SESSION['user_email'], "essai", "essaimsg", array('From' => 'essai')); // example of notification
                // we'll unset session to avoid any future errors
                unset($_SESSION['user_id']);
                unset($_SESSION['demandeId']);
                unset($_SESSION['user_email']);
                unset($_SESSION['user_name']);
                wp_redirect(home_url('/wp-admin/admin.php?page=my-demandes&offset=1'));
                exit();
            }
        }
    }
}
