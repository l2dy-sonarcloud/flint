<?php

$user = Nano_Session::user();
Nano_Session::delete($user);

header('Location: /');
die();
