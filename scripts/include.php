<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);

date_default_timezone_set('America/Detroit');

function __autoload($class)
{
    $class = strtolower(str_replace('_', '/', $class));
    require_once('../' . $class . '.php');
}
