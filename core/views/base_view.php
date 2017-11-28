<?php

namespace core\views;

use core\controllers\Administrator_controller;
use core\controllers\Dashboard_controller;
use core\controllers\Deposit_controller;
use core\engine\Application;
use core\controllers\Investor_controller;

class Base_view
{
    static public function header($title = '', $contentClass = '')
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Cryptaur<?= $title ? " - $title" : '' ?></title>
            <base href="<?= APPLICATION_URL ?>/">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no, minimal-ui">
            <link rel="shortcut icon" href="favicon.png" type="image/png">
            <link rel="stylesheet" href="styles/materialize.min.css?<?= md5_file(PATH_TO_WEB_ROOT_DIR . '/styles/materialize.min.css') ?>">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <link rel="stylesheet" href="styles/bundle.min.css?<?= md5_file(PATH_TO_WEB_ROOT_DIR . '/styles/bundle.min.css') ?>">
            <script type="text/javascript" src="scripts/jquery-3.2.1.min.js"></script>
        </head>
        <body>

        <nav>
            <?= self::nav() ?>
        </nav>
        <div class="wrapper">
        <div class="content <?= $contentClass ?>">

        <?php
        return ob_get_clean();
    }

    static private function nav()
    {
        ob_start();
        ?>
        <div class="nav-wrapper">
            <a href="#" class="brand-logo">crypt<span>aur</span></a>
            <a href="#" data-activates="mobile-demo" class="button-collapse"><i class="material-icons">menu</i></a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <?= self::menuList() ?>
                <li><a class="dropdown-button" href="#!" data-activates="dropdown_flag"><img src="images/flag_usa.png"/><i class="material-icons right">keyboard_arrow_down</i></a>
                </li>
            </ul>
            <ul class="side-nav" id="mobile-demo">
                <?= self::menuList() ?>
            </ul>
        </div>
        <ul id="dropdown_flag" class="dropdown-content">
            <li><a href="#!"><img src="images/flag_usa.png"/></a></li>
            <li><a href="#!"><img src="images/flag_rus.png"/></a></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    static private function menuList()
    {
        // todo: what li is actice?
        ob_start();
        if (Application::$authorizedAdministrator) { ?>
            <li><a href="<?= Administrator_controller::COINS_SETTINGS ?>">Coins settings</a></li>
            <li><a href="<?= Administrator_controller::ADMINISTRATORS_LIST ?>">Administrators</a></li>
            <li class="login">Admin: <?= Application::$authorizedAdministrator->email ?></li>
            <li><a href="<?= Administrator_controller::LOGOUT_URL ?>">Logout</a></li>
        <?php } elseif (Application::$authorizedInvestor) { ?>
            <li><a href="">About</a></li>
            <li><a href="<?= Dashboard_controller::BASE_URL ?>">Dashboard</a></li>
            <li><a href="<?= Deposit_controller::TRANSACTIONS_URL ?>">Transactions history</a></li>
            <li class="login"><?= Application::$authorizedInvestor->email ?></li>
            <li><a href="<?= Investor_controller::LOGOUT_URL ?>">Logout</a></li>
        <?php } else { ?>
            <li><a href="#">About</a></li>
            <li><a href="<?= Investor_controller::LOGIN_URL ?>">Login</a></li>
            <li><a href="<?= Investor_controller::REGISTER_URL ?>">Register</a></li>
        <?php }
        return ob_get_clean();
    }

    static public function text($text)
    {
        return "<h3>$text</h3>";
    }

    static public function footer()
    {
        ob_start();
        ?>

        </div>
        <footer>
            <div class="container">
                <div class="row">
                    <div class="col s12 m6 copyright">
                        <p>Copyright &copy; 2017. All right reserved.</p>
                    </div>
                    <div class="col s12 m6 terms-and-conditions">
                        <p>Terms and Conditions</p>
                    </div>
                </div>
            </div>
        </footer>
        </div>

        <script type="text/javascript" src="scripts/materialize.min.js?<?= md5_file(PATH_TO_WEB_ROOT_DIR . '/scripts/materialize.min.js') ?>"></script>
        <script type="text/javascript" src="scripts/script.js?<?= md5_file(PATH_TO_WEB_ROOT_DIR . '/scripts/script.js') ?>"></script>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}