<?php

require __DIR__.'/vendor/autoload.php';

$app = new App\Kernel;
$app
    ->setEnv($app::ENV_DEV)
    ->web()
    ->run();
