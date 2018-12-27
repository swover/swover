<?php

namespace Swover\Utils;

/**
 * Across processes config-cache Class
 */
class Cache
{
    private static $config_key = 'swover_cache';

    public static function set($key, $val)
    {
        Config::set(self::$config_key.'.'.$key, $val);
    }

    public static function get($key, $default = '')
    {
        return Config::get(self::$config_key.'.'.$key, $default);
    }

    public static function del($key)
    {
    }

    public static function all()
    {
        return Config::get(self::$config_key);
    }
}
