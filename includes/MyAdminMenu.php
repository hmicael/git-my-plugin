<?php
// my-plugin/MyAdminMenu.php

class MyAdminMenu
{
    private $offset = 1;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        // setting offset for pagination
        if (isset($_GET['offset']) && $_GET['offset'] > 0) {
            $this->offset = (int)sanitize_text_field($_GET['offset']);
        }
    }

    /**
     * Function which display all plugin page
     * @return void
     */
    public function add_admin_menu()
    {
        add_menu_page('My Plugin', 'My Plugin', 'edit_posts', 'my-plugin', array($this, 'my_plugin_html'));
        add_submenu_page('my-plugin', 'Demandes', 'Demandes', 'manage_options', 'my-demandes', array($this, 'demande_html'));
        add_submenu_page('my-plugin', 'Chiffre d\'affaire', 'Chiffre d\'affaire', 'edit_posts', 'my-ca', array($this, 'ca_html'));
        add_submenu_page('my-plugin', 'Parrains', 'Parrains', 'manage_options', 'my-parrains', array($this, 'parrain_html'));
        add_submenu_page('my-plugin', 'Filleuls', 'Filleuls', 'edit_posts', 'my-filleuls', array($this, 'filleul_html'));
    }

    public function my_plugin_html()
    {
        $current_user = wp_get_current_user();
        $userRequest = new MyUserRequest();
        echo '<h1>' . get_admin_page_title() . '</h1>';
        $response = $userRequest->getProfile($current_user->ID);
        if ($response) {
            var_dump($response);
            ?>
            <p>
                Nom: <?= $response['username'] ?> <br/>
                Email: <?= $response['email'] ?>
            </p>
            <?php
        }
    }

    /**
     * Function which display the page about demande
     * @return void
     */
    public function demande_html()
    {
        $demandeRequest = new MyDemandeRequest($this->offset);
        echo '<h1>' . get_admin_page_title() . '</h1>';
        $response = $demandeRequest->getDemandes($this->offset);
        if ($response) {
            $items = $response['_embedded']['items'];
            $myListTable = new MyDemandeList(); // creation of an instance of WP_List for displaying demande
            $myListTable->setData($items);
            $myListTable->show();
            $this->show_pagination($response['total'], $this->offset);
        }
        // when deleting a demande
        if (isset($_GET['delete']) && !empty($_GET['delete']) && current_user_can('manage_options')) {
            $demandeId = (int)sanitize_text_field($_GET['delete']);
            $demandeRequest->deleteDemande($demandeId);
        }
    }

    /**
     * Function called to show pagination for different kind of list
     * @param $total
     * @param int $activeOffset
     */
    public function show_pagination($total, $activeOffset = 1)
    {
        if ($total == 0) {
            return;
        }
        $url = home_url('/wp-admin/admin.php?' . $_SERVER['QUERY_STRING']);
        ?>
        <div class="pagination">
            <?php
            $page = ceil($total / 20); // 20 est la limite par page
            foreach (range(1, $page) as $value) {
                //($value*20)-19 pour obtenir l'offest du premier element de la page
                $offset = (($value * 20) - 19);
                if ($activeOffset == $offset) {
                    echo '<a class="active" href="' . $url . '&offset=' . $offset . '"/>' . $page . '</a>';
                } else {
                    echo '<a href="' . $url . '&offset=' . $offset . '"/>' . $page . '</a>';
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Function which display the page aboout
     * @return void
     */
    public function ca_html()
    {
        $caRequest = new MyCARequest();
        $response = null;
        // if a specified year is set, else it'll be the current year
        $year = (isset($_GET['year']) && !empty($_GET['year'])) ? sanitize_text_field($_GET['year']) : date('Y');
        // if some user_id is specified, else it'll be the current_user_id
        $user_id = (isset($_GET['id']) && !empty($_GET['id']) && current_user_can('manage_options')) ? (int)sanitize_text_field($_GET['id']) : wp_get_current_user()->ID;
        echo '<h1>' . get_admin_page_title() . ' annee ' . $year . '</h1>';
        // if a specified date for CA is requested
        if (isset($_GET['date']) && !empty($_GET['date'])) {
            $date = sanitize_text_field($_GET['date']);
            $date = str_replace('-', '/', substr($date, 0, 7));
            $response = $caRequest->getCaByDate($user_id, $date); // get the list of CA
            if ($response) {
                $items = $response['_embedded']['orders'];
                $myListTable = new MyOrderList();
                $myListTable->setData($items);
                $date = strftime('%B', strtotime(sanitize_text_field($_GET['date'])));
                echo '<h2>Commandes du mois de ' . $date . '</h2>';
                $myListTable->show();
            }
        } else { // the list of current year CA will be requested, it's the default action
            $response = $caRequest->getCA($user_id, $year); // get the list of CA
            if ($response) {
                $items = $response['_embedded']['items'];
                $myListTable = new MyCAList(); // creation of an instance of WP_List for displaying CA
                $myListTable->setData($items);
                $myListTable->show();
            }
            //pagination encore a mettre en forme
            echo '<div class="pagination">';
            foreach (range(2018, 2021) as $value) {
                if ($value == $year) {
                    echo '<a class="active" href="' . home_url('/wp-admin/admin.php?page=my-ca&year=' . $value) . '">' . $value . '</a>';
                } else {
                    echo '<a href="' . home_url('/wp-admin/admin.php?page=my-ca&year=' . $value . '">' . $value) . '</a>';
                }
            }
            echo "</div>";
        }
    }

    public function parrain_html()
    {
        $parrainRequest = new MyParrainRequest($this->offset);
        echo '<h1>' . get_admin_page_title() . '</h1>';
        /* BEGIN : Assign a filleul to a parrain */
        if (isset($_GET['action']) && $_GET['action'] == 'assign') {
            // mila confirmena ny sotrie de la page pour ne pas casser la tache
            if (isset($_GET['filleul-to-assign']) && !empty($_GET['filleul-to-assign']) && current_user_can('manage_options')) {
                echo '<h2>Choisissez un parrain : </h2>';
                if (isset($_GET['chosen-parrain']) && !empty($_GET['chosen-parrain'])) {
                    $parrainRequest->assignFilleulToParrain(
                        (int)sanitize_text_field($_GET['chosen-parrain']),
                        (int)sanitize_text_field($_GET['filleul-to-assign'])
                    );
                }
            }
        }
        /* END : Assign a filleul to a parrain */
        $response = $parrainRequest->getParrains($this->offset);
        if ($response) {
            $items = $response['_embedded']['items'];
            $myListTable = new MyParrainList($items);
            $myListTable->setData($items);
            $myListTable->show();
            $this->show_pagination($response['total'], $this->offset);
        }
        if (isset($_GET['delete']) && !empty($_GET['delete']) && current_user_can('manage_options')) {
            $id = (int)sanitize_text_field($_GET['delete']);
            $parrainRequest->deleteParrain($id);
        }
    }

    public function filleul_html()
    {
        $filleulRequest = new MyFilleulRequest($this->offset);
        $current_user = wp_get_current_user();
        $parrainId = $current_user->ID;
        echo '<h1>' . get_admin_page_title() . '</h1>';
        // button to see indirect filleul
        if (current_user_can('manage_options') && empty($_GET['indirect'])) {
            echo '<a href="' . home_url('/wp-admin/admin.php?page=my-filleuls&indirect=1') . '">Voir les filleuls sans parrain</a>';
        }
        // when an admin wants to see one parrain's filleul list
        if (isset($_GET['parrain']) && !empty($_GET['parrain']) && current_user_can('manage_options')) {
            $parrainId = (int)sanitize_text_field($_GET['parrain']);
            $filleulRequest->setEntityUrl($filleulRequest->entityUrl . '&parrain=' . $parrainId);
        }
        // when an admin wants to see filleul who doesn't have parrain (customer from direct inscription)
        if (isset($_GET['indirect']) && $_GET['indirect'] == '1' && current_user_can('manage_options')) {
            $filleulRequest->setEntityUrl($filleulRequest->entityUrl . '&indirect=1');
            $response = $filleulRequest->getIndirectFilleuls();
        } else {
            $response = $filleulRequest->getFilleuls($parrainId, $this->offset); // get the filleuls list of the current user
        }
        if ($response) {
            $items = $response['_embedded']['items'];
            $myListTable = new MyFilleulList();
            $myListTable->setData($items);
            $myListTable->show();
            $this->show_pagination($response['total'], $this->offset);
        }
        // when an admin wants to delete a filleul
        if (isset($_GET['delete']) && !empty($_GET['delete']) && current_user_can('edit_posts')) {
            $id = (int)sanitize_text_field($_GET['delete']);
            $filleulRequest->deleteFilleul($id);
        }
        // invite someone
        echo '<h2>Invitez vos amis</h2>';
        if (isset($_POST['send_invitation']) && $_POST['send_invitation'] == 1) {
            $emails = sanitize_textarea_field($_POST['emails']);
            $emails = explode(';', $emails);
            $not_exist_email = [];
            foreach ($emails as $email) {
                if (!email_exists($email)) {
                    $not_exist_email[] = sanitize_email($email); //need to do something
                }
            }
            $data = [
                'emails' => $not_exist_email,
                'parrain' => [
                    'user_id' => $parrainId,
                    'user_name' => $current_user->user_login,
                    'email' => $current_user->user_email,
                    'filleuls' => []
                ]
            ];
            $parrainRequest = new MyParrainRequest($this->offset);
            echo $parrainRequest->sendInvitation($data);
        }
        include_once plugin_dir_path(__FILE__) . '../templates/parrainageForm.php';
    }
}
