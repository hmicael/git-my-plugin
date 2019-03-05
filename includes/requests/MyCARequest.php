<?php
// my-plugin/includes/requests/MyCARequest.php

include_once plugin_dir_path(__FILE__) . '/MyRequest.php';

class MyCARequest extends MyRequest
{
    /**
     * Function which make a request to get a detailed CA information of a specified date
     * @param $user_id
     * @param $date
     * @return array|mixed|object|string
     */
    public function getCaByDate($user_id, $date)
    {
        $url = sprintf('http://%s/cas/%s/%s', CA, $user_id, $date);
        return $this->get($url);
    }

    /**
     * Function which make a request to get the CAs of a specifiead year
     * @param $user_id
     * @param $year
     * @param int $offset
     * @return array|mixed|object|string
     */
    public function getCA($user_id, $year, $offset = 1)
    {
        $url = sprintf('http://%s/cas/%s?year=%s&offset=%s', CA, $user_id, $year, $offset);
        return $this->get($url);
    }
}
