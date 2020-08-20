<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

date_default_timezone_set('UTC');

function __autoload($class)
{
    $class = strtolower(str_replace('_', '/', $class));
    require_once(dirname(__FILE__) . '/../' . $class . '.php');
}
