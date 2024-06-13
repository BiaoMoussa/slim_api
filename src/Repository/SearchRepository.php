<?php

declare(strict_types=1);

namespace App\Repository;


use App\Exception\SearchException;
use Exception;
use PDO;
use PDOException;

/** */
class SearchRepository extends BaseRepository
{



    public function insert($params = [],  $page = 1, $perPage = 10)
    {
        $produit = (int)$params["produit"];
        $params["position"] = $params["position"] ?? null;
        if (!isset($params["position"])) {
            $QUERY_SEARCH = "SELECT ph.*, com.libelle_commune
            FROM pharmacie_has_produits  php,
             pharmacies  ph, produits pr,
              categories cat,communes com
              WHERE php.id_pharmacie=ph.id_pharmacie 
              AND php.id_produit=pr.id_produit 
              AND pr.id_categorie=cat.id_categorie
              AND ph.id_commune = com.id_commune
              AND php.statut=1
              AND ph.statut=1
              AND pr.id_produit='$produit' ";
        } else {
            $position = $params["position"];
            $QUERY_SEARCH = "SELECT ph.* , com.libelle_commune,
            ROUND(haversine(extract_latitude('$position'),extract_longitude('$position'),
            extract_latitude(ph.coordonnees),extract_longitude(ph.coordonnees))) as distance_km
            FROM pharmacie_has_produits  php,
             pharmacies  ph, produits pr,
              categories cat, communes com
              WHERE php.id_pharmacie=ph.id_pharmacie 
              AND php.id_produit=pr.id_produit 
              AND pr.id_categorie=cat.id_categorie
              AND ph.id_commune = com.id_commune
              AND php.statut=1
              AND ph.statut=1
              AND pr.id_produit='$produit' 
              ORDER BY distance_km ASC
              ";
            
        }

        $results =  $this->database->query($QUERY_SEARCH)->fetchAll(PDO::FETCH_OBJ);
        $nombre_resultat = count($results);

        $searchCount = $this->database->query("SELECT id_recherche FROM recherches")->rowCount();

        $code_recherche = "SEARCH-" . $searchCount + 1;
        $QUERY_INSERT_SEARCH = "INSERT INTO recherches (id_produit,position_recherche, code_recherche)VALUES (:id_produit,:position, :code)";
        $insert_sarch_params = ["id_produit" => $produit, "position" => $params["position"], "code" => $code_recherche];

        $QUERY_INSERT_RESULT = "INSERT INTO resultats_recherche (id_recherche,nombre_resultat) VALUES (:id_recherche,:nombre_resultat)";
        $insert_result_params = ["nombre_resultat" => $nombre_resultat];

        $QUERY_INSERT_LIGNE_RESULT = "INSERT INTO lignes_resultat_recherche(id_resultat, id_pharmacie) VALUES (:id_resultat,:id_pharmacie)";
        $insert_lignes_resultat_params = [];

        // Si la recherche existe on fait une simple consultation
        if ($this->exists("LOWER(code_recherche) = '$code_recherche'")) {
            return  $this->getResultsWithPagination($QUERY_INSERT_SEARCH, $page, $perPage);
        }

        try {


            // Début de la transaction
            $this->database->beginTransaction();

            // Insertion de la recherche
            $insert_search_ok = $this->database->prepare($QUERY_INSERT_SEARCH)->execute($insert_sarch_params);
            if (!$insert_search_ok)
                $this->database->rollBack();

            $id_recherche = $this->database->lastInsertId();
            $insert_result_params["id_recherche"] = $id_recherche;

            // Insertion du nombre de resultat
            $insert_result_ok = $this->database->prepare($QUERY_INSERT_RESULT)->execute($insert_result_params);

            if (!$insert_result_ok)
                $this->database->rollBack();
            $id_resultat = $this->database->lastInsertId();
            $insert_lignes_resultat_params["id_resultat"] = $id_resultat;

            // Insertion des lignes de resultat
            if ($nombre_resultat > 0) {
                foreach ($results as $key => $result) {
                    $insert_lignes_resultat_params["id_pharmacie"] = $result->id_pharmacie;
                    $insert_ligne_result_ok = $this->database->prepare($QUERY_INSERT_LIGNE_RESULT)->execute($insert_lignes_resultat_params);
                    if (!$insert_ligne_result_ok)
                        $this->database->rollBack();
                }
            }
            $this->database->commit();
            return  $this->getResultsWithPagination($QUERY_SEARCH, $page, $perPage);
        } catch (Exception $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function update($id, $params, $critere = 'true')
    {
    }

    public function delete($id, $crietre = "true")
    {
    }

    public function getAll($critere = '', $page = 1, $perPage = 10)
    {
        $QUERY = "SELECT id_user as id, nom_user as nom, prenom_user as prenom, 
        login ,users.id_profil as profil,libelle_profil, id_pharmacie as pharmacie, users.statut
        FROM users, profils WHERE users.id_profil=profils.id_profil AND profils.level=2
          AND $critere";
        return  $this->getResultsWithPagination($QUERY, $page, $perPage);
    }

    public function getOne($id, $critere = 'true')
    {
        $QUERY = "SELECT id_user as id, nom_user as nom, prenom_user as prenom, 
        login ,users.id_profil as profil, libelle_profil, id_pharmacie as pharmacie, users.statut
        FROM users, profils WHERE users.id_profil=profils.id_profil AND profils.level=2
         AND users.id_user='$id'  AND $critere";
        $user = $this->database->query($QUERY)->fetch(PDO::FETCH_ASSOC);
        if (empty($user)) {
            throw new SearchException("Search non trouvé.", 404);
        }

        $idProfil = $user['profil'];
        $subCritera = "id_action IN (SELECT id_action FROM profil_has_actions WHERE id_profil='$idProfil') AND level=2";

        $actions = $this->database->query("SELECT url_action as url, methode 
                                    FROM actions WHERE id_action IN (SELECT id_action FROM profil_has_actions WHERE id_profil='$idProfil')")
            ->fetchAll(PDO::FETCH_ASSOC);
        $menu = $this->database->query("SELECT id_action as id, 
                                    url_action as url, icon, libelle_action as label, description_action as description 
                                FROM actions WHERE (is_menu=1  AND $subCritera) ORDER BY ordre")
            ->fetchAll(PDO::FETCH_ASSOC);

        $dashboardMenu  = $this->database->query("SELECT id_action as id, 
                                    url_action as url, icon, libelle_action as label, description_action as description 
                                FROM actions WHERE url_action='/v1/admin/dashboard'")
            ->fetch(PDO::FETCH_ASSOC);

        $menu = array_merge(array($dashboardMenu), $menu);
        return array_merge($user, ["actions" => $actions, "menu" => $menu]);
    }

    public function changePassowrd($id, $oldPassword, $newPassowrd)
    {
        try {
            $this->getOne($id);
            $user = $this->database->query("SELECT password FROM users WHERE id_user='$id'")->fetch(PDO::FETCH_ASSOC);
            if (!password_verify($oldPassword, $user["password"])) {
                throw new SearchException("Mot de passe incorrect", 403);
            }
            $QUERY = "UPDATE users SET password=:password WHERE id_user=:id";
            $newPassowrd = password_hash($newPassowrd, PASSWORD_BCRYPT);
            $this->database->prepare($QUERY)->execute(["password" => "$newPassowrd", "id" => $id]);
            return true;
        } catch (SearchException $exception) {
            throw $exception;
        }
    }

    public function resetPassword($id)
    {
        try {
            $this->getOne($id);
            $newPassowrd = password_hash("Deafult2024", PASSWORD_BCRYPT);
            $QUERY = "UPDATE users SET password='$newPassowrd' WHERE id_user='$id'";
            $this->database->query($QUERY)->execute();
            return "Le nouveau mot de passe est 'Deafult2024'";
        } catch (SearchException $exception) {
            throw $exception;
        }
    }

    public function exists($critere = 'true'): bool
    {
        return $this->database->query("SELECT id_recherche FROM recherches WHERE $critere")->rowCount() > 0;
    }

    private function profilExists($id, $crietre = "true"): bool
    {
        return $this->database->query("SELECT * FROM profils WHERE id_profil='$id' AND $crietre")->rowCount() > 0;
    }
}
