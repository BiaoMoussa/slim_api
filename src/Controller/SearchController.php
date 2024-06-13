<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\SearchException;
use App\Exception\UserException;
use App\Repository\SearchRepository;
use App\Repository\UserRepository;

use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

class SearchController extends BaseController
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
        $pharmacie =   $params["userLogged"]["user"]->pharmacie??null;
        $critere = "true AND id_pharmacie='$pharmacie'";
        $action = (new UserRepository)->getOne($id,$critere);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
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
       
         array_unshift($result["content"],["nombre"=>count($result["content"])]);
        
        return $this->jsonResponseWithoutMessage($response, 'success', $result, 200);
    }


    private function validateInsert($params)
    {
        $this->required($params, "produit", new UserException("produit est obligatoire."));
        $this->validateUpdate($params);
    }

    private function validateUpdate($params)
    {
        if (isset($params["produit"]) && !is_null($params["produit"])) {
            if (!is_numeric($params["produit"])) throw new SearchException("produit doit être un entier");
        }
        if (isset($params["position"]) && !is_null($params["position"])) {
            $this->isGeolocationCoordinatesValid($params["position"], new SearchException("Position n'est pas validate. Veuillez donner des coordonnées valides."));
        }
       
    }
}
