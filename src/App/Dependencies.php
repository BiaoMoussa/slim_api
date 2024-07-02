<?php

declare(strict_types=1);

use App\Handler\ApiError;
use App\Service\RedisService;
use Slim\Exception\NotFoundException;

$database = $container->get('settings')['db'];
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;port=%s;charset=utf8',
    $database['host'],
    $database['name'],
    $database['port']
);
$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = 'Africa/Niamey'"
);
$pdo = new PDO($dsn, $database['user'], $database['pass'], $options);

$GLOBALS["pdo"] = $pdo;
$GLOBALS["errorHandler"]=static fn (): ApiError => new ApiError();
$container = $GLOBALS["errorHandler"];
$GLOBALS["notFoundHnadler"] = static function () {
    return static function ($request, $response): void {
        throw new NotFoundException($request, $response);
    };
};

$GLOBALS["redis_service"] =  static function ($container): RedisService {
    $redis = $container->get('settings')['redis'];
    return new RedisService(new \Predis\Client($redis['url']));
};



