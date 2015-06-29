<?php
/**
 * lib/neko.php
 * @author  Patrick Spek <pspek@tyil.nl>
 * @package NekoPHP
 * @license BSD 3-clause license
 */

namespace NekoPHP;

/**
 * NekoPHP
 */
class NekoPHP
{
    /**
     * @var string
     */
    private static $url_module;

    /**
     * @var callable
     */
    private $exception_handler;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $module;

    /**
     * @var string
     */
    private $module_dir;

    /**
     * @var string
     */
    private $page;

    /**
     * @var array[string]
     */
    private $parts;

    /**
     * @var string
     */
    private $router;

    /**
     * @return string
     */
    public static function getBaseUrl()
    {
        return '//'.$_SERVER['HTTP_HOST'];
    }

    /**
     * @return string
     */
    public static function getCurrentUrl()
    {
        return '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    /**
     * @return string
     */
    public static function getModuleUrl()
    {
        return '//'.$_SERVER['HTTP_HOST'].self::$url_module;
    }

    /**
     * @return string
     */
    public static function getRootDir()
    {
        return dirname(__DIR__);
    }

    /**
     * @return void
     */
    public function __construct()
    {
        $this->router = require self::getRootDir().'/conf/router.php';
    }

    /**
     * @return string
     */
    public function getModuleDir()
    {
        return $this->module_dir;
    }

    /**
     * @param callable $handler
     * @return void
     */
    public function setExceptionHandler($handler)
    {
        $this->exception_handler = $handler;
    }

    /**
     * @return void
     */
    public function prepare()
    {
        if ($_SERVER['REQUEST_URI'] == '/') {
            $parts            = [];
            self::$url_module = '/';
        } else {
            // break the URL into parts
            $parts            = explode('/', substr($_SERVER['REQUEST_URI'], 1));
            self::$url_module = '/'.strtolower(array_shift($parts));

            if (count($parts) > 0) {
                $page = strtolower(array_shift($parts));
            }
        }

        if (!isset($this->router[self::$url_module])) {
            if (!isset($this->router['404'])) {
                throw new \Exception('No module defined for '.self::$url_module.'. Additionally, no 404 route was defined.');
            } else {
                self::$url_module = '404';
                $parts            = [];
                $page             = '404';
            }
        }

        if (!isset($page) || $page === '') {
            $page = strtolower($this->router[self::$url_module]);
        }

        $module           = $this->router[self::$url_module];
        $this->module_dir = $this->getRootDir().'/Modules/'.$module;

        $env = [
            'method'     => strtolower($_SERVER['REQUEST_METHOD']),
            'module'     => $module,
            'page'       => $page,
            'parts'      => $parts
        ];

        return $env;
    }

    /**
     * @param string $message
     * @return void
     */
    private function renderException(\Exception $exception)
    {
        if (isset($this->exception_handler)) {
            return $this->{exception_handler}($exception);
        }

        return $exception->getMessage();
    }

    /**
     * @return string
     */
    public function run(array $env)
    {
        $page_path = $this->getModuleDir().'/Pages/'.$env['method'].'-'.$env['page'].'.php';
        $module    = 'NekoPHP\Modules\\'.$env['module'].'\\Module';
        $page      = 'NekoPHP\Modules\\'.$env['module'].'\\Page';
        $data      = [];

        array_unshift($env['parts'], '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

        try {
            if (!file_exists($page_path)) {
                throw new \Exception('File not found: '.$page_path);
            }

            require $page_path;

            // run before hook if it exists
            if (file_exists(self::getRootDir().'/Shared/Shared.php')) {
                require self::getRootDir().'/Shared/Shared.php';

                if (method_exists('\NekoPHP\Shared\Shared', 'before')) {
                    $data = \NekoPHP\Shared\Shared::before();
                }
            }

            // run the module initializer if it exists
            if (file_exists($this->getModuleDir().'/Module.php')) {
                require $this->getModuleDir().'/Module.php';
                $data = $module::module($data);
            }

            if (!class_exists($page)) {
                throw new \Exception('Class not found: '.$page);
            }

            if (!method_exists($page, 'main')) {
                throw new \Exception('Method not found: '.$page.'::main()');
            }

            return $page::main($env['parts'], $data);
        } catch (\Exception $e) {
            return $this->renderException($e);
        }
    }
}

