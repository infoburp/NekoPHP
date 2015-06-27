<?php

namespace NekoPHP\Traits;

trait ZendDb
{
    public static function createZendDb($params = null)
    {
        if ($params == null) {
            $params = \NekoPHP\Settings::load('database')->get();
        }

        $adapter = new \Zend\Db\Adapter\Adapter($params);
        return $adapter;
    }
}

