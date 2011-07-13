<?php

$user   = Nano_Session::user();
$fossil = new Nano_Fossil($user);

if ($repo = $fossil->getRepoById($_GET['id'])) {
    $fossil->remRepo($repo['name']);
}

header('Location: /secure/');
die();
