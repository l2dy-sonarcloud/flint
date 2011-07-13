<?php

$user   = Nano_Session::user();
$fossil = new Nano_Fossil($user);

if ($repo = $fossil->getRepoById($_GET['id'])) {
    $fossil->pullRepo($repo['name']);
    $_SESSION['pull'] = $repo['name'];
}

header('Location: /secure/');
die();
