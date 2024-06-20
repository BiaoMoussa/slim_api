<?php

declare(strict_types=1);

namespace App\Controller;


use App\Exception\SearchException;
use App\Repository\SearchRepository;
use App\Repository\UserRepository;

use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

class SearchController extends BaseController
{
    /**
     * Authentification d'un client
     */
    public function signin(Request $request, Response $response): Response
    {
        $input = $request->getParsedBody();
        $data = json_decode((string) json_encode($input), false);
        if (!isset($data->numero)) {
            throw new SearchException('numero est obligatoire.', 400);
        }
        if (!isset($data->password)) {
            throw new SearchException('password est obligatoire.', 400);
        }

        if (is_null($data->numero) || empty($data->numero)) {
            throw new SearchException('numero ne peut être vide.', 400);
        }
        if (is_null($data->password) || empty($data->password)) {
            throw new SearchException('password ne peut être vide.', 400);
        }

        $searchRepository = new SearchRepository();

        $compte = $searchRepository->siginin((array)$input);
       
        $token = [
            'compte' => $compte,
            'iat' => time(),
            'exp' => time() + (int)$_SERVER["TOKEN_EXPIRE"] * 60,
        ];

        $token =  JWT::encode($token, $_SERVER['SECRET_KEY'], "HS512");
        $message = ['Authorization' =>  $token, "expires" => (int)$_SERVER["TOKEN_EXPIRE"] * 60];

        return $this->jsonResponse($response, 'succes', $message, 200);
    }

