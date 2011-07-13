<?php

class Nano_View_Form
{
    protected static $idx = 1;

    public static function text($id, $default = null, $label = null)
    {
        $view = new Nano_View();

        $view->id      = $id;
        $view->default = $default;
        $view->idx     = self::tabIdx();

        if (!$label) {
            $view->label = ucfirst(str_replace('-', ' ', $id));
        }
        else {
            $view->label = $label;
        }

        if ($errors = Nano_Variable::get('validationErrors')) {
            if (isset($errors[$id])) {
                $view->errors = $errors[$id];
            }
        }

        $view->render(dirname(__FILE__) . '/presentation/form_text.tpl');
    }

    public static function password($id, $label = null)
    {
        $view = new Nano_View();

        $view->id  = $id;
        $view->idx = self::tabIdx();

        if (!$label) {
            $view->label = ucfirst(str_replace('-', ' ', $id));
        }
        else {
            $view->label = $label;
        }

        if ($errors = Nano_Variable::get('validationErrors')) {
            if (isset($errors[$id])) {
                $view->errors = $errors[$id];
            }
        }

        $view->render(dirname(__FILE__) . '/presentation/form_password.tpl');
    }

    public static function button($default)
    {
        $view = new Nano_View();

        $view->default = $default;
        $view->idx     = self::tabIdx();

        $view->render(dirname(__FILE__) . '/presentation/form_button.tpl');       
    }

    public static function textarea($id, $default = null, $label = null)
    {
        $view = new Nano_View();

        $view->id      = $id;
        $view->default = $default;
        $view->idx     = self::tabIdx();

        if (!$label) {
            $view->label = ucfirst(str_replace('-', ' ', $id));
        }
        else {
            $view->label = $label;
        }

        if ($errors = Nano_Variable::get('validationErrors')) {
            if (isset($errors[$id])) {
                $view->errors = $errors[$id];
            }
        }

        $view->render(dirname(__FILE__) . '/presentation/form_textarea.tpl');
    }

    public static function checkbox($id, $default = null, $label = null)
    {
        $view = new Nano_View();

        $view->id      = $id;
        $view->default = $default;
        $view->idx     = self::tabIdx();

        if (!$label) {
            $view->label = ucfirst(str_replace('-', ' ', $id));
        }
        else {
            $view->label = $label;
        }

        if ($errors = Nano_Variable::get('validationErrors')) {
            if (isset($errors[$id])) {
                $view->errors = $errors[$id];
            }
        }

        $view->render(dirname(__FILE__) . '/presentation/form_checkbox.tpl');
    }

    public static function file($id, $label = null)
    {
        $view = new Nano_View();

        $view->id  = $id;
        $view->idx = self::tabIdx();

        if (!$label) {
            $view->label = ucfirst(str_replace('-', ' ', $id));
        }
        else {
            $view->label = $label;
        }

        if ($errors = Nano_Variable::get('validationErrors')) {
            if (isset($errors[$id])) {
                $view->errors = $errors[$id];
            }
        }

        $view->render(dirname(__FILE__) . '/presentation/form_file.tpl');
    }

    protected static function tabIdx()
    {
        return self::$idx++;
    }
}
