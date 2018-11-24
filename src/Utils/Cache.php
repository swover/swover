<?php

namespace Swover\Utils;

/**
 * Across processes cache Class, based on swoole_table
 */
class Cache
{
    private static $instance = null;

    private function __construct()
    {
        self::$instance = new \swoole_table(2);
        self::$instance->column('cache', \swoole_table::TYPE_STRING, 1000);
        self::$instance->create();
    }

    public static function getInstance()
    {
        if (self::$instance) {
            return self::$instance;
        } else {
            new self();
            return self::$instance;
        }
    }

    public static function set($key, $val)
    {
        self::getInstance()->set($key, ['cache' => $val]);
    }

    public static function get($key, $default = '')
    {
        $cache = self::getInstance()->get($key);
        if ($cache === false) {
            return $default;
        }
        return $cache['cache'];
    }

    public static function del($key)
    {
        return self::getInstance()->del($key);
    }

    public static function all()
    {
        $data = [];
        foreach (self::getInstance() as $key=>$value) {
            $data[$key] = $value;
        }
        return $data;
    }
}
