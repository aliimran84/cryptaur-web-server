<?php

namespace core\controllers;

use core\models\Administrator;
use core\engine\Application;
use core\engine\Utility;
use core\engine\Router;
use core\views\Base_view;
use core\views\Administrator_view;
use core\views\Menu_point;

class Administrator_controller
{
    static public $initialized = false;

    const BASE_URL = 'administrator';
    const LOGIN_URL = 'administrator/login';
    const LOGOUT_URL = 'administrator/logout';
    const SET_URL = 'administrator/set';
    const SETTINGS = 'administrator/settings';
    const ADMINISTRATORS_LIST = 'administrator/list';

    const SESSION_KEY = 'authorized_administrator_id';

    static public function init()
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        session_start();
        $authorizedAdministrator = Administrator::getById(@$_SESSION[self::SESSION_KEY]);
        if ($authorizedAdministrator) {
            Application::$authorizedAdministrator = $authorizedAdministrator;
        }
        session_abort();

        Router::register(function () {
            if (Application::$authorizedAdministrator) {
                Utility::location();
            } else {
                Utility::location(self::LOGIN_URL);
            }
        }, self::BASE_URL);

        Router::register(function () {
            if (Application::$authorizedAdministrator) {
                Utility::location(self::BASE_URL);
            }
            Base_view::$TITLE = 'Administrator login';
            Base_view::$MENU_POINT = Menu_point::Admin_login;
            echo Base_view::header();
            echo Administrator_view::loginForm();
            echo Base_view::footer();
        }, self::LOGIN_URL, Router::GET_METHOD);
        Router::register(function () {
            if (Application::$authorizedAdministrator) {
                Utility::location(self::BASE_URL);
            }
            self::handleLoginRequest();
        }, self::LOGIN_URL, Router::POST_METHOD);

        Router::register(function () {
            if (!Application::$authorizedAdministrator) {
                Utility::location(self::BASE_URL);
            }
            self::handleLogoutRequest();
        }, self::LOGOUT_URL);

        Router::register(function () {
            // administrators can setup only administrator
            if (!Application::$authorizedAdministrator) {
                Utility::location(self::BASE_URL);
            }
            Base_view::$TITLE = 'Administrator setup';
            echo Base_view::header();
            $email = '';
            if (isset($_GET['id'])) {
                $email = Administrator::getById($_GET['id'])->email;
            }
            echo Administrator_view::setForm($email);
            echo Base_view::footer();
        }, self::SET_URL, Router::GET_METHOD);
        Router::register(function () {
            // administrators can setup only administrator
            if (!Application::$authorizedAdministrator) {
                Utility::location(self::BASE_URL);
            }
            self::handleSetRequest();
        }, self::SET_URL, Router::POST_METHOD);

        Router::register(function () {
            if (!Application::$authorizedAdministrator) {
                Utility::location(self::BASE_URL);
            }
            Base_view::$TITLE = 'Administrators list';
            Base_view::$MENU_POINT = Menu_point::Administrators_list;
            echo Base_view::header();
            echo Administrator_view::administratorsList();
            echo Base_view::footer();
        }, self::ADMINISTRATORS_LIST);

        Router::register(function () {
            // administrators can setup only administrator
            if (!Application::$authorizedAdministrator) {
                Utility::location(self::BASE_URL);
            }
            Base_view::$TITLE = 'Settings';
            Base_view::$MENU_POINT = Menu_point::Settings;
            echo Base_view::header();
            echo Administrator_view::coinsSettings();
            echo Base_view::footer();
        }, self::SETTINGS, Router::GET_METHOD);
    }

    static public function handleLoginRequest()
    {
        $administratorId = @Administrator::getIdByEmailPassword($_POST['email'], $_POST['password']);
        if ($administratorId) {
            session_start();
            $_SESSION[self::SESSION_KEY] = $administratorId;
            session_write_close();
            Utility::location(self::BASE_URL);
        }
        Utility::location(self::LOGIN_URL);
    }

    static public function handleLogoutRequest()
    {
        session_start();
        unset($_SESSION[self::SESSION_KEY]);
        session_write_close();
        Utility::location();
    }

    static public function handleSetRequest()
    {
        if (!filter_var(@$_POST['email'], FILTER_VALIDATE_EMAIL)) {
            Utility::location(self::SET_URL . '?err=1');
        }
        if (!preg_match('/^[0-9A-Za-z?!@#$%\-\_\.,;:]{6,50}$/', @$_POST['password'])) {
            Utility::location(self::SET_URL . '?err=2');
        }
        Administrator::setAdministrator($_POST['email'], self::hashPassword($_POST['password']));
        Utility::location(self::ADMINISTRATORS_LIST);
    }

    static public function hashPassword($password)
    {
        return hash('sha256', $password . APPLICATION_ID . 'administrator');
    }
}