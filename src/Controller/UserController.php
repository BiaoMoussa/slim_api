<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\UserException;
use App\Repository\UserRepository;

use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

class UserController extends BaseController
{

    public function login(Request $request, Response $response): Response
    {
        $input = $request->getParsedBody();
        $data = json_decode((string) json_encode($input), false);
        if (!isset($data->login)) {
            throw new UserException('login est obligatoire.', 400);
        }
        if (!isset($data->password)) {
            throw new UserException('password est required.', 400);
        }

        if (is_null($data->login) || empty($data->login)) {
            throw new UserException('login ne peut être vide.', 400);
        }
        if (is_null($data->password) || empty($data->password)) {
            throw new UserException('password ne peut être vide.', 400);
        }

        $userRepository = new UserRepository;

        $user = $userRepository->login((array)$input);

        $token = [
            'user' => $user,
            'iat' => time(),
            'exp' => time() + (int)$_SERVER["TOKEN_EXPIRE"]*60,
        ];

        $token =  JWT::encode($token, $_SERVER['SECRET_KEY'],"HS512");
        $message = ['Authorization' =>  $token, "expires"=>(int)$_SERVER["TOKEN_EXPIRE"]*60];

        return $this->jsonResponse($response, 'succes', $message, 200);
    }

    public function getAll(Request $request, Response $response): Response
    {
        $queryParams = (array)$request->getQueryParams();
        $userRepository = new UserRepository;
        $params  = (array)$request->getParsedBody();
        $pharmacie =   $params["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_pharmacie='$pharmacie'";
        if (isset($queryParams["id"]) && !is_null($queryParams["id"])) {
            $idUser = $queryParams["id"];
            $critere .= "AND id_user='$idUser' ";
        }
        if (isset($queryParams["profil"]) && !is_null($queryParams["profil"])) {
            $profilUser = $queryParams["profil"];
            $critere .= "AND id_profil='$profilUser' ";
        }
        if (isset($queryParams["nom"]) && !is_null($queryParams["nom"])) {
            $nomUser = strtolower($queryParams["nom"]);
            $critere .= "AND LOWER(nom_user) LIKE '%$nomUser%' ";
        }
        if (isset($queryParams["prenom"]) && !is_null($queryParams["prenom"])) {
            $prenomUser = strtolower($queryParams["prenom"]);
            $critere .= "AND LOWER(prenom_user) LIKE '%$prenomUser%' ";
        }
        if (isset($queryParams["login"]) && !is_null($queryParams["login"])) {
            $loginUser = strtolower($queryParams["login"]);
            $critere .= "AND LOWER(login) LIKE '%$loginUser%' ";
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
        $params  = (array)$request->getParsedBody();
        $pharmacie =   $params["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_pharmacie='$pharmacie'";
        $action = (new UserRepository)->getOne($id,$critere);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    public function add(Request $request, Response $response): Response
    {
        $params  = (array)$request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id??null;
        $params["createdBy"] = $params["userLogged"]["user"]->id??null;
        $params["pharmacie"] = $params["userLogged"]["user"]->pharmacie??null;
        unset($params["userLogged"]);
        $this->validateInsert($params);
        $repository = new UserRepository;
        $user = $repository->insert($params);
        return $this->jsonResponseWithData($response, 'success', "User créé avec succès.", $user, 200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = (array)$request->getParsedBody();
        $params["updatedBy"] = $params["userLogged"]["user"]->id??null;
        $params["updatedAt"] = date("Y-m-d H:i:s");
        $params["pharmacie"] = $params["userLogged"]["user"]->pharmacie??null;
        unset($params["userLogged"]);
        $this->validateUpdate($params);
        $repository = new UserRepository;
        $user = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, 'success', "User modifiée avec succès.", $user, 200);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $params  = (array)$request->getParsedBody();
        $pharmacie =   $params["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_pharmacie='$pharmacie'";
        (new UserRepository)->delete($id,$critere);
        return $this->jsonResponse($response, 'success', "User supprimé avec succès", 200);
    }

    public function changePassword(Request $request, Response $response): Response
    {
        $parsedBody = (array)$request->getParsedBody();
        $this->required($parsedBody, "id", new UserException("id est obligatoire."));
        $this->required($parsedBody, "oldPassword", new UserException("oldPassword est obligatoire."));
        $this->required($parsedBody, "newPassword", new UserException("newPassword est obligatoire."));
        if (isset($parsedBody["id"]) && !is_null($parsedBody["id"])) {
            if (!is_numeric($parsedBody["id"])) throw new UserException("id doit être en nombre entier.");
        }
        if (isset($parsedBody["oldPassword"]) && !is_null($parsedBody["oldPassword"])) {
            if (!is_string($parsedBody["oldPassword"])) throw new UserException("oldPassword doit être une chaine de caractère");
            if (strlen($parsedBody["oldPassword"]) < 6) throw new UserException("oldPassword doit avoir au moins 6 caractères");
        }
        if (isset($parsedBody["newPassword"]) && !is_null($parsedBody["newPassword"])) {
            if (!is_string($parsedBody["newPassword"])) throw new UserException("newPassword doit être une chaine de caractère");
            if (strlen($parsedBody["newPassword"]) < 6) throw new UserException("newPassword doit avoir au moins 6 caractères");
        }

        $repository = new UserRepository;
        $repository->changePassowrd($parsedBody["id"], $parsedBody["oldPassword"], $parsedBody["newPassword"]);
        return $this->jsonResponse($response, "success", "Mot de passe changé avec succès.", 200);
    }

    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $message =  (new UserRepository)->resetPassword($id);
        return $this->jsonResponse($response, "success", $message, 200);
    }
    private function validateInsert($params)
    {
        $this->required($params, "nom", new UserException("nom est obligatoire."));
        $this->required($params, "prenom", new UserException("prenom est obligatoire."));
        $this->required($params, "login", new UserException("login est obligatoire."));
        $this->required($params, "password", new UserException("password est obligatoire."));
        $this->required($params, "profil", new UserException("profil est obligatoire."));
        $this->validateUpdate($params);
    }

    private function validateUpdate($params)
    {
        if (isset($params["nom"]) && !is_null($params["nom"])) {
            if (!is_string($params["nom"])) throw new UserException("nom doit être une chaine de caractère");
            if (strlen($params["nom"]) <= 1) throw new UserException("nom doit avoir au moins 2 caractères");
        }
        if (isset($params["prenom"]) && !is_null($params["prenom"])) {
            if (!is_string($params["prenom"])) throw new UserException("prenom doit être une chaine de caractère");
            if (strlen($params["prenom"]) <= 1) throw new UserException("prenom doit avoir au moins 2 caractères");
        }

        if (isset($params["login"]) && !is_null($params["login"])) {
            if (!is_string($params["login"])) throw new UserException("login doit être une chaine de caractère");
            if (strlen($params["login"]) <= 2) throw new UserException("login doit avoir au moins 3 caractères");
            if (!preg_match("/^([a-z]+)+$/", $params["login"])) throw new UserException("login ne doit comporter que des les lettre de a à z sans accent.");
        }

        if (isset($params["profil"]) && !is_null($params["profil"])) {
            if (!is_int($params["profil"])) throw new UserException("profil doit être un entier.");
        }

        if (isset($params["password"]) && !is_null($params["password"])) {
            if (!is_string($params["password"])) throw new UserException("password doit être une chaine de caractère");
            if (strlen($params["password"]) < 6) throw new UserException("password doit avoir au moins 6 caractères");
            // if(!preg_match("/^([a-z]+[A-Z]+[0-9]+)+$/", $params["login"])) throw new UserException("password doit comporter au moins une lettre majuscule, une lettre MAJUSCULE et un chiffre");
        }
    }
}
