<?php
// my-plugin/list/MyCAList.php

include_once plugin_dir_path(__FILE__) . '/MyAbstractList.php';

class MyCAList extends MyAbstractList
{
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array('user_id', '_embedded');
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->data;
    }

    public function get_columns()
    {
        $columns = [
            'total' => 'Total (Ar)',
            'date' => 'Date'
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
        $date = str_replace('-', '/', substr($item['date'], 0, 4));
        $actions = array(
            'List' => sprintf(
                '<a href="%s?page=my-ca&date=%s&year=%s">Lister</a>',
                home_url('/wp-admin/admin.php'),
                $item['date'],
                $date
            )
        );

        return sprintf('%1$s %2$s', $item['total'], $this->row_actions($actions));
    }

    public function column_date($item)
    {
        $item['date'] = preg_replace_callback(
            '#^(.+)$#',
            function ($matches) {
                return strftime('%h %G', strtotime($matches[0]));
            },
            $item['date']
        );
        return $item['date'];
    }
}
