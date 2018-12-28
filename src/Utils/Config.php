<?php

namespace Swover\Utils;

/**
 * Config set & get like Laravel
 */
class Config
{

    private static $config = [];

    /**
     * Loads the configuration file under the path into the application
     *
     * @param  string $path
     * @return void
     */
    public static function loadPath($path)
    {
        $path = rtrim($path, '/') . '/';
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                self::loadFile($path . $file);
            }
        }
    }

    /**
     * Load a configuration file into the application.
     *
     * @param  string $file_name
     * @return bool
     */
    public static function loadFile($file_name)
    {
        if (!is_file($file_name)) return false;

        if (strrchr($file_name, '.') !== '.php') return false;

        self::set(basename($file_name, '.php'), require $file_name);

        return true;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  string $key
     * @param  mixed $default
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
            if (is_array($array) && array_key_exists($segment, $array)) {
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
     * @param  string $key
     * @param  mixed $value
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
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}
