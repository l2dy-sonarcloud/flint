<?php

$view = new Nano_View();
$view->title(' - Log In');

if (isset($_GET['token'])) {
    if (Nano_Session::loginWithToken($_GET['token'])) {
        $_SESSION['token-login'] = true;
        header('Location: /secure/account/');
        die();
    }
    else {
        $view->invalid = true;
    }
}

$redirect = 'secure';

if (isset($_SESSION['auth-required'])) {
    $view->auth = true;
    $_SESSION['redirect'] = $_SESSION['auth-required'];
    unset($_SESSION['auth-required']);
}

if (isset($_SESSION['forgot-password'])) {
    $view->forgot = true;
    unset($_SESSION['forgot-password']);
}

if ($_POST) {
    $validation = new Nano_Validation();

    $rules             = array();
    $rules['username'] = 'required,login';
    $rules['password'] = 'required';

    if ($validation->validate($_POST, $rules)) {
        if (isset($_SESSION['redirect'])) {
            $redirect = $_SESSION['redirect'];
            unset($_SESSION['redirect']);
        }

        header('Location: /' . $redirect . '/');
        die();
    }
    else {
        Nano_Variable::set('validationErrors', $validation->errors());
    }
}

$view->dispatch();
