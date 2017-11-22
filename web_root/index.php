<?php

require __DIR__ . '/../loader.php';

use core\engine\Application;
use core\engine\Configuration;
use core\engine\Router;

Application::init();

Configuration::requireLoadConfigFromFile(PATH_TO_WORKING_DIR . '/config.json');

Router::registerDefault(function () {
    echo 'Not found';
});
Router::register(function () {
    var_dump(core\engine\DB::get("SELECT * FROM a WHERE a=?", [1]));
}, 'test');


Router::register(function () {
    if (@$_GET['to'] && @$_GET['msg']) {
        var_dump(\core\engine\Email::send($_GET['to'], [], $_GET['msg'], $_GET['msg']));
    }
}, 'email');

call_user_func(Router::current());