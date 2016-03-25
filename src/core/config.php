<?php
namespace Quick\Core;

class Config
{
    private static $tables = array();

    public static function load($file) {
        if (($data = require($file)) && is_array($data)) {
            self::$tables += $data;
        }
    }

    public static function set($key, $value) {
        self::$tables[$key] = $value;
    }

    public static function get($key) {
        return isset(self::$tables[$key]) ? self::$tables[$key] : null;
    }
}