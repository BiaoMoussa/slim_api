<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\PharmacieHasProduitException;
use App\Admin\Repository\PharmacieHasProduitRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class PharmacieHasProduitController extends BaseController
{
    public function add(Request $request, Response $response, array $args): Response
    {

        $params  = $request->getParsedBody();
        $pharmacie = ($params["userLogged"]["user"]->pharmacie == 0) ? $args["id"] : $params["userLogged"]["user"]->pharmacie;
        $params["createdAt"] = date("Y-m-d H:i:s");
        $params["createdBy"] = $params["userLogged"]["user"]->id ?? 0;
        unset($params["userLogged"]);
        $this->validate($params['produits']);
        $repository = new PharmacieHasProduitRepository;
        $pharmacie = $repository->addPharmacieHasProduit($pharmacie, $params['produits'], $params["createdBy"], $params["createdAt"]);
        return $this->jsonResponseWithData($response, "success", "Produits ajoutés avec succès", $pharmacie, 200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];

        $params  = (array)$request->getParsedBody();
        $params["modified_by"] = $params["userLogged"]["user"]->id ?? null;
        $params["modified_at"] = date("Y-m-d H:i:s");
        $pharmacie = $params["userLogged"]["user"]->pharmacie;
        $critere = "true";
        if ($pharmacie > 0) {
            $critere = "id_pharmacie=$pharmacie";
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
        $pharmacie = $params["userLogged"]["user"]->pharmacie;
        $critere = "true";
        if ($pharmacie > 0) {
            $critere = "php.id_pharmacie=$pharmacie ";
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

        if (isset($queryParams["statut"])) {
            $statut = $queryParams["statut"];
            $critere .= " AND php.statut='$statut'";
        }

        if (isset($queryParams["categorie"]) && !empty($queryParams["categorie"])) {
            $categorie = $queryParams["categorie"];
            $critere .= " AND pr.id_categorie='$categorie'";
        }

        if (isset($queryParams["designation"]) && !empty($queryParams["designation"])) {
            $designation = $queryParams["designation"];
            $critere .= " AND pr.designation LIKE '%$designation%'";
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
        $pharmacie = $params["userLogged"]["user"]->pharmacie;
        $critere = "true";
        if ($pharmacie > 0) {
            $critere = "php.id_pharmacie=$pharmacie ";
        }
        $prdouit = (new PharmacieHasProduitRepository)->getOne($id, $critere);
        return $this->jsonResponseWithoutMessage($response, 'success', $prdouit, 200);
    }

    public function setStatus(Request $request, Response $response, array $args): Response
    {

        $id = $args['id'];
        $params = $request->getParsedBody();
        unset($params["userLogged"]);
        $this->required($params, "status", new PharmacieHasProduitException("status est obligatoire"));
        if (isset($params["status"])) {
            if (is_null($params["status"])) throw new PharmacieHasProduitException("status ne peut être vide.");
            if (!is_numeric($params["status"])) throw new PharmacieHasProduitException("status doit être un nombre entier.");
            if ($params["status"] != 0 && $params["status"] != 1) throw new PharmacieHasProduitException("status doit être 0:inactif ou 1:actif.");
        }
        $params["modifiedBy"] = $params["userLogged"]["user"]->id ?? null;
        $params["modifiedAt"] = date("Y-m-d H:i:s");
        $repository = new PharmacieHasProduitRepository;
        $produit = $repository->ChangerStatutPharmacieHasProduit($id, $params['status'], $params["modifiedBy"], $params["modifiedAt"]);
        $message = "Produit ";
        $message .= ($params["status"] == 1) ? "activé " : "désactivé";
        $message .= " avec succès !";
        return $this->jsonResponseWithData($response, "success", $message, $produit, 200);
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
        $queryParams = $request->getQueryParams();

        $params  = (array)$request->getParsedBody();
        $pharmacie = $params["userLogged"]["user"]->pharmacie;
        $critere = "true";
        if ($pharmacie > 0) {
            $critere = "php.id_pharmacie=$pharmacie ";
        }
        
        if (isset($queryParams["pharmacie"]) && !empty($queryParams["pharmacie"])) {
            $pharmacie = $queryParams["pharmacie"];
            $critere .= " AND php.id_pharmacie='$pharmacie'";
        }

        if (isset($queryParams["produit"]) && !empty($queryParams["produit"])) {
            $produit = $queryParams["produit"];
            $critere .= " AND php.id_produit='$produit'";
        }

        if (isset($queryParams["statut"])) {
            $statut = $queryParams["statut"];
            $critere .= " AND php.statut='$statut'";
        }

        if (isset($queryParams["categorie"]) && !empty($queryParams["categorie"])) {
            $categorie = $queryParams["categorie"];
            $critere .= " AND pr.id_categorie='$categorie'";
        }

        if (isset($queryParams["designation"]) && !empty($queryParams["designation"])) {
            $designation = $queryParams["designation"];
            $critere .= " AND pr.designation LIKE '%$designation%'";
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
        $pharmacies = (new PharmacieHasProduitRepository)->getPharmacieHasProduits($id,$critere,$page,$perPage);
        return $this->jsonResponseWithoutMessage($response, "success", $pharmacies, 200);
    }



    public function deleteGroupePharmacies(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $parsedBody = (array)$request->getParsedBody();

        $pharmacie = $parsedBody["userLogged"]["user"]->pharmacie;
        $critere = "true";
        if ($pharmacie > 0) {
            $critere = "php.id_pharmacie=$pharmacie ";
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
