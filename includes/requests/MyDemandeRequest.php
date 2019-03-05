<?php
// my-plugin/includes/requests/MyDemandeRequest.php
include_once plugin_dir_path(__FILE__) . '/MyRequest.php';

class MyDemandeRequest extends MyRequest
{
    public function __construct($offset = null)
    {
        $this->entityUrl = home_url('/wp-admin/admin.php?page=my-demandes&offset=' . $offset);
    }

    /**
     * Function which make a request to delete a demande
     * @param $demandeId
     * @return string
     */
    public function deleteDemande($demandeId)
    {
        $url = sprintf('http://%s/demandes/%s', DEMANDE, $demandeId);
        $this->delete($url);
    }

    /**
     * Function which make a request to get all the demandes
     * @param int $offset
     * @return array|mixed|object|string
     */
    public function getDemandes($offset = 1)
    {
        $url = sprintf('http://%s/demandes?offset=%s', DEMANDE, $offset);
        $this->entityUrl .= '&offset=' . $offset;
        return $this->get($url);
    }

    /**
     * Function which make a request to get one demande
     * @param $id
     * @return array|bool|mixed|object
     */
    public function getDemande($id)
    {
        $url = sprintf('http://%s/demandes/%s', DEMANDE, $id);
        return $this->get($url);
    }

    /**
     * Function which make a request to validate a demande
     * @param $id
     * @return array|bool
     */
    public function toggleState($id)
    {
        $url = sprintf('http://%s/demandes/%s/toggle-state', DEMANDE, $id);
        return $this->post($url, 'PUT');
    }
}
