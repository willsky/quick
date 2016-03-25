<?php
namespace Quick\Core;

abstract class Singleton {

    /**
    *   Return class instance
    *   @return object
    **/
    public static function instance() {
        if (!Registry::exists($class=get_called_class())) {
            $ref=new \Reflectionclass($class);
            $args=func_get_args();
            Registry::set($class,
                $args?$ref->newinstanceargs($args):new $class);
        }

        return Registry::get($class);
    }

}
