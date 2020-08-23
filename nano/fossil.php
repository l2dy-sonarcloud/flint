<?php

class Nano_Fossil
{
    protected $absolute_path_root;
    protected $relative_path_root;
    protected $absolute_path;
    protected $relative_path;
    protected $user;
    protected $workdir;
    protected $use_suid;
    protected $fossil_binary;

    public function __construct($user) {
        $this->repo_dir = $_SERVER['DOCUMENT_ROOT'] . '/../repos';
        $this->absolute_path_root = $this->repo_dir . '/' . $user['username'] . '/';
        $this->absolute_path = $this->absolute_path_root . 'data/';
        $this->user = $user;

        $fossil_config_file = dirname(__FILE__) . '/../config/fossil.cnf';
        $fossil_config = array();
        if (file_exists($fossil_config_file)) {
                $fossil_config = parse_ini_file($fossil_config_file);
        }
        if (isset($fossil_config['use_suid']) && $fossil_config['use_suid'] === '1') {
            $this->use_suid = true;
        } else {
            $this->use_suid = false;
        }

        /*
         * The "suid-fossil" wrapper will either call Fossil directly (if use_suid is false),
         * or invoke a wrapper before calling fossil within a chroot.
         */
        $this->fossil_binary = dirname(__FILE__) . "/../scripts/fossil-as-user/suid-fossil";

        if ($this->use_suid) {
            $this->relative_path_root = '/';
            $this->relative_path = '/data/';
            $this->root_fs = $this->absolute_path_root;
        } else {
            $this->relative_path_root = $this->absolute_path_root;
            $this->relative_path = $this->absolute_path;
            $this->root_fs = '';
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
        $repo_file = $this->repository_file($repo);
        Nano_Db::setDb("sqlite:{$repo_file}");
        $result = Nano_Db::execute($sql, $bind);
        Nano_Db::unsetDb();
        return($result);
    }

    private function fossil_sql_query($repo, $sql, $bind = array()) {
        $repo_file = $this->repository_file($repo);
        Nano_Db::setDb("sqlite:{$repo_file}");
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
        }

        $userid = escapeshellarg($this->user['id']);
        $cmd = "FLINT_USERID={$userid} FLINT_USERNAME={$username} {$cmd}";

        if ($cgi) {
            $cmd = "GATEWAY_INTERFACE=1 {$cmd}";
        } else {
            $cmd = "unset GATEWAY_INTERFACE; {$cmd}";
        }

        return $cmd;
    }

    private function repository_file($repo, $absolute = true) {
        if ($absolute) {
            $retval = "{$this->absolute_path}{$repo}.fossil";
        } else {
            $retval = "{$this->relative_path}{$repo}.fossil";
        }

        return $retval;
    }

    private function fossil($repo, $argv, &$output = null, &$return = null, $timeout = 0, $cgi = false) {
        $command = $this->getFossilCommand($timeout, $cgi);

        foreach ($argv as $arg) {
            if ($arg === $this->repository_file($repo)) {
                $arg = $this->repository_file($repo, FALSE);
            }

            $command = $command . " " . escapeshellarg($arg);
        }
        $command = $command . " 2>&1";

        exec($command, $output, $return);
    }

    private function createRepositoryCGI() {
        if (!file_exists($this->absolute_path)) {
            mkdir($this->absolute_path_root);
            mkdir($this->absolute_path);

            $content = "#!{$this->fossil_binary}\ndirectory: ./data/\nnotfound: http://{$_SERVER['SERVER_NAME']}/notfound";
            file_put_contents("{$this->absolute_path_root}repository", $content);
            chmod("{$this->absolute_path_root}repository", 0555);
        }
    }

    private function randomPassword($length = 12) {
        $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!";
        $alphabet_len = strlen($alphabet);
        $result = "";
        for ($idx = 0; $idx < $length; $idx++) {
            $char_idx = mt_rand(0, $alphabet_len - 1);
            $char = $alphabet[$char_idx];
            $result = $result . $char;
        }
        return $result;
    }

    private function createUser($repo) {
        $username = $this->user['username'];
        $repo_file = $this->repository_file($repo);

        $password = $this->randomPassword(64);

        $this->fossil($repo, array('user', 'new', $username, 'Flint User', $password, '-R', $repo_file), $output, $return);

        if ($return === 0) {
            $this->fossil($repo, array('user', 'capabilities', $username, 's', '-R', $repo_file), $output, $return);

            if ($return === 0) {
                return true;
            }
        }

        return false;
    }

