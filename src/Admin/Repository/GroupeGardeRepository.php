<?php

declare(strict_types=1);

namespace App\Admin\Repository;


use App\Admin\Exception\ActionException;
use App\Admin\Exception\ProfilException;
use PDO;
use PDOException;

class GroupeGardeRepository  extends BaseRepository
{

    public function __construct()
    {
        $this->database = $GLOBALS["pdo"];
    }
    public function insert($params = [])
    {
        try {
            $libelle_groupe = $params["libelle"];
            if ($this->exists("LOWER(libelle_groupe)='$libelle_groupe'")) {
                throw new ActionException("Ce groupe existe déjà !");
            }

            $QUERY = "INSERT INTO groupe_gardes (libelle_groupe, created_by,modified_by)
                    VALUES (:libelle, :createdBy, :updatedBy)";
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
            $groupe =  $this->getOne($id);
            $params["id"] = $id;
            $params["libelle"] = isset($params["libelle"]) ? $params["libelle"] : $groupe["libelle"];
            $libelle_groupe = $params["libelle"];
            if ($libelle_groupe != $groupe["libelle"]) {
                if ($this->exists("LOWER(libelle_groupe)='$libelle_groupe'")) {
                    throw new ActionException("Ce groupe existe déjà !");
                }
            }

            $QUERY = "UPDATE groupe_gardes 
                    SET libelle_groupe=:libelle,
                    modified_by=:updatedBy,
                    modified_at=:updatedAt
                    WHERE id_groupe_garde=:id";
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
        $QUERY = "DELETE FROM groupe_gardes WHERE id_groupe_garde=$id AND $critere";
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
        $QUERY = "SELECT id_groupe_garde as id, libelle_groupe as libelle, statut FROM groupe_gardes WHERE 1 AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_groupe_garde as id, libelle_groupe as libelle, statut FROM groupe_gardes WHERE id_groupe_garde=$id AND $critere";
        $action = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($action)) {
            throw new ActionException("Groupe non trouvé", 404);
        }
        return $action;
    }
    public function setStatus($id, $status)
    {
        try {
            $this->getOne($id);
            $this->database->beginTransaction();
            $QUERY = "UPDATE groupe_gardes 
                    SET statut=:status
                    WHERE id_groupe_garde=:id";
            $QUERY_OTHERS = "UPDATE groupe_gardes 
                    SET statut=:status
                    WHERE id_groupe_garde<>:id";
            $QUERY_PHARMACIES = "UPDATE pharmacies 
                    SET garde=:status
                    WHERE id_pharmacie IN (SELECT id_pharmacie FROM groupe_has_pharmacies WHERE id_groupe=:id)";
            $QUERY_PHARMACIES_OTHERS = "UPDATE pharmacies 
                    SET garde=:status
                    WHERE id_pharmacie IN (SELECT id_pharmacie FROM groupe_has_pharmacies WHERE id_groupe<>:id)";
            if ($status == 0) {
                $statusOthers = 1;
            } else {
                $statusOthers = 0;
            }

            $this->database->prepare($QUERY)->execute(["status" => $status, "id" => (int)$id]);
            $this->database->prepare($QUERY_PHARMACIES)->execute(["status" => $status, "id" => (int)$id]);

            if ($status == 1) {
                $this->database->prepare($QUERY_OTHERS)->execute(["status" => $statusOthers, "id" => (int)$id]);
                $this->database->prepare($QUERY_PHARMACIES_OTHERS)->execute(["status" => $statusOthers, "id" => (int)$id]);
            }

            $this->database->commit();
            return $this->getOne($id);
        } catch (ActionException $exception) {
            $this->database->rollBack();
            throw $exception;
        } catch (PDOException $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }
    public function exists($critere = 'true'): bool
    {
        $QUERY = "SELECT * FROM groupe_gardes WHERE  $critere";
        return  $this->database->query($QUERY)->rowCount() > 0;
    }

    public function getGroupePharmacies($idGroupe)
    {
        $QUERY = "SELECT nom_pharmacie as nom, telephone, adresse, coordonnees, statut, observation,garde
            FROM pharmacies 
            WHERE id_pharmacie IN (SELECT id_pharmacie FROM groupe_has_pharmacies WHERE id_groupe='$idGroupe')";
        return  $this->database->query($QUERY)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addGroupePharmacies($idGroupe, $pharmacies = [])
    {
        try {
            $groupe =  $this->getOne($idGroupe);
            $this->database->beginTransaction();
            $QUERY = "INSERT INTO groupe_has_pharmacies (id_groupe,id_pharmacie) VALUES (:id_groupe,:pharmacie)";
            $QUERY_PHARMACIES = "UPDATE pharmacies 
            SET garde=:status
            WHERE id_pharmacie IN (SELECT id_pharmacie FROM groupe_has_pharmacies WHERE id_groupe=:id)";
            if (!empty($pharmacies)) {
                foreach ($pharmacies as $pharmacie) {
                    $this->pharmacieExists($pharmacie);
                    $this->relationExists($idGroupe, $pharmacie);
                    $query = $this->database->prepare($QUERY);
                    $query->bindParam("id_groupe", $idGroupe);
                    $query->bindParam("pharmacie", $pharmacie);
                    $query->execute();
                }
            }

            if ($groupe["statut"] == 1) {
                $this->database->prepare($QUERY_PHARMACIES)->execute(["status" => 1, "id" => $idGroupe]);
            }

            $this->database->commit();
            return $this->getGroupePharmacies($idGroupe);
        } catch (ProfilException $exception) {
            $this->database->rollBack();
            throw $exception;
        } catch (PDOException $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function deleteGroupePharmacies($idGroupe, $pharmacies = [])
    {
        try {
            $this->database->beginTransaction();
            $this->getOne($idGroupe);
            $QUERY = "DELETE FROM groupe_has_pharmacies WHERE id_groupe=:id_groupe AND id_pharmacie=:id_pharmacie";
            $QUERY_PHARMACIES = "UPDATE pharmacies 
            SET garde=:status
            WHERE id_pharmacie =:id_pharmacie";
            if (!empty($pharmacies)) {
                foreach ($pharmacies as $pharmacie) {
                    $this->pharmacieExists($pharmacie);
                    $query = $this->database->prepare($QUERY);
                    $query->bindParam("id_groupe", $idGroupe);
                    $query->bindParam("id_pharmacie", $pharmacie);
                    $query->execute();
                    $this->database->prepare($QUERY_PHARMACIES)
                    ->execute(["status" => 0, "id_pharmacie" => $pharmacie]);
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

    public function relationExists($idGroupe, $pharmacie)
    {
        $control_existence_liaison = $this->database
            ->query("SELECT * FROM groupe_has_pharmacies WHERE id_pharmacie='$pharmacie' AND id_groupe='$idGroupe'")
            ->rowCount() > 0;
        if ($control_existence_liaison) {
            throw new ProfilException("La pharmacie $pharmacie est déjà dans la liste des phharmacies du groupe $idGroupe.");
        }
    }

    private function pharmacieExists($pharmacie)
    {
        $control_existence_pharmacie = $this->database
            ->query("SELECT * FROM pharmacies WHERE id_pharmacie='$pharmacie'")
            ->rowCount() > 0;
        if (!$control_existence_pharmacie) {
            throw new ProfilException("La pharmacie $pharmacie n'existe pas.");
        }
    }
}
