<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\SearchAuth;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;


final class SearchSigninMiddleware extends Base
{
    public function __invoke(
        Request $request,
        Response $response,
        Route $next
    ): ResponseInterface {
        
        $jwtHeader = $request->getHeaderLine('Authorization');
        if (!$jwtHeader) {
            throw new SearchAuth('JWT Token required.', 400);
        }
        $jwt = explode('Bearer ', $jwtHeader);
       
        if (!isset($jwt[1])) {
            throw new SearchAuth('JWT Token invalid.', 400);
        }
        $decoded = (array)$this->checkToken($jwt[1]);
        
        $compte = $decoded["compte"];
       
        // S'il n y a aucun aucun compte 
        if(empty($compte)){
            throw new SearchAuth('Veuillez vous connecter.', 400);
        }

        // Si le compte est désactivé
        if(!$compte->statut){
            throw new SearchAuth('Votre compte est désactivé.', 400);
        }

        // Si le compte est désactivé
        if($compte->solde_recherche<=0){
            throw new SearchAuth('Votre solde recherche est insuffusant. Veuillez recharger votre compte', 400);
        }
        $object = (array) $request->getParsedBody();
        $object['connectedAccount'] = $compte;
        return $next($request->withParsedBody($object), $response);
    }
   
}
