<?php

$view = new Nano_View();
$view->title(' - Create Repository');
$view->addScript('global', 'jquery.js');

if (isset($_GET['type']) && $_GET['type'] == 'new' && $_POST) {
    $validation = new Nano_Validation();

    $rules                    = array();
    $rules['repository-name'] = 'required,filename';

    if ($validation->validate($_POST, $rules)) {
        $user   = Nano_Session::user();
        $fossil = new Nano_Fossil($user);

        if (count($fossil->getRepos()) <= 10) {
            if (isset($_POST['repository-password']) && !empty($_POST['repository-password'])) {
                $password = $_POST['repository-password'];
            }
            else {
                $password = null;
            }

            $private = isset($_POST['private']) ? '1' : '0';

            if (isset($_POST['project-code']) && !empty($_POST['project-code'])) {
                $projectCode = $_POST['project-code'];
            } else {
                $projectCode = null;
            }

            if ($result = $fossil->newRepo($_POST['repository-name'], $password, $private, $projectCode)) {
                $view->user     = $user;
                $view->name     = $_POST['repository-name'];
                $view->private  = $private;
                $view->password = $result;
                $view->success  = true;
            }
            else {
                $view->error = true;
            }
        }
        else {
            $view->max = true;
        }
    }
    else {
        Nano_Variable::set('validationErrors', $validation->errors());
    }
}

if (isset($_GET['type']) && $_GET['type'] == 'clone' && $_POST) {
    $validation = new Nano_Validation();

    $rules                    = array();
    $rules['repository-name'] = 'required,filename';
    $rules['clone-url']       = 'required';

    if ($validation->validate($_POST, $rules)) {
        $user   = Nano_Session::user();
        $fossil = new Nano_Fossil($user);

        if (count($fossil->getRepos()) <= 10) {
            if (isset($_POST['repository-password']) && !empty($_POST['repository-password'])) {
                $password = $_POST['repository-password'];
            }
            else {
                $password = null;
            }

            $private = isset($_POST['private']) ? '1' : '0';
            $update  = isset($_POST['auto-update']) ? '1' : '0';

            if ($result = $fossil->cloneRepo($_POST['repository-name'], $password, $_POST['clone-url'],
                                             $private, $update)) {
                $view->user     = $user;
                $view->name     = $_POST['repository-name'];
                $view->private  = $private;
                $view->update   = $update;
                $view->password = $result;
                $view->success  = true;
            }
            else {
                $view->error = true;
            }
        }
        else {
            $view->max = true;
        }
    }
    else {
        Nano_Variable::set('validationErrors', $validation->errors());
    }
}

if (isset($_GET['type']) && $_GET['type'] == 'upload' && $_POST) {
    $validation = new Nano_Validation();

    $rules                        = array();
    $rules['repository-name']     = 'required,filename';
    $rules['repository-password'] = 'required';

    if ($validation->validate($_POST, $rules)) {
        if (!isset($_FILES['upload']) || $_FILES['upload']['error'] != 0) {
            $view->error = true;
        }
        else {
            $user   = Nano_Session::user();
            $fossil = new Nano_Fossil($user);

            if (count($fossil->getRepos()) <= 10) {
                $private = isset($_POST['private']) ? '1' : '0';

                if ($fossil->uploadRepo($_POST['repository-name'], $_POST['repository-password'], $private, $_FILES['upload'])) {
                    $view->user     = $user;
                    $view->name     = $_POST['repository-name'];
                    $view->private  = $private;
                    $view->password = 'sha1';
                    $view->success  = true;
                }
                else {
                    $view->error = true;
                }
            }
            else {
                $view->max = true;
            }
        }
    }
    else {
        Nano_Variable::set('validationErrors', $validation->errors());
    }
}

$view->dispatch();
