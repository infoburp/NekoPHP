<?php
/**
 * public/index.php
 * @author:  Patrick Spek <tyil@scriptkitties.moe>
 * @license: BSD 3-clause license
 */

namespace NekoPHP;

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
}

try {
    $neko        = new NekoPHP();
    $environment = $neko->prepare();

    echo $neko->run($environment);
} catch(\Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
}

