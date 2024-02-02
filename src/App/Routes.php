<?php

declare(strict_types=1);
use App\Middleware\Auth;

/** @var \Slim\App $app */
/**
 * Dans ce fichier sont définies toutes les routes de l'application
 * 
 */




/**
 * Ici on a les routes non souscrites au middleware Auth
 */

$app->get('/', 'App\Controller\DefaultController:getHelp');
$app->get('/status', 'App\Controller\DefaultController:getStatus');
$app->post('/login', "App\Controller\UserController:login");
$app->get('/error',"App\Controller\UserController:handleError");








/**
 * Les routes des fonctionnalités usuelles 
 */

$app->group('/api/v1', function () use ($app): void {
    $app->group('/users', function () use ($app): void {
        $app->get('', function(){});
        $app->post('', function(){});
        $app->get('/{id}', function(){});
        $app->put('/{id}', function(){});
        $app->delete('/{id}', function(){});
    })->add(new Auth());

    $app->group('/profils', function () use ($app): void {
        $app->get('', "App\Controller\UserController:getUsers")->add(new Auth());
        $app->post('',function(){});
        $app->get('/{id}', function(){})->add(new Auth());
        $app->put('/{id}', function(){})->add(new Auth());
        $app->delete('/{id}', function(){})->add(new Auth());
    });

    $app->group('/actions', function () use ($app): void {
        $app->get('', function(){});
        $app->post('', function(){});
        $app->get('/{id}',function(){});
        $app->put('/{id}', function(){});
        $app->delete('/{id}',function(){});
    });
});






/**
 * Les routes liées à partie Administration 
 */

$app->group('/api/v1/admin', function () use ($app): void {
    $app->group('/users', function () use ($app): void {
        $app->get('', function(){});
        $app->post('', function(){});
        $app->get('/{id}', function(){});
        $app->put('/{id}', function(){});
        $app->delete('/{id}', function(){});
    })->add(new Auth());

    $app->group('/profils', function () use ($app): void {
        $app->get('', "App\Controller\UserController:getUsers")->add(new Auth());
        $app->post('',function(){});
        $app->get('/{id}', function(){})->add(new Auth());
        $app->put('/{id}', function(){})->add(new Auth());
        $app->delete('/{id}', function(){})->add(new Auth());
    });

    $app->group('/pharmacies', function () use ($app): void {
        $app->get('', function(){});
        $app->post('', function(){});
        $app->get('/{id}',function(){});
        $app->put('/{id}', function(){});
        $app->delete('/{id}',function(){});
    });
});

