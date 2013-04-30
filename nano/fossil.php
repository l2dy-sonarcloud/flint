<?php

class Nano_Fossil
{
    protected $path;
    protected $user;

    public function __construct($user)
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/../repos/' . $user['username'] . '/';
        $this->user = $user;
    }

    public function newRepo($repo, $password = null, $private = 0, $projectCode = null)
    {
        if (!file_exists($this->path)) {
            mkdir($this->path);

            $content = "#!/usr/local/bin/fossil\ndirectory: ./\nnotfound: http://{$_SERVER['SERVER_NAME']}/notfound";
            file_put_contents("{$this->path}repository", $content);
            chmod("{$this->path}repository", 0555);
        }

        if (!file_exists("{$this->path}{$repo}.fossil")) {
            putenv('HOME=/tmp');
            putenv("USER={$this->user['username']}");
            putenv("GATEWAY_INTERFACE");
            exec("/usr/local/bin/fossil new -A {$this->user['username']} {$this->path}{$repo}.fossil", $output, $return);

            if ($return !== 0) {
                if (file_exists("{$this->path}{$repo}.fossil")) {
                    unlink("{$this->path}{$repo}.fossil");
                }

                return false;
            }

            $sql = "INSERT INTO repositories
                           (user_id, name, private, cloned, auto_update)
                    VALUES (:id, :name, :private, 0, 0)";

            $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private);

            if (Nano_Db::execute($sql, $bind)) {
                Nano_Db::setDb("sqlite:{$this->path}{$repo}.fossil");

                if ($projectCode) {
                    $sql  = "UPDATE config SET value = :code WHERE name = 'project-code'";
                    $bind = array('code' => $projectCode);
                    Nano_Db::execute($sql, $bind);
                }

                if ($password) {
                    $sql = "SELECT value FROM config WHERE name = 'project-code'";

                    if ($result = Nano_Db::query($sql)) {
                        $code     = array_pop($result);
                        $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");

                        $sql  = "UPDATE user SET pw = :password WHERE login = :user";
                        $bind = array('password' => $password, 'user' => $this->user['username']);
                        Nano_Db::execute($sql, $bind);

                        $return = 'sha1';
                    }
                }
                else {
                    $sql  = "SELECT pw FROM user WHERE login = :user";
                    $bind = array('user' => $this->user['username']);

                    if ($result = Nano_Db::query($sql, $bind)) {
                        $password = array_pop($result);
                        $return   = $password['pw'];
                    }
                }

                $sql = "UPDATE config SET value = 1 WHERE name = 'localauth'";
                Nano_Db::execute($sql);
                Nano_Db::unsetDb();

                return $return;
            }
        }

        return false;
    }

    public function cloneRepo($repo, $password = null, $url, $private = 0, $update = 0)
    {
        if (!file_exists($this->path)) {
            mkdir($this->path);

            $content = "#!/usr/local/bin/fossil\ndirectory: ./\nnotfound: http://{$_SERVER['SERVER_NAME']}/notfound";
            file_put_contents("{$this->path}repository", $content);
            chmod("{$this->path}repository", 0555);
        }

        if (!file_exists("{$this->path}{$repo}.fossil")) {
            putenv('HOME=/tmp');
            putenv("USER={$this->user['username']}");
            putenv("GATEWAY_INTERFACE");
            exec("/usr/local/bin/fossil clone -A {$this->user['username']} {$url} {$this->path}{$repo}.fossil", $output,
                 $return);

            if ($return !== 0) {
                if (file_exists("{$this->path}{$repo}.fossil")) {
                    unlink("{$this->path}{$repo}.fossil");
                }

                return false;
            }

            $sql = "INSERT INTO repositories
                           (user_id, name, private, cloned, auto_update)
                    VALUES (:id, :name, :private, 1, :auto)";

            $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private, 'auto' => $update);

            if (Nano_Db::execute($sql, $bind)) {
                Nano_Db::setDb("sqlite:{$this->path}{$repo}.fossil");

                if ($password) {
                    $sql = "SELECT value FROM config WHERE name = 'project-code'";

                    if ($result = Nano_Db::query($sql)) {
                        $code     = array_pop($result);
                        $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");

                        $sql  = "UPDATE user SET pw = :password WHERE login = :user";
                        $bind = array('password' => $password, 'user' => $this->user['username']);
                        Nano_Db::execute($sql, $bind);

                        $return = 'sha1';
                    }
                }
                else {
                    $sql  = "SELECT pw FROM user WHERE login = :user";
                    $bind = array('user' => $this->user['username']);

                    if ($result = Nano_Db::query($sql, $bind)) {
                        $password = array_pop($result);
                        $return   = $password['pw'];
                    }
                }

                $sql = "UPDATE config SET value = 1 WHERE name = 'localauth'";
                Nano_Db::execute($sql);
                Nano_Db::unsetDb();

                return $return;
            }
        }

        return false;
    }

    public function uploadRepo($repo, $password, $private = 0, array $file)
    {
        if (!file_exists($this->path)) {
            mkdir($this->path);

            $content = "#!/usr/local/bin/fossil\ndirectory: ./\nnotfound: http://{$_SERVER['SERVER_NAME']}/notfound";
            file_put_contents("{$this->path}repository", $content);
            chmod("{$this->path}repository", 0555);
        }

        if (!file_exists("{$this->path}{$repo}.fossil")) {
            putenv('HOME=/tmp');
            putenv("USER={$this->user['username']}");
            putenv("GATEWAY_INTERFACE");

            if (!@move_uploaded_file($file['tmp_name'], "{$this->path}{$repo}.fossil")) {
                return false;
            }

            exec("/usr/local/bin/fossil config -R {$this->path}{$repo}.fossil export project /tmp/config",
                 $output, $return);

            if (file_exists('/tmp/config')) {
                unlink('/tmp/config');
            }

            if ($return !== 0) {
                if (file_exists("{$this->path}{$repo}.fossil")) {
                    unlink("{$this->path}{$repo}.fossil");
                }

                return false;
            }

            exec("/usr/local/bin/fossil user new {$this->user['username']} 'Flint User' {$password} -R {$this->path}{$repo}.fossil",
                $output, $return);

            if ($return == 0) {
                exec("/usr/local/bin/fossil user capabilities {$this->user['username']} s -R {$this->path}{$repo}.fossil",
                    $output, $return);

                if ($return !== 0) {
                    unlink("{$this->path}{$repo}.fossil");
                    return false;
                }
            }
            else if ($return == 1) {
                Nano_Db::setDb("sqlite:{$this->path}{$repo}.fossil");
                    
                $sql = "SELECT value FROM config WHERE name = 'project-code'";
                    
                if ($result = Nano_Db::query($sql)) {
                    $code     = array_pop($result);
                    $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");
                        
                    $sql  = "UPDATE user SET cap = 's', pw = :password WHERE login = :user";
                    $bind = array('password' => $password, 'user' => $this->user['username']);
                    Nano_Db::execute($sql, $bind);
                }

                Nano_Db::unsetDb();
            }
            else {
                unlink("{$this->path}{$repo}.fossil");
                return false;
            }

            $sql = "INSERT INTO repositories
                           (user_id, name, private, cloned, auto_update)
                    VALUES (:id, :name, :private, 0, 0)";

            $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private);

            if (Nano_Db::execute($sql, $bind)) {
                Nano_Db::setDb("sqlite:{$this->path}{$repo}.fossil");

                $sql = "UPDATE config SET value = 1 WHERE name = 'localauth'";
                Nano_Db::execute($sql);
                Nano_Db::unsetDb();

                return 'sha1';
            }
        }

        return false;
    }

    public function pullRepo($repo, $url = '')
    {
        if (file_exists("{$this->path}{$repo}.fossil")) {
            putenv('HOME=/tmp');
            putenv("USER={$this->user['username']}");
            putenv("GATEWAY_INTERFACE");
            exec("/usr/local/bin/fossil pull {$url} -R {$this->path}{$repo}.fossil", $output, $return);

            if ($return !== 0) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function getRepos()
    {
        $sql = "SELECT *
                  FROM repositories
                 WHERE user_id = :id";

        $bind = array('id' => $this->user['id']);

        if ($result = Nano_Db::query($sql, $bind)) {
            return $result;
        }

        return false;
    }

    public function getRepoById($id)
    {
        $sql = "SELECT *
                  FROM repositories
                 WHERE user_id = :user
                   AND id = :id";

        $bind = array('user' => $this->user['id'], 'id' => $id);

        if ($result = Nano_Db::query($sql, $bind)) {
            $return = array_pop($result);
        }
        else {
            return false;
        }

        Nano_Db::setDb("sqlite:{$this->path}{$return['name']}.fossil");

        $sql  = "SELECT value FROM config WHERE name = 'last-sync-url'";

        if ($result = Nano_Db::query($sql)) {
            $url                 = array_pop($result);
            $return['clone-url'] = $url['value'];
        }

        $sql  = "SELECT value FROM config WHERE name = 'last-sync-pw'";

        if ($result = Nano_Db::query($sql)) {
            $password           = array_pop($result);
            $return['clone-pw'] = $password['value'];

            exec("/usr/local/bin/fossil test-obscure {$return['clone-pw']}", $output, $returnCode);

            if ($returnCode === 0) {
                if (preg_match('/^UNOBSCURE: (.*) -> (.*)$/', $output[1], $matches)) {
                    $return['clone-pw'] = $matches[2];
                }
            }
        }

        Nano_Db::unsetDb();

        if (isset($return)) {
            return $return;
        }

        return false;
    }

    public function updateRepo($repo, $private, $update, $password = null)
    {
        if (file_exists("{$this->path}{$repo}.fossil")) {
            $sql = "UPDATE repositories
                       SET private     = :private,
                           auto_update = :auto
                     WHERE user_id = :id
                       AND name    = :repo";

            $bind = array(
                'private' => $private,
                'auto'    => $update,
                'id'      => $this->user['id'],
                'repo'    => $repo,
            );

            if (Nano_Db::execute($sql, $bind)) {
                if ($password) {
                    Nano_Db::setDb("sqlite:{$this->path}{$repo}.fossil");

                    $sql = "SELECT value FROM config WHERE name = 'project-code'";

                    if ($result = Nano_Db::query($sql)) {
                        $code     = array_pop($result);
                        $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");

                        $sql  = "UPDATE user SET pw = :password WHERE login = :user";
                        $bind = array('password' => $password, 'user' => $this->user['username']);
                        Nano_Db::execute($sql, $bind);
                        Nano_Db::unsetDb();
                    }
                }

                return true;
            }
        }

        return false;
    }

    public function remRepo($repo)
    {
        if (file_exists("{$this->path}{$repo}.fossil")) {
            $sql = "DELETE FROM repositories
                     WHERE user_id = :id
                       AND name    = :repo";

            $bind = array('id' => $this->user['id'], 'repo' => $repo);

            if (Nano_Db::execute($sql, $bind)) {
                unlink("{$this->path}{$repo}.fossil");

                return true;
            }
        }

        return false;
    }

    public function remAllRepos()
    {
        if (file_exists("{$this->path}")) {
            $sql = "DELETE FROM repositories
                     WHERE user_id = :id";

            $bind = array('id' => $this->user['id']);

            if (Nano_Db::execute($sql, $bind)) {
                foreach (glob("{$this->path}*.fossil") as $repo) {
                    unlink($repo);
                }

                unlink("{$this->path}repository");
                rmdir("{$this->path}");

                return true;
            }
        }

        return false;
    }
}
