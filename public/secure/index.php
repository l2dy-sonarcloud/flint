<?php

$view = new Nano_View();
$view->addScript('global', 'jquery.js');

$user   = Nano_Session::user();
$fossil = new Nano_Fossil($user);

$public  = array();
$private = array();

if ($repos = $fossil->getRepos()) {
    foreach ($repos as $repo) {
        if ($repo['private']) {
            $private[] = $repo;
        }
        else {
            $public[] = $repo;
        }
    }
}

$view->user    = $user;
$view->public  = $public;
$view->private = $private;

if (isset($_SESSION['new-account'])) {
    $view->new = true;
    unset($_SESSION['new-account']);
}

if (isset($_SESSION['pull'])) {
    $view->pull = $_SESSION['pull'];
    unset($_SESSION['pull']);
}

if (isset($_SESSION['update'])) {
    $view->pull = $_SESSION['update'];
    unset($_SESSION['update']);
}

$view->dispatch();
