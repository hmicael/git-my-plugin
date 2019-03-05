<?php
// my-plugin/list/MyDemandeList.php

include_once plugin_dir_path(__FILE__) . '/MyAbstractList.php';

class MyDemandeList extends MyAbstractList
{
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array('id', 'user_id', 'state');
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->data;
    }

    public function get_columns()
    {
        return array(
            'shop_name' => 'Shop name',
            'email' => 'Email',
            'description' => 'Description',
            'submitted_at' => 'Submitted at'
        );
    }

    public function get_sortable_columns()
    {
        $sortable_columns = [];
        foreach ($this->get_columns() as $key => $value) {
            $sortable_columns[$key] = [$key, true];
        }
        return $sortable_columns;
    }

    public function column_shop_name($item)
    {
        $actions = array(
            'Id' => sprintf('<span>Id : %s</span>', $item['id']),
            'create' => sprintf(
                '<a href="%s?page=my-demandes&demande=%s">Creer</a>',
                home_url('/wp-admin/admin.php'),
                $item['id']
            ),
            'delete' => sprintf(
                '<a href="%s?page=my-demandes&delete=%s" class="my_delete">Supprimer</a>',
                home_url('/wp-admin/admin.php'),
                $item['id']
            )
        );
        return sprintf('%1$s %2$s', $item['shop_name'], $this->row_actions($actions));
    }
}
