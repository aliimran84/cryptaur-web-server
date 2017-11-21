<?php

require __DIR__ . '/../loader.php';

use core\Application;
use core\Configuration;
use core\Router;

Application::init();

Configuration::requireLoadConfigFromFile(PATH_TO_WORKING_DIR . '/config.json');

Router::registerDefault(function () {
    echo 'Not found';
});
Router::register(function () {
    echo '!!!';
}, 'test');


Router::register(function () {
    if (@$_GET['to'] && @$_GET['msg']) {
        var_dump(\core\Email::send($_GET['to'], [], $_GET['msg'], $_GET['msg']));
    }
}, 'email');

call_user_func(Router::current());