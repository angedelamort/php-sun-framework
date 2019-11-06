<?php 
namespace sunframework;

use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use DirectoryIterator;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Slim\App;
use Slim\Views\Twig;
use SplFileInfo;
use sunframework\i18n\I18n;
use sunframework\i18n\I18NTwigExtension;
use sunframework\route\IRoutable;
use sunframework\route\IWhitelistable;
use sunframework\system\Stopwatch;
use sunframework\system\SunLogger;
use sunframework\twigExtensions\DebugBarTwigExtension;
use sunframework\twigExtensions\LibraryItem;
use sunframework\twigExtensions\SunTwigCollector;
use sunframework\user\AuthManager;
use sunframework\system\SSP;
use sunframework\system\StringUtil;
use sunframework\twigExtensions\CsrfExtension;
use sunframework\twigExtensions\OperatorExtension;
use sunframework\twigExtensions\LibraryExtension;
use sunframework\twigExtensions\SwitchTwigExtension;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use sunframework\user\UserTwigExtension;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;


class SunApp extends App {
    private $logger;
    private $debugBar = null;
    private $routables = [];
    private $whitelistableRoutes = [];
    /** @var AuthManager */
    private $authManager = null;
    /** @var SunAppConfig */
    private $config;

    /**
     * SunApp constructor.
     * @param SunAppConfig $config
     * @param array $container
     * @throws Exception
     */
    public function __construct(SunAppConfig $config, $container = [])
    {
        $this->config = $config;


        if ($config->isDebugBarEnabled()) {
            $this->debugBar = new DebugBar();
            $this->debugBar->addCollector(new PhpInfoCollector());
            $this->debugBar->addCollector(new MessagesCollector());
            $this->debugBar->addCollector(new RequestDataCollector());
            $this->debugBar->addCollector(Stopwatch::getCollector());
            $this->debugBar->addCollector(new MemoryCollector());
            $this->debugBar->addCollector(new ExceptionsCollector());
            SunLogger::init($this->debugBar);
        }

        $this->logger = new SunLogger("sun-app");
        $this->logger->message("hello world!");

        if ($config->isDebugBarEnabled()) {
            $this->getContainer()['Logger'] = function () {
                $logger = new SunLogger('slim');
                return $logger;
            };
        }

//        $this->initDatabase();

        $this->initSession();

        if ($this->config->isI18nEnabled()) {
            $this->initI18n();
        }

        parent::__construct(array_merge_recursive($container, [
            'settings' => [
                'determineRouteBeforeAppMiddleware' => true,
                'displayErrorDetails' => true
            ]
        ]));

        if ($this->config->isCsrfEnabled()) {
            $this->initCsrf();
        }
        $this->initAuthManager();
        $this->initView();
        $this->registerRoutes();
    }

    public function isDebug() {
        return $this->config->isDebugEnabled();
    }

    public function run($silent = false) {
        try {
            $sw = (new Stopwatch('run'))->start();
            return parent::run($silent);
        } finally {
            // NOTE: will never be called because it the render will end the measuring. But just for the sake of symmetry.
            $sw->stop();
        }
    }

    public function addLibrary(LibraryItem $item) {
        LibraryExtension::addLibrary($item);
    }

    /**
     * @return AuthManager
     */
    public function getAuthManager() {
        return $this->authManager;
    }

    /**
     * @throws Exception
     */
    private function initI18n() {
        try {
            $sw = (new Stopwatch('i18n-init'))->start();
            I18n::init($this->config->getI18nDirectory(), $this->config->getI18nDefaultLanguage(), $this->config->getI18nDomain());
        } finally {
            $sw->stop();
        }
    }

    private function initDatabase() {
        /*$config = $this->config['db'];
        Model::config($config);
        SSP::config($config);*/
    }

    private function initSession() {
        try {
            $sw = (new Stopwatch('session-init'))->start();
            session_start([
                'cookie_lifetime' => $this->config->getCookieLifetime(),
                //'read_and_close'  => true, -> TODO : make read-only pages : would need to modify the initialisation flow. Maybe create a session class.
            ]);
        } finally {
            $sw->stop();
        }
    }

    private function initAuthManager() {
        try {
            $sw = (new Stopwatch('auth-manager-init'))->start();
            if ($this->config->isSessionEnabled()) {
                $this->authManager = new AuthManager($this->config->getUserSessionInterface());
            }
        } finally {
            $sw->stop();
        }
    }

    private function initCsrf() {
        try {
            $sw = (new Stopwatch('csrf-init'))->start();
            $this->getContainer()['csrf'] = function () {
                $csrf = new \Slim\Csrf\Guard();

                $csrf->setFailureCallable(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {

                    $route = $request->getAttribute('route');
                    $pattern = $route->getPattern();
                    if (count(array_filter($this->whitelistableRoutes, function ($whiteListEntry) use ($pattern) {
                            return StringUtil::startsWith($pattern, $whiteListEntry);
                        })) > 0) {
                        return $next($request, $response);
                    }

                    // if a bad post, just try to reload the page.
                    if ($this->config->isCsrfRedirectPost() && $request->getMethod() == 'POST') {
                        $this->logger->info("it's a post, try to go to the GET with same address");
                        return $response->withStatus(302)->withHeader('Location', $request->getUri());
                    }

                    // if other type of error,
                    $cb = $this->config->getCsrfFailureCallback();
                    if ($cb && is_callable($cb)) {
                        return call_user_func($cb, $request, $response);
                    }

                    // TODO: use twig?
                    $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
                    $body->write('Failed CSRF check!');

                    $this->logger->warn("CSRF error");
                    return $response->withStatus(400)->withHeader('Content-type', 'text/plain')->withBody($body);
                });

                return $csrf;
            };

            $this->add($this->getContainer()->get('csrf'));
        } finally {
            $sw->stop();
        }
    }

