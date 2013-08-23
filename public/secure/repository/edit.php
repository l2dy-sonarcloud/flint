<?php

$view = new Nano_View();
$view->title(' - Edit Repository');

$user    = Nano_Session::user();
$fossil  = new Nano_Fossil($user);
$success = false;

if ($repo = $fossil->getRepoById($_GET['id'])) {
    if (isset($repo['clone-pw'])) {
        $repo['clone-url'] = str_replace('@', ':' . $repo['clone-pw'] . '@', $repo['clone-url']);
    }

    $view->repo = $repo;

    if($_POST) {
        if (isset($_POST['submit']) && $_POST['submit'] == "Download File") {
            header('Content-type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $repo['name'] . '.fossil"');
            readfile($repo['repo-file']);
            die();
        }
        if (isset($_POST['submit']) && $_POST['submit'] == "Update Repository") {
            $validation = new Nano_Validation();
    
            $rules = array();
    
            if ($validation->validate($_POST, $rules)) {
                if (isset($_POST['repository-password']) && !empty($_POST['repository-password'])) {
                    $password = $_POST['repository-password'];
                }
                else {
                    $password = null;
                }
    
                $private = isset($_POST['private']) ? '1' : '0';
                $update  = isset($_POST['auto-update']) ? '1' : '0';

                if (isset($_POST['clone-url']) && $_POST['clone-url'] != "") {
                    $cloned  = '1';
                    if ($fossil->pullRepo($repo['name'], $_POST['clone-url'])) {
                        $success = true;
                    } else {
                        $success     = false;
                        $view->error = true;
                    }
                } else {
                    $success = true;
                    $cloned  = '0';
                }
    
                if ($success && $fossil->updateRepo($repo['name'], $private, $update, $cloned, $password)) {
                    $success = true;
                }
                else {
                    $success     = false;
                    $view->error = true;
                }
    
                if ($success) {
                    $_SESSION['update'] = $repo['name']; 
                    header('Location: /secure/');
                    die();
                }
            }
            else {
                Nano_Variable::set('validationErrors', $validation->errors());
            }
        }
    }
}
else {
    $view->error = true;
}

$view->dispatch();
