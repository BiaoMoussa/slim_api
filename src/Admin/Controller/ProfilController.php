<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\ProfilException;
use App\Admin\Repository\ProfilRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class ProfilController extends BaseController
{
    public function add(Request $request, Response $response): Response
    {
        $params  = $request->getParsedBody();
        $this->validate($params);
        $repository = new ProfilRepository;
        $action = $repository->insert($params);
        return $this->jsonResponseWithData($response, "success", "Action ajoutée avec succès", $action, 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = $request->getParsedBody();
        $this->validate($params);
        $repository = new ProfilRepository;
        $action = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, "success", "Action modifiée avec succès", $action, 200);
    }

    public function getAll(Request $request, Response $response): Response
    {

        $queryParams = $request->getQueryParams();
        $repository = new ProfilRepository;
        $critere = "true ";
        if (isset($queryParams["id"]) && !empty($queryParams["id"])) {
            $id = $queryParams["id"];
            $critere .= " AND id_profil='$id'";
        }
        if (isset($queryParams["libelle"]) && !empty($queryParams["libelle"])) {
            $libelle = strtolower($queryParams["libelle"]);
            $critere .= " AND LOWER(libelle_profil) LIKE '%$libelle%'";
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
        $action = (new ProfilRepository)->getOne($id);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        (new ProfilRepository)->delete($id);
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
        $actions = (new ProfilRepository)->addActions($id, $actions);
        return $this->jsonResponseWithData($response, "success", $message, $actions, 200);
    }

    public function deleteActions(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $parsedBody = (array)$request->getParsedBody();
        $this->validateActionsSaving($parsedBody);
        $actions = (array)$parsedBody["actions"];
        $message = count($actions) > 1 ? "actions ajoutées avec succès" : "action ajoutée avec succès";
        $actions = (new ProfilRepository)->deleteActions($id, $actions);
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
