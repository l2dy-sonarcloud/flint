<?php

class Nano_Fossil
{
    protected absolute_path;
    protected relative_path;
    protected user;
    protected workdir;
    protected use_suid;
    protected fossil_binary;

    public function __construct($user)
    {
        $this->repo_dir = $_SERVER['DOCUMENT_ROOT'] . '/../repos';
        $this->absolute_path = $this->repo_dir . '/' . $user['username'] . '/';
        $this->user = $user;
        $this->use_suid = false;

        if ($this->use_suid) {
            $this->fossil_binary = dirname(__FILE__) . "/../scripts/fossil-as-user/suid-fossil";
            $this->relative_path = '/' . $user['username'] . '/';
        } else {
            $this->fossil_binary = "/usr/local/bin/fossil";
            $this->relative_path = $this->absolute_path;
        }

        if (!$this->use_suid) {
            $this->workdir = "/tmp/workdir-flint-" . bin2hex(openssl_random_pseudo_bytes(20));

            mkdir($this->workdir);
        }
    }

    public function __destruct() {
        if (!$this->use_suid) {
            system("rm -rf '{$this->workdir}'");
        }
    }

    private function fossil_sql_execute($repo, $sql, $bind = array()) {
        Nano_Db::setDb("sqlite:{$this->absolute_path}{$repo}.fossil");
        $result = Nano_Db::execute($sql, $bind);
        Nano_Db::unsetDb();
        return($result);
    }

    private function fossil_sql_query($repo, $sql, $bind = array()) {
        Nano_Db::setDb("sqlite:{$this->absolute_path}{$repo}.fossil");
        $result = Nano_Db::query($sql, $bind);
        Nano_Db::unsetDb();
        return($result);
    }

    private function getFossilCommand($timeout = 0, $cgi = false) {
        $fossil = $this->fossil_binary;

        if ($timeout) {
            $fossil = "timeout {$timeout} {$fossil}";
        }

        $username = escapeshellarg($this->user['username']);

        $cmd = "USER={$username} {$fossil}";

        if (!$this->use_suid) {
            $cmd = "HOME={$this->workdir} {$cmd}";
        } else {
            $userid = escapeshellarg($this->user['id']);
            $cmd = "FLINT_USERID={$userid} FLINT_USERNAME={$username} {$cmd}";
        }

        if ($cgi) {
            $cmd = "GATEWAY_INTERFACE=1 {$cmd}";
        } else {
            $cmd = "unset GATEWAY_INTERFACE; {$cmd}";
        }

        return $cmd;
    }

    public function newRepo($repo, $password = null, $private = 0, $projectCode = null, $sha3 = false)
    {
        if (!file_exists($this->absolute_path)) {
            mkdir($this->absolute_path);

            $content = "#!/usr/local/bin/fossil\ndirectory: ./\nnotfound: http://{$_SERVER['SERVER_NAME']}/notfound";
            file_put_contents("{$this->absolute_path}repository", $content);
            chmod("{$this->absolute_path}repository", 0555);
        }

        if ($sha3 === true) {
            $shaArg = "";
        } else {
            $shaArg = "--sha1";
        }

        if (!file_exists("{$this->absolute_path}{$repo}.fossil")) {
            exec($this->getFossilCommand() . " new " . $shaArg . " -A " . escapeshellarg($this->user['username']) . " " . escapeshellarg("{$this->relative_path}{$repo}.fossil"), $output, $return);

            if ($return !== 0) {
                if (file_exists("{$this->absolute_path}{$repo}.fossil")) {
                    unlink("{$this->absolute_path}{$repo}.fossil");
                }

                return false;
            }

            /* Install default configuration */
            exec($this->getFossilCommand() . " configuration import -R " . escapeshellarg("{$this->relative_path}{$repo}.fossil") . " " . escapeshellarg($_SERVER['DOCUMENT_ROOT'] . "/../config/fossil-default.cnf"), $output, $return);

            $sql = "INSERT INTO repositories
                           (user_id, name, private, cloned, auto_update)
                    VALUES (:id, :name, :private, 0, 0)";

            $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private);

            if (Nano_Db::execute($sql, $bind)) {
                if ($projectCode) {
                    $sql  = "UPDATE config SET value = :code WHERE name = 'project-code'";
                    $bind = array('code' => $projectCode);
                    $this->fossil_sql_execute($repo, $sql, $bind);
                }

                if ($password) {
                    $sql = "SELECT value FROM config WHERE name = 'project-code'";

                    if ($result = $this->fossil_sql_query($repo, $sql)) {
                        $code     = array_pop($result);
                        $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");

                        $sql  = "UPDATE user SET pw = :password WHERE login = :user";
                        $bind = array('password' => $password, 'user' => $this->user['username']);
                        $this->fossil_sql_execute($repo, $sql, $bind);

                        $return = 'sha1';
                    }
                } else {
                    $sql  = "SELECT pw FROM user WHERE login = :user";
                    $bind = array('user' => $this->user['username']);

                    if ($result = $this->fossil_sql_query($repo, $sql, $bind)) {
                        $password = array_pop($result);
                        $return   = $password['pw'];
                    }
                }

                $sql = "UPDATE config SET value = 1 WHERE name = 'localauth'";
                $this->fossil_sql_execute($repo, $sql);

                return $return;
            }
        }

