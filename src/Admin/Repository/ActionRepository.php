<?php

declare(strict_types=1);

namespace App\Admin\Repository;


use App\Admin\Exception\ActionException;
use PDO;
use PDOException;

class ActionRepository  extends BaseRepository
{

    public function __construct()
    {
        $this->database = $GLOBALS["pdo"];
    }
    public function insert($params = [])
    {
        try {
            $params["level"] = 1;
            $libelle_action = $params["libelle"];
            $methode = $params["methode"];
            $url = $params["url"];
            $params["description"] = isset($params["description"]) ? $params["description"] : "";
            if ($this->exists("libelle_action='$libelle_action' AND methode='$methode' AND url_action='$url'")) {
                throw new ActionException("Cette action existe déjà !");
            }
            $QUERY = "INSERT INTO actions (libelle_action, description_action, methode, url_action, level)
                    VALUES (:libelle, :description, :methode,:url,:level)";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($this->database->lastInsertId());
        } catch (ActionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function update($id, $params, $crietre = '')
    {
        try {
            $action = $this->getOne($id);
            $params["level"] = 1;
            $params["id"] = $id;
            $params["description"] =  $params["description"] ?? $action["description"];
            $params["libelle"] =  $params["libelle"] ?? $action["libelle"];
            $params["methode"] =  $params["methode"] ?? $action["methode"];
            $params["url"] =  $params["url"] ?? $action["url"];

            $libelle_action = $params["libelle"];
            $methode = $params["methode"];
            $url = $params["url"];
            if ($libelle_action != $action["libelle"] && $methode != $action["methode"] && $url != $action["url"]) {
                if ($this->exists("libelle_action='$libelle_action' AND methode='$methode' AND url_action='$url'")) {
                    throw new ActionException("Cette action existe déjà !");
                }
            }
            $QUERY = "UPDATE actions 
                    SET libelle_action=:libelle,
                    description_action=:description, 
                    methode=:methode, 
                    url_action=:url,
                    level=:level
                    WHERE id_action=:id";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($id);
        } catch (ActionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function delete($id, $critere = "true")
    {
        $QUERY = "DELETE FROM actions WHERE id_action=$id AND $critere";
        try {
            $this->getOne($id);
            $this->database->query($QUERY)->execute();
        } catch (ActionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function getAll($critere = 'true', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT id_action as id, libelle_action as libelle, methode, 
        url_action as url,description_action as description
        FROM actions WHERE 1 AND $critere";

        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_action as id, libelle_action as libelle, methode, 
        url_action as url,description_action as description 
        FROM actions WHERE id_action=$id AND $critere";
        $action = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($action)) {
            throw new ActionException("Action non trouvée", 404);
        }
        return $action;
    }

    public function exists($critere = 'true'): bool
    {
        $QUERY = "SELECT * FROM actions WHERE  $critere";
        return  $this->database->query($QUERY)->rowCount() > 0;
    }
}
