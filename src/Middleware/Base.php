<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\Auth;
use Firebase\JWT\JWT;

abstract class Base
{
    protected function checkToken(string $token): object
    {
        try {
            return JWT::decode($token, $_SERVER['SECRET_KEY'], ['HS512']);
        } catch (\UnexpectedValueException $exception) {
            throw new Auth('Forbidden: Accès interdit à cette url.', 403);
        }
    }
}
