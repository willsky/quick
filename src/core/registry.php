<?php
namespace Quick\Core;

class Registry 
{
    private static $table = array();
    /**
     *   Return TRUE if object exists in catalog
     *   @return bool
     *   @param $key string
     **/
    public static function exists($key) {
        return isset(self::$table[$key]);
    }

    /**
     *   Add object to catalog
     *   @return object
     *   @param $key string
     *   @param $obj object
     **/
    public static function set($key,$obj) {
        return self::$table[$key] = $obj;
    }

    /**
     *   Retrieve object from catalog
     *   @return object
     *   @param $key string
     **/
    public static function get($key) {
        return self::$table[$key];
    }

    /**
     *   Delete object from catalog
     *   @return NULL
     *   @param $key string
     **/
    public static function clear($key) {
        self::$table[$key] = NULL;
        unset(self::$table[$key]);
    }

    //! Prohibit cloning
    private function __clone() {
    }

    //! Prohibit instantiation
    private function __construct() {
    }
}