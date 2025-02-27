<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\ActionException;
use App\Admin\Repository\ActionRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 */
class ActionController extends BaseController
{

    /**
     * @var string[]
     */
    protected $methodes = ["get", "post", "put", "delete"];

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ActionException
     */
    public function add(Request $request, Response $response): Response
    {
        $params  = $request->getParsedBody();
        unset($params["userLogged"]);
        $this->validate($params);
        $repository = new ActionRepository;
        $action = $repository->insert($params);
        return $this->jsonResponseWithData($response, "success", "Action ajoutée avec succès", $action, 201);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ActionException
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $params  = $request->getParsedBody();
        unset($params["userLogged"]);
        $this->validateUpdate($params);
        $repository = new ActionRepository;
        $action = $repository->update($id, $params);
        return $this->jsonResponseWithData($response, "success", "Action modifiée avec succès", $action, 200);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getAll(Request $request, Response $response): Response
    {

        $queryParams = $request->getQueryParams();
        $repository = new ActionRepository;
        $critere = "true ";
        if (isset($queryParams["id"]) && !empty($queryParams["id"])) {
            $id = $queryParams["id"];
            $critere .= " AND id_action='$id'";
        }
        if (isset($queryParams["libelle"]) && !empty($queryParams["libelle"])) {
            $libelle = strtolower($queryParams["libelle"]);
            $critere .= " AND LOWER(libelle_action) LIKE '%$libelle%'";
        }

        if (isset($queryParams["url"]) && !empty($queryParams["url"])) {
            $url = strtolower($queryParams["url"]);
            $critere .= " AND LOWER(url_action) LIKE '%$url$'";
        }

        if (isset($queryParams["methode"]) && !empty($queryParams["methode"])) {
            $methode = strtolower($queryParams["methode"]);
            $critere .= " AND LOWER(methode) LIKE '%$methode%'";
        }

        if (isset($queryParams["isMenu"])) {
            $isMenu = intval($queryParams["isMenu"]);
            $critere .= " AND is_menu = $isMenu";
        }

        if (isset($queryParams["parent"])) {
            $parent = intval($queryParams["parent"]);
            $critere .= " AND parent = $parent";
        }

        if (isset($queryParams["description"]) && !empty($queryParams["description"])) {
            $description = strtolower($queryParams["description"]);
            $critere .= " AND LOWER(description_action) LIKE '%$description%'";
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

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ActionException
     */
    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = $args["id"];
        $action = (new ActionRepository)->getOne($id);
        return $this->jsonResponseWithoutMessage($response, 'success', $action, 200);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ActionException
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        (new ActionRepository)->delete($id);
        return $this->jsonResponse($response, 'success', "Action supprimée avec succès", 200);
    }

    /**
     * @param string $method
     * @return void
     * @throws ActionException
     */
    protected function checkMethod(string $method): void
    {
        if (!in_array($method, $this->methodes)) {
            throw new ActionException('Methode doit être une de: ' . join('|', $this->methodes));
        }
    }

    /**
     * @param string $url
     * @return void
     * @throws ActionException
     */
    protected function checkUrl(string $url): void
    {
        if (!is_int(strpos($url, '/'))) {
            throw new ActionException('url doit comporter au moins un /');
        }
    }

    /**
     * @param $params
     * @return void
     * @throws ActionException
     */
    private function validate($params)
    {
        $this->required($params, "libelle", new ActionException("libelle est obligatoire"));
        $this->required($params, "url", new ActionException("url est obligatoire"));
        $this->required($params, "methode", new ActionException("methode est obligatoire"));
        if (strlen($params["libelle"]) < 3) throw new ActionException("libelle doit comporter au moins 3 lettres");
        if (strlen($params["url"]) < 3) throw new ActionException("url doit comporter au moins 3 lettres");
        if (strlen($params["methode"]) < 3) throw new ActionException("methode doit comporter au moins 3 lettres");
        $this->checkMethod($params["methode"]);
    }


    /**
     * @param $params
     * @return void
     * @throws ActionException
     */
    private function validateUpdate($params): void
    {
        if (isset($params["libelle"]) && !is_null($params["libelle"])) {
            if (strlen($params["libelle"]) < 3) throw new ActionException("libelle doit comporter au moins 3 lettres");
        }
        if (isset($params["url"]) && !is_null($params["url"])){
            if (strlen($params["url"]) < 3) throw new ActionException("url doit comporter au moins 3 lettres");
        }
        if (isset($params["methode"]) && !is_null($params["methode"])){
            if (strlen($params["methode"]) < 3) throw new ActionException("methode doit comporter au moins 3 lettres");
        }
        if (isset($params["methode"])) $this->checkMethod($params["methode"]);
    }
}
