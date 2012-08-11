<?php

// symfony directories
$sf_symfony_lib_dir  = dirname(__FILE__).'/../lib/symfony';
$sf_symfony_data_dir = dirname(__FILE__).'/../data/symfony';

if(!function_exists('vv')) {
    function vv()
    {
        foreach (func_get_args() as $arg)
        {
            var_dump($arg);
        }
    }

    function earc($array, $funcName)
    {
        foreach ($array as $a)
        {
            vv($a->$funcName());
        }
    }

    function showdebug()
    {
        $css = file_get_contents(SF_ROOT_DIR . '/web/sf/sf_web_debug/css/main.css');
        $js = file_get_contents(SF_ROOT_DIR . '/web/sf/sf_web_debug/js/main.js');
        echo '<style>' . $css . '</style>';
        echo '<script>' . $js . '</script>';
    }

    function e()
    {
        exit();
    }
}