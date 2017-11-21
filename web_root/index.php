<?php

require __DIR__ . '/../loader.php';

use core\router\Router;

Router::registerDefault(function () {
    echo 'Not found';
});
Router::register(function () {
    echo '!!!';
}, 'test');

call_user_func(Router::current());