    private function setPassword($repo, $password) {
        if (!$password) {
            $password = $this->randomPassword();
        }

        $this->fossil($repo, array('user', '-R', $this->repository_file($repo), 'password', $this->user['username'], $password), $output, $return);

        if ($return !== 0) {
            return false;
        }

        return $password;
    }

    private function setProjectCode($repo, $projectCode) {
        if ($projectCode) {
            $sql  = "UPDATE config SET value = :code WHERE name = 'project-code'";
            $bind = array('code' => $projectCode);
            $this->fossil_sql_execute($repo, $sql, $bind);
        }
    }

    private function repoConfig($repo) {
        $sql = "UPDATE config SET value = 1 WHERE name = 'localauth'";
        $this->fossil_sql_execute($repo, $sql);
    }

    private function postCreateRepo($repo, $password, $projectCode = null) {
        $this->setProjectCode($repo, $projectCode);
        $this->repoConfig($repo);
        $return = $this->setPassword($repo, $password);

        return $return;
    }

    public function newRepo($repo, $password = null, $private = 0, $projectCode = null, $sha3 = false, &$errorMessage = null) {
        $this->createRepositoryCGI();

        $create_command = array('new');

        if ($sha3 !== true) {
            $create_command[] = '--sha1';
        }

        $repo_file = $this->repository_file($repo);
        if (file_exists($repo_file)) {
            $errorMessage = 'File already exists';

            return false;
        }

        array_push($create_command, '-A', $this->user['username'], $repo_file);
        $this->fossil($repo, $create_command, $output, $return);

        if ($return !== 0) {
            if (file_exists($repo_file)) {
                unlink($repo_file);
            }

            $errorMessage = 'fossil new failed: '. join("\n", $output);

            return false;
        }

        /* Install default configuration */
        /** XXX:TODO: This won't work within the chroot **/
        $this->fossil($repo, array('configuration', 'import', '-R', $repo_file, $_SERVER['DOCUMENT_ROOT'] . "/../config/fossil-default.cnf"));

        $sql = "INSERT INTO repositories
                       (user_id, name, private, cloned, auto_update)
                VALUES (:id, :name, :private, 0, 0)";

        $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private);

        if (!Nano_Db::execute($sql, $bind)) {
            if (file_exists($repo_file)) {
                unlink($repo_file);
            }

            $errorMessage = 'Internal Error: DB Insert failed';

            return false;
        }

        $return = $this->postCreateRepo($repo, $password, $projectCode);

