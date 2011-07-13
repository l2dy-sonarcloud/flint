<?php

$view = new Nano_View;

$sql = "SELECT *
          FROM repositories AS r
         INNER JOIN users AS u
            ON r.user_id = u.id
         WHERE private = 0";

if ($result = Nano_Db::query($sql)) {
    $view->repositories = $result;
}

$view->dispatch();
