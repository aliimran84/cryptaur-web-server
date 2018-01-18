<?php

namespace core\views;

use core\controllers\Administrator_controller;
use core\controllers\Dashboard_controller;
use core\controllers\Deposit_controller;
use core\engine\Application;
use core\controllers\Investor_controller;
use core\translate\Translate;

class Base_view
{
    static public $TITLE = '';
    static public $CONTENT_BLOCK_CLASSES = [];
    static public $MENU_POINT = 0;

    static public function header()
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Cryptaur<?= self::$TITLE ? ' - ' . self::$TITLE : '' ?></title>
            <base href="<?= APPLICATION_URL ?>/">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no, minimal-ui">
            <link rel="shortcut icon" href="favicon.png" type="image/png">
            <link rel="stylesheet" href="styles/materialize.min.css?<?= md5_file(PATH_TO_WEB_ROOT_DIR . '/styles/materialize.min.css') ?>">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <link rel="stylesheet" href="styles/bundle.min.css?<?= md5_file(PATH_TO_WEB_ROOT_DIR . '/styles/bundle.min.css') ?>">
            <?php if (Application::$authorizedAdministrator) { ?>
                <link rel="stylesheet" href="styles/administrator.css?<?= md5_file(PATH_TO_WEB_ROOT_DIR . '/styles/administrator.css') ?>">
            <?php } ?>
            <script type="text/javascript" src="scripts/jquery-3.2.1.min.js"></script>
        </head>
        <body>

        <nav>
            <?= self::nav() ?>
        </nav>
        <div class="wrapper">
        <div class="content <?= implode(' ', self::$CONTENT_BLOCK_CLASSES) ?>">

