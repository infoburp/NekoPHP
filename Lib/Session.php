<?php

namespace NekoPHP;

/**
 * @author  Patrick Spek <p.spek@tyil.nl>
 * @package NekoPHP
 * @license BSD 3-clause license
 */
class Session
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @param string $namespace
     * @return \NekoPHP\Session
     */
    public function __construct($namespace = 'default')
    {
        $this->namespace = $namespace;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (isset($_SESSION[$this->namespace][$key])) {
            return $_SESSION[$this->namespace][$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $_SESSION[$this->namespace][$key] = $value;
    }
}

