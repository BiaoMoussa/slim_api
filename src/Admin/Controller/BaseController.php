<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Repository\BaseRepository;
use Exception;
use Respect\Validation\Validator;
use Slim\Container;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

abstract class BaseController
{
    protected Container $container;
    protected BaseRepository $repository;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array|object|string|null $message
     */
    protected function jsonResponse(
        Response $response,
        string $status,
        $message,
        int $code
    ): Response {
        $result = [
            'code' => $code,
            'status' => $status,
            'message' => $message,
        ];

        return $response->withJson($result, $code, JSON_PRETTY_PRINT);
    }
    /**
     * @param array|object|string|null $message
     */
    protected function jsonResponseWithData(
        Response $response,
        string $status,
        $message,
        $data,
        int $code
    ): Response {
        $result = [
            'code' => $code,
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];

        return $response->withJson($result, $code, JSON_PRETTY_PRINT);
    }

    /**
     * @param array|object|null $message
     */
    protected function jsonResponseWithoutMessage(
        Response $response,
        string $status,
        $data,
        int $code
    ): Response {
        $result = [
            'code' => $code,
            'status' => $status,
            'data' => $data
        ];

        return $response->withJson($result, $code, JSON_PRETTY_PRINT);
    }

    protected static function isRedisEnabled(): bool
    {
        return filter_var($_SERVER['REDIS_ENABLED'], FILTER_VALIDATE_BOOLEAN);
    }


    protected function required($params = [], $key = "", Exception $exception)
    {
        if (empty($params)) {
            throw new Exception("Veuillez renseigner les paramètres obligatoires.");
        }

        if (!array_key_exists($key, $params)) {
            throw $exception;
        }
    }

    protected function validateEmail(string $email, Exception $exception): string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!Validator::email()->validate($email)) {
            throw new Exception('Invalid email', 400);
        }
        return (string) $email;
    }

    protected function isGeolocationCoordinatesValid(string $geolocation, Exception $exception)
    {
        if (!preg_match('/^([+-]?([1-8]?\d(\.\d+)?|90(\.0+)?)),\s*([+-]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?))$/', $geolocation)) {
            throw $exception;
        }
        return $geolocation;
    }

    /**
     * Moves the uploaded file to the upload directory and assigns it a unique name
     * to avoid overwriting an existing uploaded file.
     *
     * @param string $directory directory to which the file is moved
     * @param UploadedFile $uploadedFile file uploaded file to move
     * @return string filename of moved file
     */
    protected function moveUploadedFile($directory, UploadedFile $uploadedFile, array $inspectedExtensions = [], $customName=null)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        if (!empty($inspectedExtensions) &&!in_array($extension, $inspectedExtensions)) {
            throw new Exception('Extension du fichier incorrect.', 400);
        }
        $basename = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME); 
        $filename = $customName?sprintf('%s.%0.8s', $customName, $extension):sprintf('%s.%0.8s', $basename, $extension) ;

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    protected function validatePhoneNumber(string $phone, Exception $exception)
    {
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            throw $exception;
        }
        return $phone;
    }

    protected function validateSocialMediaLink(string $link, Exception $exception)
    {
        $pattern = '/https?:\/\/(?:www\.)?(facebook|twitter|instagram|linkedin|wa\.me|chat\.whatsapp)\.com\/[^\s]*/i';
        if (!preg_match($pattern, $link)) {
            throw $exception;
        }
        return $link;
    }
}
