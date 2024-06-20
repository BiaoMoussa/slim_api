<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\UserException;
use App\Admin\Repository\StatsRepository;
use App\Admin\Repository\UserRepository;

use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

class StatsController extends BaseController
{
    public function getAll(Request $request, Response $response): Response
    {
        $queryParams = (array)$request->getQueryParams();
        $statsRepository = new StatsRepository;
        $critere = " true ";
        

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

        $encodeJson = $statsRepository->getAll($critere, $page, $perPage);

        return $this->jsonResponseWithoutMessage($response, 'success', $encodeJson, 200);
    }


    public function getStat(Request $request, Response $response, array $args): Response
    {
        $pharmacie = $args['id'];
        $statsRepository = new StatsRepository;
    
        $encodeJson = $statsRepository->getStatistique($pharmacie);

        return $this->jsonResponseWithoutMessage($response, 'success', $encodeJson, 200);
    }
}
