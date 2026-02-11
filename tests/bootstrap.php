<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
<<<<<<< HEAD

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
=======
>>>>>>> b2f43ba2c3b18bebe120cab4f5fa1f2e65b267bc