    private function initView() {
        try {
            $sw = (new Stopwatch('view-init'))->start();
            if (!$this->config->isTwigEnabled())
                return;

            $this->getContainer()['view'] = function ($container) {
                $options = [];
                if ($this->config->isCacheEnabled()) {
                    $options['cache'] = $this->config->getCacheDirectory();
                }
                $view = new Twig($this->config->getTwigTemplateLocations(), $options);

                if ($this->config->isDebugBarEnabled()) {
                    $profile = new Profile();
                    $view->addExtension(new ProfilerExtension($profile));
                    $this->debugBar->addCollector(new SunTwigCollector($profile));
                }

                // NOTE: probably find a way to extract the twig extensions somewhere else. Not really nice here
                $router = $container->get('router');
                $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
                $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
                $view->addExtension(new SwitchTwigExtension()); //https://github.com/buzzingpixel/twig-switch
                $view->addExtension(new LibraryExtension());
                $view->addExtension(new OperatorExtension());

                if ($this->config->isCsrfEnabled() && isset($this->getContainer()['csrf'])) {
                    $view->addExtension(new CsrfExtension($container->get('csrf')));
                }
                if ($this->config->isI18nEnabled()) {
                    $view->addExtension(new I18NTwigExtension());
                }
                if ($this->authManager) {
                    $view->addExtension(new UserTwigExtension($this->authManager));
                }
                if ($this->config->isDebugBarEnabled()) {
                    $view->addExtension(new DebugBarTwigExtension($this->debugBar, $this->config->getDebugBarSourceDir(), $this->config->getDebugBarBaseUrl()));
                }
                if ($this->config->getTwigNewExtensionCallback()) {
                    call_user_func($this->config->getTwigNewExtensionCallback(), $view);
                }

                return $view;
            };
        } finally {
            $sw->stop();
        }
    }

    private function registerRoutes() {
        try {
            $sw = (new Stopwatch('route-register-init'))->start();
            if ($this->config->getRouteDirectories() != null) {
                $classes = $this->get_all_implementors($this->config->getRouteDirectories());

                foreach($classes as $klass) {
                    $instance = null;
                    if (is_subclass_of($klass, IWhitelistable::class)) {
                        $instance = new $klass();
                        $this->whitelistableRoutes = array_merge($this->whitelistableRoutes, $instance->getWhitelistedRoutes());
                    }
                    if (is_subclass_of($klass, IRoutable::class)) {
                        if ($instance == null)
                            $instance = new $klass();
                        $this->routables[$klass] = $instance;
                        $instance->registerRoute($this);
                    }
                }
            }

            if (is_callable($this->config->getRouteCallback())) {
                call_user_func($this->config->getRouteCallback(), $this);
            }
        } finally {
            $sw->stop();
        }
    }

    /**
     * Returns an array of classes that implement the $interface
     * @param array $items
     * @param bool $recursive
     * @return array list of classes that should be used for routes.
     */
    private function get_all_implementors(array $items, bool $recursive = false) {
        // TODO: cache -> in release, kinda slow. also, in debug, need to know if new controllers
        try {
            $classes = [];
            foreach ($items as $namespace => $directory) {
                if (is_string($namespace)) {
                    //$classes = array_merge($classes, ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE));
                    $classes = array_merge($classes, self::getClasses($directory, $namespace, $recursive));
                }
            }
            return $classes;
        } catch (Exception $e) {
            die("could not load the controllers\n$e");
        }
    }

    public static function getClasses($controllerDir, $namespace, $recursive = false) {
        $files = [];
        if ($recursive) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerDir));
            /** @var SplFileInfo $fileInfo */
            foreach ($rii as $fileInfo) {
                if (!$fileInfo->isDir()) {
                    $files[] = [
                        'basename' => $fileInfo->getBasename('.php'),
                        'path' => $fileInfo->getPath()
                    ];
                }
            }
        } else {
            /** @var SplFileInfo $fileInfo */
            foreach (new DirectoryIterator($controllerDir, ) as $fileInfo) {
                if (!$fileInfo->isDir() && $fileInfo->getExtension() === 'php') {
                    $files[] = [
                        'basename' => $fileInfo->getBasename('.php'),
                        'path' => $fileInfo->getPath()
                    ];
                }
            }
        }

        array_walk($files, function(&$item, $key, $userData) {
            $namespace = substr($item['path'], strlen($userData[1]));
            if (strlen($namespace) > 0) {
                $namespace = $namespace . str_replace('/', '\\', $namespace);
            } else {
                $namespace = $userData[0];
            }
            $item = $namespace . '\\' . $item['basename'];
        }, [$namespace, $controllerDir]);

        return $files;
    }
}