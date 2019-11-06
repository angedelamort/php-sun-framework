<?php
namespace sunframework;

use http\Exception\InvalidArgumentException;
use sunframework\user\IUserSessionInterface;
use sunframework\user\UserSession;

class SunAppConfig {
    /** @var bool */
    private $routeEnabled = false;
    /** @var array|null */
    private $routeDirectories;
    /** @var callable|null */
    private $routeCallback;
    /** @var bool */
    private $cacheEnabled = false;
    /**@var bool */
    private $twigEnabled = false;
    /**@var string */
    private $cacheDirectory = "";
    /** @var array|string|null */
    private $twigTemplateLocations;
    /** @var bool */
    private $csrfEnabled = false;
    /** @var bool */
    private $csrfRedirectPost;
    /** @var callable|null */
    private $csrfFailureCallback;
    /** @var callable|null */
    private $twigNewExtensionCallback;
    /** @var bool */
    private $i18nEnabled = false;
    /** @var string */
    private $i18nDirectory;
    /** @var string <*/
    private $i18nDomain;
    /** @var string */
    private $i18nDefaultLanguage;
    /** @var int 14 days is the default. */
    private $cookieLifetime = self::DEFAULT_COOKIE_LIFETIME;
    /** @var bool */
    private $debugEnabled = false;
    /** @var bool */
    private $sessionEnabled = false;
    /** @var IUserSessionInterface */
    private $userSessionInterface;
    /** @var bool */
    private $isDebugBarEnabled = false;
    /** @var string */
    private $debugBarSourceDir;
    /** @var string */
    private $debugBarBaseUrl;

    private const DEFAULT_COOKIE_LIFETIME = 1209600;

    /**
     * Enable the route mechanism.
     * A route is defined by [namespace => source]
     * @param $directories array|null Controller(s) Must inherit from IRoutable.
     * @param $callback callable|null Can overwrite the default mechanism if you don't want to use controllers.
     * @return SunAppConfig
     */
    public function activateRoutes(array $directories, $callback = null) {
        $this->routeEnabled = true;
        $this->routeDirectories = $directories;

        if ($callback){
            if (is_callable($callback)) {
                $this->routeCallback = $callback;
            } else {
                throw new InvalidArgumentException("callback");
            }
        }

        return $this;
    }

    /**
     * @param $templateLocations string|array directory where the twig templates are located
     * @param callable $onNewExtension When the app will add new extension, will call this callback.
     * @return SunAppConfig
     */
    public function activateTwig($templateLocations, callable $onNewExtension = null) {
        $this->twigEnabled = true;
        if (is_string($templateLocations)) {
            $this->twigTemplateLocations = [$templateLocations];
        } else if (is_array($templateLocations)) {
            $this->twigTemplateLocations = $templateLocations;
        } else {
            throw new InvalidArgumentException("templateLocations");
        }

        if ($onNewExtension){
            if (is_callable($onNewExtension)) {
                $this->twigNewExtensionCallback = $onNewExtension;
            } else {
                throw new InvalidArgumentException("onNewExtension");
            }
        }
        return $this;
    }

    /**
     * Application will check that the CSRF token is passed when not doing a POST operation.
     * @param bool $redirectPost if true, will redirect on the GET with same route instead of failure.
     * @param callable|null $failureCallback Override with your own personal failure mechanism.
     * @return SunAppConfig
     */
    public function activateCsrfToken(bool $redirectPost = true, callable $failureCallback = null) {
        $this->csrfEnabled = true;
        $this->csrfRedirectPost = $redirectPost;
        $this->csrfFailureCallback = $failureCallback;
        return $this;
    }

    /**
     * Enable the cache and also for all subsystem that can use it.
     * @param string $cacheDirectory
     * @return SunAppConfig
     */
    public function activateCache(string $cacheDirectory) {
        $this->cacheEnabled = true;
        $this->cacheDirectory = $cacheDirectory;
        return $this;
    }

    /**
     * By default, it won't be activated, so follow the documentation and create your locale files.
     * This module is well integrated with Twig and Cache.
     * @param string $directory locale directory. If null, no locale will be set.
     * @param string $domain the name of the file that will be used to find the string.
     * @param string $defaultLanguage the default locale. You will need a file with this extension: en-US, fr-CA, etc
     * @return SunAppConfig
     */
    public function activateI18n(string $directory, string $domain, string $defaultLanguage) {
        $this->i18nEnabled = true;
        $this->i18nDirectory = $directory;
        $this->i18nDomain = $domain;
        $this->i18nDefaultLanguage = $defaultLanguage;
        return $this;
    }

