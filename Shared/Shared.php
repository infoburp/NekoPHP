<?php

namespace NekoPHP\Shared;

/**
 * @author  Patrick Spek <p.spek@tyil.nl>
 * @package NekoPHP
 * @license BSD 3-clause license
 */
class Shared
{
    /**
     * @return array[string => mixed]
     */
    public static function before()
    {
        // setup twig
        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem());
        $twig->getLoader()->addPath(__DIR__.'/Twig');
        $twig->addGlobal('asset', \NekoPHP\Settings::load('settings')->get('asset-url'));
        $twig->addGlobal('base_url', \NekoPHP\NekoPHP::getBaseUrl());

        // add the current user object to twig, if it exists
        $user_session = new \NekoPHP\Session('user');
        $user_id = $user_session->get('id');

        if ($user_id > 0) {
            $user = new \NekoPHP\Modules\User\Models\User($user_id);
            $twig->addGlobal('cuser', $user);
        }

        return [
            'twig' => $twig
        ];
    }
}

