<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\Auth as ExceptionAuth;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;
use stdClass;

final class Auth extends Base
{
    public function __invoke(
        Request $request,
        Response $response,
        Route $next
    ): ResponseInterface {
        $jwtHeader = $request->getHeaderLine('Authorization');
        if (!$jwtHeader) {
            throw new ExceptionAuth('JWT Token required.', 400);
        }
        $jwt = explode('Bearer ', $jwtHeader);

        if (!isset($jwt[1])) {
            throw new ExceptionAuth('JWT Token invalid.', 400);
        }
        $decoded = (array)$this->checkToken($jwt[1]);
        $object = (array) $request->getParsedBody();

        $object['userLogged'] = $decoded;
        $method = strtolower($request->getMethod());
        $url = $request->getUri()->getPath();
        $urlToMatch = new stdClass;
        $urlToMatch->url = $this->urlAdapter($url);
        $urlToMatch->methode = $method;
        $permission_accordee = (bool)array_search($urlToMatch, $decoded["user"]->actions);
        if (!$permission_accordee && $permission_accordee!=0) {
            throw new ExceptionAuth('Forbidden: Accès interdit à cette url.', 403);
        }
        return $next($request->withParsedBody($object), $response);
    }

    private function urlAdapter($url)
    {
        $arrray = explode('/', $url);
        if (is_numeric($arrray[count($arrray) - 1])) {
            unset($arrray[count($arrray) - 1]);
            return  join("/", $arrray);
        } else {
            return $url;
        }
    }
}
