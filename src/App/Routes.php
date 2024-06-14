<?php

declare(strict_types=1);

use App\Controller\PharmacieController;
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
$app->post('/v1/login', "App\Controller\UserController:login");
$app->post('/v1/admin/login', "App\Admin\Controller\UserController:login");
$app->get('/error', "App\Controller\UserController:handleError");




/**
 * Recherche de produits
 */
$app->post('/search', 'App\Controller\SearchController:make'); 


/**
 * Les routes des fonctionnalités usuelles 
 */

$app->group('/v1', function () use ($app): void {
    // Actions pour pour les pharmacies
    $app->group('/actions', function () use ($app): void {
        $app->get('', "App\Controller\ActionController:getAll");
        $app->post('', "App\Controller\ActionController:add");
        $app->get('/{id}', "App\Controller\ActionController:getOne");
        $app->put('/{id}', "App\Controller\ActionController:update");
        $app->delete('/{id}', "App\Controller\ActionController:delete");
    });
    $app->group('/users', function () use ($app): void {
        $app->get('', "App\Controller\UserController:getAll");
        $app->post('', "App\Controller\UserController:add");
        $app->post('/resetPassword/{id}', "App\Controller\UserController:resetPassword");
        $app->get('/{id}', "App\Controller\UserController:getOne");
        $app->put('/{id}', "App\Controller\UserController:update");
        $app->post('/changePassword', "App\Controller\UserController:changePassword");
        $app->delete('/{id}', "App\Controller\UserController:delete");
    })->add(new Auth);

    $app->group('/profils', function () use ($app): void {
        $app->group('/actions', function () use ($app): void {
            $app->get('/{id}', "App\Controller\ProfilController:getProfilActions");
            $app->post('/{id}', "App\Controller\ProfilController:addActions");
            $app->delete('/{id}', "App\Controller\ProfilController:deleteActions");
        });
        $app->get('', "App\Controller\ProfilController:getAll");
        $app->post('', "App\Controller\ProfilController:add");
        $app->get('/{id}', "App\Controller\ProfilController:getOne");
        $app->put('/{id}', "App\Controller\ProfilController:update");
        $app->put('/setStatus/{id}', "App\Controller\ProfilController:setStatus");
        $app->delete('/{id}', "App\Controller\ProfilController:delete");
    })->add(new Auth);

    //Ce groupe concerne les paramètres de l'application du coté de l'administrateur de pharmacie
    $app->group('/parametres', function () use ($app): void {
        $app->get('/{id}', "App\Admin\Controller\PharmacieController:getOne");
        $app->put('/{id}', "App\Admin\Controller\PharmacieController:update");
        
       
    })->add(new Auth);

    $app->group("/stocks", function () use ($app): void {
        // $app->get('', "App\Admin\Controller\PharmacieHasProduitController:getAll");
        // $app->post('/{id}', "App\Admin\Controller\PharmacieHasProduitController:add");
        $app->put('/setStatus/{id}', "App\Admin\Controller\PharmacieHasProduitController:setStatus");
        //$app->get('/{id}', "App\Admin\Controller\PharmacieHasProduitController:getOne");
        $app->get('/{id}', "App\Admin\Controller\PharmacieHasProduitController:getPharamacieProduits");
        // $app->put('/{id}', "App\Admin\Controller\PharmacieHasProduitController:update");
        // $app->delete('/{id}', "App\Admin\Controller\PharmacieHasProduitController:delete");
    })->add(new Auth);

    // Consulter les catégories de produits
    $app->group('/categories', function () use ($app): void {
        $app->get('', "App\Admin\Controller\CategorieController:getAll");
    });
});






/**
 * Les routes liées à partie Administration 
 */

