<?php

$view = new Nano_View;

$sql = "SELECT *
          FROM repositories AS r
         INNER JOIN users AS u
            ON r.user_id = u.id
         WHERE private = 0";

if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'user') {
        $sql .= " ORDER BY u.username ASC";
    }
    else if ($_GET['sort'] == 'repository') {
        $sql .= " ORDER BY r.name ASC";
    }
}

if ($result = Nano_Db::query($sql)) {
    $repositories = array(
        0 => array(),
        1 => array(),
        2 => array(),
    );

    $i = 0;

    foreach ($result as $repo) {
        if ($i == 3) {
            $i = 0;
        }

        $repositories[$i][] = $repo;
        $i++;
    }

    $view->repositories = $repositories;
}

$view->dispatch();