        return false;
    }

    public function cloneRepo($repo, $password = null, $url, $private = 0, $update = 0)
    {
        if (!file_exists($this->absolute_path)) {
            mkdir($this->absolute_path);

            $content = "#!/usr/local/bin/fossil\ndirectory: ./\nnotfound: http://{$_SERVER['SERVER_NAME']}/notfound";
            file_put_contents("{$this->absolute_path}repository", $content);
            chmod("{$this->absolute_path}repository", 0555);
        }

        if (!file_exists("{$this->absolute_path}{$repo}.fossil")) {
            exec($this->getFossilCommand(3600) . " clone -A " . escapeshellarg($this->user['username']) . " " . escapeshellarg($url) . " " . escapeshellarg("{$this->relative_path}{$repo}.fossil"), $output,
                 $return);

            if ($return !== 0) {
                if (file_exists("{$this->absolute_path}{$repo}.fossil")) {
                    unlink("{$this->absolute_path}{$repo}.fossil");
                }

                return false;
            }

            $sql = "INSERT INTO repositories
                           (user_id, name, private, cloned, auto_update)
                    VALUES (:id, :name, :private, 1, :auto)";

            $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private, 'auto' => $update);

            if (Nano_Db::execute($sql, $bind)) {
                if ($password) {
                    $sql = "SELECT value FROM config WHERE name = 'project-code'";

                    if ($result = $this->fossil_sql_query($repo, $sql)) {
                        $code     = array_pop($result);
                        $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");

                        $sql  = "UPDATE user SET pw = :password WHERE login = :user";
                        $bind = array('password' => $password, 'user' => $this->user['username']);
                        $this->fossil_sql_execute($repo, $sql, $bind);

                        $return = 'sha1';
                    }
                } else {
                    $sql  = "SELECT pw FROM user WHERE login = :user";
                    $bind = array('user' => $this->user['username']);

                    if ($result = $this->fossil_sql_query($repo, $sql, $bind)) {
                        $password = array_pop($result);
                        $return   = $password['pw'];
                    }
                }

                $sql = "UPDATE config SET value = 1 WHERE name = 'localauth'";
                $this->fossil_sql_execute($repo, $sql);

                return $return;
            }
        }

        return false;
    }

    public function uploadRepo($repo, $password, $private = 0, array $file)
    {
        if (!file_exists($this->absolute_path)) {
            mkdir($this->absolute_path);

            $content = "#!/usr/local/bin/fossil\ndirectory: ./\nnotfound: http://{$_SERVER['SERVER_NAME']}/notfound";
            file_put_contents("{$this->absolute_path}repository", $content);
            chmod("{$this->absolute_path}repository", 0555);
        }

        if (!file_exists("{$this->absolute_path}{$repo}.fossil")) {
            if (!@move_uploaded_file($file['tmp_name'], "{$this->absolute_path}{$repo}.fossil")) {
                return false;
            }

            exec($this->getFossilCommand() . " config -R " . escapeshellarg("{$this->relative_path}{$repo}.fossil") . " export project /tmp/config",
                 $output, $return);

            if (file_exists('/tmp/config')) {
                unlink('/tmp/config');
            }

            if ($return !== 0) {
                if (file_exists("{$this->absolute_path}{$repo}.fossil")) {
                    unlink("{$this->absolute_path}{$repo}.fossil");
                }

                return false;
            }

            exec($this->getFossilCommand() . " user new " . escapeshellarg($this->user['username']) . " 'Flint User' {$password} -R " . escapeshellarg("{$this->relative_path}{$repo}.fossil"),
                $output, $return);

            if ($return == 0) {
                exec($this->getFossilCommand() . " user capabilities " . escapeshellarg($this->user['username']) . " s -R " . escapeshellarg("{$this->relative_path}{$repo}.fossil"),
                    $output, $return);

                if ($return !== 0) {
                    unlink("{$this->absolute_path}{$repo}.fossil");
                    return false;
                }
            } else if ($return == 1) {
                $sql = "SELECT value FROM config WHERE name = 'project-code'";
                    
                if ($result = $this->fossil_sql_query($repo, $sql)) {
                    $code     = array_pop($result);
                    $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");
                        
                    $sql  = "UPDATE user SET cap = 's', pw = :password WHERE login = :user";
                    $bind = array('password' => $password, 'user' => $this->user['username']);
                    $this->fossil_sql_execute($repo, $sql, $bind);
                }
            } else {
                unlink("{$this->absolute_path}{$repo}.fossil");
                return false;
            }

            $sql = "INSERT INTO repositories
                           (user_id, name, private, cloned, auto_update)
                    VALUES (:id, :name, :private, 0, 0)";

            $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private);

            if (Nano_Db::execute($sql, $bind)) {
                $sql = "UPDATE config SET value = 1 WHERE name = 'localauth'";
                $this->fossil_sql_execute($repo, $sql);

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

        if (file_exists("{$this->absolute_path}{$repo}.fossil")) {
            # Ensure that no non-default SSH command can be used for a pull
            exec($this->getFossilCommand(3600) . " unset ssh-command -R " . escapeshellarg("{$this->relative_path}{$repo}.fossil") . " 2>&1",
              $output, $return);
            if ($return !== 0) {
                return false;
            }

            if ($url == '') {
                exec($this->getFossilCommand(3600) . " pull -R " . escapeshellarg("{$this->relative_path}{$repo}.fossil") . " 2>&1",
                  $output, $return);
            } else {
                exec($this->getFossilCommand(3600) . " pull " . escapeshellarg($url) . " -R " . escapeshellarg("{$this->relative_path}{$repo}.fossil") . " 2>&1",
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
        } else {
            return false;
        }

        $repo = $return['name'];
        $return['repo-file'] = "{$this->absolute_path}{$return['name']}.fossil";

        if ($return['cloned']) {
            $sql  = "SELECT value FROM config WHERE name = 'last-sync-url'";

            if ($result = $this->fossil_sql_query($repo, $sql)) {
                $url                 = array_pop($result);
                $return['clone-url'] = $url['value'];
            }
        }

        $sql  = "SELECT value FROM config WHERE name = 'last-sync-pw'";

        if ($result = $this->fossil_sql_query($repo, $sql)) {
            $password           = array_pop($result);
            $return['clone-pw'] = $password['value'];

            exec($this->getFossilCommand() . " test-obscure " . escapeshellarg($return['clone-pw']), $output, $returnCode);

            if ($returnCode === 0) {
                if (preg_match('/^UNOBSCURE: (.*) -> (.*)$/', $output[1], $matches)) {
                    $return['clone-pw'] = $matches[2];
                }
            }
        }

        if (isset($return)) {
            return $return;
        }

        return false;
    }

    public function updateRepo($repo, $private, $update, $cloned, $password = null)
    {
        if (file_exists("{$this->absolute_path}{$repo}.fossil")) {
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
                    $sql = "SELECT value FROM config WHERE name = 'project-code'";

                    if ($result = $this->fossil_sql_query($repo, $sql)) {
                        $code     = array_pop($result);
                        $password = sha1("{$code['value']}/{$this->user['username']}/{$password}");

                        $sql  = "UPDATE user SET pw = :password WHERE login = :user";
                        $bind = array('password' => $password, 'user' => $this->user['username']);
                        $this->fossil_sql_execute($repo, $sql, $bind);
                    }
                }

                return true;
            }
        }

        return false;
    }

    public function remRepo($repo)
    {
        if (file_exists("{$this->absolute_path}{$repo}.fossil")) {
            $sql = "DELETE FROM repositories
                     WHERE user_id = :id
                       AND name    = :repo";

            $bind = array('id' => $this->user['id'], 'repo' => $repo);

            if (Nano_Db::execute($sql, $bind)) {
                unlink("{$this->absolute_path}{$repo}.fossil");

                return true;
            }
        }

        return false;
    }

    public function remAllRepos()
    {
        if (file_exists("{$this->absolute_path}")) {
            $sql = "DELETE FROM repositories
                     WHERE user_id = :id";

            $bind = array('id' => $this->user['id']);

            if (Nano_Db::execute($sql, $bind)) {
                foreach (glob("{$this->absolute_path}*.fossil") as $repo) {
                    unlink($repo);
                }

                unlink("{$this->absolute_path}repository");
                rmdir("{$this->absolute_path}");

                return true;
            }
        }

        return false;
    }
}
