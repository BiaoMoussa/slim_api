<?php

declare(strict_types=1);

namespace App\Admin\Repository;

use App\Admin\Exception\PharmacieException;
use App\Repository\ProfilRepository;
use App\Repository\UserRepository;
use PDO;
use PDOException;

class PharmacieRepository  extends BaseRepository
{

    public function __construct()
    {
        $this->database = $GLOBALS["pdo"];
    }
    public function insert($params = [])
    {
        $nom = $params["nom"];
        $params["observation"] = $params["observation"] ?? "";
        $params["coordonnees"] = $params["coordonnees"] ?? "";
        $telphone = $params["telephone"];
        try {
            if ($this->exists("LOWER(nom_pharmacie)='$nom' || telephone='$telphone'")) {
                throw new PharmacieException("Cette pharmacie existe déjà.");
            }
            $QUERY = "INSERT INTO pharmacies(nom_pharmacie,telephone,adresse,coordonnees,observation,created_by,modified_by)
                VALUES (:nom,:telephone,:adresse,:coordonnees,:observation,:createdBy,:updatedBy)";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($this->database->lastInsertId());
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function update($id, $params, $critere = 'true')
    {

        $pharmacie = $this->getOne($id);
        $oldTelephone = $pharmacie["telephone"];
        $oldNom = strtolower($pharmacie["nom"]);
        $params["nom"] = $params["nom"] ?? $pharmacie["nom"];
        $params["telephone"] = $params["telephone"] ?? $pharmacie["telephone"];
        $params["adresse"] = $params["adresse"] ?? $pharmacie["adresse"];
        $params["coordonnees"] = $params["coordonnees"] ?? $pharmacie["coordonnees"];
        $params["observation"] = $params["observation"] ?? $pharmacie["observation"];
        $newTelephone = $params["telephone"];
        $newNom = strtolower($params["nom"]);
        $params["id"] = $id;

        try {
            if (strtolower($newNom) != $oldNom) {
                if ($this->exists("LOWER(nom_pharmacie)='$newNom'")) {
                    throw new PharmacieException("Cette pharmacie existe déjà.");
                }
            }
            if ($newTelephone != $oldTelephone) {
                if ($this->exists("telephone='$newTelephone'")) {
                    throw new PharmacieException("Cette pharmacie existe déjà.");
                }
            }
            $QUERY = "UPDATE pharmacies SET
                        nom_pharmacie=:nom,
                        telephone=:telephone,
                        adresse=:adresse,
                        coordonnees=:coordonnees,
                        observation=:observation,
                        modified_at=:updatedAt,
                        modified_by=:updatedBy
                        WHERE id_pharmacie=:id AND $critere
                        ";
            $this->database->prepare($QUERY)->execute($params);
            return $this->getOne($id);
        } catch (PDOException $exception) {
            throw $exception;
        }
    }


    public function setStatus($id, $status)
    {
        try {
            $this->getOne($id);
            $QUERY = "UPDATE pharmacies 
                    SET statut=:status
                    WHERE id_pharmacie=:id";
            $this->database->prepare($QUERY)->execute(["status" => $status, "id" => (int)$id]);
            return $this->getOne($id);
        } catch (PharmacieException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function delete($id, $critere = "true")
    {
        $QUERY = "DELETE FROM pharmacies WHERE id_pharmacie=$id AND $critere";
        try {
            $this->getOne($id);
            $this->database->query($QUERY)->execute();
        } catch (PharmacieException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function getAll($critere = 'true', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT id_pharmacie as id, nom_pharmacie as nom, telephone, adresse, coordonnees, statut, observation, garde
        FROM pharmacies WHERE 1 AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_pharmacie as id, nom_pharmacie as nom, telephone, adresse, coordonnees, statut, observation, garde
        FROM pharmacies WHERE id_pharmacie='$id' AND $critere";
        $pharmacie = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($pharmacie)) {
            throw new PharmacieException("Pharmacie non trouvée.", 404);
        }
        return $pharmacie;
    }
    public function exists($critere = 'true'): bool
    {
        return $this->database->query("SELECT * FROM pharmacies WHERE $critere")->rowCount() > 0;
    }

    public function addAdmin($id, $params)
    {
        $pharmacie = $this->getOne($id);
        $QUERY_FIND_ADMIN = "SELECT * FROM users WHERE id_pharmacie='$id' ORDER BY id_user ASC LIMIT 1";
        $admin_exits = $this->database->query($QUERY_FIND_ADMIN)->rowCount() > 0;
        if($admin_exits) {
            throw new PharmacieException("Admin existe déjà.",400);
        }
      
        $params["nom"] = $params["nom"] ?? "Nom admin " . $pharmacie["nom"];
        $nom_tab = explode(" ",strtolower($params["nom"]));
        $nom_sans_esapce = join("", $nom_tab);
        $libelle_profile = "profilAdmin-" . strtolower(trim($params["nom"]));
        $params["prenom"] = $params["prenom"] ?? "Prénom admin " . $pharmacie["nom"];
        $params["login"] = $nom_sans_esapce;
        $params["password"] = $params["password"] ?? "Default2024";
        $actions = $this->database->query("SELECT id_action FROM actions WHERE level='2'")->fetchAll(PDO::FETCH_COLUMN); // Récupération des actions de level 2
        try {
            $profil["libelle"] = $libelle_profile;
            $profil["createdBy"] = $params["createdBy"];
            $profil["updatedBy"] = $params["updatedBy"];
            $profil["pharmacie"] = $id;
            $insertedProfil = (new ProfilRepository)->insert($profil);
            $idProfil = $this->database->lastInsertId();
            $params["pharmacie"] = $id;
            $params["profil"] = $insertedProfil["id"];
            $admin = (new UserRepository)->insert($params);
            if(count($actions) > 0) {
                (new ProfilRepository)->addActions($idProfil, $actions);
            }
            return $admin;
        } catch (PharmacieException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function updateAdmin($id, $params)
    {
        try {
            $this->getOne($id);
            $admin = $this->getAdmin($id);
            $id = $admin["id"];
            $admin["nom"] = $params["nom"] ?? $admin["nom"];
            $admin["prenom"] = $params["prenom"] ?? $admin["prenom"];
            $admin["login"] = $params["login"] ?? $admin["login"];
            $admin["updatedAt"] = $params["updatedAt"] ;
            $admin["updatedBy"] = $params["updatedBy"] ;
            $actions = $this->database->query("SELECT id_action FROM actions WHERE level='2'")->fetchAll(PDO::FETCH_COLUMN); // Récupération des actions de level 2
            if(count($actions) > 0) { // on profite pour mettre à jour la liste des actions.
                (new ProfilRepository)->addActions($admin["profil"], $actions);
            }
            return (new UserRepository)->update($id, $admin);
        } catch (PharmacieException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    public function getAdmin($id)
    {
        $QUERY_FIND_ADMIN = "SELECT id_user as id, nom_user as nom, prenom_user as prenom, login ,id_profil as profil FROM users WHERE id_pharmacie='$id' ORDER BY id ASC LIMIT 1";
        $admin_exits = $this->database->query($QUERY_FIND_ADMIN)->rowCount() > 0;
        if (!$admin_exits) {
            throw new PharmacieException("Aucun admin trouvé !", 404);
        }
        return $this->database->query($QUERY_FIND_ADMIN)->fetch(PDO::FETCH_ASSOC);
    }
}
