<?php
// my-plugin/includes/requests/MyRequest.php

abstract class MyRequest
{
	public $entityUrl;

	/**
	 * Function which make a request to create new data
	 */
	public function new($url, $data)
	{
		$params = array(
	        'headers' => array('Content-type' => 'application/json; charset=utf8'),
	        'body' => wp_json_encode($data),
	        'timeout' => 15
	    );
	    $response = wp_remote_post($url, $params);
	    if ($this->isTimeOutOrExpired($response)) {
	    	return false;
	    }
	    if (201 != wp_remote_retrieve_response_code($response)) {
	    	$response = json_decode(wp_remote_retrieve_body($response), true);
            $_SESSION['my-message'] = '<div class="notice notice-error is-dismissible">
                        					<p>' . $response['message'] . '</p>
                    				   </div>';
            return false;
        }
        return json_decode(wp_remote_retrieve_body($response), true);
	}

	/**
	 * Function which make a get request
	 */
	public function get($url)
	{
		$params = array(
	        'headers' => array('Authorization' => 'Bearer ' . $_COOKIE['token']),
	        'timeout' => 15
	    );
	    $response = wp_remote_get($url, $params);
	    if ($this->isTimeOutOrExpired($response)) {
	    	return false;
	    }
	    if (200 != wp_remote_retrieve_response_code($response)) {
	    	// for 404 error
	    	if (404 == wp_remote_retrieve_response_code($response)) {
	    		$clasName = get_class($this);
	    		$clasName = preg_match('#^My(.*)Request$#', $clasName, $matches);
        		$clasName = $matches[1];
	    		$message = '<div class="notice notice-error is-dismissible">
        						<p>' . $clasName . 'is not found.</p>
    		 		    	</div>';
	    	} else {
	    		$response = json_decode(wp_remote_retrieve_body($response), true);
		    	// show any error
		    	$message = '<div class="notice notice-error is-dismissible">
	        					<p>' . $response['message'] . '</p>
	    		 		    </div>';
	    	}
    		do_action('admin_notices', $message);
            return false;
	    }
	    return json_decode(wp_remote_retrieve_body($response), true);
	}

	/**
	 Function which make POST|PUT request exept newAction
	 */
	public function post($url, $method, $data = null)
	{
		$params = array(
            'headers' => array(
                'Content-type' => 'application/json; charset=utf8',
                'Authorization' => 'Bearer ' . $_COOKIE['token']
            ),
            'method' => $method,
            'timeout' => 15
        );
        if (isset($data)) {
        	$params['body'] = wp_json_encode($data);
        }
        $response = wp_remote_post($url, $params);
        if ($this->isTimeOutOrExpired($response)) {
        	return false;
        }
        $notice = 'success';
        if (204 != wp_remote_retrieve_response_code($response) ||
        	200 != wp_remote_retrieve_response_code($response)) {
        	$notice = 'error';
        }
        // no content response doesn't have any message to display
    	if (204 != wp_remote_retrieve_response_code($response)) {
    		// 404 error
    		if (404 == wp_remote_retrieve_response_code($response)) {
	    		$clasName = get_class($this);
	    		$clasName = preg_match('#^My(.*)Request$#', $clasName, $matches);
        		$clasName = $matches[1];
	    		$_SESSION['my-message'] = '<div class="notice notice-error is-dismissible">
        									   <p>' . $clasName . 'is not found.</p>
    		 		    				   </div>';
	    	} else {
	    		$message = json_decode(wp_remote_retrieve_body($response), true);
	    		// here we display any message (error or not)
	        	$_SESSION['my-message'] = '<div class="notice notice-' . $notice . ' is-dismissible">
	            	    				       <p>' . $message['message'] . '</p>
	            						   </div>';
	    	}
    	}
		return [
			'response' => json_decode(wp_remote_retrieve_body($response), true),
			'code' => wp_remote_retrieve_response_code($response)
		];
	}

	/**
	 * Function which make a delete request
	 */
	public function delete($url)
	{
		$params = array(
	        'headers' => array(
	            'Content-type' => 'application/json; charset=utf8',
	            'Authorization' => 'Bearer ' . $_COOKIE['token']
	        ),
	        'method' => 'DELETE',
	        'timeout' => 15
	    );
	    $response = wp_remote_request($url, $params);
	    if (! $this->isTimeOutOrExpired($response)) {
	    	$response = json_decode(wp_remote_retrieve_body($response), true);
	    	// here we display any message (error or not)
	    	$_SESSION['my-message'] = '<div class="notice notice-success is-dismissible">
    										<p>' . $response['message'] . '</p>
 					    			   </div>';
	    }
	    wp_redirect($this->entityUrl);
	    exit;
	}

	/**
	 * Function which check response
	 */
	public function isTimeOutOrExpired($response)
	{
		// most of the time caused by timeout
		if (is_wp_error($response)) {
            $message = '<div class="notice notice-warning is-dismissible">
							<p>Delai d\'attente depasse. Veuillez ressayer !</p>
	    	 		    </div>';
    		do_action('admin_notices', $message);
            return true;
        }
        // token expired
	    if (401 == wp_remote_retrieve_response_code($response)) {
	    	setcookie('token', null, -1, '/'); // unset the cookie to avoid some future error
	        wp_logout();
	        return true;
	    }
        return false; // if no error
	}

	/**
	 * Function which set EntityUrl
	 */
	public function setEntityUrl($url)
	{
		$this->entityUrl = $url;
	}
}