        return $return;
    }

    public function cloneRepo($repo, $password = null, $url, $private = 0, $update = 0, &$errorMessage = null) {
        $this->createRepositoryCGI();

        $repo_file = $this->repository_file($repo);
        if (file_exists($repo_file)) {
            $errorMessage = 'File already exists';

            return false;
        }

        $this->fossil($repo, array('clone', '-A', $this->user['username'], $url, $repo_file), $output, $return, 3600);

        if ($return !== 0) {
            if (file_exists($repo_file)) {
                unlink($repo_file);
            }

            $errorMessage = 'Clone failed: ' . join("\n", $output);

            return false;
        }

        $sql = "INSERT INTO repositories
                       (user_id, name, private, cloned, auto_update)
                VALUES (:id, :name, :private, 1, :auto)";

        $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private, 'auto' => $update);

        if (!Nano_Db::execute($sql, $bind)) {
            if (file_exists($repo_file)) {
                unlink($repo_file);
            }

            $errorMessage = 'Internal Error: DB Insert failed';

            return false;
        }

        $return = $this->postCreateRepo($repo, $password);

        return $return;
    }

    public function uploadRepo($repo, $password, $private = 0, array $file, &$errorMessage = null) {
        $this->createRepositoryCGI();

        $repo_file = $this->repository_file($repo);

        if (file_exists($repo_file)) {
            $errorMessage = 'File already exists';

            return false;
        }

        if (!@move_uploaded_file($file['tmp_name'], $repo_file)) {
            $errorMessage = 'Internal Error: Upload failed';

            return false;
        }

        $this->fossil($repo, array('config', '-R', $repo_file, 'export', 'project', '/tmp/config'), $output, $return);
        if (file_exists($this->root_fs . '/tmp/config')) {
            unlink($this->root_fs . '/tmp/config');
        }

        if ($return !== 0) {
            if (file_exists($repo_file)) {
                unlink($repo_file);
            }

            $errorMessage = 'Invalid repository';

            return false;
        }

        $return = $this->createUser($repo);
        if ($return === false) {
            if (file_exists($repo_file)) {
                unlink($repo_file);
            }

            $errorMessage = 'Failed to create new user';

            return false;
        }

        $sql = "INSERT INTO repositories
                       (user_id, name, private, cloned, auto_update)
                VALUES (:id, :name, :private, 0, 0)";

        $bind = array('id' => $this->user['id'], 'name' => $repo, 'private' => $private);

        if (!Nano_Db::execute($sql, $bind)) {
            if (file_exists($repo_file)) {
                unlink($repo_file);
            }

            $errorMessage = 'Internal Error: DB Insert failed';

            return false;
        }

        $return = $this->postCreateRepo($repo, $password);

        return $return;
    }

    public function pullRepo($repo, $url = '', &$outputstr = null) {
        $repo_file = $this->repository_file($repo);

        if ($url != '') {
            if (file_exists($url) || preg_match('/:\/\//', $url) == 0) {
                $outputstr = "Invalid URL";
                return false;
            }
        }

        if (!file_exists($repo_file)) {
            $outputstr = "Invalid repository";

            return false;
        }

        # Ensure that no non-default SSH command can be used for a pull
        $this->fossil($repo, array('unset', 'ssh-command', '-R', $repo_file), $output, $return);
        if ($return !== 0) {
            $outputstr = "Failed to unset ssh-command: " . join("\n", $output);
            return false;
        }

        if ($url == '') {
            $this->fossil($repo, array('pull', '-R', $repo_file), $output, $return, 3600);
        } else {
            $this->fossil($repo, array('pull', '-R', $repo_file, $url), $output, $return, 3600);
        }

        $outputstr = join("\n", $output);

        if ($return !== 0) {
            return false;
        }

        return true;
    }

    public function getRepos() {
        $sql = "SELECT *
                  FROM repositories
                 WHERE user_id = :id";

        $bind = array('id' => $this->user['id']);

        if ($result = Nano_Db::query($sql, $bind)) {
            return $result;
        }

        return false;
    }

    public function getRepoById($id) {
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
        $repo_file = $this->repository_file($repo);
        $return['repo-file'] = $repo_file;

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

            $this->fossil($repo, array('test-obscure', $return['clone-pw']), $output, $returnCode);

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

    public function updateRepo($repo, $private, $update, $cloned, $password = null) {
        $repo_file = $this->repository_file($repo);

        if (!file_exists($repo_file)) {
            return false;
        }

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

        if (!Nano_Db::execute($sql, $bind)) {
            return false;
        }

        if ($password) {
            $this->setPassword($repo, $password);
        }

        return true;
    }

    public function remRepo($repo) {
        $repo_file = $this->repository_file($repo);

        if (!file_exists($repo_file)) {
            return false;
        }

        $sql = "DELETE FROM repositories
                 WHERE user_id = :id
                   AND name    = :repo";

        $bind = array('id' => $this->user['id'], 'repo' => $repo);

        if (!Nano_Db::execute($sql, $bind)) {
            return false;
        }

        unlink($repo_file);

        return true;
    }

    public function remAllRepos() {
        if (!file_exists("{$this->absolute_path}")) {
            return false;
        }

        $sql = "DELETE FROM repositories
                 WHERE user_id = :id";

        $bind = array('id' => $this->user['id']);

        if (!Nano_Db::execute($sql, $bind)) {
            return false;
        }

        system('rm -f ' . escapeshellarg($this->absolute_path) . '/*.fossil');

        unlink("{$this->absolute_path_root}repository");
        rmdir("{$this->absolute_path_root}");

        return true;
    }

    public function rebuildAllRepos() {
        if (!file_exists("{$this->absolute_path}")) {
            return false;
        }

        $sql = "SELECT name FROM repositories WHERE user_id = :id";
        $bind = array('id' => $this->user['id']);
        $result = Nano_Db::query($sql, $bind);
        if ($result === false) {
            return false;
        }

        foreach ($result as $repo_info) {
            $repo = $repo_info['name'];
            $repo_file = $this->repository_file($repo);
            $this->fossil($repo, array('rebuild', $repo_file, '--quiet', '--wal'), $output, $return, 7200);
            if ($return !== 0) {
                $outputstr = join("\n", $output);
                error_log("Failed while rebuilding {$repo_file}: {$outputstr}");
            }
        }

        return true;
    }
}
