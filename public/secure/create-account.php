<?php

$view = new Nano_View();
$view->title(' - Create Account');

if ($_POST) {
    $validation = new Nano_Validation();

    $rules                   = array();
    $rules['first-name']     = 'required,name';
    $rules['last-name']      = 'required,name';
    $rules['email']          = 'required,email,uniqueEmail';
    $rules['username']       = 'required,username,unique';
    $rules['password']       = 'required';
    $rules['password-again'] = 'required,match[password]';

    if ($validation->validate($_POST, $rules)) {
        $user = array();
        $user['firstname'] = $_POST['first-name'];
        $user['lastname']  = $_POST['last-name'];
        $user['email']     = $_POST['email'];
        $user['username']  = $_POST['username'];
        $user['password']  = $_POST['password'];

        if (Nano_Session::create($user)) {
            $_SESSION['new-account'] = true;
            Nano_Session::login($user['username'], $user['password']);
            header('Location: /secure/');
            die();
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
