<?php

declare(strict_types=1);

namespace App\Repository;


use App\Admin\Exception\ActionException;
use App\Exception\ProfilException;
use PDO;
use PDOException;

class ProfilRepository  extends BaseRepository
{

    public function __construct()
    {
        $this->database = $GLOBALS["pdo"];
    }
    public function insert($params = [])
    {
        try {
            $params["level"] = 2;
            $libelle_action = $params["libelle"];
            if ($this->exists("LOWER(libelle_profil)='$libelle_action' AND level='2'")) {
                throw new ActionException("Cette action existe déjà !");
            }
            $QUERY = "INSERT INTO profils (libelle_profil, level,id_societe, created_by,updated_by)
                    VALUES (:libelle, :level,:pharmacie, :createdBy, :updatedBy)";
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
            $profil =  $this->getOne($id);
            $params["level"] = 2;
            $params["id"] = $id;
            $params["libelle"] = isset($params["libelle"]) ? $params["libelle"] : $profil["libelle"];
            $libelle_profil = $params["libelle"];
            if ($libelle_profil != $profil["libelle"]) {
                if ($this->exists("LOWER(libelle_profil)='$libelle_profil' AND level='2'")) {
                    throw new ActionException("Cette action existe déjà !");
                }
            }
            
            $QUERY = "UPDATE profils 
                    SET libelle_profil=:libelle,
                    level=:level,
                    updated_by=:updatedBy,
                    updated_at=:updatedAt
                    WHERE id_profil=:id AND id_societe=:pharmacie";
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
        $QUERY = "DELETE FROM profils WHERE id_profil=$id AND $critere";
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
        $QUERY = "SELECT id_profil as id, libelle_profil as libelle, statut FROM profils WHERE 1 AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_profil as id, libelle_profil as libelle, statut FROM profils WHERE id_profil=$id AND $critere";
        $action = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($action)) {
            throw new ActionException("Profil non trouvé", 404);
        }
        return $action;
    }
    public function setStatus($id, $params)
    {
        try {
            $status = $params["status"];
            $pharmacie = $params["pharmacie"];
            $this->getOne($id, "id_societe='$pharmacie'");
            $QUERY = "UPDATE profils 
                    SET statut=:status
                    WHERE id_profil=:id";
            $this->database->prepare($QUERY)->execute(["status" => $status, "id" => (int)$id]);
            return $this->getOne($id, "id_societe='$pharmacie'");
        } catch (ActionException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }
    public function exists($critere = 'true'): bool
    {
        $QUERY = "SELECT * FROM profils WHERE  $critere";
        return  $this->database->query($QUERY)->rowCount() > 0;
    }

    public function getActions($idProfil)
    {
        $QUERY = "SELECT id_action as id, libelle_action as libelle, 
            description_action as description, methode, url_action as url, level 
            FROM actions 
            WHERE id_action IN (SELECT id_action FROM profil_has_actions WHERE id_profil='$idProfil')";
        return  $this->database->query($QUERY)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addActions($idProfil, $actions = [],$critere="true")
    {
        try {
            $this->database->beginTransaction();
            $this->getOne($idProfil, $critere);
            $QUERY = "INSERT INTO profil_has_actions (id_profil,id_action) VALUES (:id_profil,:id_action)";
            if (!empty($actions)) {
                foreach ($actions as $action) {
                    $this->actionsExists($action);
                    if(!$this->relationExists($idProfil, $action)){
                        $query = $this->database->prepare($QUERY);
                        $query->bindParam("id_profil", $idProfil);
                        $query->bindParam("id_action", $action);
                        $query->execute();
                    }
                }
            }
            $this->database->commit();
            return $this->getActions($idProfil);
        } catch (ProfilException $exception) {
            $this->database->rollBack();
            throw $exception;
        } catch (PDOException $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function deleteActions($idProfil, $actions = [],$crietre)
    {
        try {
            $this->database->beginTransaction();
            $this->getOne($idProfil,$crietre);
            $QUERY = "DELETE FROM profil_has_actions WHERE id_action=:id_action AND id_profil=:id_profil";
            if (!empty($actions)) {
                foreach ($actions as $action) {
                    $this->actionsExists($action);
                    $query = $this->database->prepare($QUERY);
                    $query->bindParam("id_profil", $idProfil);
                    $query->bindParam("id_action", $action);
                    $query->execute();
                }
            }
            $this->database->commit();
            return true;
        } catch (ProfilException $exception) {
            $this->database->rollBack();
            throw $exception;
        } catch (PDOException $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function relationExists($idProfil, $idAction)
    {
       return $control_existence_liaison = $this->database
            ->query("SELECT * FROM profil_has_actions WHERE id_action='$idAction' AND id_profil='$idProfil'")
            ->rowCount() > 0;
    }

    private function actionsExists($idAction)
    {
        $control_existence_action = $this->database
            ->query("SELECT * FROM actions WHERE id_action='$idAction' AND level='2'")
            ->rowCount() > 0;
        if (!$control_existence_action) {
            throw new ProfilException("L'action $idAction n'existe pas.");
        }
    }
}
