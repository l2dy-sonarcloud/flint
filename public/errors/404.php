<?php

header('HTTP/1.0 404 Not Found');

$view = new Nano_View();
$view->title(' - 404 Not Found');
$view->dispatch();
