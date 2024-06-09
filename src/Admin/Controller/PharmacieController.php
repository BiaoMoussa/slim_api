<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Controller\BaseController;
use App\Admin\Exception\PharmacieException;
use App\Admin\Repository\PharmacieRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class PharmacieController extends BaseController
{

    public function add(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id ?? null;
        $params["createdBy"] = $params["userLogged"]["user"]->id ?? null;
        unset($params["userLogged"]);
        $this->validateInsert($params);
        $repository = new PharmacieRepository;
        $reponse = $repository->insert($params);
        return $this->jsonResponseWithData($response, 'success', "Pharmacie créée avec succès", $reponse, 200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id ?? null;
        $params["updatedAt"] = date("Y-m-d H:i:s");
        unset($params["userLogged"]);
        $this->validateUpdate($params);
        $repository = new PharmacieRepository;
        $pharmacie = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, "success", "Pharmacie modifiée avec succès", $pharmacie, 200);
    }

    public function getAll(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $repository = new PharmacieRepository;
        $critere = "true ";
        if (isset($queryParams["id"]) && !empty($queryParams["id"])) {
            $id = $queryParams["id"];
            $critere .= " AND id_pharmacie='$id'";
        }
        if (isset($queryParams["nom"]) && !empty($queryParams["nom"])) {
            $nom = strtolower($queryParams["nom"]);
            $critere .= " AND LOWER(nom_pharmacie) LIKE '%$nom%'";
        }
        if (isset($queryParams["adresse"]) && !empty($queryParams["adresse"])) {
            $adresse = strtolower($queryParams["adresse"]);
            $critere .= " AND LOWER(adresse) LIKE '%$adresse%'";
        }

        if (isset($queryParams["telephone"]) && !empty($queryParams["telephone"])) {
            $telephone = strtolower($queryParams["telephone"]);
            $critere .= " AND LOWER(telephone) LIKE '%$telephone%'";
        }

        if (isset($queryParams["garde"]) && ($queryParams["garde"] == "1" || $queryParams["garde"] == "0")) {
            $garde = (int)$queryParams["garde"];
            $critere .= " AND garde = '$garde'";
        }

        if (isset($queryParams["statut"]) && ($queryParams["statut"] == "1" || $queryParams["statut"] == "0")) {
            $statut = (int)$queryParams["statut"];
            $critere .= " AND statut = '$statut'";
        }

        if (isset($queryParams["isFree"]) && ($queryParams["isFree"] == "1" || $queryParams["isFree"] == "0")) {
            $isFree = (int)$queryParams["isFree"];
            if ($isFree == 1) {
                $critere .= " AND id_pharmacie NOT IN (SELECT id_pharmacie FROM groupe_has_pharmacies)";
            }
        }

        if (isset($queryParams["groupe"]) && !empty($queryParams["groupe"])) {
            $groupe = (int)$queryParams["groupe"];
            $critere .= " AND id_pharmacie IN (SELECT id_pharmacie FROM groupe_has_pharmacies WHERE id_groupe= '$groupe') ";
        }

        if (isset($queryParams["coordonnees"]) && !empty($queryParams["coordonnees"])) {
            $coordonnees = strtolower($queryParams["coordonnees"]);
            $critere .= " AND LOWER(coordonnees) LIKE '%$coordonnees%'";
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

    public function addAdmin(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $params = $request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id ?? null;
        $params["createdBy"] = $params["userLogged"]["user"]->id ?? null;
        unset($params["userLogged"]);
        $this->validateAdmin($params);
        $admin = (new PharmacieRepository)->addAdmin($id, $params);
        return $this->jsonResponseWithData($response, "success", "Admin créé avec succès(password:Default2024)", $admin, 200);
    }

    public function updateAdmin(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id ?? null;
        $params["updatedAt"] = date("Y-m-d H:i:s");
        unset($params["userLogged"]);
        $this->validateAdmin($params);
        $repository = new PharmacieRepository;
        $admin = $repository->updateAdmin($id, $params);
        return $this->jsonResponseWithData($response, "success", "Admin modifiée avec succès", $admin, 200);
    }

    public function getAdmin(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $admin = (new PharmacieRepository)->getAdmin($id);
        return $this->jsonResponseWithoutMessage($response, 'success', $admin, 200);
    }

    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $action = (new PharmacieRepository)->getOne($id);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    public function setStatus(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $params = $request->getParsedBody();
        $this->required($params, "status", new PharmacieException("status est obligatoire"));
        if (isset($params["status"])) {
            if (is_null($params["status"])) throw new PharmacieException("status ne peut être vide.");
            if (!is_numeric($params["status"])) throw new PharmacieException("status doit être un nombre entier.");
            if ($params["status"] != 0 && $params["status"] != 1) throw new PharmacieException("status doit être 0:inactif ou 1:actif.");
        }
        $profil = (new PharmacieRepository)->setStatus($id, $params["status"]);
        return $this->jsonResponseWithData($response, "success", "Statut mis à jour avec succès.", $profil, 200);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        (new PharmacieRepository)->delete($id);
        return $this->jsonResponse($response, 'success', "Pharmacie supprimée avec succès", 200);
    }

    private function validateInsert($params)
    {
        $this->required($params, "nom", new PharmacieException("nom est obligatoire."));
        $this->required($params, "telephone", new PharmacieException("telephone est obligatoire."));
        $this->required($params, "adresse", new PharmacieException("adresse est obligatoire."));
        //$this->required($params, "coordonnees", new PharmacieException("coordonnees est obligatoire."));
        $this->validateUpdate($params);
    }

    private function validateUpdate($params)
    {
        if (isset($params["nom"])) {
            if (strlen($params["nom"]) < 3) throw new PharmacieException("nom doit avoir au moins 3 caractères.");
        }
        if (isset($params["telephone"])) {
            if (strlen($params["telephone"]) != 8) throw new PharmacieException("telephone doit avoir  8 caractères.");
            if (!is_numeric($params["telephone"])) throw new PharmacieException("telephone au mauvais format.");
        }
        if (isset($params["adresse"])) {
            if (strlen($params["adresse"]) < 2) throw new PharmacieException("adresse doit avoir au moins 2 caractères.");
        }
    }

    private function  validateAdmin($params)
    {
        if (isset($params["nom"])) {
            if (strlen($params["nom"]) < 3) throw new PharmacieException("nom doit avoir au moins 3 caractères.");
        }
        if (isset($params["prenom"])) {
            if (strlen($params["prenom"]) < 3) throw new PharmacieException("prenom doit avoir au moins 3 caractères.");
        }
        if (isset($params["login"]) && !is_null($params["login"])) {
            if (!is_string($params["login"])) throw new PharmacieException("login doit être une chaine de caractère");
            if (strlen($params["login"]) <= 2) throw new PharmacieException("login doit avoir au moins 3 caractères");
            if (!preg_match("/^([a-z]+)+$/", $params["login"])) throw new PharmacieException("login ne doit comporter que des les lettre de a à z sans accent.");
        }
    }
}