    /**
     * @param int $lifetimeSec
     * @return SunAppConfig
     */
    public function setCookieLifetime(int $lifetimeSec = self::DEFAULT_COOKIE_LIFETIME) {
        $this->cookieLifetime = $lifetimeSec;
        return $this;
    }

    /**
     * @param bool $isDebug
     * @return SunAppConfig
     */
    public function setDebugMode(bool $isDebug = true) {
        $this->debugEnabled = $isDebug;
        return $this;
    }

    /**
     * @param IUserSessionInterface $userSessionInterface The implementation you want to use. By default the sunframework\user\UserSession will be used.
     * @return SunAppConfig
     */
    public function activateSession(IUserSessionInterface $userSessionInterface = null) {
        $this->sessionEnabled = true;
        if ($userSessionInterface) {
            $this->userSessionInterface = $userSessionInterface;
        } else {
            $this->userSessionInterface = new UserSession();
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isRouteEnabled(): bool
    {
        return $this->routeEnabled;
    }

    /**
     * @return array|string|null
     */
    public function getRouteDirectories()
    {
        return $this->routeDirectories;
    }

    /**
     * @return callable|null
     */
    public function getRouteCallback(): ?callable
    {
        return $this->routeCallback;
    }

    /**
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * @return bool
     */
    public function isTwigEnabled(): bool
    {
        return $this->twigEnabled;
    }

    /**
     * @return array|string|null
     */
    public function getTwigTemplateLocations()
    {
        return $this->twigTemplateLocations;
    }

    /**
     * @return bool
     */
    public function isCsrfEnabled(): bool
    {
        return $this->csrfEnabled;
    }

    /**
     * @return bool
     */
    public function isCsrfRedirectPost(): bool
    {
        return $this->csrfRedirectPost;
    }

    /**
     * @return callable|null
     */
    public function getCsrfFailureCallback(): ?callable
    {
        return $this->csrfFailureCallback;
    }

    /**
     * @return callable|null
     */
    public function getTwigNewExtensionCallback(): ?callable
    {
        return $this->twigNewExtensionCallback;
    }

    /**
     * @return bool
     */
    public function isI18nEnabled(): bool
    {
        return $this->i18nEnabled;
    }

    /**
     * @return string
     */
    public function getI18nDirectory(): string
    {
        return $this->i18nDirectory;
    }

    /**
     * @return string
     */
    public function getI18nDomain(): string
    {
        return $this->i18nDomain;
    }

    /**
     * @return string
     */
    public function getI18nDefaultLanguage(): string
    {
        return $this->i18nDefaultLanguage;
    }

    /**
     * @return int
     */
    public function getCookieLifetime(): int
    {
        return $this->cookieLifetime;
    }

    /**
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->debugEnabled;
    }

    /**
     * @return bool
     */
    public function isSessionEnabled(): bool
    {
        return $this->sessionEnabled;
    }

    /**
     * @return IUserSessionInterface
     */
    public function getUserSessionInterface(): IUserSessionInterface
    {
        return $this->userSessionInterface;
    }

    /**
     * @return string
     */
    public function getCacheDirectory(): string
    {
        return $this->cacheDirectory;
    }

    /**
     * @return bool
     */
    public function isDebugBarEnabled(): bool
    {
        return $this->isDebugBarEnabled;
    }

    /**
     * @return string
     */
    public function getDebugBarBaseUrl(): string
    {
        return $this->debugBarBaseUrl;
    }

    /**
     * @return string
     */
    public function getDebugBarSourceDir(): string
    {
        return $this->debugBarSourceDir;
    }

    /**
     * @param string $debugBarSourceDir
     * @param string $debugBarBaseUrl
     * @return SunAppConfig
     * @note You will need a compatible jQuery version
     */
    public function activateDebugBar(string $debugBarSourceDir, string $debugBarBaseUrl)
    {
        $this->isDebugBarEnabled = true;
        $this->debugBarSourceDir = $debugBarSourceDir;
        $this->debugBarBaseUrl = $debugBarBaseUrl;
        return $this;
    }
}