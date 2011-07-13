<?php

class Nano_Validation
{
    private static $_errors = array();

    public function validate($post = array(), $rules = array())
    {
        $post = array_intersect_key($post, $rules);

        foreach ($post as $key => $val) {
            foreach (explode(',', $rules[$key]) as $rule) {
                $option = null;

                if (preg_match('/\[(.*)\]$/i', $rule, $matches)) {
                    $option = $matches[1];
                    $rule   = str_replace($matches[0], null, $rule);
                }

                $params = array($val, $option);
                $result = call_user_func_array('Nano_Validation_Rules::' . $rule, $params);

                $find    = array('{{field}}', '{{option}}');
                $replace = array(ucfirst(str_replace('-', ' ', $key)), $option);

                if (!$result && !isset(self::$_errors[$key]['required'])) {
                    self::$_errors[$key][$rule] = str_replace($find, $replace,
                                                              Nano_Validation_Rules::$_rules[$rule]);
                }
            }
        }

        if (self::$_errors) {
            return false;
        }

        return true;
    }

    public function errors()
    {
        if (self::$_errors) {
            return self::$_errors;
        }
    }
}
