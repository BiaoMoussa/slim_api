<?php
declare(strict_types=1);
use App\Handler\ApiError;

$errorHandler = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $statusCode = (new ApiError)->getStatusCode($exception);
        $className = new \ReflectionClass(get_class($exception));
        $data = [
            'message' => $exception->getMessage(),
            'class' => $className->getName(),
            'status' => 'error',
            'code' => $statusCode,
        ];
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $response->getBody()->write((string) $body);

        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-type', 'application/json+problem');
    };
};
$settings = [
    'settings' => [
        'displayErrorDetails' => filter_var($_SERVER['DISPLAY_ERROR_DETAILS'], FILTER_VALIDATE_BOOLEAN),
        'db' => [
            'host' => $_SERVER['DB_HOST'],
            'name' => $_SERVER['DB_NAME'],
            'user' => $_SERVER['DB_USER'],
            'pass' => $_SERVER['DB_PASS'],
            'port' => $_SERVER['DB_PORT'],
        ],
        'redis' => [
            'enabled' => $_SERVER['REDIS_ENABLED'],
            'url' => $_SERVER['REDIS_URL'],
        ],
        'app' => [
            'domain' => $_SERVER['APP_DOMAIN'],
            'secret' => $_SERVER['SECRET_KEY'],
        ],
    ],
];

if($_SERVER['DISPLAY_ERROR_DETAILS_JSON']=='true'){
  $settings['errorHandler'] = $errorHandler;
}else{
   unset($settings['errorHandler']);
}

return $settings;

