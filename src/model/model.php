<?php
namespace Quick\Model;
use Quick\Core\Binarychop;
use Quick\Core\Config;

defined('ERROR_MYSQL_CONFIG_ERROR') || define('ERROR_MYSQL_CONFIG_ERROR', 3000);

class Model
{
    private static $_conns = array();
    
    protected static $field_type_maps = [];
    protected static $select_fields = [];
    
    protected static $table_name = 'test';
    
    protected static function getTable($shareId = NULL, $name = 'default', $tableName = NULL) {
        if (is_null($tableName)) {
            $tableName = static::$table_name;
        }
        
        if (is_null($shareId)) {
            return $tableName;
        }
        
        $tableNumber = self::getSharedTableNumber($name);
        
        if ($tableNumber < 2) {
            return $tableName;
        }
        
        if (is_numeric($shareId)) {
            $shareId = intval($shareId);
        } else {
                $shareId = crc32($shareId);
        }

        $id = ( $shareId / 10) % $modula;
        return $tableName . "_" . $id;
    }
    
    private static function getShared($name = 'default') {
        $config = Config::get('database');
        $shared = isset($config['shared']) ? $config['shared'] : [];
        $shared = isset($shared[$name]) ? $shared[$name] : FALSE;
        return (is_array($shared) && !empty($shared)) ? $shared : FALSE;
    }
    
    private static function getSharedRules($name = 'default') {
        $shared = self::getShared($name);
        
        if (!$shared) {
            return FALSE;
        }
        
        $rules = isset($shared['rules']) ? $shared['rules'] : FALSE;
        return (is_array($rules) && !empty($rules)) ? $rules : FALSE;
    }
    
    private static function getSharedTableNumber($name = 'default') {
        $shared = self::getShared($name);
        
        if (!$shared) {
            return 1;
        }
        
        $modula = isset($shared['table_number']) ? intval($shared['table_number']) : 1;
        
        if ($modula < 1) {
            $modula = 1;    
        }
        
        return $modula;
    }
    
    private static function getNodes() {
        $config = Config::get('database');
        return isset($config['nodes']) ? $config['nodes'] : array();
    }
    
    private static function getDbConfName($name = 'default', $sharedId = NULL) {
        $rules = self::getSharedRules($name);
        
        if ($rules && !is_null($sharedId)) {
            if (is_numeric($sharedId)) {
                $sharedId = intval($sharedId);
            } else {
                $sharedId = crc32($sharedId);
            }

            $indexs = array_keys($rules);
            sort($indexs, SORT_NUMERIC);
            $modula = max($indexs) + 1;
            $i = $sharedId % $modula;
            $idx = Binarychop::find($indexs, $i);

            if (FALSE === $idx) {
                throw new \Exception(sprintf("The index %d not found in shared database configuration %s rules", $i, $name), ERROR_MYSQL_CONFIG_ERROR);
            }

            $name = $rules[$idx];
        }
        
        return $name;
    }
    
    protected static function connect($name = 'default', $sharedId = NULL) {
        $name = self::getDbConfName($name, $sharedId);
        $nodes = self::getNodes();

        if (isset(self::$_conns[$name])) {
            return self::$_conns[$name];
        }

        if (isset($nodes[$name])) {
            self::$_conns[$name] = new Medoo($nodes[$name]);
        } else {
            throw new \Exception(sprintf('Database of %s not found in configurate', $name), ERROR_MYSQL_CONFIG_ERROR);
        }

        return self::$_conns[$name];
    }
    
    
    protected function getConnectionGroup($name, $shareIds = NULL, $tableName = NULL) {
        $conns = [];
        
        if (is_null($tableName)) {
            $tableName = static::$table_name;
        }
        
        if ($shareIds) {
            if (is_array($shareIds)) {
                foreach($shareIds as $shareId) {
                    $conn = self::getDbConfName($name, $shareId);
                    $table = self::getTable($shareId, $name, $tableName);
                    $conns[$conn]['connect'] = self::connect($name, $shareId);
                    $conns[$conn]['table'][$table][] = $shareId;
                }
            } else {
                $conn = self::getDbConfName($name, $shareIds);
                $table = self::getTable($shareIds, $name, $tableName);
                $conns[$conn]['table'][$table][] = $shareIds;
                $conns[$conn]['connect'] = self::connect($name, $shareId);
            }
        }
        
        return $conns;
    }
    
    /**
    *  生成URL query STRING，这里要
    **/
    private function http_build_query($query) {
        $uri = [];
        foreach($query as $k => $v) {
            array_push($uri, $k . '='. $v);
        }

        return implode('&', $uri);
    }
    
    protected function formatRow($data, $rows = FALSE) {
        if ($rows) {
            foreach($data as $i => $row) {
                $data[$i] = $this->formatRow($row);
            }
        } else {
            foreach (static::$select_fields as $field => $alias) {
                if (!isset($data[$alias])) {
                    continue;
                }

                if (is_int($field)) {
                    $field = $alias;
                }

                $type = isset(static::$field_type_maps[$field]) ? strtolower(static::$field_type_maps[$field]) : 'str';

                switch($type) {
                    case 'int':
                    $data[$alias] = intval($data[$alias]);
                    break;
                    case 'float':
                    $data[$alias] = floatval($data[$alias]);
                    break;
                    case 'map':
                    $query = [
                        'center' => $data[$alias],
                        'markers' => $data[$alias],
                        'width' => MAP_WIDHT,
                        'height' => MAP_HEIGHT,
                        'zoom' => MAP_ZOOM,
                        'markerStyles' => MAP_MARKER_STYLE
                    ];
                    $data[$alias] = MAP_URL . '?' . $this->http_build_query($query);
                    break;
                    case 'geo':
                    $query = [
                    'center' => $data[$alias],
                    'markers' => $data[$alias],
                    'width' => MAP_WIDHT,
                    'height' => MAP_HEIGHT,
                    'zoom' => MAP_ZOOM,
                    'markerStyles' => MAP_MARKER_STYLE
                    ];
                    $location = array_map('floatval', explode(',', $data[$alias]));
                    $location = ['longitude' => $location[0], 'latitude' => $location[1]];
                    $data[$alias] = [
                        'map_url' => MAP_URL . '?' . $this->http_build_query($query),
                        'location' => $location
                    ];
                    break;
                    case 'json':
                    $data[$alias] = json_decode($data[$alias], TRUE);
                    break;
                    case 'bool':
                    $data[$alias] = (bool)$data[$alias];
                    break;
                    default:
                    break;
                }
            }
        }

        return $data;
    }
}
