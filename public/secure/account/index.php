<?php

$view = new Nano_View();
$view->title(' - Account');
$view->addScript('global', 'jquery.js');

$user       = Nano_Session::user();
$view->user = $user;

if (isset($_SESSION['token-login'])) {
    $view->token = true;
    unset($_SESSION['token-login']);
}

if ($_POST) {
    $validation = new Nano_Validation();

    $rules                   = array();
    $rules['first-name']     = 'required,alpha';
    $rules['last-name']      = 'required,alpha';
    $rules['email']          = 'required,email';
    $rules['password-again'] = 'match[password]';

    if (isset($_POST['email']) && $_POST['email'] != $user['email']) {
        $rules['email'] = 'required,email,uniqueEmail';
    }

    if ($validation->validate($_POST, $rules)) {
        $info = array();
        $info['firstname'] = $_POST['first-name'];
        $info['lastname']  = $_POST['last-name'];
        $info['email']     = $_POST['email'];

        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $info['password']  = $_POST['password'];
        }

        if (Nano_Session::update($user, $info)) {
            $view->success = true;
        }
        else {
            $view->error = true;
        }
    }
    else {
        Nano_Variable::set('validationErrors', $validation->errors());
    }
}

$view->dispatch();
