<?php
namespace Quick\Network;
use \Quick\Core\Singleton;
use \Quick\Core\Config;

defined('ERROR_MISS_DEPENDENCE') || define('ERROR_MISS_DEPENDENCE', 201);
defined('EXCEPTION_SYSTEM_CORE') || define('EXCEPTION_SYSTEM_CORE', 400);

class Http extends Singleton
{
    const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36';

    private static $timeout;
    private static $engine;

    public function __construct(){
        $httpConfig = Config::get('http');
        
        if (!$httpConfig){
            $httpConfig = [];
        }
        
        self::$timeout = isset($httpConfig['timeout']) ? abs(intval($httpConfig['timeout'])) : 10;
        
        if (self::$timeout < 1) {
            self::$timeout = 10;
        }
        
        self::$engine = isset($httpConfig['engine']) ? trim($httpConfig['engine']) : 'curl';
    }

    public function engine($name = null) {

        if ( !$name ) {
            $name = self::$engine;
        }

        if ( !$name ) {
            $name = 'curl';
        }

        $name = strtolower($name);

        $flags = array(
            'curl' => extension_loaded('curl'),
            'socket' => function_exists('fsockopen')
        );

        if ( isset($flags[$name]) && $flags[$name] ) {
            return $name;
        }

        foreach($flags as $_name => $value ) {
            if ($value) {
                return $_name;
            }
        }

        return false;
    }

    public function setTimeout($timeout) {
        $timeout = intval($timeout);

        if ( $timeout > 0 ) {
            self::$timeout = $timeout;
        }
    }

    public function __call($func, $args) {
        $func = strtoupper($func);
        $methods = array('GET', 'POST', 'PUT', 'DELETE');

        if ( !in_array($func, $methods) ) {
            throw new \Exception(sprintf('Method %s not defined in class \Quick\Network\Http', $func), EXCEPTION_SYSTEM_CORE);
        }

        if ( count($args) < 1) {
            throw new \Exception(sprintf('Miss parameter of url method %s in class \Quick\Network\Http', $func), EXCEPTION_SYSTEM_CORE);
        }
        $url = '';
        $headers = array();
        $fields = array();

        switch(count($args)) {
        case 1:
            $url = $args[0];
            break;
        case 2:
            list($url, $fields) = $args;
            break;
        default:
            list($url, $fields, $headers) = $args;
        }

        if ( !isset($headers['User-Agent']) ) {
            $headers['User-Agent'] = self::USER_AGENT;
        }
        
        if ( !isset($headers['Expect']) ) {
            $headers['Expect'] = '';
        }

        $options = array(
            'method' => $func, 
            'headers' => $headers, 
            'timeout' => self::$timeout,
            'follow_location' => TRUE,
            'max_redirects' => 10
        );

        if ( !empty($fields) ) {
            $options['content'] = $fields;
        }

        $engine = $this->engine();

        if ( !$engine ) {
            throw new \Exception('lib http driver is not installed', ERROR_MISS_DEPENDENCE);
        }

        $response = FALSE;
        
        switch($engine) {
        case 'curl':
            $response = Curl::request($url, $options);
            break;
        default:
            $response = Socket::request($url, $options);
        }
        
        return $response;
    }
}
