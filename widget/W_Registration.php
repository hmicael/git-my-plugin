<?php

class W_Registration extends WP_Widget
{
    private $current_user;
    private $error;

    public function __construct()
    {
        parent::__construct('W_Registration', 'Community Registration', array('description' => 'Un formulaire de demande d\'integration dans la communaute.'));
        $this->current_user = wp_get_current_user();
        add_action('wp_loaded', array($this, 'sendRequest'));
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        echo $args['before_title'];
        echo apply_filters('widget_title', $instance['title']);
        echo $args['after_title'];
        if ($this->error != "") {
            ?>
            <span class="my-error"><?= $this->error ?></span>
        <?php } ?>
        <form id="registration_form" action="" method="post">
            <p>
                <label for="shop_name">Votre identifiant:</label>
                <input id="shop_name" name="shop_name" type="text" value="<?= $this->current_user->user_login ?>"
                       required readonly/>
            </p>
            <p>
                <label for="shop_description">Votre decription:</label>
                <textarea id="shop_description" name="description"></textarea>
            </p>
            <input type="hidden" name="send_request" value="1"/>
            <input type="submit" value="Soummettre votre demande"/>
        </form>
        <?php
        echo $args['after_widget'];
    }

    /**
     *Function which show form in the back-office
     * @param $instance
     */
    public function form($instance)
    {
        $title = isset($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_name('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>"/>
        </p>
        <?php
    }

    public function sendRequest()
    {
        $role = (array)$this->current_user->roles;
        $role = $role[0];
        if (isset($_POST['send_request']) &&
            !empty($_POST['send_request']) &&
            $role == 'customer'
        ) {
            $body = [
                'user_id' => $this->current_user->ID,
                'shop_name' => $this->current_user->user_login,
                'email' => $this->current_user->user_email,
                'description' => htmlspecialchars($_POST['description']),
                'submitted_at' => date("Y-m-d"),
                'state' => false
            ];
            $body = wp_json_encode($body);
            $params = array(
                'headers' => array('Content-type' => 'application/json; charset=utf8'),
                'body' => $body,
                'timeout' => 15
            );
            $url = sprintf('http://%s/demandes/new', DEMANDE);
            $response = wp_remote_post($url, $params);
            if (wp_remote_retrieve_response_code($response) == 400) {
                $this->error = "Vous avez deja soumis votre demande !";
            }
            if (is_wp_error($response)) {
                $this->error = "Delai d'attente depasse ! Veuillez ressayer !";
            }
        } else {
            $this->error = "Seul les clients peuvent demander a entrer dans la communaute !";
        }
    }
}