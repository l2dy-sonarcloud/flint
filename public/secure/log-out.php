<?php

if (Nano_Session::user()) {
    Nano_Session::logout();
}

header('Location: /');
die();
