<?php
namespace Quick\Core;

final class App extends Singleton
{
    private static $tables = array(
        'timezone' => 'Asia/Shanghai',
        'charset' => 'UTF-8',
        'cache_enable' => false,
        'serialize' => 'json',
        'logger' => array('level' => Logger::FATAL),
        'session_enable' => TRUE
    );

    protected function __construct()
    {
        $charset = $this->get('charset');

        if (!$charset) $charset = 'UTF-8';

        ini_set('default_charset',$charset);

        if (extension_loaded('mbstring')) {
            mb_internal_encoding($charset);
        }

        ini_set('display_errors',0);

        if ($timeZone = $this->get('timezone')) date_default_timezone_set($timeZone);

        ini_set('display_errors',0);
        // Deprecated directives
        @ini_set('magic_quotes_gpc',0);
        @ini_set('register_globals',0);
        @error_reporting(E_ALL|E_STRICT);

        if ($this->get('session_enable')) 
            session_start();
    }

    public function run() {
        Router::run();
    }

    public function get($key) {
        $value = Config::get($key);
        return $value ? $value : (isset(self::$tables[$key]) ? self::$tables[$key] : null);
    }

    public function serialize($data) {
        $serialize = strtolower($this->get('serialize'));

        if ('igbinary' == $serialize) {
            return igbinary_serialize($data);
        } 

        if ('json' == $serialize) {
            return json_encode($data);
        }

        if ('php' == $serialize) {
            return serialize($data);
        }

        if (extension_loaded('igbinary')) {
            return igbinary_serialize($data);
        }

        if(extension_loaded('json')) {
            return json_encode($data);
        }

        return serialize($data);
    }

    public function unserialize($data) {
        $serialize = strtolower($this->get('serialize'));

        if ('igbinary' == $serialize) 
            return igbinary_unserialize($data);

        if ('json' == $serialize) 
            return json_decode($data, TRUE);

        if ('php' == $serialize) 
            return unserialize($data);

        if (extension_loaded('igbinary'))
            return igbinary_unserialize($data);

        if (extension_loaded('json')) 
            return json_decode($data, TRUE);

        return unserialize($data);
    }

    public function read($file, $lf=FALSE) {
        if (!file_exists($file)) return NULL;
        
        $out=@file_get_contents($file);
        return $lf?preg_replace('/\r\n|\r/',"\n", $out):$out;
    }

    public function write($file,$data,$append=FALSE) {
        return file_put_contents($file,$data,LOCK_EX|($append?FILE_APPEND:0));
    }
}