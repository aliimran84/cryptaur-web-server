<?php

namespace core\engine;

/**
 * Class Router - singleton
 * @package core\router
 */
class Router
{
    static public $queryVar = '';

    /**
     * singleton object
     * @var Router
     */
    static private $_instance;

    /**
     * @return Router
     */
    static private function inst()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
    }

    const POST_METHOD = 'POST';
    const GET_METHOD = 'GET';
    const ANY_METHOD = '*';

    /**
     * @var array
     */
    private $handlers = [];

    /**
     * @param callable $callback
     * @param string $path
     * @param string $method
     * @return bool
     */
    static public function register($callback, $path, $method = Router::ANY_METHOD)
    {
        $router = self::inst();
        if (isset($router->handlers["$path/$method"])) {
            return false;
        }
        $router->handlers["$path/$method"] = $callback;
        return true;
    }

    /**
     * @param callable $callback
     * @return bool
     */
    static public function registerDefault($callback)
    {
        return self::register($callback, '');
    }

    /**
     * @param string $path
     * @param string $method
     * @return callable
     */
    static private function getByPathAndMethod($path, $method = Router::ANY_METHOD)
    {
        $path = trim($path, '/');
        $router = self::inst();
        if (isset($router->handlers["$path/$method"])) {
            return $router->handlers["$path/$method"];
        } else if (isset($router->handlers["$path/" . Router::ANY_METHOD])) {
            return $router->handlers["$path/" . Router::ANY_METHOD];
        }
        $isSub = preg_match_all('/(.*)\/(.*)$/', $path, $matches);
        if ($isSub) {
            $subPath = $matches[1][0];
            self::$queryVar = $matches[2][0];
            if ($path !== $subPath) {
                return self::getByPathAndMethod($subPath, $method);
            }
        }
        return $router->handlers["/" . Router::ANY_METHOD];
    }

    /**
     * @return callable
     */
    static public function current()
    {
        $method = '*';
        if ($_SERVER['REQUEST_METHOD'] === Router::POST_METHOD || $_SERVER['REQUEST_METHOD'] === Router::GET_METHOD) {
            $method = $_SERVER['REQUEST_METHOD'];
        }
        $path = self::parsePath()['call'];
        return self::getByPathAndMethod($path, $method);
    }

    /**
     * @return array
     */
    static private function parsePath()
    {
        $path = array();
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_path = explode('?', $_SERVER['REQUEST_URI']);
            $path['base'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/');
            $path['call'] = substr(urldecode($request_path[0]), strlen($path['base']) + 1);
            if ($path['call'] == basename($_SERVER['PHP_SELF'])) {
                $path['call'] = '';
            }
            $path['call_parts'] = explode('/', $path['call']);
            $path['query'] = urldecode(@$request_path[1]);
            $vars = explode('&', $path['query']);
            foreach ($vars as $var) {
                $t = explode('=', $var);
                $path['query_vars'][@$t[0]] = @$t[1];
            }
        }
        return $path;
    }
}