<?php

$view = new Nano_View();
$view->title(' - Help on Cloning');

$user    = Nano_Session::user();
$fossil  = new Nano_Fossil($user);
$success = false;

if ($repo = $fossil->getRepoById($_GET['id'])) {
	$view->repo = $repo;
	$view->user = $user;
} else {
	$view->error = true;
}

$view->dispatch();
