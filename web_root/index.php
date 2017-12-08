<?php

require __DIR__ . '/../loader.php';

use core\engine\Application;
use core\engine\DB;
use core\engine\Router;
use core\models\Coin;
use core\models\Investor;

Application::init();

Router::register(function () {
    $invsetor = Investor::getById(114);
    var_dump($invsetor);
//    var_dump(Investor::summonedBy($invsetor, true));
//    var_dump(\core\models\Bounty::rewardForReferrer($invsetor));
}, 'test');
Router::register(function () {
    echo \core\views\Base_view::header();
    echo \core\views\Base_view::footer();
}, 'test/1');

Router::register(function () {
    if (@$_GET['to'] && @$_GET['msg']) {
        var_dump(\core\engine\Email::send($_GET['to'], [], $_GET['msg'], $_GET['msg']));
    }
}, 'email');

call_user_func(Router::current());