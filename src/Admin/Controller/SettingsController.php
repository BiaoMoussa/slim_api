<?php

declare(strict_types=1);

namespace App\Admin\Controller;

use App\Admin\Exception\SettingsException;
use App\Admin\Repository\ActionRepository;
use App\Admin\Repository\SettingsRepository;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 *
 */
class SettingsController extends BaseController
{



    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws SettingsException
     */
    public function add(Request $request, Response $response): Response
    {
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws SettingsException
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $params  = $request->getParsedBody();
        $params["updated_by"] = $params["userLogged"]["user"]->id ?? null;
        unset($params["userLogged"]);
        $this->validate($params);
        $config = (new SettingsRepository)->set($params);
        return $this->jsonResponseWithData($response, "success", "Paramètres mis à jour avec succès", $config, 200);
    }
    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getAll(Request $request, Response $response): Response
    {
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws SettingsException
     */
    public function getOne(Request $request, Response $response, array $args): Response
    {
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws SettingsException
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        return $response;
    }

    /**
     * Récupérer la configuration de l'application
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function find(Request $request, Response $response, array $args): Response
    {
        return $this->jsonResponseWithoutMessage($response, "success", (new SettingsRepository())->find(), 200);
    }


    /**
     * Récupérer la configuration de l'application
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function findForPublic(Request $request, Response $response, array $args): Response
    {
        return $this->jsonResponseWithoutMessage($response, "success", (new SettingsRepository())->findForPublic(), 200);
    }


    /**
     * Charger le logo l'application
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function loadLogo(Request $request, Response $response, array $args): Response
    {
        $repository = new SettingsRepository();
         
        $request->getParsedBody();
        $params = [];
        $params["updated_by"] = $params["userLogged"]["user"]->id ?? null;

        $directory = $filepath = __DIR__ . '/../../../public/assets/images/' ;

        $uploadedFiles = $request->getUploadedFiles();

        if ($uploadedFiles) {
            if (!isset($uploadedFiles['logo_file']))
                throw new SettingsException("logo_file est obligatoire", 400);
        } else {
            throw new SettingsException("logo_file est obligatoire", 400);
        }

        // Récupération du fichier uploader
        $uploadedFile = $uploadedFiles['logo_file'];
        try {
            // Si le chargement s'est bien passé
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {


                // Le nom du fichier
                $filename = "logo";


                // Upload du fichier
                $uplodedFilename = $this->moveUploadedFile($directory, $uploadedFile, ['png', 'jpg', 'jpeg'], $filename);

                $params["logo"] = $uplodedFilename;

                if ($repository->setLogo($params)) {
                    return $this->jsonResponse($response, "success", "Logo mise à jour avec succès", 200);
                } else {
                    throw new SettingsException("Chargement du logo échoué.", 400);
                }
            } else {
                throw new SettingsException("Erreur de chargement du fichier.", 400);
            }
        } catch (SettingsException $exception) {
            throw $exception;
        }
    }

    

    /**
     * @param $params
     * @return void
     * @throws SettingsException
     */
    private function validate($params)
    {
        $this->required($params, "app_name", new SettingsException("app_name est obligatoire"));
        $this->validateUpdate($params);
    }


    /**
     * @param $params
     * @return void
     * @throws SettingsException
     */
    private function validateUpdate($params): void
    {
        if (isset($params["app_name"]) && !is_null($params["app_name"])) {
            if (strlen($params["app_name"]) < 3) throw new SettingsException("app_name doit comporter au moins 3 lettres");
        }
        if (isset($params["slogan"]) && !is_null($params["slogan"])) {
            if (strlen($params["slogan"]) < 3 || strlen($params["slogan"]) > 256) throw new SettingsException("slogan doit comporter au moins 3 lettres et 255 lettres au maximum");
        }
        if (isset($params["adresse"]) && !is_null($params["adresse"])) {
            if (strlen($params["adresse"]) < 3 || strlen($params["adresse"]) > 256) throw new SettingsException("adresse doit comporter au moins 3 lettres et 255 lettres au maximum");
        }
        if (isset($params["email"]) && !is_null($params["email"])) {
            $this->validateEmail($params["email"], new SettingsException("Email incorrect.", 403));
        }

        if (isset($params["telephone1"]) && !is_null($params["telephone1"])) {
            $this->validatePhoneNumber($params["telephone1"], new SettingsException("Telephone 1 incorrect.", 403));
        }
        if (isset($params["telephone2"]) && !is_null($params["telephone2"])) {
            $this->validatePhoneNumber($params["telephone2"], new SettingsException("Telephone 2 incorrect.", 403));
        }
        if (isset($params["telephone3"]) && !is_null($params["telephone3"])) {
            $this->validatePhoneNumber($params["telephone3"], new SettingsException("Telephone 3 incorrect.", 403));
        }
        if (isset($params["telephone4"]) && !is_null($params["telephone4"])) {
            $this->validatePhoneNumber($params["telephone4"], new SettingsException("Telephone 4 incorrect.", 403));
        }

        if (isset($params["lien_facebook"]) && !is_null($params["lien_facebook"])) {
            $this->validateSocialMediaLink($params["lien_facebook"], new SettingsException("Le lien facebook est au mauvais format.", 403));
        }

        if (isset($params["lien_twitter"]) && !is_null($params["lien_twitter"])) {
            $this->validateSocialMediaLink($params["lien_twitter"], new SettingsException("Le lien twitter est au mauvais format.", 403));
        }

        if (isset($params["lien_instagram"]) && !is_null($params["lien_instagram"])) {
            $this->validateSocialMediaLink($params["lien_instagram"], new SettingsException("Le lien instagram est au mauvais format.", 403));
        }

        if (isset($params["lien_linkedin"]) && !is_null($params["lien_linkedin"])) {
            $this->validateSocialMediaLink($params["lien_linkedin"], new SettingsException("Le lien linkedin est au mauvais format.", 403));
        }

        if (isset($params["lien_tiktok"]) && !is_null($params["lien_tiktok"])) {
            $this->validateSocialMediaLink($params["lien_tiktok"], new SettingsException("Le lien tiktok est au mauvais format.", 403));
        }

        if (isset($params["lien_whatsapp"]) && !is_null($params["lien_whatsapp"])) {
            $this->validateSocialMediaLink($params["lien_whatsapp"], new SettingsException("Le lien whatsapp est au mauvais format.", 403));
        }

        if (isset($params["temps_expiration_session"]) && !is_null($params["temps_expiration_session"])) {
            if (!is_int($params["temps_expiration_session"])) {
                throw new SettingsException("Temps d'expiration de session doit être un entier.", 403);
            }

            if ($params["temps_expiration_session"] < 15 || $params["temps_expiration_session"] > 120) {
                throw new SettingsException("Temps d'expiration doit être compris entre 15 et 120 miniutes.", 403);
            }
        }
    }
}
