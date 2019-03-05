<?php
// my-plugin/includes/MyOrderAdmin.php

class MyOrderAdmin
{
    public function __construct()
    {
        add_action('woocommerce_order_status_completed', array($this, 'save_order'), 10, 1);
        add_action('before_delete_post', array($this, 'delete_order'), 10, 1);
    }

    /**
     * Function called when an order is completed
     * @param int $order_id
     * @return void
     */
    public function save_order($order_id)
    {
        $order = wc_get_order($order_id);
        $post = get_post($order_id);
        $params = array(
            'headers' => array(
                'Content-type' => 'application/json; charset=utf8',
                'Authorization' => 'Bearer ' . $_COOKIE['token']
            ),
            'body' => wp_json_encode(
            	array(
            		'order_id' => $order_id,
		            'user_id' => $post->post_author,
		            'total' => $order->get_total(),
		            'date_completed' => $order->get_date_completed()->date_i18n()
		        )
            ),
            'timeout' => 15
        );
        $url = sprintf('http://%s/orders/new', CA);
        $response = wp_remote_post($url, $params); //request to add new Order of the current month CA
        //token expired
        if (401 == wp_remote_retrieve_response_code($response)) {
            wp_logout();
            return;
        }
        //if the request failed or timeout
        if (wp_remote_retrieve_response_code($response) != 201 || is_wp_error($response)) {
            $order->update_status('pending'); // the order won't be completed
            // then do something to notify the use
        }
    }

    /**
     * Function called before an completed order will be deleted
     * @param int $id
     * @return void
     */
    public function delete_order($id)
    {
        global $post_type;
        if ($post_type !== 'shop_order') {
            return;
        }
        $order = wc_get_order($id);
        if ($order->get_status() != 'completed') {
            return;
        }
        $params = array(
            'headers' => array(
                'Content-type' => 'application/json; charset=utf8',
                'Authorization' => 'Bearer ' . $_COOKIE['token']
            ),
            'method' => 'DELETE',
            'timeout' => 15
        );
        // making the request to delete the concerned Order from its CA
        $url = sprintf('http://%s/orders/%s', CA, $id);
        $response = wp_remote_request($url, $params);
        // token expired
        if (401 == wp_remote_retrieve_response_code($response)) {
            wp_logout();
            return;
        }
        if (200 != wp_remote_retrieve_response_code($response) || is_wp_error($response)) {
            $_SESSION['my-message'] = '<div class="notice notice-error is-dismissible">
                                            <p>Une erreur est survenue lors de la suppression. Veuillez ressayer !</p>
                                       </div>';
            wp_redirect($order->get_edit_order_url());
            exit();
        }
    }
}
