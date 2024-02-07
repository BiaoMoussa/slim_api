<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\ProduitException;
use App\Admin\Repository\ProduitRepository;


use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

class ProduitController extends BaseController
{

   

    public function getAll(Request $request, Response $response): Response
    {
        $queryParams = (array)$request->getQueryParams();
        $produitRepository = new ProduitRepository;
        $critere = " true ";
        if (isset($queryParams["id"]) && !is_null($queryParams["id"])) {
            $idProduit = $queryParams["id"];
            $critere .= "AND id_produit='$idProduit' ";
        }
    
        if (isset($queryParams["designation"]) && !is_null($queryParams["designation"])) {
            $designation = strtolower($queryParams["designation"]);
            $critere .= "AND LOWER(designation) LIKE '%$designation%' ";
        }

        if (isset($queryParams["id_categorie"]) && !is_null($queryParams["id_categorie"])) {
            $id_categorie = strtolower($queryParams["id_categorie"]);
            $critere .= "AND p.id_categorie = '$id_categorie' ";
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

        $encodeJson = $produitRepository->getAll($critere, $page, $perPage);

        return $this->jsonResponseWithoutMessage($response, 'success', $encodeJson, 200);
    }

    public function getOne(Request $request, Response $response, array $args)
    {
        $id = $args["id"];
        $action = (new ProduitRepository)->getOne($id);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    public function add(Request $request, Response $response): Response
    {
    
        $params  = (array)$request->getParsedBody();     
        
        $params["created_by"] = $params["userLogged"]["user"]->id??null;
        $params["created_at"] = date("Y-m-d H:i:s");
        unset($params["userLogged"]);
        $this->validateInsert($params);
        $repository = new ProduitRepository;
        $user = $repository->insert($params);
        return $this->jsonResponseWithData($response, 'success', "Produit créée avec succès.", $user, 200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $params["modified_by"] = $params["userLogged"]["user"]->id??null;
        $params["modified_at"] = date("Y-m-d H:i:s");
        unset($params["userLogged"]);
        $this->validateUpdate($params);
        $repository = new ProduitRepository;
        $user = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, 'success', "Produit modifiée avec succès.", $user, 200);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        (new ProduitRepository)->delete($id);
        return $this->jsonResponse($response, 'success', "Produit supprimée avec succès", 200);
    }



    private function validateInsert($params)
    {
        $this->required($params, "designation", new ProduitException("designation est obligatoire."));
        $this->required($params, "description", new ProduitException("description est obligatoire."));
        $this->required($params, "id_categorie", new ProduitException("categorie est obligatoire."));
        $this->validateUpdate($params);
    }

    private function validateUpdate($params)
    {
        if (isset($params["designation"]) && !is_null($params["designation"])) {
            if (!is_string($params["designation"])) throw new ProduitException("designation doit être une chaine de caractère");
            if (strlen($params["designation"]) <= 1) throw new ProduitException("designation doit avoir au moins 2 caractères");
        }
      

    }
}
