<?php

$repositories = glob('../repos/*/*.fossil');

foreach ($repositories as $repository) {
    echo $repository . "\n";
    system("sudo fossil rebuild -R {$repository}");
    echo "\n";
}
