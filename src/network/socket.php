<?php
namespace Quick\Network;
defined('EXCEPTION_SYSTEM_CORE') || define('EXCEPTION_SYSTEM_CORE', 400);

class Socket{
    public static function request() {
        throw new \Exception('socket not implement', EXCEPTION_SYSTEM_CORE);
        return FALSE; 
    }
}

