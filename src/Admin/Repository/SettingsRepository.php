<?php

declare(strict_types=1);

namespace App\Admin\Repository;


use App\Admin\Exception\SettingsException;
use PDO;
use PDOException;

class SettingsRepository  extends BaseRepository
{

    public function __construct()
    {
        $this->database = $GLOBALS["pdo"];
    }
    public function insert($params = [])
    {
        $this->database->exec("INSERT INTO paramatres(app_name)VALUES('INNAMAGANI')");
    }

    public function update($id, $params, $crietre = '')
    {
    }

    public function delete($id, $critere = "true")
    {
    }

    public function getAll($critere = 'true', $page = 1, $perPage = 10)
    {
    }

    public function getOne($id, $critere = 'true')
    {
    }

    public function find()
    {
        $QUERY = "SELECT * FROM parametres LIMIT 1";
        $config = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($config)) {
            $this->insert();
            $config = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        }
        return $config;
    }

    public function findForPublic()
    {
        $QUERY = "SELECT app_name,slogan,app_logo,adresse,email,
        telephone1,telephone2,telephone3,telephone4,
        lien_facebook,lien_twitter,lien_instagram,
        lien_whatsapp,lien_linkedin,
        lien_tiktok FROM parametres LIMIT 1";
        $config = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($config)) {
            $this->insert();
            $config = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        }
        return $config;
    }

    public function set($params = [])
    {
        try {
            $updateParams = [];
            $config = $this->find();
            $old_app_name = $config['app_name'];
            // Mise à jour des champs
            $updateParams["slogan"] = $params["slogan"] ?? $config["slogan"];
            $updateParams["telephone1"] = $params["telephone1"] ?? $config["telephone1"];
            $updateParams["telephone2"] = $params["telephone2"] ?? $config["telephone2"];
            $updateParams["telephone3"] = $params["telephone3"] ?? $config["telephone3"];
            $updateParams["telephone4"] = $params["telephone4"] ?? $config["telephone4"];
            $updateParams["lien_facebook"] = $params["lien_facebook"] ?? $config["lien_facebook"];
            $updateParams["lien_twitter"] = $params["lien_twitter"] ?? $config["lien_twitter"];
            $updateParams["lien_instagram"] = $params["lien_instagram"] ?? $config["lien_instagram"];
            $updateParams["lien_whatsapp"] = $params["lien_whatsapp"] ?? $config["lien_whatsapp"];
            $updateParams["lien_linkedin"] = $params["lien_linkedin"] ?? $config["lien_linkedin"];
            $updateParams["lien_tiktok"] = $params["lien_tiktok"] ?? $config["lien_tiktok"];
            $updateParams["temps_expiration_session"] = $params["temps_expiration_session"] ?? $config["temps_expiration_session"];
            $updateParams["email"] = $params["email"] ?? $config["email"];
            $updateParams["adresse"] = $params["adresse"] ?? $config["adresse"];
            $updateParams["updated_by"] = $params["updated_by"] ?? $config["updated_by"];
            $updateParams["app_name"] = $params["app_name"];

            $UPDATE_QUERY = "UPDATE parametres 
            SET app_name =:app_name,
            slogan =:slogan,
            telephone1 =:telephone1,
            telephone2 =:telephone2,
            telephone3 =:telephone3,
            telephone4 =:telephone4,
            lien_facebook =:lien_facebook,
            lien_twitter =:lien_twitter,
            lien_instagram =:lien_instagram,
            lien_whatsapp =:lien_whatsapp,
            lien_linkedin =:lien_linkedin,
            lien_tiktok =:lien_tiktok,
            temps_expiration_session =:temps_expiration_session,
            email =:email,
            adresse =:adresse,
            updated_by =:updated_by
            WHERE app_name ='$old_app_name'";
            $this->database->prepare($UPDATE_QUERY)->execute($updateParams);
            return $this->find();
        } catch (SettingsException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }


    public function setLogo($params=['logo' => "logo"])
    {
        try {
            $updateParams = [];
            $config = $this->find();
            $old_app_name = $config['app_name'];
            // Mise à jour des champs
            $updateParams["app_logo"] = $params["logo"];
            $updateParams["updated_by"] = $params["updated_by"] ?? $config["updated_by"];

            $UPDATE_QUERY = "UPDATE parametres 
            SET app_logo =:app_logo,
            updated_by =:updated_by
            WHERE app_name ='$old_app_name'";
            $this->database->prepare($UPDATE_QUERY)->execute($updateParams);
            return $this->find();
        } catch (SettingsException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function exists($critere = 'true'): bool
    {
        return true;
    }
}
