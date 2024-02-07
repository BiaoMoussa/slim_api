<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\GroupeGardeException;
use App\Admin\Repository\GroupeGardeRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class GroupeGardeController extends BaseController
{
    public function add(Request $request, Response $response): Response
    {
        $params  = $request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id??null;
        $params["createdBy"] = $params["userLogged"]["user"]->id??null;
        unset($params["userLogged"]);
        $this->validate($params);
        $repository = new GroupeGardeRepository;
        $pharmacie = $repository->insert($params);
        return $this->jsonResponseWithData($response, "success", "Action ajoutée avec succès", $pharmacie, 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id??null;
        $params["updatedAt"] = date("Y-m-d H:i:s");
        unset($params["userLogged"]);
        $this->validate($params);
        $repository = new GroupeGardeRepository;
        $pharmacie = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, "success", "Action modifiée avec succès", $pharmacie, 200);
    }

    public function getAll(Request $request, Response $response): Response
    {

        $queryParams = $request->getQueryParams();
        $repository = new GroupeGardeRepository;
        $critere = "true ";
        if (isset($queryParams["id"]) && !empty($queryParams["id"])) {
            $id = $queryParams["id"];
            $critere .= " AND id_groupe_garde='$id'";
        }
        if (isset($queryParams["libelle"]) && !empty($queryParams["libelle"])) {
            $libelle = strtolower($queryParams["libelle"]);
            $critere .= " AND LOWER(libelle_groupe) LIKE '%$libelle%'";
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
        $pharmacie = (new GroupeGardeRepository)->getOne($id);
        return $this->jsonResponseWithoutMessage($response, 'success', $pharmacie, 200);
    }

    public function setStatus(Request $request, Response $response, array $args): Response{
        $id = $args['id'];
        $params = $request->getParsedBody();
        $this->required($params,"status",new GroupeGardeException("status est obligatoire"));
        if(isset($params["status"])){
            if(is_null($params["status"])) throw new GroupeGardeException("status ne peut être vide.");
            if(!is_numeric($params["status"])) throw new GroupeGardeException("status doit être un nombre entier.");
            if($params["status"]!=0 && $params["status"]!=1) throw new GroupeGardeException("status doit être 0:inactif ou 1:actif.");
        }
        $profil = (new GroupeGardeRepository)->setStatus($id, $params["status"]);
        return $this->jsonResponseWithData($response,"success","Statut mis à jour avec succès." ,$profil,200);
    }
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        (new GroupeGardeRepository)->delete($id);
        return $this->jsonResponse($response, 'success', "Groupe supprimé avec succès", 200);
    }

    private function validate($params)
    {
        $this->required($params, "libelle", new GroupeGardeException("libelle est obligatoire"));
        if (strlen($params["libelle"]) < 3) throw new GroupeGardeException("libelle doit comporter au moins 3 lettres");
    }


    public function getGroupePharmacies(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $pharmacies = (new GroupeGardeRepository)->getGroupePharmacies($id);
        return $this->jsonResponseWithoutMessage($response, "success", $pharmacies, 200);
    }

    public function addGroupePharmacies(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $parsedBody = (array)$request->getParsedBody();
        $this->validateActionsSaving($parsedBody);
        $pharmacies = (array)$parsedBody["pharmacies"];
        $message = count($pharmacies) > 1 ? "pharmacies ajoutées avec succès" : "pharmacie ajoutée avec succès";
        $pharmacies = (new GroupeGardeRepository)->addGroupePharmacies($id, $pharmacies);
        return $this->jsonResponseWithData($response, "success", $message, $pharmacies, 200);
    }

    public function deleteGroupePharmacies(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $parsedBody = (array)$request->getParsedBody();
        $this->validateActionsSaving($parsedBody);
        $pharmacies = (array)$parsedBody["pharmacies"];
        $message = count($pharmacies) > 1 ? "pharmacies ajoutées avec succès" : "pharmacie ajoutée avec succès";
        $pharmacies = (new GroupeGardeRepository)->deleteGroupePharmacies($id, $pharmacies);
        return $this->jsonResponseWithData($response, "success", $message, $pharmacies, 200);
    }

    private function validateActionsSaving($parsedBody)
    {
        $this->required($parsedBody, "pharmacies", new GroupeGardeException("pharmacies est obligatoire"));
        if (empty($parsedBody)) throw new GroupeGardeException("pharmacies ne peut être vide");
        foreach ($parsedBody["pharmacies"] as  $value) {
            if (!is_int($value)) throw new GroupeGardeException("Les éléments de pharmacies pharmacies sont des entiers.");
        }
    }
}
