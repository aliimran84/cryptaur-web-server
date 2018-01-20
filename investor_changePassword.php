<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\models\Investor;

Application::init();

$investor = Investor::getByEmail(@$argv[1]);
if (!$investor) {
    echo 'User not found';
} else {
    $investor->changePassword(@$argv[2]);
    echo 'Password change. New password length: ' . strlen(@$argv[2]);
}
echo "\r\n";