<?php
// my-plugin/includes/requests/MyUserRequest.php
include_once plugin_dir_path(__FILE__) . '/MyRequest.php';

class MyUserRequest extends MyRequest
{
    /**
     * Function which make a connexion request to community
     * @param $current_user_login
     * @param $password
     * @return string
     */
    public function connexion($current_user_login, $password)
    {
        $params = array(
            'headers' => array('Content-type' => 'application/json; charset=utf8'),
            'body' => wp_json_encode([
                'username' => $current_user_login,
                'password' => $password
            ]),
            'timeout' => 15,
            'method' => 'POST'
        );
        $url = sprintf('http://%s/login', SECURITY);
        $response = wp_remote_post($url, $params);
        if (!$this->isTimeOutOrExpired($response) &&
            200 == wp_remote_retrieve_response_code($response)
        ) {
            $response = json_decode(wp_remote_retrieve_body($response), true);
            setcookie('token', $response['token'], time() + 24 * 3600);//, null, null, false, true); // we register the token in client's cookie
            return true;
        }
        return false;
    }

    /**
     * Function which make a request to change the community password of the current user
     * @param $userId
     * @param $current_password
     * @param $first
     * @param $second
     * @return bool
     */
    public function changePassword($userId, $current_password, $first, $second)
    {
        $data = [
            'current_password' => $current_password,
            'plainPassword' => [
                'first' => $first,
                'second' => $second
            ]
        ];
        $url = sprintf('http://%s/password/%s/edit', SECURITY, $userId);
        return $this->post($url, 'POST', $data);
    }

    public function newUser($body)
    {
        $url = sprintf('http://%s/register', SECURITY);
        return $this->new($url, $body);
    }

    /**
     * Function which make a request to get the profile of community member
     * @param $id
     * @return array|mixed|object|string
     */
    public function getProfile($id)
    {
        $url = sprintf('http://%s/profile/%s', SECURITY, $id);
        return $this->get($url);
    }

    /**
     * Function which send a request to edit an user in community db
     * @param $id
     * @param $data
     * @return bool
     */
    public function edit($id, $data)
    {
        $url = sprintf('http://%s/profile/%s/edit', SECURITY, $id);
        return $this->post($url, 'PUT', $data);
    }

    /**
     * Function which make a request to rest password
     * @param $userName
     * @return bool
     */
    public function requestResetPassword($userName)
    {
        $url = sprintf('http://%s/password/reset/request', SECURITY);
        $response = $this->post($url, 'POST', ['username' => $userName]);
        if ($response != false && $response['code'] == 200) {
            return $response['response']['token'];
        }
        return $response;
    }

    /**
     * Function which make a request to confirm rest password
     * @param $token
     * @param $newPass
     * @return bool
     */
    public function confirmResetPassword($token, $newPass)
    {
        $url = sprintf('http://%s/password/reset/confirm?token=%s', SECURITY, $token);
        $data = [
            'token' => $token,
            'plainPassword' => [
                'first' => $newPass,
                'second' => $newPass
            ]
        ];
        return $this->post($url, 'POST', $data);
    }
}
