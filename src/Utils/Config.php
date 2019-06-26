<?php

namespace Swover\Utils;

/**
 * Config
 */
class Config extends ArrayObject
{
    /**
     * Config constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($this->prepare($config));
    }

    private function prepare(array $config)
    {
        if (isset($config['setting']) && is_array($config['setting'])) {
            foreach ($config['setting'] as $key => $item) {
                if ($key == 'setting') continue;
                if (isset($config[$key])) {
                    $config[$key] = $item;
                    unset($config['setting'][$key]);
                }
            }
        }

        return $config;
    }

    public function get($name, $default = null)
    {
        return isset($this->$name) ? $this->$name : (isset($this['setting'][$name]) ? $this['setting'][$name] : $default);
    }
}