f<?php
// my-plugin/list/MyOrderList.php

include_once plugin_dir_path(__FILE__) . '/MyAbstractList.php';

class MyOrderList extends MyAbstractList
{
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array('user_id', 'order_id');
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->data;
    }

    public function get_columns()
    {
        $columns = [
            'total' => 'Total',
            'date_completed' => 'Date completed'
        ];
        return $columns;
    }

    public function get_sortable_columns()
    {
        $sortable_columns = [];
        foreach ($this->get_columns() as $key => $value) {
            $sortable_columns[$key] = [$key, true];
        }
        return $sortable_columns;
    }

    public function column_total($item)
    {
        $item['total'] .= ' Ar';
        $actions = array(
            'Id' => sprintf('<span>Id : %s</span>', $item['order_id']),
            'Voir' => sprintf(
                '<a href="%s?post=%s&action=edit">Voir</a>',
                home_url('/wp-admin/post.php'),
                $item['order_id']
            )
        );

        return sprintf('%1$s %2$s', $item['total'], $this->row_actions($actions));
    }
}
