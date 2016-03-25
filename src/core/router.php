<?php
namespace Quick\Core;

defined('ERROR_ROUTER_NOT_FOUND') || define('ERROR_ROUTER_NOT_FOUND', 1400);

final class Router
{
    private static $map = array();
    private static $_params = array();
    public static $controller = 'Index';
    public static $action = 'index';

    public static function set($path, $action = NULL) {
        if (is_array($path)) {
            foreach($path as $_path => $_action) {
                self::set($_path, $action);
            }
        } else {
            $path = strtolower($path);
            $map[$path] = $action;
        }
    }

    public static function run(){
        $uri = '/';

        if (isset($_REQUEST['req_uri'])) {
            $uri = trim($_REQUEST['req_uri']);
            unset($_REQUEST['req_uri']);
            $_SERVER['REQUEST_URI'] = $uri;
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }

        if (isset($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        }

        if (isset(self::$map[$uri])) {
            self::match(self::$map[$uri]);
        } else {
            self::matchDefault($uri);
        }

        $className = '\\Controllers\\'.self::$controller;
        $controllerObject =  new $className();

        if (!method_exists($controllerObject, self::$action)) {
            throw new \Exception(sprintf("Controller %s lost method of %s!", self::$controller, self::$action), ERROR_ROUTER_NOT_FOUND);
        }

        $runAction = TRUE;
        
        if (method_exists($controllerObject, 'before')) {
            $runAction = $controllerObject->before();
        }

        if ($runAction) {
            call_user_func_array(array($controllerObject, self::$action), self::getParams());
            
            if (method_exists($controllerObject, 'after')) {
                $controllerObject->after();
            }
        }        

        if ($controllerObject->autoRender) {
            $controllerObject->view->render();
        }
    }

    public static function getParams() {
        return self::$_params;
    }

    private static function matchDefault($path) {
        $path = trim($path, '/');

        if ($path) {
            $pattern = explode('/', $path);
            self::$controller = ucfirst($pattern[0]);

            if(count($pattern) > 1) {
                self::$action = $pattern[1];
                self::$_params = array_slice($pattern, 2);
            }
        }
    }

    private static function match($action) {
        if ($action) {
            $pattern = explode(':', $action);
            self::$controller = $pattern[0];

            if (count($pattern) > 1) {
                self::$action = $pattern[1];
                self::$_params = array_slice($pattern, 2);
            }
        }
    }
}