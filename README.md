# PHP Sun Framework
another simple PHP framework.

## Features
* routing - Slim
* templating - Twig
* i18n (internationalization) - custom
* Simple User Management - custom
* CSRF Token - twig
* mySql - simple ORM (maybe remove?)
* server-side tables integration - DataTables.net (remove?)

## Installing

````
composer require "angedelamort/php-sun-framework"
````

## Using
Quick start without using too much configuration.
````
--- index.php ---
<?php
use sunframework\SunApp;

require('autoload.php');

$app = new SunApp([
    'routes.custom' => function(Slim\App $app) {
        $app->get('/', function() {
            return "Hello World";
        });
    }
]);
$app->run()

````

This is simple, but  it doesn't scale well if you put too much code in the index.php.
Since you are using composer, you should use the "psr-4" mechanism combined with 
the one from this framework.  I would suggest to take a quick look at the sample.

````
--- public/index.php ---
<?php

use sunframework\SunApp;

require "../../vendor/autoload.php";

$app = new SunApp([
    'i18n.directory' => dirname(__DIR__) . '/locale',
    'view.templates' => dirname(__DIR__) . '/templates',
    'routes.controllers' => 'sample\controllers'
]);
$app->run();
````
````
--- app/controllers/HomeController.php ---
namespace sample\controllers;

use sunframework\route\IRoutable;

class HomeController implements IRoutable {
    public function registerRoute($app) {
        $app->get('/', function($request, $response, $args) {
            return $this->view->render($response, 'test.twig', [
                'user' => 'John Doe'
            ]);
        });
    }
}
````
````
--- templates/test.twig---
<!DOCTYPE html>
<html>
<body>
<h1>{{ i18n('appName') }}</h1>
<p>Hello {{ user }}.</p>
</body>
</html>
````

So, this is a simple MVC design.
* The "test.twig" is the View. Check twig for more information on templating
* the "HomeController" is your controller and this is where you bind a route to your view.
* The model is more subtle in this example, but it's the array containing the "user". It
can be hardcoded like this or you can create a real model in a model directory.

### Configs
The configuration are in the file "SunApp.php".
````
public $config = [
        'routes.controllers' => null,   // string|array<string>: Override with namespace(s) containing controllers (Must inherit from IRoutable).
        'routes.custom' => null,        // callback: If you just want to make a simple function for registering your routes.
        'view.templates' => '.',        // string|array<string>: directory where the twig templates are located.
        'view.cache' => false,          // bool: set to true to enable the cache
        'view.csrf' => false,           // enable CSRF token.
        'view.addExtension' => null,    // callback($twig): If you want to register new extension
        'session.cookie_lifetime' => 1209600,   // int: 14 days is the default.
        'i18n.directory' => null,       // string: locale directory. If null, no locale will be set.
        'i18n.default' => 'en-us',      // string: the default locale. You will need a file with this extension
        'i18n.domain' => 'default'      // string: the name of the file that will be used to find the string.
    ];
````
If this documentation becomes out of date, always refer to that file.

### Using Libraries
In the templating part, I've added an easy way to include files in a page. The reasons why:
1. I love using CDN versions
2. It's always complicated to have the same version on all templates.
3. Switching between the minified version and the standard one can be annoying.

## Running the sample
Using docker-compose
````
docker-compose run composer install
docker-compose up
````
Launch http://localhost:9999/

## Configuring
Even if it's a simple framework, you can easily activate each features individually. 
The reason is that sometime you don't need a mySql database for a simple web site. Or if
you don't like twig as a template engine, you can always use another one.

## TODO
* remove recaptcha manager.
* remove useless twig extensions.
* add doc for CSRF token in the form. See Also whitelisting.
* refactor twig extension if needed.
* try to document all cases (twig, i18n, etc).
* try to put back the SSP - really useful with datatables.net
* make sure you can minify the versions of the local files if necessary.
* Twig Global Variables
    * user
    * userRole
    * csrf
* explain the switch statement 
* How to add extensions.
* Explain registration of controllers.