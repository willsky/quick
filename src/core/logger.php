<?php
namespace Quick\Core;

final class Logger {
    const FATAL = 0;
    const ERROR = 1;
    const WARN = 2;
    const DEBUG = 3; 
    const INFO = 4;
    const NOTICE = 5;

    private static $filePath = NULL;
    private static $fileNamePrefix = NULL;
    private static $setting = NULL;
    private static $fileName = NULL;

    private static function getLogerLevel(){

        if (is_null(self::$setting)) {
            self::$setting = App::instance()->get('logger');

            if ( !self::$setting ) { 
                self::$setting = array();
            }

            self::$filePath = isset(self::$setting['path']) ? self::$setting['path'] : (APP_PATH . DS . 'logs');
            self::$fileNamePrefix = isset(self::$setting['prefix']) ? self::$setting['prefix'] : 'quick';
        }

        if ( isset(self::$setting['level'])) $level = intval(self::$setting['level']);

        if ( $level > 5 ) $level = 5;

        if ( $level < 0 ) $level = 0;

        return $level;
    }

    private static function writeToFile($msg){
        $filePath = implode(DIRECTORY_SEPARATOR, array(self::$filePath, self::$fileName));
        $msg .= PHP_EOL;
        error_log($msg, 3, $filePath);
    }

    private static function write($name, $msg, $level='debug'){
        $level = strtolower($level);
        $className = get_called_class();

        if ( method_exists($className, $level) ) {
            $level_num = constant($className . '::' . strtoupper($level));

            if ($level_num <= static::getLogerLevel()) {
                if (is_array($msg) || is_object($msg)) {
                    $msg = print_r($msg, TRUE);
                }
                
                self::$fileName = sprintf("%s_%s_%s.log", self::$fileNamePrefix, $level, date('Ymd'));
                // $msg = sprintf('[%s] %s: [%s] %s', date('Y-m-d H:i:s'), strtoupper($level), $name, print_r($msg, true));
                $msg = sprintf('[%s] %s: [%s] %s', date('Y-m-d H:i:s'), strtoupper($level), $name, $msg);
                static::writeToFile($msg);
            }
        }
    }

    public static function debug($name, $msg){
        static::write($name, $msg, 'debug');
    }

    public static function error($name, $msg){
        static::write($name, $msg, 'error');
    }

    public static function info($name, $msg){
        static::write($name, $msg, 'info');
    }

    public static function notice($name, $msg){
        static::write($name, $msg, 'notice');
    }

    public static function warn($name, $msg){
        static::write($name, $msg, 'warn');
    }

    public static function fatal($name, $msg){
        static::write($name, $msg, 'fatal');
    }
}
