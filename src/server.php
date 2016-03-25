<?php
namespace Quick;

defined('FRAME_PATH') || define('FRAME_PATH', dirname(__DIR__));
defined('LIB_PATH') || define('LIB_PATH', FRAME_PATH);
defined('APP_PATH') || define('APP_PATH', dirname(LIB_PATH) . DIRECTORY_SEPARATOR . 'app');
defined('CONF_PATH') || define('CONF_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'conf');
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

// 错误码相关
defined('ERROR_SYSTEM_CORE_MAX') || define('ERROR_SYSTEM_CORE_MAX', 1000);
defined('EXCEPTION_SYSTEM_CORE') || define('EXCEPTION_SYSTEM_CORE', 400);
defined('ERROR_SYSTEM_CORE') || define('ERROR_SYSTEM_CORE', 500);

class Server {
    public static function run($func = NULL) {
        if (!defined('APP_RUN')) {
            self::autoload();
            Core\Config::load(CONF_PATH . DS . 'bootstrap.php');

            if (is_callable($func)) {
                call_user_func($func);
            }
            
            self::exceptionHandler();
            self::errorHandler();
            self::registerShutdown();
            Core\App::instance()->run();
            define('APP_RUN', TRUE);
        }
    }

    public static function autoload($func = NULL) {
        if (!defined('AUTOLOAD')) {
            if ( is_null($func) ) {
                spl_autoload_register(array(get_called_class(), 'auto'));
            } else {
                spl_autoload_register($func);
            }

            define('AUTOLOAD', TRUE);
        }
    }

    private static function formatClassPath($className){
        $className = ltrim($className, '\\');
        return strtolower(strtr($className, '\\', DIRECTORY_SEPARATOR)) . '.php';
    }

    private static function auto($className) {
        if ( !class_exists($className, FALSE) && !interface_exists($className, FALSE) ) {
            $classFile = self::formatClassPath($className);
            $libPrefix = explode(DIRECTORY_SEPARATOR, $classFile);

            switch(strtoupper($libPrefix[0])) {
                case 'CONTROLLERS':
                case 'MODELS':
                case 'PLUGIN':
                    require(implode(DIRECTORY_SEPARATOR, array(APP_PATH, $classFile)));
                    break;
                default:
                    require(implode(DIRECTORY_SEPARATOR, array(LIB_PATH, $classFile)));
            }
            
        }
    }

    public static function errorHandler($func = NULL) {
        if (!defined('ERROR_HANDLER')) {
            if ( is_null($func) ) {
                set_error_handler(array(get_called_class(), 'error'));
            } else {
                set_error_handler($func);
            }
            define('ERROR_HANDLER', TRUE);
        }
    }

    public static function error($code, $message, $errorFile, $errorLine, $trace) {
        if ($code < ERROR_SYSTEM_CORE_MAX) {
            $code = ERROR_SYSTEM_CORE;
        }

        Core\Logger::error("System", sprintf('error_file: %s; error_line: %s; code: %s ; msg: %s', $errorFile, $errorLine, $code, $message));
        require_once(__DIR__ . DS . 'view'. DS .'error.php');
        exit;
    }

    public static function exceptionHandler($func = NULL) {
        if (!defined('EXCEPTION_HANDLER')) {
            if ( is_null($func) ) {
                set_exception_handler(array(get_called_class(), 'exception'));
            } else {
                set_exception_handler($func);
            }
            define('EXCEPTION_HANDLER', TRUE);
        }
    }

    public static function exception($exceptionObject) {
        $code = $exceptionObject->getCode();
        $message = $exceptionObject->getMessage();
        $errorFile = $exceptionObject->getFile();
        $errorLine = $exceptionObject->getLine();
        $trace = $exceptionObject->getTrace();

        if ($code < ERROR_SYSTEM_CORE_MAX) {
            $code = EXCEPTION_SYSTEM_CORE;
        }

        Core\Logger::error("System", sprintf('code: %s   msg: %s', $code, $message));
        require_once(__DIR__ . DS . 'view'. DS .'error.php');
        exit;
    }

    public static function registerShutdown($func = NULL) {
        if (!defined('SHUTDOWN')) {
            if ( is_null($func) ) {
                register_shutdown_function(array(get_called_class(), 'shutdown'));
            } else {
                register_shutdown_function($func);
            }

            define('SHUTDOWN', TRUE);
        }
    }

    public static function shutdown() {
        if (function_exists('app_shutdown')) {
            app_shutdown();
        }

        if (($error = error_get_last()) && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR,E_COMPILE_ERROR))) {
            throw new \Exception(sprintf('Fatal error: %s', $error['message']), ERROR_SYSTEM_CORE);
        }
    }
}
