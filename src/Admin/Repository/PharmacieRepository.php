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
        $code = $this->database->query("SELECT count(id_pharmacie)  FROM pharmacies")->fetchColumn();
        if ($code == 0) {
            $code = 1;
        } else {
            $code += 1;
        }

        $params["code_pharmacie"] = "PHARM_" . $code;
        try {
            if ($this->exists("LOWER(nom_pharmacie)='$nom' || telephone='$telphone'")) {
                throw new PharmacieException("Cette pharmacie existe déjà.");
            }
            $this->database->beginTransaction();

            $QUERY = "INSERT INTO pharmacies(nom_pharmacie,telephone,adresse,coordonnees,code_pharmacie, id_commune,observation,created_by,modified_by)
                VALUES (:nom,:telephone,:adresse,:coordonnees,:code_pharmacie,:commune,:observation,:createdBy,:updatedBy)";
            $this->database->prepare($QUERY)->execute($params);

            $idPharmacie = $this->database->lastInsertId();


            // Association des produits à la pharmacie créée
            $createdBy = $params["createdBy"];
            $updatedBy = $params["updatedBy"];
            $QUERY_MAPPING_PHARMACIE_PRODUIT = "INSERT INTO pharmacie_has_produits (id_pharmacie,id_produit,created_by,modified_by)
            SELECT :id_pharmacie, id_produit,:created_by,:updated_by  FROM produits";
            $mapping_params = ["id_pharmacie" => $idPharmacie, "created_by" => $createdBy, "updated_by" => $updatedBy];
            $this->database->prepare($QUERY_MAPPING_PHARMACIE_PRODUIT)->execute($mapping_params);
            $this->database->commit();
            return $this->getOne($idPharmacie);
        } catch (PharmacieException $pharmacieException) {
            throw $pharmacieException;
        } catch (PDOException $exception) {
            $this->database->rollBack();
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
        $params["commune"] = $params["commune"] ?? $pharmacie["commune"];
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
            $id_commune = $params["commune"];
            if ($pharmacie["id_commune"] != $id_commune && $this->database->query("SELECT id_commune FROM communes WHERE id_commune='$id_commune'")->rowCount() == 0)
                throw new PharmacieException("Commune introuvable", 404);
            $QUERY = "UPDATE pharmacies SET
                        nom_pharmacie=:nom,
                        telephone=:telephone,
                        adresse=:adresse,
                        coordonnees=:coordonnees,
                        id_commune =:commune,
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

    public function syncProduit($id, $params)
    {

        $pharmacie = $this->getOne($id);

        try {
            $this->database->beginTransaction();
            // Association des produits à la pharmacie créée
            $created_by = $params["created_by"];
            $created_at = $params["created_at"];
            $QUERY = "INSERT INTO pharmacie_has_produits (id_pharmacie,id_produit,created_by,created_at)
            SELECT :id_pharmacie, id_produit,:created_by,:created_at FROM produits WHERE id_produit NOT IN (SELECT id_produit FROM pharmacie_has_produits WHERE id_pharmacie = $id)";
            $mapping_params = ["id_pharmacie" => $id, "created_by" => $created_by, "created_at" => $created_at];
            $stmt = $this->database->prepare($QUERY);
            $stmt->execute($mapping_params);
            $rowCount = $stmt->rowCount();
            $this->database->commit();
            return $rowCount;
        } catch (PDOException $exception) {
            $this->database->rollBack();
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
        $QUERY = "SELECT id_pharmacie as id, nom_pharmacie as nom, telephone, adresse, coordonnees, statut, observation, garde, communes.*
        FROM pharmacies, communes WHERE pharmacies.id_commune=communes.id_commune AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getCommunes($critere = 'true', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT *  FROM communes WHERE  $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_pharmacie as id, nom_pharmacie as nom, telephone, adresse, coordonnees, statut, observation, garde,code_pharmacie, communes.* 
        FROM pharmacies, communes WHERE pharmacies.id_commune=communes.id_commune AND id_pharmacie='$id' AND $critere";
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
        if ($admin_exits) {
            throw new PharmacieException("Admin existe déjà.", 400);
        }

        $params["nom"] = $params["nom"] ?? "Nom admin " . $pharmacie["nom"];
        $nom_tab = explode(" ", strtolower($params["nom"]));
        $nom_sans_esapce = join("", $nom_tab);
        $libelle_profile = "profilAdmin-" . strtolower(trim($params["nom"]));
        $params["prenom"] = $params["prenom"] ?? "Prénom admin " . $pharmacie["nom"];
        $params["login"] = $nom_sans_esapce;
        $params["password"] = $params["password"] ?? "Default2024";
        $actions = $this->database->query("SELECT id_action FROM actions WHERE level='2'")->fetchAll(PDO::FETCH_COLUMN); // Récupération des actions de level 2
        try {
            $this->database->beginTransaction();
            $profil["libelle"] = $libelle_profile;
            $profil["createdBy"] = $params["createdBy"];
            $profil["updatedBy"] = $params["updatedBy"];
            $profil["pharmacie"] = $id;
            $insertedProfil = (new ProfilRepository)->insert($profil);
            $idProfil = $this->database->lastInsertId();
            $params["pharmacie"] = $id;
            $params["profil"] = $insertedProfil["id"];
            $admin = (new UserRepository)->insert($params);
            if (count($actions) > 0) {
                (new ProfilRepository)->addActions($idProfil, $actions);
            }
            $this->database->commit();
            return $admin;
        } catch (PharmacieException $exception) {
            $this->database->rollBack();
            throw $exception;
        } catch (PDOException $exception) {
            $this->database->rollBack();
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
            $admin["updatedAt"] = $params["updatedAt"];
            $admin["updatedBy"] = $params["updatedBy"];
            $actions = $this->database->query("SELECT id_action FROM actions WHERE level='2'")->fetchAll(PDO::FETCH_COLUMN); // Récupération des actions de level 2
            if (count($actions) > 0) { // on profite pour mettre à jour la liste des actions.
                (new ProfilRepository)->addActions($admin["profil"], $actions);
            }
            $admin["pharmacie"] = $id;
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
