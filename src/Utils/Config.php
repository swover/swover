<?php

namespace Swover\Utils;

/**
 * Config set & get like Laravel
 */
class Config
{
    private static $config = [];

    /**
     * Initializes the configuration item
     *
     * @param string $path config-file's path
     * @param null $name Filename without ext
     */
    public function init($path, $name = null)
    {
        $path = rtrim($path, '/').'/';
        if (is_null($name)) {
            self::initPath($path);
        } else {
            self::initFile($path, $name);
        }
    }

    private static function initPath($path)
    {
        $dp = dir($path);
        while ($file = $dp ->read()){
            if($file !="." && $file !=".." && is_file($path.$file) && strrchr($file,'.') == '.php'){
                self::initFile($path, substr($file, 0, strlen($file) - 4));
            }
        }
        $dp->close();
    }

    private static function initFile($path, $name)
    {
        $file_name = $path.$name.'.php';
        if (file_exists($file_name)) {
            self::$config[$name] = require $file_name;
        }
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (empty(self::$config)) {
            return $default;
        }

        $array = self::$config;

        if (is_null($key)) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if ( is_array($array) && array_key_exists($segment, $array) ) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function set($key, $value)
    {
        $array = &self::$config;

        //If no key is given to the method, the entire array will be replaced.
        //if (is_null($key)) {
        //    return $array = $value;
        //}

        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}