$app->group('/v1/admin', function () use ($app): void {

    // Les statistiques
    $app->get('/stats', "App\Admin\Controller\StatsController:getAll");

    $app->group('/actions', function () use ($app): void {
        $app->get('', "App\Admin\Controller\ActionController:getAll");
        $app->post('', "App\Admin\Controller\ActionController:add");
        $app->get('/{id}', "App\Admin\Controller\ActionController:getOne");
        $app->put('/{id}', "App\Admin\Controller\ActionController:update");
        $app->delete('/{id}', "App\Admin\Controller\ActionController:delete");
    });
    
    $app->group('/profils', function () use ($app): void {
        $app->group('/actions', function () use ($app): void {
            $app->get('/{id}', "App\Admin\Controller\ProfilController:getProfilActions");
            $app->post('/{id}', "App\Admin\Controller\ProfilController:addActions");
            $app->delete('/{id}', "App\Admin\Controller\ProfilController:deleteActions");
        });
        $app->get('', "App\Admin\Controller\ProfilController:getAll");
        $app->post('', "App\Admin\Controller\ProfilController:add");
        $app->get('/{id}', "App\Admin\Controller\ProfilController:getOne");
        $app->put('/{id}', "App\Admin\Controller\ProfilController:update");
        $app->put('/setStatus/{id}', "App\Admin\Controller\ProfilController:setStatus");
        $app->delete('/{id}', "App\Admin\Controller\ProfilController:delete");
    })->add(new Auth);

    $app->group('/users', function () use ($app): void {
        $app->get('', "App\Admin\Controller\UserController:getAll");
        $app->post('', "App\Admin\Controller\UserController:add");
        $app->post('/resetPassword/{id}', "App\Admin\Controller\UserController:resetPassword");
        $app->get('/{id}', "App\Admin\Controller\UserController:getOne");
        $app->put('/{id}', "App\Admin\Controller\UserController:update");
        $app->put('/setStatus/{id}', "App\Admin\Controller\UserController:setStatus");
        $app->post('/changePassword', "App\Admin\Controller\UserController:changePassword");
        $app->delete('/{id}', "App\Admin\Controller\UserController:delete");
    })->add(new Auth);


    $app->group('/groupe_pharmacie_garde', function () use ($app): void {
        $app->get('', "App\Controller\UserController:getUsers");
        $app->post('', function () {
        });
        $app->get('/{id}', function () {
        })->add(new Auth());
        $app->put('/{id}', function () {
        })->add(new Auth());
        $app->delete('/{id}', function () {
        })->add(new Auth());
    });


    $app->group('/pharmacies', function () use ($app): void {
        $app->group("/stock", function () use ($app): void {
            $app->get('', "App\Admin\Controller\PharmacieHasProduitController:getAll");
            $app->post('/{id}', "App\Admin\Controller\PharmacieHasProduitController:add");
            $app->put('/setStatus/{id}', "App\Admin\Controller\PharmacieHasProduitController:setStatus");
            $app->get('/{id}', "App\Admin\Controller\PharmacieHasProduitController:getOne");
            $app->get('/pharmacie/{id}', "App\Admin\Controller\PharmacieHasProduitController:getPharamacieProduits");
            $app->put('/{id}', "App\Admin\Controller\PharmacieHasProduitController:update");
            $app->delete('/{id}', "App\Admin\Controller\PharmacieHasProduitController:delete");
        });
        $app->group("/admin", function () use ($app): void {
            $app->post('/{id}', "App\Admin\Controller\PharmacieController:addAdmin");
            $app->put('/{id}', "App\Admin\Controller\PharmacieController:updateAdmin");
            $app->get('/{id}', "App\Admin\Controller\PharmacieController:getAdmin");
        });
        $app->get('', "App\Admin\Controller\PharmacieController:getAll");
        $app->post('', "App\Admin\Controller\PharmacieController:add");
        $app->get('/{id}', "App\Admin\Controller\PharmacieController:getOne");
        $app->put('/{id}', "App\Admin\Controller\PharmacieController:update");
        $app->put('/SynProduit/{id}', "App\Admin\Controller\PharmacieController:synProduit");
        $app->post('/setStatus/{id}', "App\Admin\Controller\PharmacieController:setStatus");
        $app->delete('/{id}', "App\Admin\Controller\PharmacieController:delete");
    })->add(new Auth);

    $app->group('/categories', function () use ($app): void {
        $app->get('', "App\Admin\Controller\CategorieController:getAll");
        $app->post('', "App\Admin\Controller\CategorieController:add");
        $app->get('/{id}', "App\Admin\Controller\CategorieController:getOne");
        $app->put('/{id}', "App\Admin\Controller\CategorieController:update");
        $app->delete('/{id}', "App\Admin\Controller\CategorieController:delete");
    })->add(new Auth());
    $app->group('/groupes_gardes', function () use ($app): void {
        $app->group('/pharmacies', function () use ($app): void {
            $app->get('/{id}', "App\Admin\Controller\GroupeGardeController:getGroupePharmacies");
            $app->post('/{id}', "App\Admin\Controller\GroupeGardeController:addGroupePharmacies");
            $app->delete('/{id}', "App\Admin\Controller\GroupeGardeController:deleteGroupePharmacies");
        });
        $app->get('', "App\Admin\Controller\GroupeGardeController:getAll");
        $app->post('', "App\Admin\Controller\GroupeGardeController:add");
        $app->get('/{id}', "App\Admin\Controller\GroupeGardeController:getOne");
        $app->put('/{id}', "App\Admin\Controller\GroupeGardeController:update");
        $app->put('/setStatus/{id}', "App\Admin\Controller\GroupeGardeController:setStatus");
        $app->delete('/{id}', "App\Admin\Controller\GroupeGardeController:delete");
    })->add(new Auth);


    $app->group('/produits', function () use ($app): void {
        $app->get('', "App\Admin\Controller\ProduitController:getAll");
        $app->post('', "App\Admin\Controller\ProduitController:add");
        $app->get('/{id}', "App\Admin\Controller\ProduitController:getOne");
        $app->put('/{id}', "App\Admin\Controller\ProduitController:update");
        $app->delete('/{id}', "App\Admin\Controller\ProduitController:delete");
    })->add(new Auth());
});
