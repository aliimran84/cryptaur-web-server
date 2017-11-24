<?php

require __DIR__ . '/loader.php';

use core\controllers\Administrator_controller;
use core\engine\Application;
use core\models\Administrator;

Application::init();

if (!function_exists('readline')) {
    function readline($prompt = null)
    {
        if ($prompt) {
            echo $prompt;
        }
        $fp = fopen('php://stdin', "r");
        $line = rtrim(fgets($fp, 1024));
        return $line;
    }
}

$email = readline('Enter admin email: ');
$password = readline('Enter admin password: ');

Administrator::setAdministrator($email, Administrator_controller::hashPassword($password));