<?php

$view = new Nano_View;

if (isset($_GET['user'])) {
    if ($user = Nano_Session::getUserByName($_GET['user'])) {
        $fossil = new Nano_Fossil($user);
        $public = array();

        if ($repos = $fossil->getRepos()) {
            foreach ($repos as $repo) {
                if ($repo['private'] == 0) {
                    $public[] = $repo;
                }
            }
        }

        $view->user   = $user;
        $view->public = $public;
    }
    else {
        $view->notfound = true;
    }
}

$view->dispatch();
