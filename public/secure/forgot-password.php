<?php

$view = new Nano_View();
$view->title(' - Forgot Password');

if ($_POST) {
    $validation = new Nano_Validation();

    $rules          = array();
    $rules['email'] = 'required,email';

    if ($validation->validate($_POST, $rules)) {
        if (Nano_Session::requestPasswordReset($_POST['email'])) {
            $_SESSION['forgot-password'] = true;
            header('Location: /secure/log-in/');
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
