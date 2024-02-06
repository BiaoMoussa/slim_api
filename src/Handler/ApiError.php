<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;

final class ApiError extends \Slim\Handlers\Error
{

    public function __invoke(
        Request $request,
        Response $response,
        \Exception $exception
    ): Response {
        $statusCode = $this->getStatusCode($exception);
        $className = new \ReflectionClass(get_class($exception));
        $data = ($_SERVER["DISPLAY_ERROR_DETAILS_JSON_WITOUT_CLASS_PATH"]=='true') ?[
            'message' => $exception->getMessage(),
            'status' => 'error',
            'code' => $statusCode,
        ]:[
            'message' => $exception->getMessage(),
            'class' => $className->getName(),
            'status' => 'error',
            'code' => $statusCode,
        ];
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $response->getBody()->write((string) $body);

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-type', 'application/json');
    }
    public function getStatusCode(\Exception $exception): int
    {
        $statusCode = 500;
        if (is_int($exception->getCode()) &&
            $exception->getCode() >= 400 &&
            $exception->getCode() <= 500
        ) {
            $statusCode = $exception->getCode();
        }

        return $statusCode;
    }
}
