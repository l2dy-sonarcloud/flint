<?php

function __autoload($class)
{
    $class = strtolower(str_replace('_', '/', $class));
    require_once('../' . $class . '.php');
}

date_default_timezone_set('America/Detroit');

Nano_View::title('Flint - Fossil SCM Hosting');
Nano_View::mtime(filemtime($_SERVER['DOCUMENT_ROOT'] . '/global/styles/wrapper.css'));
Nano_Session::init();
Nano_Router::init();
