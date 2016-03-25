<?php
namespace Quick\Controller;

abstract class Factory
{
    public $view = NULL;
    protected $method = 'GET';
    protected $_body = array();
    protected $_query = array();
    protected $_files = array();
    protected $_request = array();
    protected $_env = array();
    protected $_cookie = array();
    protected $_session = array();
    protected $trustProxy = FALSE;
    private static $_models = array();
    public $autoRender = TRUE;

    protected static $_detectors = [
        'get' => ['env' => 'REQUEST_METHOD', 'value' => 'GET'],
        'post' => ['env' => 'REQUEST_METHOD', 'value' => 'POST'],
        'put' => ['env' => 'REQUEST_METHOD', 'value' => 'PUT'],
        'patch' => ['env' => 'REQUEST_METHOD', 'value' => 'PATCH'],
        'delete' => ['env' => 'REQUEST_METHOD', 'value' => 'DELETE'],
        'head' => ['env' => 'REQUEST_METHOD', 'value' => 'HEAD'],
        'options' => ['env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'],
        'ssl' => ['env' => 'HTTPS', 'options' => [1, 'on']],
        'ajax' => ['env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'],
        'flash' => ['env' => 'HTTP_USER_AGENT', 'pattern' => '/^(Shockwave|Adobe) Flash/'],
        'json' => ['accept' => ['application/json'], 'param' => '_ext', 'value' => 'json'],
        'xml' => ['accept' => ['application/xml', 'text/xml'], 'param' => '_ext', 'value' => 'xml'],
    ];

    protected static $_detectorCache = array();

    public function __construct() {
        $this->_query = $_GET;
        $this->_body = $_POST;
        $this->_files = $_FILES;
        $this->_request = $_REQUEST;
        $this->_env = $_SERVER;
        $this->_cookie = $_COOKIE;
        $this->_session = $_SESSION;
        $this->initView();
        $this->init();
    }

    protected function init() {}

    protected function is($type = 'GET') {
        $type = strtolower($type);
        if (!isset(static::$_detectors[$type])) {
            return false;
        }

        if (!isset(static::$_detectorCache[$type])) {
            static::$_detectorCache[$type] = $this->_is($type);
        }

        return static::$_detectorCache[$type];
    }

    private function _is($type)
    {
        $detect = static::$_detectors[$type];
        // if (is_callable($detect)) {
        //     return call_user_func($detect, $this);
        // }
        if (isset($detect['env']) && $this->_environmentDetector($detect)) {
            return true;
        }

        if (isset($detect['accept']) && $this->_acceptHeaderDetector($detect)) {
            return true;
        }

        return false;
    }

    private function _environmentDetector($detect)
    {
        if (isset($detect['env'])) {
            if (isset($detect['value'])) {
                return $this->env($detect['env']) == $detect['value'];
            }
            if (isset($detect['pattern'])) {
                return (bool)preg_match($detect['pattern'], $this->env($detect['env']));
            }
            if (isset($detect['options'])) {
                $pattern = '/' . implode('|', $detect['options']) . '/i';
                return (bool)preg_match($pattern, $this->env($detect['env']));
            }
        }
        return false;
    }

    private function _acceptHeaderDetector($detect)
    {
        $acceptHeaders = explode(',', $this->env('HTTP_ACCEPT'));
        foreach ($detect['accept'] as $header) {
            if (in_array($header, $acceptHeaders)) {
                return true;
            }
        }
        return false;
    }

    protected function clientIp()
    {
        if ($this->trustProxy && $this->env('HTTP_X_FORWARDED_FOR')) {
            $ipaddr = preg_replace('/(?:,.*)/', '', $this->env('HTTP_X_FORWARDED_FOR'));
        } else {
            if ($this->env('HTTP_CLIENT_IP')) {
                $ipaddr = $this->env('HTTP_CLIENT_IP');
            } else {
                $ipaddr = $this->env('REMOTE_ADDR');
            }
        }

        if ($this->env('HTTP_CLIENTADDRESS')) {
            $tmpipaddr = $this->env('HTTP_CLIENTADDRESS');

            if (!empty($tmpipaddr)) {
                $ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
            }
        }
        return trim($ipaddr);
    }

    // GET参数
    protected function query($field, $default = NULL) {
        return isset($this->_query[$field]) ? $this->_query[$field] : $default;
    }
    // Post参数
    protected function body($field, $default = NULL) {
        return isset($this->_body[$field]) ? $this->_body[$field] : $default;
    }

    protected function file($field, $default = NULL) {
        return isset($this->_files[$field]) ? $this->_files[$field] : $default;
    }

    protected function request($field, $default = NULL) {
        return isset($this->_request[$field]) ? $this->_request[$field] : $default;
    }

    protected function env($field, $default = NULL) {
        return isset($this->_env[$field]) ? $this->_env[$field] : (getenv($field)? : $default);
    }

    protected function cookie($field, $default = NULL) {
        return isset($this->_cookie[$field]) ? $this->_cookie[$field] : $default;
    }

    protected function session($field, $default = NULL) {
        return isset($this->_session[$field]) ? $this->_session[$field] : $default;
    }

    protected function render($tpl = NULL){
        $this->view && $this->view->render($tpl);
    }

    abstract protected function initView();

    protected function model($name) {
        if (isset(self::$_models[$name])){
            return self::$_models[$name];
        }

        $class = '\\Models\\' . $name;
        $ref = new \Reflectionclass($class);
        $args = array_slice(func_get_args(), 1);
        self::$_models[$name] = $args ? $ref->newinstanceargs($args) : new $class();
        return self::$_models[$name];
    }

    public function __get($name) {
        return $this->model($name);
    }
}
