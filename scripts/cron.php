<?php

include('include.php');

$sql = "SELECT *
          FROM repositories AS r
         INNER JOIN users AS u
            ON r.user_id = u.id
         WHERE auto_update = 1 AND cloned = 1";

if ($result = Nano_Db::query($sql)) {
    foreach ($result as $repo) {
        $fossil = new Nano_Fossil($repo);
        if ($fossil->pullRepo($repo['name'], '', $output)) {
            echo "{$repo['name']}.fossil successfully updated.\n";
        } else {
            echo "{$repo['name']}.fossil failed to be updated.\n" . join("\n        ", $output);
        }
    }
}         
