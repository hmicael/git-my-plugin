<?php
// my-plugin/list/MyAbstractList.php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

abstract class MyAbstractList extends WP_List_Table
{
    protected $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function get_columns()
    {
        $columns = [];
        foreach ($this->data[0] as $key => $value) {
            $columns[$key] = ucfirst(str_replace("_", " ", $key));
        }
        return $columns;
    }

    public function column_default($item, $column_name)
    {
        $key = array_keys($this->data[0]);
        if (in_array($column_name, $key)) {
            return stripcslashes($item[$column_name]);
        } else {
            return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function show()
    {
        echo '<div class="wrap">';
        $this->prepare_items();
        $this->display();
        echo '</div>';
    }
}
