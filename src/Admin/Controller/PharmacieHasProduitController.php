<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\PharmacieHasProduitException;
use App\Admin\Repository\PharmacieHasProduitRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class PharmacieHasProduitController extends BaseController
{
    public function add(Request $request, Response $response , array $args): Response
    {
       
        $params  = $request->getParsedBody();
        $pharmacie = ($params["userLogged"]["user"]->pharmacie==0) ? $args["id"] : $params["userLogged"]["user"]->pharmacie ;
        $params["createdAt"] = date("Y-m-d H:i:s");
        $params["createdBy"] = $params["userLogged"]["user"]->id??0;
        unset($params["userLogged"]);
        $this->validateActionsSaving($params['produits']);
        $repository = new PharmacieHasProduitRepository;
        $pharmacie = $repository->addPharmacieHasProduit($pharmacie, $params['produits'],$params["createdBy"],$params["createdAt"]);
        return $this->jsonResponseWithData($response, "success", "Produits ajoutés avec succès", $pharmacie, 200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
       
        $params  = (array)$request->getParsedBody();
        $params["modified_by"] = $params["userLogged"]["user"]->id??null;
        $params["modified_at"] = date("Y-m-d H:i:s");
        $pharmacie=$params["userLogged"]["user"]->pharmacie;
        $critere="true";
        if ($pharmacie > 0) {
            $critere="id_pharmacie=$pharmacie";
        }
        unset($params["userLogged"]);
        $this->validateUpdate($params);
        $repository = new PharmacieHasProduitRepository;
        $produit = $repository->update($id, $params, $critere);
        return $this->jsonResponseWithData($response, "success", "Produit pharmacie modifiée avec succès", $produit, 200);
    }

    public function getAll(Request $request, Response $response): Response
    {

        $queryParams = $request->getQueryParams();
        
        $params  = (array)$request->getParsedBody();
        $pharmacie=$params["userLogged"]["user"]->pharmacie;
        $critere="true";
        if ($pharmacie > 0) {
            $critere="php.id_pharmacie=$pharmacie ";
        }
        $repository = new PharmacieHasProduitRepository;
        if (isset($queryParams["pharmacie"]) && !empty($queryParams["pharmacie"])) {
            $pharmacie = $queryParams["pharmacie"];
            $critere .= " AND php.id_pharmacie='$pharmacie'";
        }

        if (isset($queryParams["produit"]) && !empty($queryParams["produit"])) {
            $produit = $queryParams["produit"];
            $critere .= " AND php.id_produit='$produit'";
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
        $pharmacies = $repository->getAll($critere, $page, $perPage);
        return $this->jsonResponseWithoutMessage($response, 'success', $pharmacies, 200);
    }

    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $pharmacie=$params["userLogged"]["user"]->pharmacie;
        $critere="true";
        if ($pharmacie > 0) {
            $critere="php.id_pharmacie=$pharmacie ";
        }
        $prdouit = (new PharmacieHasProduitRepository)->getOne($id, $critere);
        return $this->jsonResponseWithoutMessage($response, 'success', $prdouit, 200);
    }

    public function setStatus(Request $request, Response $response, array $args): Response{
        
        $params  = $request->getParsedBody();
        // var_dump($params); die;
        
        $pharmacie=$params["userLogged"]["user"]->pharmacie;
        $critere="true";
        if ($pharmacie > 0) {
            $critere="php.id_pharmacie=$pharmacie ";
        }
        $params["modifiedAt"] = date("Y-m-d H:i:s");
        $params["modifiedBy"] = $params["userLogged"]["user"]->id??0;
        unset($params["userLogged"]);
        $this->validateProduit($params['produits']);
        if (!is_numeric($params['statut'])) throw new PharmacieHasProduitException("Le statut est un entier.");
        $repository = new PharmacieHasProduitRepository;
        $pharmacie = $repository->ChangerStatutPharmacieHasProduit( $params['produits'], $params['statut'], $params["modifiedBy"],$params["modifiedAt"], $critere);
        return $this->jsonResponseWithData($response, "success", "Statut Produits changés avec succès", $pharmacie, 200);
    }
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        (new PharmacieHasProduitRepository)->delete($id);
        return $this->jsonResponse($response, 'success', "Produit pharmacie supprimé avec succès", 200);
    }

    private function validate($params)
    {
        foreach ($params as  $value) {
            if (!is_numeric($value['prix'])) throw new PharmacieHasProduitException("Les prix sont des entiers.");
            if (!is_numeric($value['id_produit'])) throw new PharmacieHasProduitException("Les produit sont des entiers.");
        }
    }

    private function validateProduit($params)
    {
        foreach ($params as  $value) {
            if (!is_numeric($value)) throw new PharmacieHasProduitException("Les produit sont des entiers.");
          
        }
    }

    private function validateUpdate($params)
    {
        
            if (!is_numeric($params['prix'])) throw new PharmacieHasProduitException("Le prix est un entier.");
            if (!is_numeric($params['statut'])) throw new PharmacieHasProduitException("Le statut est un entier.");
       
    }


    public function getPharamacieProduits(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $pharmacies = (new PharmacieHasProduitRepository)->getPharmacieHasProduits($id);
        return $this->jsonResponseWithoutMessage($response, "success", $pharmacies, 200);
    }

 

    public function deleteGroupePharmacies(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $parsedBody = (array)$request->getParsedBody();

        $pharmacie=$parsedBody["userLogged"]["user"]->pharmacie;
        $critere="true";
        if ($pharmacie > 0) {
            $critere="php.id_pharmacie=$pharmacie ";
        }
        

        $pharmacies = (new PharmacieHasProduitRepository)->delete($id, $critere);
        return $this->jsonResponseWithData($response, "success", "Produit Supprimé", $pharmacies, 200);
    }

    private function validateActionsSaving($parsedBody)
    {
        $this->required($parsedBody, "pharmacies", new PharmacieHasProduitException("pharmacies est obligatoire"));
        if (empty($parsedBody)) throw new PharmacieHasProduitException("pharmacies ne peut être vide");
        foreach ($parsedBody["pharmacies"] as  $value) {
            if (!is_numeric($value)) throw new PharmacieHasProduitException("Les éléments de pharmacies pharmacies sont des entiers.");
        }
    }
}
