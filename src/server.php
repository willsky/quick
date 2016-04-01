<?php
namespace Quick;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);
// defined('FRAME_PATH') || define('FRAME_PATH', dirname(__DIR__));
// defined('LIB_PATH') || define('LIB_PATH', FRAME_PATH);
defined('APP_PATH') || define('APP_PATH', dirname(dirname(dirname(__DIR__))) . DS . 'app');
defined('CONF_PATH') || define('CONF_PATH', APP_PATH . DS . 'conf');
defined('APP_NAMESPACE') || define('APP_NAMESPACE', '\\App');
defined('VIEW_PATH') || define('VIEW_PATH', APP_PATH . DS . 'views');

// 错误码相关
defined('ERROR_SYSTEM_CORE_MAX') || define('ERROR_SYSTEM_CORE_MAX', 1000);
defined('EXCEPTION_SYSTEM_CORE') || define('EXCEPTION_SYSTEM_CORE', 400);
defined('ERROR_SYSTEM_CORE') || define('ERROR_SYSTEM_CORE', 500);

class Server {
    public static function run($func = NULL) {
        if (!defined('APP_RUN')) {
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

        if (($error = error_get_last()) && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR,E_COMPILE_ERROR, E_CORE_WARNING, E_COMPILE_WARNING))) {
            $errorFile = $error['file'];
            $code = ERROR_SYSTEM_CORE;
            $message = $error['message'];
            $errorLine = $error['line'];
            $trace = [];
            Core\Logger::error("Core", sprintf('error_file: %s; error_line: %s; code: %s ; msg: %s', $errorFile, $errorLine, $code, $message));
            require_once(__DIR__ . DS . 'view'. DS .'error.php');
            exit;
        }
    }
}
