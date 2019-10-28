<?php 
namespace sunframework;

use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Monolog\Logger;
use Slim\App;
use sunframework\i18n\I18n;
use sunframework\i18n\I18NTwigExtension;
use sunframework\route\IRoutable;
use sunframework\route\IWhitelistable;
use sunframework\twigExtensions\LibraryItem;
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


class SunApp extends App {
    private $logger;
    private $routables = [];
    private $whitelistableRoutes = [];
    /** @var AuthManager */
    private $authManager;
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
        $this->logger = new Logger("sun-app");
        $this->config = $config;

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
        I18n::init($this->config->getI18nDirectory(), $this->config->getI18nDefaultLanguage(), $this->config->getI18nDomain());
    }

    private function initDatabase() {
        /*$config = $this->config['db'];
        Model::config($config);
        SSP::config($config);*/
    }

    private function initSession() {
        session_start([
            'cookie_lifetime' => $this->config->getCookieLifetime(),
            //'read_and_close'  => true, -> TODO : make read-only pages : would need to modify the initialisation flow. Maybe create a session class.
        ]);
    }

    private function initAuthManager() {
        if ($this->config->isSessionEnabled()) {
            $this->authManager = new AuthManager($this->config->getUserSessionInterface());
        }
    }

    private function initCsrf() {
        $this->getContainer()['csrf'] = function () {
            $csrf = new \Slim\Csrf\Guard();

            $csrf->setFailureCallable(function(ServerRequestInterface $request, ResponseInterface $response, callable $next) {

                $route = $request->getAttribute('route');
                $pattern = $route->getPattern();
                if (count(array_filter($this->whitelistableRoutes, function($whiteListEntry) use ($pattern) {
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
    }

    private function initView() {
        $this->getContainer()['view'] = function ($container) {
            $view = new \Slim\Views\Twig($this->config->getTwigTemplateLocations(), [
                'cache' => $this->config->getCacheDirectory()
            ]);

            // NOTE: probably find a way to extract the twig extensions somewhere else. Not really nice here
            $router = $container->get('router');
            $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
            $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

            if (isset($this->getContainer()['csrf'])) {
                $view->addExtension(new CsrfExtension($container->get('csrf')));
            }

            $view->addExtension(new SwitchTwigExtension()); //https://github.com/buzzingpixel/twig-switch
            $view->addExtension(new I18NTwigExtension());
            $view->addExtension(new LibraryExtension());
            $view->addExtension(new OperatorExtension());
            $view->addExtension(new UserTwigExtension($this->authManager));

            if ($this->config->getTwigNewExtensionCallback()) {
                call_user_func($this->config->getTwigNewExtensionCallback(), $view);
            }

            return $view;
        };
    }

    private function registerRoutes() {
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
    }

    /**
     * Returns an array of classes that implement the $interface
     * @param $namespace string|array where lies the controllers.
     * @return array list of classes that should be used for routes.
     */
    private function get_all_implementors($namespace) {
        try {
            $classes = [];
            if (is_string($namespace)) {
                $classes = ClassFinder::getClassesInNamespace($namespace, ClassFinder::RECURSIVE_MODE);
            } else if (is_array($namespace)) {
                foreach ($namespace as $n) {
                    if (is_string($n)) {
                        $classes = array_merge($classes, ClassFinder::getClassesInNamespace($n, ClassFinder::RECURSIVE_MODE));
                    }
                }
            }
            return $classes;
        } catch (Exception $e) {
            die("could not load the controllers\n$e");
        }
    }
}