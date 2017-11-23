<?php

namespace core\views;

class Base_view
{
    static public function header($title = '')
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
            <link rel="stylesheet" href="styles/materialize.min.css">
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
            <link rel="stylesheet" href="styles/bundle.min.css?rev=6f325e308238c8ac4ee386583ae4b092">
        </head>
        <body>

        <nav>
            <?= self::nav() ?>
        </nav>
        <div class="wrapper">
        <div class="content">
        <div class="row">

        <?php
        return ob_get_clean();
    }

    static public function nav()
    {
        ob_start();
        ?>
        <div class="nav-wrapper">
            <a href="#" class="brand-logo">crypt<span>aur</span></a>
            <a href="#" data-activates="mobile-demo" class="button-collapse"><i class="material-icons">menu</i></a>
            <ul id="nav-mobile" class="right hide-on-med-and-down">
                <li><a href="#">About</a></li>
                <li><a href="#">Dashboard</a></li>
                <li><a class="active" href="#">Transactions history</a></li>
                <li><a href="#">Settings</a></li>
                <li class="login">name@mail.com</li>
                <li><a href="#">Logout</a></li>
                <li><a class="dropdown-button" href="#!" data-activates="dropdown_flag"><img src="images/flag_usa.png"/><i class="material-icons right">keyboard_arrow_down</i></a>
                </li>
            </ul>
            <ul class="side-nav" id="mobile-demo">
                <li><a href="#">About</a></li>
                <li><a href="#">Dashboard</a></li>
                <li><a href="#">Transactions history</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="#">Logout</a></li>
            </ul>
        </div>
        <ul id="dropdown_flag" class="dropdown-content">
            <li><a href="#!"><img src="images/flag_usa.png"/></a></li>
            <li><a href="#!"><img src="images/flag_rus.png"/></a></li>
        </ul>
        <?php
        return ob_get_clean();
    }

    static public function footer()
    {
        ob_start();
        ?>

        </div>
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

        <script type="text/javascript" src="scripts/jquery-3.2.1.min.js"></script>
        <script type="text/javascript" src="scripts/materialize.min.js"></script>
        <script type="text/javascript" src="scripts/script.js"></script>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}