        <?php
        return ob_get_clean();
    }

    static private function nav()
    {
        ob_start();
        ?>
        <div class="nav-wrapper">
            <a href="https://cryptaur.com" class="brand-logo">crypt<span>aur</span></a>
            <a href="#" data-activates="mobile-demo" class="button-collapse"><i class="material-icons">menu</i></a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <?= self::menuList() ?>
                <li><a class="dropdown-button" href="#!" data-activates="dropdown_flag"><img src="<?php
                        if (Translate::defaultLang() === 'ru') {
                            echo 'images/flag_rus.png';
                        } else {
                            echo 'images/flag_usa.png';
                        }
                        ?>"/><i class="material-icons right">keyboard_arrow_down</i></a>
                </li>
            </ul>
            <ul class="side-nav" id="mobile-demo">
                <?= self::menuList() ?>
            </ul>
        </div>
        <ul id="dropdown_flag" class="dropdown-content">
            <li><a href="<?= Translate::languageSwitchHref('en') ?>"><img src="images/flag_usa.png"/></a></li>
            <li><a href="<?= Translate::languageSwitchHref('ru') ?>"><img src="images/flag_rus.png"/></a></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    /**
     * @param int $point
     * @return string classname "active"
     */
    static private function activeMenuItem($point)
    {
        if (self::$MENU_POINT === $point) {
            return 'active';
        }
        return '';
    }

    static private function menuList()
    {
        ob_start();
        if (Application::$authorizedAdministrator) { ?>
            <li class="<?= self::activeMenuItem(Menu_point::Administrator_logs) ?>">
                <a href="<?= Administrator_controller::LOGS ?>"><?= Translate::td('Logs') ?></a></li>
            <li class="<?= self::activeMenuItem(Menu_point::Administrator_settings) ?>">
                <a href="<?= Administrator_controller::SETTINGS ?>"><?= Translate::td('Settings') ?></a></li>
            <li class="<?= self::activeMenuItem(Menu_point::Administrators_list) ?>">
                <a href="<?= Administrator_controller::ADMINISTRATORS_LIST ?>"><?= Translate::td('Administrators') ?></a>
            </li>
            <li class="login <?= self::activeMenuItem(Menu_point::Admin_login) ?>">
                Admin: <?= Application::$authorizedAdministrator->email ?></li>
            <li class="<?= self::activeMenuItem(Menu_point::Admin_logout) ?>">
                <a href="<?= Administrator_controller::LOGOUT_URL ?>"><?= Translate::td('Logout') ?></a></li>
        <?php } elseif (Application::$authorizedInvestor) { ?>
            <li class="<?= self::activeMenuItem(Menu_point::Dashboard) ?>">
                <a href="<?= Dashboard_controller::BASE_URL ?>"><?= Translate::td('Dashboard') ?></a></li>
            <li class="<?= self::activeMenuItem(Menu_point::Transactions) ?>">
                <a href="<?= Deposit_controller::TRANSACTIONS_URL ?>"><?= Translate::td('Transactions history') ?></a>
            </li>
            <?php if (@$_SESSION['tester']) { ?>
                <li class="<?= self::activeMenuItem(Menu_point::Cryptaur_ether_wallet) ?>">
                    <a href="<?= Investor_controller::CRYPTAURETHERWALLET_URL ?>"><?= Translate::td('Cryptaur Ether Wallet') ?></a>
                </li>
            <?php } ?>
            <li class="<?= self::activeMenuItem(Menu_point::Settings) ?>">
                <a href="<?= Investor_controller::SETTINGS_URL ?>"><?= Translate::td('Settings') ?></a></li>
            <li class="login <?= self::activeMenuItem(Menu_point::Login) ?>"><?= Application::$authorizedInvestor->email ?></li>
            <li class="<?= self::activeMenuItem(Menu_point::Logout) ?>">
                <a href="<?= Investor_controller::LOGOUT_URL ?>"><?= Translate::td('Logout') ?></a></li>
        <?php } else { ?>
            <li class="<?= self::activeMenuItem(Menu_point::Login) ?>">
                <a href="<?= Investor_controller::LOGIN_URL ?>"><?= Translate::td('Login') ?></a>
            </li>
            <li class="<?= self::activeMenuItem(Menu_point::Register) ?>">
                <a href="<?= Investor_controller::REGISTER_URL ?>"><?= Translate::td('Register') ?></a></li>
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
                        <p>
                            Copyright &copy; 2017. <?= Translate::td('All right reserved') ?>.
                            <?php
                            if (@$_GET['executed_time']) {
                                echo Application::executedTime();
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col s12 m6 terms-and-conditions">
                        <a target="_blank" href="https://cryptaur.com/terms-and-conditions"><?= Translate::td('Terms and Conditions') ?></a>
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

    static public function about_stageOne()
    {
        ob_start();
        ?>
        <section class="stage-one">
            <div class="row">
                <h3><?= Translate::td('Stage one') ?></h3>
            </div>
            <div class="row">
                <div class="col s12 m2">
                    <p class="date"><?= Translate::td('Start date') ?>:<br><?= Translate::td('November') ?> 27, 2017</p>
                    <p class="date"><?= Translate::td('End date') ?>:<br><?= Translate::td('December') ?> 07, 2017</p>
                </div>
                <div class="col s12 m10">
                    <div class="row">
                        <p class="header"><?= Translate::td('Discount') ?>:</p>
                        <p><?= Translate::td('The discount is offered throughout the 1st stage (November 27 through December 7) and will be 40% for the first day and 20% thereafter.') ?></p>
                    </div>
                    <div class="row">
                        <p class="header"><?= Translate::td('Purchase limits') ?>:</p>
                        <p>
                            <?= Translate::td('The minimum contribution limit remains the same as during presale – 50 USD (in CPT equivalent). Both existing participants who registered themselves during presale and new participants can take part in the token sale.') ?>
                        </p>
                    </div>
                    <div class="row">
                        <p class="header"><?= Translate::td('Referral Program') ?>:</p>
                        <p>
                            <?= Translate::td('Anyone can get registered and participate in the token sale (see Terms and Conditions for legal restrictions). A new participant can get registered either directly at the web page of the project or using a group invite link from an existing participant. Newly registered participants can invite further potential participants by sending them their group invite links. The following reward system is applicable to all participants (newly registered or registered earlier) who have at least 10000 CPTs in their Cryptaur wallet (cumulatively from all the previous presale and sale stages) on the CPTs purchased during the token sale by those they invited') ?>
                            :
                        </p>
                    </div>
                    <div class="row">
                        <p class="header"><?= Translate::td('From Level') ?> 1: 3%</p>
                        <p class="header"><?= Translate::td('From Level') ?> 2: 3%</p>
                        <p class="header"><?= Translate::td('From Level') ?> 3: 3%</p>
                        <p class="header"><?= Translate::td('From Level') ?> 4: 3%</p>
                        <p class="header"><?= Translate::td('From Level') ?> 5: 4%</p>
                        <p class="header"><?= Translate::td('From Level') ?> 6: 4%</p>
                    </div>
                    <div class="row">
                        <p>
                            <?= Translate::td('The bounty is awarded with the compression procedure applied to participants who have at least 10000 CPTs. Bounties for the purchases made during the 1st Stage will be calculated at the end of the 1st Stage with the compression procedure applied. The bounty received can be withdrawn to any Ether wallet address or re-invested into CPTs. In case of re-investment the discount will be 40% on the first day and 20% until January 22, including the period between Stage One and Stage Two of the token sale.') ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}