<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\ActionException;
use App\Admin\Repository\ActionRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class ActionController extends BaseController
{
    
    protected $metodes = ["get","post","put","delete"];
    public function add(Request $request, Response $response): Response
    {
        $params  = $request->getParsedBody();
        $this->validate($params);
        $repository = new ActionRepository;
        $action = $repository->insert($params);
        return $this->jsonResponseWithData($response, "success", "Action ajoutée avec succès", $action, 201);
    }

    public function update(Request $request, Response $response,array $args): Response
    {
        $id = $args["id"];
        $params  = $request->getParsedBody();
        $this->validate($params);
        $repository = new ActionRepository;
        $action = $repository->update($id,$params);
        return $this->jsonResponseWithData($response, "success", "Action modifiée avec succès", $action, 200);
    }

    public function getAll(Request $request, Response $response): Response
    {

        $queryParams = $request->getQueryParams();
        $repository = new ActionRepository;
        $critere = "true ";
        if(isset($queryParams["id"]) && !empty($queryParams["id"])){
            $id = $queryParams["id"];
            $critere .= " AND id_action='$id'";
        }
        if(isset($queryParams["libelle"]) && !empty($queryParams["libelle"])){
            $libelle = strtolower($queryParams["libelle"]);
            $critere .= " AND LOWER(libelle_action) LIKE '%$libelle%'";
        }

        if(isset($queryParams["url"]) && !empty($queryParams["url"])){
            $url = strtolower($queryParams["url"]);
            $critere .= " AND LOWER(url_action) LIKE '%$url$'";
        }

        if(isset($queryParams["methode"]) && !empty($queryParams["methode"])){
            $methode = strtolower($queryParams["methode"]);
            $critere .= " AND LOWER(methode) LIKE '%$methode%'";
        }
        if(isset($queryParams["description"]) && !empty($queryParams["description"])){
            $description = strtolower($queryParams["description"]);
            $critere .= " AND LOWER(description_action) LIKE '%$description%'";
        }
        if(isset($queryParams["perPage"]) && !empty($queryParams["perPage"])){
            $perPage = (int)$queryParams["perPage"];
        }else{
            $perPage = 10;
        }

        if(isset($queryParams["page"]) && !empty($queryParams["page"])){
            $page = (int)$queryParams["page"];
        }else{
            $page = 1;
        }
        $actions = $repository->getAll($critere,$page,$perPage);
        return $this->jsonResponseWithoutMessage($response,'success', $actions,200);
    }

    public function getOne(Request $request, Response $response, array $args): Response{
        $id = $args["id"];
        $action = (new ActionRepository)->getOne($id);
        return $this->jsonResponseWithoutMessage($response,'success', $action,200);
    }

    public function delete(Request $request, Response $response,array $args): Response{
        $id = $args['id'];
        (new ActionRepository)->delete($id);
        return $this->jsonResponse($response,'success', "Action supprimée avec succès",200);
    }

    protected function checkMethod(string $method){
        if(!in_array($method,$this->metodes)){
            throw new ActionException('Methode doit être une de: '.join('|',$this->metodes));
        }
    }

    protected function checkUrl(string $url){
        if(!is_int(strpos($url,'/'))){
            throw new ActionException('url doit comporter au moins un /');
        }
    }

    private function validate($params){
        $this->required($params, "libelle", new ActionException("libelle est obligatoire"));
        $this->required($params, "url", new ActionException("url est obligatoire"));
        $this->required($params, "methode", new ActionException("methode est obligatoire"));
        if(strlen($params["libelle"])<3) new ActionException("libelle doit comporter au moins 3 lettres");
        if(strlen($params["url"])<3) new ActionException("url doit comporter au moins 3 lettres");
        if(strlen($params["methode"])<3) new ActionException("methode doit comporter au moins 3 lettres");
        $this->checkMethod($params["methode"]);
    }
}
