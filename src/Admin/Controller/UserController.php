<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Repository\UserRepository;
use App\Exception\User as ExceptionUser;


use Slim\Http\Request;
use Slim\Http\Response;
use Firebase\JWT\JWT;

class UserController extends BaseController
{


    public function login(Request $request, Response $response): Response
    {
        $input = $request->getParsedBody();
        $data = json_decode((string) json_encode($input), false);
        if (!isset($data->email)) {
            throw new ExceptionUser('The field "email" is required.', 400);
        }
        if (!isset($data->password)) {
            throw new ExceptionUser('The field "password" is required.', 400);
        }

        $userRepository = new UserRepository;

        $user = $userRepository->login($input);

        $token = [
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
        ];

        $token =  JWT::encode($token, $_SERVER['SECRET_KEY']);
        $message = ['Authorization' => 'Bearer ' . $token];

        return $this->jsonResponse($response, 'succes', $message, 200);
    }

    public function getUsers(Request $request, Response $response): Response
    {
        $arg = $request->getQueryParams();
        $userRepository = new UserRepository;
        $encodeJson = $userRepository->getAll($arg);
        return $this->jsonResponse($response, 'success', $encodeJson, 200);
    }
}
