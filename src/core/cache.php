<?php
namespace Quick\Core;

defined('ERROR_CACHE_CONNECT_FAIL') || define('ERROR_CACHE_CONNECT_FAIL', 1100);
defined('ERROR_CACHE_CONFIG_ERROR') || define('ERROR_CACHE_CONFIG_ERROR', 3001);

class Cache
{
    private static $conns = array();
    private static $ttl = 0;
    private static $prefix = 'quick';
    private static $engine = 'file';

    public static function set($key, $value, $name = 'default') {
        if (!App::instance()->get('cache_enable')) 
            return FALSE;
        $client = self::client($key, $name);
        $ndx = self::$prefix . '_' . $key;
        $time = microtime(TRUE);
        // if ($cached = self::exist($key)){
        //     list($time, $ttl) = $cached; 
        // }
        $data = App::instance()->serialize(array($value, $time, self::$ttl)); 

        switch(self::$engine) {
        case 'apc':
            return apc_store($ndx, $data, self::$ttl);
        case 'redis':
            // return $client->set($ndx, $data, array('ex' => self::$ttl));
            if (self::$ttl > 0) {
                return $client->setex($ndx, self::$ttl, $data);
            } else {
                return $client->set($ndx, $data);
            }
            break;
        case 'memcache':
            return memcache_set($client, $ndx, $data, 0, self::$ttl);
            break;
        default:
            return App::instance()->write($client . DS . $ndx, $data);
        }

        return FALSE;
    }

    public static function get($key, $name = 'default', $func = NULL) {
        if (!App::instance()->get('cache_enable')) {
            if (is_callable($func)) {
                return call_user_func($func);
            } else {
                return NULL;
            }
        }

        $data = NULL;
        
        if (!self::exist($key, $name, $data) && is_callable($func)) {
            $data = call_user_func($func);
            
            if (FALSE !== $data) {
                self::set($key, $data, $name);
            }
        }

        return $data;
    }

    public static function delete($key, $name = 'default') {
        if (!App::instance()->get('cache_enable')) 
            return TRUE;

        $client = self::client($key, $name);
        $ndx = self::$prefix . '_' . $key;

        switch(self::$engine) {
        case 'apc':
            return apc_delete($ndx);
        case 'redis':
            return $client->del($ndx);
            break;
        case 'memcache':
            return memcache_delete($client,$ndx);
            break;
        default:
            return @unlink($client . DS . $ndx);
        }

        return TRUE;
    }

    private static function exist($key, $name = 'default', &$val = NULL) {
        if (!App::instance()->get('cache_enable')) 
            return FALSE;
        $quick = App::instance();
        $client = self::client($key, $name);
        $ndx = self::$prefix . '_' . $key;
        $raw = NULL;

        switch(self::$engine) {
            case 'apc':
                $raw = apc_fetch($ndx);
                break;
            case 'redis':
                $raw = $client->get($ndx);
                break;
            case 'memcache':
                $raw=memcache_get($client,$ndx);
                break;
            default:
                $path = $client . DS . $ndx;
                $raw = $quick->read($path);
        }

        if ($raw) {
            list($val, $time, $ttl) = $quick->unserialize($raw);

            if ($ttl === 0 || $time + $ttl > microtime(TRUE)) {
                return array($time,$ttl);
            }

            $val = NULL;
            self::delete($key);
        }
        
        return FALSE;
    }

    private static function client($key = NULL, $name = 'default'){
        if (!App::instance()->get('cache_enable')) 
            return NULL;
        $cache_config = Config::get('cache');
        $shared = isset($cache_config['shared']) ? $cache_config['shared'] : [];
        $nodes = isset($cache_config['nodes']) ? $cache_config['nodes'] : [];
        $rules = isset($shared[$name]) ? $shared[$name] : [];
        unset($cache_config); // 减少内存使用

        // 如果进行了分片处理, 只有有key才能进行分片
        if (!empty($rules) && !is_null($key)) {
            $rules = $shared[$name];

            if (is_numeric($key)) {
                $key = intval($key);
            } else {
                $key = crc32($key);
            }

            $indexs = array_keys($rules);
            sort($indexs, SORT_NUMERIC);
            $modula = max($indexs) + 1;
            $i = $key % $modula;
            $idx = Binarychop::find($indexs, $i);

            if (FALSE === $idx) {
                throw new \Exception(sprintf("The index %d not found in shared configurate %s rules", $i, $name), ERROR_CACHE_CONFIG_ERROR);
            }

            $name = $rules[$idx];
        }

        // 缓存cache连接
        if (isset(self::$conns[$name])) {
            return self::$conns[$name];
        }

        if (isset($nodes[$name])) {
            $node = $nodes[$name];
            $engine = strtolower($node['engine']);
            self::$prefix = $node['prefix'];
            self::$ttl = $node['ttl'];

            switch($engine) {
                case 'apc':
                    self::$engine = 'apc';
                    return NULL;
                    break;
                case 'redis':
                    self::$engine = 'redis';
                    $host = $node['host'];
                    $port = $node['port'];
                    $client = new \Redis();

                    if ($client->connect($host, $port, 2)){
                        self::$conns[$name] = $client;
                    } else {
                        throw new \Exception(sprintf('Redis(%s:%s) server has go away', $host, $port), ERROR_CACHE_CONNECT_FAIL);
                    }
                    break;
                case 'memcache':
                    self::$engine = 'memcache';
                    $host = $node['host'];
                    $port = $node['port'];

                    if ($client = @memcache_connect($host,$port)) {
                        self::$conns[$name] = $client;
                    } else {
                        throw new \Exception(sprintf('Memcache(%s:%s) server has go away', $host, $port), ERROR_CACHE_CONNECT_FAIL);
                    }
                    break;
                default:
                    self::$engine = 'file';
                    $path = isset($node['path']) ? $node['path'] : APP_PATH . DS . 'tmp' . DS .'caches';
                    return $path;
            }

            return self::$conns[$name];
        } else {
            throw new \Exception(sprintf("Cache of %s not found in configurate", $name), 1);
        }

        return NULL;
    }
}