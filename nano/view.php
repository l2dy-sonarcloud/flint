<?php

class Nano_View
{
    private $_data          = array();
    public static $_links   = array();
    public static $_scripts = array();
    public static $_styles  = array();
    public static $_title   = null;
    public static $_mtime   = null;

    public function dispatch()
    {
        self::addStyle('global', 'wrapper.css');
        self::addScript('global', 'wrapper.js');

        $path = explode('/', Nano_Variable::get('controller'));
        $file = array_pop($path);
        $path = implode('/', $path);

        self::addStyle($path, str_replace('php', 'css', $file));
        self::addScript($path, str_replace('php', 'js', $file));

        $path = $_SERVER['DOCUMENT_ROOT'] . '/' . $path . '/presentation/';

        $file = $path . str_replace('php', 'tpl', $file);

        Nano_Variable::remove('controller');

        ob_start();
        require_once($_SERVER['DOCUMENT_ROOT'] . '/global/presentation/wrapper.tpl');
        $wrapper = ob_get_clean();

        if (is_file($file)) {
            ob_start();
            require_once($file);
            $contents = trim(ob_get_clean());
            $wrapper  = str_replace('{{pagecontents}}', $contents, $wrapper);
        }

        echo $wrapper;
    }

    public static function addLink($path, $file)
    {
        $link = ($path ? '/' . $path : null) . '/' . $file;

        if (is_file($_SERVER['DOCUMENT_ROOT'] . $link . '.php')) {
            self::$_links[$file] = $link . '/';
        }
    }

    public static function addScript($path, $file)
    {
        $script = ($path ? '/' . $path : null) . '/scripts/' . $file;

        if (is_file($_SERVER['DOCUMENT_ROOT'] . $script)) {
            self::$_scripts[$file] = $script;
        }   
    }

    public static function addStyle($path, $file)
    {
        $style = ($path ? '/' . $path : null) . '/styles/' . $file;

        if (is_file($_SERVER['DOCUMENT_ROOT'] . $style)) {
            self::$_styles[$file] = $style;
        }
    }

    public function render($tpl, $return = false)
    {
        if (is_file($tpl)) {
            ob_start();
            require($tpl);
            $contents = trim(ob_get_clean());

            if ($return) {
                return $contents;
            }

            echo $contents;
        }
    }

    public static function title($title)
    {
        self::$_title .= $title;
    }

    public static function mtime($mtime)
    {
        self::$_mtime = $mtime;
    }

    public function __call($method, $params)
    {
        $method    = explode('_', $method);
        $method[1] = isset($method[1]) ? $method[1] : $method[0];

        if ($result = call_user_func_array('Nano_View_' . $method[0] . '::' . $method[1], $params)) {
            return $result;
        }

        return false;
    }

    public function __get($name)
    {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        return false;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }
}
