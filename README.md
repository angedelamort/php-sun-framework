# PHP Sun Framework
another simple PHP framework.

## Features
* routing - Slim
* templating - Twig
* i18n (internationalization) - custom
* Simple User Management - custom
* CSRF Token - twig
* Rendering State Machine - custom
* Cache - custom
* server-side tables integration - DataTables.net (remove?)
* DebugBar - DebugBar

## Installing

````
composer require "angedelamort/php-sun-framework"
````

### Running all tests
```
composer run tests
```

### Using docker-compose
Just prefix the commands
```
docker-compose run %composer-command%
```

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

## Running the sample
Using docker-compose
````
docker-compose run composer install
docker-compose up
````
Launch http://localhost:9999/

## Documentation
The goal of this framework is to provide a simple way to create a php application using routing and a templating engine. I've added some other features, but my initial idea was that I didn't want to have to depends on anything complicated to setup my code.

First, since I've been using docker, I use it to develop and test my code. It's a lot more simple since I don't have to fight the different configuration files or php/mySql/apache/etc versions.

Secondly, I usually hate the fact that when I want to use an external framework I either have to get it locally in put my code mixed with the framework or I have to setup a database just because the framework said so.

Finally, I wanted something that we can easily scale and add features without the limitation. So I've tried to keep the technologies as vanilla as possible. Also, I've added some twig extension along the way to simplify the templating.

I hope you'll like it.

### SunApp Configuration
The configuration are in the file "SunApp.php".

If you want to have a working applicationm you'll have to either add a namespace to the ``routes.controllers`` or at least set a custom callback in the ``routes.custom``. And in order to work, you'll have to let know the web server that you want to route everything to ``index.php``. In the sample folder, I've added a ``.htaccess`` or you can have a look in the ``/phpdocker/ngnix/ngnix.conf`` for examples.
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

### Controllers
todo: explains how it works (psr4) and how to implement it

#### User Permission
explains the role

