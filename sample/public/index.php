<?php

use sunframework\SunApp;
use sunframework\twigExtensions\LibraryItem;

require "../../vendor/autoload.php";


$app = new SunApp([
    'i18n.directory' => dirname(__DIR__) . '/locale',
    'view.templates' => dirname(__DIR__) . '/templates',
    'routes.controllers' => 'sample\controllers'
]);

$app->addLibrary(new LibraryItem('jquery', [
    'jsMin' => ['https://code.jquery.com/jquery-3.3.1.min.js']
]));

$app->addLibrary(new LibraryItem('semantic-ui', [
    'jsMin' => ['https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js'],
    'cssMin' => ['https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.css']
]));

$app->run();