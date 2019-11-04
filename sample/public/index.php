<?php

use sunframework\SunApp;
use sunframework\SunAppConfig;
use sunframework\twigExtensions\LibraryItem;

require "../../vendor/autoload.php";

$options = (new SunAppConfig())
    ->activateI18n(dirname(__DIR__) . '/locale', 'default', 'en-US')
    ->activateTwig(dirname(__DIR__) . '/templates')
    ->activateCsrfToken()
    ->activateSession()
    ->activateRoutes('sample\controllers');

$app = new SunApp($options);

$app->addLibrary(new LibraryItem('jquery', [
    'jsMin' => ['https://code.jquery.com/jquery-3.3.1.min.js']
]));

$app->addLibrary(new LibraryItem('semantic-ui', [
    'jsMin' => ['https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js'],
    'cssMin' => ['https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css']
]));

$app->run();
