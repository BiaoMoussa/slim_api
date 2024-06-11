<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ProfilException;
use App\Repository\ProfilRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class ProfilController extends BaseController
{
    public function add(Request $request, Response $response): Response
    {
        $params  = $request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id??null;
        $params["createdBy"] = $params["userLogged"]["user"]->id??null;
        $params["pharmacie"] = $params["userLogged"]["user"]->pharmacie??null;
        unset($params["userLogged"]);
        $this->validate($params);
        $repository = new ProfilRepository;
        $action = $repository->insert($params);
        return $this->jsonResponseWithData($response, "success", "Action ajoutée avec succès", $action, 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id??null;
        $params["updatedAt"] = date("Y-m-d H:i:s");
        $params["pharmacie"] = $params["userLogged"]["user"]->pharmacie??null;
        unset($params["userLogged"]);
        $this->validate($params);
        $repository = new ProfilRepository;
        $action = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, "success", "Action modifiée avec succès", $action, 200);
    }

    public function getAll(Request $request, Response $response): Response
    {

        $queryParams = $request->getQueryParams();
        $repository = new ProfilRepository;
        $params  = (array)$request->getParsedBody();
        $pharamcie =   $params["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_societe='$pharamcie'";
        if (isset($queryParams["id"]) && !empty($queryParams["id"])) {
            $id = $queryParams["id"];
            $critere .= " AND id_profil='$id'";
        }
        if (isset($queryParams["libelle"]) && !empty($queryParams["libelle"])) {
            $libelle = strtolower($queryParams["libelle"]);
            $critere .= " AND LOWER(libelle_profil) LIKE '%$libelle%'";
        }

        if (isset($queryParams["statut"]) && ($queryParams["statut"]=="1" || $queryParams["statut"]=="0")) {
            $statut = (int)$queryParams["statut"];
            $critere .= " AND statut='$statut' ";
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
        $actions = $repository->getAll($critere, $page, $perPage);
        return $this->jsonResponseWithoutMessage($response, 'success', $actions, 200);
    }

    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $pharamcie =   $params["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_societe='$pharamcie'";
        $action = (new ProfilRepository)->getOne($id, $critere);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    public function setStatus(Request $request, Response $response, array $args): Response{
        $id = $args['id'];
        $params = $request->getParsedBody();
        $pharamcie =   $params["userLogged"]["user"]->pharmacie??null;
        $this->required($params,"status",new ProfilException("status est obligatoire"));
        if(isset($params["status"])){
            if(is_null($params["status"])) throw new ProfilException("status ne peut être vide.");
            if(!is_numeric($params["status"])) throw new ProfilException("status doit être un nombre entier.");
            if($params["status"]!=0 && $params["status"]!=1) throw new ProfilException("status doit être 0:inactif ou 1:actif.");
        }
        $profil = (new ProfilRepository)->setStatus($id, ["status"=>$params["status"],"pharmacie"=>$pharamcie]);
        return $this->jsonResponseWithData($response,"success","Statut mis à jour avec succès." ,$profil,200);
    }
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $params  = (array)$request->getParsedBody();
        $pharamcie =   $params["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_societe='$pharamcie'";
        (new ProfilRepository)->delete($id,$critere);
        return $this->jsonResponse($response, 'success', "Profil supprimé avec succès", 200);
    }

    private function validate($params)
    {
        $this->required($params, "libelle", new ProfilException("libelle est obligatoire"));
        if (strlen($params["libelle"]) < 3) throw new ProfilException("libelle doit comporter au moins 3 lettres");
    }


    public function getProfilActions(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $actions = (new ProfilRepository)->getActions($id);
        return $this->jsonResponseWithoutMessage($response, "success", $actions, 200);
    }

    public function addActions(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $parsedBody = (array)$request->getParsedBody();
        $this->validateActionsSaving($parsedBody);
        $actions = (array)$parsedBody["actions"];
        $message = count($actions) > 1 ? "actions ajoutées avec succès" : "action ajoutée avec succès";
        $pharamcie =   $parsedBody["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_societe='$pharamcie'";
        $actions = (new ProfilRepository)->addActions($id, $actions,$critere);
        return $this->jsonResponseWithData($response, "success", $message, $actions, 200);
    }

    public function deleteActions(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $parsedBody = (array)$request->getParsedBody();
        $this->validateActionsSaving($parsedBody);
        $actions = (array)$parsedBody["actions"];
        $message = count($actions) > 1 ? "actions ajoutées avec succès" : "action ajoutée avec succès";
        $pharamcie =   $parsedBody["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_societe='$pharamcie'";
        $actions = (new ProfilRepository)->deleteActions($id, $actions,$critere);
        return $this->jsonResponseWithData($response, "success", $message, $actions, 200);
    }

    private function validateActionsSaving($parsedBody)
    {
        $this->required($parsedBody, "actions", new ProfilException("actions est obligatoire"));
        if (empty($parsedBody)) throw new ProfilException("actions ne peut être vide");
        foreach ($parsedBody["actions"] as  $value) {
            if (!is_int($value)) throw new ProfilException("Les éléments de actions actions sont des entiers.");
        }
    }
}