    /**
     * Inscription
     */
    public function signup(Request $request, Response $response, array $args){
        $params  = (array)$request->getParsedBody();
        $this->validateSignup($params);
       $compte = (new SearchRepository())->signup($params);
       return $this->jsonResponseWithData($response, 'success', "Compte créé avec succès.", $compte, 200);
    }
    public function getAll(Request $request, Response $response): Response
    {
        $queryParams = (array)$request->getQueryParams();
        $userRepository = new UserRepository;
        $params  = (array)$request->getParsedBody();
        $pharmacie =   $params["userLogged"]["user"]->pharmacie ?? null;
        $critere = "true AND id_pharmacie='$pharmacie'";
        if (isset($queryParams["id"]) && !is_null($queryParams["id"])) {
            $idUser = $queryParams["id"];
            $critere .= "AND users.id_user='$idUser' ";
        }
        if (isset($queryParams["profil"]) && !is_null($queryParams["profil"])) {
            $profilUser = $queryParams["profil"];
            $critere .= "AND users.id_profil='$profilUser' ";
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
        $pharmacie =   $params["userLogged"]["user"]->pharmacie ?? null;
        $critere = "true AND id_pharmacie='$pharmacie'";
        $action = (new UserRepository)->getOne($id, $critere);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }


    public function profil(Request $request, Response $response, array $args)
    {
        $params  = (array)$request->getParsedBody();
        $numero =   $params["connectedAccount"]->numero_telephone;
        $action = (new SearchRepository())->getOneAccount($numero);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    

    public function updateAccount(Request $request, Response $response, array $args){
        $params  = (array)$request->getParsedBody();
        $numero =   $params["connectedAccount"]->numero_telephone;
        $params["numero"] = $numero;
        $this->validateUpdateAccount($params);
       $compte = (new SearchRepository())->updateAccount($params);
       return $this->jsonResponseWithData($response, 'success', "Compte mis à jour avec succès.", $compte, 200);
    }

    public function make(Request $request, Response $response): Response
    {
        $queryParams = (array)$request->getQueryParams();
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
        $params  = (array)$request->getParsedBody();
        // $params["updatedBy"] = $params["userLogged"]["user"]->id??null;
        // $params["createdBy"] = $params["userLogged"]["user"]->id??null;
        // $params["pharmacie"] = $params["userLogged"]["user"]->pharmacie??null;
        // unset($params["userLogged"]);
        $this->validateInsert($params);
        $repository = new SearchRepository();
        $result = $repository->insert($params, $page, $perPage);

       

        return $this->jsonResponseWithoutMessage($response, 'success', $result, 200);
    }

    /**
     * Historique de recherche d'un client
     */
    public function histories(Request $request, Response $response){
        $params  = (array)$request->getParsedBody();
        $numero =   $params["connectedAccount"]->numero_telephone;
        $params["numero"] = $numero;
       $recherches = (new SearchRepository())->getSearchHistories($numero);
        return $this->jsonResponseWithoutMessage($response, 'success', $recherches, 200);
    }

    private function validateInsert($params)
    {
        $this->required($params, "produit", new SearchException("produit est obligatoire."));
        $this->validateUpdate($params);
    }


    private function validateUpdate($params)
    {
        if (isset($params["produit"]) && !is_null($params["produit"])) {
            if (!is_string($params["produit"])) throw new SearchException("produit doit être un entier");
        }
        if (isset($params["position"]) && !is_null($params["position"])) {
            $this->isGeolocationCoordinatesValid($params["position"], new SearchException("Position n'est pas validate. Veuillez donner des coordonnées valides."));
        }
    }


    public function changePassword(Request $request, Response $response): Response
    {
        $parsedBody = (array)$request->getParsedBody();
        
        $this->required($parsedBody, "oldPassword", new SearchException("oldPassword est obligatoire."));
        $this->required($parsedBody, "newPassword", new SearchException("newPassword est obligatoire."));
       
        if (isset($parsedBody["oldPassword"]) && !is_null($parsedBody["oldPassword"])) {
            if (!is_string($parsedBody["oldPassword"])) throw new SearchException("oldPassword doit être une chaine de caractère");
            if (strlen($parsedBody["oldPassword"]) < 6) throw new SearchException("oldPassword doit avoir au moins 6 caractères");
        }
        if (isset($parsedBody["newPassword"]) && !is_null($parsedBody["newPassword"])) {
            if (!is_string($parsedBody["newPassword"])) throw new SearchException("newPassword doit être une chaine de caractère");
            if (strlen($parsedBody["newPassword"]) < 6) throw new SearchException("newPassword doit avoir au moins 6 caractères");
        }
        $numero =   $parsedBody["connectedAccount"]->numero_telephone;
        $repository = new SearchRepository;
        $repository->changePassowrd($numero, $parsedBody["oldPassword"], $parsedBody["newPassword"]);
        return $this->jsonResponse($response, "success", "Mot de passe changé avec succès.", 200);
    }


    private function validateSignup($params)
    {
        $this->required($params, "numero", new SearchException("Le numero est obligatoire."));
        $this->required($params, "password", new SearchException("Le mot de passe est obligatoire."));
        $this->validateUpdateSignup($params);
    }
    private function validateUpdateSignup($params)
    {
        
        if (isset($params["numero"]) && !is_null($params["numero"])) {
            $this->validatePhoneNumber($params["numero"], new SearchException("Numero n'est pas validate. Veuillez donner un numero valide."));
        }

        if (isset($params["password"]) && !is_null($params["password"])) {
            if (!is_string($params["password"])) throw new SearchException("password doit être une chaine de caractère");
            if (strlen($params["password"]) < 6) throw new SearchException("password doit avoir au moins 6 caractères");
            // if(!preg_match("/^([a-z]+[A-Z]+[0-9]+)+$/", $params["login"])) throw new SearchException("password doit comporter au moins une lettre majuscule, une lettre MAJUSCULE et un chiffre");
        }

        if (isset($params["pays"]) && !is_null($params["pays"])) {
            if ($params["pays"]="NE") throw new SearchException("Le pays doit être NE");
        }

        if (isset($params["nom"]) && !is_null($params["nom"])) {
            if (!is_string($params["nom"])) throw new SearchException("nom doit être une chaine de caractère");
            if (strlen($params["nom"]) <= 1) throw new SearchException("nom doit avoir au moins 2 caractères");
        }
        if (isset($params["prenom"]) && !is_null($params["prenom"])) {
            if (!is_string($params["prenom"])) throw new SearchException("prenom doit être une chaine de caractère");
            if (strlen($params["prenom"]) <= 1) throw new SearchException("prenom doit avoir au moins 2 caractères");
        }
    }


    private function validateUpdateAccount($params)
    {

        if (isset($params["pays"]) && !is_null($params["pays"])) {
            if ($params["pays"]="NE") throw new SearchException("Le pays doit être NE");
        }

        if (isset($params["nom"]) && !is_null($params["nom"])) {
            if (!is_string($params["nom"])) throw new SearchException("nom doit être une chaine de caractère");
            if (strlen($params["nom"]) <= 1) throw new SearchException("nom doit avoir au moins 2 caractères");
        }
        if (isset($params["prenom"]) && !is_null($params["prenom"])) {
            if (!is_string($params["prenom"])) throw new SearchException("prenom doit être une chaine de caractère");
            if (strlen($params["prenom"]) <= 1) throw new SearchException("prenom doit avoir au moins 2 caractères");
        }
    }
}
