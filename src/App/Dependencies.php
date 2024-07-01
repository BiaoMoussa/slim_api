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
$pdo = new PDO($dsn, $database['user'], $database['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND,"SET time_zone = 'Africa/Niamey'");
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



