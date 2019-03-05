<?php
// my-plugin/autoload.php

function LoadClass($class)
{
    // only for MyPlugin class
    if (preg_match('#^My#', $class)) {
        if (preg_match('#List$#', $class)) { // if the class id a list
            include_once plugin_dir_path(__FILE__) . 'lists/' . $class . '.php';
        } elseif (preg_match('#Request$#', $class)) { // if the class is a request
            include_once plugin_dir_path(__FILE__) . 'requests/' . $class . '.php';
        } else {
            include_once plugin_dir_path(__FILE__) . $class . '.php';
        }
    }
}

spl_autoload_register('LoadClass');
