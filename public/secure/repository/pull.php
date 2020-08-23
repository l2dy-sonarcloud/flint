<?php

$user   = Nano_Session::user();
$fossil = new Nano_Fossil($user);

if ($repo = $fossil->getRepoById($_GET['id'])) {
    $success = $fossil->pullRepo($repo['name'], '', $output);
    $_SESSION['pull'] = array(
        'name' => $repo['name'],
        'success' => $success,
        'output' => $output
    );
}

header('Location: /secure/');
die();
