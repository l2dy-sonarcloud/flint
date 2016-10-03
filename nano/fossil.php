<?php

class Nano_Fossil
{
    protected $path;
    protected $user;
    protected $workdir;

    public function __construct($user)
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/../repos/' . $user['username'] . '/';
        $this->user = $user;
        $this->workdir = "/tmp/workdir-flint-" . bin2hex(openssl_random_pseudo_bytes(20));

        mkdir($this->workdir);
    }

    public function __destruct() {
        system("rm -rf '{$this->workdir}'");
    }

    private function getFossilCommand($timeout = 0, $cgi = false) {
        $fossil = "/usr/local/bin/fossil";

        if ($timeout) {
            $fossil = "timeout {$timeout} {$fossil}";
        }

        $cmd = "HOME={$this->workdir} USER={$this->user['username']} {$fossil}";

        if ($cgi) {
            $cmd = "GATEWAY_INTERFACE=1 {$cmd}";
        } else {
            $cmd = "unset GATEWAY_INTERFACE; {$cmd}";
        }

        return $cmd;
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
            exec($this->getFossilCommand() . " new -A " . escapeshellarg($this->user['username']) . " " . escapeshellarg("{$this->path}{$repo}.fossil"), $output, $return);

            if ($return !== 0) {
                if (file_exists("{$this->path}{$repo}.fossil")) {
                    unlink("{$this->path}{$repo}.fossil");
                }

                return false;
            }

            /* Install default configuration */
            exec($this->getFossilCommand() . " configuration import -R " . escapeshellarg("{$this->path}{$repo}.fossil") . " " . escapeshellarg($_SERVER['DOCUMENT_ROOT'] . "/../config/fossil-default.cnf"), $output, $return);

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
            exec($this->getFossilCommand(3600) . " clone -A " . escapeshellarg($this->user['username']) . " " . escapeshellarg($url) . " " . escapeshellarg("{$this->path}{$repo}.fossil"), $output,
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
            if (!@move_uploaded_file($file['tmp_name'], "{$this->path}{$repo}.fossil")) {
                return false;
            }

            exec($this->getFossilCommand() . " config -R " . escapeshellarg("{$this->path}{$repo}.fossil") . " export project /tmp/config",
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

            exec($this->getFossilCommand() . " user new " . escapeshellarg($this->user['username']) . " 'Flint User' {$password} -R " . escapeshellarg("{$this->path}{$repo}.fossil"),
                $output, $return);

            if ($return == 0) {
                exec($this->getFossilCommand() . " user capabilities " . escapeshellarg($this->user['username']) . " s -R " . escapeshellarg("{$this->path}{$repo}.fossil"),
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

    public function pullRepo($repo, $url = '', &$outputstr = null)
    {
        if ($url != '') {
            if (file_exists($url) || preg_match('/:\/\//', $url) == 0) {
                $outputstr = "Invalid URL";
                return false;
            }
        }

        if (file_exists("{$this->path}{$repo}.fossil")) {
            if ($url == '') {
                exec($this->getFossilCommand(3600) . " pull -R " . escapeshellarg("{$this->path}{$repo}.fossil") . " 2>&1",
                  $output, $return);
            } else {
                exec($this->getFossilCommand(3600) . " pull " . escapeshellarg($url) . " -R " . escapeshellarg("{$this->path}{$repo}.fossil") . " 2>&1",
                  $output, $return);
            }

            $outputstr = join("\n", $output);

            if ($return !== 0) {
                return false;
            }

            return true;
        }

        $outputstr = "Invalid repository";

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

        $return['repo-file'] = "{$this->path}{$return['name']}.fossil";

        Nano_Db::setDb("sqlite:{$this->path}{$return['name']}.fossil");

        if ($return['cloned']) {
            $sql  = "SELECT value FROM config WHERE name = 'last-sync-url'";

            if ($result = Nano_Db::query($sql)) {
                $url                 = array_pop($result);
                $return['clone-url'] = $url['value'];
            }
        }

        $sql  = "SELECT value FROM config WHERE name = 'last-sync-pw'";

        if ($result = Nano_Db::query($sql)) {
            $password           = array_pop($result);
            $return['clone-pw'] = $password['value'];

            exec($this->getFossilCommand() . " test-obscure " . escapeshellarg($return['clone-pw']), $output, $returnCode);

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

    public function updateRepo($repo, $private, $update, $cloned, $password = null)
    {
        if (file_exists("{$this->path}{$repo}.fossil")) {
            $sql = "UPDATE repositories
                       SET private     = :private,
                           auto_update = :auto,
                           cloned      = :cloned
                     WHERE user_id = :id
                       AND name    = :repo";

            $bind = array(
                'private' => $private,
                'auto'    => $update,
                'cloned'  => $cloned,
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
