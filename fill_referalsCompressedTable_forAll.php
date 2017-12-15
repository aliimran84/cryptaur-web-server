<?php

require __DIR__ . '/loader.php';

use core\engine\Application;
use core\models\Investor;

Application::init();

Investor::fill_referalsCompressedTable_forAll(function ($i, $all) {
    echo "Fill for $i/$all\r\n";
});