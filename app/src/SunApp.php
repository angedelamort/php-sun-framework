<?php 
namespace sunframework;

use HaydenPierce\ClassFinder\ClassFinder;
use Slim\App;
use sunframework\i18n\I18n;
use sunframework\i18n\I18NTwigExtension;
use sunframework\route\IRoutable;
use sunframework\route\IWhitelistable;
use sunframework\twigExtensions\LibraryItem;
use sunframework\user\AuthManager;
use sunframework\user\RoleValidator;
use sunframework\system\SSP;
use sunframework\system\StringUtil;
use sunframework\twigExtensions\CsrfExtension;
use sunframework\twigExtensions\OperatorExtension;
use sunframework\twigExtensions\IncludeExtension;
use sunframework\twigExtensions\SwitchTwigExtension;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use sunframework\user\UserTwigExtension;


class SunApp extends App {
    private $routables = [];
    private $whitelistableRoutes = [];
    private $authManager;

    public $config = [
        'routes.controllers' => null,   // string|array<string>: Override with namespace(s) containing controllers (Must inherit from IRoutable).
        'routes.custom' => null,        // callback: If you just want to make a simple function for registering your routes.
        'view.templates' => '.',        // string|array<string>: directory where the twig templates are located.
        'view.cache' => false,          // bool: set to true to enable the cache
        'view.csrf' => false,           // enable CSRF token. TODO: add doc for token in the form. See Also whitelisting.
        'view.addExtension' => null,    // callback($twig): If you want to register new extension
        'session.cookie_lifetime' => 1209600,   // int: 14 days is the default.
        'i18n.directory' => null,       // string: locale directory. If null, no locale will be set.
        'i18n.default' => 'en-us',      // string: the default locale. You will need a file with this extension
        'i18n.domain' => 'default',     // string: the name of the file that will be used to find the string.
        'debug' => false                // Set to true if you want to debug Slim
    ];

    public function __construct($config, $container = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

//        $this->initDatabase();

        $this->initSession();

        if ($this->config['i18n.directory']) {
            $this->initI18n();
        }

        parent::__construct(array_merge_recursive($container, [
            'settings' => [
                'determineRouteBeforeAppMiddleware' => true,
                'displayErrorDetails' => true
            ]
        ]));

        if ($this->config['view.csrf']) {
            $this->initCsrf();
        }
        $this->initAuthManager();
        $this->initView();
        $this->registerRoutes();
    }

    public function isDebug() {
        return $this->config['debug'];
    }

    public function addLibrary(LibraryItem $item) {
        IncludeExtension::addLibrary($item);
    }

    public function getUser() {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function getUserRole() {
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    }

    private function initI18n() {
        I18n::init($this->config['i18n.directory'], $this->config['i18n.default'], $this->config['i18n.domain']);

        // TODO: set the current language based on the user preferences.
    }

    private function initDatabase() {
        /*$config = $this->config['db'];
        Model::config($config);
        SSP::config($config);*/
    }

    private function initSession() {
        session_start([
            'cookie_lifetime' => $this->config['session.cookie_lifetime'],
            //'read_and_close'  => true, -> TODO : make read-only pages : would need to modify the initialisation flow. Maybe create a session class.
        ]);
    }

    private function initAuthManager() {
        $this->authManager = new AuthManager(new RoleValidator());
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
                if ($request->getMethod() == 'POST') {
                    // TODO: add this mechanism in the config.
                    error_log("it's a post, try to go to the GET with same address\n");
                    return $response->withStatus(302)->withHeader('Location', $request->getUri());
                }

                // if other type of error,
                $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
                $body->write('Failed CSRF check!'); // TODO: add a link? or a better page? or redirect to a page...

                error_log("CSRF error\n");
                return $response->withStatus(400)->withHeader('Content-type', 'text/plain')->withBody($body);
            });

            return $csrf;
        };

        $this->add($this->getContainer()->get('csrf'));
    }

    private function initView() {
        $this->getContainer()['view'] = function ($container) {
            $view = new \Slim\Views\Twig($this->config['view.templates'], [
                'cache' => $this->config['view.cache']
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
            $view->addExtension(new IncludeExtension());
            $view->addExtension(new OperatorExtension());
            $view->addExtension(new UserTwigExtension($this->authManager));

            if ($this->config['view.addExtension']) {
                call_user_func($this->config['view.addExtension'], $view);
            }

            // try to add a style include css to minify.
            // TODO later and probably use mySQL? but not sure it'S a good idea...
            //$view->getEnvironment()->addTokenParser(new \sunframework\system\twigExtensions\IncludeStyle_TokenParser([
            //    'src' => 'path/to/parse/css',
            //    'dst' => 'path/to/export/css'
            //]));

            return $view;
        };
    }

    private function registerRoutes() {
        if ($this->config['routes.controllers'] != null) {
            $classes = $this->get_all_implementors($this->config['routes.controllers']);

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

        if (is_callable($this->config['routes.custom'])) {
            call_user_func($this->config['routes.custom'], $this);
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
        } catch (\Exception $e) {
            die("could not load the controllers\n$e");
        }
    }
}