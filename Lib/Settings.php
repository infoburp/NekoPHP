<?php

namespace NekoPHP;

/**
 * @author  Patrick Spek <p.spek@tyil.nl>
 * @package NekoPHP
 * @license BSD 3-clause license
 */
class Settings
{
    /**
     * @var array[string => mixed]
     */
    private $data;

    /**
     * @param string $config
     * @param string $module
     * @return \NekoPHP\Settings
     */
    public static function load($config, $module = null)
    {
        $file = NekoPHP::getRootDir();

        if ($module !== null) {
            $file .= '/Modules/'.$module;
        }

        $file .= '/conf/'.$config.'.php';

        if (!file_exists($file)) {
            throw new \Exception('File not found: '.$file);
        }

        $data = require $file;

        return new self($data);
    }

    /**
     * @param array[string => mixed] $data
     * @return \NekoPHP\Settings
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key];
    }
}

