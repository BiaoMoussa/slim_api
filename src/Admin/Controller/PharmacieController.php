<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\PharmacieException;
use App\Exception\User as ExceptionUser;
use App\Repository\PharmacieRepository;
use App\Repository\UserRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class PharmacieController extends BaseController
{
    public function __construct() {
        $this->repository = new PharmacieRepository;
    }

   
    
    public function insert(Request $request, Response $response):Response {
        $body = $request->getParsedBody();
        if(!$body){
            throw new PharmacieException("Veuillez renseigner les paramètres du body.");
        }
        if(!isset($body["nom"])){
            throw new PharmacieException("Le nom est obligatoire");
        }
        if(!isset($body["adresse"])){
            throw new PharmacieException("L'adresse est obligatoire");
        }
        if(!isset($body["telephone"])){
            throw new PharmacieException("Le téléphone est obligatoire");
        }
        $repository = new PharmacieRepository;
        $reponse = $repository->insert($body);
        return $this->jsonResponse($response, 'success', $reponse, 200);
    }


    public function findAll(Request $request, Response $response):Response {
        $repository = new PharmacieRepository;
        $reponse = $repository->getAll();
        return $this->jsonResponseWithoutMessage($response, 'success', $reponse, 200);
    }
    
}
