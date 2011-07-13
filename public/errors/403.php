<?php

header('HTTP/1.0 403 Forbidden');

$view = new Nano_View();
$view->title(' - 403 Forbidden');
$view->dispatch();
