<?php

class Nano_Validation_Rules
{
    public static $_rules = array(
        'required'    => '{{field}} is required.',
        'login'       => '{{field}} and password combination is invalid.',
        'email'       => '{{field}} must be a valid email address.',
        'username'    => '{{field}} cannot contain spaces.',
        'alpha'       => '{{field}} must only contain letters.',
        'name'        => '{{field}} must only contain letters, spaces and hyphens.',
        'numeric'     => '{{field}} must only contain numbers.',
        'filename'    => '{{field}} must only contain letters, numbers, underscores and dashes.',
        'length'      => '{{field}} cannot be longer than {{option}} characters.',
        'match'       => '{{field}} does not match {{option}}.',
        'unique'      => '{{field}} is already in use.',
        'uniqueEmail' => '{{field}} is already in use.',
    );

    public static function required($val)
    {
        if ($val && !empty($val)) {
            return true;
        }

        return false;
    }

    public static function login($val)
    {
        if (Nano_Session::login($val, $_POST['password'])) {
            return true;
        }

        return false;
    }

    public static function email($val)
    {
        if (preg_match('/^[a-zA-Z0-9+._-]+\@[a-z0-9.-]+\.[a-z]{2,6}$/', $val)) {
            return true;
        }

        return false;
    }

    public static function username($val)
    {
        if (preg_match('/^[^ ]+$/', $val)) {
            return true;
        }

        return false;
    }

    public static function alpha($val)
    {
        if (preg_match('/^[a-z ]+$/i', $val)) {
            return true;
        }

        return false;
    }

    public static function name($val)
    {
        if (preg_match('/^[a-z -]+$/i', $val)) {
            return true;
        }

        return false;
    }

    public static function numeric($val)
    {
        if (preg_match('/^[0-9]+$/', $val)) {
            return true;
        }

        return false;
    }

    public static function filename($val) {
        if (preg_match('/^[a-z0-9_-]+$/i', $val)) {
            return true;
        }

        return false;
    }

    public static function length($val, $option)
    {
        if (strlen($val) <= $option) {
            return true;
        }

        return false;
    }

    public static function match($val, $option)
    {
        if ($val == $_POST[$option]) {
            return true;
        }

        return false;
    }

    public static function unique($val)
    {
        if (Nano_Session::unique($val)) {
            return true;
        }

        return false;
    }

    public static function uniqueEmail($val)
    {
        if (Nano_Session::uniqueEmail($val)) {
            return true;
        }

        return false;
    }
}
