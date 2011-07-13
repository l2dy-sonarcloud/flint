<?php

class Nano_Router
{
    public static function init()
    {
        $uri     = trim($_SERVER['REQUEST_URI'], '/');
        $rewrite = explode('/', $uri);

        if ($rewrite[0] == 'nano') {
            header('Location: /');
            die();
        }

        if (empty($rewrite[0]) && is_file('index.php')) {
            $load = 'index.php';
        }
        else {
            $file = null;
            $path = null;
            $tmp  = array();

            foreach ($rewrite as $part) {
                if (is_file($path . $part . '.php')) {
                    $file  = $part . '.php';
                    $tmp[] = $part;
                    break;
                }
                else if (is_dir($path . $part)) {
                    $path .= $part . '/';
                    $tmp[] = $part;
                }

                if (!$file && !$path) {
                    break;
                }
            }

            if ($file && is_file($path . $file)) {
                $load = $path . $file;
            }
            else if (is_file($path . 'index.php')) {
                $load = $path . 'index.php';
            }

            $vars = array_values(array_diff_assoc($rewrite, $tmp));

            if ($vars) {
                if (count($vars) % 2 == 0) {
                    $i = 0;
                    while($i < count($vars)) {
                        $_GET[$vars[$i]] = $vars[$i + 1];
                        $i = $i + 2;
                    }
                }
                else {
                    $load = 'errors/404.php';
                }
            }
        }

        foreach ($_POST as $key => $value) {
            $_POST[$key] = trim($value);
        }

        if (isset($load)) {
            if ($config = @parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/../config/secure.cnf', true)) {
                foreach ($config as $secure) {
                    $securePath = str_replace('/', '\/', $secure['path']);

                    if (preg_match("/^{$securePath}/", $uri)) {
                        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                            header('Location: https://' . $_SERVER['SERVER_NAME'] . '/' . $uri);
                            die();
                        }
                        else {
                            foreach ($secure['exception'] as $exception) {
                                if (preg_match("/^{$secure['path']}\/{$exception}/", $uri)) {
                                    $allow = true;
                                }
                            }

                            if (!isset($allow)) {
                                if (!Nano_Session::user()) {
                                    $_SESSION['auth-required'] = $uri;
                                    header("Location: /{$secure['path']}/log-in/");
                                    die();
                                }
                            }

                            $ssl = true;
                        }
                    }
                }

                if (!isset($ssl)) {
                    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                        header('Location: http://' . $_SERVER['SERVER_NAME'] . '/' . $uri);
                        die();
                    }
                }
            }
            else {
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                    header('Location: http://' . $_SERVER['SERVER_NAME'] . '/' . $uri);
                    die();
                }
            }

            Nano_Variable::set('controller', $load);
            require_once($load);
        }
        else {
            Nano_Variable::set('controller', 'errors/404.php');
            require_once('errors/404.php');
        }
    }
}
