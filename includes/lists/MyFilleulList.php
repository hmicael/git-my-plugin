<?php
// my-plugin/list/MyFilleulList.php

include_once plugin_dir_path(__FILE__) . '/MyAbstractList.php';

class MyFilleulList extends MyAbstractList
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
        $columns = array(
            'user_name' => 'Filleuls',
            'email' => 'Email',
            'parrain' => 'Parrain'
        );
        if (isset($this->data['indirect'])) {
            $columns['indirect'] = 'Indirect';
        }
        return $columns;
    }

    public function column_parrain($item)
    {
        return isset($item['parrain']) ? sprintf('%1$s', $item['parrain']['user_name']) : 'Aucun';
    }

    public function column_user_name($item)
    {
        $actions = array(
            'Id' => sprintf('<span>Id : %s</span>', $item['user_id']),
            'delete' => sprintf(
                '<a href="%s?%s&delete=%s" class="my_delete">Supprimer</a>',
                home_url('/wp-admin/admin.php'),
                $_SERVER['QUERY_STRING'],
                $item['user_id']
            )
        );
        if (isset($_GET['indirect']) && $_GET['indirect'] == '1' && current_user_can('manage_options')) {
            $actions['filleul-to-assign'] = sprintf(
                '<a href="%s?page=my-parrains&action=assign&filleul-to-assign=%s">Assigner un parrain</a>',
                home_url('/wp-admin/admin.php'), $item['user_id']
            );
        }
        return sprintf('%1$s %2$s', $item['user_name'], $this->row_actions($actions));
    }
}
