<?php

require __DIR__ . '/../loader.php';

use core\engine\Application;
use core\engine\Router;

Application::init();

Router::registerDefault(function () {
    echo 'Not found';
});
Router::register(function () {
    var_dump(@Application::getValue('version'));
}, 'test');


Router::register(function () {
    if (@$_GET['to'] && @$_GET['msg']) {
        var_dump(\core\engine\Email::send($_GET['to'], [], $_GET['msg'], $_GET['msg']));
    }
}, 'email');

call_user_func(Router::current());