<?php
// my-plugin/includes/requests/MyParrainRequest.php
include_once plugin_dir_path(__FILE__) . '/MyRequest.php';

class MyParrainRequest extends MyRequest
{

    public function __construct($offset = null)
    {
        $this->entityUrl = home_url('/wp-admin/admin.php?page=my-parrains&offset=' . $offset);
    }

    /**
     * Get list of Parrain
     * @param int $offset
     * @return array|mixed|object|string
     */
    public function getParrains($offset = 1)
    {
        $url = sprintf(
            'http://%s/parrains?offset=%s&monthReport=%s',
            AFFILIATION,
            $offset,
            $this->getSalesReportData()
        );
        $this->entityUrl .= '&offset=' . $offset;
        return $this->get($url);
    }

    /**
     * Get sales report data.
     * @return object
     */
    private function getSalesReportData()
    {
        require_once(ABSPATH . 'wp-content/plugins/woocommerce/includes/admin/reports/class-wc-admin-report.php');
        require_once(ABSPATH . 'wp-content/plugins/woocommerce/includes/admin/reports/class-wc-report-sales-by-date.php');
        $sales_by_date = new WC_Report_Sales_By_Date();
        $sales_by_date->start_date = strtotime(date('Y-m-01', current_time('timestamp')));
        // 86400s = 1day
        $sales_by_date->end_date = strtotime('+1month', $sales_by_date->start_date) - 86400;
        $sales_by_date->chart_groupby = 'day';
        $sales_by_date->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';
        return $sales_by_date->get_report_data()->net_sales;
    }

    /**
     * Get one Parrain
     * @param $user_id
     * @return array|mixed|object|string
     */
    public function getParrain($user_id)
    {
        $url = sprintf(
            'http://%s/parrains/%s?monthReport=%s',
            AFFILIATION,
            $user_id,
            $this->getSalesReportData()
        );
        return $this->get($url);
    }

    /**
     * Function which make a request to delete a Parrain with his Filleul
     * @param $user_id
     * @return string
     */
    public function deleteParrain($user_id)
    {
        $url = sprintf('http://%s/parrains/%s', AFFILIATION, $user_id);
        $this->delete($url);
    }

    /**
     * Create a new Parrain
     * @param $data
     * @return int|string
     */
    public function newParrain($data)
    {
        $url = sprintf('http://%s/parrains/new', AFFILIATION);
        return $this->new($url, $data);
    }

    /**
     * Send a invitation to join woocommerce customer by email
     * @param $data
     * @return string
     */
    public function sendInvitation($data)
    {
        $params = array(
            'headers' => array(
                'Content-type' => 'application/json; charset=utf8',
                'Authorization' => 'Bearer ' . $_COOKIE['token']
            ),
            'body' => wp_json_encode($data),
            'timeout' => 15
        );
        $url = sprintf('http://%s/parrains/invite', AFFILIATION);
        $response = wp_remote_post($url, $params);
        if (200 != wp_remote_retrieve_response_code($response) &&
            ! $this->isTimeOutOrExpired($response)
        ) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return '<div class="notice notice-error is-dismissible">
	                    <p>' . $body['message'] . '!</p>
	                </div>';
        }
        return '<div class="notice notice-success is-dismissible">
	                <p> Emails envoyes !</p>
	            </div>';
    }

    public function assignFilleulToParrain($parrainId, $filleulId)
    {
        $url = sprintf(
            'http://%s/parrains/%s/filleuls/%s/assign',
            AFFILIATION,
            $parrainId,
            $filleulId
        );
        $response = $this->post($url, 'PUT');
        wp_redirect($this->entityUrl);
        exit;
    }
}
