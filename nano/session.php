<?php

class Nano_Session
{
    public static function init()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public static function create($user)
    {
        $user['hash'] = crypt($user['password'], self::generateSalt());

        $sql = "INSERT INTO users
                       (first_name, last_name, email, username, hash)
                VALUES (:first, :last, :email, :username, :hash)";

        $bind             = array();
        $bind['first']    = $user['firstname'];
        $bind['last']     = $user['lastname'];
        $bind['email']    = $user['email'];
        $bind['username'] = $user['username'];
        $bind['hash']     = $user['hash'];

        if (Nano_Db::execute($sql, $bind)) {
            return true;
        }

        return false;
    }

    public static function delete($user)
    {
        $bind = array('id' => $user['id']);

        $sql = "DELETE FROM users
                 WHERE id = :id";

        Nano_Db::execute($sql, $bind);

        $sql = "DELETE FROM sessions
                 WHERE user_id = :id";

        Nano_Db::execute($sql, $bind);

        $fossil = new Nano_Fossil($user);
        $fossil->remAllRepos();

        return true;
    }

    public static function update($user, $info)
    {
        $bind = array();

        $sql = "UPDATE users
                   SET first_name = :first,
                       last_name  = :last,
                       email      = :email";

        if (isset($info['password'])) {
            $info['hash'] = crypt($info['password'], self::generateSalt());

            $bind['hash'] = $info['hash'];

            $sql .= ", password = '',
                       salt     = '',
                       hash     = :hash";
        }

        $sql .= " WHERE id = :id";

        $bind['first']    = $info['firstname'];
        $bind['last']     = $info['lastname'];
        $bind['email']    = $info['email'];
        $bind['id']       = $user['id'];

        if (Nano_Db::execute($sql, $bind)) {
            return true;
        }

        return false;
    }

    public static function login($username, $password)
    {
        $sql = "SELECT *
                  FROM users
                 WHERE username = :username";

        $bind             = array();
        $bind['username'] = $username;

        if ($result = Nano_Db::query($sql, $bind)) {
            $result = array_pop($result);

            if (!empty($result['hash'])) {
                $verify = (crypt($password, $result['hash']) == $result['hash']);
            } else {
                $verify = (hash_hmac('md5', $password, $result['salt']) == $result['password']);
            }

            if ($verify) {
                if (empty($result['hash'])) {
                    $hash = crypt($password, self::generateSalt());

                    $sql = "UPDATE users
                               SET password = '',
                                   salt     = '',
                                   hash     = :hash";

                    $bind = array('hash' => $hash);

                    Nano_Db::execute($sql, $bind);
                }

                $sql = "REPLACE INTO sessions
                                (user_id, session_id, session_date)
                         VALUES (:user, :session, datetime('now'))";

                $bind            = array();
                $bind['user']    = $result['id'];
                $bind['session'] = session_id();

                Nano_Db::execute($sql, $bind);

                self::cleanup();

                return true;
            }
        }

        return false;
    }

    public static function loginWithToken($token)
    {
        $sql = "SELECT *
                  FROM tokens AS t
                 INNER JOIN users AS u
                    ON t.user_id = u.id
                 WHERE t.token = :token
                   AND t.create_date > datetime('now', '-24 hour')";

        $bind          = array();
        $bind['token'] = $token;

        if ($result = Nano_Db::query($sql, $bind)) {
            $result = array_pop($result);

            $sql = "DELETE FROM tokens
                     WHERE token = :token";

            Nano_Db::execute($sql, $bind);

            $sql = "REPLACE INTO sessions
                            (user_id, session_id, session_date)
                     VALUES (:user, :session, datetime('now'))";

            $bind            = array();
            $bind['user']    = $result['id'];
            $bind['session'] = session_id();

            Nano_Db::execute($sql, $bind);

            self::cleanup();

            return true;
        }

        return false;
    }

    public static function requestPasswordReset($email)
    {
        $sql = "SELECT *
                  FROM users
                 WHERE email = :email";

        $bind          = array();
        $bind['email'] = $email;

        if ($result = Nano_Db::query($sql, $bind)) {
            $result = array_pop($result);

            $sql = "REPLACE INTO tokens
                            (user_id, token, create_date)
                     VALUES (:user, :token, datetime('now'))";

            $bind          = array();
            $bind['user']  = $result['id'];
            $bind['token'] = sha1("{$result['id']}{$result['username']}{$result['email']}" . mt_rand());

            Nano_Db::execute($sql, $bind);

            $headers = "From: Flint <no-reply@{$_SERVER['SERVER_NAME']}>\r\n" .
                       "Reply-To: Flint <no-reply@{$_SERVER['SERVER_NAME']}>";

            $message = "{$result['first_name']},\n\nUse the link below to reset your {$_SERVER['SERVER_NAME']} password. " .
                       "Your one time token expires in 24 hours.\n\n" .
                       "https://{$_SERVER['SERVER_NAME']}/secure/log-in/token/{$bind['token']}\n\n" .
                       "The Flint Team";

            mail($result['email'], 'Flint.tld Forgot Password', $message, $headers,
                 '-fno-reply@' . $_SERVER['SERVER_NAME']);

            return true;
        }

        return false;
    }

    public static function cleanup()
    {
        $sql = "DELETE FROM sessions
                 WHERE session_date < datetime('now', '-24 hour')";

        Nano_Db::execute($sql);

        $sql = "DELETE FROM tokens
                 WHERE create_date < datetime('now', '-24 hour')";

        Nano_Db::execute($sql);

        return true;
    }

    public static function logout()
    {
        $user = self::user();

        $sql = "DELETE FROM sessions
                 WHERE user_id = :user";

        $bind         = array();
        $bind['user'] = $user['id'];

        Nano_Db::execute($sql, $bind);

        session_destroy();

        return true;
    }

    public static function user()
    {
        $sql = "SELECT *
                  FROM users AS u
                 INNER JOIN sessions AS s
                    ON s.user_id = u.id
                 WHERE s.session_id = :session";

        $bind            = array();
        $bind['session'] = session_id();

        if ($result = Nano_Db::query($sql, $bind)) {
            return array_pop($result);
        }

        return false;
    }

    public static function getUserByName($username)
    {
        $sql = "SELECT *
                  FROM users
                 WHERE username = :username";

        $bind             = array();
        $bind['username'] = $username;

        if ($result = Nano_Db::query($sql, $bind)) {
            return array_pop($result);
        }

        return false;
    }

    public static function getUserByEmail($email)
    {
        $sql = "SELECT *
                  FROM users
                 WHERE email = :email";

        $bind          = array();
        $bind['email'] = $email;

        if ($result = Nano_Db::query($sql, $bind)) {
            return array_pop($result);
        }

        return false;
    }

    public static function unique($username)
    {
        if (self::getUserByName($username)) {
            return false;
        }

        return true;
    }

    public static function uniqueEmail($email)
    {
        if (self::getUserByEmail($email)) {
            return false;
        }

        return true;
    }

    protected static function generateSalt()
    {
        return '$2a$12$' . substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(16))), 0, 22);
    }
}

Nano_Session::init();
