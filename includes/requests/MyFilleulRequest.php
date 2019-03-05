<?php
// my-plugin/includes/requests/MyFilleulRequest.php
include_once plugin_dir_path(__FILE__) . '/MyRequest.php';

class MyFilleulRequest extends MyRequest
{

    public function __construct($offset = null)
    {
        $this->entityUrl = home_url('/wp-admin/admin.php?page=my-filleuls&offset=' . $offset);
    }

    /**
     * Get a list of Filleul
     * @param $parrainId
     * @param int $offset
     * @return array|mixed|object|string
     */
    public function getFilleuls($parrainId, $offset = 1)
    {
        $url = sprintf('http://%s/filleuls/%s/list?offset=%s', AFFILIATION, $parrainId, $offset);
        $this->entityUrl .= '&offset=' . $offset;
        return $this->get($url);
    }

    /**
     * Returns the list of Filleul who doesn't have a parrain (from direct inscription)
     * @param int $offset
     * @return array|mixed|object|string
     */
    public function getIndirectFilleuls($offset = 1)
    {
        $url = sprintf('http://%s/filleuls/indirect/list?offset=%s', AFFILIATION, $offset);
        $this->entityUrl .= '&offset=' . $offset;
        return $this->get($url);
    }

    /**
     * Get one Filleul
     * @param $user_id
     * @return array|mixed|object|string
     */
    public function getFilleul($user_id)
    {
        $url = sprintf('http://%s/filleul/%s', AFFILIATION, $user_id);
        return $this->get($url);
    }

    /**
     * Function which make a request to delete a filleul
     * @param $user_id
     * @return string
     */
    public function deleteFilleul($user_id)
    {
        $url = sprintf('http://%s/filleuls/%s', AFFILIATION, $user_id);
        $this->delete($url);
    }

    /**
     * Create a new Filleul
     * @param $data
     * @return integer
     */
    public function newFilleul($data)
    {
        $url = sprintf('http://affiliation-new-filleul/parrains/%s/new-filleul', $data['parrainId']);
        return $this->new($url, $data);
    }
}
