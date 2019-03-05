<?php
// my-plugin/list/MyParrainList.php

include_once plugin_dir_path(__FILE__) . '/MyAbstractList.php';

class MyParrainList extends MyAbstractList
{
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array('id', 'user_id');
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->data;
    }

    public function get_columns()
    {
        return array(
            'user_name' => 'Parrains',
            'email' => 'Email',
            'bonus' => 'Bonus (Ar)',
            'filleuls' => 'Filleuls'
        );
    }

    public function column_user_name($item)
    {
        $actions = array(
            'Id' => sprintf('<span>Id : %s</span>', $item['user_id']),
            'Voir filleuls' => sprintf(
                '<a href="%s?page=my-filleuls&parrain=%s">Voir filleuls</a>',
                home_url('/wp-admin/admin.php'),
                $item['user_id']
            ),
            'delete' => sprintf(
                '<a href="%s?page=my-parrains&delete=%s" class="my_delete">Supprimer</a>',
                home_url('/wp-admin/admin.php'),
                $item['user_id']
            )
        );
        if (isset($_GET['action']) && $_GET['action'] == 'assign' && current_user_can('manage_options')) {
            $actions['chosen-parrain'] = sprintf(
                '<a href="%s?%s&chosen-parrain=%s">Assigner</a>',
                home_url('/wp-admin/admin.php'),
                $_SERVER['QUERY_STRING'],
                $item['user_id']
            );
        }
        return sprintf('%1$s %2$s', $item['user_name'], $this->row_actions($actions));
    }

    public function column_filleuls($item)
    {
        return sprintf('%1$s', count($item['filleuls']));
    }
}