### Templating
Using twig (https://twig.symfony.com/doc/2.x/) It's a simple but powerful templating engine.

I have added some helpers (extensions) in order to simplify some operations. See
the rest of the section for more information. 

#### Global Variables
* ``user``: todo
* ``userRole``: todo
* ``csrf``: todo

#### Functions
##### js & css
This function is used to include registered libraries or just a normal file. 
The goal is to simplify the templates. 

The syntax is simple: ``{{ js(param1, param2, ...) }}``

An Example:
````
{{ js('jquery', 'semantic-ui') }}
{{ css('css/home.css') }}
````

*Note*: the ultimate goal for those macros would be to merge the files together
and minify them (server-side) instead of running a separate process.

##### csrf
I created this one because we have to add hidden fields for the CSRF token. since it can be 
annoying to repeat the same code over and over again, I created this small utility.
You can have a look at the next section **CSRF Token** but it can be used like that:

```
<form method="POST" action="action.php">
    {{ csrf() }}
    <input type="text" name="test" />
    <input type="submit" value="Submit">
</form>
```

#### CSRF Token
What is a CSRF Token?
https://en.wikipedia.org/wiki/Cross-site_request_forgery
So, if you're doing POST with forms, you might want to add that.

#### How to add it to a page template
There is 2 way of generating the CSRF token:
1. Manually using the global variable
```
<input type="hidden" name="{{csrf.keys.name}}" value="{{csrf.name}}">
<input type="hidden" name="{{csrf.keys.value}}" value="{{csrf.value}}">

---> will output something like: 
<input type="hidden" name="csrf_name" value="csrf5da7cd639e2e2">
<input type="hidden" name="csrf_value" value="6d7cd94be73fef2d2da0a862077ef3b2">
```

2. Using the function generator
```
{{ csrf() }}

---> will output something like: 
<input type="hidden" name="csrf_name" value="csrf5da7cd639e2e2"><input type="hidden" name="csrf_value" value="6d7cd94be73fef2d2da0a862077ef3b2">
```

##### Whitelisting
In some cases (e.g. API), you might want to disable the CSRF for certain routes since it's automatically added to all methods except the GET. I've added a whitelisting mechanism for such case. 

You just have to implements the ``IWhitelistable`` interface. For now it doesn't use any fency mechanism and only use "startsWith".

#### Switch
Sometime you want to do a switch statement in your template.
```
{% switch value %}
    {% case value %}
    {% case value %}
    {% default %}
{% endswitch %}
```

#### Adding new Twig Extension
When the framework is adding twig extensions, you can be call by registering a callback when you
initialize the app.

```
$app = new SunApp([
    'view.addExtension' => function($view) {
        $view->addExtension(new MyExtension());
    }
]);
```

### Users and Authentication
todo: explain simply how to use and override

### Using Libraries / Incluides
In the templating part, I've added an easy way to include files in a page. The reasons why:
1. I love using CDN versions
2. It's always complicated to have the same version on all templates.
3. Switching between the minified version and the standard one can be annoying.

## Extras

### State Machine
You'll probably say: 'Why a new state machine?'. And I probably agree with you that there is already 
really good state machines out there. But I wanted to create something with a twist.

1. I wanted my state machine to be initialized lazily. Since the script loads every time,
I don't want the whole state machine to be instanciated.
2. I wanted to make a rendering oriented State Machine (MVC). In this case, its integrate
beautifully with the controller model.

This is not an advance state machine (no hierarchy) and the implementation is really 'naive'.

To understand the idea properly, I would suggest you to try the sample 'hangman' and check 
the code afterward. I made a really simple implementation of the game, but you can easily
imagine the rest.

For how it works:
1. You define states with appropriate transitions
2. You initialize a state machine with a state.
3. You call step() on it and it will execute the transitions with their conditions.
4. Once a transition occur, you have to handle the persistence.

#### States
A state contains transitions. You class must inherit the base class ``BaseState``.
The base class contains a lot of default values that you can overwrite.
```
class FinalState extends BaseState {
    public function __construct() {
        parent::__construct();
        
        $mySimpleTransition = new Transition(NextState::name());
        $this->addTransition($mySimpleTransition);
    }
}
```

#### Transitions
Same as states, you can create your own transitions if you see a use case. Unlike States,
you add conditions instead of transitions. By default, if a transition doesn't have any
condition, it's always true.  

Also, since **it's not recommended** to change a value in the verify, I've added an ``onExecute``
callback at the end of the transition if the result is true.

```
$t = new Transition(NextState::name(), [$this, 'setLetter']);
$t->addCondition(function (StateMachineContext $context) {
    $val = intval($context->getRequest()->getParsedBodyParam('val'));
    return $val < 10;
});
```

So, in this example, if the post parameter 'val' is smaller than 10, the transition
will be true and the state machine can go to the ``NextState``. 

#### Factory
Since it would be annoying to do a mapping table between the name of the state (that you 
want to persist) and the class, I've added a small factory for that matter. Like the
controllers, it's base on the 'haydenpierce/class-finder' package.

### SSP
server-side datatables.net


### Debugging using a debug bar
In the project, I've added the https://github.com/maximebf/php-debugbar. Since it uses twig 
1.0, I had to do some gymnastics in order to get it working. Also, because of the way
it register to monolog, I had to do some kind of wrapper. So, if you want to use it,
here's the steps. 

1. When initializing your app, add to the option the debug bar:
    ````php
    $options = new SunAppConfig()->activateDebugBar(__DIR__ . '/generated', '/generated')->(...)
    $app = new SunApp($options);
    ```` 
2.  In your Twig template, you will need to add 2 statements:
    ````html
    <!-- HEADER -->
    {{ debugBarHeader() }}
    
    <!-- END OF BODY -->
    {{ debugBarFooter() }}
    ````
3. Normally, it works from here, but if you want your new messages to be catch by the
debugBar, you will need to use the wrapper ``SunLogger``:
    ````php
    // Normal monolog stuff
    $logger = new SunLogger('name');
    $logger->info('Hello here!');
    
    // To appear in the 'Message' section
    $logger->message('Should appear in the message section');
    ````
    
**Note:** Since I didn't want people to start copying files around for debug purposes,
I generate the files in the directory given as parameter when initializing the debugBar.
But if you start playing with it (changing some configs), you will probably need to delete the generated files
manually before doing a new render.

Also, **jQuery is disabled**. You will need toi include it manually. I didn't want to
have multiple version of jQuery at the same time. Since it's for debug purposes, I think
it's a small annoyance.