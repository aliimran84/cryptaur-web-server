<?php

namespace core\views;

use core\models\Investor_controller;

class Investor_view
{
    static public function loginForm()
    {
        ob_start();
        ?>
        <form action="/<?= Investor_controller::LOGIN_URL ?>" method="post">
            <input type="text" name="email" placeholder="email">
            <input type="password" name="password" placeholder="password">
            <input type="submit">
        </form>
        <?php
        return ob_get_clean();
    }

    static public function registerForm()
    {
        ob_start();
        ?>
        <form action="/<?= Investor_controller::REGISTER_URL ?>" method="post">
            <input type="text" name="email" placeholder="email">
            <input type="password" name="password" placeholder="password">
            <input type="text" name="eth_address" placeholder="eth_address">
            <input type="text" name="referrer_code" placeholder="referrer_code">
            <input type="submit">
        </form>
        <?php
        return ob_get_clean();
    }
}