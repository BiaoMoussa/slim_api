<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\CategorieException;
use App\Admin\Repository\CategorieRepository;

use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

class CategorieController extends BaseController
{

   

    public function getAll(Request $request, Response $response): Response
    {
        $queryParams = (array)$request->getQueryParams();
        $userRepository = new CategorieRepository;
        $critere = " true ";
        if (isset($queryParams["id"]) && !is_null($queryParams["id"])) {
            $idCategorie = $queryParams["id"];
            $critere .= "AND id_categorie='$idCategorie' ";
        }
    
        if (isset($queryParams["code_categorie"]) && !is_null($queryParams["code_categorie"])) {
            $code = strtolower($queryParams["code_categorie"]);
            $critere .= "AND LOWER(code_categorie) LIKE '%$code%' ";
        }

        if (isset($queryParams["libelle_categorie"]) && !is_null($queryParams["libelle_categorie"])) {
            $libelle = strtolower($queryParams["libelle_categorie"]);
            $critere .= "AND LOWER(libelle_categorie) LIKE '%$libelle%' ";
        }
    
     

        if (isset($queryParams["perPage"]) && !empty($queryParams["perPage"])) {
            $perPage = (int)$queryParams["perPage"];
        } else {
            $perPage = 10;
        }

        if (isset($queryParams["page"]) && !empty($queryParams["page"])) {
            $page = (int)$queryParams["page"];
        } else {
            $page = 1;
        }

        $encodeJson = $userRepository->getAll($critere, $page, $perPage);

        return $this->jsonResponseWithoutMessage($response, 'success', $encodeJson, 200);
    }

    public function getOne(Request $request, Response $response, array $args)
    {
        $id = $args["id"];
        $action = (new CategorieRepository)->getOne($id);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    public function add(Request $request, Response $response): Response
    {
        $params  = (array)$request->getParsedBody();
        $params["created_by"] = $params["userLogged"]["user"]->id??null;
        $params["created_at"] = date("Y-m-d H:i:s");
        unset($params["userLogged"]);
        $this->validateInsert($params);
        $repository = new CategorieRepository;
        $user = $repository->insert($params);
        return $this->jsonResponseWithData($response, 'success', "Categorie créée avec succès.", $user, 200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $params["modified_by"] = $params["userLogged"]["user"]->id??null;
        $params["modified_at"] = date("Y-m-d H:i:s");
        unset($params["userLogged"]);
        $this->validateUpdate($params);
        $repository = new CategorieRepository;
        $user = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, 'success', "Categorie modifiée avec succès.", $user, 200);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        (new CategorieRepository)->delete($id);
        return $this->jsonResponse($response, 'success', "Categorie supprimée avec succès", 200);
    }



    private function validateInsert($params)
    {
        $this->required($params, "code_categorie", new CategorieException("code est obligatoire."));
        $this->required($params, "libelle_categorie", new CategorieException("libelle est obligatoire."));
        $this->validateUpdate($params);
    }

    private function validateUpdate($params)
    {
        if (isset($params["code_categorie"]) && !is_null($params["code_categorie"])) {
            if (!is_string($params["code_categorie"])) throw new CategorieException("code doit être une chaine de caractère");
            if (strlen($params["code_categorie"]) <= 1) throw new CategorieException("code doit avoir au moins 2 caractères");
        }
        if (isset($params["libelle_categorie"]) && !is_null($params["libelle_categorie"])) {
            if (!is_string($params["libelle_categorie"])) throw new CategorieException("libelle doit être une chaine de caractère");
            if (strlen($params["libelle_categorie"]) <= 1) throw new CategorieException("libelle doit avoir au moins 2 caractères");
        }

    }
}
