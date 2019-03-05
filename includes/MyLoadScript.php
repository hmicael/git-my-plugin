<?php
// my-plugin/includes/MyLoadScript.php

class MyLoadScript
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'add_script'), 5);
        add_action('wp_enqueue_scripts', array($this, 'add_script'), 5);
        add_action('wp_enqueue_scripts', array($this, 'add_stylesheet'), 5);
        add_action('admin_enqueue_scripts', array($this, 'add_stylesheet'));
    }

    /**
     * Function which register a script
     * @return void
     */
    public function add_script()
    {
        //wp_register_script( 'script', plugins_url( 'assets/js/script.js', __FILE__ ), array( 'jquery' ), 23102018, true );
        wp_register_script('jQuery', plugins_url('../assets/js/jquery.js', __FILE__));
        wp_register_script('jquery.cookie', plugins_url('../assets/js/jquery.cookie.js', __FILE__));
        wp_register_script(
            'script',
            plugins_url('../assets/js/script.js', __FILE__),
            array(),
            23102018,
            true
        );
        wp_enqueue_script('jQuery');
        wp_enqueue_script('jquery.cookie');
        wp_enqueue_script('script');
    }

    /**
     * Function which register stylesheet
     * @return void
     */
    public function add_stylesheet()
    {
        wp_register_style(
            'custom-style',
            plugins_url('../assets/css/style.css', __FILE__),
            array(),
            '23102018',
            'all'
        );
        wp_enqueue_style('custom-style');
    }
}
